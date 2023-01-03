DELETE FROM `#__content_types` WHERE (type_alias LIKE 'com_administrativetools.%');
DROP TABLE IF EXISTS `#__fabrik_pkgs`;
DROP TABLE IF EXISTS `#__fabrik_harvesting`;

-- Fabrik sync lists 1.0
DROP TABLE IF EXISTS `#__fabrik_sync_lists_connections`;