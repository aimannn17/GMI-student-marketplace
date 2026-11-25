-- ============================================================
--  GMI Student Marketplace - database schema
--  Import this file once (phpMyAdmin > Import, or: mysql -u root < marketplace.sql)
-- ============================================================
CREATE DATABASE IF NOT EXISTS `marketplace` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `marketplace`;

-- Users. role: 1 = member (can buy & sell), 2 = seller, 3 = admin
CREATE TABLE IF NOT EXISTS `user` (
  `user_id`      INT AUTO_INCREMENT PRIMARY KEY,
  `username`     VARCHAR(50)  NOT NULL UNIQUE,
  `fullname`     VARCHAR(100) NOT NULL,
  `password`     VARCHAR(255) NOT NULL,
  `phone_number` VARCHAR(20)  NOT NULL,
  `role`         TINYINT      NOT NULL DEFAULT 1,
  `status`       VARCHAR(20)  NOT NULL DEFAULT 'active',      -- active | suspended
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `categories` (
  `category_id`   INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `condition` (
  `condition_id`   INT AUTO_INCREMENT PRIMARY KEY,
  `condition_name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `item` (
  `item_id`      INT AUTO_INCREMENT PRIMARY KEY,
  `item_name`    VARCHAR(150)  NOT NULL,
  `price`        DECIMAL(10,2) NOT NULL,
  `description`  TEXT          NOT NULL,
  `phone_number` VARCHAR(20)   NOT NULL,
  `user_id`      INT           NOT NULL,
  `image`        VARCHAR(255)  DEFAULT NULL,
  `category_id`  INT           DEFAULT NULL,
  `condition_id` INT           DEFAULT NULL,
  `status`       VARCHAR(20)   NOT NULL DEFAULT 'active',     -- active | sold
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_item_user` (`user_id`),
  KEY `idx_item_status` (`status`),
  CONSTRAINT `fk_item_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Purchase / sales records. No foreign keys on purpose: history must survive
-- even if an item or account is later deleted.
CREATE TABLE IF NOT EXISTS `transactions` (
  `transaction_id`   INT AUTO_INCREMENT PRIMARY KEY,
  `item_id`          INT           NOT NULL,
  `buyer_id`         INT           NOT NULL,
  `seller_id`        INT           NOT NULL,
  `price`            DECIMAL(10,2) NOT NULL,
  `status`           VARCHAR(20)   NOT NULL DEFAULT 'Completed',
  `transaction_date` DATETIME      NOT NULL,
  `payment_method`   VARCHAR(30)   NOT NULL,
  `delivery_method`  VARCHAR(30)   NOT NULL,
  KEY `idx_tx_buyer`  (`buyer_id`),
  KEY `idx_tx_seller` (`seller_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `login_log` (
  `log_id`     INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT DEFAULT NULL,
  `username`   VARCHAR(50) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `status`     VARCHAR(20) NOT NULL,                          -- success | failed | suspended
  `login_time` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Required lookup data (ids match the old hard-coded list in home.php) ──
INSERT IGNORE INTO `categories` (`category_id`, `category_name`) VALUES
  (1, 'Books'), (2, 'Gadget'), (3, 'Food & Beverages'), (4, 'Clothing'), (5, 'Others');

INSERT IGNORE INTO `condition` (`condition_id`, `condition_name`) VALUES
  (1, 'new'), (2, 'like new'), (3, 'used');

-- ── Default admin account.  Username: admin   Password: admin123
--    CHANGE THIS PASSWORD after your first login (Profile page). ──
INSERT IGNORE INTO `user` (`username`, `fullname`, `password`, `phone_number`, `role`, `status`)
VALUES ('admin', 'Administrator', '0192023a7bbd73250516f069df18b500', '0000000000', 3, 'active');
