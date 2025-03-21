<?php
/**
 * API para upload de favicon
 */

// Ativar exibição de erros para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inclusão dos arquivos necessários
require_once '../config/config.php';
require_once '../includes/functions.php';

// Criar log de debug
error_log("Upload favicon iniciado: " . date('Y-m-d H:i:s'));

// Verificar autenticação
if (!isLoggedIn() || !isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Acesso não autorizado'
    ]);
    exit;
}

// Verificar token CSRF
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Token de segurança inválido'
    ]);
    exit;
}

// Verificar se foi enviado um arquivo
if (!isset($_FILES['image'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Nenhum arquivo enviado'
    ]);
    error_log("Erro no upload do favicon: Nenhum arquivo enviado");
    exit;
}

// Verificar código de erro do upload
if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'Erro no upload: ';
    switch ($_FILES['image']['error']) {
        case UPLOAD_ERR_INI_SIZE:
            $error_message .= 'O arquivo excede o tamanho máximo permitido no php.ini';
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $error_message .= 'O arquivo excede o tamanho máximo permitido no formulário HTML';
            break;
        case UPLOAD_ERR_PARTIAL:
            $error_message .= 'O arquivo foi enviado parcialmente';
            break;
        case UPLOAD_ERR_NO_FILE:
            $error_message .= 'Nenhum arquivo foi enviado';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $error_message .= 'Pasta temporária ausente';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $error_message .= 'Falha ao escrever o arquivo no disco';
            break;
        case UPLOAD_ERR_EXTENSION:
            $error_message .= 'Uma extensão PHP interrompeu o upload';
            break;
        default:
            $error_message .= 'Erro desconhecido';
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $error_message
    ]);
    error_log("Erro no upload do favicon: " . $error_message);
    exit;
}

// Verificar tipo e tamanho do arquivo
$allowed_types = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'];
$max_size = 2 * 1024 * 1024; // 2MB

$file_type = $_FILES['image']['type'];
error_log("Tipo de arquivo enviado para favicon: " . $file_type);

// Se o tipo de MIME não for reconhecido, tentar determinar pelo nome do arquivo
if (empty($file_type) || $file_type === 'application/octet-stream') {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $file_type = 'image/jpeg';
    } elseif ($ext === 'png') {
        $file_type = 'image/png';
    } elseif ($ext === 'svg') {
        $file_type = 'image/svg+xml';
    } elseif ($ext === 'ico') {
        $file_type = 'image/x-icon';
    }
    error_log("Tipo de arquivo favicon determinado pela extensão: " . $file_type);
}

if (!in_array($file_type, $allowed_types)) {
    error_log("Erro no upload do favicon: Tipo de arquivo não permitido - " . $file_type);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Tipo de arquivo não permitido. Apenas JPG, PNG, SVG e ICO são permitidos.'
    ]);
    exit;
}

if ($_FILES['image']['size'] > $max_size) {
    error_log("Erro no upload do favicon: Tamanho do arquivo excede o limite - " . $_FILES['image']['size'] . " bytes");
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Tamanho do arquivo excede o limite (2MB)'
    ]);
    exit;
}

// Criar diretório de ícones se não existir
$upload_dir = '../assets/img/icons/';
if (!is_dir($upload_dir)) {
    $result = mkdir($upload_dir, 0755, true);
    if (!$result) {
        error_log("Erro ao criar diretório: " . $upload_dir);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao criar diretório de upload. Verifique as permissões.'
        ]);
        exit;
    }
}

// Verificar permissões do diretório
if (!is_writable($upload_dir)) {
    error_log("Diretório não tem permissão de escrita: " . $upload_dir);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Diretório de upload não tem permissão de escrita. Entre em contato com o administrador do sistema.'
    ]);
    exit;
}

// Gerar nome de arquivo único com timestamp
$file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
$filename = 'favicon.' . $file_ext;
$upload_path = $upload_dir . $filename;

// Log antes de mover o arquivo
error_log("Tentando mover arquivo para: " . $upload_path);
error_log("Arquivo temporário: " . $_FILES['image']['tmp_name'] . ", Tamanho: " . $_FILES['image']['size'] . " bytes");

// Mover arquivo para o diretório de uploads
if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
    error_log("Arquivo movido com sucesso para: " . $upload_path);
    
    // Caminho relativo para o banco de dados
    $relative_path = 'assets/img/icons/' . $filename;
    
    // Atualizar configuração no banco de dados
    updateSetting('favicon_url', $relative_path);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'file_path' => $relative_path
    ]);
} else {
    $move_error = error_get_last();
    error_log("Falha ao mover arquivo: " . ($move_error ? $move_error['message'] : 'Erro desconhecido'));
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao salvar o arquivo. Detalhes: ' . ($move_error ? $move_error['message'] : 'Erro desconhecido')
    ]);
    exit;
}