<?php
/**
 * API para obter uma landing page de exemplo
 */

// Incluir configurações e funções
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar se o usuário está autenticado e é administrador
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado.']);
    exit;
}

// Buscar um vendedor que tenha landing page configurada
$conn = getConnection();
$stmt = $conn->prepare("SELECT id, landing_page_name FROM users WHERE role = 'seller' AND landing_page_name IS NOT NULL AND landing_page_name != '' AND status = 'active' LIMIT 1");
$stmt->execute();
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

if ($seller) {
    // Gerar URL para a landing page
    $landingPageUrl = url('index.php?route=lp/' . $seller['landing_page_name']);
    
    echo json_encode(['success' => true, 'url' => $landingPageUrl]);
} else {
    echo json_encode(['success' => false, 'error' => 'Nenhum vendedor com landing page encontrado.']);
}