<?php
/**
 * Funções auxiliares do sistema
 */

/**
 * Obter URL completa do site
 */
function url($path = '') {
    $base_url = APP_URL;
    
    // Remover barra final do base_url se existir
    $base_url = rtrim($base_url, '/');
    
    // Remover barra inicial do path se existir
    $path = ltrim($path, '/');
    
    return $base_url . '/' . $path;
}

/**
 * Sanitizar entrada do usuário
 */
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
        return $input;
    }
    
    if (is_string($input)) {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    return $input;
}

/**
 * Obter conexão com o banco de dados
 */
function getConnection() {
    static $conn;
    
    if ($conn) {
        return $conn;
    }
    
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $conn;
    } catch (PDOException $e) {
        die('Erro de conexão: ' . $e->getMessage());
    }
}

/**
 * Verifica se o usuário tem permissão para acessar determinada funcionalidade
 */
function hasPermission($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    
    if (!$user) {
        return false;
    }
    
    // Admin tem acesso a tudo
    if ($user['role'] === 'admin') {
        return true;
    }
    
    // Verificar se o usuário tem o perfil requerido
    if ($required_role === 'seller' && $user['role'] === 'seller') {
        return true;
    }
    
    if ($required_role === 'manager' && ($user['role'] === 'manager' || $user['role'] === 'admin')) {
        return true;
    }
    
    return false;
}

/**
 * Gerar um token CSRF
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formatação de data e hora
 */
function formatDateTime($datetime) {
    if (empty($datetime)) {
        return '';
    }
    
    $date = new DateTime($datetime);
    return $date->format('d/m/Y H:i');
}

/**
 * Formatação de data
 */
function formatDate($date) {
    if (empty($date)) {
        return '';
    }
    
    $date = new DateTime($date);
    return $date->format('d/m/Y');
}

/**
 * Formatação de valor monetário
 */
function formatCurrency($value) {
    return number_format((float) $value, 2, ',', '.');
}

/**
 * Obter configurações do sistema
 */
function getSetting($key, $default = null) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
    $stmt->execute(['key' => $key]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        return $result['setting_value'];
    }
    
    return $default;
}

/**
 * Salvar configuração do sistema
 */
function setSetting($key, $value) {
    $conn = getConnection();
    
    // Verificar se a configuração já existe
    $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_key = :key");
    $stmt->execute(['key' => $key]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists) {
        // Atualizar
        $stmt = $conn->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = :key");
    } else {
        // Inserir
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)");
    }
    
    return $stmt->execute([
        'key' => $key,
        'value' => $value
    ]);
}

/**
 * Enviar mensagem por e-mail
 */
