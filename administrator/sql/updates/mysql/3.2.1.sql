-- Fabrik sync lists 1.0
CREATE TABLE IF NOT EXISTS `#__fabrik_sync_lists_connections` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`host` varchar(255) NOT NULL,
`user` varchar(255) NOT NULL,
`name` varchar(255) NOT NULL,
`prefix` varchar(255) NOT NULL,
`password` varchar(255) NOT NULL,
`port` int(11) NOT NULL,
`checked_out_time` datetime NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;