<?php
/**
 * API simplificada para teste de upload
 */

// Ativar log de erros
ini_set('display_errors', 0);
error_reporting(E_ALL);
$log_file = '../logs/upload-debug.log';
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Iniciando upload simplificado\n", FILE_APPEND);

// Verificar se foi enviado um arquivo
if (!isset($_FILES['image'])) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Nenhum arquivo enviado\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Nenhum arquivo enviado'
    ]);
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
    
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] " . $error_message . "\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $error_message
    ]);
    exit;
}

$file_tmp = $_FILES['image']['tmp_name'];
$file_name = $_FILES['image']['name'];
$file_size = $_FILES['image']['size'];
$file_type = $_FILES['image']['type'];

file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Arquivo enviado: " . $file_name . ", Tipo: " . $file_type . ", Tamanho: " . $file_size . " bytes\n", FILE_APPEND);

// Criar diretório de ícones se não existir
$upload_dir = '../assets/img/icons/';
if (!is_dir($upload_dir)) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Criando diretório: " . $upload_dir . "\n", FILE_APPEND);
    $result = mkdir($upload_dir, 0755, true);
    if (!$result) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Falha ao criar diretório\n", FILE_APPEND);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao criar diretório de upload. Verifique as permissões.'
        ]);
        exit;
    }
}

// Gerar nome de arquivo único
$file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
$target_name = 'upload_' . time() . '.' . $file_ext;
$upload_path = $upload_dir . $target_name;

file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Tentando mover arquivo para: " . $upload_path . "\n", FILE_APPEND);

// Tentar mover o arquivo
if (move_uploaded_file($file_tmp, $upload_path)) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Arquivo movido com sucesso\n", FILE_APPEND);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'file_path' => 'assets/img/icons/' . $target_name,
        'message' => 'Upload realizado com sucesso'
    ]);
} else {
    $move_error = error_get_last();
    $error_msg = ($move_error ? $move_error['message'] : 'Erro desconhecido');
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Falha ao mover arquivo: " . $error_msg . "\n", FILE_APPEND);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao salvar o arquivo. Detalhes: ' . $error_msg
    ]);
}