function sendEmail($to, $subject, $message) {
    // Implementação básica de envio de e-mail
    $headers = 'From: ' . getSetting('system_email', 'noreply@concamp.com.br') . "\r\n" .
        'Reply-To: ' . getSetting('admin_email', 'admin@concamp.com.br') . "\r\n" .
        'X-Mailer: PHP/' . phpversion() . "\r\n" .
        'MIME-Version: 1.0' . "\r\n" .
        'Content-Type: text/html; charset=UTF-8';
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Obter usuários com perfil específico
 */
function getUsersByRole($role) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE role = :role AND status = 'active' ORDER BY name");
    $stmt->execute(['role' => $role]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obter leads com filtros e paginação
 */
function getLeads($filters = [], $page = 1, $per_page = 15) {
    $conn = getConnection();
    
    // Construir consulta base
    $sql = "SELECT 
        l.id, l.name, l.email, l.phone, l.city, l.state,
        l.plan_id, l.plan_type, l.plan_credit, l.plan_model, l.plan_term,
        l.first_installment, l.other_installments, l.status, l.seller_id,
        l.created_at, u.name as seller_name
    FROM leads l
    LEFT JOIN users u ON l.seller_id = u.id
    WHERE 1=1";
    
    // Adicionar condições de filtro
    $params = [];
    
    if (isset($filters['status']) && !empty($filters['status'])) {
        $sql .= " AND l.status = :status";
        $params['status'] = $filters['status'];
    }
    
    if (isset($filters['plan_type']) && !empty($filters['plan_type'])) {
        $sql .= " AND l.plan_type = :plan_type";
        $params['plan_type'] = $filters['plan_type'];
    }
    
    if (isset($filters['seller_id']) && !empty($filters['seller_id'])) {
        $sql .= " AND l.seller_id = :seller_id";
        $params['seller_id'] = $filters['seller_id'];
    }
    
    if (isset($filters['search']) && !empty($filters['search'])) {
        $search = '%' . $filters['search'] . '%';
        $sql .= " AND (l.name LIKE :search OR l.email LIKE :search OR l.phone LIKE :search)";
        $params['search'] = $search;
    }
    
    if (isset($filters['date_from']) && !empty($filters['date_from'])) {
        $sql .= " AND DATE(l.created_at) >= :date_from";
        $params['date_from'] = $filters['date_from'];
    }
    
    if (isset($filters['date_to']) && !empty($filters['date_to'])) {
        $sql .= " AND DATE(l.created_at) <= :date_to";
        $params['date_to'] = $filters['date_to'];
    }
    
    // Contar resultados para paginação
    $count_sql = "SELECT COUNT(*) FROM (" . $sql . ") as count_query";
    $stmt = $conn->prepare($count_sql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // Calcular total de páginas
    $total_pages = ceil($total / $per_page);
    
    // Ajustar página atual
    if ($page < 1) $page = 1;
    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
    
    // Adicionar ordenação e limites para paginação
    $sql .= " ORDER BY l.created_at DESC";
    $sql .= " LIMIT " . (($page - 1) * $per_page) . ", " . $per_page;
    
    // Executar consulta principal
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'leads' => $leads,
        'total' => $total,
        'total_pages' => $total_pages,
        'current_page' => $page
    ];
}

/**
 * Obter detalhes de um lead pelo ID
 */
function getLeadById($id) {
    $conn = getConnection();
    
    $sql = "SELECT 
        l.*, u.name as seller_name 
    FROM leads l
    LEFT JOIN users u ON l.seller_id = u.id
    WHERE l.id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => $id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Obter acompanhamentos de um lead
 */
function getLeadFollowups($lead_id) {
    $conn = getConnection();
    
    $sql = "SELECT 
        f.*, u.name as user_name
    FROM followups f
    LEFT JOIN users u ON f.user_id = u.id
    WHERE f.lead_id = :lead_id
    ORDER BY f.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['lead_id' => $lead_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Adicionar acompanhamento para um lead
 */
function addLeadFollowup($lead_id, $user_id, $type, $content, $due_date = null) {
    $conn = getConnection();
    
    $sql = "INSERT INTO followups 
        (lead_id, user_id, type, content, due_date, created_at) 
    VALUES 
        (:lead_id, :user_id, :type, :content, :due_date, NOW())";
    
    $stmt = $conn->prepare($sql);
    
    $params = [
        'lead_id' => $lead_id,
        'user_id' => $user_id,
        'type' => $type,
        'content' => $content,
        'due_date' => $due_date
    ];
    
    $result = $stmt->execute($params);
    
    if ($result) {
        return [
            'success' => true,
            'id' => $conn->lastInsertId()
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Erro ao adicionar acompanhamento'
        ];
    }
}

/**
 * Atualizar status de um lead
 */
function updateLeadStatus($lead_id, $status, $user_id = null) {
    $conn = getConnection();
    
    // Obter dados atuais do lead
    $stmt = $conn->prepare("SELECT status, seller_id FROM leads WHERE id = :id");
    $stmt->execute(['id' => $lead_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        return [
            'success' => false,
            'error' => 'Lead não encontrado'
        ];
    }
    
    // Verificar se o status é diferente
    if ($lead['status'] === $status) {
        return [
            'success' => true,
            'message' => 'Status já está atualizado'
        ];
    }
    
    // Atualizar status
    $stmt = $conn->prepare("UPDATE leads SET status = :status, updated_at = NOW() WHERE id = :id");
    $result = $stmt->execute([
        'id' => $lead_id,
        'status' => $status
    ]);
    
    if (!$result) {
        return [
            'success' => false,
            'error' => 'Erro ao atualizar status'
        ];
    }
    
    // Registrar o acompanhamento
    if ($user_id) {
        $message = "Status atualizado de '{$lead['status']}' para '{$status}'.";
        addLeadFollowup($lead_id, $user_id, 'status_update', $message);
    }
    
    return [
        'success' => true,
        'message' => 'Status atualizado com sucesso'
    ];
}

/**
 * Assinar vendedor a um lead
 */
function assignLeadToSeller($lead_id, $seller_id, $user_id = null) {
    $conn = getConnection();
    
    // Verificar se o vendedor existe
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE id = :id AND role = 'seller' AND status = 'active'");
    $stmt->execute(['id' => $seller_id]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$seller) {
        return [
            'success' => false,
            'error' => 'Vendedor não encontrado ou inativo'
        ];
    }
    
    // Obter dados atuais do lead
    $stmt = $conn->prepare("SELECT seller_id FROM leads WHERE id = :id");
    $stmt->execute(['id' => $lead_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        return [
            'success' => false,
            'error' => 'Lead não encontrado'
        ];
    }
    
    // Verificar se o vendedor já está assinado
    if ($lead['seller_id'] == $seller_id) {
        return [
            'success' => true,
            'message' => 'Lead já está assinado para este vendedor'
        ];
    }
    
    // Atualizar o lead
    $stmt = $conn->prepare("UPDATE leads SET seller_id = :seller_id, updated_at = NOW() WHERE id = :id");
    $result = $stmt->execute([
        'id' => $lead_id,
        'seller_id' => $seller_id
    ]);
    
    if (!$result) {
        return [
            'success' => false,
            'error' => 'Erro ao assinar vendedor'
        ];
    }
    
    // Registrar o acompanhamento
    if ($user_id) {
        $message = "Lead atribuído ao vendedor {$seller['name']}.";
        addLeadFollowup($lead_id, $user_id, 'seller_assignment', $message);
    }
    
    return [
        'success' => true,
        'message' => 'Vendedor assinado com sucesso'
    ];
}

/**
 * Enviar mensagem via WhatsApp
 */
function sendWhatsAppMessage($number, $message, $media_url = null, $custom_token = null) {
    // Obter token do WhatsApp
    $token = $custom_token ? $custom_token : getSetting('whatsapp_api_token', '');
    
    if (empty($token)) {
        return [
            'success' => false,
            'error' => 'Token da API do WhatsApp não configurado'
        ];
    }
    
    // Formatar número de telefone
    $number = preg_replace('/[^0-9]/', '', $number);
    
    // Verificar se o número tem o formato correto
    if (strlen($number) < 10) {
        return [
            'success' => false,
            'error' => 'Número de telefone inválido'
        ];
    }
    
    // Adicionar prefixo internacional se necessário
    if (substr($number, 0, 2) !== '55') {
        $number = '55' . $number;
    }
    
    // URL da API
    $url = 'https://wheapi.com/api/send';
    
    // Dados para envio
    $data = [
        'token' => $token,
        'phone' => $number,
        'message' => $message
    ];
    
    // Se tiver mídia, adicionar URL
    if (!empty($media_url)) {
        $data['media_url'] = $media_url;
    }
    
    // Iniciar curl
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_POST, true);
    
    // Executar curl
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Verificar se houve erro
    if ($http_code != 200) {
        return [
            'success' => false,
            'error' => 'Erro ao enviar mensagem: HTTP ' . $http_code,
            'response' => $response
        ];
    }
    
    // Decodificar resposta
    $result = json_decode($response, true);
    
    // Verificar se a API retornou sucesso
    if (isset($result['success']) && $result['success']) {
        return [
            'success' => true,
            'message_id' => $result['id'] ?? null
        ];
    }
    
    return [
        'success' => false,
        'error' => $result['error'] ?? 'Erro desconhecido',
        'response' => $result
    ];
}

/**
 * Enviar mensagem com mídia via WhatsApp
 */
function sendWhatsAppMediaMessage($number, $message, $media_url, $custom_token = null) {
    // Se não tiver URL de mídia, usar envio normal
    if (empty($media_url)) {
        return sendWhatsAppMessage($number, $message, null, $custom_token);
    }
    
    // Passo 1: Enviar a mídia primeiro
    $media_result = sendWhatsAppMessage($number, '', $media_url, $custom_token);
    error_log('Resultado do envio de mídia: ' . json_encode($media_result));
    
    // Se falhar no envio da mídia, tentar enviar apenas o texto
    if (!$media_result['success']) {
        error_log('Falha no envio da mídia, tentando enviar apenas o texto...');
        $text_result = sendWhatsAppMessage($number, $message, null, $custom_token);
        
        return [
            'success' => $text_result['success'],
            'media_result' => $media_result,
            'text_result' => $text_result,
            'fallback_used' => true
        ];
    }
    
    // Passo 2: Se o envio da mídia foi bem-sucedido, aguardar um momento
    // para garantir que a mensagem anterior foi processada
    usleep(500000); // Esperar 500ms
    
    // Passo 3: Enviar apenas o texto como uma mensagem separada
    $text_result = sendWhatsAppMessage($number, $message, null, $custom_token);
    error_log('Resultado do envio apenas do texto: ' . json_encode($text_result));
    
    // Retornar sucesso se ambos os envios foram bem-sucedidos
    return [
        'success' => $media_result['success'] && $text_result['success'],
        'media_result' => $media_result,
        'text_result' => $text_result,
        'fallback_used' => true
    ];
}

/**
 * Obter planos disponíveis por tipo (car ou motorcycle) e prazo
 */
function getPlans($type, $term = null) {
    $conn = getConnection();
    
    $sql = "SELECT * FROM plans WHERE plan_type = :type AND active = 1";
    $params = ['type' => $type];
    
    if ($term) {
        $sql .= " AND term = :term";
        $params['term'] = $term;
    }
    
    $sql .= " ORDER BY credit_value ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obter um plano pelo ID
 */
function getPlanById($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT 
        id,
        COALESCE(name, '') as name,
        plan_type,
        credit_value,
        term,
        first_installment,
        other_installments,
        COALESCE(admin_fee, 0) as admin_fee,
        active
    FROM plans 
    WHERE id = :id");
    
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Obter prazos disponíveis por tipo de plano
 */
function getAvailableTerms($type) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT DISTINCT term FROM plans WHERE plan_type = :type AND active = 1 ORDER BY term ASC");
    $stmt->execute(['type' => $type]);
    
    $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $result;
}

/**
 * Calcular valor total a pagar
 */
function calculateTotalValue($first_installment, $other_installments, $term) {
    return $first_installment + ($other_installments * ($term - 1));
}

/**
 * Salvar um lead no banco de dados
 */
function saveLead($data) {
    $conn = getConnection();
    
    $sql = "INSERT INTO leads (
        name, email, phone, city, state, 
        plan_id, plan_type, plan_credit, plan_model, plan_term, 
        first_installment, other_installments, status, seller_id
    ) VALUES (
        :name, :email, :phone, :city, :state, 
        :plan_id, :plan_type, :plan_credit, :plan_model, :plan_term, 
        :first_installment, :other_installments, 'new', :seller_id
    )";
    
    $stmt = $conn->prepare($sql);
    
    $params = [
        'name' => $data['name'] ?? '',
        'email' => $data['email'] ?? '',
        'phone' => $data['phone'] ?? '',
        'city' => $data['city'] ?? '',
        'state' => $data['state'] ?? '',
        'plan_id' => $data['plan_id'] ?? null,
        'plan_type' => $data['plan_type'] ?? '',
        'plan_credit' => $data['plan_credit'] ?? 0,
        'plan_model' => $data['plan_model'] ?? '',
        'plan_term' => $data['plan_term'] ?? 0,
        'first_installment' => $data['first_installment'] ?? 0,
        'other_installments' => $data['other_installments'] ?? 0,
        'seller_id' => $data['seller_id'] ?? null
    ];
    
    $result = $stmt->execute($params);
    
    if ($result) {
        $lead_id = $conn->lastInsertId();
        
        // Notificar vendedor, se atribuído
        if (!empty($params['seller_id'])) {
            // Criar notificação para o vendedor
            $seller_id = $params['seller_id'];
            $lead_name = $params['name'];
            $plan_type = $params['plan_type'] == 'car' ? 'Carro' : 'Moto';
            $plan_credit = formatCurrency($params['plan_credit']);
            
            // Criar título e mensagem da notificação
            $title = "Novo Lead: {$lead_name}";
            $message = "Você recebeu um novo lead interessado em {$plan_type} no valor de R$ {$plan_credit}";
            
            // Enviar notificação para o vendedor
            createNotification(
                $seller_id,
                $title,
                $message,
                'lead',
                'fas fa-user-plus',
                'primary',
                $lead_id,
                'lead',
                "index.php?route=lead-detail&id={$lead_id}"
            );
        }
        
        return [
            'success' => true,
            'lead_id' => $lead_id
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Erro ao salvar lead'
        ];
    }
}

/**
 * Obter dados para o dashboard
 */
function getDashboardData($user_id, $is_admin = false, $date_range = 30) {
    $conn = getConnection();
    
    // Data inicial para o filtro
    $date_from = date('Y-m-d', strtotime("-{$date_range} days"));
    $date_to = date('Y-m-d');
    
    // Resultado
    $result = [
        'total_leads' => 0,
        'leads_by_status' => [
            'new' => 0,
            'contacted' => 0,
            'negotiating' => 0,
            'converted' => 0,
            'lost' => 0
        ],
        'leads_by_date' => [],
        'conversion_rate' => 0
    ];
    
    // Consulta SQL base
    $sql_base = "FROM leads WHERE DATE(created_at) BETWEEN :date_from AND :date_to";
    
    // Se não for admin, filtrar por vendedor
    if (!$is_admin) {
        $sql_base .= " AND seller_id = :user_id";
    }
    
    // Total de leads
    $sql = "SELECT COUNT(*) " . $sql_base;
    $stmt = $conn->prepare($sql);
    
    $params = [
        'date_from' => $date_from,
        'date_to' => $date_to
    ];
    
    if (!$is_admin) {
        $params['user_id'] = $user_id;
    }
    
    $stmt->execute($params);
    $result['total_leads'] = (int) $stmt->fetchColumn();
    
    // Leads por status
    $sql = "SELECT status, COUNT(*) as count " . $sql_base . " GROUP BY status";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['status'];
        $count = (int) $row['count'];
        
        if (isset($result['leads_by_status'][$status])) {
            $result['leads_by_status'][$status] = $count;
        }
    }
    
    // Leads por data
    $sql = "SELECT DATE(created_at) as date, COUNT(*) as count " . $sql_base . " GROUP BY DATE(created_at) ORDER BY date";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $date = $row['date'];
        $count = (int) $row['count'];
        
        $result['leads_by_date'][$date] = $count;
    }
    
    // Taxa de conversão
    $converted = $result['leads_by_status']['converted'];
    $total = $result['total_leads'];
    
    if ($total > 0) {
        $result['conversion_rate'] = round(($converted / $total) * 100, 2);
    }
    
    return $result;
}

/**
 * Obter performance de vendedores para o relatório
 */
function getSellersPerformance($date_from, $date_to) {
    $conn = getConnection();
    
    $sql = "SELECT 
                u.id, 
                u.name, 
                COUNT(l.id) as total_leads,
                SUM(CASE WHEN l.status = 'new' THEN 1 ELSE 0 END) as new_leads,
                SUM(CASE WHEN l.status = 'contacted' THEN 1 ELSE 0 END) as contacted,
                SUM(CASE WHEN l.status = 'negotiating' THEN 1 ELSE 0 END) as negotiating,
                SUM(CASE WHEN l.status = 'converted' THEN 1 ELSE 0 END) as converted,
                SUM(CASE WHEN l.status = 'lost' THEN 1 ELSE 0 END) as lost
            FROM 
                users u
            LEFT JOIN 
                leads l ON u.id = l.seller_id AND DATE(l.created_at) BETWEEN :date_from AND :date_to
            WHERE 
                u.role = 'seller' AND u.status = 'active'
            GROUP BY 
                u.id, u.name
            ORDER BY 
                converted DESC, total_leads DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'date_from' => $date_from,
        'date_to' => $date_to
    ]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obter planos mais populares para o relatório
 */
function getPopularPlans($date_from, $date_to) {
    $conn = getConnection();
    
    $sql = "SELECT 
                plan_type, 
                plan_credit, 
                plan_term, 
                plan_model,
                COUNT(*) as count
            FROM 
                leads
            WHERE 
                DATE(created_at) BETWEEN :date_from AND :date_to
            GROUP BY 
                plan_type, plan_credit, plan_term, plan_model
            ORDER BY 
                count DESC, plan_credit DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'date_from' => $date_from,
        'date_to' => $date_to
    ]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Criar uma nova notificação
 * 
 * @param int $user_id ID do usuário que receberá a notificação
 * @param string $title Título da notificação
 * @param string $message Mensagem da notificação
 * @param string $type Tipo da notificação (lead, task, message, system)
 * @param string $icon Ícone FontAwesome para a notificação
 * @param string $color Cor da notificação (primary, success, danger, warning, info)
 * @param int|null $reference_id ID de referência (como ID do lead, tarefa, etc)
 * @param string|null $reference_type Tipo de referência (como 'lead', 'task', etc)
 * @param string|null $action_url URL para ação (opcional)
 * @return array Resultado da operação
 */
function createNotification($user_id, $title, $message, $type = 'system', $icon = null, $color = 'primary', $reference_id = null, $reference_type = null, $action_url = null) {
    // Definir ícone baseado no tipo se não for fornecido
    if ($icon === null) {
        switch ($type) {
            case 'lead':
                $icon = 'fas fa-user-plus';
                break;
            case 'task':
                $icon = 'fas fa-tasks';
                break;
            case 'message':
                $icon = 'fas fa-envelope';
                break;
            default:
                $icon = 'fas fa-bell';
        }
    }
    
    $conn = getConnection();
    
    try {
        $stmt = $conn->prepare("INSERT INTO notifications (
            user_id, title, message, type, icon, color, 
            reference_id, reference_type, action_url, is_read, created_at
        ) VALUES (
            :user_id, :title, :message, :type, :icon, :color,
            :reference_id, :reference_type, :action_url, 0, NOW()
        )");
        
        $stmt->execute([
            'user_id' => $user_id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'icon' => $icon,
            'color' => $color,
            'reference_id' => $reference_id,
            'reference_type' => $reference_type,
            'action_url' => $action_url
        ]);
        
        return [
            'success' => true,
            'notification_id' => $conn->lastInsertId()
        ];
    } catch (PDOException $e) {
        error_log("Erro ao criar notificação: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Erro ao criar notificação: ' . $e->getMessage()
        ];
    }
}

/**
 * Obter notificações para um usuário
 * 
 * @param int $user_id ID do usuário
 * @param bool $unread_only Retornar apenas notificações não lidas
 * @param int $limit Limite de notificações para retornar
 * @return array Lista de notificações
 */
function getUserNotifications($user_id, $unread_only = false, $limit = 10) {
    $conn = getConnection();
    
    $sql = "SELECT * FROM notifications WHERE user_id = :user_id";
    
    if ($unread_only) {
        $sql .= " AND is_read = 0";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT :limit";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao obter notificações: " . $e->getMessage());
        return [];
    }
}

/**
 * Contar notificações não lidas para um usuário
 * 
 * @param int $user_id ID do usuário
 * @return int Número de notificações não lidas
 */
function countUnreadNotifications($user_id) {
    $conn = getConnection();
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0");
        $stmt->execute(['user_id' => $user_id]);
        
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erro ao contar notificações não lidas: " . $e->getMessage());
        return 0;
    }
}

/**
 * Marcar notificação como lida
 * 
 * @param int $notification_id ID da notificação
 * @param int $user_id ID do usuário (para verificação de segurança)
 * @return bool Resultado da operação
 */
function markNotificationAsRead($notification_id, $user_id) {
    $conn = getConnection();
    
    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            'id' => $notification_id,
            'user_id' => $user_id
        ]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Erro ao marcar notificação como lida: " . $e->getMessage());
        return false;
    }
}

