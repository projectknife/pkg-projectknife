CREATE TABLE IF NOT EXISTS `#__pk_extensions` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `admin_view` varchar(255) NOT NULL DEFAULT 'default',
  `ordering` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;