-- Criar tabela de notificações
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
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar e adicionar as colunas que estão faltando na tabela seller_lp_content
ALTER TABLE `seller_lp_content` ADD COLUMN IF NOT EXISTS `seller_photo` varchar(255) DEFAULT NULL;
ALTER TABLE `seller_lp_content` ADD COLUMN IF NOT EXISTS `footer_bg_color` varchar(20) DEFAULT '#343a40';
ALTER TABLE `seller_lp_content` ADD COLUMN IF NOT EXISTS `footer_text_color` varchar(20) DEFAULT '#f8f9fa';

-- Criar tabela followups se não existir
CREATE TABLE IF NOT EXISTS `followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('note','task','reminder','call','email','whatsapp') NOT NULL DEFAULT 'note',
  `content` text NOT NULL,
  `status` enum('pending','completed','canceled') DEFAULT 'pending',
  `due_date` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar tabela lead_messages se não existir
CREATE TABLE IF NOT EXISTS `lead_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `direction` enum('incoming','outgoing') NOT NULL DEFAULT 'outgoing',
  `channel` enum('whatsapp','email','sms','system') NOT NULL DEFAULT 'system',
  `content` text NOT NULL,
  `status` enum('sent','delivered','read','failed') DEFAULT 'sent',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;