/**
 * Marcar todas as notificações do usuário como lidas
 * 
 * @param int $user_id ID do usuário
 * @return bool Resultado da operação
 */
function markAllNotificationsAsRead($user_id) {
    $conn = getConnection();
    
    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0");
        $stmt->execute(['user_id' => $user_id]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao marcar todas notificações como lidas: " . $e->getMessage());
        return false;
    }
}

/**
 * Formatar tempo relativo (ex: "há 5 minutos", "há 2 horas", etc)
 * 
 * @param int $timestamp Timestamp a ser formatado
 * @return string Tempo relativo formatado
 */
function formatRelativeTime($timestamp) {
    $current_time = time();
    $diff = $current_time - $timestamp;
    
    if ($diff < 60) {
        return "Agora";
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "Há " . $minutes . ($minutes == 1 ? " minuto" : " minutos");
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "Há " . $hours . ($hours == 1 ? " hora" : " horas");
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "Há " . $days . ($days == 1 ? " dia" : " dias");
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return "Há " . $weeks . ($weeks == 1 ? " semana" : " semanas");
    } else {
        return formatDate(date('Y-m-d', $timestamp));
    }
}

/**
 * Definir mensagem de flash (mensagem que aparecerá na próxima requisição)
 * 
 * @param string $type Tipo da mensagem (success, error, warning, info)
 * @param string $message Texto da mensagem
 * @return void
 */
