-- Migration : ajouter tables ventes et journal de stock
CREATE TABLE IF NOT EXISTS `sales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ref` VARCHAR(60) NOT NULL UNIQUE,
  `user_id` INT DEFAULT NULL,
  `client_id` INT DEFAULT NULL,
  `total_ht` DECIMAL(12,2) NOT NULL,
  `total_ttc` DECIMAL(12,2) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sale_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sale_id` INT NOT NULL,
  `article_id` INT NOT NULL,
  `qty` INT NOT NULL,
  `price_unit` DECIMAL(12,2) NOT NULL,
  `total` DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `article_id` INT NOT NULL,
  `type` ENUM('in','out','adjust','import','repair_use','sale') NOT NULL,
  `qty_change` INT NOT NULL,
  `reference` VARCHAR(100) DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `note` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `repair_parts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `repair_id` INT NOT NULL,
  `article_id` INT NOT NULL,
  `qty` INT NOT NULL,
  `price_unit` DECIMAL(12,2) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
