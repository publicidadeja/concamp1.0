<?php
// Suprimir todos os avisos e erros - apenas mostrar no log
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar sessão e incluir arquivos necessários
@session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Log para depuração
error_log("API Lead/Update-Status: Início da execução - " . date('Y-m-d H:i:s'));

// Definir header JSON no início do arquivo
header('Content-Type: application/json');

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido. Use POST.');
    }

    // Verificar e obter parâmetros
    $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
    $status = isset($_POST['status']) ? sanitize($_POST['status']) : '';
    
    if (!$lead_id) {
        throw new Exception('ID do lead não fornecido.');
    }

    if (!in_array($status, ['new', 'contacted', 'negotiating', 'converted', 'lost'])) {
        throw new Exception('Status inválido.');
    }

    // Verificar CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        throw new Exception('Token de segurança inválido.');
    }

    // Atualizar status
    $result = updateLeadStatus($lead_id, $status);
    
    if (!$result) {
        throw new Exception('Erro ao atualizar status.');
    }

    // Retornar sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Status atualizado com sucesso.',
        'status' => $status
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}