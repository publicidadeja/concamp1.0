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

// Incluir cabeçalho
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