function setFlashMessage($type, $message) {
    if ($type === 'success') {
        $_SESSION['success_message'] = $message;
    } elseif ($type === 'error') {
        $_SESSION['error_message'] = $message;
    } else {
        $_SESSION[$type . '_message'] = $message;
    }
}

/**
 * Redirecionar para outra URL
 * 
 * @param string $url URL de destino
 * @return void
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Obter estatísticas para o dashboard
 *
 * @param int $user_id ID do usuário
 * @param bool $is_admin Se o usuário é administrador
 * @return array Estatísticas do dashboard
 */
function getDashboardStats($user_id, $is_admin) {
    $conn = getConnection();
    
    // Array para armazenar todos os resultados
    $stats = [
        'status_stats' => [
            'new' => 0,
            'contacted' => 0,
            'negotiating' => 0,
            'converted' => 0,
            'lost' => 0
        ],
        'type_stats' => [
            'car' => 0,
            'motorcycle' => 0
        ],
        'recent_stats' => [],
        'tasks_count' => 0
    ];
    
    // Verificar se as tabelas necessárias existem
    $tables = ['leads', 'follow_ups'];
    $missing_tables = [];
    
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE '$table'");
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            $missing_tables[] = $table;
        }
    }
    
    // Se alguma tabela estiver faltando, retorne os dados padrão
    if (!empty($missing_tables)) {
        return $stats;
    }
    
    // Condição SQL base para filtrar por vendedor (se não for admin)
    $seller_condition = $is_admin ? "" : "AND seller_id = :user_id";
    
    // Leads por status
    $sql = "SELECT status, COUNT(*) as count FROM leads WHERE 1=1 $seller_condition GROUP BY status";
    $stmt = $conn->prepare($sql);
    
    if (!$is_admin) {
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($stats['status_stats'][$row['status']])) {
            $stats['status_stats'][$row['status']] = (int)$row['count'];
        }
    }
    
    // Leads por tipo
    $sql = "SELECT plan_type, COUNT(*) as count FROM leads WHERE plan_type IS NOT NULL $seller_condition GROUP BY plan_type";
    $stmt = $conn->prepare($sql);
    
    if (!$is_admin) {
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($stats['type_stats'][$row['plan_type']])) {
            $stats['type_stats'][$row['plan_type']] = (int)$row['count'];
        }
    }
    
    // Leads recentes (últimos 7 dias)
    $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
            FROM leads 
            WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) $seller_condition 
            GROUP BY DATE(created_at) 
            ORDER BY date";
    
    $stmt = $conn->prepare($sql);
    
    if (!$is_admin) {
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    
    // Inicializar com os últimos 7 dias (com valor 0)
    $last_7_days = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $last_7_days[$date] = 0;
    }
    
    // Preencher com os valores reais
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $last_7_days[$row['date']] = (int)$row['count'];
    }
    
    $stats['recent_stats'] = $last_7_days;
    
    // Contar tarefas pendentes
    $sql = "SELECT COUNT(*) as count 
            FROM follow_ups 
            WHERE user_id = :user_id 
            AND type = 'task' 
            AND (status IS NULL OR status = 'pending')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['tasks_count'] = (int)$row['count'];
    
    return $stats;
}

