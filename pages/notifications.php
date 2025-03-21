<?php
/**
 * Página de notificações
 */

// Iniciar buffer de saída para evitar problemas com headers já enviados
ob_start();

// Adicionar meta-tags para evitar cache nesta página
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Verificar autenticação
if (!isLoggedIn()) {
    // Verificar se temos cookies de autenticação PWA
    if (isset($_COOKIE['pwa_user_id']) && isset($_COOKIE['pwa_auth_token'])) {
        // Tentar renovar a sessão com base nos cookies
        $user_id = (int)$_COOKIE['pwa_user_id'];
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE id = :id AND status = 'active'");
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            // Agora estamos logados
            
            // Registrar informações de depuração para ajudar no diagnóstico
            error_log("PWA Auth: Restaurada sessão para usuário ID {$user['id']}, Nome: {$user['name']}, Perfil: {$user['role']}");
        } else {
            error_log("PWA Auth: Não foi possível encontrar usuário com ID {$user_id} ou está inativo");
        }
    } else {
        // Verificar se são vendedores acessando via dispositivo mobile que não está salvando cookies
        $is_mobile = isset($_SERVER['HTTP_USER_AGENT']) && 
                    (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false || 
                     strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false || 
                     strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false);
                     
        if ($is_mobile && isset($_GET['ref']) && $_GET['ref'] === 'header' && isset($_GET['role'])) {
            error_log("Detecção de acesso mobile: " . $_SERVER['HTTP_USER_AGENT'] . " - Role: " . $_GET['role']);
            
            // Verificar qualquer perfil de usuário (vendedor ou admin)
            if (isset($_GET['pwa']) && $_GET['pwa'] === '1') {
                $userRole = $_GET['role'] ?? '';
                error_log("⚠️ MODO DEMO: Tentando login automático para perfil: " . $userRole);
                
                // Apenas permitir auto-login para vendedor e admin
                if ($userRole === 'seller' || $userRole === 'admin') {
                    $conn = getConnection();
                    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE role = :role AND status = 'active' ORDER BY id ASC LIMIT 1");
                    $stmt->bindParam(':role', $userRole);
                    $stmt->execute();
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        // Criar uma sessão para o usuário
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name']; 
                        $_SESSION['user_role'] = $user['role'];
                        
                        // Definir cookies SEM HttpOnly para permitir acesso via JavaScript
                        // e SEM Secure para permitir HTTP (apenas para testes)
                        $token = bin2hex(random_bytes(32));
                        setcookie('pwa_user_id', $user['id'], time() + (86400 * 30), "/", "", false, false);
                        setcookie('pwa_auth_token', $token, time() + (86400 * 30), "/", "", false, false);
                        
                        error_log("⚠️ MODO DEMO: Auto-login para {$user['role']} ID {$user['id']}, {$user['name']}");
                    } else {
                        error_log("Não foi possível encontrar usuário com perfil {$userRole}");
                    }
                }
            }
        }
    }
    
    // Verificar novamente se o login foi bem-sucedido
    if (!isLoggedIn()) {
        ob_end_clean(); // Limpar buffer antes de redirecionar
        redirect('index.php?route=login');
    }
}

$current_user = getCurrentUser();
$page_title = 'Notificações';
$body_class = 'notifications-page';

// Verificar se estamos em uma aplicação PWA
$is_pwa = isset($_SERVER['HTTP_USER_AGENT']) && 
          (strpos($_SERVER['HTTP_USER_AGENT'], 'wv') !== false || 
           isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'cors');

// Registrar informações completas de diagnóstico para esta requisição
$ref = $_GET['ref'] ?? 'n/a';
$role = $_GET['role'] ?? 'n/a';
$pwa = $_GET['pwa'] ?? 'n/a';
$is_mobile = isset($_SERVER['HTTP_USER_AGENT']) && 
            (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false || 
             strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false || 
             strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false);

error_log("🔍 DIAGNÓSTICO NOTIFICAÇÕES: ref=$ref, role=$role, pwa=$pwa, mobile=" . ($is_mobile ? 'sim' : 'não') . 
          ", user_id=" . ($_SESSION['user_id'] ?? 'não definido') . 
          ", cookie_user_id=" . ($_COOKIE['pwa_user_id'] ?? 'não definido'));

// Verificar se é uma solicitação de reautenticação
$needs_reauth = !isLoggedIn() && ($is_pwa || $is_mobile);
if ($needs_reauth) {
    error_log("🔄 Tentando reautenticação para notificações...");
    
    // Verificar se temos uma sessão de usuário salva em cookie local
    if (isset($_COOKIE['pwa_user_id']) && isset($_COOKIE['pwa_auth_token'])) {
        // Tentar reautenticar com cookies
        $user_id = (int)$_COOKIE['pwa_user_id'];
        $_SESSION['user_id'] = $user_id;
        error_log("🔄 Reautenticação via cookies realizada: user_id=$user_id");
    }
    // A autenticação principal já é feita no index.php para casos onde não há cookies
}

