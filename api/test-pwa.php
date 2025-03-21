<?php
/**
 * Script para testar a funcionalidade do PWA
 */

// Desativar a saída de erros para a resposta JSON
ini_set('display_errors', 0);
error_reporting(0);

// Inclusão dos arquivos necessários
require_once '../config/config.php';
require_once '../includes/functions.php';

// Verificar diretório de ícones
$icons_dir = '../assets/img/icons/';
$icons_exist = is_dir($icons_dir);
$icons_writable = false;
if ($icons_exist) {
    try {
        $icons_writable = is_writable($icons_dir);
    } catch (Exception $e) {
        $icons_writable = false;
    }
}

// Verificar manifesto
$manifest_file = '../manifest.json';
$manifest_exists = file_exists($manifest_file);

// Verificar .htaccess
$htaccess_file = '../.htaccess';
$htaccess_exists = file_exists($htaccess_file);
$htaccess_content = '';
if ($htaccess_exists) {
    try {
        $htaccess_content = file_get_contents($htaccess_file);
    } catch (Exception $e) {
        $htaccess_content = '';
    }
}
$htaccess_has_rewrite = strpos($htaccess_content, 'RewriteRule ^manifest') !== false;

// Verificar service worker
$sw_file = '../service-worker.js';
$sw_exists = file_exists($sw_file);

// Verificar script PWA
$pwa_script = '../assets/js/pwa.js';
$pwa_script_exists = file_exists($pwa_script);

// Verificar extensões PHP
$gd_enabled = extension_loaded('gd');
$imagick_enabled = extension_loaded('imagick');

// Verificar se PWA está ativado nas configurações
$pwa_enabled = getSetting('pwa_enabled') === '1';
$pwa_icon_url = getSetting('pwa_icon_url');
$pwa_icon_exists = false;
if (!empty($pwa_icon_url)) {
    try {
        $pwa_icon_exists = file_exists('../' . $pwa_icon_url);
    } catch (Exception $e) {
        $pwa_icon_exists = false;
    }
}

// Preparar resultado
$result = [
    'success' => true,
    'tests' => [
        'directories' => [
            'icons_dir_exists' => $icons_exist,
            'icons_dir_writable' => $icons_writable
        ],
        'files' => [
            'manifest_exists' => $manifest_exists,
            'htaccess_exists' => $htaccess_exists,
            'htaccess_has_rewrite' => $htaccess_has_rewrite,
            'service_worker_exists' => $sw_exists,
            'pwa_script_exists' => $pwa_script_exists
        ],
        'php_extensions' => [
            'gd_enabled' => $gd_enabled,
            'imagick_enabled' => $imagick_enabled
        ],
        'settings' => [
            'pwa_enabled' => $pwa_enabled,
            'pwa_icon_exists' => $pwa_icon_exists
        ]
    ]
];

// Verificar problemas
$issues = [];

if (!$icons_exist) {
    $issues[] = 'O diretório de ícones não existe. Execute: mkdir -p assets/img/icons/';
}

if ($icons_exist && !$icons_writable) {
    $issues[] = 'O diretório de ícones não tem permissão de escrita. Execute: chmod 755 assets/img/icons/';
}

if (!$htaccess_has_rewrite) {
    $issues[] = 'A regra de reescrita para o manifest.json não está configurada no .htaccess.';
}

if (!$gd_enabled && !$imagick_enabled) {
    $issues[] = 'Nenhuma extensão de processamento de imagem (GD ou Imagick) está disponível no PHP.';
}

$result['issues'] = $issues;
$result['has_issues'] = count($issues) > 0;

// Retornar resultado
header('Content-Type: application/json');
echo json_encode($result);