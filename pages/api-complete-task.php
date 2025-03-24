<?php
/**
 * API para marcar tarefa como concluída
 */

// Incluir arquivos necessários
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Nota: O cabeçalho Content-Type já foi definido em index.php

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

// Verificar se a tarefa existe (permitir que qualquer usuário conclua qualquer tarefa)
// Este comportamento pode ser ajustado conforme a política da empresa
$stmt = $conn->prepare("
    SELECT f.*, l.name as lead_name 
    FROM follow_ups f
    JOIN leads l ON f.lead_id = l.id
    WHERE f.id = :id AND f.type = 'task'
");
$stmt->execute([
    'id' => $task_id
]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    echo json_encode([
        'success' => false,
        'error' => 'Tarefa não encontrada'
    ]);
    exit;
}

// Verificar se o usuário tem permissão para completar a tarefa
// Administradores podem completar quaisquer tarefas, outros usuários apenas as suas
if (!isAdmin() && $task['user_id'] != $current_user['id']) {
    echo json_encode([
        'success' => false,
        'error' => 'Você não tem permissão para completar esta tarefa'
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