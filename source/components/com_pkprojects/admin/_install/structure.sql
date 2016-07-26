CREATE TABLE IF NOT EXISTS `#__pk_projects` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `category_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(128) NOT NULL DEFAULT '',
  `alias` varchar(128) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `published` tinyint(3) NOT NULL DEFAULT '0',
  `access` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `access_inherit` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `progress` tinyint(3) NOT NULL DEFAULT '0',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `checked_out` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `start_date_inherit` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `start_date_task_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `due_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `due_date_inherit` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `due_date_task_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `duration` int(10) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_alias` (`category_id`,`alias`),
  KEY `idx_published` (`published`),
  KEY `idx_access` (`access`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_asset_id` (`asset_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__pk_project_users` (
  `project_id` int(10) NOT NULL DEFAULT '0',
  `user_id` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`project_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;