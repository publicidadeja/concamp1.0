<?php
/**
 * API para upload de logo
 */

// Suprimir todos os avisos e erros
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar sessão e incluir arquivos necessários
@session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Definir header JSON
header('Content-Type: application/json');

// Verificar se o usuário é administrador
if (!isAdmin()) {
    echo json_encode([
        'success' => false,
        'error' => 'Permissão negada. Apenas administradores podem realizar esta ação.'
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
    // Verificar se o arquivo foi enviado
    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'Erro no upload do arquivo.';
        
        if (isset($_FILES['logo'])) {
            switch ($_FILES['logo']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message = 'O arquivo é muito grande.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message = 'O upload foi interrompido.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_message = 'Nenhum arquivo foi enviado.';
                    break;
            }
        }
        
        echo json_encode([
            'success' => false,
            'error' => $error_message
        ]);
        exit;
    }
    
    // Verificar o tipo de arquivo
    $allowed_types = ['image/jpeg', 'image/png', 'image/svg+xml'];
    if (!in_array($_FILES['logo']['type'], $allowed_types)) {
        echo json_encode([
            'success' => false,
            'error' => 'Tipo de arquivo não permitido. Use apenas JPG, PNG ou SVG.'
        ]);
        exit;
    }
    
    // Verificar tamanho do arquivo (máximo 2MB)
    $max_size = 2 * 1024 * 1024; // 2MB
    if ($_FILES['logo']['size'] > $max_size) {
        echo json_encode([
            'success' => false,
            'error' => 'O arquivo é muito grande. Tamanho máximo: 2MB.'
        ]);
        exit;
    }
    
    // Criar diretório de uploads se não existir
    $upload_dir = __DIR__ . '/../assets/img/uploads';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    error_log("Diretório de upload: " . $upload_dir);
    
    // Gerar nome de arquivo único
    $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
    $file_name = 'logo_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . '/' . $file_name;
    
    // Mover o arquivo enviado para o diretório de destino
    if (!move_uploaded_file($_FILES['logo']['tmp_name'], $file_path)) {
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao salvar o arquivo. Verifique as permissões do diretório.'
        ]);
        exit;
    }
    
    // Caminho relativo para uso no sistema
    $relative_path = 'assets/img/uploads/' . $file_name;
    
    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'file_path' => $relative_path,
        'message' => 'Logo enviado com sucesso!'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar upload: ' . $e->getMessage()
    ]);
}