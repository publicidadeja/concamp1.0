<?php
/**
 * Funções de autenticação
 */

/**
 * Verificar se o usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
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
 * Obter lista de usuários
 */
function getUsers($filters = []) {
    $conn = getConnection();
    
    $sql = "SELECT id, name, email, role, status, created_at FROM users WHERE 1=1";
    $params = [];
    
    if (!empty($filters['role'])) {
        $sql .= " AND role = :role";
        $params['role'] = $filters['role'];
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND status = :status";
        $params['status'] = $filters['status'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (name LIKE :search OR email LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }
    
    $sql .= " ORDER BY name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
