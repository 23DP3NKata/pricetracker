-- PriceTracker full MySQL schema
-- Generated from current Laravel migrations in backend/database/migrations
-- MySQL 8.0+

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS `pricetrackerdb`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `pricetrackerdb`;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables in dependency-safe order
DROP TABLE IF EXISTS `system_logs`;
DROP TABLE IF EXISTS `admin_actions`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `price_history`;
DROP TABLE IF EXISTS `user_products`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- Users
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  `status` ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
  `status_changed_by` BIGINT UNSIGNED NULL,
  `status_changed_at` TIMESTAMP NULL DEFAULT NULL,
  `monthly_limit` INT UNSIGNED NOT NULL DEFAULT 5,
  `checks_used` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_username_change` TIMESTAMP NULL DEFAULT NULL,
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_name_unique` (`name`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_status_index` (`status`),
  KEY `users_status_changed_by_index` (`status_changed_by`),
  CONSTRAINT `users_status_changed_by_foreign`
    FOREIGN KEY (`status_changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions (Sanctum SPA)
CREATE TABLE `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password reset tokens (Breeze)
CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products
CREATE TABLE `products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `url` VARCHAR(500) NOT NULL,
  `image_url` VARCHAR(500) NULL,
  `current_price` DECIMAL(10,2) NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'EUR',
  `store_name` VARCHAR(100) NULL,
  `status` ENUM('active', 'hidden') NOT NULL DEFAULT 'active',
  `tracking_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `checks_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_successful_check` TIMESTAMP NULL DEFAULT NULL,
  `consecutive_errors` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_url_unique` (`url`),
  KEY `products_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User products (tracking subscriptions)
CREATE TABLE `user_products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `check_interval` INT UNSIGNED NOT NULL DEFAULT 1440,
  `last_checked_at` TIMESTAMP NULL DEFAULT NULL,
  `next_check_at` TIMESTAMP NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_products_user_id_product_id_unique` (`user_id`, `product_id`),
  KEY `user_products_next_check_at_is_active_index` (`next_check_at`, `is_active`),
  KEY `user_products_product_id_index` (`product_id`),
  CONSTRAINT `user_products_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_products_product_id_foreign`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Price history
CREATE TABLE `price_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `checked_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `price_history_product_id_checked_at_index` (`product_id`, `checked_at`),
  KEY `price_history_checked_at_index` (`checked_at`),
  CONSTRAINT `price_history_product_id_foreign`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `old_price` DECIMAL(10,2) NOT NULL,
  `new_price` DECIMAL(10,2) NOT NULL,
  `message` TEXT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_is_read_index` (`user_id`, `is_read`),
  KEY `notifications_created_at_index` (`created_at`),
  KEY `notifications_product_id_index` (`product_id`),
  CONSTRAINT `notifications_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_product_id_foreign`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin actions
CREATE TABLE `admin_actions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_user_id` BIGINT UNSIGNED NULL,
  `action_type` ENUM(
    'block_user', 'unblock_user', 'delete_user', 'restore_user',
    'hide_product', 'restore_product',
    'change_user_role', 'promote_user', 'demote_user', 'change_user_limit'
  ) NOT NULL,
  `target_user_id` BIGINT UNSIGNED NULL,
  `target_product_id` BIGINT UNSIGNED NULL,
  `reason` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_actions_created_at_index` (`created_at`),
  KEY `admin_actions_action_type_index` (`action_type`),
  KEY `admin_actions_admin_user_id_index` (`admin_user_id`),
  KEY `admin_actions_target_user_id_index` (`target_user_id`),
  KEY `admin_actions_target_product_id_index` (`target_product_id`),
  CONSTRAINT `admin_actions_admin_user_id_foreign`
    FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `admin_actions_target_user_id_foreign`
    FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `admin_actions_target_product_id_foreign`
    FOREIGN KEY (`target_product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System logs
CREATE TABLE `system_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `level` ENUM('info', 'warning', 'error', 'critical') NOT NULL,
  `category` ENUM('scraper', 'price_check', 'auth', 'email', 'database', 'api', 'system') NOT NULL,
  `message` TEXT NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `user_name_snapshot` VARCHAR(100) NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `stack_trace` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `system_logs_level_created_at_index` (`level`, `created_at`),
  KEY `system_logs_level_index` (`level`),
  KEY `system_logs_category_index` (`category`),
  KEY `system_logs_user_id_index` (`user_id`),
  KEY `system_logs_product_id_index` (`product_id`),
  CONSTRAINT `system_logs_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `system_logs_product_id_foreign`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
