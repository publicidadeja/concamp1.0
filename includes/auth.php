<?php
/**
 * Funções de autenticação
 */

/**
 * Verificar se o usuário está logado
 */
function isLoggedIn() {
    // Verificação normal por sessão
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return true;
    }
    
    // Verificação específica para PWA - detecção ampliada
    $is_pwa = isset($_SERVER['HTTP_USER_AGENT']) && 
             (strpos($_SERVER['HTTP_USER_AGENT'], 'wv') !== false || 
              strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false ||
              isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'cors' ||
              isset($_GET['pwa']) && $_GET['pwa'] === '1');
    
    // Se usuário está acessando notificações a partir do header, tratar como PWA
    $from_header = isset($_GET['route']) && $_GET['route'] === 'notifications' && 
                   isset($_GET['ref']) && $_GET['ref'] === 'header';
                   
    if ($is_pwa || $from_header) {
        error_log("Verificação de autenticação PWA. User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A'));
        
        // Verificar se temos cookies de PWA
        if (isset($_COOKIE['pwa_user_id']) && isset($_COOKIE['pwa_auth_token'])) {
            $user_id = (int)$_COOKIE['pwa_user_id'];
            $token = $_COOKIE['pwa_auth_token'];
            
            // Validar o token no banco de dados
            if (verifyPwaToken($user_id, $token)) {
                $_SESSION['user_id'] = $user_id;
                
                // Agora precisamos buscar os dados do usuário para restaurar a sessão completa
                $conn = getConnection();
                $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE id = :id AND status = 'active'");
                $stmt->execute(['id' => $_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    error_log("Sessão PWA restaurada via cookie para usuário: {$user['name']} ({$user['role']})");
                    return true;
                } else {
                    error_log("Falha ao restaurar sessão PWA: usuário não encontrado ou inativo");
                }
            } else {
                error_log("Falha ao restaurar sessão PWA: token inválido ou expirado");
                // Limpar cookies inválidos
                setcookie('pwa_user_id', '', time() - 3600, "/");
                setcookie('pwa_auth_token', '', time() - 3600, "/");
            }
        }
        
        error_log("Verificação PWA falhou: Sem cookies válidos");
    }
    
    return false;
}

/**
 * Obter ID do usuário logado
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obter dados do usuário logado
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Verificar se o usuário logado é administrador
 */
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

/**
 * Verificar se o usuário logado é gerente
 */
function isManager() {
    $user = getCurrentUser();
    return $user && ($user['role'] === 'manager' || $user['role'] === 'admin');
}

/**
 * Fazer login
 */
function login($email, $password) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email AND status = 'active'");
    $stmt->execute(['email' => $email]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Iniciar sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        
        // Verificar se estamos em um dispositivo móvel ou PWA
        $is_mobile = isset($_SERVER['HTTP_USER_AGENT']) && 
                    (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false || 
                     strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false || 
                     strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false);
        
        $is_pwa = isset($_SERVER['HTTP_USER_AGENT']) && 
                 (strpos($_SERVER['HTTP_USER_AGENT'], 'wv') !== false || 
                  isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'cors');
        
        // Para dispositivos móveis ou PWA, sempre definir cookies persistentes
        if ($is_mobile || $is_pwa || isset($_GET['pwa'])) {
            // Gerar token para PWA/mobile
            $token = bin2hex(random_bytes(32));
            $expires_in = 86400 * 30; // 30 dias em segundos
            
            // Salvar token no banco de dados
            if (savePwaToken($user['id'], $token, $expires_in)) {
                // Definir cookies com HttpOnly e Secure para maior segurança
                $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
                setcookie('pwa_user_id', $user['id'], [
                    'expires' => time() + $expires_in,
                    'path' => "/",
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                setcookie('pwa_auth_token', $token, [
                    'expires' => time() + $expires_in,
                    'path' => "/",
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                
                error_log("Login: Cookies PWA definidos para usuário {$user['id']} ({$user['role']})");
            } else {
                error_log("Erro ao salvar token PWA para usuário {$user['id']}");
            }
        }
        
        return true;
    }
    
    return false;
}

/**
 * Fazer logout
 */
function logout() {
    // Limpar variáveis de sessão
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_role']);
    
    // Limpar cookies de PWA
    if (isset($_COOKIE['pwa_user_id'])) {
        setcookie('pwa_user_id', '', time() - 3600, "/", "", false, true);
    }
    
    if (isset($_COOKIE['pwa_auth_token'])) {
        setcookie('pwa_auth_token', '', time() - 3600, "/", "", false, true);
    }
    
    // Destruir a sessão
    session_destroy();
    
    return true;
}

/**
 * Verificar se o usuário tem permissão para acessar uma página
 * NOTA: Esta função foi removida daqui e mantida apenas em functions.php
 * para evitar duplicação.
 */
// function hasPermission($required_role) { ... }

/**
 * Verificar se um e-mail já está cadastrado
 */
function emailExists($email) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    
    return $stmt->rowCount() > 0;
}

/**
 * Criar um novo usuário
 */
function createUser($name, $email, $password, $role = 'seller') {
    $conn = getConnection();
    
    // Verificar se o e-mail já está em uso
    if (emailExists($email)) {
        return [
            'success' => false,
            'error' => 'O e-mail já está em uso.'
        ];
    }
    
    // Criptografar a senha
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Inserir usuário
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
    $result = $stmt->execute([
        'name' => $name,
        'email' => $email,
        'password' => $password_hash,
        'role' => $role
    ]);
    
    if ($result) {
        return [
            'success' => true,
            'user_id' => $conn->lastInsertId()
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Erro ao criar usuário.'
    ];
}

/**
 * Atualizar um usuário existente
 * NOTA: Esta função foi removida daqui e mantida apenas em functions.php
 * para evitar duplicação.
 */
// function updateUser($id, $data) { ... }

/**
 * Obter um usuário pelo ID
 * NOTA: Esta função foi removida daqui e mantida apenas em functions.php
 * para evitar duplicação.
 */
// function getUserById($id) { ... }

/**
 * Função removida:
 * Obter lista de usuários
 * NOTA: Esta função foi movida para functions.php para evitar duplicação.
 */
// function getUsers($filters = []) {...}

/**
 * Atualizar senha do usuário
 */
function updatePassword($user_id, $current_password, $new_password) {
    $conn = getConnection();
    
    // Verificar senha atual
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($current_password, $user['password'])) {
        return [
            'success' => false,
            'error' => 'Senha atual incorreta.'
        ];
    }
    
    // Atualizar senha
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
    $result = $stmt->execute([
        'id' => $user_id,
        'password' => $password_hash
    ]);
    
    if ($result) {
        return [
            'success' => true
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Erro ao atualizar senha.'
    ];
}

/**
 * Gerar token para recuperação de senha
 */
function generatePasswordResetToken($email) {
    $conn = getConnection();
    
    // Verificar se o e-mail existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND status = 'active'");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return [
            'success' => false,
            'error' => 'E-mail não encontrado ou usuário inativo.'
        ];
    }
    
    // Gerar token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Remover tokens antigos
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user['id']]);
    
    // Inserir novo token
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
    $result = $stmt->execute([
        'user_id' => $user['id'],
        'token' => $token,
        'expires_at' => $expires_at
    ]);
    
    if ($result) {
        return [
            'success' => true,
            'token' => $token,
            'user_id' => $user['id']
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Erro ao gerar token.'
    ];
}

/**
 * Verificar token de recuperação de senha
 */
function verifyPasswordResetToken($token) {
    $conn = getConnection();
    
    $stmt = $conn->prepare("
        SELECT pr.user_id, u.email 
        FROM password_resets pr 
        JOIN users u ON pr.user_id = u.id 
        WHERE pr.token = :token AND pr.expires_at > NOW() AND u.status = 'active'
    ");
    $stmt->execute(['token' => $token]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Redefinir senha com token
 */
function resetPasswordWithToken($token, $new_password) {
    $conn = getConnection();
    
    // Verificar token
    $token_data = verifyPasswordResetToken($token);
    
    if (!$token_data) {
        return [
            'success' => false,
            'error' => 'Token inválido ou expirado.'
        ];
    }
    
    // Atualizar senha
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
    $result = $stmt->execute([
        'id' => $token_data['user_id'],
        'password' => $password_hash
    ]);
    
    if ($result) {
        // Remover token usado
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = :token");
        $stmt->execute(['token' => $token]);
        
        return [
            'success' => true
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Erro ao redefinir senha.'
    ];
}
?>
