<?php
// Habilitar logs para depuração mas sem exibir erros na resposta (JSON puro)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Desativar exibição de erros para garantir JSON limpo
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/whatsapp-error.log');

// Iniciar sessão e incluir arquivos necessários
@session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Definir header JSON
header('Content-Type: application/json');

// Log de depuração
error_log('API message/send.php: Iniciada - ' . date('Y-m-d H:i:s'));
error_log('Dados recebidos: ' . json_encode($_POST));

/**
 * Função para obter mensagem de erro de upload
 * @param int $error_code Código de erro de upload
 * @return string Descrição do erro
 */
function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return "O arquivo excede o tamanho máximo permitido pelo PHP (php.ini).";
        case UPLOAD_ERR_FORM_SIZE:
            return "O arquivo excede o tamanho máximo permitido pelo formulário.";
        case UPLOAD_ERR_PARTIAL:
            return "O upload do arquivo foi feito parcialmente.";
        case UPLOAD_ERR_NO_FILE:
            return "Nenhum arquivo foi enviado.";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Diretório temporário não encontrado.";
        case UPLOAD_ERR_CANT_WRITE:
            return "Falha ao gravar arquivo no disco.";
        case UPLOAD_ERR_EXTENSION:
            return "Uma extensão PHP interrompeu o upload.";
        default:
            return "Erro desconhecido.";
    }
}

/**
 * Processa o upload de um arquivo de mídia
 * @param array $file Dados do arquivo
 * @return array Array com informações do upload (success, media_url, media_type, error)
 */
function processMediaUpload($file) {
    $result = [
        'success' => false,
        'media_url' => null,
        'media_type' => null,
        'error' => null
    ];

    if (!isset($file) || $file['error'] !== 0) {
        $result['error'] = isset($file) ? getUploadErrorMessage($file['error']) : 'Nenhum arquivo enviado';
        return $result;
    }

    $temp_file = $file['tmp_name'];
    $media_type = $file['type'];

    // Criar diretório de uploads se não existir
    $upload_dir = __DIR__ . '/../../uploads/media/';
    if (!file_exists($upload_dir)) {
        error_log('Criando diretório de uploads: ' . $upload_dir);
        if (!mkdir($upload_dir, 0755, true)) {
            $result['error'] = 'Falha ao criar diretório de uploads';
            return $result;
        }
    }

    // Nome do arquivo com timestamp para evitar colisões
    $filename = time() . '_' . $file['name'];
    $upload_path = $upload_dir . $filename;

    // Log de depuração
    error_log('Tentando mover arquivo de ' . $temp_file . ' para: ' . $upload_path);

    // Mover o arquivo para o diretório de uploads
    if (move_uploaded_file($temp_file, $upload_path)) {
        $result['success'] = true;
        $result['media_url'] = 'uploads/media/' . $filename; // Caminho relativo para o arquivo
        $result['media_type'] = $media_type;
        error_log('Upload bem-sucedido: ' . $result['media_url']);
    } else {
        $result['error'] = 'Falha ao mover arquivo para o diretório de destino';
        error_log('ERRO: ' . $result['error'] . ': ' . $upload_path);
        error_log('Permissões do diretório: ' . substr(sprintf('%o', fileperms($upload_dir)), -4));
    }

    return $result;
}

/**
 * Obtém o token de WhatsApp adequado com base no usuário atual e no lead
 * @param array $current_user Dados do usuário atual
 * @param array $lead Dados do lead
 * @param bool $is_admin Se o usuário é admin
 * @return string|null Token de WhatsApp ou null se não encontrado
 */
function getWhatsAppToken($current_user, $lead, $is_admin) {
    $token = null;
    $user_id = $current_user['id'];
    
    // Se o usuário atual é o vendedor atribuído, usar seu token personalizado
    if ($user_id == $lead['seller_id'] && !empty($current_user['whatsapp_token'])) {
        $token = $current_user['whatsapp_token'];
        error_log('Usando token de WhatsApp do vendedor: ' . $user_id . ' - ' . $token);
    }
    // Se usuário é admin, verificar token do vendedor atribuído
    else if ($is_admin && !empty($lead['seller_id'])) {
        // Tentar obter token do vendedor atribuído ao lead
        $seller = getUserById($lead['seller_id']);
        
        if ($seller && !empty($seller['whatsapp_token'])) {
            $token = $seller['whatsapp_token'];
            error_log('Admin usando token de WhatsApp do vendedor atribuído: ' . $lead['seller_id'] . ' - ' . $token);
        } else {
            // Usar token global de backup
            $token = getSetting('whatsapp_api_token', '');
            error_log('Vendedor atribuído não tem token. Usando token global: ' . (empty($token) ? 'Não configurado' : 'Configurado'));
        }
    } else {
        // Tentar usar token global como último recurso
        $token = getSetting('whatsapp_api_token', '');
        error_log('Usando token global de WhatsApp: ' . (empty($token) ? 'Não configurado' : 'Configurado'));
    }
    
    return $token;
}

