-- Schema for backend_mysql (nwrcontractregistry)
CREATE DATABASE IF NOT EXISTS `nwrcontractregistry` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nwrcontractregistry`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- NOTE: this inserts a plain-text password 'secret' for quick testing. Change to a hashed password for production.
INSERT INTO `users` (`username`, `password`) VALUES ('admin', 'secret')
ON DUPLICATE KEY UPDATE password=VALUES(password);

CREATE TABLE IF NOT EXISTS `contracts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `party` VARCHAR(255),
  `start_date` DATE,
  `end_date` DATE,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `contracts` (`title`, `party`, `start_date`, `end_date`, `notes`) VALUES
('Sample Contract', 'Acme Corp', '2025-01-01', '2025-12-31', 'Seeded sample');
