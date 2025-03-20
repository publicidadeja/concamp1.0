<?php
/**
 * API para marcar tarefa como concluída
 */

// Suprimir todos os avisos e erros
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar sessão e incluir arquivos necessários
@session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Log para depuração
error_log("API Task/Complete: Início da execução - " . date('Y-m-d H:i:s'));

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Verificar CSRF token
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Token inválido']);
    exit;
}

// Obter parâmetros
$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

// Validar parâmetros
if (!$task_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
    exit;
}

// Obter usuário atual
$current_user = getCurrentUser();
$user_id = $current_user['id'];
$is_admin = isAdmin();

// Obter dados da tarefa
$conn = getConnection();
$stmt = $conn->prepare("
    SELECT f.*, l.seller_id
    FROM follow_ups f
    JOIN leads l ON f.lead_id = l.id
    WHERE f.id = :id AND f.type = 'task'
");
$stmt->execute(['id' => $task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Tarefa não encontrada']);
    exit;
}

// Verificar permissão (apenas admin, criador da tarefa ou vendedor atribuído pode concluir)
if (!$is_admin && $task['user_id'] != $user_id && $task['seller_id'] != $user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Você não tem permissão para concluir esta tarefa']);
    exit;
}

// Concluir tarefa
$result = completeTask($task_id);

if ($result) {
    // Adicionar nota de conclusão
    addFollowUp(
        $task['lead_id'], 
        $user_id, 
        'note', 
        "Tarefa concluída: " . $task['content']
    );
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erro ao concluir tarefa']);
}
exit;
