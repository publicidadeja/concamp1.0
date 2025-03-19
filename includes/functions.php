<?php
/**
 * Funções globais do sistema
 */

/**
 * Conexão com o banco de dados
 */
function getConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Erro na conexão com o banco de dados: " . $e->getMessage());
        }
    }
    
    return $conn;
}

/**
 * Formatar número para moeda brasileira
 */
function formatCurrency($value) {
    if ($value === null) {
        return '0,00';
    }
    return number_format($value, 2, ',', '.');
}

/**
 * Formatar número de telefone
 */
function formatPhone($phone) {
    // Remover qualquer caractere não numérico
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Adicionar código do país (55) se não tiver
    if (strlen($phone) <= 11) {
        $phone = '55' . $phone;
    }
    
    return $phone;
}

/**
 * Formatar data para exibição
 */
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Formatar data e hora para exibição
 */
function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Gerar URL completa
 */
function url($path = '') {
    $base_url = APP_URL ?: (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    return rtrim($base_url, '/') . '/' . ltrim($path, '/');
}

/**
 * Redirecionar para outra página
 */
function redirect($path) {
    if (!headers_sent()) {
        header('Location: ' . url($path));
        exit;
    } else {
        echo '<script>window.location.href="' . url($path) . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . url($path) . '"></noscript>';
        exit;
    }
}

/**
 * Sanitizar input
 */
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
        return $input;
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Obter configuração do banco de dados
 */
function getSetting($key) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
    $stmt->execute(['key' => $key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['setting_value'] : null;
}

/**
 * Atualizar configuração no banco de dados
 */
function updateSetting($key, $value) {
    $conn = getConnection();
    
    // Verificar se a configuração já existe
    $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_key = :key");
    $stmt->execute(['key' => $key]);
    
    if ($stmt->rowCount() > 0) {
        $stmt = $conn->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = :key");
    } else {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)");
    }
    
    return $stmt->execute(['key' => $key, 'value' => $value]);
}

/**
 * Verificar se uma string contém apenas números
 */
function isNumeric($str) {
    return preg_match('/^[0-9]+$/', $str);
}

/**
 * Enviar mensagem via WhatsApp API
 */
function sendWhatsAppMessage($number, $message, $media = null, $custom_token = null) {
    $token = $custom_token ?: getSetting('whatsapp_token') ?: WHATSAPP_API_TOKEN;
    $api_url = WHATSAPP_API_URL;
    
    // Formatar número de telefone
    $number = formatPhone($number);
    
    if ($media) {
        // Envio com mídia
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        
        $headers = [
            'Authorization: Bearer ' . $token
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $post_fields = [
            'number' => $number,
            'medias' => curl_file_create($media['path'], $media['type'], $media['name'])
        ];
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    } else {
        // Envio de texto simples
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $post_data = json_encode([
            'number' => $number,
            'body' => $message
        ]);
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    
    curl_close($ch);
    
    if ($err) {
        return [
            'success' => false,
            'error' => $err
        ];
    }
    
    $response_data = json_decode($response, true);
    
    return [
        'success' => isset($response_data['status']) && $response_data['status'] === 'success',
        'data' => $response_data
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
    $result = $stmt->execute([
        'name' => $data['name'],
        'email' => $data['email'] ?? null,
        'phone' => $data['phone'],
        'city' => $data['city'] ?? null,
        'state' => $data['state'] ?? null,
        'plan_id' => $data['plan_id'] ?? null,
        'plan_type' => $data['plan_type'],
        'plan_credit' => $data['plan_credit'],
        'plan_model' => $data['plan_model'] ?? null,
        'plan_term' => $data['plan_term'],
        'first_installment' => $data['first_installment'],
        'other_installments' => $data['other_installments'],
        'seller_id' => $data['seller_id'] ?? null
    ]);
    
    if ($result) {
        return $conn->lastInsertId();
    }
    
    return false;
}

/**
 * Registrar mensagem enviada no banco de dados
 */
function registerSentMessage($lead_id, $user_id, $message, $template_id = null, $media_url = null, $media_type = null, $status = 'sent') {
    $conn = getConnection();
    
    $sql = "INSERT INTO sent_messages (
        lead_id, user_id, template_id, message, media_url, media_type, status
    ) VALUES (
        :lead_id, :user_id, :template_id, :message, :media_url, :media_type, :status
    )";
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        'lead_id' => $lead_id,
        'user_id' => $user_id,
        'template_id' => $template_id,
        'message' => $message,
        'media_url' => $media_url,
        'media_type' => $media_type,
        'status' => $status
    ]);
    
    if ($result) {
        return $conn->lastInsertId();
    }
    
    return false;
}

/**
 * Processar template de mensagem com dados dinâmicos
 */
function processMessageTemplate($template, $data) {
    $placeholders = [
        '{nome}' => $data['nome'] ?? '',
        '{tipo_veiculo}' => $data['tipo_veiculo'] ?? '',
        '{valor_credito}' => isset($data['valor_credito']) ? formatCurrency($data['valor_credito']) : '',
        '{prazo}' => $data['prazo'] ?? '',
        '{valor_primeira}' => isset($data['valor_primeira']) ? formatCurrency($data['valor_primeira']) : '',
        '{valor_demais}' => isset($data['valor_demais']) ? formatCurrency($data['valor_demais']) : '',
        '{nome_consultor}' => $data['nome_consultor'] ?? getSetting('default_consultant')
    ];
    
    return str_replace(array_keys($placeholders), array_values($placeholders), $template);
}

/**
 * Obter modelos de mensagens por categoria
 */
function getMessageTemplates($category = null) {
    $conn = getConnection();
    
    $sql = "SELECT * FROM message_templates WHERE active = 1";
    $params = [];
    
    if ($category) {
        $sql .= " AND category = :category";
        $params['category'] = $category;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obter modelo de mensagem pelo ID
 */
function getMessageTemplateById($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM message_templates WHERE id = :id");
    $stmt->execute(['id' => $id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Verificar se um e-mail já está em uso
 */
function emailExists($email, $exclude_id = null) {
    $conn = getConnection();
    
    $sql = "SELECT id FROM users WHERE email = :email";
    $params = ['email' => $email];
    
    if ($exclude_id) {
        $sql .= " AND id != :id";
        $params['id'] = $exclude_id;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->rowCount() > 0;
}

/**
 * Obter leads com paginação e filtros
 */
function getLeads($filters = [], $page = 1, $per_page = 10) {
    $conn = getConnection();
    
    $sql = "SELECT l.*, u.name as seller_name 
            FROM leads l 
            LEFT JOIN users u ON l.seller_id = u.id 
            WHERE 1=1";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($filters['status'])) {
        $sql .= " AND l.status = :status";
        $params['status'] = $filters['status'];
    }
    
    if (!empty($filters['plan_type'])) {
        $sql .= " AND l.plan_type = :plan_type";
        $params['plan_type'] = $filters['plan_type'];
    }
    
    if (!empty($filters['seller_id'])) {
        $sql .= " AND l.seller_id = :seller_id";
        $params['seller_id'] = $filters['seller_id'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (l.name LIKE :search OR l.email LIKE :search OR l.phone LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND DATE(l.created_at) >= :date_from";
        $params['date_from'] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND DATE(l.created_at) <= :date_to";
        $params['date_to'] = $filters['date_to'];
    }
    
    // Calcular total para paginação
    $count_sql = "SELECT COUNT(*) FROM (" . $sql . ") as counted";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();
    
    // Ordenação e paginação
    $sql .= " ORDER BY l.created_at DESC";
    $sql .= " LIMIT :offset, :limit";
    
    $offset = ($page - 1) * $per_page;
    $params['offset'] = $offset;
    $params['limit'] = $per_page;
    
    $stmt = $conn->prepare($sql);
    
    // PDO não aceita LIMIT com parâmetros nomeados, então precisamos fazer o bind manualmente
    foreach ($params as $key => $value) {
        if ($key === 'offset' || $key === 'limit') {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':' . $key, $value);
        }
    }
    
    $stmt->execute();
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular total de páginas
    $total_pages = ceil($total / $per_page);
    
    return [
        'leads' => $leads,
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => $total_pages
    ];
}

/**
 * Obter um lead pelo ID
 */
function getLeadById($id) {
    $conn = getConnection();
    
    $sql = "SELECT l.*, u.name as seller_name 
            FROM leads l 
            LEFT JOIN users u ON l.seller_id = u.id 
            WHERE l.id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => $id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Atualizar status de um lead
 */
function updateLeadStatus($id, $status, $seller_id = null) {
    $conn = getConnection();
    
    $sql = "UPDATE leads SET status = :status";
    $params = [
        'id' => $id,
        'status' => $status
    ];
    
    if ($seller_id) {
        $sql .= ", seller_id = :seller_id";
        $params['seller_id'] = $seller_id;
    }
    
    $sql .= " WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Adicionar follow-up para um lead
 */
function addFollowUp($lead_id, $user_id, $type, $content, $due_date = null) {
    $conn = getConnection();
    
    $sql = "INSERT INTO follow_ups (lead_id, user_id, type, content, due_date) 
            VALUES (:lead_id, :user_id, :type, :content, :due_date)";
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        'lead_id' => $lead_id,
        'user_id' => $user_id,
        'type' => $type,
        'content' => $content,
        'due_date' => $due_date
    ]);
    
    if ($result) {
        return $conn->lastInsertId();
    }
    
    return false;
}

/**
 * Obter follow-ups de um lead
 */
function getLeadFollowUps($lead_id) {
    $conn = getConnection();
    
    $sql = "SELECT f.*, u.name as user_name 
            FROM follow_ups f 
            JOIN users u ON f.user_id = u.id 
            WHERE f.lead_id = :lead_id 
            ORDER BY f.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['lead_id' => $lead_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obter mensagens enviadas para um lead
 */
function getLeadMessages($lead_id) {
    $conn = getConnection();
    
    $sql = "SELECT m.*, u.name as user_name 
            FROM sent_messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.lead_id = :lead_id 
            ORDER BY m.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['lead_id' => $lead_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obter tarefas pendentes de um usuário
 */
function getUserPendingTasks($user_id) {
    $conn = getConnection();
    
    $sql = "SELECT f.*, l.name as lead_name 
            FROM follow_ups f 
            JOIN leads l ON f.lead_id = l.id 
            WHERE f.user_id = :user_id 
            AND f.type = 'task' 
            AND f.status = 'pending' 
            ORDER BY f.due_date ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Marcar tarefa como concluída
 */
function completeTask($task_id) {
    $conn = getConnection();
    
    $sql = "UPDATE follow_ups SET status = 'completed' WHERE id = :id AND type = 'task'";
    
    $stmt = $conn->prepare($sql);
    return $stmt->execute(['id' => $task_id]);
}

/**
 * Obter estatísticas para o dashboard
 */
function getDashboardStats($user_id = null, $is_admin = false) {
    $conn = getConnection();
    
    // Leads por status
    $sql_status = "SELECT status, COUNT(*) as count FROM leads";
    $params_status = [];
    
    if (!$is_admin && $user_id) {
        $sql_status .= " WHERE seller_id = :user_id";
        $params_status['user_id'] = $user_id;
    }
    
    $sql_status .= " GROUP BY status";
    
    $stmt = $conn->prepare($sql_status);
    $stmt->execute($params_status);
    $status_stats = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status_stats[$row['status']] = $row['count'];
    }
    
    // Leads por tipo de plano
    $sql_type = "SELECT plan_type, COUNT(*) as count FROM leads";
    $params_type = [];
    
    if (!$is_admin && $user_id) {
        $sql_type .= " WHERE seller_id = :user_id";
        $params_type['user_id'] = $user_id;
    }
    
    $sql_type .= " GROUP BY plan_type";
    
    $stmt = $conn->prepare($sql_type);
    $stmt->execute($params_type);
    $type_stats = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $type_stats[$row['plan_type']] = $row['count'];
    }
    
    // Leads dos últimos 7 dias
    $sql_recent = "SELECT DATE(created_at) as date, COUNT(*) as count FROM leads";
    $params_recent = [];
    
    if (!$is_admin && $user_id) {
        $sql_recent .= " WHERE seller_id = :user_id";
        $params_recent['user_id'] = $user_id;
    }
    
    $sql_recent .= " GROUP BY DATE(created_at)
                     ORDER BY DATE(created_at) DESC
                     LIMIT 7";
    
    $stmt = $conn->prepare($sql_recent);
    $stmt->execute($params_recent);
    $recent_stats = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $recent_stats[$row['date']] = $row['count'];
    }
    
    // Tarefas pendentes
    $sql_tasks = "SELECT COUNT(*) as count FROM follow_ups WHERE type = 'task' AND status = 'pending'";
    $params_tasks = [];
    
    if (!$is_admin && $user_id) {
        $sql_tasks .= " AND user_id = :user_id";
        $params_tasks['user_id'] = $user_id;
    }
    
    $stmt = $conn->prepare($sql_tasks);
    $stmt->execute($params_tasks);
    $tasks_count = $stmt->fetchColumn();
    
    return [
        'status_stats' => $status_stats,
        'type_stats' => $type_stats,
        'recent_stats' => $recent_stats,
        'tasks_count' => $tasks_count
    ];
}

/**
 * Obter usuários com uma função específica
 */
function getUsersByRole($role = null) {
    $conn = getConnection();
    
    $sql = "SELECT * FROM users WHERE status = 'active'";
    $params = [];
    
    if ($role) {
        $sql .= " AND role = :role";
        $params['role'] = $role;
    }
    
    $sql .= " ORDER BY name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obter dados de um usuário pelo ID
 */
function getUserById($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Atualizar dados de um usuário
 */
function updateUser($id, $data) {
    $conn = getConnection();
    
    $fields = [];
    $params = ['id' => $id];
    
    foreach ($data as $key => $value) {
        $fields[] = "$key = :$key";
        $params[$key] = $value;
    }
    
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return [
            'success' => true,
            'rows_affected' => $stmt->rowCount()
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Obter dados de um usuário pelo nome da landing page.
 */
function getUserByLandingPageName($landing_page_name) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE landing_page_name = :name AND role = 'seller' AND status = 'active'");
    $stmt->execute(['name' => $landing_page_name]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Verificar se o usuário tem permissão para acessar determinada funcionalidade
 */
function hasPermission($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    
    if ($role === 'admin' && $user['role'] === 'admin') {
        return true;
    }
    
    if ($role === 'seller' && ($user['role'] === 'seller' || $user['role'] === 'admin')) {
        return true;
    }
    
    if ($role === 'manager' && ($user['role'] === 'manager' || $user['role'] === 'admin')) {
        return true;
    }
    
    return false;
}

/**
 * Criar token CSRF para proteção de formulários
 */
function createCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obter relatório de desempenho dos vendedores
 */
function getSellerPerformanceReport($date_from, $date_to) {
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
 * Obter relatório de planos populares
 */
function getPopularPlansReport($date_from, $date_to) {
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
