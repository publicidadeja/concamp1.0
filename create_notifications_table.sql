-- Criar tabela de notificações se não existir
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