/**
 * Verifica e processa o resultado do envio de WhatsApp
 * @param mixed $result Resultado da API de WhatsApp
 * @return array Array com status do envio (success, message_id, error)
 */
function processWhatsAppResult($result) {
    $response = [
        'success' => false,
        'message_id' => null,
        'error' => null
    ];
    
    // Se não houver resultado, retornar erro
    if ($result === false) {
        $response['error'] = 'Falha na comunicação com a API de WhatsApp';
        return $response;
    }
    
    // Verificar se temos um resultado de sucesso
    if (isset($result['success']) && $result['success'] === true) {
        $response['success'] = true;
        
        // Capturar ID da mensagem se disponível
        if (isset($result['message_id'])) {
            $response['message_id'] = $result['message_id'];
        }
    } else {
        // Capturar mensagem de erro se disponível
        if (isset($result['error'])) {
            $response['error'] = $result['error'];
        } else {
            $response['error'] = 'Erro desconhecido no envio do WhatsApp';
        }
    }
    
    return $response;
}

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    // Verificar parâmetros essenciais
    if (!isset($_POST['lead_id']) || !isset($_POST['message'])) {
        throw new Exception('Parâmetros incompletos: ID do lead e mensagem são obrigatórios');
    }

    // Verificar CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        throw new Exception('Token de segurança inválido ou expirado');
    }

    // Obter dados básicos
    $lead_id = intval($_POST['lead_id']);
    $message = trim($_POST['message']);
    
    // Validar mensagem não vazia
    if (empty($message)) {
        throw new Exception('A mensagem não pode estar vazia');
    }

    // Obter usuário atual
    $current_user = getCurrentUser();
    if (!$current_user) {
        throw new Exception('Usuário não autenticado. Faça login novamente.');
    }

    $user_id = $current_user['id'];
    $is_admin = isAdmin();

    // Obter dados do lead
    $lead = getLeadById($lead_id);
    if (!$lead) {
        throw new Exception('Lead não encontrado. Verifique se o ID está correto.');
    }

    // Verificar permissão (apenas admin ou vendedor atribuído pode enviar mensagem)
    if (!$is_admin && $lead['seller_id'] != $user_id) {
        throw new Exception('Você não tem permissão para enviar mensagem para este lead');
    }

    // Processar variáveis na mensagem
    $message_data = [
        'nome' => $lead['name'],
        'tipo_veiculo' => $lead['plan_type'] == 'car' ? 'Carro' : 'Moto',
        'valor_credito' => $lead['plan_credit'],
        'prazo' => $lead['plan_term'],
        'valor_primeira' => $lead['first_installment'],
        'valor_demais' => $lead['other_installments'],
        'nome_consultor' => $current_user['name']
    ];

    error_log('Mensagem original: ' . $message);

    // Substituir variáveis no template - tanto no formato {{var}} quanto {var}
    $message = processMessageTemplate($message, $message_data);
    
    // Garantir que nenhuma variável ficou sem substituição
    foreach ($message_data as $key => $value) {
        $message = str_replace(['{' . $key . '}', '{{' . $key . '}}'], $value, $message);
    }

    error_log('Mensagem processada: ' . $message);

    // Obter o token de WhatsApp apropriado
    $whatsapp_token = getWhatsAppToken($current_user, $lead, $is_admin);

    // Registrar template_id se fornecido
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : null;

    // Processar upload de mídia (se houver)
    $media_upload = ['success' => false, 'media_url' => null, 'media_type' => null];
    
    if (isset($_FILES['media'])) {
        error_log('Processando mídia: ' . json_encode($_FILES['media']));
        $media_upload = processMediaUpload($_FILES['media']);
        
        if (!$media_upload['success'] && $media_upload['error']) {
            error_log('Erro no upload de mídia: ' . $media_upload['error']);
            // Não lançar exceção, apenas registrar o erro e continuar sem mídia
        }
    }

    // Variáveis para mídia
    $media_url = $media_upload['success'] ? $media_upload['media_url'] : null;
    $media_type = $media_upload['success'] ? $media_upload['media_type'] : null;

    // Tentar enviar a mensagem via WhatsApp se tiver token
    $whatsapp_status = ['success' => false, 'message_id' => null, 'error' => null];
    
    if (!empty($whatsapp_token)) {
        error_log('Tentando enviar mensagem via WhatsApp');

        // Preparar dados de mídia se existir
        $media_data = null;
        $has_media = false;

        if ($media_upload['success']) {
            $media_data = [
                'path' => $media_url,
                'type' => $media_type,
                'name' => basename($media_url)
            ];
            $has_media = true;
            error_log('Mídia para envio: ' . json_encode($media_data));
        }

        // Log para diagnóstico
        error_log('Enviando para número: ' . $lead['phone']);
        error_log('Possui mídia: ' . ($has_media ? 'Sim' : 'Não'));

        // Enviar mensagem com ou sem mídia
        $whatsapp_result = false;
        
        if ($has_media) {
            $whatsapp_result = sendWhatsAppWithFallback(
                $lead['phone'],
                $message,
                $media_data,
                $whatsapp_token
            );
        } else {
            $whatsapp_result = sendWhatsAppMessage(
                $lead['phone'],
                $message,
                null,
                $whatsapp_token
            );
        }
        
        // Processar resultado do envio
        $whatsapp_status = processWhatsAppResult($whatsapp_result);
        
        if (!$whatsapp_status['success']) {
            error_log('Falha no envio WhatsApp: ' . ($whatsapp_status['error'] ?? 'Erro desconhecido'));
        } else {
            error_log('WhatsApp enviado com sucesso. ID: ' . ($whatsapp_status['message_id'] ?? 'N/A'));
        }
    } else {
        error_log('Nenhum token de WhatsApp disponível. Apenas registrando mensagem.');
        $whatsapp_status['error'] = 'Token de WhatsApp não configurado';
    }

    // Registrar mensagem no banco de dados (sempre, mesmo que o envio falhe)
    $message_status = $whatsapp_status['success'] ? 'sent' : 'pending';
    $external_id = $whatsapp_status['message_id'];

    error_log("Registrando mensagem com status: " . $message_status);
    
    $message_id = registerSentMessage(
        $lead_id,
        $user_id,
        $message,
        $template_id,
        $media_url,
        $media_type,
        $message_status,
        $external_id
    );

    if (!$message_id) {
        throw new Exception('Erro ao registrar mensagem no banco de dados');
    }

    error_log("Mensagem registrada com sucesso. ID: " . $message_id);

    // Preparar resposta para o cliente
    $response = [
        'success' => true,
        'message' => $whatsapp_status['success']
            ? 'Mensagem enviada com sucesso via WhatsApp'
            : 'Mensagem registrada mas não foi enviada via WhatsApp' . 
              ($whatsapp_status['error'] ? ': ' . $whatsapp_status['error'] : ''),
        'sent_message' => [
            'id' => $message_id,
            'content' => $message,
            'user_name' => $current_user['name'],
            'sent_date' => date('d/m/Y H:i:s'),
            'media_url' => $media_url
        ],
        'whatsapp_sent' => $whatsapp_status['success']
    ];

    // Adicionar informações sobre falha no upload de mídia, se houver
    if (isset($_FILES['media']) && !$media_upload['success']) {
        $response['media_error'] = $media_upload['error'];
    }

    // Cabeçalhos para evitar problemas de cache
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header('Content-Type: application/json; charset=utf-8');

    // Limpar buffer de saída
    if (ob_get_length()) ob_clean();

    // Enviar resposta JSON
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    // Log de erro
    error_log('Erro na API message/send.php: ' . $e->getMessage());

    // Cabeçalhos para evitar problemas de cache
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header('Content-Type: application/json; charset=utf-8');

    // Limpar buffer de saída
    if (ob_get_length()) ob_clean();

    // Resposta de erro para o cliente
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
