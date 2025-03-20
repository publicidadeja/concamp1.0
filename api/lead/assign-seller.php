<?php
/**
 * API para atribuir vendedor a um lead
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
error_log("API Lead/Assign-Seller: Início da execução - " . date('Y-m-d H:i:s'));

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
$seller_id = isset($_POST['seller_id']) ? intval($_POST['seller_id']) : 0;

// Validar parâmetros
if (!$lead_id || !$seller_id) {
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

// Verificar permissão (apenas admin pode atribuir vendedor)
if (!$is_admin && $lead['seller_id'] != $user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Você não tem permissão para atribuir vendedor']);
    exit;
}

// Verificar se o vendedor existe
$seller = getUserById($seller_id);

if (!$seller) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Vendedor não encontrado']);
    exit;
}

// Atualizar lead com o novo vendedor
$result = updateLeadStatus($lead_id, $lead['status'], $seller_id);

if ($result) {
    // Registrar mudança na timeline
    $content = "Lead atribuído para vendedor: " . $seller['name'];
    
    addFollowUp($lead_id, $user_id, 'note', $content);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'seller_name' => $seller['name']
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erro ao atribuir vendedor']);
}
exit;
