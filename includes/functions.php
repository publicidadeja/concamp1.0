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
 * 
 * @param mixed $input Dado a ser sanitizado
 * @param string $context Contexto da sanitização (html, attr, js, url, css, sql)
 * @return mixed Dados sanitizados
 */
function sanitize($input, $context = 'html') {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value, $context);
        }
        return $input;
    }
    
    if (!is_string($input)) {
        return $input;
    }
    
    // Limpar espaços em branco no início e fim
    $input = trim($input);
    
    switch ($context) {
        case 'html':
            // Sanitização básica para contexto HTML
            return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
        case 'attr':
            // Sanitização para atributos HTML
            return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
        case 'js':
            // Sanitização para uso em JavaScript
            $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // Escapar caracteres que poderiam fechar tags de script
            $input = str_replace(
                ['<', '>', '/', '\\', '"', "'", '`'],
                ['\\u003C', '\\u003E', '\\u002F', '\\u005C', '\\u0022', '\\u0027', '\\u0060'],
                $input
            );
            return $input;
            
        case 'url':
            // Sanitização para URLs
            if (filter_var($input, FILTER_VALIDATE_URL) === false) {
                // Se não for uma URL válida, remover qualquer caractere potencialmente perigoso
                return filter_var($input, FILTER_SANITIZE_URL);
            }
            return $input;
            
        case 'css':
            // Sanitização para uso em CSS
            return preg_replace('/[^a-zA-Z0-9\-_\s\.#(),:]/', '', $input);
            
        case 'sql':
            // Sanitização para strings SQL sem usar prepared statements
            // Note: Isso não substitui prepared statements, apenas é uma camada adicional de proteção
            $conn = getConnection();
            return $conn->quote($input);
            
        default:
            // Sanitização padrão
            return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

/**
 * Função para sanitizar entradas de acordo com o tipo esperado
 *
 * @param mixed $input Valor a ser sanitizado
 * @param string $type Tipo esperado (int, float, email, url, etc)
 * @return mixed Valor sanitizado
 */
function sanitizeType($input, $type = 'string') {
    if ($input === null || $input === '') {
        return null;
    }
    
    switch ($type) {
        case 'int':
        case 'integer':
            return filter_var($input, FILTER_VALIDATE_INT) !== false
                ? (int) $input 
                : null;
            
        case 'float':
        case 'double':
            return filter_var($input, FILTER_VALIDATE_FLOAT) !== false
                ? (float) $input 
                : null;
            
        case 'bool':
        case 'boolean':
            return filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL)
                ? filter_var($input, FILTER_SANITIZE_EMAIL)
                : null;
            
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL)
                ? filter_var($input, FILTER_SANITIZE_URL)
                : null;
            
        case 'string':
        default:
            return is_string($input) ? sanitize($input) : (string) $input;
    }
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
 * Obter configurações do sistema com cache em memória
 * 
 * @param string $key Chave da configuração
 * @param mixed $default Valor padrão se a configuração não existir
 * @return mixed Valor da configuração
 */
function getSetting($key, $default = null) {
    // Cache estático para evitar múltiplas consultas ao banco
    static $settings_cache = [];
    
    // Verificar se já temos o valor em cache
    if (isset($settings_cache[$key])) {
        return $settings_cache[$key];
    }
    
    // Buscar do banco de dados
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
    $stmt->execute(['key' => $key]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Armazenar em cache e retornar
        $settings_cache[$key] = $result['setting_value'];
        return $result['setting_value'];
    }
    
    // Valor padrão (não armazenamos em cache para permitir mudanças futuras)
    return $default;
}

/**
 * Salvar configuração do sistema
 * 
 * @param string $key Chave da configuração
 * @param mixed $value Valor da configuração
 * @return bool Sucesso ou falha
 */
function setSetting($key, $value) {
    static $settings_cache = [];
    $conn = getConnection();
    
    try {
        // Usar transação para garantir consistência
        $conn->beginTransaction();
        
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
        
        $result = $stmt->execute([
            'key' => $key,
            'value' => $value
        ]);
        
        // Limpar o cache para esta chave
        if (isset($settings_cache[$key])) {
            unset($settings_cache[$key]);
        }
        
        $conn->commit();
        return $result;
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Erro ao salvar configuração: " . $e->getMessage());
        return false;
    }
}

/**
 * Atualizar configuração do sistema (alias para setSetting para compatibilidade)
 * 
 * @param string $key Chave da configuração
 * @param mixed $value Valor da configuração
 * @return bool Sucesso ou falha
 */
