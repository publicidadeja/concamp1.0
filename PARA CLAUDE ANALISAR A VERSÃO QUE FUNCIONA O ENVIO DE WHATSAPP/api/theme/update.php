<?php
/**
 * API para atualizar a versão do tema e forçar o recarregamento do CSS
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
        'error' => 'Acesso negado. Apenas administradores podem atualizar o tema.'
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
    // Atualizar a versão do tema no banco de dados
    $new_version = time();
    updateSetting('theme_version', $new_version);
    
    echo json_encode([
        'success' => true,
        'version' => $new_version,
        'message' => 'Tema atualizado com sucesso.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao atualizar o tema: ' . $e->getMessage()
    ]);
}
exit;