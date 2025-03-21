<?php
/**
 * Script para gerar o arquivo manifest.json dinamicamente
 * com as configurações definidas pelo administrador
 */

// Inclusão dos arquivos necessários
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

// Verificar se o PWA está ativado
$pwa_enabled = getSetting('pwa_enabled') === '1';

// Se o PWA não estiver ativado, cria um manifest vazio
if (!$pwa_enabled) {
    $manifest = [
        'name' => 'ConCamp',
        'short_name' => 'ConCamp',
        'display' => 'browser'
    ];
    
    header('Content-Type: application/json');
    echo json_encode($manifest);
    exit;
}

// Obter configurações do PWA
$name = getSetting('pwa_name') ?: 'ConCamp - Sistema de Gestão de Contratos Premiados';
$short_name = getSetting('pwa_short_name') ?: 'ConCamp';
$description = getSetting('pwa_description') ?: 'Sistema para gerenciamento de contratos premiados de carros e motos';
$theme_color = getSetting('pwa_theme_color') ?: '#0d6efd';
$background_color = getSetting('pwa_background_color') ?: '#ffffff';

// Verificar se há um ícone PWA personalizado
$pwa_icon_url = getSetting('pwa_icon_url');
$icon_base_path = 'assets/img/icons/';

// Tamanhos de ícones
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$icons = [];

// Determinar extensão do arquivo de ícone
$icon_extension = 'png';
if ($pwa_icon_url) {
    $pathinfo = pathinfo($pwa_icon_url);
    $icon_extension = $pathinfo['extension'];
    $icon_base_name = $pathinfo['filename'];
    
    // Adicionar os ícones ao manifest
    foreach ($sizes as $size) {
        // Para SVG, usar o mesmo arquivo para todos os tamanhos
        if ($icon_extension === 'svg') {
            $icons[] = [
                'src' => $pwa_icon_url,
                'sizes' => "{$size}x{$size}",
                'type' => 'image/svg+xml',
                'purpose' => 'any maskable'
            ];
        } else {
            // Para PNG/JPG, usar os diferentes tamanhos gerados
            $icon_file = "icon-{$size}x{$size}.{$icon_extension}";
            $icons[] = [
                'src' => $icon_base_path . $icon_file,
                'sizes' => "{$size}x{$size}",
                'type' => "image/{$icon_extension}",
                'purpose' => 'any maskable'
            ];
        }
    }
} else {
    // Usar ícones padrão
    foreach ($sizes as $size) {
        $icons[] = [
            'src' => $icon_base_path . "icon-{$size}x{$size}.png",
            'sizes' => "{$size}x{$size}",
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ];
    }
}

// Criar o objeto manifest
$manifest = [
    'name' => $name,
    'short_name' => $short_name,
    'description' => $description,
    'start_url' => './index.php',
    'display' => 'standalone',
    'background_color' => $background_color,
    'theme_color' => $theme_color,
    'orientation' => 'portrait-primary',
    'icons' => $icons
];

// Retornar o manifest como JSON
header('Content-Type: application/json');
echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);