/**
 * Obter tarefas pendentes de um usuário
 *
 * @param int $user_id ID do usuário
 * @param int $limit Limite de tarefas a retornar
 * @return array Lista de tarefas pendentes
 */
function getUserPendingTasks($user_id, $limit = 5) {
    $conn = getConnection();
    
    // Verificar se a tabela follow_ups existe
    $stmt = $conn->prepare("SHOW TABLES LIKE 'follow_ups'");
    $stmt->execute();
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        return [];
    }
    
    $sql = "SELECT f.*, l.name as lead_name
            FROM follow_ups f
            JOIN leads l ON f.lead_id = l.id
            WHERE f.user_id = :user_id 
            AND f.type = 'task'
            AND (f.status IS NULL OR f.status = 'pending')
            ORDER BY f.due_date ASC, f.created_at DESC
            LIMIT :limit";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Formatar número de telefone
 * Remove caracteres não numéricos e garante o formato correto para envio de mensagens
 * 
 * @param string $phone Número de telefone para formatar
 * @return string Telefone formatado
 */
function formatPhone($phone) {
    // Remover todos os caracteres não numéricos
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Verificar se tem o prefixo do país (55 para Brasil)
    if (strlen($phone) <= 11 && substr($phone, 0, 2) !== '55') {
        $phone = '55' . $phone;
    }
    
    // Garantir que tenha 9 dígitos para celular (incluindo o 9 à frente)
    if (strlen($phone) == 12) { // Se tem 55 + 2 DDD + 8 dígitos (telefone antigo)
        $ddd = substr($phone, 2, 2);
        $number = substr($phone, 4);
        $phone = '55' . $ddd . '9' . $number;
    }
    
    return $phone;
}

