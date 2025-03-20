<?php
/**
 * Cabeçalho do site
 */

// Definir título padrão se não estiver definido
$page_title = $page_title ?? 'Sistema de Gestão de Contratos Premiados';
$body_class = $body_class ?? '';

// Verificar se o usuário está logado
$is_logged_in = isLoggedIn();
$current_user = $is_logged_in ? getCurrentUser() : null;
$user_role = $current_user ? $current_user['role'] : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo url('assets/css/style.css'); ?>" rel="stylesheet">
    
    <?php if ($is_logged_in): ?>
    <link href="<?php echo url('assets/css/dashboard.css'); ?>" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Tema com cores fixas -->
    <link href="<?php echo url('assets/css/hardcoded-theme.css?v=' . time()); ?>" rel="stylesheet" id="hardcoded-theme-css">
    
    <!-- Script para garantir que o tema seja sempre atualizado -->
    <script>
    (function() {
        // Verificar se o tema está desatualizado
        const storedThemeVersion = localStorage.getItem('theme_version');
        const currentThemeVersion = '<?php echo $theme_timestamp; ?>';
        
        if (storedThemeVersion !== currentThemeVersion) {
            // Armazenar nova versão
            localStorage.setItem('theme_version', currentThemeVersion);
            
            // Se o usuário recarregou a página nos últimos 5 segundos, não recarregar novamente
            const lastReload = localStorage.getItem('last_reload');
            const now = new Date().getTime();
            
            if (!lastReload || (now - parseInt(lastReload)) > 5000) {
                localStorage.setItem('last_reload', now);
                // Forçar recarga do CSS sem recarregar a página
                const themeCss = document.getElementById('dynamic-theme-css');
                if (themeCss) {
                    const currentSrc = themeCss.getAttribute('href');
                    const baseSrc = currentSrc.split('?')[0];
                    const newSrc = baseSrc + '?v=' + currentThemeVersion;
                    themeCss.setAttribute('href', newSrc);
                }
            }
        }
    })();
    </script>
    
    <!-- IMask para máscaras de input -->
    <script src="https://unpkg.com/imask"></script>
</head>
<body class="<?php echo $body_class; ?> theme-applied">
    <?php if ($is_logged_in): ?>
    <!-- Navbar para usuários logados -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo url('index.php?route=dashboard'); ?>">
                <?php 
                // Verificar se há um logo personalizado
                $logo_url = getSetting('logo_url');
                if (!empty($logo_url)): 
                    $logo_full_url = url($logo_url);
                    error_log("Logo URL para usuários logados: " . $logo_full_url);
                ?>
                <img src="<?php echo $logo_full_url; ?>" alt="Logo" height="40" class="d-inline-block align-text-top">
                <?php else: ?>
                <i class="fas fa-car-side me-2"></i>
                <?php echo getSetting('site_name') ?: 'ConCamp'; ?>
                <?php endif; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('index.php?route=dashboard'); ?>">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('index.php?route=leads'); ?>">
                            <i class="fas fa-users me-1"></i> Leads
                        </a>
                    </li>
                    
                    <?php if ($user_role === 'seller'): ?>
                    <!-- Menu específico para vendedores -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('index.php?route=seller-landing-page'); ?>">
                            <i class="fas fa-pager me-1"></i> Minha Landing Page
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($user_role === 'admin'): ?>
                    <!-- Menu de administração -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i> Administração
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="<?php echo url('index.php?route=admin-users'); ?>">
                                    <i class="fas fa-users-cog me-1"></i> Usuários
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo url('index.php?route=admin-plans'); ?>">
                                    <i class="fas fa-file-invoice-dollar me-1"></i> Planos
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo url('index.php?route=admin-settings'); ?>">
                                    <i class="fas fa-sliders-h me-1"></i> Configurações
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo url('index.php?route=admin-reports'); ?>">
                                    <i class="fas fa-chart-bar me-1"></i> Relatórios
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo $current_user['name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?php echo url('index.php?route=logout'); ?>">
                                    <i class="fas fa-sign-out-alt me-1"></i> Sair
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Mensagens de feedback -->
    <div class="container mt-3">
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </div>
    
    <!-- Conteúdo principal para usuários logados -->
    <div class="container mt-4">
    <?php else: ?>
    <!-- Navbar para visitantes -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="<?php echo url('index.php'); ?>">
                <?php 
                // Verificar se há um logo personalizado
                $logo_url = getSetting('logo_url');
                if (!empty($logo_url)): 
                    $logo_full_url = url($logo_url);
                    error_log("Logo URL para visitantes: " . $logo_full_url);
                ?>
                <img src="<?php echo $logo_full_url; ?>" alt="Logo" height="40" class="d-inline-block align-text-top">
                <?php else: ?>
                <i class="fas fa-car-side me-2"></i>
                <?php echo getSetting('site_name') ?: 'ConCamp'; ?>
                <?php endif; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('index.php'); ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('index.php?route=simulador'); ?>">Simulador</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('index.php?route=login'); ?>">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Mensagens de feedback para visitantes -->
    <div class="container mt-3">
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </div>
    
    <!-- Conteúdo principal para visitantes -->
    <div class="container mt-4">
    <?php endif; ?>