function updateSetting($key, $value) {
    return setSetting($key, $value);
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
 * Obter lista de usuários com filtros
 * 
 * @param array $filters Filtros para consulta (role, status, search)
 * @return array Lista de usuários
 */
function getUsers($filters = []) {
    $conn = getConnection();
    
    // Consulta base
    $sql = "SELECT id, name, email, role, status, created_at FROM users WHERE 1=1";
    $params = [];
    
    // Aplicar filtros de forma segura
    if (!empty($filters['role'])) {
        $sql .= " AND role = :role";
        $params['role'] = $filters['role'];
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND status = :status";
        $params['status'] = $filters['status'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (name LIKE :search OR email LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }
    
    // Ordenação
    $sql .= " ORDER BY name ASC";
    
    // Executar consulta
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    
    // Contar resultados para paginação - abordagem mais eficiente
    $count_sql = "SELECT COUNT(*) FROM leads l LEFT JOIN users u ON l.seller_id = u.id WHERE 1=1";
    
    // Adicionar as mesmas condições da consulta principal
    if (isset($filters['status']) && !empty($filters['status'])) {
        $count_sql .= " AND l.status = :status";
    }
    
    if (isset($filters['plan_type']) && !empty($filters['plan_type'])) {
        $count_sql .= " AND l.plan_type = :plan_type";
    }
    
    if (isset($filters['seller_id']) && !empty($filters['seller_id'])) {
        $count_sql .= " AND l.seller_id = :seller_id";
    }
    
    if (isset($filters['search']) && !empty($filters['search'])) {
        $count_sql .= " AND (l.name LIKE :search OR l.email LIKE :search OR l.phone LIKE :search)";
    }
    
    if (isset($filters['date_from']) && !empty($filters['date_from'])) {
        $count_sql .= " AND DATE(l.created_at) >= :date_from";
    }
    
    if (isset($filters['date_to']) && !empty($filters['date_to'])) {
        $count_sql .= " AND DATE(l.created_at) <= :date_to";
    }
    
    $stmt = $conn->prepare($count_sql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // Calcular total de páginas
    $pages = ceil($total / $per_page);
    
    // Ajustar página atual
    if ($page < 1) $page = 1;
    if ($page > $pages && $pages > 0) $page = $pages;
    
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
        'pages' => $pages,
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
function getLeadFollowups($lead_id, $page = 1, $limit = 10) {
    $conn = getConnection();
    
    // Verificar se a tabela followups existe
    try {
        $check = $conn->query("SHOW TABLES LIKE 'followups'");
        if ($check->rowCount() == 0) {
            // Tabela não existe, criar a tabela
            $conn->exec("CREATE TABLE IF NOT EXISTS `followups` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `lead_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `type` enum('note','task','reminder','call','email','whatsapp') NOT NULL DEFAULT 'note',
                `content` text NOT NULL,
                `status` enum('pending','completed','canceled') DEFAULT 'pending',
                `due_date` datetime DEFAULT NULL,
                `completed_at` datetime DEFAULT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `lead_id` (`lead_id`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            error_log("Tabela followups criada automaticamente.");
            
            // Retornar array vazio pois não há dados ainda
            return [
                'follow_ups' => [],
                'total' => 0,
                'pages' => 0
            ];
        }
    } catch (PDOException $e) {
        error_log("Erro ao verificar ou criar tabela followups: " . $e->getMessage());
        return [
            'follow_ups' => [],
            'total' => 0,
            'pages' => 0
        ];
    }
    
    try {
        // Calcular o offset para paginação
        $offset = ($page - 1) * $limit;
        
        // Consulta para contar o total de registros
        $count_sql = "SELECT COUNT(*) FROM followups WHERE lead_id = :lead_id";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->execute(['lead_id' => $lead_id]);
        $total = $count_stmt->fetchColumn();
        
        // Calcular o número de páginas
        $pages = ceil($total / $limit);
        
        // Consulta principal com paginação
        $sql = "SELECT 
            f.*, u.name as user_name
        FROM followups f
        LEFT JOIN users u ON f.user_id = u.id
        WHERE f.lead_id = :lead_id
        ORDER BY f.created_at DESC
        LIMIT :offset, :limit";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':lead_id', $lead_id, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $follow_ups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'follow_ups' => $follow_ups,
            'total' => $total,
            'pages' => $pages
        ];
    } catch (PDOException $e) {
        error_log("Erro ao buscar followups: " . $e->getMessage());
        return [
            'follow_ups' => [],
            'total' => 0,
            'pages' => 0
        ];
    }
}

/**
 * Adicionar acompanhamento para um lead
 */
function addLeadFollowup($lead_id, $user_id, $type, $content, $due_date = null) {
    $conn = getConnection();
    
    // Verificar se a tabela followups existe
    try {
        $check = $conn->query("SHOW TABLES LIKE 'followups'");
        if ($check->rowCount() == 0) {
            // Tabela não existe, criar a tabela
            $conn->exec("CREATE TABLE IF NOT EXISTS `followups` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `lead_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `type` enum('note','task','reminder','call','email','whatsapp') NOT NULL DEFAULT 'note',
                `content` text NOT NULL,
                `status` enum('pending','completed','canceled') DEFAULT 'pending',
                `due_date` datetime DEFAULT NULL,
                `completed_at` datetime DEFAULT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `lead_id` (`lead_id`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            error_log("Tabela followups criada automaticamente ao adicionar novo followup.");
        }
    } catch (PDOException $e) {
        error_log("Erro ao verificar ou criar tabela followups: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Erro ao verificar estrutura do banco de dados: ' . $e->getMessage()
        ];
    }
    
    try {
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
    } catch (PDOException $e) {
        error_log("Erro ao adicionar followup: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Erro ao adicionar acompanhamento: ' . $e->getMessage()
        ];
    }
}

/**
 * Alias para addLeadFollowup para compatibilidade com código existente
 * 
 * @param int $lead_id ID do lead
 * @param int $user_id ID do usuário
 * @param string $type Tipo de acompanhamento (note, task, reminder)
 * @param string $content Conteúdo do acompanhamento
 * @param string|null $due_date Data de vencimento (para tarefas e lembretes)
 * @return int|bool ID do acompanhamento criado ou false em caso de erro
 */
/**
 * Obter mensagens de um lead com paginação
 * 
 * @param int $lead_id ID do lead
 * @param int $page Número da página atual
 * @param int $limit Limite de itens por página
 * @return array Array com mensagens, total e número de páginas
 */
function getLeadMessages($lead_id, $page = 1, $limit = 10) {
    $conn = getConnection();
    
    // Verificar se a tabela lead_messages existe
    try {
        $check = $conn->query("SHOW TABLES LIKE 'lead_messages'");
        if ($check->rowCount() == 0) {
            // Tabela não existe, criar a tabela
            $conn->exec("CREATE TABLE IF NOT EXISTS `lead_messages` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `lead_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `direction` enum('incoming','outgoing') NOT NULL DEFAULT 'outgoing',
                `channel` enum('whatsapp','email','sms','system') NOT NULL DEFAULT 'system',
                `content` text NOT NULL,
                `status` enum('sent','delivered','read','failed') DEFAULT 'sent',
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `lead_id` (`lead_id`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            error_log("Tabela lead_messages criada automaticamente.");
            
            // Retornar array vazio pois não há dados ainda
            return [
                'messages' => [],
                'total' => 0,
                'pages' => 0
            ];
        }
    } catch (PDOException $e) {
        error_log("Erro ao verificar ou criar tabela lead_messages: " . $e->getMessage());
        return [
            'messages' => [],
            'total' => 0,
            'pages' => 0
        ];
    }
    
    try {
        // Calcular o offset para paginação
        $offset = ($page - 1) * $limit;
        
        // Consulta para contar o total de registros
        $count_sql = "SELECT COUNT(*) FROM lead_messages WHERE lead_id = :lead_id";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->execute(['lead_id' => $lead_id]);
        $total = $count_stmt->fetchColumn();
        
        // Calcular o número de páginas
        $pages = ceil($total / $limit);
        
        // Consulta principal com paginação
        $sql = "SELECT 
            m.*, u.name as user_name
        FROM lead_messages m
        LEFT JOIN users u ON m.user_id = u.id
        WHERE m.lead_id = :lead_id
        ORDER BY m.created_at DESC
        LIMIT :offset, :limit";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':lead_id', $lead_id, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'messages' => $messages,
            'total' => $total,
            'pages' => $pages
        ];
    } catch (PDOException $e) {
        error_log("Erro ao buscar mensagens: " . $e->getMessage());
        return [
            'messages' => [],
            'total' => 0,
            'pages' => 0
        ];
    }
}

/**
 * Adicionar mensagem para um lead
 * 
 * @param int $lead_id ID do lead
 * @param int $user_id ID do usuário
 * @param string $content Conteúdo da mensagem
 * @param string $direction Direção da mensagem (incoming, outgoing)
 * @param string $channel Canal da mensagem (whatsapp, email, sms, system)
 * @return array Resultado da operação
 */
function addLeadMessage($lead_id, $user_id, $content, $direction = 'outgoing', $channel = 'system') {
    $conn = getConnection();
    
    // Verificar se a tabela lead_messages existe
    try {
        $check = $conn->query("SHOW TABLES LIKE 'lead_messages'");
        if ($check->rowCount() == 0) {
            // Tabela não existe, criar a tabela
            $conn->exec("CREATE TABLE IF NOT EXISTS `lead_messages` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `lead_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `direction` enum('incoming','outgoing') NOT NULL DEFAULT 'outgoing',
                `channel` enum('whatsapp','email','sms','system') NOT NULL DEFAULT 'system',
                `content` text NOT NULL,
                `status` enum('sent','delivered','read','failed') DEFAULT 'sent',
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `lead_id` (`lead_id`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            error_log("Tabela lead_messages criada automaticamente ao adicionar nova mensagem.");
        }
    } catch (PDOException $e) {
        error_log("Erro ao verificar ou criar tabela lead_messages: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Erro ao verificar estrutura do banco de dados: ' . $e->getMessage()
        ];
    }
    
    try {
        $sql = "INSERT INTO lead_messages 
            (lead_id, user_id, direction, channel, content, status, created_at) 
        VALUES 
            (:lead_id, :user_id, :direction, :channel, :content, 'sent', NOW())";
        
        $stmt = $conn->prepare($sql);
        
        $params = [
            'lead_id' => $lead_id,
            'user_id' => $user_id,
            'direction' => $direction,
            'channel' => $channel,
            'content' => $content
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
                'error' => 'Erro ao adicionar mensagem'
            ];
        }
    } catch (PDOException $e) {
        error_log("Erro ao adicionar mensagem: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Erro ao adicionar mensagem: ' . $e->getMessage()
        ];
    }
}

function addFollowUp($lead_id, $user_id, $type, $content, $due_date = null) {
    $result = addLeadFollowup($lead_id, $user_id, $type, $content, $due_date);
    
    if (isset($result['success']) && $result['success'] && isset($result['id'])) {
        return $result['id'];
    }
    
    return false;
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
 * 
 * @param string $number Número do telefone
 * @param string $message Mensagem a ser enviada
 * @param string|array|null $media Caminho do arquivo ou array com dados da mídia (opcional)
 * @param string|null $custom_token Token personalizado (opcional)
 * @return array Status e dados da mensagem
 */
function sendWhatsAppMessage($number, $message, $media = null, $custom_token = null) {
    error_log('============ INÍCIO DA FUNÇÃO GLOBAL sendWhatsAppMessage =============');
    error_log('Número: ' . $number);
    error_log('Token personalizado: ' . ($custom_token ? 'Sim (' . substr($custom_token, 0, 5) . '...)' : 'Não'));
    error_log('Mensagem: ' . substr($message, 0, 30) . (strlen($message) > 30 ? '...' : ''));
    error_log('Mídia: ' . ($media ? (is_array($media) ? 'Array de dados' : $media) : 'Nenhuma'));

    try {
        // Verificar token (usar token personalizado ou global)
        $token = $custom_token ? $custom_token : getSetting('whatsapp_api_token', '');
        
        if (empty($token)) {
            error_log('Erro: Token da API WhatsApp não configurado');
            return [
                'success' => false,
                'error' => 'Token da API WhatsApp não configurado'
            ];
        }
        
        // Formatar número de telefone
        $number = preg_replace('/[^0-9]/', '', $number);
        
        // Verificar se o número tem pelo menos 10 dígitos
        if (strlen($number) < 10) {
            error_log('Erro: Número de telefone inválido - ' . $number);
            return [
                'success' => false,
                'error' => 'Número de telefone inválido'
            ];
        }
        
        // Adicionar o prefixo internacional se não houver
        if (substr($number, 0, 2) !== '55') {
            $number = '55' . $number;
        }
        
        // URL da API
        $api_url = WHATSAPP_API_URL;
        error_log('URL da API: ' . $api_url);
        
        // Log da tentativa com o início do token (para depuração)
        error_log('Enviando WhatsApp para ' . $number . ' com token ' . substr($token, 0, 5) . '...');
        
        // Processar tipo de requisição com base na existência de mídia
        if ($media) {
            error_log('MODO: Enviando mensagem com mídia via WhatsApp API');
            
            // Usar multipart/form-data para enviar arquivos
            $curl = curl_init();
            
            // Configurar para envio de mídia
            curl_setopt_array($curl, [
                CURLOPT_URL => $api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token
                ]
            ]);
            
            // Preparar o campo de mídia
            if (is_array($media)) {
                // Se for array, usa os dados fornecidos
                $media_file = $media['path'];
                error_log('Usando dados de mídia do array: ' . $media_file);
            } else {
                // Se for string, usa como caminho do arquivo
                $media_file = $media;
                error_log('Usando caminho de mídia: ' . $media_file);
            }
            
            // Verificar se o arquivo existe
            if (!file_exists($media_file)) {
                error_log('Erro: Arquivo de mídia não encontrado - ' . $media_file);
                return [
                    'success' => false,
                    'error' => 'Arquivo de mídia não encontrado'
                ];
            }
            
            // Preparar formulário multipart para Upload de Arquivos
            $postFields = [
                'number' => $number,
                'medias' => new CURLFile($media_file)
            ];
            
            // Se também houver mensagem de texto, adicionar ao request
            if (!empty($message)) {
                $postFields['message'] = $message;
            }
            
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
            
            // Log para depuração (sem mostrar conteúdo binário)
            error_log('Enviando form-data com mídia para ' . $number);
        } else {
            // Apenas texto - usar JSON
            error_log('MODO: Enviando mensagem de texto simples via WhatsApp API');
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'number' => $number,
                    'body' => $message
                ])
            ]);
            
            // Log para depuração
            error_log('Payload JSON: ' . json_encode(['number' => $number, 'body' => $message]));
        }
        
        // Executar requisição
        error_log('Executando requisição cURL...');
        $response = curl_exec($curl);
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        // Verificar erros de cURL
        if ($response === false) {
            $curl_error = curl_error($curl);
            error_log('Erro cURL: ' . $curl_error);
            curl_close($curl);
            return [
                'success' => false,
                'error' => 'Erro na comunicação com a API: ' . $curl_error
            ];
        }
        
        curl_close($curl);
        
        // Log do resultado
        error_log('Resposta da API WhatsApp (HTTP ' . $status_code . '): ' . $response);
        
        // Decodificar resposta
        $response_data = json_decode($response, true) ?: [];
        error_log('Dados da resposta (decodificado): ' . json_encode($response_data));
        
        // Verificar sucesso
        $success = $status_code >= 200 && $status_code < 300;
        
        $result = [
            'success' => $success,
            'data' => $response_data,
            'lead_id' => null,
            'message_id' => $response_data['id'] ?? null
        ];
        
        error_log('Resultado final: ' . json_encode($result));
        error_log('============ FIM DA FUNÇÃO GLOBAL sendWhatsAppMessage =============');
        
        return $result;
    } catch (Exception $e) {
        error_log('EXCEÇÃO CAPTURADA em sendWhatsAppMessage: ' . $e->getMessage());
        error_log('Trace: ' . $e->getTraceAsString());
        return [
            'success' => false,
            'error' => 'Erro interno: ' . $e->getMessage()
        ];
    }
}

/**
 * Enviar mensagem WhatsApp com tratamento avançado para mídia + texto
 * 
 * @param string $number Número de telefone do destinatário
 * @param string $message Mensagem a ser enviada
 * @param array $media_data Dados da mídia (caminho, tipo, nome)
 * @param string $custom_token Token personalizado da API WhatsApp
 * @return array Resultado da operação
 */
function sendWhatsAppWithFallback($number, $message, $media_data = null, $custom_token = null) {
    error_log('Iniciando envio WhatsApp com fallback');
    
    // Log de depuração para diagnóstico
    error_log('Dados da mídia: ' . json_encode($media_data));
    
    // Se não tiver mídia, usa o método padrão
    if (empty($media_data)) {
        error_log('Sem mídia, usando método padrão');
        return sendWhatsAppMessage($number, $message, null, $custom_token);
    }
    
    try {
        // Verificar se o arquivo existe (quando for arquivo físico)
        if (isset($media_data['path']) && !empty($media_data['path'])) {
            $media_path = $media_data['path'];
            $file_exists = file_exists($media_path);
            
            if ($file_exists) {
                error_log('Arquivo de mídia encontrado: ' . $media_path);
            } else {
                error_log('AVISO: Arquivo não encontrado no caminho informado: ' . $media_path);
                // Tenta um caminho alternativo baseado na raiz do site
                $alt_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $media_path;
                if (file_exists($alt_path)) {
                    error_log('Arquivo encontrado em caminho alternativo: ' . $alt_path);
                    $media_path = $alt_path;
                    $file_exists = true;
                } else {
                    error_log('Arquivo não encontrado em caminho alternativo: ' . $alt_path);
                    error_log('Tentando usar o caminho original mesmo sem o arquivo existir localmente');
                }
            }
            
            // Envio com mídia primeira
            $result_media = sendWhatsAppMessage(
                $number, 
                '', // Mensagem vazia para enviar apenas mídia
                $media_path,
                $custom_token
            );
            
            error_log('Resultado do envio de mídia: ' . json_encode($result_media));
            
            // Enviar texto em seguida
            if (!empty(trim($message))) {
                error_log('Enviando mensagem de texto após mídia');
                $result_text = sendWhatsAppMessage($number, $message, null, $custom_token);
                error_log('Resultado do envio de texto: ' . json_encode($result_text));
                
                // Considerar sucesso se pelo menos um dos dois foi enviado
                return [
                    'success' => $result_media['success'] || $result_text['success'],
                    'media_sent' => $result_media['success'],
                    'text_sent' => $result_text['success'],
                    'message_id' => $result_media['message_id'] ?? $result_text['message_id'] ?? null
                ];
            }
            
            // Se não tinha texto, retorna resultado da mídia
            return $result_media;
        } else {
            error_log('Arquivo de mídia não encontrado ou inválido. Enviando apenas o texto.');
            return sendWhatsAppMessage($number, $message, null, $custom_token);
        }
    } catch (Exception $e) {
        error_log('Erro no envio com fallback: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Erro no processamento: ' . $e->getMessage()
        ];
    }
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
 * Obter relatório de performance dos vendedores
 * Alias para getSellersPerformance para compatibilidade
 */
function getSellerPerformanceReport($date_from, $date_to) {
    return getSellersPerformance($date_from, $date_to);
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
 * Obter relatório de planos mais populares
 * Alias para getPopularPlans para compatibilidade
 */
function getPopularPlansReport($date_from, $date_to) {
    return getPopularPlans($date_from, $date_to);
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
    // Clear any existing output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
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
 * Marcar tarefa como concluída
 * 
 * @param int $task_id ID da tarefa
 * @return bool Resultado da operação
 */
function completeTask($task_id) {
    $conn = getConnection();
    
    try {
        $stmt = $conn->prepare("UPDATE follow_ups SET 
            status = 'completed', 
            completed_at = NOW(), 
            notified = 0
            WHERE id = :id AND type = 'task'");
            
        $stmt->execute(['id' => $task_id]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Erro ao marcar tarefa como concluída: " . $e->getMessage());
        return false;
    }
}

/**
 * Formatar número de telefone
 * Remove caracteres não numéricos e garante o formato correto para envio de mensagens
 * 
 * @param string $phone Número de telefone para formatar
 * @return string Telefone formatado no padrão E.164 (ex: 5511999999999)
 */
function formatPhone($phone) {
    if (empty($phone)) {
        return '';
    }
    
    // Remover todos os caracteres não numéricos
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Se o número estiver vazio após a limpeza
    if (empty($phone)) {
        return '';
    }
    
    // Identificar o formato do número baseado no comprimento
    $length = strlen($phone);
    
    // Já está no formato E.164 completo (com código do país)
    if ($length >= 12 && $length <= 13) {
        // Verificar se começa com código do Brasil
        if (substr($phone, 0, 2) === '55') {
            return $phone;
        }
    }
    
    // Número sem código do país
    if ($length >= 10 && $length <= 11) {
        // Adicionar código do Brasil
        return '55' . $phone;
    }
    
    // Telefone com DDD mas sem o 9 (formato antigo)
    if ($length === 8 || $length === 9) {
        // Assumir DDD padrão (configuração do sistema)
        $default_ddd = getSetting('default_ddd', '11');
        
        // Se for um número de 8 dígitos, adicionar o 9 na frente
        if ($length === 8) {
            return '55' . $default_ddd . '9' . $phone;
        }
        
        // Se já tiver 9 dígitos, apenas adicionar DDD e código do país
        return '55' . $default_ddd . $phone;
    }
    
    // Formato desconhecido, retornar como está
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
                    'category' => 'simulation',
                    'content' => "Olá {{nome}}, recebi sua simulação para {{tipo_veiculo}} no valor de R$ {{valor_credito}}. Vou te ajudar com mais informações. \n\nAqui está o resumo:\n- Prazo: {{prazo}} meses\n- Primeira parcela: R$ {{valor_primeira}}\n- Demais parcelas: R$ {{valor_demais}}\n\nAguarde que logo entrarei em contato. \n\nAtenciosamente,\n{{nome_consultor}}",
                    'active' => 1
                ]
            ],
            'welcome' => [
                [
                    'id' => 0,
                    'name' => 'Template padrão de boas-vindas',
                    'category' => 'simulation',
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
    
    // Filtrar por tipo/categoria se especificado
    if ($type) {
        $sql .= " AND category = :category";
        $params['category'] = $type;
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
 * Atualizar um usuário existente
 * 
 * @param int $id ID do usuário a ser atualizado
 * @param array $data Array associativo com os dados para atualização
 * @return array Resultado da operação
 */
function updateUser($id, $data) {
    $conn = getConnection();
    
    // Verificar se o usuário existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    
    if ($stmt->rowCount() === 0) {
        return [
            'success' => false,
            'error' => 'Usuário não encontrado.'
        ];
    }
    
    // Construir SQL de atualização
    $fields = [];
    $params = ['id' => $id];
    
    foreach ($data as $key => $value) {
        // Tratamento especial para senha
        if ($key === 'password' && !empty($value)) {
            $params[$key] = password_hash($value, PASSWORD_DEFAULT);
        } else {
            $params[$key] = $value;
        }
        
        $fields[] = "{$key} = :{$key}";
    }
    
    // Adicionar data de atualização
    $fields[] = "updated_at = NOW()";
    
    // Executar atualização
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        return [
            'success' => true
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Erro ao atualizar usuário.'
        ];
    }
}

/**
 * Gerar nome de arquivo seguro para uploads
 * 
 * @param string $original_filename Nome original do arquivo
 * @param string $prefix Prefixo opcional para o arquivo
 * @return string Nome de arquivo seguro
 */
function generateSecureFilename($original_filename, $prefix = '') {
    // Obter extensão do arquivo original (em minúsculas)
    $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    
    // Limitar a extensão a caracteres seguros
    $extension = preg_replace('/[^a-z0-9]/', '', $extension);
    
    // Garantir que a extensão seja válida
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'svg'];
    if (!in_array($extension, $allowed_extensions)) {
        $extension = 'bin'; // Extensão genérica para tipos não permitidos
    }
    
    // Gerar parte aleatória do nome do arquivo
    $random_string = bin2hex(random_bytes(8)); // 16 caracteres hexadecimais
    
    // Gerar timestamp
    $timestamp = time();
    
    // Construir nome do arquivo
    // Formato: prefixo_timestamp_randombytes.extensão
    $filename = '';
    
    if (!empty($prefix)) {
        // Garantir que o prefixo tenha apenas caracteres seguros
        $prefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix);
        if (!empty($prefix)) {
            $filename .= $prefix . '_';
        }
    }
    
    $filename .= $timestamp . '_' . $random_string . '.' . $extension;
    
    return $filename;
}

/**
 * Salvar token de autenticação PWA
 * 
 * @param int $user_id ID do usuário
 * @param string $token Token de autenticação
 * @param int $expires_in Tempo de expiração em segundos (padrão: 30 dias)
 * @return bool Sucesso ou falha
 */
function savePwaToken($user_id, $token, $expires_in = 2592000) {
    $conn = getConnection();
    
    try {
        // Verificar se a tabela pwa_tokens existe
        $stmt = $conn->prepare("SHOW TABLES LIKE 'pwa_tokens'");
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            // Criar a tabela se não existir
            $sql = "CREATE TABLE IF NOT EXISTS `pwa_tokens` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `token` varchar(255) NOT NULL,
                `expires_at` datetime NOT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `token` (`token`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $conn->exec($sql);
        }
        
        // Remover tokens antigos do mesmo usuário
        $stmt = $conn->prepare("DELETE FROM pwa_tokens WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        
        // Inserir novo token
        $stmt = $conn->prepare("INSERT INTO pwa_tokens (user_id, token, expires_at) 
                               VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL :expires_in SECOND))");
        
        return $stmt->execute([
            'user_id' => $user_id,
            'token' => $token,
            'expires_in' => $expires_in
        ]);
    } catch (PDOException $e) {
        error_log("Erro ao salvar token PWA: " . $e->getMessage());
        return false;
    }
}

/**
 * Verificar token de autenticação PWA
 * 
 * @param int $user_id ID do usuário
 * @param string $token Token de autenticação
 * @return bool Token válido ou não
 */
function verifyPwaToken($user_id, $token) {
    $conn = getConnection();
    
    try {
        // Verificar se a tabela existe
        $stmt = $conn->prepare("SHOW TABLES LIKE 'pwa_tokens'");
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            return false;
        }
        
        // Verificar se o token existe e é válido
        $stmt = $conn->prepare("SELECT 1 FROM pwa_tokens 
                               WHERE user_id = :user_id 
                               AND token = :token 
                               AND expires_at > NOW()");
        
        $stmt->execute([
            'user_id' => $user_id,
            'token' => $token
        ]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Erro ao verificar token PWA: " . $e->getMessage());
        return false;
    }
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
    
    try {
        // Verificar se a tabela existe
        $stmt = $conn->prepare("SHOW TABLES LIKE 'sent_messages'");
        $stmt->execute();
        $table_exists = $stmt->rowCount() > 0;
        
        if (!$table_exists) {
            // Criar a tabela se não existir
            error_log("Tabela sent_messages não existe. Criando...");
            $sql = "CREATE TABLE IF NOT EXISTS `sent_messages` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `lead_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `template_id` int(11) DEFAULT NULL,
                `message` text NOT NULL,
                `media_url` varchar(255) DEFAULT NULL,
                `media_type` varchar(50) DEFAULT NULL,
                `message_id` varchar(100) DEFAULT NULL,
                `status` varchar(20) NOT NULL DEFAULT 'sent',
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `lead_id` (`lead_id`),
                KEY `user_id` (`user_id`),
                KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $conn->exec($sql);
            error_log("Tabela sent_messages criada com sucesso");
        }
        
        // Verificar se a coluna media_type existe
        $stmt = $conn->prepare("SHOW COLUMNS FROM sent_messages LIKE 'media_type'");
        $stmt->execute();
        $column_exists = $stmt->rowCount() > 0;
        
        if (!$column_exists) {
            // Adicionar a coluna media_type se não existir
            error_log("Coluna media_type não existe. Adicionando...");
            $conn->exec("ALTER TABLE sent_messages ADD COLUMN `media_type` varchar(50) DEFAULT NULL AFTER `media_url`");
            error_log("Coluna media_type adicionada com sucesso");
        }
        
        // Extrair media_type da URL da mídia, se disponível
        $media_type = null;
        if (!empty($media_url)) {
            $extension = strtolower(pathinfo($media_url, PATHINFO_EXTENSION));
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $media_type = 'image/' . $extension;
            } elseif ($extension === 'pdf') {
                $media_type = 'application/pdf';
            } elseif (in_array($extension, ['mp4', 'mov'])) {
                $media_type = 'video/' . $extension;
            }
        }
        
        // Inserir o registro da mensagem
        error_log("Inserindo mensagem no banco de dados...");
        $sql = "INSERT INTO sent_messages (lead_id, user_id, template_id, message, media_url, media_type, message_id, status, created_at)
                VALUES (:lead_id, :user_id, :template_id, :message, :media_url, :media_type, :message_id, :status, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'lead_id' => $lead_id,
            'user_id' => $user_id,
            'template_id' => $template_id,
            'message' => $message,
            'media_url' => $media_url,
            'media_type' => $media_type,
            'message_id' => $message_id,
            'status' => $status
        ]);
        
        $insert_id = $conn->lastInsertId();
        error_log("Mensagem registrada com sucesso. ID: " . $insert_id);
        
        // Adicionar mensagem à tabela lead_messages também (se existir)
        try {
            // Verificar se a tabela lead_messages existe
            $stmt = $conn->prepare("SHOW TABLES LIKE 'lead_messages'");
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                error_log("Tabela lead_messages existe. Adicionando registro lá também...");
                
                // Inserir na tabela lead_messages também
                $sql = "INSERT INTO lead_messages 
                        (lead_id, user_id, direction, channel, content, status, created_at) 
                    VALUES 
                        (:lead_id, :user_id, 'outgoing', 'whatsapp', :content, :status, NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'lead_id' => $lead_id,
                    'user_id' => $user_id,
                    'content' => $message,
                    'status' => ($status === 'sent') ? 'sent' : 'failed'
                ]);
                
                error_log("Mensagem também adicionada à tabela lead_messages");
            }
        } catch (PDOException $e) {
            // Apenas log, não interromper o processo
            error_log("Aviso: Erro ao adicionar à tabela lead_messages: " . $e->getMessage());
        }
        
        return $insert_id;
    } catch (PDOException $e) {
        // Log detalhado para diagnóstico
        error_log("ERRO AO REGISTRAR MENSAGEM: " . $e->getMessage());
        error_log("Detalhes: lead_id={$lead_id}, user_id={$user_id}, template_id=" . var_export($template_id, true) . 
                  ", media_url=" . var_export($media_url, true) . ", media_type=" . var_export($media_type, true) . 
                  ", message_id=" . var_export($message_id, true) . ", status={$status}");
        error_log("Mensagem (primeiros 100 caracteres): " . substr($message, 0, 100));
        error_log("Query: INSERT INTO sent_messages (lead_id, user_id, template_id, message, media_url, media_type, message_id, status, created_at) VALUES (...)");
        
        // Verificar se há comprimento máximo excedido
        if (strlen($message) > 50000) {
            error_log("AVISO: A mensagem é muito longa (" . strlen($message) . " caracteres). Isso pode causar problemas com alguns bancos de dados.");
        }
        
        try {
            // Tentar inserir uma versão truncada da mensagem se for muito longa
            if (strlen($message) > 50000) {
                error_log("Tentando inserir com mensagem truncada...");
                $truncated_message = substr($message, 0, 50000);
                
                $sql = "INSERT INTO sent_messages (lead_id, user_id, template_id, message, media_url, media_type, message_id, status, created_at)
                        VALUES (:lead_id, :user_id, :template_id, :message, :media_url, :media_type, :message_id, :status, NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'lead_id' => $lead_id,
                    'user_id' => $user_id,
                    'template_id' => $template_id,
                    'message' => $truncated_message . "\n[Mensagem truncada...]",
                    'media_url' => $media_url,
                    'media_type' => $media_type,
                    'message_id' => $message_id,
                    'status' => $status
                ]);
                
                $insert_id = $conn->lastInsertId();
                error_log("Sucesso ao inserir mensagem truncada! ID: " . $insert_id);
                return $insert_id;
            }
        } catch (PDOException $e2) {
            error_log("Também falhou com mensagem truncada: " . $e2->getMessage());
        }
        
        return false;
    }

    // Adicione estas funções no arquivo functions.php

/**
 * Função para enviar mensagem via WhatsApp
 */
function sendWhatsAppMessage($phone, $message, $media = null, $token = null) {
    try {
        // Log para debug
        error_log("Iniciando envio de mensagem WhatsApp");
        error_log("Telefone: " . $phone);
        error_log("Mensagem: " . $message);
        error_log("Token: " . ($token ? 'Presente' : 'Ausente'));

        // Validar parâmetros
        if (empty($phone) || empty($message)) {
            throw new Exception('Telefone e mensagem são obrigatórios');
        }

        // Formatar número de telefone (remover caracteres não numéricos)
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Se não foi fornecido token, tentar obter o token global
        if (empty($token)) {
            $token = getSetting('whatsapp_api_token');
        }

        // Verificar se existe token configurado
        if (empty($token)) {
            return [
                'success' => false,
                'error' => 'Token do WhatsApp não configurado'
            ];
        }

        // URL da API (deve estar definida em config.php)
        $api_url = WHATSAPP_API_URL;

        // Preparar dados para envio
        $data = [
            'phone' => $phone,
            'message' => $message
        ];

        // Adicionar mídia se existir
        if ($media && is_array($media)) {
            $data['media'] = $media;
        }

        // Configurar cURL
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);

        // Executar requisição
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Log da resposta
        error_log("Resposta da API WhatsApp: " . $response);
        error_log("HTTP Code: " . $http_code);

        // Verificar erros do cURL
        if (curl_errno($ch)) {
            throw new Exception('Erro cURL: ' . curl_error($ch));
        }

        curl_close($ch);

        // Decodificar resposta
        $result = json_decode($response, true);

        // Verificar se a resposta é válida
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Resposta inválida da API');
        }

        return [
            'success' => $http_code === 200 && isset($result['success']) && $result['success'],
            'data' => $result
        ];

    } catch (Exception $e) {
        error_log("Erro ao enviar mensagem WhatsApp: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Função para registrar mensagem enviada
 */
function registerSentMessage($lead_id, $user_id, $message, $template_id = null, $media_url = null, $media_type = null, $status = 'sent', $external_id = null) {
    try {
        $conn = getConnection();

        // Verificar se a tabela existe
        $conn->exec("CREATE TABLE IF NOT EXISTS lead_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lead_id INT NOT NULL,
            user_id INT NOT NULL,
            template_id INT NULL,
            message TEXT NOT NULL,
            media_url VARCHAR(255) NULL,
            media_type VARCHAR(50) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'sent',
            external_id VARCHAR(100) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY lead_id (lead_id),
            KEY user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Preparar e executar a inserção
        $stmt = $conn->prepare("
            INSERT INTO lead_messages 
            (lead_id, user_id, template_id, message, media_url, media_type, status, external_id) 
            VALUES 
            (:lead_id, :user_id, :template_id, :message, :media_url, :media_type, :status, :external_id)
        ");

        $result = $stmt->execute([
            'lead_id' => $lead_id,
            'user_id' => $user_id,
            'template_id' => $template_id,
            'message' => $message,
            'media_url' => $media_url,
            'media_type' => $media_type,
            'status' => $status,
            'external_id' => $external_id
        ]);

        if ($result) {
            return $conn->lastInsertId();
        }

        return false;

    } catch (PDOException $e) {
        error_log("Erro ao registrar mensagem no banco: " . $e->getMessage());
        throw new Exception("Erro ao registrar mensagem no banco de dados");
    }
}
}