/**
 * Obter templates de mensagens
 * 
 * @param string $type Tipo do template (simulation, welcome, etc)
 * @return array Lista de templates
 */
function getMessageTemplates($type = null) {
    $conn = getConnection();
    
    // Verificar se a tabela existe
    $stmt = $conn->prepare("SHOW TABLES LIKE 'message_templates'");
    $stmt->execute();
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        // Criar um template padrão em memória se a tabela não existir
        $default_templates = [
            'simulation' => [
                [
                    'id' => 0,
                    'name' => 'Template padrão de simulação',
                    'type' => 'simulation',
                    'content' => "Olá {{nome}}, recebi sua simulação para {{tipo_veiculo}} no valor de R$ {{valor_credito}}. Vou te ajudar com mais informações. \n\nAqui está o resumo:\n- Prazo: {{prazo}} meses\n- Primeira parcela: R$ {{valor_primeira}}\n- Demais parcelas: R$ {{valor_demais}}\n\nAguarde que logo entrarei em contato. \n\nAtenciosamente,\n{{nome_consultor}}",
                    'active' => 1
                ]
            ],
            'welcome' => [
                [
                    'id' => 0,
                    'name' => 'Template padrão de boas-vindas',
                    'type' => 'welcome',
                    'content' => "Olá {{nome}}, bem-vindo ao nosso sistema de consórcios. Estamos felizes em ter você conosco!\n\nAtenciosamente,\nEquipe ConCamp",
                    'active' => 1
                ]
            ]
        ];
        
        if ($type && isset($default_templates[$type])) {
            return $default_templates[$type];
        } elseif ($type) {
            return [];
        } else {
            $all_templates = [];
            foreach ($default_templates as $templates) {
                $all_templates = array_merge($all_templates, $templates);
            }
            return $all_templates;
        }
    }
    
    // Consulta SQL base
    $sql = "SELECT * FROM message_templates WHERE active = 1";
    $params = [];
    
    // Filtrar por tipo se especificado
    if ($type) {
        $sql .= " AND type = :type";
        $params['type'] = $type;
    }
    
    $sql .= " ORDER BY name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Processar template de mensagem substituindo variáveis
 * 
 * @param string $template Template com placeholders no formato {{variavel}}
 * @param array $data Array associativo com os dados para substituição
 * @return string Mensagem processada
 */
