<?php
/**
 * API para atualizar status de um lead
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
$status = isset($_POST['status']) ? sanitize($_POST['status']) : '';

// Validar parâmetros
if (!$lead_id || !in_array($status, ['new', 'contacted', 'negotiating', 'converted', 'lost'])) {
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

// Verificar permissão (apenas admin ou vendedor atribuído pode atualizar)
if (!$is_admin && $lead['seller_id'] != $user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Você não tem permissão para atualizar este lead']);
    exit;
}

// Atualizar status
$result = updateLeadStatus($lead_id, $status);

if ($result) {
    // Registrar mudança na timeline
    $content = "Status alterado para: ";
    
    switch ($status) {
        case 'new':
            $content .= "Novo";
            break;
        case 'contacted':
            $content .= "Contatado";
            break;
        case 'negotiating':
            $content .= "Negociando";
            break;
        case 'converted':
            $content .= "Convertido";
            break;
        case 'lost':
            $content .= "Perdido";
            break;
    }
    
    addFollowUp($lead_id, $user_id, 'note', $content);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar status']);
}
exit;
