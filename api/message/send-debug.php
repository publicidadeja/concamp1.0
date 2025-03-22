<?php
// Endpoint simplificado para depuração
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/whatsapp-debug.log');

// Iniciar sessão
@session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Definir header JSON
header('Content-Type: application/json');

// Log inicial
error_log('API message/send-debug.php: Iniciada - ' . date('Y-m-d H:i:s'));
error_log('Dados recebidos: ' . json_encode($_POST));

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    // Verificar parâmetros básicos
    if (!isset($_POST['lead_id']) || !isset($_POST['message'])) {
        throw new Exception('Parâmetros lead_id e message são obrigatórios');
    }
    
    // Verificar CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        throw new Exception('Token de segurança inválido');
    }
    
    // Obter dados básicos
    $lead_id = intval($_POST['lead_id']);
    $message = $_POST['message'];
    
    // Obter usuário atual
    $current_user = getCurrentUser();
    if (!$current_user) {
        throw new Exception('Usuário não autenticado');
    }
    
    // Verificar lead
    $lead = getLeadById($lead_id);
    if (!$lead) {
        throw new Exception('Lead não encontrado');
    }
    
    // Resposta de sucesso simplificada para teste
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => 'Este é um endpoint de depuração simplificado',
        'lead_id' => $lead_id,
        'user_id' => $current_user['id'],
        'lead_phone' => $lead['phone'] ?? 'Não disponível',
        'message_preview' => substr($message, 0, 50) . (strlen($message) > 50 ? '...' : ''),
        'sent_message' => [
            'id' => 0,
            'content' => $message,
            'user_name' => $current_user['name'],
            'sent_date' => date('d/m/Y H:i:s')
        ],
        'whatsapp_sent' => false,
        'debug' => true
    ]);
    exit;
    
} catch (Exception $e) {
    error_log('Erro em send-debug.php: ' . $e->getMessage());
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => true
    ]);
    exit;
}