-- Fabrik sync lists 1.0
CREATE TABLE IF NOT EXISTS `#__fabrik_sync_lists_connections` (
`id` int(11) NOT NULL AUTO_INCREMENT,
-- Fabrik sync lists 2.0
-- Id Task: 13
`urlApi` varchar(255) NOT NULL,
`keyApi` varchar(255) NOT NULL,
`secretApi` varchar(255) NOT NULL,
`host` varchar(255),
`user` varchar(255),
`name` varchar(255),
`prefix` varchar(255),
`password` varchar(255),
`port` int(11),
`checked_out_time` datetime,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;