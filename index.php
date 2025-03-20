<?php
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
    'admin-reports',
    'seller-landing-page' // Rota para a página de configuração do vendedor
];

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
        include_once __DIR__ . '/pages/home.php';
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
    case 'seller-landing-page': // Rota para a página de configuração do vendedor
        include_once __DIR__ . '/pages/seller/landing-page.php';
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
?>
