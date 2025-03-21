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
    
    // Fazer log do usuário atual para depuração
    error_log("Dados do usuário atual: " . json_encode($current_user));
    error_log("ID do vendedor do lead: " . $lead['seller_id']);
    error_log("ID do usuário atual: " . $user_id);
    
    // Se o usuário atual é o vendedor atribuído, usar seu token personalizado
    if ($user_id == $lead['seller_id'] && !empty($current_user['whatsapp_token'])) {
        $whatsapp_token = $current_user['whatsapp_token'];
        error_log('Usando token de WhatsApp do vendedor: ' . $user_id . ' - ' . $whatsapp_token);
    }
    // Se não tem token do vendedor ou usuário é admin, usar token global
    else if ($is_admin && !empty($lead['seller_id'])) {
        // Tentar obter token do vendedor atribuído ao lead
        $seller = getUserById($lead['seller_id']);
        error_log("Dados do vendedor atribuído: " . json_encode($seller));
        
        if ($seller && !empty($seller['whatsapp_token'])) {
            $whatsapp_token = $seller['whatsapp_token'];
            error_log('Admin usando token de WhatsApp do vendedor atribuído: ' . $lead['seller_id'] . ' - ' . $whatsapp_token);
        } else {
            error_log('Vendedor atribuído não tem token de WhatsApp. Usando token global.');
            // Usar token global de backup
            $whatsapp_token = getSetting('whatsapp_api_token', '');
            error_log('Usando token global de WhatsApp: ' . (empty($whatsapp_token) ? 'Não configurado' : 'Token configurado'));
        }
    } else {
        // Tentar usar token global como último recurso
        $whatsapp_token = getSetting('whatsapp_api_token', '');
        error_log('Usando token global de WhatsApp: ' . (empty($whatsapp_token) ? 'Não configurado' : 'Token configurado'));
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
            $temp_file = $_FILES['media']['tmp_name'];
            $media_type = $_FILES['media']['type'];
            
            // Criar diretório de uploads se não existir
            $upload_dir = __DIR__ . '/../../uploads/media/';
            if (!file_exists($upload_dir)) {
                error_log('Criando diretório de uploads: ' . $upload_dir);
                mkdir($upload_dir, 0755, true);
            }
            
            // Nome do arquivo com timestamp para evitar colisões
            $filename = time() . '_' . $_FILES['media']['name'];
            $upload_path = $upload_dir . $filename;
            
            // Log de depuração
            error_log('Tentando mover arquivo para: ' . $upload_path);
            
            // Mover o arquivo para o diretório de uploads
            if (move_uploaded_file($temp_file, $upload_path)) {
                $media_url = 'uploads/media/' . $filename;
                error_log('Upload bem-sucedido: ' . $media_url);
            } else {
                error_log('ERRO: Falha ao mover arquivo para: ' . $upload_path);
                error_log('Permissões do diretório: ' . substr(sprintf('%o', fileperms($upload_dir)), -4));
            }
            
            error_log('Mídia válida detectada: ' . $media_type);
            error_log('Arquivo temporário: ' . $temp_file);
            error_log('URL da mídia final: ' . $media_url);
        } else {
            error_log('Erro no upload de mídia: ' . $_FILES['media']['error'] . ' - ' . getUploadErrorMessage($_FILES['media']['error']));
        }
    } else {
        error_log('Nenhuma mídia detectada nos arquivos enviados');
    }

    /**
 * Função para processar template de mensagem
 */
function processMessageTemplate($message, $data) {
    foreach ($data as $key => $value) {
        $message = str_replace('{' . $key . '}', $value, $message);
    }
    return $message;
}

/**
 * Função para enviar mensagem via WhatsApp - Usa a função global do sistema
 * com suporte à nova API
 */
function sendWhatsAppMessage($phone, $message, $media = null, $token) {
    // Reutiliza a função global com a implementação atualizada
    // Esta função é apenas um wrapper para compatibilidade com
    // código existente neste arquivo
    
    // Chamar a função global que agora suporta a nova API
    return \sendWhatsAppMessage($phone, $message, $media, $token);
}

