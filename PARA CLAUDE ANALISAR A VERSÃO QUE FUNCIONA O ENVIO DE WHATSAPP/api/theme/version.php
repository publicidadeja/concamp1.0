<?php
/**
 * API para verificar a versão atual do tema
 */

// Cabeçalho para conteúdo JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Iniciar sessão
session_start();

// Incluir dependências
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Verificar se o usuário está autenticado
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'error' => 'Acesso negado. Você precisa estar logado para acessar esta funcionalidade.'
    ]);
    exit;
}

// Obter a versão atual do tema
$theme_version = getSetting('theme_version') ?: time();

// Retornar a versão em formato JSON
echo json_encode([
    'success' => true,
    'version' => $theme_version
]);
exit;