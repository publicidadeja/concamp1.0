<?php
/**
 * Processamento da simulação
 */

// Verificar método de requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?route=simulador');
    exit;
}

// Obter dados do formulário
$plan_type = isset($_POST['plan_type']) ? sanitize($_POST['plan_type']) : '';
$plan_term = isset($_POST['plan_term']) ? intval($_POST['plan_term']) : 0;
$plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
$plan_credit = isset($_POST['plan_credit']) ? floatval($_POST['plan_credit']) : 0;
$plan_model = isset($_POST['plan_model']) ? sanitize($_POST['plan_model']) : '';
$first_installment = isset($_POST['first_installment']) ? floatval($_POST['first_installment']) : 0;
$other_installments = isset($_POST['other_installments']) ? floatval($_POST['other_installments']) : 0;

$name = isset($_POST['name']) ? sanitize($_POST['name']) : '';
$email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
$phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
$city = isset($_POST['city']) ? sanitize($_POST['city']) : '';
$state = isset($_POST['state']) ? sanitize($_POST['state']) : '';

// Validar dados
if (empty($plan_type) || empty($plan_term) || empty($plan_id) || 
    empty($plan_credit) || empty($first_installment) || empty($other_installments) || 
    empty($name) || empty($phone) || empty($city) || empty($state)) {
    
    // Redirecionar com erro
    $_SESSION['error_message'] = 'Por favor, preencha todos os campos obrigatórios.';
    header('Location: index.php?route=simulador');
    exit;
}

// Formatar telefone para padrão E.164
$phone = formatPhone($phone);

// Dados para salvar no banco de dados
$leadData = [
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'city' => $city,
    'state' => $state,
    'plan_id' => $plan_id,
    'plan_type' => $plan_type,
    'plan_credit' => $plan_credit,
    'plan_model' => $plan_model,
    'plan_term' => $plan_term,
    'first_installment' => $first_installment,
    'other_installments' => $other_installments
];

// Verificar se existe vendedor disponível para atribuir automaticamente
$seller_id = 0;
$available_sellers = getUsersByRole('seller', true); // Obter vendedores ativos
if (!empty($available_sellers)) {
    // Selecionar um vendedor aleatoriamente ou pelo critério definido
    $selected_seller = $available_sellers[array_rand($available_sellers)];
    $seller_id = $selected_seller['id'];
    $leadData['seller_id'] = $seller_id;
}

// Salvar lead no banco de dados
$result = saveLead($leadData);

if (!$result['success']) {
    // Erro ao salvar
    $_SESSION['error_message'] = 'Ocorreu um erro ao processar sua simulação. Por favor, tente novamente.';
    header('Location: index.php?route=simulador');
    exit;
}

// Lead ID para uso posterior
$lead_id = $result['lead_id'];

// Se temos um vendedor atribuído, forçar a atribuição e verificar
if ($seller_id > 0) {
    $seller = getUserById($seller_id);
    if ($seller) {
        // Forçar uma atualização direta na tabela leads para garantir a atribuição
        $conn = getConnection();
        
        // Verificar estado atual
        $stmt = $conn->prepare("SELECT seller_id FROM leads WHERE id = :id");
        $stmt->execute(['id' => $lead_id]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Log para debug
        error_log("Lead ID: " . $lead_id . " - Vendedor atual: " . ($lead['seller_id'] ?? 'nenhum') . " - Novo vendedor: " . $seller_id);
        
        // Atualizar o vendedor diretamente na tabela
        $stmt = $conn->prepare("UPDATE leads SET seller_id = :seller_id WHERE id = :id");
        $result = $stmt->execute(['id' => $lead_id, 'seller_id' => $seller_id]);
        
        // Verificar resultado da atualização
        error_log("Atualização do vendedor: " . ($result ? "SUCESSO" : "FALHA"));
        
        // Verificar após a atualização para confirmar
        $stmt = $conn->prepare("SELECT seller_id FROM leads WHERE id = :id");
        $stmt->execute(['id' => $lead_id]);
        $updatedLead = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Lead ID: " . $lead_id . " - Vendedor após atualização: " . ($updatedLead['seller_id'] ?? 'nenhum'));
        
        // Registrar na timeline
        $content = "Lead atribuído automaticamente para o vendedor: " . $seller['name'];
        addFollowUp($lead_id, 1, 'note', $content); // Admin ID = 1
    }
}

// Enviar mensagem no WhatsApp
// Obter template padrão
$templates = getMessageTemplates('simulation');
$template = null;

if (!empty($templates)) {
    $template = $templates[0];
}

if ($template) {
    // Preparar dados para substituição nas variáveis do template
    $messageData = [
        'nome' => $name,
        'tipo_veiculo' => $plan_type == 'car' ? 'Carro' : 'Moto',
        'valor_credito' => $plan_credit,
        'prazo' => $plan_term,
        'valor_primeira' => $first_installment,
        'valor_demais' => $other_installments,
        'nome_consultor' => getSetting('default_consultant') ?: 'ConCamp'
    ];
    
    // Substituir variáveis no template
    $message = processMessageTemplate($template['content'], $messageData);
    
    // Verificar se ainda existem placeholders no formato {variavel} e fazer substituição direta
    foreach ($messageData as $key => $value) {
        $message = str_replace('{' . $key . '}', $value, $message);
    }
    
    // Log da mensagem processada
    error_log("Mensagem após processamento de variáveis: " . substr($message, 0, 100) . "...");
    
    // Obter o token do admin (ID 1)
    $admin = getUserById(1);
    $admin_token = $admin['whatsapp_token'] ?? '';
    
    // Log para depuração
    error_log("Admin token para envio: " . ($admin_token ? substr($admin_token, 0, 3) . '...' : 'Não encontrado'));
    
    // Enviar via WhatsApp API passando explicitamente o token do admin
    $result = sendWhatsAppMessage($phone, $message, null, $admin_token);
    
    // Registrar mensagem enviada
    if (isset($result['success']) && $result['success']) {
        // Usar ID do administrador como padrão para mensagens automáticas
        $admin_id = 1; // ID do usuário admin
        
        registerSentMessage(
            $result['lead_id'],
            $admin_id,
            $message,
            $template['id'],
            null,
            null,
            'sent'
        );
    }
}

// Redirecionar para página de sucesso
$_SESSION['success_message'] = 'Simulação realizada com sucesso! Em breve, entraremos em contato pelo WhatsApp.';
$_SESSION['simulation_data'] = [
    'name' => $name,
    'plan_type' => $plan_type == 'car' ? 'Carro' : 'Moto',
    'plan_credit' => $plan_credit,
    'plan_term' => $plan_term,
    'first_installment' => $first_installment,
    'other_installments' => $other_installments
];

// Redirecionar para a home (que agora é a landing page)
header('Location: index.php?simulation_success=true');
exit;
