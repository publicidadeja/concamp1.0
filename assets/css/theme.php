<?php
/**
 * CSS dinâmico para personalização do tema
 */

// Definir tipo de conteúdo como CSS
header('Content-Type: text/css');

// Obter versão do tema para controle de cache
$theme_version = getSetting('theme_version') ?: time();

// Definir cabeçalhos anti-cache para garantir que as mudanças sejam aplicadas imediatamente
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('ETag: "theme-' . $theme_version . '"');

// Iniciar sessão e incluir arquivos necessários
@session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Resetar cache de banco de dados para obter sempre os valores mais recentes
if (function_exists('clearSettingsCache')) {
    clearSettingsCache();
} else {
    // Função auxiliar para limpar cache se não existir globalmente
    function clearSettingsCache() {
        if (isset($_SESSION['settings_cache'])) {
            unset($_SESSION['settings_cache']);
        }
    }
    clearSettingsCache();
}

// Definir cores diretamente conforme solicitado pelo cliente
$primary_color = '#00053c'; // Cor principal azul escuro
$secondary_color = '#6c757d'; // Mantendo secundária padrão
$header_color = '#ffffff'; // Cabeçalho branco
$dark_mode = false; // Desativar modo escuro

// Debug para verificar os valores obtidos
error_log("theme.php - Configurações obtidas: " . json_encode([
    'primary_color' => $primary_color,
    'secondary_color' => $secondary_color,
    'header_color' => $header_color,
    'dark_mode' => $dark_mode,
    'theme_version' => $theme_version
]));

// Para suporte a preview de temas via JavaScript
if (isset($_GET['preview']) && $_GET['preview'] === '1') {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    
    // Verificar se temos parâmetros de tema na URL
    if (isset($_GET['primary']) && preg_match('/#[0-9a-fA-F]{6}/', $_GET['primary'])) {
        $primary_color = $_GET['primary'];
    }
    
    if (isset($_GET['secondary']) && preg_match('/#[0-9a-fA-F]{6}/', $_GET['secondary'])) {
        $secondary_color = $_GET['secondary'];
    }
    
    if (isset($_GET['header']) && preg_match('/#[0-9a-fA-F]{6}/', $_GET['header'])) {
        $header_color = $_GET['header'];
    }
    
    if (isset($_GET['dark']) && in_array($_GET['dark'], ['0', '1'])) {
        $dark_mode = $_GET['dark'] === '1';
    }
}

// Definir cores complementares e variantes
$primary_dark = adjustBrightness($primary_color, -15);
$primary_light = adjustBrightness($primary_color, 15);
$secondary_dark = adjustBrightness($secondary_color, -15);
$secondary_light = adjustBrightness($secondary_color, 15);

// Função auxiliar para ajustar o brilho de uma cor hexadecimal
function adjustBrightness($hex, $steps) {
    // Extrair os componentes R, G e B da cor hexadecimal
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // Ajustar o brilho
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));

    // Converter de volta para hexadecimal
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