/**
 * Função para enviar WhatsApp com fallback - Não mais necessária com a nova API
 * que suporta envio de texto e mídia juntos, mas mantida para compatibilidade
 */
function sendWhatsAppWithFallback($phone, $message, $media, $token) {
    error_log("Usando sendWhatsAppMessage para envio unificado de mídia e texto");
    
    // Com a nova API, podemos enviar mídia e texto em uma única chamada
    return sendWhatsAppMessage($phone, $message, $media, $token);
}

/**
 * Função para registrar mensagem enviada
 */
function registerSentMessage($lead_id, $user_id, $message, $template_id = null, $media_url = null, $media_type = null, $status = 'sent', $external_id = null) {
    $conn = getConnection();
    
    try {
        // Verificar se a tabela existe
        $conn->query("CREATE TABLE IF NOT EXISTS `lead_messages` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `lead_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `template_id` int(11) DEFAULT NULL,
            `message` text NOT NULL,
            `media_url` varchar(255) DEFAULT NULL,
            `media_type` varchar(50) DEFAULT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'sent',
            `external_id` varchar(100) DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `lead_id` (`lead_id`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Log dos parâmetros recebidos
        error_log("Tentando registrar mensagem com parâmetros:");
        error_log("Lead ID: " . $lead_id);
        error_log("User ID: " . $user_id);
        error_log("Message: " . $message);
        error_log("Status: " . $status);
        
        // Preparar e executar a inserção
        $sql = "INSERT INTO lead_messages 
                (lead_id, user_id, template_id, message, media_url, media_type, status, external_id, created_at) 
                VALUES 
                (:lead_id, :user_id, :template_id, :message, :media_url, :media_type, :status, :external_id, NOW())";
        
        $stmt = $conn->prepare($sql);
        
        $params = [
            'lead_id' => $lead_id,
            'user_id' => $user_id,
            'template_id' => $template_id,
            'message' => $message,
            'media_url' => $media_url,
            'media_type' => $media_type,
            'status' => $status,
            'external_id' => $external_id
        ];
        
        // Log da query e parâmetros
        error_log("SQL: " . $sql);
        error_log("Parâmetros: " . json_encode($params));
        
        $result = $stmt->execute($params);
        
        if ($result) {
            $message_id = $conn->lastInsertId();
            error_log("Mensagem registrada com sucesso. ID: " . $message_id);
            return $message_id;
        }
        
        error_log("Falha ao registrar mensagem no banco de dados");
        return false;
        
    } catch (PDOException $e) {
        error_log("Erro PDO ao registrar mensagem: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Erro ao registrar mensagem: " . $e->getMessage());
        return false;
    }
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
            // Caminho completo para o arquivo
            $complete_path = __DIR__ . '/../../' . $media_url;
            
            // Compatibilidade com o código anterior ou simulação
            $media_data = [
                'path' => file_exists($complete_path) ? $complete_path : $media_url,
                'type' => $media_type,
                'name' => basename($media_url)
            ];
            $has_media = true;
            error_log('Usando dados de mídia: ' . json_encode($media_data));
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
    
    error_log("Tentando registrar mensagem com os seguintes parâmetros:");
    error_log("Lead ID: " . $lead_id);
    error_log("User ID: " . $user_id);
    error_log("Template ID: " . ($template_id ?? 'null'));
    error_log("Media URL: " . ($media_url ?? 'null'));
    error_log("Media Type: " . ($media_type ?? 'null'));
    error_log("Status: " . $message_status);
    
    try {
        $message_id = registerSentMessage(
            $lead_id,
            $user_id,
            $message,
            $template_id,
            $media_url,
            $media_type,
            $message_status,
            null // message_id (ID externo da API de WhatsApp, se houver)
        );
        
        if (!$message_id) {
            error_log("registerSentMessage retornou false ou 0");
            throw new Exception('Erro ao registrar mensagem no banco de dados');
        }
        
        error_log("Mensagem registrada com sucesso. ID: " . $message_id);
    } catch (Exception $reg_ex) {
        error_log("Exceção ao registrar mensagem: " . $reg_ex->getMessage());
        throw new Exception('Erro ao registrar mensagem: ' . $reg_ex->getMessage());
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