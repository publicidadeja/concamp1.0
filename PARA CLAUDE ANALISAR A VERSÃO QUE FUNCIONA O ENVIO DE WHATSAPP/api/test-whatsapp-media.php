<?php
/**
 * API para teste de envio de mídia via WhatsApp
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
$media = isset($_FILES['media']) ? $_FILES['media'] : null;

// Validar parâmetros
if (empty($phone) || !$media) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
    exit;
}

// Validar arquivo
if ($media['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'Erro no upload do arquivo: ';
    
    switch ($media['error']) {
        case UPLOAD_ERR_INI_SIZE:
            $error_message .= 'O arquivo excede o tamanho máximo permitido pelo PHP.';
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $error_message .= 'O arquivo excede o tamanho máximo permitido pelo formulário.';
            break;
        case UPLOAD_ERR_PARTIAL:
            $error_message .= 'O arquivo foi parcialmente enviado.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $error_message .= 'Nenhum arquivo foi enviado.';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $error_message .= 'Falta a pasta temporária.';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $error_message .= 'Falha ao gravar o arquivo no disco.';
            break;
        case UPLOAD_ERR_EXTENSION:
            $error_message .= 'Upload interrompido por uma extensão do PHP.';
            break;
        default:
            $error_message .= 'Erro desconhecido.';
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $error_message]);
    exit;
}

// Verificar tipo de arquivo
$allowed_types = [
    'image/jpeg', 'image/png', 'image/gif',  // Imagens
    'application/pdf',                        // PDF
    'audio/mpeg', 'audio/mp3',                // Áudio
    'video/mp4'                               // Vídeo
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $media['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Tipo de arquivo não permitido. Tipos permitidos: JPG, PNG, GIF, PDF, MP3, MP4.']);
    exit;
}

// Formatar número de telefone
$phone = formatPhone($phone);

// Obter token de API
$token = getSetting('whatsapp_token');

if (empty($token)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Token de API não configurado. Configure o token nas configurações de integração.']);
    exit;
}

// Criar diretório temporário se não existir
$temp_dir = __DIR__ . '/../temp';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0755, true);
}

// Mover arquivo para diretório temporário
$temp_file = $temp_dir . '/' . uniqid() . '_' . basename($media['name']);
if (!move_uploaded_file($media['tmp_name'], $temp_file)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erro ao mover arquivo para diretório temporário.']);
    exit;
}

// Enviar mídia via WhatsApp
try {
    $api_url = WHATSAPP_API_URL;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    $headers = [
        'Authorization: Bearer ' . $token
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $post_fields = [
        'number' => $phone,
        'medias' => curl_file_create($temp_file, $mime_type, basename($media['name']))
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    // Remover arquivo temporário
    if (file_exists($temp_file)) {
        unlink($temp_file);
    }
    
    if ($err) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erro CURL: ' . $err]);
        exit;
    }
    
    if ($http_code >= 400) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erro do servidor: ' . $http_code, 'response' => $response]);
        exit;
    }
    
    $response_data = json_decode($response, true);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => isset($response_data['status']) && $response_data['status'] === 'success',
        'data' => $response_data
    ]);
} catch (Exception $e) {
    // Remover arquivo temporário em caso de erro
    if (file_exists($temp_file)) {
        unlink($temp_file);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erro ao enviar mídia: ' . $e->getMessage()]);
}
exit;
?>
