<?php
// Verifica se o sistema já está instalado
if (file_exists(__DIR__ . '/../config/config.php')) {
    echo "O sistema já está instalado. Para reinstalar, exclua o arquivo config/config.php.";
    exit;
}

// Verificar se o formulário foi enviado
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $db_host = trim($_POST['db_host']);
    $db_name = trim($_POST['db_name']);
    $db_user = trim($_POST['db_user']);
    $db_pass = $_POST['db_pass'];
    $admin_name = trim($_POST['admin_name']);
    $admin_email = trim($_POST['admin_email']);
    $admin_pass = $_POST['admin_pass'];
    $admin_pass_confirm = $_POST['admin_pass_confirm'];
    $whatsapp_token = trim($_POST['whatsapp_token']);

    // Validar campos
    if (empty($db_host) || empty($db_name) || empty($db_user) || 
        empty($admin_name) || empty($admin_email) || empty($admin_pass)) {
        $error = "Todos os campos são obrigatórios, exceto a senha do banco de dados.";
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email inválido.";
    } elseif ($admin_pass !== $admin_pass_confirm) {
        $error = "As senhas não coincidem.";
    } elseif (strlen($admin_pass) < 8) {
        $error = "A senha deve ter pelo menos 8 caracteres.";
    } else {
        // Tentar conectar ao banco de dados
        try {
            $conn = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Criar banco de dados se não existir
            $conn->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $conn->exec("USE `$db_name`");
            
            // Criar tabelas
            // Tabela de usuários
            $conn->exec("CREATE TABLE IF NOT EXISTS `users` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `email` varchar(255) NOT NULL,
                `password` varchar(255) NOT NULL,
                `role` enum('admin','manager','seller') NOT NULL DEFAULT 'seller',
                `status` enum('active','inactive') NOT NULL DEFAULT 'active',
                `landing_page_name` varchar(255) DEFAULT NULL,
                `whatsapp_token` varchar(255) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `email` (`email`),
                UNIQUE KEY `unique_landing_page` (`landing_page_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Tabela de planos
            $conn->exec("CREATE TABLE IF NOT EXISTS `plans` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `plan_type` enum('car','motorcycle') NOT NULL,
                `model` varchar(255) DEFAULT NULL,
                `credit_value` decimal(10,2) NOT NULL,
                `term` int(11) NOT NULL,
                `first_installment` decimal(10,2) NOT NULL,
                `other_installments` decimal(10,2) NOT NULL,
                `admin_fee` decimal(10,2) DEFAULT '0.00',
                `active` tinyint(1) NOT NULL DEFAULT '1',
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Tabela de leads
            $conn->exec("CREATE TABLE IF NOT EXISTS `leads` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `email` varchar(255) DEFAULT NULL,
                `phone` varchar(20) NOT NULL,
                `city` varchar(255) DEFAULT NULL,
                `state` varchar(50) DEFAULT NULL,
                `plan_id` int(11) DEFAULT NULL,
                `plan_type` enum('car','motorcycle') DEFAULT NULL,
                `plan_credit` decimal(10,2) DEFAULT NULL,
                `plan_model` varchar(255) DEFAULT NULL,
                `plan_term` int(11) DEFAULT NULL,
                `first_installment` decimal(10,2) DEFAULT NULL,
                `other_installments` decimal(10,2) DEFAULT NULL,
                `status` enum('new','contacted','negotiating','converted','lost') NOT NULL DEFAULT 'new',
                `seller_id` int(11) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `plan_id` (`plan_id`),
                KEY `seller_id` (`seller_id`),
                CONSTRAINT `leads_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL,
                CONSTRAINT `leads_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Tabela de tarefas e follow-ups
            $conn->exec("CREATE TABLE IF NOT EXISTS `follow_ups` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `lead_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `type` enum('note','task','reminder') NOT NULL,
                `content` text NOT NULL,
                `due_date` datetime DEFAULT NULL,
                `status` enum('pending','completed') DEFAULT 'pending',
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `lead_id` (`lead_id`),
                KEY `user_id` (`user_id`),
                CONSTRAINT `follow_ups_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
                CONSTRAINT `follow_ups_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Tabela de modelos de mensagens
            $conn->exec("CREATE TABLE IF NOT EXISTS `message_templates` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `category` enum('simulation','follow_up','benefits','interest','meeting','post_meeting','contract') NOT NULL,
                `content` text NOT NULL,
                `active` tinyint(1) NOT NULL DEFAULT '1',
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Tabela de mensagens enviadas
            $conn->exec("CREATE TABLE IF NOT EXISTS `sent_messages` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `lead_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `template_id` int(11) DEFAULT NULL,
                `message` text NOT NULL,
                `media_url` varchar(255) DEFAULT NULL,
                `media_type` varchar(50) DEFAULT NULL,
                `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `lead_id` (`lead_id`),
                KEY `user_id` (`user_id`),
                KEY `template_id` (`template_id`),
                CONSTRAINT `sent_messages_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
                CONSTRAINT `sent_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                CONSTRAINT `sent_messages_ibfk_3` FOREIGN KEY (`template_id`) REFERENCES `message_templates` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Tabela de configurações
            $conn->exec("CREATE TABLE IF NOT EXISTS `settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `setting_key` varchar(255) NOT NULL,
                `setting_value` text NOT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `setting_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Inserir configurações padrão
            $stmt = $conn->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES (:key, :value)");
            $stmt->execute(['key' => 'company_name', 'value' => 'ConCamp']);
            $stmt->execute(['key' => 'whatsapp_token', 'value' => $whatsapp_token]);
            $stmt->execute(['key' => 'default_consultant', 'value' => $admin_name]);
            
            // Inserir usuário administrador
            $admin_pass_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES (:name, :email, :password, 'admin')");
            $stmt->execute([
                'name' => $admin_name,
                'email' => $admin_email,
                'password' => $admin_pass_hash
            ]);
            
            // Inserir planos pré-configurados - Carros 60 meses
            $cars_60 = [
                ['car', null, 35364.78, 60, 1218.30, 664.83],
                ['car', null, 41730.49, 60, 1437.46, 784.52],
                ['car', null, 40532.70, 60, 1396.31, 761.89],
                ['car', null, 41226.95, 60, 1420.21, 775.08],
                ['car', null, 45157.59, 60, 1555.71, 848.90]
            ];
            
            // Inserir planos pré-configurados - Carros 80 meses
            $cars_80 = [
                ['car', null, 35364.78, 80, 974.64, 488.44],
                ['car', null, 41730.49, 80, 1149.97, 576.21],
                ['car', null, 40532.70, 80, 1117.04, 559.65],
                ['car', null, 41226.95, 80, 1136.17, 569.24],
                ['car', null, 45157.59, 80, 1244.56, 623.53]
            ];
            
            // Inserir planos pré-configurados - Motos 60 meses
            $motos_60 = [
                ['motorcycle', 'CRUISYM 150', 17400.00, 60, 599.52, 327.12],
                ['motorcycle', 'NK 150', 18500.00, 60, 637.40, 347.80],
                ['motorcycle', 'DR 160', 20400.00, 60, 702.96, 383.52],
                ['motorcycle', 'DK 150 CBS', 14900.00, 60, 513.36, 280.12],
                ['motorcycle', 'NH 190', 19000.00, 60, 654.56, 357.20],
                ['motorcycle', 'KTM DUKE 390', 39500.00, 60, 1361.28, 742.60]
            ];
            
            // Inserir planos pré-configurados - Motos 72 meses
            $motos_72 = [
                ['motorcycle', 'CRUISYM 150', 17400.00, 72, 522.00, 272.88],
                ['motorcycle', 'NK 150', 18500.00, 72, 555.00, 290.10],
                ['motorcycle', 'DR 160', 20400.00, 72, 612.00, 319.92],
                ['motorcycle', 'DK 150 CBS', 14900.00, 72, 447.00, 233.76],
                ['motorcycle', 'NH 190', 19000.00, 72, 570.00, 298.06],
                ['motorcycle', 'KTM DUKE 390', 39500.00, 72, 1185.00, 619.14]
            ];
            
            // Inserir planos no banco de dados
            $plans = array_merge($cars_60, $cars_80, $motos_60, $motos_72);
            
            $stmt = $conn->prepare("INSERT INTO `plans` 
    (`name`, `plan_type`, `model`, `credit_value`, `term`, `first_installment`, `other_installments`, `admin_fee`) 
    VALUES (:name, :type, :model, :credit, :term, :first, :other, :admin_fee)");

foreach ($plans as $plan) {
    $plan_name = $plan[0] === 'car' ? 
        "Plano Carro " . number_format($plan[2], 2, ',', '.') . " - " . $plan[3] . "x" :
        $plan[1] . " - " . $plan[3] . "x";
    
    $stmt->execute([
        'name' => $plan_name,
        'type' => $plan[0],
        'model' => $plan[1],
        'credit' => $plan[2],
        'term' => $plan[3],
        'first' => $plan[4],
        'other' => $plan[5],
        'admin_fee' => 0.00 // Valor padrão para taxa administrativa
    ]);
}

// Inserir configurações padrão
$default_settings = [
    ['company_name', 'ConCamp'],
    ['whatsapp_token', $whatsapp_token],
    ['default_consultant', $admin_name],
    ['default_message_template', $default_message],
    ['site_title', 'ConCamp - Sistema de Gestão de Consórcios'],
    ['leads_per_page', '10'],
    ['enable_whatsapp', '1'],
    ['notification_email', $admin_email]
];

$stmt = $conn->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES (:key, :value)");
foreach ($default_settings as $setting) {
    $stmt->execute(['key' => $setting[0], 'value' => $setting[1]]);
}

// Inserir templates de mensagem adicionais
$message_templates = [
    [
        'name' => 'Mensagem de Boas-vindas',
        'category' => 'follow_up',
        'content' => "Olá {nome}! Seja bem-vindo(a) à ConCamp!\n\nSou {nome_consultor}, seu consultor(a) dedicado(a)..."
    ],
    [
        'name' => 'Lembrete de Reunião',
        'category' => 'meeting',
        'content' => "Olá {nome}! Confirmando nossa reunião para {data_reuniao}..."
    ]
    // Adicione mais templates conforme necessário
];

$stmt = $conn->prepare("INSERT INTO `message_templates` (`name`, `category`, `content`) VALUES (:name, :category, :content)");
foreach ($message_templates as $template) {
    $stmt->execute([
        'name' => $template['name'],
        'category' => $template['category'],
        'content' => $template['content']
    ]);
}

// Após cada operação de criação de tabela
if (!$result) {
    throw new PDOException("Erro ao criar tabela: " . implode(" ", $conn->errorInfo()));
}
            
            // Inserir modelo de mensagem padrão para simulação
            $default_message = "Olá {nome}! Aqui está sua simulação de Contrato Premiado ConCamp:

📊 *SUA SIMULAÇÃO PERSONALIZADA*
🚗 Tipo: {tipo_veiculo}
💰 Crédito: R$ {valor_credito}
⏳ Prazo: {prazo} meses
💵 Primeira parcela: R$ {valor_primeira}
💸 Demais parcelas: R$ {valor_demais}/mês

✅ Vantagens do seu Contrato Premiado:
• Sorteio mensal pela Loteria Federal
• Você começa a concorrer após o pagamento da 2ª parcela
• Possibilidade de quitar 100% do contrato
• Empresa com mais de 20 anos no mercado
• Mais de 400 prêmios já entregues

Quer saber mais detalhes ou agendar uma apresentação? Estou à disposição! 😊

*{nome_consultor}*
Consultor(a) ConCamp";

            $stmt = $conn->prepare("INSERT INTO `message_templates` 
                (`name`, `category`, `content`) 
                VALUES ('Mensagem Padrão para Simulação', 'simulation', :content)");
            $stmt->execute(['content' => $default_message]);
            
            // Criar arquivo de configuração
            $config_content = "<?php
// Configurações do banco de dados
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');

// Configurações da aplicação
define('APP_NAME', 'ConCamp');
define('APP_URL', '');
define('WHATSAPP_API_URL', 'https://api2.publicidadeja.com.br/api/messages/send');
define('WHATSAPP_API_TOKEN', '$whatsapp_token');

// Não altere as configurações abaixo
define('PATH_ROOT', __DIR__ . '/..');
?>";
            
            // Criar diretório config se não existir
            if (!file_exists(__DIR__ . '/../config')) {
                mkdir(__DIR__ . '/../config', 0755, true);
            }
            
            // Salvar arquivo de configuração
            file_put_contents(__DIR__ . '/../config/config.php', $config_content);
            
            $success = "Instalação concluída com sucesso! <a href='../index.php'>Clique aqui</a> para acessar o sistema.";
        } catch (PDOException $e) {
            $error = "Erro na instalação: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - ConCamp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
        }
        .install-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            font-weight: bold;
            color: #0d6efd;
        }
        .form-group {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <div class="logo">
                <h1>ConCamp</h1>
                <p>Instalação do Sistema</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php else: ?>
                <form method="post" action="">
                    <h4 class="mb-4">Configuração do Banco de Dados</h4>
                    
                    <div class="form-group">
                        <label for="db_host">Host do Banco de Dados</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Nome do Banco de Dados</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" value="concamp" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user">Usuário do Banco de Dados</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_pass">Senha do Banco de Dados</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass">
                    </div>
                    
                    <h4 class="mt-5 mb-4">Configuração do Administrador</h4>
                    
                    <div class="form-group">
                        <label for="admin_name">Nome do Administrador</label>
                        <input type="text" class="form-control" id="admin_name" name="admin_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">Email do Administrador</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_pass">Senha do Administrador</label>
                        <input type="password" class="form-control" id="admin_pass" name="admin_pass" required>
                        <small class="form-text text-muted">A senha deve ter pelo menos 8 caracteres.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_pass_confirm">Confirme a Senha</label>
                        <input type="password" class="form-control" id="admin_pass_confirm" name="admin_pass_confirm" required>
                    </div>
                    
                    <h4 class="mt-5 mb-4">Configuração da API de WhatsApp</h4>
                    
                    <div class="form-group">
                        <label for="whatsapp_token">Token da API de WhatsApp</label>
                        <input type="text" class="form-control" id="whatsapp_token" name="whatsapp_token" required>
                        <small class="form-text text-muted">Token de autenticação para a API de WhatsApp.</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block w-100 mt-4">Instalar Sistema</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
