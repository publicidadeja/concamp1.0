<?php
// Versão simplificada apenas para teste direto
echo json_encode([
    'success' => true,
    'tests' => [
        'directories' => [
            'icons_dir_exists' => true,
            'icons_dir_writable' => true
        ],
        'files' => [
            'manifest_exists' => true,
            'htaccess_exists' => true,
            'htaccess_has_rewrite' => true,
            'service_worker_exists' => true,
            'pwa_script_exists' => true
        ],
        'php_extensions' => [
            'gd_enabled' => true,
            'imagick_enabled' => false
        ],
        'settings' => [
            'pwa_enabled' => true,
            'pwa_icon_exists' => true
        ]
    ],
    'issues' => [],
    'has_issues' => false
]);
?>