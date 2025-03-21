<?php
/**
 * API para upload de ícone PWA
 */

// Ativar exibição de erros para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inclusão dos arquivos necessários
require_once '../config/config.php';
require_once '../includes/functions.php';

// Criar log de debug
error_log("Upload PWA icon iniciado: " . date('Y-m-d H:i:s'));

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
    error_log("Erro no upload do PWA icon: Nenhum arquivo enviado");
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
    error_log("Erro no upload do PWA icon: " . $error_message);
    exit;
}

// Verificar se as extensões necessárias estão disponíveis
if (!extension_loaded('gd') && !extension_loaded('imagick')) {
    error_log("Erro no upload do PWA icon: Extensões GD e Imagick não estão disponíveis");
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'As extensões de processamento de imagem (GD ou Imagick) não estão disponíveis no servidor. Entre em contato com o administrador.'
    ]);
    exit;
}

// Verificar tipo e tamanho do arquivo
$allowed_types = ['image/jpeg', 'image/png', 'image/svg+xml'];
$max_size = 2 * 1024 * 1024; // 2MB

$file_type = $_FILES['image']['type'];
error_log("Tipo de arquivo enviado: " . $file_type);

// Se o tipo de MIME não for reconhecido, tentar determinar pelo nome do arquivo
if (empty($file_type) || $file_type === 'application/octet-stream') {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $file_type = 'image/jpeg';
    } elseif ($ext === 'png') {
        $file_type = 'image/png';
    } elseif ($ext === 'svg') {
        $file_type = 'image/svg+xml';
    }
    error_log("Tipo de arquivo determinado pela extensão: " . $file_type);
}

if (!in_array($file_type, $allowed_types)) {
    error_log("Erro no upload do PWA icon: Tipo de arquivo não permitido - " . $file_type);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Tipo de arquivo não permitido. Apenas JPG, PNG e SVG são permitidos.'
    ]);
    exit;
}

if ($_FILES['image']['size'] > $max_size) {
    error_log("Erro no upload do PWA icon: Tamanho do arquivo excede o limite - " . $_FILES['image']['size'] . " bytes");
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
$filename = 'icon-512x512.' . $file_ext;
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
    updateSetting('pwa_icon_url', $relative_path);
    
    // Criar diferentes tamanhos de ícones para PWA
    $sizes = [72, 96, 128, 144, 152, 192, 384, 512];
    $sourceImage = null;
    
    // Verificar se estamos lidando com SVG
    $is_svg = ($file_type === 'image/svg+xml');

    error_log("Processando imagem PWA do tipo: " . $file_type);

    try {
        // Carregar imagem de origem baseada no tipo
        if (!$is_svg) {
            if ($file_type === 'image/jpeg') {
                $sourceImage = imagecreatefromjpeg($upload_path);
                error_log("Carregada imagem JPEG para PWA");
            } elseif ($file_type === 'image/png') {
                $sourceImage = imagecreatefrompng($upload_path);
                error_log("Carregada imagem PNG para PWA");
            }
            
            if (!$sourceImage) {
                throw new Exception("Falha ao carregar a imagem de origem");
            }
        } else {
            // Para SVG, apenas mantemos o arquivo como está
            error_log("Arquivo SVG detectado para PWA, não é necessário redimensionar");
            $relative_path = 'assets/img/icons/icon-512x512.svg';
        }
        
        // Criar as diferentes variantes de tamanho (exceto para SVG)
        if (!$is_svg && $sourceImage) {
            foreach ($sizes as $size) {
                // Pular tamanho original
                if ($size == 512) continue;
                
                // Criar imagem redimensionada
                $destImage = imagecreatetruecolor($size, $size);
                if (!$destImage) {
                    throw new Exception("Falha ao criar a imagem de destino para o tamanho {$size}x{$size}");
                }
                
                error_log("Criando ícone PWA no tamanho {$size}x{$size}");
                
                // Preservar transparência para PNG
                if ($file_type === 'image/png') {
                    imagealphablending($destImage, false);
                    imagesavealpha($destImage, true);
                    $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
                    imagefilledrectangle($destImage, 0, 0, $size, $size, $transparent);
                }
                
                // Redimensionar a imagem
                $source_width = imagesx($sourceImage);
                $source_height = imagesy($sourceImage);
                
                if (!imagecopyresampled($destImage, $sourceImage, 0, 0, 0, 0, $size, $size, $source_width, $source_height)) {
                    throw new Exception("Falha ao redimensionar a imagem para {$size}x{$size}");
                }
                
                // Salvar a imagem redimensionada
                $resizedFilename = 'icon-' . $size . 'x' . $size . '.' . $file_ext;
                $resizedPath = $upload_dir . $resizedFilename;
                
                if ($file_type === 'image/jpeg') {
                    if (!imagejpeg($destImage, $resizedPath, 90)) {
                        throw new Exception("Falha ao salvar a imagem JPEG redimensionada: {$resizedPath}");
                    }
                } else {
                    if (!imagepng($destImage, $resizedPath, 9)) {
                        throw new Exception("Falha ao salvar a imagem PNG redimensionada: {$resizedPath}");
                    }
                }
                
                error_log("Ícone PWA criado com sucesso: {$resizedPath}");
                
                imagedestroy($destImage);
            }
            
            // Limpar memória
            imagedestroy($sourceImage);
        }
    } catch (Exception $e) {
        error_log("Erro no processamento de imagens do PWA: " . $e->getMessage());
        // Se houver erro no processamento, apenas continuamos com o ícone original
    }
    
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