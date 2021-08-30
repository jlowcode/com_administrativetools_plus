CREATE TABLE IF NOT EXISTS `#__fabrik_pkgs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` TEXT,
  `file` TEXT,
  `record` INT(255) DEFAULT NULL,
  `date_time` datetime DEFAULT NULL,
  `users_id` INT(11) DEFAULT NULL,
  `params` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__fabrik_harvesting` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`repository` text DEFAULT NULL,
`list` varchar(255) DEFAULT NULL,
`dowload_file` varchar(255) DEFAULT NULL,
`extract` varchar(255) DEFAULT NULL,
`syncronism` tinyint(2) DEFAULT NULL,
`field1` varchar(255) DEFAULT NULL,
`field2` varchar(255) DEFAULT NULL,
`status` tinyint(1) DEFAULT 0,
`date_creation` datetime DEFAULT NULL,
`date_execution` datetime DEFAULT NULL,
`users_id` int(11) DEFAULT NULL,
`record_last` varchar(255) DEFAULT NULL,
`map_header` mediumtext DEFAULT NULL,
`map_metadata` mediumtext DEFAULT NULL,
`line_num` int(11) DEFAULT 0,
`page_xml` int(11) DEFAULT 0,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;