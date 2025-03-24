<?php
// Suprimir todos os avisos e erros
error_reporting(0);
ini_set('display_errors', 0);

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

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    // Verificar parâmetros essenciais
    if (!isset($_POST['lead_id']) || !isset($_POST['message'])) {
        throw new Exception('Parâmetros incompletos');
    }
    
    // Verificar CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        throw new Exception('Token de segurança inválido');
    }
    
    // Obter dados básicos
    $lead_id = intval($_POST['lead_id']);
    $message = $_POST['message'];
    
    // Obter usuário atual
    $current_user = getCurrentUser();
    if (!$current_user) {
        throw new Exception('Usuário não autenticado');
    }
    
    $user_id = $current_user['id'];
    $is_admin = isAdmin();
    
    // Obter dados do lead
    $lead = getLeadById($lead_id);
    if (!$lead) {
        throw new Exception('Lead não encontrado');
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
    
    // Substituir variáveis no template
    $message = processMessageTemplate($message, $message_data);
    
    // Verificar permissão (apenas admin ou vendedor atribuído pode enviar mensagem)
    if (!$is_admin && $lead['seller_id'] != $user_id) {
        throw new Exception('Você não tem permissão para enviar mensagem para este lead');
    }
    
    // Obter o token de WhatsApp apropriado (preferência para o token do vendedor)
    $whatsapp_token = null;
    
    // Se o usuário atual é o vendedor atribuído, usar seu token personalizado
    if ($user_id == $lead['seller_id'] && !empty($current_user['whatsapp_token'])) {
        $whatsapp_token = $current_user['whatsapp_token'];
        error_log('Usando token de WhatsApp do vendedor: ' . $user_id);
    }
    // Se não tem token do vendedor ou usuário é admin, usar token global
    else if ($is_admin && !empty($lead['seller_id'])) {
        // Tentar obter token do vendedor atribuído ao lead
        $seller = getUserById($lead['seller_id']);
        if ($seller && !empty($seller['whatsapp_token'])) {
            $whatsapp_token = $seller['whatsapp_token'];
            error_log('Admin usando token de WhatsApp do vendedor atribuído: ' . $lead['seller_id']);
        }
    }
    
    // Registrar template_id se fornecido
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : null;
    
    // Processar upload de mídia (se houver)
    $media_url = null;
    $media_type = null;
    $temp_file = null;
    
    // Log de depuração de arquivos
    error_log('Conteúdo de $_FILES: ' . json_encode($_FILES));
    
    if (isset($_FILES['media'])) {
        error_log('Detalhes de mídia: ' . json_encode($_FILES['media']));
        
        if ($_FILES['media']['error'] === 0) {
            // Em um ambiente real, você moveria o arquivo para um diretório permanente
            // e armazenaria o caminho real no banco de dados
            $temp_file = $_FILES['media']['tmp_name'];
            $media_url = "uploads/" . time() . "_" . $_FILES['media']['name'];
            $media_type = $_FILES['media']['type'];
            
            error_log('Mídia válida detectada: ' . $media_type);
            error_log('Arquivo temporário: ' . $temp_file);
            error_log('URL da mídia (simulada): ' . $media_url);
        } else {
            error_log('Erro no upload de mídia: ' . $_FILES['media']['error'] . ' - ' . getUploadErrorMessage($_FILES['media']['error']));
        }
    } else {
        error_log('Nenhuma mídia detectada nos arquivos enviados');
    }
    
    // Função para obter mensagem de erro de upload
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
    
    // Tentar enviar a mensagem via WhatsApp se tiver token
    $whatsapp_result = false;
    if (!empty($whatsapp_token)) {
        error_log('Tentando enviar mensagem via WhatsApp com token personalizado');
        
        // Verificar se a mensagem está vazia (o que seria inválido)
        if (trim($message) === '') {
            error_log('Mensagem vazia detectada. Adicionando espaço em branco para garantir envio.');
            $message = ' '; // Garantir que não enviaremos uma mensagem totalmente vazia
        }
        
        // Preparar dados de mídia se existir
        $media_data = null;
        $has_media = false;
        
        if (isset($_FILES['media']) && $_FILES['media']['error'] === 0) {
            // Usar o arquivo temporário enviado
            $media_data = [
                'path' => $_FILES['media']['tmp_name'],
                'type' => $_FILES['media']['type'],
                'name' => $_FILES['media']['name']
            ];
            $has_media = true;
            error_log('Mídia válida detectada para envio: ' . $_FILES['media']['name'] . ' (' . $_FILES['media']['type'] . ')');
        } else if ($media_url && $media_type) {
            // Compatibilidade com o código anterior ou simulação
            $media_data = [
                'path' => $temp_file ?: $media_url,
                'type' => $media_type,
                'name' => basename($media_url)
            ];
            $has_media = true;
            error_log('Usando dados de mídia simulada: ' . json_encode($media_data));
        }
        
        // Log para facilitar o diagnóstico
        error_log('Enviando para número: ' . $lead['phone']);
        error_log('Mensagem a ser enviada: ' . $message);
        error_log('Possui mídia: ' . ($has_media ? 'Sim' : 'Não'));
        
        // Usar a função com fallback para garantir que tanto mídia quanto texto sejam enviados
        if ($has_media) {
            // Usar a estratégia otimizada para enviar mídia e texto separadamente
            $whatsapp_result = sendWhatsAppWithFallback(
                $lead['phone'],
                $message,
                $media_data,
                $whatsapp_token
            );
            
            error_log('Resultado final do envio WhatsApp com mídia e texto: ' . json_encode($whatsapp_result));
        } else {
            // Envio apenas de texto - não precisa de estratégia especial
            $whatsapp_result = sendWhatsAppMessage(
                $lead['phone'],
                $message,
                null,
                $whatsapp_token
            );
            
            error_log('Resultado do envio WhatsApp apenas texto: ' . json_encode($whatsapp_result));
        }
    } else {
        error_log('Nenhum token de WhatsApp disponível para envio real. Apenas registrando mensagem.');
    }
    
    // Registrar mensagem no banco de dados
    $message_status = ($whatsapp_result && $whatsapp_result['success']) ? 'sent' : 'pending';
    $message_id = registerSentMessage(
        $lead_id,
        $user_id,
        $message,
        $template_id,
        $media_url,
        $media_type,
        $message_status // Status da mensagem
    );
    
    if (!$message_id) {
        throw new Exception('Erro ao registrar mensagem');
    }
    
    error_log('Mensagem registrada com sucesso. ID: ' . $message_id);
    
    // Preparar resposta para o cliente
    $response = [
        'success' => true,
        'message' => !empty($whatsapp_token) 
            ? 'Mensagem enviada com sucesso via WhatsApp' 
            : 'Mensagem registrada com sucesso (sem envio real via WhatsApp - token não configurado)',
        'sent_message' => [
            'id' => $message_id,
            'content' => $message,
            'user_name' => $current_user['name'],
            'sent_date' => date('d/m/Y H:i:s'),
            'media_url' => $media_url
        ],
        'whatsapp_sent' => !empty($whatsapp_token)
    ];
    
    // Enviar resposta JSON
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log de erro
    error_log('Erro na API message/send.php: ' . $e->getMessage());
    
    // Resposta de erro para o cliente
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}