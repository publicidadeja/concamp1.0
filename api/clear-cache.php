<?php
/**
 * API para limpar o cache do sistema
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

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Token de segurança inválido']);
    exit;
}

// Limpar o cache do sistema
try {
    // Limpar cache de sessão
    $_SESSION['cache'] = [];
    
    // Forçar recarregamento do tema CSS definindo uma nova versão
    $new_theme_version = time();
    updateSetting('theme_version', $new_theme_version);
    
    // Limpar qualquer arquivo de cache temporário
    $cache_dir = __DIR__ . '/../cache';
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    // Registrar ação no log
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (:user_id, 'clear_cache', 'Limpeza de cache realizada')");
        $stmt->execute(['user_id' => getCurrentUserId()]);
    } catch (Exception $e) {
        // Ignora erro se a tabela não existir (não é essencial)
    }
    
    // Retornar sucesso
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Cache limpo com sucesso',
        'theme_version' => $new_theme_version
    ]);
} catch (Exception $e) {
    // Retornar erro
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erro ao limpar cache: ' . $e->getMessage()]);
}
exit;