<?php
/**
 * API para marcar notificação como lida
 */

// Verificar se é uma solicitação AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Definir cabeçalho de resposta JSON
header('Content-Type: application/json');

// Verificar autenticação
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'error' => 'Não autorizado'
    ]);
    exit;
}

$current_user = getCurrentUser();

// Verificar se a tabela notifications existe
$conn = getConnection();
$stmt = $conn->prepare("SHOW TABLES LIKE 'notifications'");
$stmt->execute();
$table_exists = $stmt->rowCount() > 0;

if (!$table_exists) {
    echo json_encode([
        'success' => false,
        'error' => 'A tabela de notificações não existe'
    ]);
    exit;
}

// Verificar parâmetros
if (empty($_POST['notification_id']) || empty($_POST['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Parâmetros inválidos'
    ]);
    exit;
}

$notification_id = (int) $_POST['notification_id'];
$user_id = (int) $_POST['user_id'];

// Verificar se é o próprio usuário
if ($current_user['id'] != $user_id) {
    echo json_encode([
        'success' => false,
        'error' => 'Acesso negado'
    ]);
    exit;
}

// Marcar notificação como lida
$result = markNotificationAsRead($notification_id, $user_id);

// Retornar resultado
echo json_encode([
    'success' => $result,
    'error' => $result ? null : 'Erro ao marcar notificação como lida'
]);
exit;