<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'concamp');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurações da aplicação
define('APP_NAME', 'ConCamp');
define('APP_URL', '/concamp');
define('WHATSAPP_API_URL', 'https://api2.publicidadeja.com.br/api/messages/send');
// Token global da API WhatsApp como backup, mas o ideal é que cada usuário tenha o seu
define('WHATSAPP_API_TOKEN', '23071997');

// Não altere as configurações abaixo
define('PATH_ROOT', __DIR__ . '/..');
?>