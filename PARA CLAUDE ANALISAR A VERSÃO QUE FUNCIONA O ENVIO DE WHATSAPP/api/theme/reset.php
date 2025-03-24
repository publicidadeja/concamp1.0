<?php
/**
 * API para redefinir as configurações de tema para os valores padrão
 */

// Cabeçalho para conteúdo JSON
header('Content-Type: application/json');

// Iniciar sessão
session_start();

// Incluir dependências
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar se o usuário está autenticado e é admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode([
        'success' => false,
        'error' => 'Acesso negado. Apenas administradores podem redefinir o tema.'
    ]);
    exit;
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Token de segurança inválido.'
    ]);
    exit;
}

try {
    // Valores padrão
    $defaults = [
        'primary_color' => '#0d6efd',
        'secondary_color' => '#6c757d',
        'header_color' => '#ffffff',
        'logo_url' => '',
        'dark_mode' => '0'
    ];
    
    // Atualizar configurações para os valores padrão
    $conn = getConnection();
    $conn->beginTransaction();
    
    foreach ($defaults as $key => $value) {
        updateSetting($key, $value);
    }
    
    // Atualizar a versão do tema para forçar o recarregamento
    updateSetting('theme_version', time());
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Tema redefinido para os valores padrão.',
        'defaults' => $defaults
    ]);
} catch (Exception $e) {
    if (isset($conn) && $conn instanceof PDO) {
        $conn->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao redefinir o tema: ' . $e->getMessage()
    ]);
}
exit;