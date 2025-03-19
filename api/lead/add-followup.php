<?php
/**
 * API para adicionar follow-up (nota, tarefa ou lembrete) a um lead
 */

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
$lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
$type = isset($_POST['type']) ? sanitize($_POST['type']) : '';
$content = isset($_POST['content']) ? sanitize($_POST['content']) : '';
$due_date = isset($_POST['due_date']) && !empty($_POST['due_date']) ? sanitize($_POST['due_date']) : null;

// Validar parâmetros
if (!$lead_id || !in_array($type, ['note', 'task', 'reminder']) || empty($content)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
    exit;
}

// Obter usuário atual
$current_user = getCurrentUser();
$user_id = $current_user['id'];
$is_admin = isAdmin();

// Obter dados do lead
$lead = getLeadById($lead_id);

if (!$lead) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Lead não encontrado']);
    exit;
}

// Verificar permissão (apenas admin ou vendedor atribuído pode adicionar follow-up)
if (!$is_admin && $lead['seller_id'] != $user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Você não tem permissão para adicionar follow-up para este lead']);
    exit;
}

// Adicionar follow-up
$followup_id = addFollowUp($lead_id, $user_id, $type, $content, $due_date);

if ($followup_id) {
    // Obter dados do follow-up para retornar
    $followup = [
        'id' => $followup_id,
        'type' => $type,
        'content' => $content,
        'due_date' => $due_date,
        'user_name' => $current_user['name'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'followup' => $followup
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erro ao adicionar follow-up']);
}
exit;