// Calcular cores de texto adequadas com base no contraste
function getTextColor($bgColor) {
    // Extrair os componentes R, G e B da cor hexadecimal
    $hex = ltrim($bgColor, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Calcular luminosidade
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    
    // Retornar cor do texto baseada na luminosidade
    return $luminance > 0.5 ? '#212529' : '#ffffff';
}

// Obter cores de texto com base no contraste
$header_text_color = getTextColor($header_color);
$primary_text_color = getTextColor($primary_color);
$secondary_text_color = getTextColor($secondary_color);

// Gerar CSS
?>
:root {
    /* Definindo cores explicitamente */
    --primary: #00053c;
    --primary-dark: #00042d;
    --primary-light: #000856;
    --secondary: #4e5055;
    --secondary-dark: #3c3e42;
    --secondary-light: #62646b;
    --header-bg: #ffffff;
    --header-text: #212529;
    --primary-text: #ffffff;
    --secondary-text: #ffffff;
}

/* Estilos de cabeçalho */
.navbar {
    background-color: var(--header-bg) !important;
    color: var(--header-text) !important;
}

.navbar .navbar-brand, 
.navbar .nav-link,
.navbar .navbar-text {
    color: var(--header-text) !important;
}

/* Corrigir contraste nos botões de navegação */
.navbar .navbar-toggler {
    border-color: var(--header-text);
    color: var(--header-text);
}

.navbar .navbar-toggler-icon {
    <?php 
    // Convertendo cor do texto do cabeçalho para rgba para o ícone do toggler
    $hex = ltrim($header_text_color, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $toggler_color = "rgba($r, $g, $b, 0.75)";
    
    // Criar versão escapada para URL do SVG
    $svg_color = str_replace('#', '%23', $header_text_color);
    if (strpos($svg_color, 'rgba') === 0 || strpos($svg_color, 'rgb') === 0) {
        // Já é um formato rgb(a), não precisa escapar
        $svg_color = $toggler_color;
    }
    ?>
    background-image: url("data:image/svg+xml;charset=utf8,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='<?php echo $toggler_color; ?>' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E") !important;
    color: var(--header-text) !important;
}

.navbar .navbar-toggler {
    border-color: var(--header-text) !important;
    color: var(--header-text) !important;
}

/* Estilos de botões - com alta especificidade para evitar sobreposição */
html body .btn-primary,
.btn-primary {
    background-color: var(--primary) !important;
    border-color: var(--primary) !important;
    color: var(--primary-text) !important;
}

html body .btn-primary:hover,
html body .btn-primary:focus,
html body .btn-primary:active,
.btn-primary:hover,
.btn-primary:focus,
.btn-primary:active {
    background-color: var(--primary-dark) !important;
    border-color: var(--primary-dark) !important;
}

html body .btn-secondary,
.btn-secondary {
    background-color: var(--secondary) !important;
    border-color: var(--secondary) !important;
    color: var(--secondary-text) !important;
}

html body .btn-secondary:hover,
html body .btn-secondary:focus,
html body .btn-secondary:active,
.btn-secondary:hover,
.btn-secondary:focus,
.btn-secondary:active {
    background-color: var(--secondary-dark) !important;
    border-color: var(--secondary-dark) !important;
}

html body .btn-outline-primary,
.btn-outline-primary {
    color: var(--primary) !important;
    border-color: var(--primary) !important;
}

html body .btn-outline-primary:hover,
html body .btn-outline-primary:focus,
html body .btn-outline-primary:active,
.btn-outline-primary:hover,
.btn-outline-primary:focus,
.btn-outline-primary:active {
    background-color: var(--primary) !important;
    color: var(--primary-text) !important;
}

/* Links e outros elementos - maior especificidade */
html body a,
body a {
    color: var(--primary) !important;
}

html body a:hover,
body a:hover {
    color: var(--primary-dark) !important;
}

.page-item.active .page-link {
    background-color: var(--primary) !important;
    border-color: var(--primary) !important;
    color: var(--primary-text) !important;
}

.bg-primary {
    background-color: var(--primary) !important;
}

.bg-secondary {
    background-color: var(--secondary) !important;
}

.text-primary {
    color: var(--primary) !important;
}

.text-secondary {
    color: var(--secondary) !important;
}

.border-primary {
    border-color: var(--primary) !important;
}

/* Personalização do sidebar */
.sidebar {
    background-color: var(--header-bg);
}

.sidebar .nav-link {
    color: var(--header-text);
}

.sidebar .nav-link:hover {
    background-color: rgba(0, 0, 0, 0.1);
}

.sidebar .nav-link.active {
    background-color: var(--primary);
    color: var(--primary-text);
}

<?php if ($dark_mode): ?>
/* Modo escuro */
body {
    background-color: #121212;
    color: #e0e0e0;
}

.card,
.modal-content,
.dropdown-menu,
.list-group-item {
    background-color: #1e1e1e;
    color: #e0e0e0;
    border-color: #333;
}

.form-control,
.form-select {
    background-color: #2c2c2c;
    border-color: #444;
    color: #e0e0e0;
}

.form-control:focus,
.form-select:focus {
    background-color: #2c2c2c;
    color: #e0e0e0;
}

.table {
    color: #e0e0e0;
}

.table-hover tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.05);
}

.dropdown-item {
    color: #e0e0e0;
}

.dropdown-item:hover {
    background-color: #333;
    color: #fff;
}

.modal-header,
.modal-footer {
    border-color: #444;
}

.text-muted {
    color: #aaa !important;
}

.bg-light {
    background-color: #2a2a2a !important;
}

.card-header {
    border-bottom-color: #444;
}

.border-bottom {
    border-bottom-color: #444 !important;
}

.alert-info {
    background-color: #0d3a58;
    border-color: #0c5460;
    color: #d1ecf1;
}

.alert-success {
    background-color: #0d462e;
    border-color: #155724;
    color: #d4edda;
}

.alert-danger {
    background-color: #5a0815;
    border-color: #721c24;
    color: #f8d7da;
}

.alert-warning {
    background-color: #524700;
    border-color: #856404;
    color: #fff3cd;
}
<?php endif; ?>