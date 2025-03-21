<?php
/**
 * API para marcar tarefa como concluída
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

// Verificar se a tabela follow_ups existe
$conn = getConnection();
$stmt = $conn->prepare("SHOW TABLES LIKE 'follow_ups'");
$stmt->execute();
$table_exists = $stmt->rowCount() > 0;

if (!$table_exists) {
    echo json_encode([
        'success' => false,
        'error' => 'A tabela de tarefas não existe'
    ]);
    exit;
}

// Verificar parâmetros
if (empty($_POST['task_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Parâmetros inválidos'
    ]);
    exit;
}

$task_id = (int) $_POST['task_id'];

// Verificar se a tarefa existe e pertence ao usuário atual
$stmt = $conn->prepare("
    SELECT * FROM follow_ups
    WHERE id = :id AND user_id = :user_id AND type = 'task'
");
$stmt->execute([
    'id' => $task_id,
    'user_id' => $current_user['id']
]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    echo json_encode([
        'success' => false,
        'error' => 'Tarefa não encontrada ou não pertence a este usuário'
    ]);
    exit;
}

// Marcar tarefa como concluída
$result = completeTask($task_id);

if ($result) {
    // Adicionar nota de conclusão ao lead
    $lead_id = $task['lead_id'];
    $content = "Tarefa concluída: " . $task['content'];
    
    // Adicionar acompanhamento de tipo 'note'
    addLeadFollowup($lead_id, $current_user['id'], 'note', $content);
    
    echo json_encode([
        'success' => true,
        'message' => 'Tarefa marcada como concluída'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao marcar tarefa como concluída'
    ]);
}
exit;