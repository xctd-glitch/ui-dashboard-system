-- Superadmin configuration
CREATE TABLE IF NOT EXISTS `system_config` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `system_on` TINYINT DEFAULT 1,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Superadmins table
CREATE TABLE IF NOT EXISTS `superadmins` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(255) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` TINYINT DEFAULT 1
);

-- Tags table for admin assignments
CREATE TABLE IF NOT EXISTS `tags` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) UNIQUE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Superadmin tags (read-only after assignment)
CREATE TABLE IF NOT EXISTS `superadmin_tags` (
  `superadmin_id` INT NOT NULL,
  `tag_id` INT NOT NULL,
  `assigned_by_id` INT,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`superadmin_id`, `tag_id`),
  FOREIGN KEY (`superadmin_id`) REFERENCES `superadmins`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
);

-- Admins (Pre-admins) table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(255) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` TINYINT DEFAULT 1,
  `created_by_superadmin_id` INT,
  FOREIGN KEY (`created_by_superadmin_id`) REFERENCES `superadmins`(`id`) ON DELETE SET NULL
);

-- Admin tags (assigned during admin creation by superadmin)
CREATE TABLE IF NOT EXISTS `admin_tags` (
  `admin_id` INT NOT NULL,
  `tag_id` INT NOT NULL,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`, `tag_id`),
  FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
);

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(255) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` TINYINT DEFAULT 1,
  `created_by_admin_id` INT,
  FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL
);

-- User tags (assigned during user creation by admin)
CREATE TABLE IF NOT EXISTS `user_tags` (
  `user_id` INT NOT NULL,
  `tag_id` INT NOT NULL,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `tag_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
);

-- Countries per admin
CREATE TABLE IF NOT EXISTS `admin_countries` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `admin_id` INT NOT NULL,
  `iso_code` VARCHAR(2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (`admin_id`, `iso_code`),
  FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE,
  INDEX (`iso_code`)
);

-- Countries per user
CREATE TABLE IF NOT EXISTS `user_countries` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `iso_code` VARCHAR(2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (`user_id`, `iso_code`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX (`iso_code`)
);

-- Target URLs for admins
CREATE TABLE IF NOT EXISTS `admin_target_urls` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `admin_id` INT NOT NULL,
  `url` VARCHAR(2048) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE,
  INDEX (`admin_id`)
);

-- Target URLs for users
CREATE TABLE IF NOT EXISTS `user_target_urls` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `url` VARCHAR(2048) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX (`user_id`)
);

-- Parked domains (managed by admins)
CREATE TABLE IF NOT EXISTS `admin_parked_domains` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `admin_id` INT NOT NULL,
  `domain` VARCHAR(255) NOT NULL,
  `cloudflare_synced` TINYINT DEFAULT 0,
  `cloudflare_sync_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY (`admin_id`, `domain`),
  FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE,
  INDEX (`admin_id`)
);

-- Parked domains (managed by users)
CREATE TABLE IF NOT EXISTS `user_parked_domains` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `domain` VARCHAR(255) NOT NULL,
  `cloudflare_synced` TINYINT DEFAULT 0,
  `cloudflare_sync_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY (`user_id`, `domain`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX (`user_id`)
);

-- User routing configurations
CREATE TABLE IF NOT EXISTS `user_routing_config` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL UNIQUE,
  `device_scope` ENUM('WAP', 'WEB', 'ALL') DEFAULT 'ALL',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Rules for redirect decision logic
CREATE TABLE IF NOT EXISTS `redirect_rules` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `rule_type` ENUM('mute_unmute', 'random_route', 'static_route') NOT NULL,
  `is_enabled` TINYINT DEFAULT 1,
  `mute_duration_on` INT DEFAULT 120,
  `mute_duration_off` INT DEFAULT 300,
  `target_url` VARCHAR(2048),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX (`user_id`, `is_enabled`)
);

-- Rule state tracking (for mute/unmute cycles)
CREATE TABLE IF NOT EXISTS `rule_state` (
  `rule_id` INT NOT NULL PRIMARY KEY,
  `last_state_change` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_muted` TINYINT DEFAULT 0,
  FOREIGN KEY (`rule_id`) REFERENCES `redirect_rules`(`id`) ON DELETE CASCADE
);

-- Domain selection preferences per user
CREATE TABLE IF NOT EXISTS `user_domain_selection` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL UNIQUE,
  `selection_type` ENUM('random_global', 'random_user', 'specific') DEFAULT 'random_global',
  `specific_domain` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Metrics and logs
CREATE TABLE IF NOT EXISTS `redirect_logs` (
  `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT,
  `domain_used` VARCHAR(255),
  `target_url` VARCHAR(2048),
  `country_iso` VARCHAR(2),
  `device_type` VARCHAR(50),
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `is_vpn` TINYINT DEFAULT 0,
  `rule_applied` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`, `created_at`),
  INDEX (`country_iso`),
  INDEX (`device_type`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- Tracker events
CREATE TABLE IF NOT EXISTS `tracker_events` (
  `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `redirect_log_id` BIGINT,
  `event_type` VARCHAR(100),
  `event_data` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`redirect_log_id`, `created_at`),
  FOREIGN KEY (`redirect_log_id`) REFERENCES `redirect_logs`(`id`) ON DELETE CASCADE
);

-- Conversions
CREATE TABLE IF NOT EXISTS `conversions` (
  `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `redirect_log_id` BIGINT,
  `conversion_type` VARCHAR(100),
  `conversion_value` DECIMAL(10, 2),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`redirect_log_id`, `created_at`),
  FOREIGN KEY (`redirect_log_id`) REFERENCES `redirect_logs`(`id`) ON DELETE CASCADE
);

-- System logs for audit trail
CREATE TABLE IF NOT EXISTS `system_logs` (
  `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `actor_type` ENUM('superadmin', 'admin', 'user', 'system'),
  `actor_id` INT,
  `action` VARCHAR(255),
  `resource_type` VARCHAR(100),
  `resource_id` INT,
  `details` JSON,
  `ip_address` VARCHAR(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`actor_id`, `created_at`),
  INDEX (`resource_type`, `created_at`)
);

-- Cloudflare sync status
CREATE TABLE IF NOT EXISTS `cloudflare_sync_status` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `domain` VARCHAR(255) UNIQUE NOT NULL,
  `last_sync_at` TIMESTAMP NULL,
  `sync_status` ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
  `error_message` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
