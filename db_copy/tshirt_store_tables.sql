-- T-Shirt Store Database Tables
-- Run this SQL to create the required tables for the T-shirt ordering system

-- ============================================================================
-- Table: tshirt_orders
-- Stores all T-shirt orders placed through the website
-- ============================================================================
CREATE TABLE IF NOT EXISTS tshirt_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    shipping_address TEXT NOT NULL,

    -- Quantities per size
    qty_xs INT NOT NULL DEFAULT 0,
    qty_s INT NOT NULL DEFAULT 0,
    qty_m INT NOT NULL DEFAULT 0,
    qty_l INT NOT NULL DEFAULT 0,
    qty_xl INT NOT NULL DEFAULT 0,
    qty_xxl INT NOT NULL DEFAULT 0,

    total_amount DECIMAL(10,2) NOT NULL,

    -- Status tracking
    paid TINYINT NOT NULL DEFAULT 0,
    paid_date DATETIME NULL,
    paypal_order_id VARCHAR(100) NULL,
    fulfilled TINYINT NOT NULL DEFAULT 0,
    fulfilled_date DATETIME NULL,
    fulfilled_by INT NULL,

    -- Audit info
    source_ip VARCHAR(45) NOT NULL,
    notes TEXT NULL,

    INDEX idx_paid (paid),
    INDEX idx_fulfilled (fulfilled),
    INDEX idx_order_date (order_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- Table: item_prices
-- Stores prices for all store items (T-shirts, future items)
-- ============================================================================
CREATE TABLE IF NOT EXISTS item_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_category VARCHAR(50) NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    item_code VARCHAR(20) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 15.00,
    active TINYINT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY idx_item_code (item_code),
    INDEX idx_category (item_category),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert initial T-shirt prices (all default to $15.00)
INSERT INTO item_prices (item_category, item_name, item_code, price, sort_order) VALUES
    ('tshirt', 'Class B T-Shirt - XS', 'tshirt_xs', 15.00, 1),
    ('tshirt', 'Class B T-Shirt - S', 'tshirt_s', 15.00, 2),
    ('tshirt', 'Class B T-Shirt - M', 'tshirt_m', 15.00, 3),
    ('tshirt', 'Class B T-Shirt - L', 'tshirt_l', 15.00, 4),
    ('tshirt', 'Class B T-Shirt - XL', 'tshirt_xl', 15.00, 5),
    ('tshirt', 'Class B T-Shirt - XXL', 'tshirt_xxl', 15.00, 6)
ON DUPLICATE KEY UPDATE item_name = VALUES(item_name);

-- ============================================================================
-- Table: store_config
-- Stores configuration settings for the store
-- ============================================================================
CREATE TABLE IF NOT EXISTS store_config (
    config_key VARCHAR(50) PRIMARY KEY,
    config_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert initial store configuration
INSERT INTO store_config (config_key, config_value) VALUES
    ('tshirt_orders_enabled', '1'),
    ('tshirt_image_url', '/images/tshirt-classb.jpg')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);

-- Note: Notification preferences are stored in the users.notif_preferences JSON column
-- Example JSON format: {"tshirt_order": true}
-- Notification types currently supported:
-- 'tshirt_order' - New T-shirt order placed (for treasurers)
-- Future types can be added as needed
