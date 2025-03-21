<?php
/**
 * Processamento da simulação para Landing Pages de Vendedores
 */

// Verificar método de requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?route=home'); // Redirecionar para home em caso de acesso indevido
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

$seller_id = isset($_POST['seller_id']) ? intval($_POST['seller_id']) : 0; // ID do vendedor

// Validar dados
if (empty($plan_type) || empty($plan_term) || empty($plan_id) ||
    empty($plan_credit) || empty($first_installment) || empty($other_installments) ||
    empty($name) || empty($phone) || empty($city) || empty($state) || empty($seller_id)) {
    
    // Redirecionar com erro
    $_SESSION['error_message'] = 'Por favor, preencha todos os campos obrigatórios.';
    header('Location: index.php?route=home'); // Redirecionar para home em caso de erro
    exit;
}

// Formatar telefone
$phone = formatPhone($phone);

// Dados para salvar (incluindo o ID do vendedor)
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
    'other_installments' => $other_installments,
    'seller_id' => $seller_id // Adiciona o ID do vendedor
];

// Salvar lead
$result = saveLead($leadData);

if (!$result['success']) {
    $_SESSION['error_message'] = 'Ocorreu um erro ao processar sua simulação. Por favor, tente novamente.';
    header('Location: index.php?route=home'); // Redirecionar para home
    exit;
}

// Enviar mensagem no WhatsApp (usando o token do vendedor)
$seller = getUserById($seller_id);
$whatsapp_token = $seller['whatsapp_token'] ?? '';

if ($whatsapp_token) {
    // Obter template padrão
    $templates = getMessageTemplates('simulation');
    $template = !empty($templates) ? $templates[0] : null;

    if ($template) {
        $messageData = [
            'nome' => $name,
            'tipo_veiculo' => $plan_type == 'car' ? 'Carro' : 'Moto',
            'valor_credito' => $plan_credit,
            'prazo' => $plan_term,
            'valor_primeira' => $first_installment,
            'valor_demais' => $other_installments,
            'nome_consultor' => $seller['name'] // Usar o nome do vendedor
        ];

        $message = processMessageTemplate($template['content'], $messageData);
        $result = sendWhatsAppMessage($phone, $message, null, $whatsapp_token); // Passar o token

        if (isset($result['success']) && $result['success']) {
            registerSentMessage(
                $result['lead_id'],
                $seller_id, // Registrar a mensagem como enviada pelo vendedor
                $message,
                $template['id'],
                null,
                null,
                'sent'
            );
        }
    }
}

// Redirecionar (com mensagem de sucesso)
$_SESSION['success_message'] = 'Simulação realizada com sucesso! Em breve, você receberá um contato pelo WhatsApp.';
header("Location: " . $_SERVER['HTTP_REFERER']); // Retorna para a LP do vendedor
exit;
