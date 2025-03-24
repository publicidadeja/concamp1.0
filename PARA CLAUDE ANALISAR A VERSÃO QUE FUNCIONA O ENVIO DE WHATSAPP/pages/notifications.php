<?php
/**
 * Página de notificações
 */

// Iniciar buffer de saída para evitar problemas com headers já enviados
ob_start();

// Verificar autenticação
if (!isLoggedIn()) {
    ob_end_clean(); // Limpar buffer antes de redirecionar
    redirect('index.php?route=login');
}

$current_user = getCurrentUser();
$page_title = 'Notificações';
$body_class = 'notifications-page';

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
$total_pages = ceil($total_notifications / $per_page);

// Ajustar página atual
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

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
                
                <?php if ($total_pages > 1): ?>
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
                            $end_page = min($start_page + 4, $total_pages);
                            
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
                            
                            <?php if ($page < $total_pages): ?>
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