// Marcar todas as notificações como visualizadas ao acessar esta página
if (isset($_GET['mark_all_read']) && $_GET['mark_all_read'] == 1) {
    markAllNotificationsAsRead($current_user['id']);
    setFlashMessage('success', 'Todas as notificações foram marcadas como lidas.');
    ob_end_clean(); // Limpar buffer antes de redirecionar
    redirect('index.php?route=notifications');
}

// Configuração de paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

// Obter todas as notificações do usuário com paginação
$conn = getConnection();

// Verificar se a tabela notifications existe
$stmt = $conn->prepare("SHOW TABLES LIKE 'notifications'");
$stmt->execute();
$table_exists = $stmt->rowCount() > 0;

if (!$table_exists) {
    ob_end_clean(); // Limpar buffer antes de redirecionar
    redirect('index.php?route=create-notifications-table');
}

// Contar total de notificações
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id");
$stmt->execute(['user_id' => $current_user['id']]);
$total_notifications = $stmt->fetchColumn();

// Calcular total de páginas
$pages = ceil($total_notifications / $per_page);

// Ajustar página atual
if ($page < 1) $page = 1;
if ($page > $pages && $pages > 0) $page = $pages;

// Obter notificações para a página atual
$offset = ($page - 1) * $per_page;
$stmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = :user_id 
    ORDER BY created_at DESC 
    LIMIT :offset, :limit
");
$stmt->bindValue(':user_id', $current_user['id'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agora é seguro incluir o cabeçalho após todas as verificações e possíveis redirecionamentos
include_once('templates/header.php');
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Notificações</h1>
        
        <?php if ($total_notifications > 0): ?>
        <a href="<?php echo url('index.php?route=notifications&mark_all_read=1'); ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-check-double me-1"></i> Marcar todas como lidas
        </a>
        <?php endif; ?>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (count($notifications) > 0): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $notification): ?>
                    <a href="<?php echo !empty($notification['action_url']) ? url($notification['action_url']) : '#'; ?>" 
                       class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'list-group-item-light'; ?>"
                       data-notification-id="<?php echo $notification['id']; ?>"
                       data-user-id="<?php echo $current_user['id']; ?>">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="notification-icon">
                                    <i class="<?php echo $notification['icon']; ?> text-<?php echo $notification['color']; ?>"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?php echo $notification['title']; ?></h5>
                                    <small class="text-muted"><?php echo formatRelativeTime(strtotime($notification['created_at'])); ?></small>
                                </div>
                                <p class="mb-1"><?php echo $notification['message']; ?></p>
                                <small>
                                    <?php 
                                    $type_labels = [
                                        'lead' => 'Lead',
                                        'task' => 'Tarefa',
                                        'message' => 'Mensagem',
                                        'system' => 'Sistema'
                                    ];
                                    $type = $notification['type'];
                                    echo isset($type_labels[$type]) ? $type_labels[$type] : 'Notificação';
                                    ?>
                                </small>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                            <div class="ms-2">
                                <span class="badge bg-primary rounded-pill">Nova</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($pages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Navegação de notificações">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo url("index.php?route=notifications&page=" . ($page - 1)); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php
                            // Exibir no máximo 5 páginas
                            $start_page = max(1, $page - 2);
                            $end_page = min($start_page + 4, $pages);
                            
                            if ($end_page - $start_page < 4) {
                                $start_page = max(1, $end_page - 4);
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo url("index.php?route=notifications&page=$i"); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo url("index.php?route=notifications&page=" . ($page + 1)); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <h5>Nenhuma notificação</h5>
                    <p class="text-muted">Você não possui notificações no momento.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Mark notifications as read when clicked
document.querySelectorAll('[data-notification-id]').forEach(item => {
    item.addEventListener('click', function() {
        const notificationId = this.getAttribute('data-notification-id');
        const userId = this.getAttribute('data-user-id');
        
        // Remove highlight class
        this.classList.remove('list-group-item-light');
        
        // Remove 'Nova' badge
        const badge = this.querySelector('.badge');
        if (badge) {
            badge.style.display = 'none';
        }
        
        // Send AJAX request to mark as read
        fetch('index.php?route=api-mark-notification-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `notification_id=${notificationId}&user_id=${userId}`
        });
    });
});
</script>

<?php
// Incluir rodapé
include_once('templates/footer.php');

// Finalizar o buffer de saída
if (ob_get_level() > 0) {
    ob_end_flush();
}
?>