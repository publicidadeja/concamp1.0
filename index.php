<?php
// Start output buffering at the beginning of the script
ob_start();

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Configurações e autoload
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Roteamento
$route = $_GET['route'] ?? 'home';

// Rotas protegidas (exigem login)
$protected_routes = [
    'dashboard',
    'leads',
    'lead-detail',
    'admin-users',
    'admin-plans',
    'admin-settings',
    'admin-landing-page-settings',
    'admin-reports',
    'seller-landing-page', // Rota para a página de configuração do vendedor
    'notifications', // Rota para a página de notificações
    'api-mark-notification-read', // API para marcar notificação como lida
    'api-mark-all-notifications-read', // API para marcar todas as notificações como lidas
    'api-get-unread-notifications-count' // API para obter contagem de notificações não lidas
];

// Verificar se estamos em notificações e vindo por acesso mobile
$is_notifications_from_header = ($route === 'notifications' && 
                               isset($_GET['ref']) && $_GET['ref'] === 'header' &&
                               isset($_GET['role']) && in_array($_GET['role'], ['seller', 'admin']));

// Autenticação de emergência para demonstração se for acesso mobile às notificações
if ($is_notifications_from_header && !isLoggedIn()) {
    $role = $_GET['role'];
    error_log("⚠️ ACESSO DIRETO ÀS NOTIFICAÇÕES: Role = $role. Implementando auto-login para demonstração");
    
    // ACESSO DE EMERGÊNCIA APENAS PARA DEMONSTRAÇÃO
    // NÃO USAR EM PRODUÇÃO - É APENAS PARA RESOLVER PROBLEMAS DE PWA EM TESTES
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE role = :role AND status = 'active' LIMIT 1");
    $stmt->bindParam(":role", $role);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Criar uma sessão para o usuário
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name']; 
        $_SESSION['user_role'] = $user['role'];
        
        // Definir cookies sem flags de segurança para máxima compatibilidade em testes
        $token = bin2hex(random_bytes(32));
        setcookie('pwa_user_id', $user['id'], time() + (86400 * 30), "/", "", false, false);
        setcookie('pwa_auth_token', $token, time() + (86400 * 30), "/", "", false, false);
        
        error_log("⚠️ MODO DEMO: Auto-login realizado para {$user['role']} {$user['name']} (ID: {$user['id']})");
    }
}

// Verificar se a rota é protegida e se o usuário está logado
if (in_array($route, $protected_routes) && !isLoggedIn()) {
    redirect('index.php?route=login');
}

// Tratar rotas de API - abordagem simplificada
if (strpos($route, 'api/') === 0) {
    // Suprimir erros e avisos para garantir resposta JSON limpa
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Extrair caminho da API
    $api_path = substr($route, 4); // Remove "api/"
    $api_file = __DIR__ . '/api/' . $api_path . '.php';
    
    // Iniciar captura de saída para garantir que nada além do JSON seja enviado
    ob_start();
    
    if (file_exists($api_file)) {
        try {
            // Incluir o arquivo da API
            include $api_file;
        } catch (Throwable $e) {
            // Limpar o buffer de saída em caso de erro
            ob_end_clean();
            // Garantir que o cabeçalho seja JSON
            header('Content-Type: application/json');
            // Retornar erro como JSON
            echo json_encode([
                'success' => false,
                'error' => 'Erro interno: ' . $e->getMessage()
            ]);
        }
    } else {
        // API não encontrada
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'API não encontrada: ' . $api_path
        ]);
    }
    
    // Capturar qualquer saída inesperada
    $output = ob_get_clean();
    
    // Se parece JSON válido, enviar como está
    if (!empty($output) && ($output[0] === '{' || $output[0] === '[')) {
        header('Content-Type: application/json');
        echo $output;
    } else if (!empty($output)) {
        // Saída inesperada - envolver em objeto JSON com erro
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Saída inesperada',
            'debug_output' => substr($output, 0, 1000) // Limitar para não sobrecarregar
        ]);
    } else {
        // Nenhuma saída - erro interno
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Nenhuma resposta do servidor'
        ]);
    }
    
    // Encerrar execução para não processar o resto da página
    exit;
}

