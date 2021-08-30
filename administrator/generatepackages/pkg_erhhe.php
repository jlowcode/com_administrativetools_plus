<?php 
defined('_JEXEC') or die(); 

class Pkg_ErhheInstallerScript { 
protected $name = 'erhhe'; 
protected $packageName = 'pkg_erhhe'; 
protected $componentName = 'com_administrativetools'; 
protected $minimumPHPVersion = '5.6.0'; 
protected $minimumJoomlaVersion = '3.8.0'; 
protected $maximumJoomlaVersion = '3.9.99'; 

public function preflight($type, $parent){ 
$sourcePath = $parent->getParent()->getPath('source'); 

$folder_path = pathinfo($_SERVER['SCRIPT_FILENAME']); 
$folder = $folder_path['dirname'] . '/manifests/packages/' . $this->name; 

if (!is_dir($folder)) { 
mkdir($folder, 0775, true); 
} 

copy($sourcePath. '/install.mysql.utf8.sql', $folder . '/install.mysql.utf8.sql'); 

copy($sourcePath. '/install.mysql1.utf8.sql', $folder . '/install.mysql1.utf8.sql'); 

return true; 
} 

public function postflight($type, $parent){ 
$db = JFactory::getDbo(); 
$query = $db->getQuery(true); 

$query->clear(); 
$query->update('#__extensions')->set('enabled = 1') 
->where('type = ' . $db->q('plugin') . ' AND (folder LIKE ' . $db->q('fabrik_%'), 'OR') 
->where('(folder=' . $db->q('system') . ' AND element = ' . $db->q('fabrik') . ')', 'OR') 
->where('(folder=' . $db->q('system') . ' AND element LIKE ' . $db->q('fabrik%') . ')', 'OR') 
->where('(folder=' . $db->q('content') . ' AND element = ' . $db->q('fabrik') . '))', 'OR'); 
$db->setQuery($query)->execute(); 

$folder_path = pathinfo($_SERVER['SCRIPT_FILENAME']); 
$folder = $folder_path['dirname'] . '/manifests/packages/' . $this->name; 

$folder_pack = $folder_path['dirname'] . '/manifests/packages/'; 

$files = array_diff(scandir($folder), array('.','..')); 

foreach ($files as $file) { 
(is_dir($folder . '/' . $file)) ? delTree($folder . '/' . $file) : unlink($folder . '/' . $file); 
} 

unlink($folder_pack . $this->packageName . '.php'); 

rmdir($folder); 

return true; 
} 

public function install($parent){ return true;} 

public function uninstall($parent){ return true;} 
}