function processMessageTemplate($template, $data) {
    // Substituir os placeholders pelos valores reais
    foreach ($data as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value, $template);
    }
    
    return $template;
}

/**
 * Obter um usuário pelo ID
 * 
 * @param int $id ID do usuário
 * @return array|false Dados do usuário ou false se não encontrado
 */
function getUserById($id) {
    $conn = getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Registrar mensagem enviada
 * 
 * @param int $lead_id ID do lead
 * @param int $user_id ID do usuário que enviou
 * @param string $message Conteúdo da mensagem
 * @param int $template_id ID do template (se aplicável)
 * @param string $media_url URL da mídia (se aplicável)
 * @param string $message_id ID da mensagem no sistema de mensagens (se aplicável)
 * @param string $status Status da mensagem (sent, failed, etc)
 * @return int|bool ID da mensagem registrada ou false em caso de erro
 */
function registerSentMessage($lead_id, $user_id, $message, $template_id = null, $media_url = null, $message_id = null, $status = 'sent') {
    $conn = getConnection();
    
    // Verificar se a tabela existe
    $stmt = $conn->prepare("SHOW TABLES LIKE 'sent_messages'");
    $stmt->execute();
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        // Criar a tabela se não existir
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `sent_messages` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `lead_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `template_id` int(11) DEFAULT NULL,
                `message` text NOT NULL,
                `media_url` varchar(255) DEFAULT NULL,
                `message_id` varchar(100) DEFAULT NULL,
                `status` varchar(20) NOT NULL DEFAULT 'sent',
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `lead_id` (`lead_id`),
                KEY `user_id` (`user_id`),
                KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $conn->exec($sql);
        } catch (PDOException $e) {
            error_log("Erro ao criar tabela sent_messages: " . $e->getMessage());
            return false;
        }
    }
    
    // Inserir o registro da mensagem
    try {
        $sql = "INSERT INTO sent_messages (lead_id, user_id, template_id, message, media_url, message_id, status, created_at)
                VALUES (:lead_id, :user_id, :template_id, :message, :media_url, :message_id, :status, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'lead_id' => $lead_id,
            'user_id' => $user_id,
            'template_id' => $template_id,
            'message' => $message,
            'media_url' => $media_url,
            'message_id' => $message_id,
            'status' => $status
        ]);
        
        return $conn->lastInsertId();
    } catch (PDOException $e) {
        error_log("Erro ao registrar mensagem enviada: " . $e->getMessage());
        return false;
    }
}