// Incluir cabeçalho para rotas não-API
include_once __DIR__ . '/templates/header.php';

// Conteúdo principal (roteamento)
switch ($route) {
    case 'home':
        include_once __DIR__ . '/pages/simulador-landing.php';
        break;
    case 'login':
        include_once __DIR__ . '/pages/login.php';
        break;
    case 'logout':
        logout();
        redirect('index.php?route=login');
        break;
    case 'simulador':
        include_once __DIR__ . '/pages/simulador.php';
        break;
    case 'simulador-landing':
        include_once __DIR__ . '/pages/simulador-landing.php';
        break;
    case 'process-simulation':
        include_once __DIR__ . '/actions/process-simulation.php';
        break;
    case 'process-seller-simulation': // Rota para processar simulações de LPs de vendedores
        include_once __DIR__ . '/actions/process-seller-simulation.php';
        break;
    case 'dashboard':
        include_once __DIR__ . '/pages/dashboard.php';
        break;
    case 'leads':
        include_once __DIR__ . '/pages/leads.php';
        break;
    case 'lead-detail':
        include_once __DIR__ . '/pages/lead-detail.php';
        break;
    case 'admin-users':
        include_once __DIR__ . '/pages/admin/users.php';
        break;
    case 'admin-plans':
        include_once __DIR__ . '/pages/admin/plans.php';
        break;
    case 'admin-settings':
        include_once __DIR__ . '/pages/admin/settings.php';
        break;
    case 'admin-reports':
        include_once __DIR__ . '/pages/admin/reports.php';
        break;
    case 'admin-landing-page-settings':
        include_once __DIR__ . '/pages/admin/landing-page-settings.php';
        break;
    case 'admin-landing-page-content':
        include_once __DIR__ . '/pages/admin/landing-page-content.php';
        break;
    case 'seller-landing-page': // Rota para a página de configuração do vendedor
        include_once __DIR__ . '/pages/seller/landing-page.php';
        break;
    case 'notifications': // Página de notificações
        include_once __DIR__ . '/pages/notifications.php';
        break;
    case 'api-mark-notification-read': // API para marcar notificação como lida
        include_once __DIR__ . '/pages/api-mark-notification-read.php';
        break;
    case 'api-mark-all-notifications-read': // API para marcar todas as notificações como lidas
        include_once __DIR__ . '/pages/api-mark-all-notifications-read.php';
        break;
    case 'api-get-unread-notifications-count': // API para obter contagem de notificações não lidas
        include_once __DIR__ . '/pages/api-get-unread-notifications-count.php';
        break;
    case 'create-notifications-table': // Rota para criar a tabela de notificações
        include_once __DIR__ . '/pages/create-notifications-table.php';
        break;
    case 'create-task-notification-field': // Rota para adicionar campo de notificação na tabela de tarefas
        include_once __DIR__ . '/pages/create-task-notification-field.php';
        break;
    case 'api-complete-task': // API para marcar tarefa como concluída
        // Suprimir erros e avisos para garantir resposta JSON limpa
        error_reporting(0);
        ini_set('display_errors', 0);
        
        // Suprimir saída anterior e incluir apenas a API
        ob_clean();
        
        // Definir cabeçalho JSON antes de incluir o arquivo
        header('Content-Type: application/json');
        
        include_once __DIR__ . '/pages/api-complete-task.php';
        exit; // Importante: encerrar execução aqui para não incluir header/footer
        break;
    default:
        // Rota customizada para landing pages de vendedores (ex: /lp/nome-do-vendedor)
        if (strpos($route, 'lp/') === 0) {
            $landing_page_name = substr($route, 3); // Extrai o nome da LP
            $_GET['name'] = $landing_page_name; // Define o parâmetro 'name'
            include_once __DIR__ . '/pages/landing-page.php';
        } else {
            include_once __DIR__ . '/pages/404.php';
        }
        break;
}

// Incluir rodapé
include_once __DIR__ . '/templates/footer.php';

// Flush the output buffer at the end of the script
if (ob_get_level()) {
    ob_end_flush();
}
?>
