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

-- Adicionar colunas na tabela seller_lp_content se não existirem
ALTER TABLE `seller_lp_content` ADD COLUMN IF NOT EXISTS `footer_bg_color` varchar(20) DEFAULT '#343a40';
ALTER TABLE `seller_lp_content` ADD COLUMN IF NOT EXISTS `footer_text_color` varchar(20) DEFAULT 'rgba(255,255,255,0.7)';