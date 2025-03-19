<?php
/**
 * API para teste de envio de mensagem via WhatsApp
 */

// Carregar configurações
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Verificar se o usuário está autenticado e é administrador
session_start();
if (!isLoggedIn() || !isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

// Obter parâmetros
$phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
$message = isset($_POST['message']) ? $_POST['message'] : '';

// Validar parâmetros
if (empty($phone) || empty($message)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
    exit;
}

// Formatar número de telefone
$phone = formatPhone($phone);

// Enviar mensagem via WhatsApp
$result = sendWhatsAppMessage($phone, $message);

// Retornar resultado
header('Content-Type: application/json');
echo json_encode($result);
exit;
?>
