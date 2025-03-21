<?php
// Script simples para testar upload
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Teste de upload funcionando',
    'timestamp' => time(),
    'server_info' => [
        'php_version' => PHP_VERSION,
        'max_upload_size' => ini_get('upload_max_filesize'),
        'max_post_size' => ini_get('post_max_size')
    ]
]);
