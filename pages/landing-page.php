<?php
/**
 * Template para Landing Pages de Vendedores
 */

// Obter o nome da landing page da URL
$landing_page_name = $_GET['name'] ?? '';

// Buscar dados do vendedor
$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM users WHERE landing_page_name = :name AND role = 'seller' AND status = 'active'");
$stmt->execute(['name' => $landing_page_name]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seller) {
    // Vendedor não encontrado ou landing page inválida
    include_once __DIR__ . '/404.php';
    exit;
}

// Dados do vendedor para a landing page
$seller_name = $seller['name'];
$seller_id = $seller['id'];
$seller_whatsapp_token = $seller['whatsapp_token']; // Usado na action do formulário

// Título da página (personalizado com o nome do vendedor)
$page_title = "Simulador de Contrato Premiado - $seller_name";
$body_class = "simulator-page";

// Incluir o conteúdo do simulador (o mesmo de pages/simulador.php, mas com a action alterada)
include __DIR__ . '/simulador-content.php';
?>
