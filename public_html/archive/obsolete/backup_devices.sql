-- Structure des tables pour R.E.Mobiles (devices & device_photos)

CREATE TABLE IF NOT EXISTS `devices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ref` varchar(32) NOT NULL UNIQUE,
  `client_name` varchar(150) NOT NULL,
  `client_phone` varchar(60) NOT NULL,
  `client_email` varchar(150) DEFAULT NULL,
  `client_address` varchar(255) DEFAULT NULL,
  `model` varchar(150) NOT NULL,
  `problem` text,
  `technician_name` varchar(150) DEFAULT NULL,
  `screen` tinyint(1) NOT NULL DEFAULT 0,
  `charge_port` tinyint(1) NOT NULL DEFAULT 0,
  `wifi` tinyint(1) NOT NULL DEFAULT 0,
  `network_tiny` tinyint(1) NOT NULL DEFAULT 0,
  `bluetooth` tinyint(1) NOT NULL DEFAULT 0,
  `audio` tinyint(1) NOT NULL DEFAULT 0,
  `micro` tinyint(1) NOT NULL DEFAULT 0,
  `battery` tinyint(1) NOT NULL DEFAULT 0,
  `other_checks` text,
  `status` varchar(40) NOT NULL DEFAULT 'Reçu',
  `price_repair` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_diagnostic` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `device_photos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `device_ref` varchar(32) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `device_ref_idx` (`device_ref`),
  CONSTRAINT `fk_photos_device` FOREIGN KEY (`device_ref`) REFERENCES `devices`(`ref`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
