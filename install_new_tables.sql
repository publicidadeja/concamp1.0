-- Adicionar campo de telefone na tabela users se não existir
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `phone` varchar(20) DEFAULT NULL;

-- Adicionar campo de Pixel do Facebook na tabela users se não existir
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `facebook_pixel` varchar(255) DEFAULT NULL;

-- Tabela de conteúdo personalizado da landing page do vendedor
CREATE TABLE IF NOT EXISTS `seller_lp_content` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `seller_id` int(11) NOT NULL,
    `headline` varchar(100) DEFAULT NULL,
    `subheadline` varchar(150) DEFAULT NULL,
    `cta_text` varchar(50) DEFAULT NULL,
    `benefit_title` varchar(100) DEFAULT NULL,
    `featured_car` varchar(255) DEFAULT NULL,
    `seller_photo` varchar(255) DEFAULT NULL,
    `footer_bg_color` varchar(20) DEFAULT '#343a40',
    `footer_text_color` varchar(20) DEFAULT 'rgba(255,255,255,0.7)',
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `seller_id` (`seller_id`),
    CONSTRAINT `seller_lp_content_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de depoimentos
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `seller_id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `city` varchar(100) DEFAULT NULL,
    `content` text NOT NULL,
    `photo` varchar(255) DEFAULT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `seller_id` (`seller_id`),
    CONSTRAINT `testimonials_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de ganhadores
CREATE TABLE IF NOT EXISTS `winners` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `seller_id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `vehicle_model` varchar(100) NOT NULL,
    `credit_amount` decimal(10,2) NOT NULL,
    `contemplation_date` date NOT NULL,
    `photo` varchar(255) DEFAULT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `seller_id` (`seller_id`),
    CONSTRAINT `winners_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de notificações do sistema
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `title` varchar(100) NOT NULL,
    `message` text NOT NULL,
    `type` enum('lead', 'task', 'message', 'system') NOT NULL DEFAULT 'system',
    `icon` varchar(50) DEFAULT 'fas fa-bell',
    `color` varchar(20) DEFAULT 'primary',
    `reference_id` int(11) DEFAULT NULL,
    `reference_type` varchar(50) DEFAULT NULL,
    `is_read` tinyint(1) NOT NULL DEFAULT 0,
    `action_url` varchar(255) DEFAULT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `is_read` (`is_read`),
    KEY `created_at` (`created_at`),
    CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar colunas na tabela seller_lp_content se não existirem
ALTER TABLE `seller_lp_content` ADD COLUMN IF NOT EXISTS `footer_bg_color` varchar(20) DEFAULT '#343a40';
ALTER TABLE `seller_lp_content` ADD COLUMN IF NOT EXISTS `footer_text_color` varchar(20) DEFAULT 'rgba(255,255,255,0.7)';

-- Adicionar colunas para os textos dos benefícios na landing page
ALTER TABLE `seller_lp_content` ADD COLUMN IF NOT EXISTS `benefit_1_title` varchar(100) DEFAULT 'Parcelas Menores';
ALTER TABLE `seller_lp_content` ADD COLUMN IF NOT EXISTS `benefit_1_text` varchar(255) DEFAULT 'Até 50% mais baratas que financiamentos tradicionais, sem juros abusivos.';
ALTER TABLE `seller_lp_content` ADD COLUMN IF NOT EXISTS `benefit_2_title` varchar(100) DEFAULT 'Segurança Garantida';
ALTER TABLE `seller_lp_content` ADD COLUMN IF NOT EXISTS `benefit_2_text` varchar(255) DEFAULT 'Contratos registrados e empresas autorizadas pelo Banco Central.';
ALTER TABLE `seller_lp_content` ADD COLUMN IF NOT EXISTS `benefit_3_title` varchar(100) DEFAULT 'Contemplação Acelerada';
ALTER TABLE `seller_lp_content` ADD COLUMN IF NOT EXISTS `benefit_3_text` varchar(255) DEFAULT 'Estratégias exclusivas para aumentar suas chances de contemplação rápida.';