-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    status_changed_by INT UNSIGNED NULL,
    status_changed_at TIMESTAMP NULL,
    monthly_limit INT UNSIGNED NOT NULL DEFAULT 0,
    checks_used INT UNSIGNED NOT NULL DEFAULT 0,
    last_username_change TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_users_status (status),
    FOREIGN KEY (status_changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: sessions
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,

    INDEX idx_sessions_user_id (user_id),
    INDEX idx_sessions_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: password_reset_tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: products
CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    symbol VARCHAR(20) NULL,
    coingecko_id VARCHAR(120) NULL,
    product_page_url VARCHAR(500) NULL,
    canonical_url VARCHAR(500) NOT NULL UNIQUE,
    image_url VARCHAR(500) NULL,
    current_price DECIMAL(20, 8) NULL,
    price_change_24h DECIMAL(10, 4) NULL,
    trend ENUM('up', 'down', 'flat') NOT NULL DEFAULT 'flat',
    rank SMALLINT UNSIGNED NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'EUR',
    status ENUM('active', 'hidden') NOT NULL DEFAULT 'active',
    tracking_count INT UNSIGNED NOT NULL DEFAULT 0,
    checks_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_successful_check TIMESTAMP NULL,
    consecutive_errors INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_products_status (status),
    INDEX idx_products_symbol (symbol),
    INDEX idx_products_coingecko_id (coingecko_id),
    INDEX idx_products_rank (rank)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: user_products
CREATE TABLE IF NOT EXISTS user_products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    target_price DECIMAL(20, 8) NULL,
    notify_when ENUM('below', 'above') NOT NULL DEFAULT 'below',
    last_checked_at TIMESTAMP NULL,
    last_notified_at TIMESTAMP NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_products_is_active_target_price (is_active, target_price),
    INDEX idx_user_products_user_id (user_id),
    INDEX idx_user_products_product_id (product_id),
    INDEX idx_user_products_user_id_product_id (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: price_history
CREATE TABLE IF NOT EXISTS price_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    price DECIMAL(20, 8) NOT NULL,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_price_history_product_id_checked_at (product_id, checked_at),
    INDEX idx_price_history_checked_at (checked_at),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    old_price DECIMAL(20, 8) NOT NULL,
    new_price DECIMAL(20, 8) NOT NULL,
    message_key VARCHAR(255) NULL,
    message_params JSON NULL,
    message TEXT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_notifications_user_id_is_read (user_id, is_read),
    INDEX idx_notifications_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: admin_actions
CREATE TABLE IF NOT EXISTS admin_actions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT UNSIGNED NULL,
    action_type ENUM(
        'block_user', 'unblock_user', 'delete_user', 'restore_user',
        'hide_product', 'restore_product',
        'change_user_role', 'promote_user', 'demote_user', 'change_user_limit'
    ) NOT NULL,
    target_user_id INT UNSIGNED NULL,
    target_product_id INT UNSIGNED NULL,
    reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_admin_actions_created_at (created_at),
    INDEX idx_admin_actions_action_type (action_type),
    FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (target_product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: system_logs
CREATE TABLE IF NOT EXISTS system_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    level ENUM('info', 'warning', 'error', 'critical') NOT NULL,
    category ENUM(
        'scraper', 'price_check', 'auth', 'email',
        'database', 'api', 'system',
        'admin'
    ) NOT NULL,
    message TEXT NOT NULL,
    user_id INT UNSIGNED NULL,
    user_name_snapshot VARCHAR(100) NULL,
    product_id INT UNSIGNED NULL,
    stack_trace TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_system_logs_level_created_at (level, created_at),
    INDEX idx_system_logs_level (level),
    INDEX idx_system_logs_category (category),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: jobs
CREATE TABLE IF NOT EXISTS jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,

    INDEX idx_jobs_queue (queue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: failed_jobs
CREATE TABLE IF NOT EXISTS failed_jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    connection VARCHAR(191) NOT NULL,
    queue VARCHAR(191) NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_failed_jobs_connection_queue_failed_at (connection, queue, failed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: job_batches
CREATE TABLE IF NOT EXISTS job_batches (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    total_jobs INT NOT NULL,
    pending_jobs INT NOT NULL,
    failed_jobs INT NOT NULL,
    failed_job_ids LONGTEXT NOT NULL,
    options MEDIUMTEXT NULL,
    cancelled_at INT NULL,
    created_at INT NOT NULL,
    finished_at INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
