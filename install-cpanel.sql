-- SMM Turk Panel - cPanel version (no CREATE DATABASE)
-- اول در phpMyAdmin دیتابیس smmturk_tork رو انتخاب کن، بعد این فایل رو Import کن

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `balance` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `spent` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `status` enum('active','banned','pending') NOT NULL DEFAULT 'active',
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `api_key` varchar(64) DEFAULT NULL,
  `referral_code` varchar(10) DEFAULT NULL,
  `referred_by` int(11) DEFAULT NULL,
  `referral_earnings` decimal(10,4) DEFAULT 0.0000,
  `email_verification_token` varchar(64) DEFAULT NULL,
  `email_verification_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Services cache table
CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL UNIQUE,
  `name` text NOT NULL,
  `type` varchar(50) DEFAULT 'Default',
  `category` varchar(100) DEFAULT NULL,
  `rate` decimal(10,5) NOT NULL,
  `min` int(11) NOT NULL,
  `max` int(11) NOT NULL,
  `refill` tinyint(1) DEFAULT 0,
  `cancel` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `markup` decimal(5,2) DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `provider_order_id` int(11) DEFAULT NULL,
  `service_id` int(11) NOT NULL,
  `service_name` text,
  `link` text NOT NULL,
  `quantity` int(11) NOT NULL,
  `charge` decimal(10,4) NOT NULL,
  `start_count` int(11) DEFAULT 0,
  `remains` int(11) DEFAULT 0,
  `status` enum('Pending','Processing','In progress','Completed','Partial','Cancelled','Refunded') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transactions table
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('deposit','order','refund','referral','admin') NOT NULL,
  `amount` decimal(10,4) NOT NULL,
  `balance_before` decimal(10,4) NOT NULL,
  `balance_after` decimal(10,4) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tickets table
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('open','answered','closed') NOT NULL DEFAULT 'open',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ticket replies
CREATE TABLE IF NOT EXISTS `ticket_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings table
CREATE TABLE IF NOT EXISTS `settings` (
  `key` varchar(100) NOT NULL,
  `value` text,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `settings` (`key`, `value`) VALUES
('site_name', 'SMM Turk'),
('site_url', 'https://smm-turk.com'),
('api_url', 'https://smmfollows.com/api/v2'),
('api_key', ''),
('currency', 'USD'),
('currency_symbol', '$'),
('markup_percent', '10'),
('min_deposit', '10'),
('referral_commission', '2'),
('referral_min_payout', '10'),
('maintenance_mode', '0'),
('registration_enabled', '1'),
('smtp_host', ''),
('smtp_port', '587'),
('smtp_user', ''),
('smtp_pass', ''),
('smtp_from', 'noreply@smm-turk.com')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- Insert default admin user (password: password)
INSERT INTO `users` (`username`, `email`, `password`, `role`, `status`, `balance`) VALUES
('admin', 'admin@smm-turk.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 0.0000)
ON DUPLICATE KEY UPDATE `role` = 'admin';
