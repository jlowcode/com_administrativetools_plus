<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Administrativetools
 * @author     Hirlei Carlos Pereira de Araújo <prof.hirleicarlos@gmail.com>
 * @copyright  2020 Hirlei Carlos Pereira de Araújo
 * @license    GNU General Public. License versão 2 ou posterior; consulte o arquivo License. Txt
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

//BEGIN - Solved problem with menu
include_once (JPATH_ADMINISTRATOR . '/components/com_menus/models/item.php');
include_once (JPATH_ADMINISTRATOR . '/components/com_menus/tables/menu.php');
//END - Solved problem with menu

use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Session\session;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

/**
 * Tools list controller class.
 *
 * @since  1.6
 */
class AdministrativetoolsControllerTools extends \Joomla\CMS\MVC\Controller\AdminController
{
    protected $user;
    protected $rowId;
    protected $permissionLevel;
    protected $listaPrincipal;
    protected $suggestId;
    protected $suggestElementId;
    protected $suggestFormId;
    protected $suggestCond;
    protected $suggestCloned = false;
    protected $clones_info = array();
    protected $listsToExport = array();
    protected $tableNames = array();
    protected $elementsId = array();

    /**
     * Method to clone existing Tools
     *
     * @return void
     * @throws Exception
     * @since  1.6
     */
    public function duplicate()
    {
        session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        $pks = $this->input->post->get('cid', array(), 'array');

        try {
            if (empty($pks)) {
                throw new Exception(Text::_('COM_ADMINISTRATIVETOOLS_NO_ELEMENT_SELECTED'));
            }

            ArrayHelper::toInteger($pks);
            $model = $this->getModel();
            $model->duplicate($pks);
            $this->setMessage(Text::_('COM_ADMINISTRATIVETOOLS_ITEMS_SUCCESS_DUPLICATED'));
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
        }

        $this->setRedirect('index.php?option=com_administrativetools&view=tools');
    }

    /**
     * Proxy for getModel.
     *
     * @param string $name Optional. Model name
     * @param string $prefix Optional. Class prefix
     * @param array $config Optional. Configuration array for model
     * @return  object    The Model
     * @since    1.6
     */
    public function getModel($name = 'tool', $prefix = 'AdministrativetoolsModel', $config = array()): object
    {
        return parent::getModel($name, $prefix, array('ignore_request' => true));
    }

    /**
     * Method to save the submitted ordering values for records via AJAX.
     *
     * @return  void
     * @throws Exception
     * @since   3.0
     */
    public function saveOrderAjax()
    {
        $input = Factory::getApplication()->input;
        $pks = $input->post->get('cid', array(), 'array');
        $order = $input->post->get('order', array(), 'array');
        ArrayHelper::toInteger($pks);
        ArrayHelper::toInteger($order);
        $model = $this->getModel();
        $return = $model->saveorder($pks, $order);

        if ($return) {
            echo "1";
        }

        Factory::getApplication()->close();
    }

    /**
     * Method that will upload files into a folder on the server, named packagesupload.
     *
     * @return void
     * @throws Exception
     * @since  1.6
     */
    public function uploadFile()
    {
        $app = JFactory::getApplication();
        $folder_path = pathinfo($_SERVER['SCRIPT_FILENAME']);
        $folder = $folder_path['dirname'] . '/components/com_administrativetools/packagesupload';

        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
        }

        if (isset($_FILES['fileUpload']) && !empty($_FILES['fileUpload']['name'])) {
            $data = $_FILES["fileUpload"];

            foreach ($data["name"] as $key => $value) {
                if (!move_uploaded_file($data['tmp_name'][$key], $folder . '/' . $value)) {
                    $app->enqueueMessage(JText::_('COM_ADMINISTRATIVETOOLS_PACKAGES_UPLOAD_ERROR') . ' - ' . $value, 'error');
                } else {
                    $app->enqueueMessage(JText::_('COM_ADMINISTRATIVETOOLS_PACKAGES_UPLOAD_SUCCESS') . ' - ' . $value, 'message');
                }
            }
        } else {
            $app->enqueueMessage(JText::_('COM_ADMINISTRATIVETOOLS_PACKAGES_UPLOAD_FILE_ERROR'), 'error');
        }

        $this->setRedirect(JRoute::_('index.php?option=com_administrativetools&view=tools&tab=1', false)); //JUri::base() .
    }

    /**
     * Ajax method that will delete the file from the packagesupload folder.
     *
     * @return void
     * @throws Exception
     * @since  1.6
     */
    public function deleteFile()
    {
        $app = JFactory::getApplication();
        $file = $app->input->getString('name');
        $folder_path = pathinfo($_SERVER['SCRIPT_FILENAME']);
        $path = $folder_path['dirname'] . '/components/com_administrativetools/packagesupload/' . $file;

        if (unlink($path)) {
            echo '1';
        } else {
            echo '0';
        }

        $app->close();
    }

    /**
     * Management method for creating package files (zip, php, xml and sql).
     *
     * @return void
     * @throws DOMException
     * @since  1.6
     */
    public function generatePackage()
    {
        date_default_timezone_set('America/Sao_Paulo');
        $app = JFactory::getApplication();
        $files = array();

        $nm_text = str_replace(' ', '', strtolower($app->input->getString('name', 'pitt')));
        $data['name'] = $this->utf8_strtr($nm_text);

        $data['record'] = $app->input->getInt('recordDB');
        $data['file'] = $app->input->get('file', null, 'ARRAY');
        $data['file'][] = 'date.zip';

        $folder_path = pathinfo($_SERVER['SCRIPT_FILENAME']);
        $folder = $folder_path['dirname'] . '/components/com_administrativetools/generatepackages';
        $nm_package = 'pkg_' . $data['name'] . '-' . date("Y-m-d_H-i-s");
        $folder_package = $folder_path['dirname'] . '/components/com_administrativetools/generatepackages/' . $nm_package;
        $folder_file = $folder_path['dirname'] . '/components/com_administrativetools/packagesupload';

        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
        }

        $zip = new ZipArchive;
        $zip->open($folder_package . '.zip', ZipArchive::CREATE);

        foreach ($data['file'] as $value) {
            $zip->addFile($folder_file . '/' . $value, $value);
            $files['files'][] = $value;
        }

        $file_php = $this->createFileScriptPhp($data['name'], $folder);

        $nm_sql = 'install.mysql.utf8.sql';
        $nm_sql_list = 'install.mysql1.utf8.sql';

        $this->createFileSqlDefault($nm_sql, $folder);
        $this->createFileSqlListJoin($nm_sql_list, $data['record'], $folder);
        $this->createXML($data, $folder);
        $zip->addFile($file_php, 'pkg_' . $data['name'] . '.php');
        $zip->addFile($folder . '/' . $nm_sql, $nm_sql);
        $zip->addFile($folder . '/' . $nm_sql_list, $nm_sql_list);
        $zip->addFile($folder . '/pkg_' . $data['name'] . '.xml', 'pkg_' . $data['name'] . '.xml');

        $zip->close();

        unlink($file_php);
        unlink($folder . '/' . $nm_sql);
        unlink($folder . '/' . $nm_sql_list);
        unlink($folder . '/pkg_' . $data['name'] . '.xml');

        $this->insertPackagesDB($data['name'], $nm_package . '.zip', $data['record'], $files);
        $app->enqueueMessage(FText::_('COM_ADMINISTRATIVETOOLS_PACKAGES_CONTROLLER_GENERATEPACKATE_SUCCESS') . ' - ' . $nm_package, 'message');
        $this->setRedirect(JRoute::_('index.php?option=com_administrativetools&view=tools&tab=1', false));
    }

    /**
     * Method that removes all special characters and accentuation.
     *
     * @param $str
     * @return array|string|string[]
     * @since  1.6
     */
    public function utf8_strtr($str)
    {
        $comAcentos = array('à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ü', 'ú',
            'ÿ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'O', 'Ù', 'Ü', 'Ú', '/',
            '-', '_', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
        $semAcentos = array('a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u',
            'y', 'A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O', 'O', '0', 'U', 'U', 'U', '',
            '', '', '', '', '', '', '', '', '', '', '', '');

        return str_replace($comAcentos, $semAcentos, $str);
    }

    /**
     * Method for creating the joomla package installation configuration xml file.
     *
     * @param $data
     * @param $folder
     * @return void
     * @throws DOMException
     * @since  1.6
     */
    private function createXML($data, $folder)
    {
        date_default_timezone_set('America/Sao_Paulo');
        $user = JFactory::getUser();
        $xml = new DOMDocument("1.0", "UTF-8");

        $xml->formatOutput = true;
        $xml->preserveWhiteSpace = false;

        $extension = $xml->createElement('extension');

        $ar_att = array('version' => '3.9', 'type' => 'package', 'method' => 'upgrade');
        $ar_type = array('com' => 'component', 'mod' => 'module', 'plg' => 'plugin');

        foreach ($ar_att as $key => $value) {
            $extension_att = $xml->createAttribute($key);
            $extension_att->value = $value;
            $extension->appendChild($extension_att);
        }

        $extension->appendChild($xml->createElement('name', ucfirst($data['name'] . FText::_('COM_ADMINISTRATIVETOOLS_PACKAGES_XML_NAME'))));
        $extension->appendChild($xml->createElement('author', $user->name));
        $extension->appendChild($xml->createElement('creationDate', date('Y-m-d')));
        $extension->appendChild($xml->createElement('packagename', $data['name']));
        $extension->appendChild($xml->createElement('version', '3.9'));
        $extension->appendChild($xml->createElement('url', FText::_('COM_ADMINISTRATIVETOOLS_PACKAGES_XML_URL')));
        $extension->appendChild($xml->createElement('packager', FText::_('COM_ADMINISTRATIVETOOLS_PACKAGES_XML_PACKAGER')));
        $extension->appendChild($xml->createElement('packagerurl', FText::_('COM_ADMINISTRATIVETOOLS_PACKAGES_XML_PACKAGER_URL')));
        $extension->appendChild($xml->createElement('copyright', FText::_('COM_ADMINISTRATIVETOOLS_PACKAGES_XML_COPYRIGHT')));
        $extension->appendChild($xml->createElement('license', FText::_('COM_ADMINISTRATIVETOOLS_PACKAGES_XML_LICENSE')));
        $extension->appendChild($xml->createElement('description', FText::_('COM_ADMINISTRATIVETOOLS_PACKAGES_XML_DESCRIPTION')));

        $files = $xml->createElement('files');

        foreach ($data['file'] as $value) {
            $ar_file = explode('_', $value);
            $file = $xml->createElement('file', $value);

            $file_type = $xml->createAttribute('type');
            $file_type->value = $ar_type[$ar_file[0]];
            $file->appendChild($file_type);

            $file_id = $xml->createAttribute('id');

            if ($ar_file[0] === 'plg') {
                if ($ar_file[2] === 'system') {
                    $file_group = $xml->createAttribute('group');
                    $file_group->value = 'system';
                    $file->appendChild($file_group);

                    $file_id->value = $ar_file[1];
                } elseif ($ar_file[2] === 'schedule') {
                    $file_group = $xml->createAttribute('group');
                    $file_group->value = 'system';
                    $file->appendChild($file_group);

                    $file_id->value = 'fabrikcron';
                } elseif ($ar_file[2] === 'content') {
                    $file_group = $xml->createAttribute('group');
                    $file_group->value = 'content';
                    $file->appendChild($file_group);

                    $file_id->value = $ar_file[1];
                } else {
                    $file_group = $xml->createAttribute('group');
                    $file_group->value = $ar_file[1] . '_' . $ar_file[2];
                    $file->appendChild($file_group);

                    $file_id->value = $ar_file[3];
                }
            } elseif ($ar_file[0] === 'mod') {
                $file_group = $xml->createAttribute('client');

                if ($ar_file[2] === 'admin') {
                    $file_group->value = $ar_file[2];
                    $file_id->value = $ar_file[0] . '_' . $ar_file[1] . '_' . $ar_file[3];
                } else {
                    $file_group->value = 'site';
                    $file_id->value = $ar_file[0] . '_' . $ar_file[1] . '_' . $ar_file[2];
                }

                $file->appendChild($file_group);
            } elseif ($ar_file[0] === 'com') {
                $file_id->value = $ar_file[0] . '_' . $ar_file[1];
            }

            $file->appendChild($file_id);
            $files->appendChild($file);
        }

        $extension->appendChild($files);
        $install = $xml->createElement('install');
        $sql = $xml->createElement('sql');

        $sqli_file = $xml->createElement('file', 'install.mysql.utf8.sql');
        $sqli_file1 = $xml->createAttribute('charset');
        $sqli_file1->value = 'utf8';
        $sqli_file2 = $xml->createAttribute('driver');
        $sqli_file2->value = 'mysqli';
        $sqli_file->appendChild($sqli_file1);
        $sqli_file->appendChild($sqli_file2);

        $sql_file = $xml->createElement('file', 'install.mysql1.utf8.sql');
        $sql_file1 = $xml->createAttribute('charset');
        $sql_file1->value = 'utf8';
        $sql_file2 = $xml->createAttribute('driver');
        $sql_file2->value = 'mysqli';
        $sql_file->appendChild($sql_file1);
        $sql_file->appendChild($sql_file2);

        $sql->appendChild($sqli_file);
        $sql->appendChild($sql_file);

        $install->appendChild($sql);
        $extension->appendChild($install);
        $extension->appendChild($xml->createElement('scriptfile', 'pkg_' . $data['name'] . '.php'));

        $xml->appendChild($extension);
        $xml->save($folder . '/pkg_' . $data['name'] . '.xml');
    }

    /**
     * Method that enters package information in the database
     *
     * @param $name
     * @param $file
     * @param $record
     * @param $params
     * @since  1.6
     */
    private function insertPackagesDB($name, $file, $record, $params)
    {
        date_default_timezone_set('America/Sao_Paulo');
        $db = JFactory::getDbo();
        $user = JFactory::getUser();

        $columns = array('name', 'file', 'record', 'date_time', 'users_id', 'params');
        $values = array($db->quote($name), $db->quote($file), $record, $db->quote(date("Y-m-d H:i:s")),
            $user->id, $db->quote(json_encode($params)));

        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__fabrik_pkgs'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));

        $db->setQuery($query);
        $db->execute();
    }

    /**
     * Ajax method that will delete the file from the generatepackage folder and the fabrik_pkgs database information.
     *
     * @throws Exception
     * @since  1.6
     */
    public function deletePackage()
    {
        $folder_path = pathinfo($_SERVER['SCRIPT_FILENAME']);
        $folder = $folder_path['dirname'] . '/components/com_administrativetools/generatepackages';

        $app = JFactory::getApplication();
        $db = JFactory::getDbo();

        $id = $app->input->getInt('id');
        $file = $app->input->getString('file');

        try {
            $db->transactionStart();

            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__fabrik_pkgs'))
                ->where($db->quoteName('id') . ' = ' . $id);

            $db->setQuery($query);
            $db->execute();

            $db->transactionCommit();

            unlink($folder . '/' . $file);

            echo '1';
        } catch (Exception $exc) {
            $db->transactionRollback();

            echo '0';
        }

        $app->close();
    }

    /**
     * Method of creating SQL file with all structures and information from the Factory defalult database and changing table names to the default
     * joomla that is dbprefix extensions.
     *
     * @param $nm_sql
     * @param $folder
     * @since  1.6
     */
    private function createFileSqlDefault($nm_sql, $folder)
    {
        $mysql_paht = $_SERVER['MYSQL_HOME'];
        $config = JFactory::getConfig();
        $app = JFactory::getApplication();

        $user = $config->get('user');
        $pass = (string)$config->get('password');
        $host = $config->get('host');
        $database = $config->get('db');
        $dbprefix = $config->get('dbprefix');

        $dir = $folder . '/' . $nm_sql;

        $table = $this->tableBDFabrikDefault();

        $joomlaTables = $app->input->get('joomlaTables');
        $textJoomlaTables = '';
        if (!empty($joomlaTables)) {
            foreach ($joomlaTables as $key => $value) {
                if ($key === 0) {
                    $textJoomlaTables .= $value;
                } else {
                    $textJoomlaTables .= ' ' . $value;
                }
            }
            $table .= ' ' . $textJoomlaTables;
        }

        if ($mysql_paht === NULL) {
            exec("mysqldump --user={$user} --password={$pass} --host={$host} {$database} {$table} --skip-comments --skip-add-drop-table --result-file={$dir} 2>&1", $output);
        } else {
            exec("{$mysql_paht}/mysqldump --user={$user} --password={$pass} --host={$host} {$database} {$table} --skip-comments --skip-add-drop-table --result-file={$dir} 2>&1", $output);
        }

        $text_file = file_get_contents($dir);

        $copy_text = str_replace($dbprefix, "#__", $text_file);

        $file = fopen($dir, 'w');
        fwrite($file, $copy_text);
        fclose($file);

        exec("sed -i 's/CREATE TABLE/CREATE TABLE IF NOT EXISTS/g' {$dir}");
    }

    /**
     * Method to take the names of Fabrik's default tables and string them so you can create the SQL file of those tables.
     *
     * @return string
     * @since  1.6
     */
    private function tableBDFabrikDefault()
    {
        $db = JFactory::getDbo();
        $config = JFactory::getConfig();

        $dbprefix = $config->get('dbprefix');
        $database = $config->get('db');

        $sql_show = "SHOW TABLES FROM {$db->qn($database)} LIKE '{$dbprefix}fabrik%'";
        $db->setQuery($sql_show);

        $ar_show = $db->loadRowList();

        $text = '';

        foreach ($ar_show as $key => $value) {
            if (($value[0] !== $dbprefix . 'fabrik_pkgs') && ($value[0] !== $dbprefix . 'fabrik_connections')) {
                if ($key === 0) {
                    $text .= $value[0];
                } else {
                    $text .= ' ' . $value[0];
                }
            }
        }

        return $text;
    }

    /**
     * Method for creating SQL file from Fabrik's lists and join, depending on the user's choice if it will be only with structures or
     * structures / information.
     *
     * @param $nm_sql
     * @param $record
     * @param $folder
     * @since  1.6
     */
    private function createFileSqlListJoin($nm_sql, $record, $folder)
    {
        $mysql_paht = $_SERVER['MYSQL_HOME'];
        $config = JFactory::getConfig();

        $user = $config->get('user');
        $pass = $config->get('password');
        $host = $config->get('host');
        $database = $config->get('db');
        $dbprefix = $config->get('dbprefix');

        $dir = $folder . '/' . $nm_sql;

        $table = $this->tableBDFabrikListJoin();

        if ($mysql_paht === NULL) {
            if ($record === 1) {
                exec("mysqldump --user={$user} --password={$pass} --host={$host} {$database} {$table} --skip-comments --skip-add-drop-table --result-file={$dir} 2>&1", $output);
            } else {
                exec("mysqldump --user={$user} --password={$pass} --host={$host} {$database} {$table} -d --skip-comments --skip-add-drop-table --result-file={$dir} 2>&1", $output);
            }
        } else {
            if ($record === 1) {
                exec("{$mysql_paht}/mysqldump --user={$user} --password={$pass} --host={$host} {$database} {$table} --skip-comments --skip-add-drop-table --result-file={$dir} 2>&1", $output);
            } else {
                exec("{$mysql_paht}/mysqldump --user={$user} --password={$pass} --host={$host} {$database} {$table} -d --skip-comments --skip-add-drop-table --result-file={$dir} 2>&1", $output);
            }
        }

        $text_file = file_get_contents($dir);

        $copy_text = str_replace($dbprefix, "#__", $text_file);

        $file = fopen($dir, 'w');
        fwrite($file, $copy_text);
        fclose($file);

        exec("sed -i 's/CREATE TABLE/CREATE TABLE IF NOT EXISTS/g' {$dir}");
    }

    /**
     * Method for taking Fabrik list and join names and sequencing them so you can create the SQL file for those tables.
     *
     * @return string
     * @since  1.6
     */
    private function tableBDFabrikListJoin(): string
    {
        $db = JFactory::getDbo();

        $sql = "SELECT DISTINCT list.db_table_name
                FROM #__fabrik_lists AS list ;";

        $db->setQuery($sql);

        $result = $db->loadObjectList();

        $text = '';
        $arTable = '';

        if (count($result) > 0) {
            foreach ($result as $key => $value) {
                $query = "SHOW TABLES LIKE '{$value->db_table_name}'";
                $db->setQuery($query);
                $exist = $db->loadRowList();
                if (!empty($exist)) {
                    if ($key == 0) {
                        $text .= $value->db_table_name;
                        $arTable .= "'{$value->db_table_name}'";
                    } else {
                        $text .= ' ' . $value->db_table_name;
                        $arTable .= ", '{$value->db_table_name}'";
                    }
                }
            }

            $sql1 = "SELECT DISTINCT joins.table_join
                    FROM #__fabrik_joins AS joins
                    WHERE joins.list_id <> 0 AND
                    joins.list_id IS NOT NULL AND
                    joins.table_join NOT IN ($arTable) ;";

            $db->setQuery($sql1);

            $result1 = $db->loadObjectList();

            if (count($result1) > 0) {
                foreach ($result1 as $value1) {
                    $query = "SHOW TABLES LIKE '$value1->table_join'";
                    $db->setQuery($query);
                    $exist = $db->loadRowList();
                    if (!empty($exist)) {
                        $text .= ' ' . $value1->table_join;
                    }
                }
            }
        }

        return $text;
    }

    /**
     * Method for creating extra script file with business rules to execute when the package is installed in joomla.
     *
     * @param $name
     * @param $folder
     * @return string
     * @since  1.6
     */
    private function createFileScriptPhp($name, $folder)
    {
        $file_php = 'pkg_' . $name . '.php';

        $nm_file = ucfirst($name);

        $script_php = "<?php \n";

        $script_php .= "defined('_JEXEC') or die(); \n\n";

        $script_php .= "class Pkg_{$nm_file}InstallerScript { \n";

        $script_php .= "protected \$name = '{$name}'; \n";
        $script_php .= "protected \$packageName = 'pkg_{$name}'; \n";
        $script_php .= "protected \$componentName = 'com_administrativetools'; \n";
        $script_php .= "protected \$minimumPHPVersion = '5.6.0'; \n";
        $script_php .= "protected \$minimumJoomlaVersion = '3.8.0'; \n";
        $script_php .= "protected \$maximumJoomlaVersion = '3.9.99'; \n\n";

        $script_php .= "public function preflight(\$type, \$parent){ \n";
        $script_php .= "\$sourcePath = \$parent->getParent()->getPath('source'); \n\n";
        $script_php .= "\$folder_path = pathinfo(\$_SERVER['SCRIPT_FILENAME']); \n";
        $script_php .= "\$folder = \$folder_path['dirname'] . '/manifests/packages/' . \$this->name; \n\n";
        $script_php .= "if (!is_dir(\$folder)) { \n";
        $script_php .= "mkdir(\$folder, 0775, true); \n";
        $script_php .= "} \n\n";
        $script_php .= "copy(\$sourcePath. '/install.mysql.utf8.sql', \$folder . '/install.mysql.utf8.sql'); \n\n";
        $script_php .= "copy(\$sourcePath. '/install.mysql1.utf8.sql', \$folder . '/install.mysql1.utf8.sql'); \n\n";
        $script_php .= "return true; \n";
        $script_php .= "} \n\n";

        $script_php .= "public function postflight(\$type, \$parent){ \n";
        $script_php .= "\$db = JFactory::getDbo(); \n";
        $script_php .= "\$query = \$db->getQuery(true); \n\n";
        $script_php .= "\$query->clear(); \n";
        $script_php .= "\$query->update('#__extensions')->set('enabled = 1') \n";
        $script_php .= "->where('type = ' . \$db->q('plugin') . ' AND (folder LIKE ' . \$db->q('fabrik_%'), 'OR') \n";
        $script_php .= "->where('(folder=' . \$db->q('system') . ' AND element = ' . \$db->q('fabrik') . ')', 'OR') \n";
        $script_php .= "->where('(folder=' . \$db->q('system') . ' AND element LIKE ' . \$db->q('fabrik%') . ')', 'OR') \n";
        $script_php .= "->where('(folder=' . \$db->q('content') . ' AND element = ' . \$db->q('fabrik') . '))', 'OR'); \n";
        $script_php .= "\$db->setQuery(\$query)->execute(); \n\n";
        $script_php .= "\$folder_path = pathinfo(\$_SERVER['SCRIPT_FILENAME']); \n";
        $script_php .= "\$folder = \$folder_path['dirname'] . '/manifests/packages/' . \$this->name; \n\n";
        $script_php .= "\$folder_pack = \$folder_path['dirname'] . '/manifests/packages/'; \n\n";
        $script_php .= "\$files = array_diff(scandir(\$folder), array('.','..')); \n\n";
        $script_php .= "foreach (\$files as \$file) { \n";
        $script_php .= "(is_dir(\$folder . '/' . \$file)) ? delTree(\$folder . '/' . \$file) : unlink(\$folder . '/' . \$file); \n";
        $script_php .= "} \n\n";
        $script_php .= "unlink(\$folder_pack . \$this->packageName . '.php'); \n\n";
        $script_php .= "rmdir(\$folder); \n\n";
        $script_php .= "return true; \n";
        $script_php .= "} \n\n";

        $script_php .= "public function install(\$parent){ return true;} \n\n";

        $script_php .= "public function uninstall(\$parent){ return true;} \n";

        $script_php .= '}';

        $arquivo = fopen($folder . '/' . $file_php, 'w');
        fwrite($arquivo, $script_php);
        fclose($arquivo);

        return $folder . '/' . $file_php;
    }

    /**
     * ListElement method responsible for listing all list elements that have been chosen in the template.
     *
     * @throws Exception
     * @since version
     */
    public function listElement()
    {
        $app = JFactory::getApplication();
        $db = JFactory::getDbo();

        $id = $app->input->getInt("idList");

        $sql = "SELECT
                element.id,
                element.label,
                element.name,
                list.db_table_name AS `table`,
                element.`plugin`,
                element.params,
                list.params as paramList
                FROM #__fabrik_elements AS element
                LEFT JOIN #__fabrik_formgroup AS fgroup ON element.group_id = fgroup.group_id
                LEFT JOIN #__fabrik_lists AS list ON fgroup.form_id = list.form_id
                WHERE
                list.published = 1 AND
                fgroup.form_id = $id
                ORDER BY
                element.label ASC;";

        $db->setQuery($sql);

        $list = $db->loadObjectList();

        if (count($list) > 0) {
            foreach ($list as $key => $value) {
                $list[$key]->params = json_decode($value->params);
                $list[$key]->paramList = json_decode($value->paramList);
            }

            echo json_encode($list);
        } else {
            echo '0';
        }

        $app->close();
    }

    /**
     * Method that will perform the transformation of elements for each business rule.
     *
     * @since version
     */
    public function rumTransformationTool()
    {
        $app = JFactory::getApplication();

        $id_form = $app->input->getInt("listTrans", 0);
        $id_source = $app->input->getInt("elementSourceTrans", 0);
        $id_target = $app->input->getInt("elementDestTrans", 0);
        $id_type = $app->input->getInt("typeTrans", 0);
        $delimiter = $app->input->getString("delimiterTransf", null);

        $joinModelSource = JModelLegacy::getInstance('Join', 'FabrikFEModel');
        $joinModelTarget = JModelLegacy::getInstance('Join', 'FabrikFEModel');

        $tableSource = $this->tableSource($id_form);

        $data_source = $this->elementSourceTarget($id_form, $id_source, $id_target);

        foreach ($data_source as $value) {
            if ($value->id == $id_source) {
                $source['data'] = $value;
                $source['params'] = json_decode($value->params);
            } elseif ($value->id == $id_target) {
                $target['data'] = $value;
                $target['params'] = json_decode($value->params);
            }
        }

        $ar_danial = array(5 => 5, 6 => 6);

        if (!array_key_exists($id_type, $ar_danial)) {
            $data_tabela = $this->dataTableSource($tableSource->db_table_name, $source['data']->name, $target['data']->name);
        }

        switch ($id_type) {
            case 1:
                $tableTaget = $this->tableTaget($target['params']->join_db_name);

                $data_target = $this->elementTableTarget($target['params']->join_db_name, $target['params']->join_val_col_synchronism, $tableTaget->id);
                $param_elem_tb_target = json_decode($data_target->params);

                if (($target['data']->plugin === 'databasejoin') && (strlen($target['params']->join_val_col_synchronism) === 0) && ($target['params']->database_join_display_type === 'dropdown')) {
                    if (count($data_tabela) !== 0) {
                        foreach ($data_tabela as $value) {
                            $exist_data_target = $this->existTargetTableData($target['params']->join_db_name, $target['params']->join_val_column, $value[$source['data']->name]);

                            if (count($exist_data_target) === 0) {
                                $result_sql = $this->insertIntoTargetAndChargeSourceTable($target['params']->join_db_name, $target['params']->join_val_column, $value[$source['data']->name], $tableSource->db_table_name, $value['id'], $target['data']->name);
                            } else {
                                if ($exist_data_target[$target['params']->join_key_column] !== $value[$target['data']->name]) {
                                    $result_sql = $this->updateDataTableSource($tableSource->db_table_name, $exist_data_target[$target['params']->join_key_column], $value['id'], $target['data']->name);
                                }
                            }
                        }
                    }
                } elseif (($target['data']->plugin === 'databasejoin') && (strlen($target['params']->join_val_col_synchronism) === 0) &&
                    (($target['params']->database_join_display_type === 'multilist') || ($target['params']->database_join_display_type === 'checkbox'))) {
                    $this->join_source = $joinModelSource->getJoinFromKey('element_id', $target['data']->id);

                    if (count($data_tabela) !== 0) {
                        foreach ($data_tabela as $value) {
                            $exist_data_target = $this->existTargetTableData($target['params']->join_db_name, $target['params']->join_val_column, $value[$source['data']->name]);

                            if (count($exist_data_target) === 0) {
                                $result_sql = $this->insertInTargetAndSourceTable($target['params']->join_db_name, $target['params']->join_val_column, $value[$source['data']->name], $this->join_source, $value['id']);
                            } else {
                                $result_sql = $this->insertMultipleSourceTable($this->join_source, $value['id'], $exist_data_target[$target['params']->join_key_column]);
                            }
                        }
                    }
                } elseif (($target['data']->plugin === 'databasejoin') && (strlen($target['params']->join_val_col_synchronism) !== 0) &&
                    (($target['params']->database_join_display_type === 'multilist') || ($target['params']->database_join_display_type === 'checkbox')) &&
                    ($param_elem_tb_target->database_join_display_type === 'dropdown')) {
                    $this->join_source = $joinModelSource->getJoinFromKey('element_id', $target['data']->id);

                    if (count($data_tabela) !== 0) {
                        foreach ($data_tabela as $value) {
                            $exist_data_target = $this->existTargetTableData($target['params']->join_db_name, $target['params']->join_val_column, $value[$source['data']->name]);

                            if (count($exist_data_target) === 0) {
                                $result_sql = $this->insertInTargetDropAndSourceMultTable($target['params']->join_db_name, $target['params']->join_val_column, $value[$source['data']->name], $this->join_source, $value['id'], $target['params']->join_val_col_synchronism);
                            } else {
                                $result_sql = $this->insertMultipleSourceTable($this->join_source, $value['id'], $exist_data_target[$target['params']->join_key_column]);
                            }
                        }
                    }
                } elseif (($target['data']->plugin === 'databasejoin') && (strlen($target['params']->join_val_col_synchronism) !== 0) && ($target['params']->database_join_display_type === 'dropdown') &&
                    (($param_elem_tb_target->database_join_display_type === 'multilist') || ($param_elem_tb_target->database_join_display_type === 'checkbox'))) {
                    $this->join_target = $joinModelTarget->getJoinFromKey('element_id', $data_target->id);

                    if (count($data_tabela) !== 0) {
                        foreach ($data_tabela as $value) {
                            $exist_data_target = $this->existTargetTableData($target['params']->join_db_name, $target['params']->join_val_column, $value[$source['data']->name]);

                            if (count($exist_data_target) === 0) {
                                $result_sql = $this->insertTargetChangesSourceInsertTargetRepeatTable($target['params']->join_db_name, $target['params']->join_val_column, $value[$source['data']->name], $tableSource->db_table_name, $value['id'], $target['data']->name, $this->join_target);
                            } else {
                                $result_sql = $this->updateTableSourceDropdownInsertTableTargetRepeat($tableSource->db_table_name, $value['id'], $target['data']->name, $this->join_target, $exist_data_target[$target['params']->join_key_column]);
                            }
                        }
                    }
                } elseif (($target['data']->plugin === 'databasejoin') && (strlen($target['params']->join_val_col_synchronism) !== 0) &&
                    (($target['params']->database_join_display_type === 'multilist') || ($target['params']->database_join_display_type === 'checkbox')) &&
                    (($param_elem_tb_target->database_join_display_type === 'multilist') || ($param_elem_tb_target->database_join_display_type === 'checkbox'))) {
                    $this->join_source = $joinModelSource->getJoinFromKey('element_id', $target['data']->id);

                    $this->join_target = $joinModelTarget->getJoinFromKey('element_id', $data_target->id);

                    if (count($data_tabela) !== 0) {
                        foreach ($data_tabela as $value) {
                            $exist_data_target = $this->existTargetTableData($target['params']->join_db_name, $target['params']->join_val_column, $value[$source['data']->name]);

                            if (count($exist_data_target) === 0) {
                                $result_sql = $this->insertTargetTableInsertRepeatTargetTableInsertRepeatSourceTable($target['params']->join_db_name, $target['params']->join_val_column, $value[$source['data']->name], $this->join_source, $value['id'], $this->join_target);
                            } else {
                                $result_sql = $this->insertSourceRepeatTableInsertTargetRepeatTable($this->join_source, $value['id'], $exist_data_target[$target['params']->join_key_column], $this->join_target);
                            }
                        }
                    }
                }

                $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_0', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_1');
                break;
            case 2:
                $sub_opt_source = $target['params']->sub_options;

                if ($target['data']->plugin === 'dropdown') {
                    if (count($data_tabela) !== 0) {
                        foreach ($data_tabela as $value) {
                            $ar_string = explode($delimiter, $value[$source['data']->name]);
                            $text_dropdown = '';
                            $total = count($ar_string) - 1;

                            foreach ($ar_string as $key1 => $ar_value) {
                                if (!(in_array($ar_value, $sub_opt_source->sub_values)) && !(in_array($ar_value, $sub_opt_source->sub_labels))) {
                                    $sub_opt_source->sub_values[] = ucwords(strtolower($ar_value));
                                    $sub_opt_source->sub_labels[] = ucwords(strtolower($ar_value));
                                }

                                if (($total >= 1) && ($key1 < $total)) {
                                    $text_dropdown .= ucwords(strtolower($ar_value)) . "\",\"";
                                } elseif ($total === $key1) {
                                    $text_dropdown .= ucwords(strtolower($ar_value));
                                } elseif ($total == 0) {
                                    $text_dropdown = ucwords(strtolower($ar_value));
                                }
                            }

                            $num_exp = explode('","', $text_dropdown);

                            if (count($num_exp) !== 1) {
                                $result_text = "[\"" . $text_dropdown . "\"]";
                            } else {
                                $result_text = $text_dropdown;
                            }

                            $result_sql = $this->updateDataTableSourceUpdateTableElement($tableSource->db_table_name, $target['data']->name, $value['id'], $target['data']->id, $result_text, $target['params']);
                        }
                    }
                }

                $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_0', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_1');
                break;
            case 3:
                $tableTaget = $this->tableTaget($target['params']->join_db_name);
                $data_target = $this->elementTableTarget($target['params']->join_db_name, $target['params']->join_val_col_synchronism, $tableTaget->id);
                $param_elem_tb_target = json_decode($data_target->params);

                if (($target['data']->plugin === 'databasejoin') && (strlen($target['params']->join_val_col_synchronism) === 0) &&
                    (($target['params']->database_join_display_type === 'multilist') || ($target['params']->database_join_display_type === 'checkbox'))) {
                    $this->join_source = $joinModelSource->getJoinFromKey('element_id', $target['data']->id);

                    if (count($data_tabela) !== 0) {
                        foreach ($data_tabela as $value) {
                            $ar_exp = explode($delimiter, $value[$source['data']->name]);

                            foreach ($ar_exp as $ar_value) {
                                $exist_data_target = $this->existTargetTableData($target['params']->join_db_name, $target['params']->join_val_column, $ar_value);

                                if (count($exist_data_target) === 0) {
                                    $result_sql = $this->insertInTargetAndSourceTable($target['params']->join_db_name, $target['params']->join_val_column, $ar_value, $this->join_source, $value['id']);
                                } else {
                                    $result_sql = $this->insertMultipleSourceTable($this->join_source, $value['id'], $exist_data_target[$target['params']->join_key_column]);
                                }
                            }
                        }
                    }
                } elseif (($target['data']->plugin === 'databasejoin') && (strlen($target['params']->join_val_col_synchronism) !== 0) &&
                    (($target['params']->database_join_display_type === 'multilist') || ($target['params']->database_join_display_type === 'checkbox')) &&
                    (($param_elem_tb_target->database_join_display_type === 'multilist') || ($param_elem_tb_target->database_join_display_type === 'checkbox'))) {
                    $this->join_source = $joinModelSource->getJoinFromKey('element_id', $target['data']->id);

                    $this->join_target = $joinModelTarget->getJoinFromKey('element_id', $data_target->id);

                    if (count($data_tabela) !== 0) {
                        foreach ($data_tabela as $value) {
                            $ar_exp = explode($delimiter, $value[$source['data']->name]);

                            foreach ($ar_exp as $ar_value) {
                                $exist_data_target = $this->existTargetTableData($target['params']->join_db_name, $target['params']->join_val_column, $ar_value);

                                if (count($exist_data_target) === 0) {
                                    $result_sql = $this->insertTargetTableInsertRepeatTargetTableInsertRepeatSourceTable($target['params']->join_db_name, $target['params']->join_val_column, $ar_value, $this->join_source, $value['id'], $this->join_target);
                                } else {
                                    $result_sql = $this->insertSourceRepeatTableInsertTargetRepeatTable($this->join_source, $value['id'], $exist_data_target[$target['params']->join_key_column], $this->join_target);
                                }
                            }
                        }
                    }
                }

                $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_0', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_1');

                break;
            case 4:
                $tableTaget = $this->tableTaget($target['params']->join_db_name);

                $data_target = $this->elementTableTarget($target['params']->join_db_name, $target['params']->join_val_col_synchronism, $tableTaget->id);

                $param_elem_tb_target = json_decode($data_target->params);

                $ar_simbol = array('["', '"]');

                if (($target['data']->plugin === 'databasejoin') && (strlen($target['params']->join_val_col_synchronism) === 0) &&
                    (($target['params']->database_join_display_type === 'multilist') || ($target['params']->database_join_display_type === 'checkbox'))) {
                    $this->join_source = $joinModelSource->getJoinFromKey('element_id', $target['data']->id);

                    if (count($data_tabela) !== 0) {
                        foreach ($data_tabela as $value) {
                            $ar_exp = explode('","', str_replace($ar_simbol, "", $value[$source['data']->name]));

                            foreach ($ar_exp as $ar_value) {
                                $exist_data_target = $this->existTargetTableData($target['params']->join_db_name, $target['params']->join_val_column, $ar_value);

                                if (count($exist_data_target) === 0) {
                                    $result_sql = $this->insertInTargetAndSourceTable($target['params']->join_db_name, $target['params']->join_val_column, $ar_value, $this->join_source, $value['id']);
                                } else {
                                    $result_sql = $this->insertMultipleSourceTable($this->join_source, $value['id'], $exist_data_target[$target['params']->join_key_column]);
                                }
                            }
                        }
                    }
                } elseif (($target['data']->plugin === 'databasejoin') && (strlen($target['params']->join_val_col_synchronism) !== 0) &&
                    (($target['params']->database_join_display_type === 'multilist') || ($target['params']->database_join_display_type === 'checkbox')) &&
                    (($param_elem_tb_target->database_join_display_type === 'multilist') || ($param_elem_tb_target->database_join_display_type === 'checkbox'))) {
                    $this->join_source = $joinModelSource->getJoinFromKey('element_id', $target['data']->id);

                    $this->join_target = $joinModelTarget->getJoinFromKey('element_id', $data_target->id);

                    if (count($data_tabela) !== 0) {
                        foreach ($data_tabela as $value) {
                            $ar_exp = explode('","', str_replace($ar_simbol, "", $value[$source['data']->name]));

                            foreach ($ar_exp as $ar_value) {
                                $exist_data_target = $this->existTargetTableData($target['params']->join_db_name, $target['params']->join_val_column, $ar_value);

                                if (count($exist_data_target) === 0) {
                                    $result_sql = $this->insertTargetTableInsertRepeatTargetTableInsertRepeatSourceTable($target['params']->join_db_name, $target['params']->join_val_column, $ar_value, $this->join_source, $value['id'], $this->join_target);
                                } else {
                                    $result_sql = $this->insertSourceRepeatTableInsertTargetRepeatTable($this->join_source, $value['id'], $exist_data_target[$target['params']->join_key_column], $this->join_target);
                                }
                            }
                        }
                    }
                }
                $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_0', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_1');

                break;
            case 5:
                $update = $app->input->getString("updateDB", 'SET NULL');

                $delete = $app->input->getString("deleteDB", 'SET NULL');

                $tableTaget = $this->tableTaget($source['params']->join_db_name);

                $data_target = $this->elementTableTarget($source['params']->join_db_name, $source['params']->join_val_col_synchronism, $tableTaget->id);

                $param_elem_tb_target = json_decode($data_target->params);

                if (($source['data']->plugin === 'databasejoin') && (strlen($source['params']->join_val_col_synchronism) === 0) &&
                    (($source['params']->database_join_display_type === 'multilist') || ($source['params']->database_join_display_type === 'checkbox'))) {
                    $engine_source_bool = $this->sourceTableStructure($tableSource->db_table_name);

                    $this->join_source = $joinModelSource->getJoinFromKey('element_id', $source['data']->id);

                    $engine_join_bool = $this->joinTableStructure($this->join_source->table_join);

                    $engine_target_bool = $this->targetTableStructure($source['params']->join_db_name);

                    if ($engine_source_bool && $engine_join_bool && $engine_target_bool) {
                        $join_type_bool = $this->estructureChecksEqualityBetweenFields($source['params']->join_db_name, $this->join_source->table_join, $this->join_source->table_key);

                        if ($join_type_bool) {
                            $fk_exist1 = $this->checkIfForeignKeyExists($this->join_source->table_join, $tableSource->db_table_name, $this->join_source->table_join_key);
                            $fk_exist2 = $this->checkIfForeignKeyExists($this->join_source->table_join, $source['params']->join_db_name, $this->join_source->table_key);

                            if (($fk_exist1->forkey === '0') && ($fk_exist2->forkey === '0')) {
                                $result_sql = $this->alterTableCreateForeignKeyRelatedFields2($this->join_source->table_join, $tableSource->db_table_name, $this->join_source->table_join_key, $source['params']->join_db_name, $this->join_source->table_key, $update, $delete);

                                $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_2', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_1');
                            } else {
                                $result_sql = true;

                                $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_3', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_1');
                            }
                        }
                    }
                } elseif (($source['data']->plugin === 'databasejoin') && (strlen($source['params']->join_val_col_synchronism) === 0) && ($source['params']->database_join_display_type === 'dropdown')) {
                    $engine_source_bool = $this->sourceTableStructure($tableSource->db_table_name);

                    $engine_target_bool = $this->targetTableStructure($source['params']->join_db_name);

                    if ($engine_source_bool && $engine_target_bool) {
                        $fk_exist = $this->checkIfForeignKeyExists($tableSource->db_table_name, $source['params']->join_db_name, $source['data']->name);

                        if ($fk_exist->forkey === '0') {
                            $result_sql = $this->alterTableCreateForeignKeyRelatedFields1($tableSource->db_table_name, $source['params']->join_db_name, $source['data']->name, $update, $delete);

                            $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_2', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_1');
                        } else {
                            $result_sql = true;

                            $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_3', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_1');
                        }
                    }
                } elseif (($source['data']->plugin === 'databasejoin') && (strlen($source['params']->join_val_col_synchronism) !== 0) && ($source['params']->database_join_display_type === 'dropdown') &&
                    (($param_elem_tb_target->database_join_display_type === 'multilist') || ($param_elem_tb_target->database_join_display_type === 'checkbox'))) {

                    $engine_source_bool = $this->sourceTableStructure($tableSource->db_table_name);

                    $engine_target_bool = $this->targetTableStructure($source['params']->join_db_name);

                    $this->join_target = $joinModelTarget->getJoinFromKey('element_id', $data_target->id);

                    $engine_join_bool = $this->joinTableStructure($this->join_target->table_join);

                    if ($engine_source_bool && $engine_target_bool && $engine_join_bool) {
                        $join_type_bool = $this->estructureChecksEqualityBetweenFields($tableSource->db_table_name, $this->join_target->table_join, $this->join_target->table_key);

                        $type_bool = $this->estructureChecksEqualityBetweenFields($source['params']->join_db_name, $tableSource->db_table_name, $source['data']->name);

                        if ($join_type_bool & $type_bool) {
                            $fk_exist = $this->checkIfForeignKeyExists($tableSource->db_table_name, $source['params']->join_db_name, $source['data']->name);
                            $fk_exist1 = $this->checkIfForeignKeyExists($this->join_target->table_join, $tableSource->db_table_name, $this->join_target->table_join_key);
                            $fk_exist2 = $this->checkIfForeignKeyExists($this->join_target->table_join, $source['params']->join_db_name, $this->join_target->table_key);

                            if (($fk_exist->forkey === '0') && ($fk_exist1->forkey === '0') && ($fk_exist2->forkey === '0')) {
                                $result_sql = $this->alterTableCreateForeignKeyRelatedFields3($this->join_target->table_join, $source['params']->join_db_name, $this->join_target->table_key, $this->join_target->table_join_key, $tableSource->db_table_name, $source['data']->name, $update, $delete);

                                $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_2', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_1');
                            } else {
                                $result_sql = true;

                                $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_3', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_1');
                            }
                        }
                    }
                } elseif (($source['data']->plugin === 'databasejoin') && (strlen($source['params']->join_val_col_synchronism) !== 0) &&
                    (($source['params']->database_join_display_type === 'multilist') || ($source['params']->database_join_display_type === 'checkbox')) &&
                    ($param_elem_tb_target->database_join_display_type === 'dropdown')) {
                    $engine_source_bool = $this->sourceTableStructure($tableSource->db_table_name);

                    $this->join_source = $joinModelSource->getJoinFromKey('element_id', $source['data']->id);

                    $engine_join_bool = $this->joinTableStructure($this->join_source->table_join);

                    $engine_target_bool = $this->targetTableStructure($source['params']->join_db_name);

                    if ($engine_source_bool && $engine_target_bool && $engine_join_bool) {
                        $join_type_bool = $this->estructureChecksEqualityBetweenFields($source['params']->join_db_name, $this->join_source->table_join, $this->join_source->table_key);

                        $type_bool = $this->estructureChecksEqualityBetweenFields($tableSource->db_table_name, $source['params']->join_db_name, $source['params']->join_val_col_synchronism);

                        if ($join_type_bool && $type_bool) {
                            $fk_exist1 = $this->checkIfForeignKeyExists($this->join_source->table_join, $tableSource->db_table_name, $this->join_source->table_join_key);
                            $fk_exist2 = $this->checkIfForeignKeyExists($this->join_source->table_join, $source['params']->join_db_name, $this->join_source->table_key);
                            $fk_exist3 = $this->checkIfForeignKeyExists($source['params']->join_db_name, $tableSource->db_table_name, $source['params']->join_val_col_synchronism);

                            if (($fk_exist1->forkey === '0') && ($fk_exist2->forkey === '0') && ($fk_exist3->forkey === '0')) {
                                $result_sql = $this->alterTableCreateForeignKeyRelatedFields3($this->join_source->table_join, $tableSource->db_table_name, $source['data']->name, $this->join_source->table_join_key, $source['params']->join_db_name, $source['params']->join_val_col_synchronism, $update, $delete);

                                $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_2', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_1');
                            } else {
                                $result_sql = true;

                                $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_3', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_1');
                            }
                        }
                    }
                } elseif (($source['data']->plugin === 'databasejoin') && (strlen($source['params']->join_val_col_synchronism) !== 0) &&
                    (($source['params']->database_join_display_type === 'multilist') || ($source['params']->database_join_display_type === 'checkbox')) &&
                    (($param_elem_tb_target->database_join_display_type === 'multilist') || ($param_elem_tb_target->database_join_display_type === 'checkbox'))) {
                    $engine_source_bool = $this->sourceTableStructure($tableSource->db_table_name);

                    $this->join_source = $joinModelSource->getJoinFromKey('element_id', $source['data']->id);

                    $engine_join_bool = $this->joinTableStructure($this->join_source->table_join);

                    $engine_target_bool = $this->targetTableStructure($source['params']->join_db_name);

                    $this->join_target = $joinModelTarget->getJoinFromKey('element_id', $data_target->id);

                    $engine_join_target_bool = $this->joinTableStructure($this->join_target->table_join);

                    if ($engine_source_bool && $engine_join_bool && $engine_target_bool && $engine_join_target_bool) {
                        $join_type_source_bool = $this->estructureChecksEqualityBetweenFields($tableSource->db_table_name, $this->join_source->table_join, $this->join_source->table_key);

                        $join_type_target_bool = $this->estructureChecksEqualityBetweenFields($source['params']->join_db_name, $this->join_target->table_join, $this->join_target->table_key);

                        if ($join_type_source_bool && $join_type_target_bool) {
                            $fk_exist1 = $this->checkIfForeignKeyExists($this->join_source->table_join, $tableSource->db_table_name, $this->join_source->table_join_key);
                            $fk_exist2 = $this->checkIfForeignKeyExists($this->join_source->table_join, $source['params']->join_db_name, $this->join_source->table_key);
                            $fk_exist3 = $this->checkIfForeignKeyExists($this->join_target->table_join, $tableSource->db_table_name, $this->join_target->table_join_key);
                            $fk_exist4 = $this->checkIfForeignKeyExists($this->join_target->table_join, $source['params']->join_db_name, $this->join_target->table_key);

                            if (($fk_exist1->forkey === '0') && ($fk_exist2->forkey === '0') && ($fk_exist3->forkey === '0') && ($fk_exist4->forkey === '0')) {
                                $result_sql = $this->alterTableCreateForeignKeyRelatedFields4($this->join_source->table_join, $this->join_target->table_join, $tableSource->db_table_name, $source['params']->join_db_name, $this->join_target->table_key, $this->join_source->table_key, $update, $delete);
                                $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_2', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_1');
                            } else {
                                $result_sql = true;
                                $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_3', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_1');
                            }
                        }
                    }
                }

                break;
            case 6:
                $table_repeat = $app->input->getInt("tableRepeat", 0);

                $thumbs_crops = $app->input->getInt("thumbsCrops", 0);

                if (($table_repeat === 1) && ($thumbs_crops === 0)) {
                    if ($source['params']->ajax_upload === '1') {
                        $data_tabela = $this->dataTableSourceFieldSingle($tableSource->db_table_name, $source['data']->name);

                        $this->join_source = $joinModelSource->getJoinFromKey('element_id', $source['data']->id);

                        foreach ($data_tabela as $value) {
                            $text = str_replace('/', "\\", $value[$source['data']->name]);

                            $result_sql = $this->insertMultipleSourceTableFileupload($this->join_source, $value['id'], $text);

                            $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_6', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_7');
                        }
                    }
                } elseif (($table_repeat === 0) && ($thumbs_crops === 1)) {
                    if ($source['params']->ajax_upload === '0') {
                        $data_tabela = $this->dataTableSourceFieldSingle($tableSource->db_table_name, $source['data']->name);

                        foreach ($data_tabela as $value) {
                            $this->performChangeThumbsCrops($source['params'], $value[$source['data']->name]);
                        }
                    } elseif ($source['params']->ajax_upload === '1') {
                        $this->join_source = $joinModelSource->getJoinFromKey('element_id', $source['data']->id);

                        $data_tabela = $this->dataTableSourceFieldSingle($this->join_source->table_join, $this->join_source->table_key);

                        foreach ($data_tabela as $key => $value) {
                            $this->performChangeThumbsCrops($source['params'], $value[$source['data']->name], $key);
                        }
                    }

                    $message = $this->displayMessages(true, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_4', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_5');
                } elseif (($table_repeat === 1) && ($thumbs_crops === 1)) {
                    if ($source['params']->ajax_upload === '1') {
                        $data_tabela = $this->dataTableSourceFieldSingle($tableSource->db_table_name, $source['data']->name);

                        $this->join_source = $joinModelSource->getJoinFromKey('element_id', $source['data']->id);

                        foreach ($data_tabela as $value) {
                            $text = str_replace('/', "\\", $value[$source['data']->name]);

                            $result_sql = $this->insertMultipleSourceTableFileupload($this->join_source, $value['id'], $text);

                            $this->performChangeThumbsCrops($source['params'], $value[$source['data']->name]);

                            $message = $this->displayMessages($result_sql, 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_8', 'COM_ADMINISTRATIVETOOLS_MESSAGE_CONTROLLER_9');
                        }
                    }
                }
                break;
        }

        $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools&tab=2';
        $this->setRedirect($site_message, $message['message'], $message['type']);
    }

    /**
     * Method that validates to show error alert when it has no element type
     *
     * @param $sourse
     * @param $target
     * @param $type
     * @return string
     * @since version
     */
    public function validateErro($sourse, $target, $type)
    {
        $data = '';

        $data->validate = 0;

        if ($type === 0) {
            $data->message = FText::_('COM_ADMINISTRATIVETOOLS_MESSAGE_ALERT_ERRO_NO_FIELD_FOR_EXECUTION') . ' ' .
                FText::_('COM_ADMINISTRATIVETOOLS_ADMINISTRATIVETOOLS_SOURCE') . ' ' . $sourse . ' / ' .
                FText::_('COM_ADMINISTRATIVETOOLS_ADMINISTRATIVETOOLS_TARGET') . ' ' . $target;
        } else {
            $data->message = FText::_('COM_ADMINISTRATIVETOOLS_MESSAGE_ALERT_ERRO_NO_FIELD_FOR_EXECUTION_TYPE') . ' ' .
                FText::_('COM_ADMINISTRATIVETOOLS_ADMINISTRATIVETOOLS_SOURCE') . ' ' . $sourse . ' / ' .
                FText::_('COM_ADMINISTRATIVETOOLS_ADMINISTRATIVETOOLS_TARGET') . ' ' . $target;

            if (($sourse === 'field') && ($target === 'databasejoin')) {
                $data->field = 1;
            } elseif (($sourse === 'field') && ($target === 'dropdown')) {
                $data->field = 2;
            } elseif (($sourse === 'dropdown') && ($target === 'databasejoin')) {
                $data->field = 4;
            }
        }

        return $data;
    }

    /**
     * Method that creates an object with all data from the source table.
     *
     * @param $table
     * @param $field_source
     * @param $field_target
     *
     * @return mixed
     *
     * @since version
     */
    public function dataTableSource($table, $field_source, $field_target)
    {
        $db = JFactory::getDbo();

        try {
            $query = "SELECT
                `table`.id,
                `table`.{$field_source},
                `table`.{$field_target}
                FROM {$table} AS `table`;";

            $db->setQuery($query);

            return $db->loadAssocList();
        } catch (Exception $exc) {
            $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR5');
            $type_message = 'warning';
            $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools&tab=3';

            $this->setRedirect($site_message, $message, $type_message);
        }
    }

    /**
     * Method that will bring the data from the source list.
     *
     * @param $id_form
     *
     * @return mixed
     *
     * @since version
     */
    public function tableSource($id_form)
    {
        $db = JFactory::getDbo();

        try {
            $query = "SELECT * FROM #__fabrik_lists AS `table` WHERE table.published = 1 AND `table`.id = {$id_form};";

            $db->setQuery($query);

            return $db->loadObject();
        } catch (Exception $exc) {
            $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR5');
            $type_message = 'warning';
            $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools&tab=3';

            $this->setRedirect($site_message, $message, $type_message);
        }
    }

    /**
     * Method that will bring all data regarding the source and destination elements.
     *
     * @param $id_form
     * @param $id_source
     * @param $id_target
     *
     * @return mixed
     *
     * @since version
     */
    public function elementSourceTarget($id_form, $id_source, $id_target)
    {
        $db = JFactory::getDbo();

        try {
            $sql = "SELECT element.* FROM
                    #__fabrik_elements AS element
                    LEFT JOIN #__fabrik_formgroup AS fg ON element.group_id = fg.group_id
                    LEFT JOIN #__fabrik_lists AS list ON fg.form_id = list.form_id
                WHERE
                    list.published = 1 AND
                    list.form_id = {$id_form}  AND
                    element.id IN ({$id_source}, {$id_target})";

            $db->setQuery($sql);

            return $db->loadObjectList();
        } catch (Exception $exc) {
            $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR5');
            $type_message = 'warning';
            $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools&tab=3';

            $this->setRedirect($site_message, $message, $type_message);
        }
    }

    /**
     * Method that will check if the information already exists in the destination table.
     *
     * @param $table
     * @param $field
     * @param $search
     *
     * @return mixed
     *
     * @since version
     */
    public function existTargetTableData($table, $field, $search)
    {
        $db = JFactory::getDbo();

        try {
            $query = "SELECT
                `table`.id,
                `table`.{$field}
                FROM {$table} AS `table`
                WHERE
                `table`.{$field} LIKE '{$db->escape($search)}';";

            $db->setQuery($query);

            return $db->loadAssoc();
        } catch (Exception $exc) {
            $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR5');
            $type_message = 'warning';
            $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools&tab=3';

            $this->setRedirect($site_message, $message, $type_message);
        }
    }

    /**
     * Method executed when the information in the destination database exists then it changes the source.
     *
     * @param $table
     * @param $id_target
     * @param $id_source
     * @param $field_target
     *
     * @return bool
     *
     * @since version
     */
    public function updateDataTableSource($table, $id_target, $id_source, $field_target)
    {
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $query = "UPDATE {$table} SET {$field_target} = {$id_target} WHERE id = {$id_source}";

            $db->setQuery($query);

            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method executed when there is no information in the destination database then it enters in destination and changes the source.
     *
     * @param $table_taget
     * @param $field_target
     * @param $data_target
     * @param $table_source
     * @param $id_source
     * @param $field_source
     *
     * @return bool
     *
     * @since version
     */
    public function insertIntoTargetAndChargeSourceTable($table_taget, $field_target, $data_target, $table_source, $id_source, $field_source)
    {
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$table_taget}` (`{$field_target}`) VALUES ('{$db->escape($data_target)}');";
            $db->setQuery($query);
            $db->execute();
            $id = $db->insertid();

            $query1 = "UPDATE {$table_source} SET {$field_source} = {$id} WHERE id = {$id_source}";
            $db->setQuery($query1);
            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method to insert into source table that is databasejoin multSelect without sync.
     *
     * @param $join_source
     * @param $id_source
     * @param $id_target
     *
     * @return bool
     *
     * @since version
     */
    public function insertMultipleSourceTable($join_source, $id_source, $id_target)
    {
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$join_source->table_join}` (`{$join_source->table_join_key}`, `{$join_source->table_key}`) VALUES ({$id_source},{$id_target})";
            $db->setQuery($query);
            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method for inserting into target table and source table.
     *
     * @param $table_taget
     * @param $field_target
     * @param $data_target
     * @param $join_source
     * @param $id_source
     *
     * @return bool
     *
     * @since version
     */
    public function insertInTargetAndSourceTable($table_taget, $field_target, $data_target, $join_source, $id_source)
    {
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$table_taget}` (`{$field_target}`) VALUES ('{$db->escape($data_target)}');";

            $db->setQuery($query);

            $db->execute();

            $id = $db->insertid();

            $query1 = "INSERT INTO `{$join_source->table_join}` (`{$join_source->table_join_key}`, `{$join_source->table_key}`) VALUES ({$id_source},{$id})";

            $db->setQuery($query1);

            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method for inserting into a dropdown target table and databasejoin multSelect source table.
     *
     * @param $table_taget
     * @param $field_target
     * @param $data_target
     * @param $join_source
     * @param $id_source
     * @param $field_sychr_source
     *
     * @return bool
     *
     * @since version
     */
    public function insertInTargetDropAndSourceMultTable($table_taget, $field_target, $data_target, $join_source, $id_source, $field_sychr_source)
    {
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$table_taget}` (`{$field_target}`, `{$field_sychr_source}`) VALUES ('{$db->escape($data_target)}', {$id_source});";

            $db->setQuery($query);

            $db->execute();

            $id = $db->insertid();

            $query1 = "INSERT INTO `{$join_source->table_join}` (`{$join_source->table_join_key}`, `{$join_source->table_key}`) VALUES ({$id_source},{$id})";

            $db->setQuery($query1);

            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method takes destination list information
     *
     * @param $table_name_taget
     *
     * @return mixed
     *
     * @since version
     */
    public function tableTaget($table_name_taget)
    {
        $db = JFactory::getDbo();

        try {
            $query = "SELECT * FROM #__fabrik_lists AS `table` WHERE table.published = 1 AND `table`.db_table_name = '{$table_name_taget}';";

            $db->setQuery($query);

            return $db->loadObject();
        } catch (Exception $exc) {
            $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR5');
            $type_message = 'warning';
            $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools&tab=3';

            $this->setRedirect($site_message, $message, $type_message);
        }
    }

    /**
     * Method takes the information from the target table element that is synchronized with the source table.
     *
     * @param $table_target
     * @param $synchronism
     * @param $id_list_target
     *
     * @return mixed
     *
     * @since version
     */
    public function elementTableTarget($table_target, $synchronism, $id_list_target)
    {
        $db = JFactory::getDbo();

        try {
            $sql = "SELECT
                    element.id,
                    element.group_id,
                    element.`plugin`,
                    element.label,
                    element.name,
                    element.params
                    FROM
                    #__fabrik_elements AS element
                    LEFT JOIN #__fabrik_formgroup AS `group` ON element.group_id = `group`.group_id
                    LEFT JOIN #__fabrik_lists AS list ON `group`.form_id = list.form_id
                    WHERE
                    list.published = 1 AND
                    element.`name` = '{$synchronism}' AND
                    list.id = {$id_list_target} AND
                    list.db_table_name = '{$table_target}'";

            $db->setQuery($sql);

            return $db->loadObject();
        } catch (Exception $exc) {
            $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR5');
            $type_message = 'warning';
            $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools&tab=3';

            $this->setRedirect($site_message, $message, $type_message);
        }
    }

    /**
     * Method that inserts target, changes source, and inserts into the repeating target table.
     *
     * @param $table_taget
     * @param $field_target
     * @param $data_target
     * @param $table_source
     * @param $id_source
     * @param $field_source
     * @param $join_target
     *
     * @return bool
     *
     * @since version
     */
    public function insertTargetChangesSourceInsertTargetRepeatTable($table_taget, $field_target, $data_target, $table_source, $id_source, $field_source, $join_target)
    {
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$table_taget}` (`{$field_target}`) VALUES ('{$db->escape($data_target)}');";

            $db->setQuery($query);

            $db->execute();

            $id = $db->insertid();

            $query1 = "UPDATE {$table_source} SET {$field_source} = {$id} WHERE id = {$id_source}";

            $db->setQuery($query1);

            $db->execute();

            $query2 = "INSERT INTO `{$join_target->table_join}` (`{$join_target->table_join_key}`, `{$join_target->table_key}`) VALUES ({$id},{$id_source})";

            $db->setQuery($query2);

            $db->execute();

            $db->transactionCommit();


            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method that alters dropdown source table and inserts into target table in repetition.
     *
     * @param $table_source
     * @param $id_source
     * @param $field_source
     * @param $join_target
     * @param $id_target
     *
     * @return bool
     *
     * @since version
     */
    public function updateTableSourceDropdownInsertTableTargetRepeat($table_source, $id_source, $field_source, $join_target, $id_target)
    {
        $db = JFactory::getDbo();

        $query = "SELECT repit.id
                    FROM {$join_target->table_join} AS repit
                    WHERE repit.{$join_target->table_join_key} = {$id_target} AND repit.{$join_target->table_key} = {$id_source};";

        $db->setQuery($query);

        $result = $db->loadObject();

        try {
            $db->transactionStart();

            $query1 = "UPDATE {$table_source} SET {$field_source} = {$id_target} WHERE id = {$id_source}";

            $db->setQuery($query1);

            $db->execute();

            if (count($result) === 0) {
                $query2 = "INSERT INTO `{$join_target->table_join}` (`{$join_target->table_join_key}`, `{$join_target->table_key}`) VALUES ({$id_target},{$id_source})";

                $db->setQuery($query2);

                $db->execute();
            }
            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Function that will insert into source table, insert into source repeating table and insert into target repeating table.
     *
     * @param $table_taget
     * @param $field_target
     * @param $data_target
     * @param $join_source
     * @param $id_source
     * @param $join_target
     *
     * @return bool
     *
     * @since version
     */
    public function insertTargetTableInsertRepeatTargetTableInsertRepeatSourceTable($table_taget, $field_target, $data_target, $join_source, $id_source, $join_target)
    {
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$table_taget}` (`{$field_target}`) VALUES ('{$db->escape($data_target)}');";

            $db->setQuery($query);

            $db->execute();

            $id = $db->insertid();

            $query1 = "INSERT INTO `{$join_source->table_join}` (`{$join_source->table_join_key}`, `{$join_source->table_key}`) VALUES ({$id_source},{$id})";

            $db->setQuery($query1);

            $db->execute();

            $query2 = "INSERT INTO `{$join_target->table_join}` (`{$join_target->table_join_key}`, `{$join_target->table_key}`) VALUES ({$id},{$id_source})";

            $db->setQuery($query2);

            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Function that will insert into source repeating table and insert into target repeating table.
     *
     * @param $join_source
     * @param $id_source
     * @param $id_target
     * @param $join_target
     *
     * @return bool
     *
     * @since version
     */
    public function insertSourceRepeatTableInsertTargetRepeatTable($join_source, $id_source, $id_target, $join_target)
    {
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$join_source->table_join}` (`{$join_source->table_join_key}`, `{$join_source->table_key}`) VALUES ({$id_source},{$id_target})";

            $db->setQuery($query);

            $db->execute();

            $query1 = "INSERT INTO `{$join_target->table_join}` (`{$join_target->table_join_key}`, `{$join_target->table_key}`) VALUES ({$id_target},{$id_source})";

            $db->setQuery($query1);

            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Function that will update the data of the source table and modify the parameters of the element table.
     *
     * @param $table
     * @param $field
     * @param $id
     * @param $id_element
     * @param $data
     * @param $element_params
     *
     * @return bool
     *
     * @since version
     */
    public function updateDataTableSourceUpdateTableElement($table, $field, $id, $id_element, $data, $element_params)
    {
        $db = JFactory::getDbo();

        $paramsDB = json_encode($element_params);

        try {
            $db->transactionStart();

            $query = "UPDATE `{$table}`
                    SET
                    `{$field}` = '{$db->escape($data)}'
                    WHERE `id` = {$id};";

            $db->setQuery($query);

            $db->execute();

            $query1 = "UPDATE `#__fabrik_elements`
                    SET
                    `params` = '{$db->escape($paramsDB)}'
                    WHERE `id` = {$id_element};";

            $db->setQuery($query1);

            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * list of element types.
     *
     * @throws Exception
     */
    public function listElementType()
    {
        $app = JFactory::getApplication();

        $db = JFactory::getDbo();

        $id = $app->input->getInt("idList");

        $type_plugin = $app->input->getString("typePlugin");

        $sql = "SELECT
                element.id,
                element.label,
                element.name,
                list.db_table_name AS `table`,
                element.`plugin`,
                element.params,
                list.params as paramList
                FROM
                #__fabrik_elements AS element
                LEFT JOIN #__fabrik_formgroup AS fgroup ON element.group_id = fgroup.group_id
                LEFT JOIN #__fabrik_lists AS list ON fgroup.form_id = list.form_id
                WHERE
                list.published = 1 AND
                fgroup.form_id = {$id} AND element.`plugin` = '{$type_plugin}'
                ORDER BY
                element.label ASC;";

        $db->setQuery($sql);

        $list = $db->loadObjectList();

        if (count($list) > 0) {
            foreach ($list as $key => $value) {
                $list[$key]->params = json_decode($value->params);
                $list[$key]->paramList = json_decode($value->paramList);
            }

            echo json_encode($list);
        } else {
            echo '0';
        }

        $app->close();
    }

    /**
     * Function to get the engine type information from a possible table, to know if it is InnoDB or MyISAM.
     *
     * @param $table
     *
     * @return mixed
     *
     * @since version
     */
    public function checkEngineTypeTable($table)
    {
        $db = JFactory::getDbo();

        $sql = "select `engine` from information_schema.tables where table_name = '{$table}';";

        $db->setQuery($sql);

        return $db->loadObject();
    }

    /**
     * Method that changes a table in the database to InnoDB.
     *
     * @param $table
     * @return bool
     */
    public function alterTableEngineType($table)
    {
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $query = "ALTER TABLE {$table} ENGINE = InnoDB;";

            $db->setQuery($query);

            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Function that takes information from the join table field that is related to the target table to check if the data type is equal.
     *
     * @param $table
     * @param $field
     *
     * @return mixed
     *
     * @since version
     */
    public function joinTableFieldType($table, $field)
    {
        $db = JFactory::getDbo();

        try {
            $sql = "SHOW COLUMNS FROM `{$table}` WHERE field = '{$field}';";

            $db->setQuery($sql);

            return $db->loadObject();
        } catch (Exception $exc) {
            $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR5');
            $type_message = 'warning';
            $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools';

            $this->setRedirect($site_message, $message, $type_message);
        }
    }

    /**
     * Function that changes the field data type of a table to make the field integer
     *
     * @param $table
     * @param $field
     * @return bool
     * @since version
     */
    public function alterTableColummDataType($table, $field)
    {
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $query = "ALTER TABLE `{$table}` modify `{$field}` INT(11);";

            $db->setQuery($query);

            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Function that will create the foreign key in the table that references the source and destination table.
     *
     * @param $table
     * @param $table_source
     * @param $field_source
     * @param $table_target
     * @param $field_target
     * @param $update
     * @param $delete
     *
     * @return bool
     *
     * @since version
     */
    public function alterTableCreateForeignKeyRelatedFields2($table, $table_source, $field_source, $table_target, $field_target, $update, $delete)
    {
        $db = JFactory::getDbo();

        $name_fk1 = 'fk_' . $table . '_' . $field_source . '_' . $table_source;

        $name_fk2 = 'fk_' . $table . '_' . $field_target . '_' . $table_target;

        try {
            $db->transactionStart();

            $query = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$name_fk1}` FOREIGN KEY ( `{$field_source}` ) REFERENCES `{$table_source}` ( `id` ) ON UPDATE {$update} ON DELETE {$delete} ;";

            $db->setQuery($query);

            $db->execute();

            $query1 = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$name_fk2}` FOREIGN KEY ( `{$field_target}` ) REFERENCES `{$table_target}` ( `id` ) ON UPDATE {$update} ON DELETE {$delete} ;";

            $db->setQuery($query1);

            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Function that checks if the foreign key already exists in the table.
     *
     * @param $table
     * @param $table_taget
     * @param $field
     * @return void
     */
    public function checkIfForeignKeyExists($table, $table_taget, $field)
    {
        $db = JFactory::getDbo();

        try {
            $sql = "SELECT COUNT(column_name) as forkey
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE REFERENCED_TABLE_SCHEMA IS NOT NULL 
                AND TABLE_NAME = '{$table}'
                AND COLUMN_NAME = '{$field}'
                AND REFERENCED_TABLE_NAME = '{$table_taget}';";

            $db->setQuery($sql);

            return $db->loadObject();
        } catch (Exception $exc) {
            $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR5');
            $type_message = 'warning';
            $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools';

            $this->setRedirect($site_message, $message, $type_message);
        }
    }

    /**
     * Function of source table structure
     *
     * @param $tableSource
     * @return bool
     */
    public function sourceTableStructure($tableSource)
    {
        $engine_source = $this->checkEngineTypeTable($tableSource);

        if ($engine_source->engine === 'MyISAM') {
            $engine_source_bool = $this->alterTableEngineType($tableSource);
        } elseif ($engine_source->engine === 'InnoDB') {
            $engine_source_bool = true;
        }
        return $engine_source_bool;
    }

    /**
     * Join Table Structure Function
     *
     * @param $join_source
     *
     * @return bool
     *
     * @since version
     */
    public function joinTableStructure($join_source)
    {
        $engine_join = $this->checkEngineTypeTable($join_source);

        if ($engine_join->engine === 'MyISAM') {
            $engine_join_bool = $this->alterTableEngineType($join_source);
        } elseif ($engine_join->engine === 'InnoDB') {
            $engine_join_bool = true;
        }
        return $engine_join_bool;
    }

    /**
     * Function of target table structure
     *
     * @param $table_target
     *
     * @return bool
     *
     * @since version
     */
    public function targetTableStructure($table_target)
    {
        $engine_target = $this->checkEngineTypeTable($table_target);

        if ($engine_target->engine === 'MyISAM') {
            $engine_target_bool = $this->alterTableEngineType($table_target);
        } elseif ($engine_target->engine === 'InnoDB') {
            $engine_target_bool = true;
        }
        return $engine_target_bool;
    }

    /**
     * Function that creates the foreign key in the source table that references the target table.
     *
     * @param $table
     * @param $field_source
     * @param $table_target
     * @param $update
     * @param $delete
     *
     * @return bool
     *
     * @since version
     */
    public function alterTableCreateForeignKeyRelatedFields1($table, $table_target, $field_source, $update, $delete)
    {
        $db = JFactory::getDbo();

        $name_fk1 = 'fk_' . $table . '_' . $field_source . '_' . $table_target;

        try {
            $db->transactionStart();

            $query = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$name_fk1}` FOREIGN KEY ( `{$field_source}` ) REFERENCES `{$table_target}` ( `id` ) ON UPDATE {$update} ON DELETE {$delete} ;";

            $db->setQuery($query);

            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Structure that checks for equality between related fields.
     *
     * @param $table_name
     * @param $table_join
     * @param $table_key
     * @return bool
     */
    public function estructureChecksEqualityBetweenFields($table_name, $table_join, $table_key)
    {
        $join_type_field = $this->joinTableFieldType($table_join, $table_key);

        $type_field = $this->joinTableFieldType($table_name, 'id');

        if ($join_type_field->Type !== $type_field->Type) {
            $join_type_bool = $this->alterTableColummDataType($table_join, $table_key);
        } elseif ($join_type_field->Type === $type_field->Type) {
            $join_type_bool = true;
        }
        return $join_type_bool;
    }

    /**
     * Function that creates the foreign key in the source table that references the target table and in the join table that references the source and destination table.
     *
     * @param $table
     * @param $table_source
     * @param $field_source
     * @param $field_source_parent
     * @param $table_target
     * @param $field_target
     * @param $update
     * @param $delete
     *
     * @return bool
     *
     * @since version
     */
    public function alterTableCreateForeignKeyRelatedFields3($table, $table_source, $field_source, $field_source_parent, $table_target, $field_target, $update, $delete)
    {
        $db = JFactory::getDbo();

        $name_fk1 = 'fk_' . $table_target . '_' . $field_target . '_' . $table_source;
        $name_fk2 = 'fk_' . $table . '_' . $field_source_parent . '_' . $table_source;
        $name_fk3 = 'fk_' . $table . '_' . $field_source . '_' . $table_target;

        try {
            $db->transactionStart();

            $query = "ALTER TABLE `{$table_target}` ADD CONSTRAINT `{$name_fk1}` FOREIGN KEY ( `{$field_target}` ) REFERENCES `{$table_source}` ( `id` ) ON UPDATE {$update} ON DELETE {$delete} ;";

            $db->setQuery($query);

            $db->execute();

            $query1 = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$name_fk2}` FOREIGN KEY ( `{$field_source_parent}` ) REFERENCES `{$table_source}` ( `id` ) ON UPDATE {$update} ON DELETE {$delete} ;";

            $db->setQuery($query1);

            $db->execute();

            $query2 = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$name_fk3}` FOREIGN KEY ( `{$field_source}` ) REFERENCES `{$table_target}` ( `id` ) ON UPDATE {$update} ON DELETE {$delete} ;";

            $db->setQuery($query2);

            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Function that creates the foreign key in synchronous repeating tables that are related to their source and destination tables.
     *
     * @param $table_join_source
     * @param $table_join_target
     * @param $table_source
     * @param $table_target
     * @param $field_source
     * @param $field_target
     * @param $update
     * @param $delete
     *
     * @return bool
     *
     * @since version
     */
    public function alterTableCreateForeignKeyRelatedFields4($table_join_source, $table_join_target, $table_source, $table_target, $field_source, $field_target, $update, $delete)
    {
        $db = JFactory::getDbo();

        $parent_id = 'parent_id';

        $name_fk1 = 'fk_' . $table_join_source . '_' . $parent_id . '_' . $table_source;
        $name_fk2 = 'fk_' . $table_join_source . '_' . $field_target . '_' . $table_target;
        $name_fk3 = 'fk_' . $table_join_target . '_' . $parent_id . '_' . $table_target;
        $name_fk4 = 'fk_' . $table_join_target . '_' . $field_source . '_' . $table_source;

        try {
            $db->transactionStart();

            $query1 = "ALTER TABLE `{$table_join_source}` ADD CONSTRAINT `{$name_fk1}` FOREIGN KEY ( `{$parent_id}` ) REFERENCES `{$table_source}` ( `id` ) ON UPDATE {$update} ON DELETE {$delete} ;";

            $db->setQuery($query1);

            $db->execute();

            $query2 = "ALTER TABLE `{$table_join_source}` ADD CONSTRAINT `{$name_fk2}` FOREIGN KEY ( `{$field_target}` ) REFERENCES `{$table_target}` ( `id` ) ON UPDATE {$update} ON DELETE {$delete} ;";

            $db->setQuery($query2);

            $db->execute();

            $query3 = "ALTER TABLE `{$table_join_target}` ADD CONSTRAINT `{$name_fk3}` FOREIGN KEY ( `{$parent_id}` ) REFERENCES `{$table_target}` ( `id` ) ON UPDATE {$update} ON DELETE {$delete} ;";

            $db->setQuery($query3);

            $db->execute();

            $query4 = "ALTER TABLE `{$table_join_target}` ADD CONSTRAINT `{$name_fk4}` FOREIGN KEY ( `{$field_source}` ) REFERENCES `{$table_source}` ( `id` ) ON UPDATE {$update} ON DELETE {$delete} ;";

            $db->setQuery($query4);

            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * display messages for different use administration tool.
     *
     * @param $result_sql
     * @param $message_true
     * @param $message_false
     * @return array
     */

    public function displayMessages($result_sql, $message_true, $message_false)
    {
        if ($result_sql) {
            $ar_message['message'] = JText::_($message_true);
            $ar_message['type'] = 'Success';
        } else {
            $ar_message['message'] = JText::_($message_false);
            $ar_message['type'] = 'info';
        }

        return $ar_message;
    }

    /**
     * Resizes the original images to thumbs that are sized in the parameters.
     *
     * @param $file
     * @param $path_thumbs
     * @param $width
     * @param $height
     */
    public function imageTransformationThumbsCrops($file, $path, $width, $height, $type = 0, $mult = 0)
    {
        $file = str_replace('\\', "/", $file);

        $ext = explode('.', $file);

        $type2 = strtolower(substr(strrchr($file, "."), 1));

        if ($mult === 0) {
            $ar_file = explode('/', $ext[count($ext) - 2]);

            $nm_file = $ar_file[count($ar_file) - 1] . '.' . $ext[count($ext) - 1];

            $path .= '/' . $nm_file;
        } else {
            $ar_file = explode('\\', $ext[count($ext) - 2]);

            $nm_file = $ar_file[count($ar_file) - 1] . '.' . $ext[count($ext) - 1];

            $path .= '\\' . $nm_file;
        }

        if ($type2 == 'jpeg')
            $type2 = 'jpg';

        $mine_type = mime_content_type($file);

        if ($type2 === 'jpg') {
            if ($mine_type === 'image/gif') {
                $image_original = imagecreatefromgif($file);
            } elseif ($mine_type === 'image/png') {
                $image_original = imagecreatefrompng($file);
            } else {
                $image_original = imagecreatefromjpeg($file);
            }
        } elseif ($type2 === 'png') {
            if ($mine_type === 'image/jpeg') {
                $image_original = imagecreatefromjpeg($file);
            } elseif ($mine_type === 'image/gif') {
                $image_original = imagecreatefromgif($file);
            } else {
                $image_original = imagecreatefrompng($file);
            }
        } elseif ($type2 === 'gif') {
            if ($mine_type === 'image/jpeg') {
                $image_original = imagecreatefromjpeg($file);
            } elseif ($mine_type === 'image/png') {
                $image_original = imagecreatefrompng($file);
            } else {
                $image_original = imagecreatefromgif($file);
            }
        }

        list($width_old, $height_old) = getimagesize($file);

        if ($type === 1) {
            $to_crop_array = array('x' => 0, 'y' => 0, 'width' => $width, 'height' => $height);

            $image_tmp = imagecrop($image_original, $to_crop_array);
        } else {
            $image_tmp = imagecreatetruecolor($width, $height);

            imagecopyresampled($image_tmp, $image_original, 0, 0, 0, 0, $width, $height, $width_old, $height_old);
        }

        if ($type2 === 'jpg') {
            imagejpeg($image_tmp, $path);
        } elseif ($type2 === 'png') {
            imagepng($image_tmp, $path);
        } elseif ($type2 === 'gif') {
            imagegif($image_tmp, $path);
        }
    }

    /**
     * It takes all the data from the source field of the list table that was chosen.
     *
     * @param $table
     * @param $field_source
     * @return mixed
     */
    public function dataTableSourceFieldSingle($table, $field_source)
    {
        $db = JFactory::getDbo();

        try {
            $query = "SELECT
                `table`.id,
                `table`.{$field_source}
                FROM {$table} AS `table`
                ORDER BY `table`.id ASC;";

            $db->setQuery($query);

            return $db->loadAssocList();
        } catch (Exception $exc) {
            $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR5');
            $type_message = 'warning';
            $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools';

            $this->setRedirect($site_message, $message, $type_message);
        }
    }

    /**
     * Method to insert into source table that is databasejoin multSelect without sync.
     *
     * @param $join_source
     * @param $id_source
     * @param $text
     *
     * @return bool
     *
     * @since version
     */
    public function insertMultipleSourceTableFileupload($join_source, $id_source, $text)
    {
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$join_source->table_join}` (`{$join_source->table_join_key}`, `{$join_source->table_key}`) VALUES ({$id_source},'{$db->escape($text)}')";

            $db->setQuery($query);

            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Execute the path of the file that is in the database, check if it exists, copy it to a temporary one, resize or cut and save it in the same place and with the same name.
     *
     * @param $data
     * @param $params
     * @param $value
     * @return bool
     */
    public function performChangeThumbsCrops($params, $value, $key)
    {
        $path = dirname(dirname($_SERVER['SCRIPT_FILENAME']));

        $photo = file_exists($path . $value);

        $file = $path . $value;

        if ($photo) {
            if ($params->make_thumbnail === '1') {
                $width = $params->thumb_max_width;
                $height = $params->thumb_max_height;

                $path_thumbs = $path . '/' . $params->thumb_dir;

                $this->imageTransformationThumbsCrops($file, $path_thumbs, $width, $height);
            }

            if ($params->fileupload_crop === '1') {
                $width = $params->fileupload_crop_width;
                $height = $params->fileupload_crop_height;

                $path_crops = $path . '/' . $params->fileupload_crop_dir;

                $this->imageTransformationThumbsCrops($file, $path_crops, $width, $height, 1);
            }
        }
    }

    /**
     * Function that will take exception messages and return them to the client already treated.
     *
     * @param $code
     * @param $message
     * @return mixed
     */
    public static function handlePossibleExceptions($code, $message)
    {
        switch ($code) {
            case 1064:
                $text = FText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_1064');

                break;
            default:
                $text = $message;

                break;
        }

        return $text;
    }

    /**
     * Method that changes the status of the harvesting table.
     *
     * @throws Exception
     */
    public function enableDisableHarvesting()
    {
        $app = JFactory::getApplication();

        $db = JFactory::getDbo();

        $id = $app->input->getInt("id");

        $status = $app->input->getInt("status");

        try {
            $db->transactionStart();

            $query = "UPDATE `#__fabrik_harvesting` SET `status` = '{$status}' WHERE `id` = {$id};";

            $db->setQuery($query);

            $db->execute();

            $db->transactionCommit();

            echo '1';
        } catch (Exception $exc) {
            $db->transactionRollback();

            echo '0';
        }

        $app->close();
    }

    /**
     * Method that deletes an item from the playlist.
     *
     * @throws Exception
     */
    public function deleteHarvesting()
    {
        $app = JFactory::getApplication();
        $db = JFactory::getDbo();
        $id = $app->input->getInt("id");

        try {
            $db->transactionStart();

            $query = "DELETE FROM `#__fabrik_harvesting` WHERE `id` = {$id}";
            $db->setQuery($query);
            $db->execute();

            $db->transactionCommit();

            echo '1';
        } catch (Exception $exc) {
            $db->transactionRollback();

            echo '0';
        }

        $app->close();
    }

    /**
     * Method that separates the form submission to perform each button of different actions on the same form.
     *
     * @throws Exception
     */
    public function submitHarvesting()
    {
        $app = JFactory::getApplication();
        $db = JFactory::getDbo();
        $user = JFactory::getUser();

        $data['link'] = $db->escape($app->input->getString("linkHarvest"));
        $btn = $db->escape($app->input->getString("btnSubmit"));
        $data['id'] = $app->input->getInt("idHarvest", 0);
        $data['list'] = $app->input->getInt("listHarvert");
        $data['download'] = $app->input->getInt("downloadHarvest", NULL);
        $data['extract'] = $app->input->getInt("extractTextHarvert", NULL);
        $data['mapRarvest'] = $app->input->get('mapRarvest', array(), 'array');
        $data['listDublinCoreType'] = $app->input->get('listDublinCoreType', array(), 'array');
        $data['mapRarvestHeader'] = $app->input->get('mapRarvestHeader', array(), 'array');
        $data['listDublinCoreTypeHeader'] = $app->input->get('listDublinCoreTypeHeader', array(), 'array');
        $data['sync'] = $app->input->getInt("syncHarvest");

        if ($data['sync'] === 0) {
            $data['field1'] = NULL;
            $data['field2'] = NULL;
        } elseif ($data['sync'] === 1) {
            $data['field1'] = $app->input->getInt("dateListHarvest");
            $data['field2'] = $db->escape($app->input->getString("dateRepositoryHarvest"));
        }

        date_default_timezone_set('America/Sao_Paulo');
        $data['registerDate'] = date("Y-m-d H:i:s");

        $data['users'] = $user->get('id');
        $data['idElements'] = '';

        foreach ($data['mapRarvestHeader'] as $key => $value) {
            $data['header'][$data['listDublinCoreTypeHeader'][$key]] = $value;

            if (strlen($data['idElements']) === 0) {
                $data['idElements'] .= $value;
            } else {
                $data['idElements'] .= ',' . $value;
            }
        }

        foreach ($data['mapRarvest'] as $key => $value) {
            $data['metadata'][$data['listDublinCoreType'][$key]][] = $value;

            if (strlen($data['idElements']) === 0) {
                $data['idElements'] .= $value;
            } else {
                $data['idElements'] .= ',' . $value;
            }
        }

        switch ($btn) {
            case 'btnSave':
                $this->repositoryValidator($data['link']);

                $result = $this->saveHarvesting($data, NULL, $data['registerDate']);

                if ($result['status']) {
                    $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_SUCCESS1');
                    $type_message = 'success';
                } else {
                    $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR0');
                    $type_message = 'warning';
                }

                break;

            case 'btnSaveRun':
                $this->repositoryValidator($data['link']);

                $data['line_num'] = 0;
                $data['page_xml'] = 0;

                $result = $this->saveHarvesting($data, $data['registerDate'], $data['registerDate'], 1);

                if (!$result['status']) {
                    $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR0');
                    $type_message = 'warning';

                    break;
                }

                $data['id'] = $result['id'];

                $result = $this->saveRunHarvesting($data);

                if (!$result) {
                    $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR4');
                    $type_message = 'warning';

                    break;
                } else {
                    $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_SUCCESS3');
                    $type_message = 'success';
                }

                break;

            case 'btnRumList':
                $result = $this->runListHarvesting($data);

                if (!$result) {
                    $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR5');
                    $type_message = 'warning';

                    break;
                } else {
                    $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_SUCCESS3');
                    $type_message = 'success';
                }

                break;
        }

        $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools&tab=3';
        $this->setRedirect($site_message, $message, $type_message);
    }

    /**
     * Method that saves the tool structure.
     *
     * @throws Exception
     */
    public function saveHarvesting($data, $dateRum = NULL, $dateRecordLast = NULL, $line = NULL)
    {
        $db = JFactory::getDbo();
        $header = $db->escape(json_encode($data['header']));
        $metadata = $db->escape(json_encode($data['metadata']));

        try {
            $db->transactionStart();

            if ($data['id'] === 0) {
                $query = "INSERT INTO `#__fabrik_harvesting` (`repository`, `list`, `dowload_file`, `extract`, `syncronism`, `field1`, `field2`, `status`, `date_creation`,
                        `date_execution`, `users_id`, `record_last`, `map_header`, `map_metadata`, `line_num`, `page_xml`) 
                        VALUES ('{$data['link']}', {$data['list']}, '{$data['download']}', {$data['extract']}, {$data['sync']}, '{$data['field1']}', '{$data['field2']}', '1', '{$data['registerDate']}', 
                        '{$dateRum}', {$data['users']}, '{$dateRecordLast}', '{$header}', '{$metadata}', 0, 0)";
            } else {
                $query = "UPDATE `#__fabrik_harvesting` SET 
                            `repository` = '{$data['link']}', 
                            `list` = {$data['list']},
                            `dowload_file` = '{$data['download']}', 
                            `extract` = {$data['extract']},
                            `syncronism` = {$data['sync']},
                            `field1` = '{$data['field1']}', 
                            `field2` = '{$data['field2']}', ";

                if (($dateRum !== NULL) && (strlen($dateRum) !== 0)) {
                    $query .= "`date_execution` = '{$dateRum}', ";
                }

                if (($line !== NULL) && (strlen($line) !== 0)) {
                    $query .= "`line_num` = {$data['line_num']},
                               `page_xml` = {$data['page_xml']}, ";
                }

                $query .= " `users_id` = {$data['users']},
                            `record_last` = '{$dateRecordLast}',
                            `map_header` = '{$header}',
                            `map_metadata` = '{$metadata}'                           
                            WHERE `id` = {$data['id']};";
            }

            $db->setQuery($query);

            $db->execute();

            if ($data['id'] === 0) {
                $dados['id'] = $db->insertid();
            } else {
                $dados['id'] = $data['id'];
            }

            $db->transactionCommit();

            $dados['status'] = true;

            return $dados;
        } catch (Exception $exc) {
            $db->transactionRollback();

            $dados['status'] = false;
            $dados['mensagem'] = $exc;

            return $dados;
        }
    }

    /**
     * Method that saves and executes what is on the form.
     *
     * @throws Exception
     */
    public function saveRunHarvesting($data)
    {
        set_time_limit(0);

        $db = JFactory::getDbo();
        $config = JFactory::getConfig();

        $ext = $config->get('dbprefix');

        $data['tableSource'] = $this->tableSource($data['list']);

        $totalRecords = $data['page_xml'];
        $currentRecords = 0;

        $baseURL = $data['link'] . '?verb=ListRecords';

        $initialParams = '&resumptionToken=oai_dc////' . $totalRecords;

        $resumptionBase = '&resumptionToken=';
        $resumptionToken = 'initial';

        $fetchCounter = 1;

        while ($resumptionToken != '') {
            if ($fetchCounter === 1) {
                $url = $baseURL . $initialParams;
                $resumptionToken = '';
            } else {
                $url = $baseURL . $resumptionBase . $resumptionToken;
            }

            $xmlObj = simplexml_load_file($url);

            if ($xmlObj) {
                $xmlNode = $xmlObj->ListRecords;

                if ($fetchCounter === 1) {
                    $arNumLineXML = get_object_vars($xmlNode->resumptionToken);
                    $lineNum = (int)$arNumLineXML['@attributes']['completeListSize'];

                    $table = $ext . 'fabrik_harvesting';
                    $this->updateDataTableSource($table, $lineNum, $data['id'], 'line_num');
                }

                if ($xmlNode->count() !== 0) {
                    $currentRecords = count($xmlNode->children());

                    $dom = new DOMDocument();

                    foreach ($xmlNode->record as $recordNode) {
                        $fields = '';
                        $fieldsContent = '';
                        unset($arFieldsElement);
                        unset($arFieldsContent);

                        if (is_array($data['header']) && (!is_null($data['header']))) {
                            $dom->loadHTML('<?xml encoding="utf-8" ?>' . trim($recordNode->header->asXML()));
                            $i = 0;

                            foreach ($data['header'] as $key => $value) {
                                $metas = $dom->getElementsByTagName($key);

                                $element = $this->mappedElementsData($data['list'], $value);

                                if ($i !== 0) {
                                    $fields .= ", {$element->name}";
                                } else {
                                    $fields .= "{$element->name}";
                                }

                                if ($data['sync'] === 1) {
                                    $arFieldsElement[] = $element->name;
                                }

                                switch ($element->plugin) {
                                    case 'date':
                                        $date = date("Y-m-d H:i:s", strtotime($db->escape($metas->item(0)->nodeValue)));

                                        if ($i !== 0) {
                                            $fieldsContent .= ", '$date'";
                                        } else {
                                            $fieldsContent .= "'$date'";
                                        }

                                        if ($data['sync'] === 1) {
                                            $arFieldsContent[] = $date;
                                        }

                                        if (($data['field2'] === 'datestamp') && ($data['sync'] === 1)) {
                                            $dateSync = date("Y-m-d", strtotime($db->escape($metas->item(0)->nodeValue)));;
                                            $fieldSync = $element->name;
                                        }

                                        break;
                                    default:
                                        if ($i !== 0) {
                                            $fieldsContent .= ", '{$db->escape($metas->item(0)->nodeValue)}'";
                                        } else {
                                            $fieldsContent .= "'{$db->escape($metas->item(0)->nodeValue)}'";
                                        }

                                        if ($data['sync'] === 1) {
                                            $arFieldsContent[] = $db->escape($metas->item(0)->nodeValue);
                                        }

                                        if ($key === 'identifier') {
                                            $identifier = $db->escape($metas->item(0)->nodeValue);
                                            $fieldIdentifier = $element->name;
                                        }

                                        break;
                                }

                                $i += 1;
                            }

                            if ($data['sync'] === 1) {
                                $result_identifier = $this->checkDataTableExist($data['tableSource']->db_table_name, $fieldIdentifier, $identifier, $fieldSync, $dateSync);
                                $update = 1;
                            } else {
                                $result_identifier = $this->checkDataTableExist($data['tableSource']->db_table_name, $fieldIdentifier, $identifier);
                                $update = 0;
                            }

                            if (is_null($result_identifier->id) && ($update === 0)) {
                                if (is_array($data['metadata']) && (!is_null($data['metadata']))) {
                                    $dom->loadHTML('<?xml encoding="utf-8" ?>' . trim($recordNode->metadata->asXML()));

                                    $i = 0;
                                    unset($arFields);
                                    unset($tableRepeat);
                                    unset($arFieldsTag);
                                    unset($tableRepeatTag);

                                    foreach ($data['metadata'] as $key => $objFields) {
                                        $tag = explode(':', $key);
                                        $metas = $dom->getElementsByTagName($tag[1]);

                                        $arLength = array_count_values($objFields);
                                        $j = 0;

                                        $fieldExtra = "";

                                        foreach ($objFields as $index => $value) {
                                            $joinModelSource = JModelLegacy::getInstance('Join', 'FabrikFEModel');
                                            $element = $this->mappedElementsData($data['list'], $value);
                                            $objParams = json_decode($element->params);

                                            if (strlen($fields) !== 0) {
                                                if ($j === 0) {
                                                    if (($objParams->database_join_display_type !== "checkbox") && ($objParams->database_join_display_type !== "multilist") && ($element->plugin !== 'tags')) {
                                                        $fields .= ", {$element->name}";
                                                        $itemFieldElement = 1;
                                                    } else {
                                                        $itemFieldElement = 0;
                                                    }
                                                } elseif (($arLength[$value] === 1) && ($objParams->database_join_display_type !== "checkbox") && ($objParams->database_join_display_type !== "multilist") && ($element->plugin !== 'tags')) {
                                                    $fields .= ", {$element->name}";
                                                    $itemFieldElement = 1;
                                                }
                                            } else {
                                                $fields .= "{$element->name}";
                                                $itemFieldElement = 1;
                                            }

                                            if (($data['sync'] === 1) && ($itemFieldElement === 1)) {
                                                $arFieldsElement[] = $element->name;
                                            }

                                            switch ($element->plugin) {
                                                case 'date':
                                                    $date = date("Y-m-d H:i:s", strtotime($db->escape($metas->item($index)->nodeValue)));

                                                    if (strlen($fieldsContent) !== 0) {
                                                        $fieldsContent .= ", '{$date}'";
                                                    } else {
                                                        $fieldsContent .= "'{$date}'";
                                                    }

                                                    if ($data['sync'] === 1) {
                                                        $arFieldsContent[] = $date;
                                                    }

                                                    if (($data['field2'] === 'dc:date') && ($data['sync'] === 1)) {
                                                        $dateSync = date("Y-m-d", strtotime($db->escape($metas->item($index)->nodeValue)));
                                                        $fieldSync = $element->name;
                                                    }

                                                    break;
                                                case 'dropdown':
                                                    $objOptions = $objParams->sub_options;

                                                    if (!(in_array($db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue))), $objOptions->sub_values)) &&
                                                        !(in_array($db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue))), $objOptions->sub_labels))) {
                                                        $objOptions->sub_values[] = ucwords(mb_strtolower($db->escape($metas->item($index)->nodeValue)));
                                                        $objOptions->sub_labels[] = ucwords(mb_strtolower($db->escape($metas->item($index)->nodeValue)));
                                                    }

                                                    $parans_update = $this->updateTableElement($value, $objParams);

                                                    if ($parans_update) {
                                                        if (strlen($fieldsContent) !== 0) {
                                                            $fieldsContent .= ", '{$db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue)))}'";
                                                        } else {
                                                            $fieldsContent .= "'{$db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue)))}'";
                                                        }
                                                    }

                                                    if ($data['sync'] === 1) {
                                                        $arFieldsContent[] = $db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue)));
                                                    }

                                                    break;
                                                case 'databasejoin':
                                                    if ((($objParams->database_join_display_type === "dropdown") || ($objParams->database_join_display_type === "radio") ||
                                                        ($objParams->database_join_display_type === "auto-complete"))) {
                                                        $exist_data_target = $this->existTargetTableData($objParams->join_db_name, $objParams->join_val_column, $metas->item($index)->nodeValue);

                                                        if (count($exist_data_target) === 0) {
                                                            $result = $this->insertTable($objParams->join_db_name, $objParams->join_val_column, $metas->item($index)->nodeValue);
                                                        } else {
                                                            $result = $exist_data_target['id'];
                                                        }

                                                        if ((strlen($fieldsContent) !== 0) && ($result !== false)) {
                                                            $fieldsContent .= ", '{$result}'";
                                                        } elseif ((strlen($fieldsContent) === 0) && ($result !== false)) {
                                                            $fieldsContent .= "'{$result}'";
                                                        } elseif ((strlen($fieldsContent) === 0) && ($result === false)) {
                                                            $fieldsContent .= ",''";
                                                        } elseif ((strlen($fieldsContent) !== 0) && ($result === false)) {
                                                            $fieldsContent .= "''";
                                                        }

                                                        if ($data['sync'] === 1) {
                                                            $arFieldsContent[] = $result;
                                                        }
                                                    } elseif ((($objParams->database_join_display_type === "checkbox") || ($objParams->database_join_display_type === "multilist"))) {
                                                        $tableRepeat[$tag[1]] = $joinModelSource->getJoinFromKey('element_id', $value);

                                                        for ($numDBJ = 0; $numDBJ < $metas->length; $numDBJ++) {
                                                            if ($tag[1] === 'creator') {
                                                                $arTagText = explode(',', $metas->item($numDBJ)->nodeValue);
                                                                $tagText = trim($arTagText[1]) . ' ' . trim($arTagText[0]);
                                                            } else {
                                                                $tagText = trim($metas->item($numDBJ)->nodeValue);
                                                            }

                                                            $exist_data_target = $this->existTargetTableData($objParams->join_db_name, $objParams->join_val_column, $tagText);

                                                            if (count($exist_data_target) === 0) {
                                                                $arFields[$tag[1]][] = (int)$this->insertTable($objParams->join_db_name, $objParams->join_val_column, $tagText);
                                                            } else {
                                                                $arFields[$tag[1]][] = (int)$exist_data_target['id'];
                                                            }
                                                        }
                                                    }

                                                    break;
                                                case 'tags':
                                                    $nameTableTags = $ext . 'tags';

                                                    if ($objParams->tags_dbname === $nameTableTags) {
                                                        $tagSelestField = 'title';
                                                    }

                                                    $tableRepeatTag[$tag[1]] = $joinModelSource->getJoinFromKey('element_id', $value);

                                                    for ($numDBJ = 0; $numDBJ < $metas->length; $numDBJ++) {
                                                        $tagText = trim(ucfirst($metas->item($numDBJ)->nodeValue));

                                                        $exist_data_target = $this->existTargetTableData($objParams->tags_dbname, $tagSelestField, $tagText);

                                                        if ($objParams->tags_dbname === $nameTableTags) {
                                                            $tagTextExtra = $this->removeAccentsSpecialCharacters(trim(strtolower($metas->item($numDBJ)->nodeValue)));
                                                            date_default_timezone_set('America/Sao_Paulo');
                                                            $data['registerDate'] = date("Y-m-d H:i:s");

                                                            $user = JFactory::getUser();
                                                            $data['users'] = $user->get('id');

                                                            $tagInsertField = "`parent_id`, `level`, `path`, `title`, `alias`, `published`, `checked_out_time`, `access`, `created_user_id`,
                                             `created_time`, `modified_time`, `publish_up`, `publish_down`";

                                                            $tagInsertData = "'1','1','{$tagTextExtra}','{$tagText}','{$tagTextExtra}','1','{$data['registerDate']}','1','{$data['users']}',
                                            '{$data['registerDate']}','{$data['registerDate']}','{$data['registerDate']}','{$data['registerDate']}'";
                                                        }

                                                        if (count($exist_data_target) === 0) {
                                                            $arFieldsTag[$tag[1]][] = (int)$this->insertTableMultipleFieldsData($objParams->tags_dbname, $tagInsertField, $tagInsertData);
                                                        } else {
                                                            $arFieldsTag[$tag[1]][] = (int)$exist_data_target['id'];
                                                        }
                                                    }

                                                    break;
                                                default:
                                                    $num = $index + 1;

                                                    if (strlen($fieldsContent) !== 0) {
                                                        if ($j === 0) {
                                                            $fieldExtra .= $db->escape($metas->item($index)->nodeValue);
                                                        } elseif ($arLength[$value] === 1) {
                                                            $fieldExtra = $db->escape($metas->item($j)->nodeValue);
                                                        } else {
                                                            $fieldExtra .= '|' . $db->escape($metas->item($index)->nodeValue);
                                                        }

                                                        if (($num === $arLength[$value]) || ($arLength[$value] === 1)) {
                                                            $fieldsContent .= ", '{$fieldExtra}'";
                                                        }
                                                    } else {
                                                        $fieldExtra .= $db->escape($metas->item($index)->nodeValue);

                                                        if ($num === $arLength[$value]) {
                                                            $fieldsContent .= "'{$fieldExtra}'";
                                                        }
                                                    }

                                                    if ($data['sync'] === 1) {
                                                        $arFieldsContent[] = $fieldExtra;
                                                    }

                                                    break;
                                            }

                                            $j += 1;
                                        }

                                        $i += 1;
                                    }
                                }

                                if ($data['download'] !== 0) {
                                    $metas2 = $this->searchFileRepositoryOER($data['link'], $identifier);

                                    $dirName = '';
                                    unset($tableRepeatFile);

                                    if ($metas2 !== false) {
                                        $path = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
                                        for ($i = 0; $i < $metas2->length; $i++) {
                                            $tag = $metas2->item($i);

                                            if (($tag->nodeName == 'description') && ((strpos($tag->getAttribute('rdf:about'), '.pdf') !== false) &&
                                                    (strpos($tag->getAttribute('rdf:about'), '.pdf.') === false))) {
                                                $linkFileXML = substr($tag->getAttribute('rdf:about'), 0, strpos($tag->getAttribute('rdf:about'), '.pdf') + 4);
                                            }
                                        }

                                        $file_ext = explode('.', $linkFileXML);

                                        if (count($file_ext) === 2) {
                                            $elementFile = $this->mappedElementsData($data['list'], $data['download']);
                                            $objParamsFile = json_decode($elementFile->params);

                                            $urlDirectory = $objParamsFile->ul_directory;

                                            $nameIdentifier = str_replace('/', '_', str_replace(':', '_', str_replace('.', '_', $identifier)));

                                            $dir = $path . $urlDirectory . $nameIdentifier . '_' . basename($linkFileXML);

                                            if ($objParamsFile->ajax_upload === '0') {
                                                $fields .= ", {$elementFile->name}";

                                                $dirName = $urlDirectory . $nameIdentifier . '_' . basename($linkFileXML);
                                                $fieldsContent .= ", '{$dirName}'";

                                                if ($data['sync'] === 1) {
                                                    $arFieldsElement[] = $elementFile->name;
                                                    $arFieldsContent[] = $dirName;
                                                }
                                            } elseif ($objParamsFile->ajax_upload === '1') {
                                                $joinModelSourceFile = JModelLegacy::getInstance('Join', 'FabrikFEModel');
                                                $tableRepeatFile['file'] = $joinModelSourceFile->getJoinFromKey('element_id', $data['download']);
                                                $dirName = $db->escape(str_replace('/', '\\', $urlDirectory . $nameIdentifier . '_' . basename($linkFileXML)));
                                            }

                                            $linkUFG = 'localhost:8080';

                                            if (strpos($linkFileXML, $linkUFG) !== false) {
                                                $arLinkFileXML = explode('/', $linkFileXML);

                                                $link = 'http://repositorio.bc.ufg.br/bitstream/ri/';

                                                $linkFileXML = $link . $arLinkFileXML[count($arLinkFileXML) - 3] . '/' . $arLinkFileXML[count($arLinkFileXML) - 2] . '/' . $arLinkFileXML[count($arLinkFileXML) - 1];
                                            }

                                            copy($linkFileXML, $dir);

                                            if ($objParamsFile->fu_make_pdf_thumb === '1') {
                                                $path_thumb = $path . '/' . $objParamsFile->thumb_dir . '/' . $nameIdentifier . '_' . basename($linkFileXML);
                                                $path_thumb = str_replace('.pdf', '.png', $path_thumb);

                                                if (!JFile::exists($path_thumb) && JFile::exists($dir)) {
                                                    $width_thumb = $objParamsFile->thumb_max_width;
                                                    $height_thumb = $objParamsFile->thumb_max_height;

                                                    $im = new Imagick($dir . '[0]');
                                                    $im->setImageFormat("png");
                                                    $im->setImageBackgroundColor(new ImagickPixel('white'));
                                                    $im->thumbnailImage($width_thumb, $height_thumb);
                                                    $im->writeImage($path_thumb);
                                                }
                                            }
                                        }
                                    }
                                }

                                if ($data['extract'] !== 0) {
                                    $metas2 = $this->searchFileRepositoryOER($data['link'], $identifier);

                                    if ($metas2 !== false) {
                                        $path = dirname(dirname($_SERVER['SCRIPT_FILENAME']));

                                        for ($i = 0; $i < $metas2->length; $i++) {
                                            $tag = $metas2->item($i);

                                            if (($tag->nodeName == 'description') && ((strpos($tag->getAttribute('rdf:about'), '.pdf.txt') !== false))) {
                                                $linkFileXML = substr($tag->getAttribute('rdf:about'), 0, strpos($tag->getAttribute('rdf:about'), '.pdf.txt') + 8);
                                            }
                                        }

                                        $linkUFG = 'localhost:8080';

                                        if (strpos($linkFileXML, $linkUFG) !== false) {
                                            $arLinkFileXML = explode('/', $linkFileXML);

                                            $link = 'http://repositorio.bc.ufg.br/bitstream/ri/';

                                            $linkFile = $link . $arLinkFileXML[count($arLinkFileXML) - 3] . '/' . $arLinkFileXML[count($arLinkFileXML) - 2] . '/' . $arLinkFileXML[count($arLinkFileXML) - 1];
                                        }

                                        $textFile = trim($db->escape(file_get_contents($linkFile)));

                                        if ((strtotime($textFile) === 0) && (is_file($dir))) {
                                            $tikaAppPath = $path . '/plugins/fabrik_form/textextract/app/tika.jar';

                                            $command = ('java -jar ' . $tikaAppPath . ' "' . $dir . '" --text');
                                            exec($command, $execOutArray);
                                            $textFile = trim($db->escape(strip_tags(implode(' ', $execOutArray))));
                                        }

                                        $elementFile = $this->mappedElementsData($data['list'], $data['extract']);

                                        $fields .= ", {$elementFile->name}";
                                        $fieldsContent .= ", '{$textFile}'";

                                        if ($data['sync'] === 1) {
                                            $arFieldsElement[] = $elementFile->name;
                                            $arFieldsContent[] = $textFile;
                                        }
                                    }
                                }

                                $result_id = (int)$this->insertTableMultipleFieldsData($data['tableSource']->db_table_name, $fields, $fieldsContent);

                                if (is_array($arFields) && is_array($data['metadata']) && (!is_null($data['metadata']))) {
                                    foreach ($arFields as $key => $vlTarget) {
                                        foreach ($vlTarget as $vlRepeat) {
                                            $resultRepeat = $this->selectTableRepeat($tableRepeat[$key]->table_join, $tableRepeat[$key]->table_join_key, $tableRepeat[$key]->table_key, $result_id, $vlRepeat);

                                            if ($resultRepeat->total === '0') {
                                                $this->insertTableRepeat($tableRepeat[$key]->table_join, $tableRepeat[$key]->table_join_key, $tableRepeat[$key]->table_key, $result_id, $vlRepeat);
                                            }
                                        }
                                        unset($tableRepeat[$key]);
                                    }

                                    unset($arFields);
                                }

                                if (is_array($arFieldsTag) && is_array($data['metadata']) && (!is_null($data['metadata']))) {
                                    foreach ($arFieldsTag as $key => $vlTarget) {
                                        foreach ($vlTarget as $vlRepeat) {

                                            $resultRepeat = $this->selectTableRepeat($tableRepeatTag[$key]->table_join, $tableRepeatTag[$key]->table_join_key, $tableRepeatTag[$key]->table_key, $result_id, $vlRepeat);

                                            if ($resultRepeat->total === '0') {
                                                $this->insertTableRepeat($tableRepeatTag[$key]->table_join, $tableRepeatTag[$key]->table_join_key, $tableRepeatTag[$key]->table_key, $result_id, $vlRepeat);
                                            }
                                        }
                                        unset($tableRepeatTag[$key]);
                                    }

                                    unset($arFieldsTag);
                                }

                                if (($data['download'] !== 0) && ($metas2 !== false)) {
                                    $resultRepeat = $this->selectTableRepeatFileUpload($tableRepeatFile['file']->table_join, $tableRepeatFile['file']->table_join_key, $tableRepeatFile['file']->table_key, $result_id, $dirName);

                                    if ($resultRepeat->total === '0') {
                                        $this->insertTableRepeat($tableRepeatFile['file']->table_join, $tableRepeatFile['file']->table_join_key, $tableRepeatFile['file']->table_key, $result_id, $dirName);
                                    }
                                }
                            } elseif (!is_null($result_identifier->id) && ($update === 1) && ($data['sync'] === 1)) {
                                if (is_array($data['metadata']) && (!is_null($data['metadata']))) {
                                    $dom->loadHTML('<?xml encoding="utf-8" ?>' . trim($recordNode->metadata->asXML()));

                                    $i = 0;
                                    unset($arFields);
                                    unset($tableRepeat);
                                    unset($arFieldsTag);
                                    unset($tableRepeatTag);

                                    foreach ($data['metadata'] as $key => $objFields) {
                                        $tag = explode(':', $key);
                                        $metas = $dom->getElementsByTagName($tag[1]);

                                        $arLength = array_count_values($objFields);
                                        $j = 0;

                                        $fieldExtra = "";

                                        foreach ($objFields as $index => $value) {
                                            $joinModelSource = JModelLegacy::getInstance('Join', 'FabrikFEModel');
                                            $element = $this->mappedElementsData($data['list'], $value);
                                            $objParams = json_decode($element->params);

                                            if (strlen($fields) !== 0) {
                                                if ($j === 0) {
                                                    if (($objParams->database_join_display_type !== "checkbox") && ($objParams->database_join_display_type !== "multilist") && ($element->plugin !== 'tags')) {
                                                        $fields .= ", {$element->name}";
                                                        $itemFieldElement = 1;
                                                    } else {
                                                        $itemFieldElement = 0;
                                                    }
                                                } elseif (($arLength[$value] === 1) && ($objParams->database_join_display_type !== "checkbox") && ($objParams->database_join_display_type !== "multilist") && ($element->plugin !== 'tags')) {
                                                    $fields .= ", {$element->name}";
                                                    $itemFieldElement = 1;
                                                }
                                            } else {
                                                $fields .= "{$element->name}";
                                                $itemFieldElement = 1;
                                            }

                                            if (($data['sync'] === 1) && ($itemFieldElement === 1)) {
                                                $arFieldsElement[] = $element->name;
                                            }

                                            switch ($element->plugin) {
                                                case 'date':
                                                    $date = date("Y-m-d H:i:s", strtotime($db->escape($metas->item($index)->nodeValue)));

                                                    if (strlen($fieldsContent) !== 0) {
                                                        $fieldsContent .= ", '{$date}'";
                                                    } else {
                                                        $fieldsContent .= "'{$date}'";
                                                    }

                                                    if ($data['sync'] === 1) {
                                                        $arFieldsContent[] = $date;
                                                    }

                                                    if (($data['field2'] === 'dc:date') && ($data['sync'] === 1)) {
                                                        $dateSync = date("Y-m-d", strtotime($db->escape($metas->item($index)->nodeValue)));
                                                        $fieldSync = $element->name;
                                                    }

                                                    break;
                                                case 'dropdown':
                                                    $objOptions = $objParams->sub_options;

                                                    if (!(in_array($db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue))), $objOptions->sub_values)) &&
                                                        !(in_array($db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue))), $objOptions->sub_labels))) {
                                                        $objOptions->sub_values[] = ucwords(mb_strtolower($db->escape($metas->item($index)->nodeValue)));
                                                        $objOptions->sub_labels[] = ucwords(mb_strtolower($db->escape($metas->item($index)->nodeValue)));
                                                    }

                                                    $parans_update = $this->updateTableElement($value, $objParams);

                                                    if ($parans_update) {
                                                        if (strlen($fieldsContent) !== 0) {
                                                            $fieldsContent .= ", '{$db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue)))}'";
                                                        } else {
                                                            $fieldsContent .= "'{$db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue)))}'";
                                                        }
                                                    }

                                                    if ($data['sync'] === 1) {
                                                        $arFieldsContent[] = $db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue)));
                                                    }

                                                    break;
                                                case 'databasejoin':
                                                    if ((($objParams->database_join_display_type === "dropdown") || ($objParams->database_join_display_type === "radio") ||
                                                        ($objParams->database_join_display_type === "auto-complete"))) {
                                                        $exist_data_target = $this->existTargetTableData($objParams->join_db_name, $objParams->join_val_column, $metas->item($index)->nodeValue);

                                                        if (count($exist_data_target) === 0) {
                                                            $result = $this->insertTable($objParams->join_db_name, $objParams->join_val_column, $metas->item($index)->nodeValue);
                                                        } else {
                                                            $result = $exist_data_target['id'];
                                                        }

                                                        if ((strlen($fieldsContent) !== 0) && ($result !== false)) {
                                                            $fieldsContent .= ", '{$result}'";
                                                        } elseif ((strlen($fieldsContent) === 0) && ($result !== false)) {
                                                            $fieldsContent .= "'{$result}'";
                                                        } elseif ((strlen($fieldsContent) === 0) && ($result === false)) {
                                                            $fieldsContent .= ",''";
                                                        } elseif ((strlen($fieldsContent) !== 0) && ($result === false)) {
                                                            $fieldsContent .= "''";
                                                        }

                                                        if ($data['sync'] === 1) {
                                                            $arFieldsContent[] = $result;
                                                        }
                                                    } elseif ((($objParams->database_join_display_type === "checkbox") || ($objParams->database_join_display_type === "multilist"))) {
                                                        $tableRepeat[$tag[1]] = $joinModelSource->getJoinFromKey('element_id', $value);

                                                        for ($numDBJ = 0; $numDBJ < $metas->length; $numDBJ++) {
                                                            if ($tag[1] === 'creator') {
                                                                $arTagText = explode(',', $metas->item($numDBJ)->nodeValue);
                                                                $tagText = trim($arTagText[1]) . ' ' . trim($arTagText[0]);
                                                            } else {
                                                                $tagText = trim($metas->item($numDBJ)->nodeValue);
                                                            }

                                                            $exist_data_target = $this->existTargetTableData($objParams->join_db_name, $objParams->join_val_column, $tagText);

                                                            if (count($exist_data_target) === 0) {
                                                                $arFields[$tag[1]][] = (int)$this->insertTable($objParams->join_db_name, $objParams->join_val_column, $tagText);
                                                            } else {
                                                                $arFields[$tag[1]][] = (int)$exist_data_target['id'];
                                                            }
                                                        }
                                                    }

                                                    break;
                                                case 'tags':
                                                    $nameTableTags = $ext . 'tags';

                                                    if ($objParams->tags_dbname === $nameTableTags) {
                                                        $tagSelestField = 'title';
                                                    }

                                                    $tableRepeatTag[$tag[1]] = $joinModelSource->getJoinFromKey('element_id', $value);

                                                    for ($numDBJ = 0; $numDBJ < $metas->length; $numDBJ++) {
                                                        $tagText = trim(ucfirst($metas->item($numDBJ)->nodeValue));

                                                        $exist_data_target = $this->existTargetTableData($objParams->tags_dbname, $tagSelestField, $tagText);

                                                        if ($objParams->tags_dbname === $nameTableTags) {
                                                            $tagTextExtra = $this->removeAccentsSpecialCharacters(trim(strtolower($metas->item($numDBJ)->nodeValue)));
                                                            date_default_timezone_set('America/Sao_Paulo');
                                                            $data['registerDate'] = date("Y-m-d H:i:s");

                                                            $user = JFactory::getUser();
                                                            $data['users'] = $user->get('id');

                                                            $tagInsertField = "`parent_id`, `level`, `path`, `title`, `alias`, `published`, `checked_out_time`, `access`, `created_user_id`,
                                             `created_time`, `modified_time`, `publish_up`, `publish_down`";

                                                            $tagInsertData = "'1','1','{$tagTextExtra}','{$tagText}','{$tagTextExtra}','1','{$data['registerDate']}','1','{$data['users']}',
                                            '{$data['registerDate']}','{$data['registerDate']}','{$data['registerDate']}','{$data['registerDate']}'";
                                                        }

                                                        if (count($exist_data_target) === 0) {
                                                            $arFieldsTag[$tag[1]][] = (int)$this->insertTableMultipleFieldsData($objParams->tags_dbname, $tagInsertField, $tagInsertData);
                                                        } else {
                                                            $arFieldsTag[$tag[1]][] = (int)$exist_data_target['id'];
                                                        }
                                                    }

                                                    break;
                                                default:
                                                    $num = $index + 1;

                                                    if (strlen($fieldsContent) !== 0) {
                                                        if ($j === 0) {
                                                            $fieldExtra .= $db->escape($metas->item($index)->nodeValue);
                                                        } elseif ($arLength[$value] === 1) {
                                                            $fieldExtra = $db->escape($metas->item($j)->nodeValue);
                                                        } else {
                                                            $fieldExtra .= '|' . $db->escape($metas->item($index)->nodeValue);
                                                        }

                                                        if (($num === $arLength[$value]) || ($arLength[$value] === 1)) {
                                                            $fieldsContent .= ", '{$fieldExtra}'";
                                                        }
                                                    } else {
                                                        $fieldExtra .= $db->escape($metas->item($index)->nodeValue);

                                                        if ($num === $arLength[$value]) {
                                                            $fieldsContent .= "'{$fieldExtra}'";
                                                        }
                                                    }

                                                    if ($data['sync'] === 1) {
                                                        $arFieldsContent[] = $fieldExtra;
                                                    }

                                                    break;
                                            }

                                            $j += 1;
                                        }

                                        $i += 1;
                                    }
                                }

                                if ($data['download'] !== 0) {
                                    $metas2 = $this->searchFileRepositoryOER($data['link'], $identifier);

                                    $dirName = '';
                                    unset($tableRepeatFile);

                                    if ($metas2 !== false) {
                                        $path = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
                                        for ($i = 0; $i < $metas2->length; $i++) {
                                            $tag = $metas2->item($i);

                                            if (($tag->nodeName == 'description') && ((strpos($tag->getAttribute('rdf:about'), '.pdf') !== false) &&
                                                    (strpos($tag->getAttribute('rdf:about'), '.pdf.') === false))) {
                                                $linkFileXML = substr($tag->getAttribute('rdf:about'), 0, strpos($tag->getAttribute('rdf:about'), '.pdf') + 4);
                                            }
                                        }

                                        $file_ext = explode('.', $linkFileXML);

                                        if (count($file_ext) === 2) {
                                            $elementFile = $this->mappedElementsData($data['list'], $data['download']);
                                            $objParamsFile = json_decode($elementFile->params);

                                            $urlDirectory = $objParamsFile->ul_directory;

                                            $nameIdentifier = str_replace('/', '_', str_replace(':', '_', str_replace('.', '_', $identifier)));

                                            $dir = $path . $urlDirectory . $nameIdentifier . '_' . basename($linkFileXML);

                                            if ($objParamsFile->ajax_upload === '0') {
                                                $fields .= ", {$elementFile->name}";

                                                $dirName = $urlDirectory . $nameIdentifier . '_' . basename($linkFileXML);
                                                $fieldsContent .= ", '{$dirName}'";

                                                if ($data['sync'] === 1) {
                                                    $arFieldsElement[] = $elementFile->name;
                                                    $arFieldsContent[] = $dirName;
                                                }
                                            } elseif ($objParamsFile->ajax_upload === '1') {
                                                $joinModelSourceFile = JModelLegacy::getInstance('Join', 'FabrikFEModel');
                                                $tableRepeatFile['file'] = $joinModelSourceFile->getJoinFromKey('element_id', $data['download']);
                                                $dirName = $db->escape(str_replace('/', '\\', $urlDirectory . $nameIdentifier . '_' . basename($linkFileXML)));
                                            }

                                            $linkUFG = 'localhost:8080';

                                            if (strpos($linkFileXML, $linkUFG) !== false) {
                                                $arLinkFileXML = explode('/', $linkFileXML);

                                                $link = 'http://repositorio.bc.ufg.br/bitstream/ri/';

                                                $linkFileXML = $link . $arLinkFileXML[count($arLinkFileXML) - 3] . '/' . $arLinkFileXML[count($arLinkFileXML) - 2] . '/' . $arLinkFileXML[count($arLinkFileXML) - 1];
                                            }

                                            copy($linkFileXML, $dir);

                                            if ($objParamsFile->fu_make_pdf_thumb === '1') {
                                                $path_thumb = $path . '/' . $objParamsFile->thumb_dir . '/' . $nameIdentifier . '_' . basename($linkFileXML);
                                                $path_thumb = str_replace('.pdf', '.png', $path_thumb);

                                                if (!JFile::exists($path_thumb) && JFile::exists($dir)) {
                                                    $width_thumb = $objParamsFile->thumb_max_width;
                                                    $height_thumb = $objParamsFile->thumb_max_height;

                                                    $im = new Imagick($dir . '[0]');
                                                    $im->setImageFormat("png");
                                                    $im->setImageBackgroundColor(new ImagickPixel('white'));
                                                    $im->thumbnailImage($width_thumb, $height_thumb);
                                                    $im->writeImage($path_thumb);
                                                }
                                            }
                                        }
                                    }
                                }

                                if ($data['extract'] !== 0) {
                                    $metas2 = $this->searchFileRepositoryOER($data['link'], $identifier);

                                    if ($metas2 !== false) {
                                        $path = dirname(dirname($_SERVER['SCRIPT_FILENAME']));

                                        for ($i = 0; $i < $metas2->length; $i++) {
                                            $tag = $metas2->item($i);

                                            if (($tag->nodeName == 'description') && ((strpos($tag->getAttribute('rdf:about'), '.pdf.txt') !== false))) {
                                                $linkFileXML = substr($tag->getAttribute('rdf:about'), 0, strpos($tag->getAttribute('rdf:about'), '.pdf.txt') + 8);
                                            }
                                        }

                                        $linkUFG = 'localhost:8080';

                                        if (strpos($linkFileXML, $linkUFG) !== false) {
                                            $arLinkFileXML = explode('/', $linkFileXML);

                                            $link = 'http://repositorio.bc.ufg.br/bitstream/ri/';

                                            $linkFile = $link . $arLinkFileXML[count($arLinkFileXML) - 3] . '/' . $arLinkFileXML[count($arLinkFileXML) - 2] . '/' . $arLinkFileXML[count($arLinkFileXML) - 1];
                                        }

                                        $textFile = trim($db->escape(file_get_contents($linkFile)));

                                        if ((strtotime($textFile) === 0) && (is_file($dir))) {
                                            $tikaAppPath = $path . '/plugins/fabrik_form/textextract/app/tika.jar';

                                            $command = ('java -jar ' . $tikaAppPath . ' "' . $dir . '" --text');
                                            exec($command, $execOutArray);
                                            $textFile = trim($db->escape(strip_tags(implode(' ', $execOutArray))));
                                        }

                                        $elementFile = $this->mappedElementsData($data['list'], $data['extract']);

                                        $fields .= ", {$elementFile->name}";
                                        $fieldsContent .= ", '{$textFile}'";

                                        if ($data['sync'] === 1) {
                                            $arFieldsElement[] = $elementFile->name;
                                            $arFieldsContent[] = $textFile;
                                        }
                                    }
                                }

                                $this->updateTableMultipleFieldsData($data['tableSource']->db_table_name, $result_identifier->id, $arFieldsElement, $arFieldsContent);

                                if (is_array($arFields) && is_array($data['metadata']) && (!is_null($data['metadata']))) {
                                    foreach ($arFields as $key => $vlTarget) {
                                        foreach ($vlTarget as $vlRepeat) {
                                            $resultRepeat = $this->selectTableRepeat($tableRepeat[$key]->table_join, $tableRepeat[$key]->table_join_key, $tableRepeat[$key]->table_key, $result_identifier->id, $vlRepeat);

                                            if ($resultRepeat->total === '0') {
                                                $this->insertTableRepeat($tableRepeat[$key]->table_join, $tableRepeat[$key]->table_join_key, $tableRepeat[$key]->table_key, $result_identifier->id, $vlRepeat);
                                            }
                                        }
                                        unset($tableRepeat[$key]);
                                    }

                                    unset($arFields);
                                }

                                if (is_array($arFieldsTag) && is_array($data['metadata']) && (!is_null($data['metadata']))) {
                                    foreach ($arFieldsTag as $key => $vlTarget) {
                                        foreach ($vlTarget as $vlRepeat) {

                                            $resultRepeat = $this->selectTableRepeat($tableRepeatTag[$key]->table_join, $tableRepeatTag[$key]->table_join_key, $tableRepeatTag[$key]->table_key, $result_identifier->id, $vlRepeat);

                                            if ($resultRepeat->total === '0') {
                                                $this->insertTableRepeat($tableRepeatTag[$key]->table_join, $tableRepeatTag[$key]->table_join_key, $tableRepeatTag[$key]->table_key, $result_identifier->id, $vlRepeat);
                                            }
                                        }
                                        unset($tableRepeatTag[$key]);
                                    }

                                    unset($arFieldsTag);
                                }

                                if (($data['download'] !== 0) && ($metas2 !== false)) {
                                    $resultRepeat = $this->selectTableRepeatFileUpload($tableRepeatFile['file']->table_join, $tableRepeatFile['file']->table_join_key, $tableRepeatFile['file']->table_key, $result_identifier->id, $dirName);

                                    if ($resultRepeat->total === '0') {
                                        $this->insertTableRepeat($tableRepeatFile['file']->table_join, $tableRepeatFile['file']->table_join_key, $tableRepeatFile['file']->table_key, $result_identifier->id, $dirName);
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $xmlNode = $xmlObj->ListRecords;
            }

            if (!$xmlNode->resumptionToken) {
                $totalRecords = $totalRecords + $currentRecords;
            } else {
                $resumptionToken = $xmlNode->resumptionToken;

                $currentRecords = $currentRecords - 1;
                $totalRecords = $totalRecords + $currentRecords;
            }

            $table = $ext . 'fabrik_harvesting';
            $this->updateDataTableSource($table, $totalRecords, $data['id'], 'page_xml');

            $fetchCounter = $fetchCounter + 1;
        }

        return true;
    }

    /**
     * Ajax method that will check if the link (address) of the repository is valid if it is really a repository.
     *
     * @return void
     */
    public function repositoryValidation()
    {
        $app = JFactory::getApplication();
        $db = JFactory::getDbo();

        $link = $db->escape($app->input->getString("link"));

        $url = $link . "?verb=Identify";

        if (!simplexml_load_file($url)) {
            echo "0";

            $app->close();
        }

        $url = $link . "?verb=ListMetadataFormats";

        if (!simplexml_load_file($url)) {
            echo "0";

            $app->close();
        }

        $url = $link . "?verb=ListSets";

        if (!simplexml_load_file($url)) {
            echo "0";

            $app->close();
        }

        $url = $link . '?verb=ListRecords&metadataPrefix=oai_dc';

        if (!simplexml_load_file($url)) {
            echo "0";

            $app->close();
        }

        $url = $link . '?verb=ListRecords&metadataPrefix=ore';

        if (!simplexml_load_file($url)) {
            echo "0";

            $app->close();
        }

        echo "1";

        $app->close();
    }

    /**
     * Method that brings the data of all the elements that were mapped on the form.
     *
     * @param $id_form
     * @param $arIdElementMap
     * @return void
     */
    public function mappedElementsData($id_form, $arIdElementMap)
    {
        $db = JFactory::getDbo();

        try {
            $sql = "SELECT element.* FROM
                    #__fabrik_elements AS element
                    LEFT JOIN #__fabrik_formgroup AS fg ON element.group_id = fg.group_id
                    LEFT JOIN #__fabrik_lists AS list ON fg.form_id = list.form_id
                WHERE
                    list.published = 1 AND
                    list.form_id = $id_form  AND
                    element.id IN ($arIdElementMap)";

            $db->setQuery($sql);

            return $db->loadObject();
        } catch (Exception $exc) {
            $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR5');
            $type_message = 'warning';
            $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools';

            $this->setRedirect($site_message, $message, $type_message);
        }
    }

    /**
     * Method that updates the table of elements with json already modified.
     *
     * @param $id_element
     * @param $element_params
     * @return bool
     */
    public function updateTableElement($id_element, $element_params)
    {
        $db = JFactory::getDbo();

        $paramsDB = json_encode($element_params);

        try {
            $db->transactionStart();

            $query1 = "UPDATE `#__fabrik_elements`
                    SET
                    `params` = '{$db->escape($paramsDB)}'
                    WHERE `id` = {$id_element};";

            $db->setQuery($query1);

            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method that inserts content in the fields in a given table passed by a parameter.
     *
     * @param $table_taget
     * @param $field_target
     * @param $data_target
     * @return bool|mixed
     */
    public function insertTable($table, $field, $data)
    {
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$table}` (`{$field}`) VALUES ('{$db->escape($data)}');";

            $db->setQuery($query);

            $db->execute();

            $id = $db->insertid();

            $db->transactionCommit();

            return $id;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method that inserts into a table with multiple fields and multiple data.
     *
     * @param $table
     * @param $field
     * @param $data
     * @return bool|mixed
     */
    public function insertTableMultipleFieldsData($table, $field, $data)
    {
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$table}` ({$field}) VALUES ({$data});";

            $db->setQuery($query);

            $db->execute();

            $id = $db->insertid();

            $db->transactionCommit();

            return $id;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return '0';
        }
    }

    /**
     * Method that checks if there is data in a repetition table.
     *
     * @param $table
     * @param $parent_id
     * @param $target
     * @param $parent_data
     * @param $target_data
     * @return mixed
     */
    public function selectTableRepeat($table, $parent_id, $target, $parent_data, $target_data)
    {
        $db = JFactory::getDbo();

        try {
            $sql = "SELECT
                    COUNT(repeat.id) AS total
                FROM
                    {$table} AS `repeat`
                WHERE
                    repeat.{$parent_id} = {$parent_data}
                 AND
                    repeat.{$target} = {$target_data}";

            $db->setQuery($sql);

            return $db->loadObject();
        } catch (Exception $exc) {
            $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR5');
            $type_message = 'warning';
            $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools';

            $this->setRedirect($site_message, $message, $type_message);
        }
    }

    /**
     * Method that inserts its fields and data into a repetition table.
     *
     * @param $table
     * @param $field
     * @param $data
     * @return mixed|string
     */
    public function insertTableRepeat($table, $parent_id, $target, $parent_data, $target_data)
    {
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$table}` ({$parent_id}, $target) VALUES ('{$parent_data}','{$target_data}');";

            $db->setQuery($query);

            $db->execute();

            $id = $db->insertid();

            $db->transactionCommit();

            return $id;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return '0';
        }
    }

    /**
     * Module that checks if there is already data in the database of the repetition structure of the plugins file upload.
     *
     * @param $table
     * @param $parent_id
     * @param $target
     * @param $parent_data
     * @param $target_data
     * @return mixed
     */
    public function selectTableRepeatFileUpload($table, $parent_id, $target, $parent_data, $target_data)
    {
        $db = JFactory::getDbo();

        try {
            $sql = "SELECT
                    COUNT(repeat.id) AS total
                FROM
                    {$table} AS `repeat`
                WHERE
                    repeat.{$parent_id} = {$parent_data}
                 AND
                    repeat.{$target} = '{$target_data}'";

            $db->setQuery($sql);

            return $db->loadObject();
        } catch (Exception $exc) {
            $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR5');
            $type_message = 'warning';
            $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools';

            $this->setRedirect($site_message, $message, $type_message);
        }
    }

    /**
     * Ajax method that retrieves the data to be edited.
     *
     * @return void
     */
    public function editHarvesting()
    {
        $app = JFactory::getApplication();
        $db = JFactory::getDbo();

        $id = $app->input->getInt("id");

        $sql = "SELECT
                    harvest.id, 
                    harvest.repository, 
                    harvest.list, 
                    harvest.dowload_file, 
                    harvest.extract, 
                    harvest.syncronism, 
                    harvest.field1, 
                    harvest.field2, 
                    harvest.map_header, 
                    harvest.map_metadata
                FROM
                    #__fabrik_harvesting AS harvest
                WHERE
                    harvest.id = {$id}";

        $db->setQuery($sql);

        $result = $db->loadObject();

        if (count($result) !== 0) {
            $data['id'] = $result->id;
            $data['repository'] = $result->repository;
            $data['list'] = $result->list;
            $data['dowload_file'] = $result->dowload_file;
            $data['extract'] = $result->extract;
            $data['syncronism'] = $result->syncronism;
            $data['field1'] = $result->field1;
            $data['field2'] = $result->field2;
            $data['map_header'] = json_decode($result->map_header);
            $data['map_metadata'] = json_decode($result->map_metadata);

            $sql = "SELECT
                element.id,
                element.label,
                element.name,
                list.db_table_name AS `table`,
                element.`plugin`,
                element.params,
                list.params as paramList
                FROM
                #__fabrik_elements AS element
                LEFT JOIN #__fabrik_formgroup AS fgroup ON element.group_id = fgroup.group_id
                LEFT JOIN #__fabrik_lists AS list ON fgroup.form_id = list.form_id
                WHERE
                list.published = 1 AND
                fgroup.form_id = {$result->list}
                ORDER BY
                element.label ASC;";

            $db->setQuery($sql);

            $list = $db->loadObjectList();

            if (count($list) > 0) {
                $data['element'] = $list;
            }

            echo json_encode($data);
        } else {
            echo '0';
        }

        $app->close();
    }

    /**
     * Method that checks whether the data in the table exists.
     * @param $table
     * @param $field
     * @param $data
     * @return mixed
     */
    public function checkDataTableExist($table, $field, $data, $fieldDate = NULL, $date = NULL)
    {
        $db = JFactory::getDbo();

        try {
            $sql = "SELECT
                    `repeat`.id	AS id
                FROM
                    {$table} AS `repeat`
                WHERE
                    repeat.{$field} = '{$data}' ";

            if (($fieldDate !== NULL) && (strlen($fieldDate) !== 0)) {
                $sql .= "and DATE_FORMAT(repeat.{$fieldDate},'%Y-%m-%d') < '{$date}'";
            }

            $db->setQuery($sql);

            return $db->loadObject();
        } catch (Exception $exc) {
            $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR5');
            $type_message = 'warning';
            $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools';

            $this->setRedirect($site_message, $message, $type_message);
        }
    }

    /**
     * update data for multiple table fields
     *
     * @param $table
     * @param $field
     * @param $data
     * @return string
     */
    public function updateTableMultipleFieldsData($table, $id, $field, $data)
    {
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $query = "UPDATE `{$table}` SET ";

            foreach ($field as $key => $value) {
                if ($key === 0) {
                    $query .= "`{$value}` = '{$data[$key]}'";
                } else {
                    $query .= ", `{$value}` = '{$data[$key]}'";
                }
            }

            $query .= " WHERE `id` = {$id};";

            $db->setQuery($query);

            $db->execute();

            $db->transactionCommit();

            return '1';
        } catch (Exception $exc) {
            $db->transactionRollback();

            return '0';
        }
    }

    /**
     * Validating method or link provided by the user in the form.
     *
     * @param $link
     * @return bool
     */
    public function repositoryValidator($link)
    {
        $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR3');
        $type_message = 'warning';
        $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools&tab=3';

        $url = $link . '?verb=ListRecords&metadataPrefix=oai_dc';

        if (!simplexml_load_file($url)) {
            $this->setRedirect($site_message, $message, $type_message);

            return false;
        }
    }

    /**
     * Method that runs the button engine for the list of options already saved in the database.
     *
     * @param $data
     * @return bool
     */
    public function runListHarvesting($data)
    {
        set_time_limit(0);

        $db = JFactory::getDbo();
        $config = JFactory::getConfig();

        $ext = $config->get('dbprefix');

        $sql = "SELECT
                    harvest.*
                FROM
                    #__fabrik_harvesting AS harvest
                WHERE
                    harvest.id = {$data['id']}";

        $db->setQuery($sql);

        $harvesting = $db->loadObject();

        $data['tableSource'] = $this->tableSource($harvesting->list);

        $totalRecords = $harvesting->page_xml;
        $currentRecords = 0;

        $baseURL = $harvesting->repository . '?verb=ListRecords';

        $initialParams = '&resumptionToken=oai_dc////' . $totalRecords;

        $resumptionBase = '&resumptionToken=';
        $resumptionToken = 'initial';

        $fetchCounter = 1;

        while ($resumptionToken != '') {
            if ($fetchCounter == 1) {
                $url = $baseURL . $initialParams;
                $resumptionToken = '';
            } else {
                $url = $baseURL . $resumptionBase . $resumptionToken;
            }

            $xmlObj = simplexml_load_file($url);

            if ($xmlObj) {
                $xmlNode = $xmlObj->ListRecords;

                if ($fetchCounter === 1) {
                    $arNumLineXML = get_object_vars($xmlNode->resumptionToken);
                    $lineNum = (int)$arNumLineXML['@attributes']['completeListSize'];

                    $table = $ext . 'fabrik_harvesting';
                    $this->updateDataTableSource($table, $lineNum, $harvesting->id, 'line_num');
                }

                if ($xmlNode->count() !== 0) {
                    $currentRecords = count($xmlNode->children());

                    $dom = new DOMDocument();

                    $data['header'] = json_decode($harvesting->map_header);

                    $data['metadata'] = json_decode($harvesting->map_metadata);

                    foreach ($xmlNode->record as $recordNode) {
                        $fields = '';
                        $fieldsContent = '';
                        unset($arFieldsElement);
                        unset($arFieldsContent);

                        if (is_object($data['header']) && (!is_null($data['header']))) {

                            $dom->loadHTML('<?xml encoding="utf-8" ?>' . trim($recordNode->header->asXML()));
                            $i = 0;

                            foreach ($data['header'] as $key => $value) {
                                $metas = $dom->getElementsByTagName($key);


                                $element = $this->mappedElementsData($harvesting->list, $value);

                                if ($i !== 0) {
                                    $fields .= ", {$element->name}";
                                } else {
                                    $fields .= "{$element->name}";
                                }

                                if ($harvesting->syncronism === '1') {
                                    $arFieldsElement[] = $element->name;
                                }

                                switch ($element->plugin) {
                                    case 'date':
                                        $date = date("Y-m-d H:i:s", strtotime($db->escape($metas->item(0)->nodeValue)));

                                        if ($i !== 0) {
                                            $fieldsContent .= ", '{$date}'";
                                        } else {
                                            $fieldsContent .= "'{$date}'";
                                        }

                                        if ($harvesting->syncronism === '1') {
                                            $arFieldsContent[] = $date;
                                        }

                                        if (($harvesting->field2 === 'datestamp') && ($harvesting->syncronism === '1')) {
                                            $dateSync = date("Y-m-d", strtotime($db->escape($metas->item(0)->nodeValue)));;
                                            $fieldSync = $element->name;
                                        }

                                        break;
                                    default:
                                        if ($i !== 0) {
                                            $fieldsContent .= ", '{$db->escape($metas->item(0)->nodeValue)}'";
                                        } else {
                                            $fieldsContent .= "'{$db->escape($metas->item(0)->nodeValue)}'";
                                        }

                                        if ($harvesting->syncronism === '1') {
                                            $arFieldsContent[] = $db->escape($metas->item(0)->nodeValue);
                                        }

                                        if ($key === 'identifier') {
                                            $identifier = $db->escape($metas->item(0)->nodeValue);
                                            $fieldIdentifier = $element->name;
                                        }

                                        break;
                                }

                                $i += 1;
                            }

                            if ($harvesting->syncronism === '1') {
                                $result_identifier = $this->checkDataTableExist($data['tableSource']->db_table_name, $fieldIdentifier, $identifier, $fieldSync, $dateSync);
                                $update = 1;
                            } else {
                                $result_identifier = $this->checkDataTableExist($data['tableSource']->db_table_name, $fieldIdentifier, $identifier);
                                $update = 0;
                            }

                            if (is_null($result_identifier->id) && ($update === 0)) {
                                if (is_object($data['metadata']) && (!is_null($data['metadata']))) {
                                    $dom->loadHTML('<?xml encoding="utf-8" ?>' . trim($recordNode->metadata->asXML()));

                                    $i = 0;
                                    unset($arFields);
                                    unset($tableRepeat);
                                    unset($arFieldsTag);
                                    unset($tableRepeatTag);

                                    foreach ($data['metadata'] as $key => $objFields) {
                                        $tag = explode(':', $key);
                                        $metas = $dom->getElementsByTagName($tag[1]);

                                        $arLength = array_count_values($objFields);
                                        $j = 0;

                                        $fieldExtra = "";

                                        foreach ($objFields as $index => $value) {
                                            $joinModelSource = JModelLegacy::getInstance('Join', 'FabrikFEModel');
                                            $element = $this->mappedElementsData($harvesting->list, $value);
                                            $objParams = json_decode($element->params);

                                            if (strlen($fields) !== 0) {
                                                if ($j === 0) {
                                                    if (($objParams->database_join_display_type !== "checkbox") && ($objParams->database_join_display_type !== "multilist") && ($element->plugin !== 'tags')) {
                                                        $fields .= ", {$element->name}";
                                                        $itemFieldElement = 1;
                                                    } else {
                                                        $itemFieldElement = 0;
                                                    }
                                                } elseif (($arLength[$value] === 1) && ($objParams->database_join_display_type !== "checkbox") && ($objParams->database_join_display_type !== "multilist") && ($element->plugin !== 'tags')) {
                                                    $fields .= ", {$element->name}";
                                                    $itemFieldElement = 1;
                                                }
                                            } else {
                                                $fields .= "{$element->name}";
                                                $itemFieldElement = 1;
                                            }

                                            if (($harvesting->syncronism === '1') && ($itemFieldElement === '1')) {
                                                $arFieldsElement[] = $element->name;
                                            }

                                            switch ($element->plugin) {
                                                case 'date':
                                                    $date = date("Y-m-d H:i:s", strtotime($db->escape($metas->item($index)->nodeValue)));

                                                    if (strlen($fieldsContent) !== 0) {
                                                        $fieldsContent .= ", '{$date}'";
                                                    } else {
                                                        $fieldsContent .= "'{$date}'";
                                                    }

                                                    if ($harvesting->syncronism === '1') {
                                                        $arFieldsContent[] = $date;
                                                    }

                                                    if (($harvesting->field2 === 'dc:date') && ($harvesting->syncronism === '1')) {
                                                        $dateSync = date("Y-m-d", strtotime($db->escape($metas->item($index)->nodeValue)));
                                                        $fieldSync = $element->name;
                                                    }

                                                    break;
                                                case 'dropdown':
                                                    $objOptions = $objParams->sub_options;

                                                    if (!(in_array($db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue))), $objOptions->sub_values)) &&
                                                        !(in_array($db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue))), $objOptions->sub_labels))) {
                                                        $objOptions->sub_values[] = ucwords(mb_strtolower($db->escape($metas->item($index)->nodeValue)));
                                                        $objOptions->sub_labels[] = ucwords(mb_strtolower($db->escape($metas->item($index)->nodeValue)));
                                                    }

                                                    $parans_update = $this->updateTableElement($value, $objParams);

                                                    if ($parans_update) {
                                                        if (strlen($fieldsContent) !== 0) {
                                                            $fieldsContent .= ", '{$db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue)))}'";
                                                        } else {
                                                            $fieldsContent .= "'{$db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue)))}'";
                                                        }
                                                    }

                                                    if ($harvesting->syncronism === '1') {
                                                        $arFieldsContent[] = $db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue)));
                                                    }

                                                    break;
                                                case 'databasejoin':
                                                    if ((($objParams->database_join_display_type === "dropdown") || ($objParams->database_join_display_type === "radio") ||
                                                        ($objParams->database_join_display_type === "auto-complete"))) {
                                                        $exist_data_target = $this->existTargetTableData($objParams->join_db_name, $objParams->join_val_column, $metas->item($index)->nodeValue);

                                                        if (count($exist_data_target) === 0) {
                                                            $result = $this->insertTable($objParams->join_db_name, $objParams->join_val_column, $metas->item($index)->nodeValue);
                                                        } else {
                                                            $result = $exist_data_target['id'];
                                                        }

                                                        if ((strlen($fieldsContent) !== 0) && ($result !== false)) {
                                                            $fieldsContent .= ", '{$result}'";
                                                        } elseif ((strlen($fieldsContent) === 0) && ($result !== false)) {
                                                            $fieldsContent .= "'{$result}'";
                                                        } elseif ((strlen($fieldsContent) === 0) && ($result === false)) {
                                                            $fieldsContent .= ",''";
                                                        } elseif ((strlen($fieldsContent) !== 0) && ($result === false)) {
                                                            $fieldsContent .= "''";
                                                        }

                                                        if ($harvesting->syncronism === '1') {
                                                            $arFieldsContent[] = $result;
                                                        }
                                                    } elseif ((($objParams->database_join_display_type === "checkbox") || ($objParams->database_join_display_type === "multilist"))) {
                                                        $tableRepeat[$tag[1]] = $joinModelSource->getJoinFromKey('element_id', $value);

                                                        for ($numDBJ = 0; $numDBJ < $metas->length; $numDBJ++) {
                                                            if ($tag[1] === 'creator') {
                                                                $arTagText = explode(',', $metas->item($numDBJ)->nodeValue);
                                                                $tagText = trim($arTagText[1]) . ' ' . trim($arTagText[0]);
                                                            } else {
                                                                $tagText = trim($metas->item($numDBJ)->nodeValue);
                                                            }

                                                            $exist_data_target = $this->existTargetTableData($objParams->join_db_name, $objParams->join_val_column, $tagText);

                                                            if (count($exist_data_target) === 0) {
                                                                $arFields[$tag[1]][] = (int)$this->insertTable($objParams->join_db_name, $objParams->join_val_column, $tagText);
                                                            } else {
                                                                $arFields[$tag[1]][] = (int)$exist_data_target['id'];
                                                            }
                                                        }
                                                    }

                                                    break;
                                                case 'tags':
                                                    $nameTableTags = $ext . 'tags';

                                                    if ($objParams->tags_dbname === $nameTableTags) {
                                                        $tagSelestField = 'title';
                                                    }

                                                    $tableRepeatTag[$tag[1]] = $joinModelSource->getJoinFromKey('element_id', $value);

                                                    for ($numDBJ = 0; $numDBJ < $metas->length; $numDBJ++) {
                                                        $tagText = trim(ucfirst($metas->item($numDBJ)->nodeValue));

                                                        $exist_data_target = $this->existTargetTableData($objParams->tags_dbname, $tagSelestField, $tagText);

                                                        if ($objParams->tags_dbname === $nameTableTags) {
                                                            $tagTextExtra = $this->removeAccentsSpecialCharacters(trim(strtolower($metas->item($numDBJ)->nodeValue)));
                                                            date_default_timezone_set('America/Sao_Paulo');
                                                            $data['registerDate'] = date("Y-m-d H:i:s");

                                                            $user = JFactory::getUser();
                                                            $data['users'] = $user->get('id');

                                                            $tagInsertField = "`parent_id`, `level`, `path`, `title`, `alias`, `published`, `checked_out_time`, `access`, `created_user_id`,
										 `created_time`, `modified_time`, `publish_up`, `publish_down`";

                                                            $tagInsertData = "'1','1','{$tagTextExtra}','{$tagText}','{$tagTextExtra}','1','{$data['registerDate']}','1','{$data['users']}',
										'{$data['registerDate']}','{$data['registerDate']}','{$data['registerDate']}','{$data['registerDate']}'";
                                                        }

                                                        if (count($exist_data_target) === 0) {
                                                            $arFieldsTag[$tag[1]][] = (int)$this->insertTableMultipleFieldsData($objParams->tags_dbname, $tagInsertField, $tagInsertData);
                                                        } else {
                                                            $arFieldsTag[$tag[1]][] = (int)$exist_data_target['id'];
                                                        }
                                                    }

                                                    break;
                                                default:
                                                    $num = $index + 1;

                                                    if (strlen($fieldsContent) !== 0) {
                                                        if ($j === 0) {
                                                            $fieldExtra .= $db->escape($metas->item($index)->nodeValue);
                                                        } elseif ($arLength[$value] === 1) {
                                                            $fieldExtra = $db->escape($metas->item($j)->nodeValue);
                                                        } else {
                                                            $fieldExtra .= '|' . $db->escape($metas->item($index)->nodeValue);
                                                        }

                                                        if (($num === $arLength[$value]) || ($arLength[$value] === 1)) {
                                                            $fieldsContent .= ", '{$fieldExtra}'";
                                                        }
                                                    } else {
                                                        $fieldExtra .= $db->escape($metas->item($index)->nodeValue);

                                                        if ($num === $arLength[$value]) {
                                                            $fieldsContent .= "'{$fieldExtra}'";
                                                        }
                                                    }

                                                    if ($harvesting->syncronism === '1') {
                                                        $arFieldsContent[] = $fieldExtra;
                                                    }

                                                    break;
                                            }

                                            $j += 1;
                                        }

                                        $i += 1;
                                    }
                                }

                                if ($harvesting->dowload_file !== '0') {
                                    $metas2 = $this->searchFileRepositoryOER($harvesting->repository, $identifier);

                                    $dirName = '';
                                    unset($tableRepeatFile);

                                    if ($metas2 !== false) {
                                        $path = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
                                        for ($i = 0; $i < $metas2->length; $i++) {
                                            $tag = $metas2->item($i);

                                            if (($tag->nodeName == 'description') && ((strpos($tag->getAttribute('rdf:about'), '.pdf') !== false) &&
                                                    (strpos($tag->getAttribute('rdf:about'), '.pdf.') === false))) {
                                                $linkFileXML = substr($tag->getAttribute('rdf:about'), 0, strpos($tag->getAttribute('rdf:about'), '.pdf') + 4);
                                            }
                                        }

                                        $file_ext = explode('.', $linkFileXML);

                                        if (count($file_ext) === 2) {
                                            $elementFile = $this->mappedElementsData($harvesting->list, $harvesting->dowload_file);
                                            $objParamsFile = json_decode($elementFile->params);

                                            $urlDirectory = $objParamsFile->ul_directory;

                                            $nameIdentifier = str_replace('/', '_', str_replace(':', '_', str_replace('.', '_', $identifier)));

                                            $dir = $path . $urlDirectory . $nameIdentifier . '_' . basename($linkFileXML);

                                            if ($objParamsFile->ajax_upload === '0') {
                                                $fields .= ", {$elementFile->name}";

                                                $dirName = $urlDirectory . $nameIdentifier . '_' . basename($linkFileXML);
                                                $fieldsContent .= ", '{$dirName}'";

                                                if ($harvesting->syncronism === '1') {
                                                    $arFieldsElement[] = $elementFile->name;
                                                    $arFieldsContent[] = $dirName;
                                                }
                                            } elseif ($objParamsFile->ajax_upload === '1') {
                                                $joinModelSourceFile = JModelLegacy::getInstance('Join', 'FabrikFEModel');
                                                $tableRepeatFile['file'] = $joinModelSourceFile->getJoinFromKey('element_id', $harvesting->dowload_file);
                                                $dirName = $db->escape(str_replace('/', '\\', $urlDirectory . $nameIdentifier . '_' . basename($linkFileXML)));
                                            }

                                            $linkUFG = 'localhost:8080';

                                            if (strpos($linkFileXML, $linkUFG) !== false) {
                                                $arLinkFileXML = explode('/', $linkFileXML);

                                                $link = 'http://repositorio.bc.ufg.br/bitstream/ri/';

                                                $linkFileXML = $link . $arLinkFileXML[count($arLinkFileXML) - 3] . '/' . $arLinkFileXML[count($arLinkFileXML) - 2] . '/' . $arLinkFileXML[count($arLinkFileXML) - 1];
                                            }

                                            copy($linkFileXML, $dir);

                                            if ($objParamsFile->fu_make_pdf_thumb === '1') {
                                                $path_thumb = $path . '/' . $objParamsFile->thumb_dir . '/' . $nameIdentifier . '_' . basename($linkFileXML);
                                                $path_thumb = str_replace('.pdf', '.png', $path_thumb);

                                                if (!JFile::exists($path_thumb) && JFile::exists($dir)) {
                                                    $width_thumb = $objParamsFile->thumb_max_width;
                                                    $height_thumb = $objParamsFile->thumb_max_height;

                                                    $im = new Imagick($dir . '[0]');
                                                    $im->setImageFormat("png");
                                                    $im->setImageBackgroundColor(new ImagickPixel('white'));
                                                    $im->thumbnailImage($width_thumb, $height_thumb);
                                                    $im->writeImage($path_thumb);
                                                }
                                            }
                                        }
                                    }
                                }

                                if ($harvesting->extract !== '0') {
                                    $metas2 = $this->searchFileRepositoryOER($harvesting->repository, $identifier);

                                    if ($metas2 !== false) {
                                        $path = dirname(dirname($_SERVER['SCRIPT_FILENAME']));

                                        for ($i = 0; $i < $metas2->length; $i++) {
                                            $tag = $metas2->item($i);

                                            if (($tag->nodeName == 'description') && ((strpos($tag->getAttribute('rdf:about'), '.pdf.txt') !== false))) {
                                                $linkFileXML = substr($tag->getAttribute('rdf:about'), 0, strpos($tag->getAttribute('rdf:about'), '.pdf.txt') + 8);
                                            }
                                        }

                                        $linkUFG = 'localhost:8080';

                                        if (strpos($linkFileXML, $linkUFG) !== false) {
                                            $arLinkFileXML = explode('/', $linkFileXML);

                                            $link = 'http://repositorio.bc.ufg.br/bitstream/ri/';

                                            $linkFile = $link . $arLinkFileXML[count($arLinkFileXML) - 3] . '/' . $arLinkFileXML[count($arLinkFileXML) - 2] . '/' . $arLinkFileXML[count($arLinkFileXML) - 1];
                                        }

                                        $textFile = trim($db->escape(file_get_contents($linkFile)));

                                        if ((strtotime($textFile) === 0) && (is_file($dir))) {
                                            $tikaAppPath = $path . '/plugins/fabrik_form/textextract/app/tika.jar';

                                            $command = ('java -jar ' . $tikaAppPath . ' "' . $dir . '" --text');
                                            exec($command, $execOutArray);
                                            $textFile = trim($db->escape(strip_tags(implode(' ', $execOutArray))));
                                        }

                                        $elementFile = $this->mappedElementsData($harvesting->list, $harvesting->extract);

                                        $fields .= ", {$elementFile->name}";
                                        $fieldsContent .= ", '{$textFile}'";

                                        if ($harvesting->syncronism === '1') {
                                            $arFieldsElement[] = $elementFile->name;
                                            $arFieldsContent[] = $textFile;
                                        }
                                    }
                                }

                                $result_id = (int)$this->insertTableMultipleFieldsData($data['tableSource']->db_table_name, $fields, $fieldsContent);

                                if (is_array($arFields) && is_object($data['metadata']) && (!is_null($data['metadata']))) {
                                    foreach ($arFields as $key => $vlTarget) {
                                        foreach ($vlTarget as $vlRepeat) {
                                            $resultRepeat = $this->selectTableRepeat($tableRepeat[$key]->table_join, $tableRepeat[$key]->table_join_key, $tableRepeat[$key]->table_key, $result_id, $vlRepeat);

                                            if ($resultRepeat->total === '0') {
                                                $this->insertTableRepeat($tableRepeat[$key]->table_join, $tableRepeat[$key]->table_join_key, $tableRepeat[$key]->table_key, $result_id, $vlRepeat);
                                            }
                                        }
                                        unset($tableRepeat[$key]);
                                    }

                                    unset($arFields);
                                }

                                if (is_array($arFieldsTag) && is_object($data['metadata']) && (!is_null($data['metadata']))) {
                                    foreach ($arFieldsTag as $key => $vlTarget) {
                                        foreach ($vlTarget as $vlRepeat) {

                                            $resultRepeat = $this->selectTableRepeat($tableRepeatTag[$key]->table_join, $tableRepeatTag[$key]->table_join_key, $tableRepeatTag[$key]->table_key, $result_id, $vlRepeat);

                                            if ($resultRepeat->total === '0') {
                                                $this->insertTableRepeat($tableRepeatTag[$key]->table_join, $tableRepeatTag[$key]->table_join_key, $tableRepeatTag[$key]->table_key, $result_id, $vlRepeat);
                                            }
                                        }
                                        unset($tableRepeatTag[$key]);
                                    }

                                    unset($arFieldsTag);
                                }

                                if (($harvesting->dowload_file !== '0') && ($metas2 !== false)) {
                                    $resultRepeat = $this->selectTableRepeatFileUpload($tableRepeatFile['file']->table_join, $tableRepeatFile['file']->table_join_key, $tableRepeatFile['file']->table_key, $result_id, $dirName);

                                    if ($resultRepeat->total === '0') {
                                        $this->insertTableRepeat($tableRepeatFile['file']->table_join, $tableRepeatFile['file']->table_join_key, $tableRepeatFile['file']->table_key, $result_id, $dirName);
                                    }
                                }
                            } elseif (!is_null($result_identifier->id) && ($update === 1) && ($harvesting->syncronism === '1')) {
                                if (is_object($data['metadata']) && (!is_null($data['metadata']))) {
                                    $dom->loadHTML('<?xml encoding="utf-8" ?>' . trim($recordNode->metadata->asXML()));

                                    $i = 0;
                                    unset($arFields);
                                    unset($tableRepeat);
                                    unset($arFieldsTag);
                                    unset($tableRepeatTag);

                                    foreach ($data['metadata'] as $key => $objFields) {
                                        $tag = explode(':', $key);
                                        $metas = $dom->getElementsByTagName($tag[1]);

                                        $arLength = array_count_values($objFields);
                                        $j = 0;

                                        $fieldExtra = "";

                                        foreach ($objFields as $index => $value) {
                                            $joinModelSource = JModelLegacy::getInstance('Join', 'FabrikFEModel');
                                            $element = $this->mappedElementsData($harvesting->list, $value);
                                            $objParams = json_decode($element->params);

                                            if (strlen($fields) !== 0) {
                                                if ($j === 0) {
                                                    if (($objParams->database_join_display_type !== "checkbox") && ($objParams->database_join_display_type !== "multilist") && ($element->plugin !== 'tags')) {
                                                        $fields .= ", $element->name";
                                                        $itemFieldElement = 1;
                                                    } else {
                                                        $itemFieldElement = 0;
                                                    }
                                                } elseif (($arLength[$value] === 1) && ($objParams->database_join_display_type !== "checkbox") && ($objParams->database_join_display_type !== "multilist") && ($element->plugin !== 'tags')) {
                                                    $fields .= ", $element->name";
                                                    $itemFieldElement = 1;
                                                }
                                            } else {
                                                $fields .= "$element->name";
                                                $itemFieldElement = 1;
                                            }

                                            if (($harvesting->syncronism === '1') && ($itemFieldElement === 1)) {
                                                $arFieldsElement[] = $element->name;
                                            }

                                            switch ($element->plugin) {
                                                case 'date':
                                                    $date = date("Y-m-d H:i:s", strtotime($db->escape($metas->item($index)->nodeValue)));

                                                    if (strlen($fieldsContent) !== 0) {
                                                        $fieldsContent .= ", '$date'";
                                                    } else {
                                                        $fieldsContent .= "'$date'";
                                                    }

                                                    if ($harvesting->syncronism === '1') {
                                                        $arFieldsContent[] = $date;
                                                    }

                                                    if (($harvesting->field2 === 'dc:date') && ($harvesting->syncronism === '1')) {
                                                        $dateSync = date("Y-m-d", strtotime($db->escape($metas->item($index)->nodeValue)));
                                                        $fieldSync = $element->name;
                                                    }

                                                    break;
                                                case 'dropdown':
                                                    $objOptions = $objParams->sub_options;

                                                    if (!(in_array($db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue))), $objOptions->sub_values)) &&
                                                        !(in_array($db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue))), $objOptions->sub_labels))) {
                                                        $objOptions->sub_values[] = ucwords(mb_strtolower($db->escape($metas->item($index)->nodeValue)));
                                                        $objOptions->sub_labels[] = ucwords(mb_strtolower($db->escape($metas->item($index)->nodeValue)));
                                                    }

                                                    $parans_update = $this->updateTableElement($value, $objParams);

                                                    if ($parans_update) {
                                                        if (strlen($fieldsContent) !== 0) {
                                                            $fieldsContent .= ", '{$db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue)))}'";
                                                        } else {
                                                            $fieldsContent .= "'{$db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue)))}'";
                                                        }
                                                    }

                                                    if ($harvesting->syncronism === '1') {
                                                        $arFieldsContent[] = $db->escape(ucwords(mb_strtolower($metas->item($index)->nodeValue)));
                                                    }

                                                    break;
                                                case 'databasejoin':
                                                    if ((($objParams->database_join_display_type === "dropdown") || ($objParams->database_join_display_type === "radio") ||
                                                        ($objParams->database_join_display_type === "auto-complete"))) {
                                                        $exist_data_target = $this->existTargetTableData($objParams->join_db_name, $objParams->join_val_column, $metas->item($index)->nodeValue);

                                                        if (count($exist_data_target) === 0) {
                                                            $result = $this->insertTable($objParams->join_db_name, $objParams->join_val_column, $metas->item($index)->nodeValue);
                                                        } else {
                                                            $result = $exist_data_target['id'];
                                                        }

                                                        if ((strlen($fieldsContent) !== 0) && ($result !== false)) {
                                                            $fieldsContent .= ", '$result'";
                                                        } elseif ((strlen($fieldsContent) === 0) && ($result !== false)) {
                                                            $fieldsContent .= "'$result'";
                                                        } elseif ((strlen($fieldsContent) === 0) && ($result === false)) {
                                                            $fieldsContent .= ",''";
                                                        } elseif ((strlen($fieldsContent) !== 0) && ($result === false)) {
                                                            $fieldsContent .= "''";
                                                        }

                                                        if ($harvesting->syncronism === '1') {
                                                            $arFieldsContent[] = $result;
                                                        }
                                                    } elseif ((($objParams->database_join_display_type === "checkbox") || ($objParams->database_join_display_type === "multilist"))) {
                                                        $tableRepeat[$tag[1]] = $joinModelSource->getJoinFromKey('element_id', $value);

                                                        for ($numDBJ = 0; $numDBJ < $metas->length; $numDBJ++) {
                                                            if ($tag[1] === 'creator') {
                                                                $arTagText = explode(',', $metas->item($numDBJ)->nodeValue);
                                                                $tagText = trim($arTagText[1]) . ' ' . trim($arTagText[0]);
                                                            } else {
                                                                $tagText = trim($metas->item($numDBJ)->nodeValue);
                                                            }

                                                            $exist_data_target = $this->existTargetTableData($objParams->join_db_name, $objParams->join_val_column, $tagText);

                                                            if (count($exist_data_target) === 0) {
                                                                $arFields[$tag[1]][] = (int)$this->insertTable($objParams->join_db_name, $objParams->join_val_column, $tagText);
                                                            } else {
                                                                $arFields[$tag[1]][] = (int)$exist_data_target['id'];
                                                            }
                                                        }
                                                    }

                                                    break;
                                                case 'tags':
                                                    $nameTableTags = $ext . 'tags';

                                                    if ($objParams->tags_dbname === $nameTableTags) {
                                                        $tagSelestField = 'title';
                                                    }

                                                    $tableRepeatTag[$tag[1]] = $joinModelSource->getJoinFromKey('element_id', $value);

                                                    for ($numDBJ = 0; $numDBJ < $metas->length; $numDBJ++) {
                                                        $tagText = trim(ucfirst($metas->item($numDBJ)->nodeValue));

                                                        $exist_data_target = $this->existTargetTableData($objParams->tags_dbname, $tagSelestField, $tagText);

                                                        if ($objParams->tags_dbname === $nameTableTags) {
                                                            $tagTextExtra = $this->removeAccentsSpecialCharacters(trim(strtolower($metas->item($numDBJ)->nodeValue)));
                                                            date_default_timezone_set('America/Sao_Paulo');
                                                            $data['registerDate'] = date("Y-m-d H:i:s");

                                                            $user = JFactory::getUser();
                                                            $data['users'] = $user->get('id');

                                                            $tagInsertField = "`parent_id`, `level`, `path`, `title`, `alias`, `published`, `checked_out_time`, `access`, `created_user_id`,
										 `created_time`, `modified_time`, `publish_up`, `publish_down`";

                                                            $tagInsertData = "'1','1','$tagTextExtra','$tagText','$tagTextExtra','1','{$data['registerDate']}','1','{$data['users']}',
										'{$data['registerDate']}','{$data['registerDate']}','{$data['registerDate']}','{$data['registerDate']}'";
                                                        }

                                                        if (count($exist_data_target) === 0) {
                                                            $arFieldsTag[$tag[1]][] = (int)$this->insertTableMultipleFieldsData($objParams->tags_dbname, $tagInsertField, $tagInsertData);
                                                        } else {
                                                            $arFieldsTag[$tag[1]][] = (int)$exist_data_target['id'];
                                                        }
                                                    }

                                                    break;
                                                default:
                                                    $num = $index + 1;

                                                    if (strlen($fieldsContent) !== 0) {
                                                        if ($j === 0) {
                                                            $fieldExtra .= $db->escape($metas->item($index)->nodeValue);
                                                        } elseif ($arLength[$value] === 1) {
                                                            $fieldExtra = $db->escape($metas->item($j)->nodeValue);
                                                        } else {
                                                            $fieldExtra .= '|' . $db->escape($metas->item($index)->nodeValue);
                                                        }

                                                        if (($num === $arLength[$value]) || ($arLength[$value] === 1)) {
                                                            $fieldsContent .= ", '$fieldExtra'";
                                                        }
                                                    } else {
                                                        $fieldExtra .= $db->escape($metas->item($index)->nodeValue);

                                                        if ($num === $arLength[$value]) {
                                                            $fieldsContent .= "'$fieldExtra'";
                                                        }
                                                    }

                                                    if ($harvesting->syncronism === '1') {
                                                        $arFieldsContent[] = $fieldExtra;
                                                    }

                                                    break;
                                            }

                                            $j += 1;
                                        }

                                        $i += 1;
                                    }
                                }

                                if ($harvesting->dowload_file !== '0') {
                                    $metas2 = $this->searchFileRepositoryOER($harvesting->repository, $identifier);

                                    $dirName = '';
                                    unset($tableRepeatFile);

                                    if ($metas2 !== false) {
                                        $path = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
                                        for ($i = 0; $i < $metas2->length; $i++) {
                                            $tag = $metas2->item($i);

                                            if (($tag->nodeName == 'description') && ((strpos($tag->getAttribute('rdf:about'), '.pdf') !== false) &&
                                                    (strpos($tag->getAttribute('rdf:about'), '.pdf.') === false))) {
                                                $linkFileXML = substr($tag->getAttribute('rdf:about'), 0, strpos($tag->getAttribute('rdf:about'), '.pdf') + 4);
                                            }
                                        }

                                        $file_ext = explode('.', $linkFileXML);

                                        if (count($file_ext) === 2) {
                                            $elementFile = $this->mappedElementsData($harvesting->list, $harvesting->dowload_file);
                                            $objParamsFile = json_decode($elementFile->params);

                                            $urlDirectory = $objParamsFile->ul_directory;

                                            $nameIdentifier = str_replace('/', '_', str_replace(':', '_', str_replace('.', '_', $identifier)));

                                            $dir = $path . $urlDirectory . $nameIdentifier . '_' . basename($linkFileXML);

                                            if ($objParamsFile->ajax_upload === '0') {
                                                $fields .= ", $elementFile->name";

                                                $dirName = $urlDirectory . $nameIdentifier . '_' . basename($linkFileXML);
                                                $fieldsContent .= ", '$dirName'";

                                                if ($harvesting->syncronism === '1') {
                                                    $arFieldsElement[] = $elementFile->name;
                                                    $arFieldsContent[] = $dirName;
                                                }
                                            } elseif ($objParamsFile->ajax_upload === '1') {
                                                $joinModelSourceFile = JModelLegacy::getInstance('Join', 'FabrikFEModel');
                                                $tableRepeatFile['file'] = $joinModelSourceFile->getJoinFromKey('element_id', $harvesting->dowload_file);
                                                $dirName = $db->escape(str_replace('/', '\\', $urlDirectory . $nameIdentifier . '_' . basename($linkFileXML)));
                                            }

                                            $linkUFG = 'localhost:8080';

                                            if (strpos($linkFileXML, $linkUFG) !== false) {
                                                $arLinkFileXML = explode('/', $linkFileXML);

                                                $link = 'http://repositorio.bc.ufg.br/bitstream/ri/';

                                                $linkFileXML = $link . $arLinkFileXML[count($arLinkFileXML) - 3] . '/' . $arLinkFileXML[count($arLinkFileXML) - 2] . '/' . $arLinkFileXML[count($arLinkFileXML) - 1];
                                            }

                                            copy($linkFileXML, $dir);

                                            if ($objParamsFile->fu_make_pdf_thumb === '1') {
                                                $path_thumb = $path . '/' . $objParamsFile->thumb_dir . '/' . $nameIdentifier . '_' . basename($linkFileXML);
                                                $path_thumb = str_replace('.pdf', '.png', $path_thumb);

                                                if (!JFile::exists($path_thumb) && JFile::exists($dir)) {
                                                    $width_thumb = $objParamsFile->thumb_max_width;
                                                    $height_thumb = $objParamsFile->thumb_max_height;

                                                    $im = new Imagick($dir . '[0]');
                                                    $im->setImageFormat("png");
                                                    $im->setImageBackgroundColor(new ImagickPixel('white'));
                                                    $im->thumbnailImage($width_thumb, $height_thumb);
                                                    $im->writeImage($path_thumb);
                                                }
                                            }
                                        }
                                    }
                                }

                                if ($harvesting->extract !== '0') {
                                    $metas2 = $this->searchFileRepositoryOER($harvesting->repository, $identifier);

                                    if ($metas2 !== false) {
                                        $path = dirname(dirname($_SERVER['SCRIPT_FILENAME']));

                                        for ($i = 0; $i < $metas2->length; $i++) {
                                            $tag = $metas2->item($i);

                                            if (($tag->nodeName == 'description') && ((strpos($tag->getAttribute('rdf:about'), '.pdf.txt') !== false))) {
                                                $linkFileXML = substr($tag->getAttribute('rdf:about'), 0, strpos($tag->getAttribute('rdf:about'), '.pdf.txt') + 8);
                                            }
                                        }

                                        $linkUFG = 'localhost:8080';

                                        if (strpos($linkFileXML, $linkUFG) !== false) {
                                            $arLinkFileXML = explode('/', $linkFileXML);

                                            $link = 'http://repositorio.bc.ufg.br/bitstream/ri/';

                                            $linkFile = $link . $arLinkFileXML[count($arLinkFileXML) - 3] . '/' . $arLinkFileXML[count($arLinkFileXML) - 2] . '/' . $arLinkFileXML[count($arLinkFileXML) - 1];
                                        }

                                        $textFile = trim($db->escape(file_get_contents($linkFile)));

                                        if ((strtotime($textFile) === 0) && (is_file($dir))) {
                                            $tikaAppPath = $path . '/plugins/fabrik_form/textextract/app/tika.jar';

                                            $command = ('java -jar ' . $tikaAppPath . ' "' . $dir . '" --text');
                                            exec($command, $execOutArray);
                                            $textFile = trim($db->escape(strip_tags(implode(' ', $execOutArray))));
                                        }

                                        $elementFile = $this->mappedElementsData($harvesting->list, $harvesting->extract);

                                        $fields .= ", $elementFile->name";
                                        $fieldsContent .= ", '$textFile'";

                                        if ($harvesting->syncronism === '1') {
                                            $arFieldsElement[] = $elementFile->name;
                                            $arFieldsContent[] = $textFile;
                                        }
                                    }
                                }

                                $this->updateTableMultipleFieldsData($data['tableSource']->db_table_name, $result_identifier->id, $arFieldsElement, $arFieldsContent);

                                if (is_array($arFields) && is_object($data['metadata']) && (!is_null($data['metadata']))) {
                                    foreach ($arFields as $key => $vlTarget) {
                                        foreach ($vlTarget as $vlRepeat) {
                                            $resultRepeat = $this->selectTableRepeat($tableRepeat[$key]->table_join, $tableRepeat[$key]->table_join_key, $tableRepeat[$key]->table_key, $result_identifier->id, $vlRepeat);

                                            if ($resultRepeat->total === '0') {
                                                $this->insertTableRepeat($tableRepeat[$key]->table_join, $tableRepeat[$key]->table_join_key, $tableRepeat[$key]->table_key, $result_identifier->id, $vlRepeat);
                                            }
                                        }
                                        unset($tableRepeat[$key]);
                                    }

                                    unset($arFields);
                                }

                                if (is_array($arFieldsTag) && is_object($data['metadata']) && (!is_null($data['metadata']))) {
                                    foreach ($arFieldsTag as $key => $vlTarget) {
                                        foreach ($vlTarget as $vlRepeat) {

                                            $resultRepeat = $this->selectTableRepeat($tableRepeatTag[$key]->table_join, $tableRepeatTag[$key]->table_join_key, $tableRepeatTag[$key]->table_key, $result_identifier->id, $vlRepeat);

                                            if ($resultRepeat->total === '0') {
                                                $this->insertTableRepeat($tableRepeatTag[$key]->table_join, $tableRepeatTag[$key]->table_join_key, $tableRepeatTag[$key]->table_key, $result_identifier->id, $vlRepeat);
                                            }
                                        }
                                        unset($tableRepeatTag[$key]);
                                    }

                                    unset($arFieldsTag);
                                }

                                if (($harvesting->dowload_file !== '0') && ($metas2 !== false)) {
                                    $resultRepeat = $this->selectTableRepeatFileUpload($tableRepeatFile['file']->table_join, $tableRepeatFile['file']->table_join_key, $tableRepeatFile['file']->table_key, $result_identifier->id, $dirName);

                                    if ($resultRepeat->total === '0') {
                                        $this->insertTableRepeat($tableRepeatFile['file']->table_join, $tableRepeatFile['file']->table_join_key, $tableRepeatFile['file']->table_key, $result_identifier->id, $dirName);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (!$xmlNode->resumptionToken) {
                $totalRecords = $totalRecords + $currentRecords;
            } else {
                $resumptionToken = $xmlNode->resumptionToken;

                $currentRecords = $currentRecords - 1;
                $totalRecords = $totalRecords + $currentRecords;

                $table = $ext . 'fabrik_harvesting';
                $this->updateDataTableSource($table, $totalRecords, $harvesting->id, 'page_xml');
            }

            $fetchCounter = $fetchCounter + 1;
        }

        return true;
    }

    /**
     * Method to check if the OER repository is valid and valid with meta data from xml.
     *
     * @param $link
     * @param $identifier
     * @return false
     */
    public function searchFileRepositoryOER($link, $identifier)
    {
        $baseURL2 = $link . '?verb=GetRecord';
        $initialParams2 = '&metadataPrefix=ore&identifier=' . $identifier;

        $url2 = $baseURL2 . $initialParams2;

        $xmlObj2 = simplexml_load_file($url2);

        if (!$xmlObj2) {
            $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR3');
            $site_message = JUri::base() . 'index.php?option=com_administrativetools&view=tools&tab=3';
            $type_message = 'warning';

            $this->setRedirect($site_message, $message, $type_message);

            return false;
        }

        $xmlObj1 = $xmlObj2->GetRecord;

        if ($xmlObj1->count() !== 0) {
            $xmlNode2 = $xmlObj2->GetRecord->record;

            $dom2 = new DOMDocument();

            $dom2->loadHTML('<?xml encoding="utf-8" ?>' . trim($xmlNode2->metadata->asXML()));

            return $dom2->getElementsByTagName('triples')->item(0)->childNodes;
        } else {
            return false;
        }
    }

    /**
     * Method that removes accented characters.
     *
     * @param $str
     * @return string|string[]
     */
    public function removeAccentsSpecialCharacters($str)
    {
        $comAcentos = array('à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ü', 'ú',
            'ÿ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'O', 'Ù', 'Ü', 'Ú');
        $semAcentos = array('a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u',
            'y', 'A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O', 'O', '0', 'U', 'U', 'U');

        return str_replace($comAcentos, $semAcentos, $str);
    }

    protected function checkTableName($name)
    {
        $db = JFactory::getDbo();

        $name = $this->user->id . '_' . $name;
        $continue = false;
        $flag = 1;

        while ($continue === false) {
            $db->setQuery("SHOW TABLES LIKE '{$name}_$flag'");
            $result = $db->loadResult();
            if ($result) {
                $flag++;
            } else {
                $continue = true;
            }
        }

        return $name . "_$flag";
    }

    public function checkDatabaseJoins()
    {
        $db = JFactory::getDbo();

        foreach ($this->elementsId as $elementId) {
            $query = $db->getQuery(true);
            $query->select('params')->from('#__fabrik_elements')->where("id = '$elementId'");
            $db->setQuery($query);
            $result = $db->loadResult();
            $params = json_decode($result);
            if (array_key_exists($params->join_db_name, $this->tableNames)) {
                $params->join_db_name = $this->tableNames[$params->join_db_name];
                $params = json_encode($params);
                $update = new stdClass();
                $update->id = $elementId;
                $update->params = $params;
                $db->updateObject('#__fabrik_elements', $update, 'id');
            } else {
                $published = 0;
                $update = new stdClass();
                $update->id = $elementId;
                $update->published = $published;
                $db->updateObject('#__fabrik_elements', $update, 'id');

                $query = $db->getQuery(true);
                $query = 'DELETE FROM `#__fabrik_joins` WHERE table_join = '."'$params->join_db_name'". ' and element_id = '.$elementId;
                $db->setQuery($query);
                $db->query($query);
            }
        }
    }

    public function exportList()
    {
        $app = JFactory::getApplication();
        $listIds = $app->input->get('lists');
        $data = $app->input->getInt('record');

        foreach ($listIds as $listId) {
            $this->exportClone_process($listId,$data);
        }

        $jsonExport = json_encode($this->listsToExport);

        $pathJson = JPATH_BASE . '/components/com_administrativetools/exportLists/listsToExport.json';
        JFile::write($pathJson, $jsonExport);

        if (JFile::exists($pathJson)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($pathJson) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($pathJson));
            flush(); // Flush system output buffer
            readfile($pathJson);
            die();
        } else {
            http_response_code(404);
        }

        JFile::delete($pathJson);

        $app->enqueueMessage(FText::_('COM_ADMINISTRATIVETOOLS_PACKAGES_CONTROLLER_EXPORTLIST_SUCCESS'), 'message');
        $this->setRedirect(JRoute::_('index.php?option=com_administrativetools&view=tools&tab=4', false));
    }

    protected function exportClone_process($listId, $data, $is_suggest = false)
    {
        $listModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
        $listModel->setId($listId);
        $formModel = $listModel->getFormModel();

        $listData = new stdClass();

        $info = new stdClass();
        $info->mappedGroups = array();
        $info->mappedElements = array();
        $info->elementsRepeat = array();
        $info->newListJoinsIds = array();
        $info->old_db_table_name = $formModel->getTableName();
        $info->db_table_name = '';

        if ($is_suggest) {
            $info->old_db_table_name = $this->clones_info[$this->listaPrincipal]->old_db_table_name;
            $info->db_table_name = $this->clones_info[$this->listaPrincipal]->db_table_name;
        }

        $this->clones_info[$listId] = $info;
        $listData->form = $this->exportCloneForm($formModel->getTable(), $listId, $is_suggest);
        $listData->list = $this->exportCloneList($listModel->getTable(), $listId, $is_suggest);
        $listData->groups = $this->exportCloneGroupsAndElements($formModel->getGroupsHiarachy(), $listId);
        $listData->groups_repeat = $this->exportCreateGroupsRepeat($listData->groups, $listId);
        $listData->table = $this->exportCreateTable($listId);
        $listData->tables_repeat = $this->exportCreateTablesRepeat($listId);
        $listData->menu = $this->exportMenuFabrik($listId,$formModel->getTable());
       
        if ($data == 1){
            $listData->table_data = $this->exportTableData($listId);
            $listData->groups_repeat_data = $this->exportGroupRepeatData($listData->groups_repeat);
            $listData->tables_repeat_data = $this->exportTableRepeatData($listData->tables_repeat);
        }

        $listData->clones_info = $this->clones_info;
        $listData->oldListId = $listId;

        $this->listsToExport[] = $listData;
    }

    protected function exportCloneForm($data, $listId, $is_suggest = false)
    {
        $this->clones_info[$listId]->formParams = json_decode($data->params);

        $cloneData = new stdClass();
        $cloneData->id = 0;
        $cloneData->label = $data->label;

        if ($is_suggest) {
            $cloneData->label .= ' - Revisão';
        }

        $cloneData->record_in_database = $data->record_in_database;
        $cloneData->error = $data->error;
        $cloneData->intro = $data->intro;
        $cloneData->created = date('Y-m-d H:i:s');
        $cloneData->created_by = $this->user->id;
        $cloneData->created_by_alias = $this->user->username;
        $cloneData->modified = $data->modified;
        $cloneData->modified_by = $data->modified_by;
        $cloneData->checked_out = $data->checked_out;
        $cloneData->checked_out_time = $data->checked_out_time;
        $cloneData->publish_up = $data->publish_up;
        $cloneData->publish_down = $data->publish_down;
        $cloneData->reset_button_label = $data->reset_button_label;
        $cloneData->submit_button_label = $data->submit_button_label;
        $cloneData->form_template = $data->form_template;
        $cloneData->view_only_template = $data->view_only_template;
        $cloneData->published = $data->published;
        $cloneData->private = $data->private;
        $cloneData->params = $data->params;

        return $cloneData;
    }

    protected function exportCloneList($data, $listId, $is_suggest = false)
    {
        $this->clones_info[$listId]->listParams = json_decode($data->params);
        $this->clones_info[$listId]->orderByList = $data->order_by;

        $cloneData = new stdClass();
        $cloneData->id = 0;
        $cloneData->label = $data->label;

        if ($is_suggest) {
            $cloneData->label .= ' - Revisão';
        }

        $cloneData->introduction = $data->introduction;
        $cloneData->form_id = 0;
        $cloneData->db_table_name = '';//$this->clones_info[$listId]->db_table_name;
        $cloneData->db_primary_key = '';
        $this->clones_info[$listId]->db_table_name . '.id';
        $cloneData->auto_inc = $data->auto_inc;
        $cloneData->connection_id = $data->connection_id;
        $cloneData->created = date('Y-m-d H:i:s');
        $cloneData->created_by = $data->created_by;
        $cloneData->created_by_alias = $data->created_by_alias;
        $cloneData->modified = date('Y-m-d H:i:s');
        $cloneData->modified_by = $this->user->id;
        $cloneData->checked_out = $data->checked_out;
        $cloneData->checked_out_time = $data->checked_out_time;
        $cloneData->published = $data->published;
        $cloneData->publish_up = $data->publish_up;
        $cloneData->publish_down = $data->publish_down;
        $cloneData->access = $data->access;
        $cloneData->hits = $data->hits;
        $cloneData->rows_per_page = $data->rows_per_page;
        $cloneData->template = $data->template;
        $cloneData->order_dir = $data->order_dir;
        $cloneData->filter_action = $data->filter_action;
        $cloneData->group_by = $data->group_by;
        $cloneData->private = $data->private;
        $cloneData->params = $data->params;

        return $cloneData;
    }

    protected function exportCloneGroupsAndElements($groups, $listId)
    {
        $db = JFactory::getDbo();
        $ordering = 1;
        $groupsData = array();

        foreach ($groups as $groupModel) {
            $groupData = new stdClass();
            $groupData->joins = array();
            $groupData->elements = array();

            $cloneData = $groupModel->getGroup()->getProperties();
            unset($cloneData['join_id']);
            $cloneData = (object)$cloneData;
            $oldId = $cloneData->id;
            $cloneData->created = date('Y-m-d H:i:s');
            $cloneData->created_by = $this->user->id;
            $cloneData->created_by_alias = $this->user->username;
            $groupData->group = $cloneData;

            if ($this->clones_info[$listId]->listParams->join_id) {
                foreach ($this->clones_info[$listId]->listParams->join_id as $key => $item) {
                    $query = $db->getQuery(true);
                    $query->select('group_id, params')->from("#__fabrik_joins")->where('id = ' . (int)$item);
                    $db->setQuery($query);
                    $result = $db->loadAssoc();
                    if ($result['group_id'] === $oldId) {
                        $groupData->joins[$key] = $result['params'];
                    }
                }
            }

            $elementsModel = $groupModel->getMyElements();
            $groupData->elements = $this->exportCloneElements($elementsModel, 0, $listId);

            $groupsData[] = $groupData;
        }

        return $groupsData;
    }

    protected function exportCloneElements($elementsModel, $group_id, $listId)
    {
        $elementsData = array();

        foreach ($elementsModel as $elementModel) {
            $cloneData = $elementModel->getElement()->getProperties();
            $cloneData = (object)$cloneData;
            $oldId = $cloneData->id;
            $cloneData->group_id = $group_id;
            $cloneData->created = date('Y-m-d H:i:s');
            $cloneData->created_by = $this->user->id;
            $cloneData->created_by_alias = $this->user->username;
            $cloneData->modified = date('Y-m-d H:i:s');

            $params = json_decode($cloneData->params);

            if ($cloneData->plugin === 'databasejoin') {
                $dbJoinMulti = array('checkbox', 'multilist');

                if (in_array($params->database_join_display_type, $dbJoinMulti)) {
                    $this->clones_info[$listId]->elementsRepeat[] = $cloneData->name;
                    $cloneData->joinOfElement = $elementModel->getJoinModel()->getJoin();
                } else {
                    $cloneData->joinOfElement = $elementModel->getJoinModel()->getJoin();
                }
            } else if (($cloneData->plugin === 'fileupload') && ((bool)$params->ajax_upload)) {
                $this->clones_info[$listId]->elementsRepeat[] = $cloneData->name;
                $cloneData->joinOfElement = $elementModel->getJoinModel()->getJoin();
            } else if ($cloneData->plugin === 'tags') {
                $this->clones_info[$listId]->elementsRepeat[] = $cloneData->name;
                $cloneData->joinOfElement = $elementModel->getJoinModel()->getJoin();
            } else if ($cloneData->plugin === 'user') {
                $cloneData->joinOfElement = $elementModel->getJoinModel()->getJoin();
            } else if ($cloneData->plugin === 'survey') {
                $this->clones_info[$listId]->elementsRepeat[] = $cloneData->name;
                $cloneData->joinOfElement = $elementModel->getJoinModel()->getJoin();
            }

            $elementsData[] = $cloneData;
        }

        return $elementsData;
    }

    protected function exportCloneJoin($data, $element_id, $element_name, $listId, $group_id, $type = '')
    {
        $cloneData = new stdClass();
        $cloneData->id = 0;

        if ($type === 'user') {
            $cloneData->list_id = 0;
            $cloneData->element_id = $element_id;
            $cloneData->join_from_table = '';
            $cloneData->table_join = '#__users';
            $cloneData->table_key = $data->table_key;
            $cloneData->table_join_key = $data->table_join_key;
            $cloneData->join_type = $data->join_type;
            $cloneData->group_id = $group_id;

            $params = array();
            $params['join-label'] = 'name';
            $params['type'] = 'element';
            $params['pk'] = '`#__users`.`id`';
            $params = (object)$params;
            $cloneData->params = json_encode($params);

        } else if ($type === 'list_join') {
            $cloneData->list_id = $this->clones_info[$listId]->listId;
            $cloneData->element_id = 0;
            $cloneData->join_from_table = $this->clones_info[$listId]->db_table_name;
            $cloneData->table_join = $this->clones_info[$listId]->listParams->table_join[$element_id];
            $cloneData->table_key = $this->clones_info[$listId]->listParams->table_key[$element_id];
            $cloneData->table_join_key = $this->clones_info[$listId]->listParams->table_join_key[$element_id];
            $cloneData->join_type = $this->clones_info[$listId]->listParams->join_type[$element_id];
            $cloneData->group_id = $group_id;
            $cloneData->params = $data;
        } else if ($type === 'dbjoin_single') {
            $cloneData->list_id = 0;
            $cloneData->element_id = $element_id;
            $cloneData->join_from_table = '';
            $cloneData->table_join = $data->table_join;
            $cloneData->table_key = $data->table_key;
            $cloneData->table_join_key = $data->table_join_key;
            $cloneData->join_type = $data->join_type;
            if ($data->group_id !== 0) {
                $cloneData->group_id = $group_id;
            } else {
                $cloneData->group_id = 0;
            }
            $params = json_decode($data->params);
            $cloneData->params = json_encode($params);
        } else {
            $cloneData->list_id = $this->clones_info[$listId]->listId;
            $cloneData->element_id = $element_id;
            $cloneData->join_from_table = $this->clones_info[$listId]->db_table_name;
            $cloneData->table_join = $this->clones_info[$listId]->db_table_name . '_repeat_' . $element_name;
            $cloneData->table_key = $data->table_key;
            $cloneData->table_join_key = $data->table_join_key;
            $cloneData->join_type = $data->join_type;
            if ($data->group_id !== 0) {
                $cloneData->group_id = $group_id;
            } else {
                $cloneData->group_id = 0;
            }
            $params = json_decode($data->params);
            $params->pk = str_replace($data->table_join, $cloneData->table_join, $params->pk);
            $cloneData->params = json_encode($params);
        }

        return $cloneData;
    }

    protected function exportCreateTable($listId)
    {
        $db = JFactory::getDbo();
        $oldTableName = $this->clones_info[$listId]->old_db_table_name;
        $db->setQuery("SHOW CREATE TABLE $oldTableName");
        return $db->loadAssoc()["Create Table"];
    }

    protected function exportTableData($listId)
    {
        $dataTable = array();
        $db = JFactory::getDbo();
        $oldTableName = $this->clones_info[$listId]->old_db_table_name;
        $db->setQuery("SELECT * FROM $oldTableName");
        $data = [];
        foreach ($db->loadRowList() as $row ){
            $data[] = "('".implode("','",array_map(
                function($vlr){
                    return addslashes($vlr);
                },
                $row)
            )."')";
        }

        $dataTable = "INSERT INTO `{$oldTableName}` VALUES ".implode(",",$data);
        return $dataTable;
    }

    protected function exportCreateTablesRepeat($listId)
    {
        $db = JFactory::getDbo();
        $oldTableName = $this->clones_info[$listId]->old_db_table_name;
        $elementsRepeat = $this->clones_info[$listId]->elementsRepeat;

        $tablesRepeat = array();

        foreach ($elementsRepeat as $element) {
            $table = $oldTableName . '_repeat_' . $element;
            $db->setQuery("SHOW CREATE TABLE $table");
            $res = $db->loadAssoc()["Create Table"];
            $tablesRepeat[] = $res;
        }

        return $tablesRepeat;
    }

    protected function exportGroupRepeatData($groups_repeat_data)
    {
        $data_group_repeat = [];
        $db = JFactory::getDbo();
        foreach ($groups_repeat_data as $groupRepeat) {
            $groupRepeat = explode('`', $groupRepeat)[1];
            $db->setQuery("SELECT * FROM $groupRepeat");
            $data = [];
                foreach ($db->loadRowList() as $row ){
                    $data[] = "('".implode("','",$row)."')";
                }
            $data_group_repeat[] = "INSERT INTO `{$groupRepeat}` VALUES ".implode(",",$data);
        }
        return $data_group_repeat;
    }

    protected function exportTableRepeatData($tablesRepeatData)
    {
        $data_table_repeat = [];
        $db = JFactory::getDbo();
        foreach ($tablesRepeatData as $tableRepeat) {
            $tableRepeat = explode('`', $tableRepeat)[1];
            $db->setQuery("SELECT * FROM $tableRepeat");
            $data = [];
                foreach ($db->loadRowList() as $row ){
                    $data[] = "('".implode("','",$row)."')";
                }
            $data_table_repeat[] = "INSERT INTO `{$tableRepeat}` VALUES ".implode(",",$data);
        }
        return $data_table_repeat;
    }

    protected function exportMenuFabrik($listId,$data)
    {   
        $menu = array();
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        
        $query = "SELECT * FROM `#__menu` WHERE link = 'index.php?option=com_fabrik&view=list&listid=". (int)$listId."'";
        $db->setQuery($query);
        $lists['lists'] = $db->loadAssocList();
        $menu['lists'] = $lists;

        $query = "SELECT * FROM `#__menu` WHERE link = 'index.php?option=com_fabrik&view=form&formid=". (int)$data->id."'";
        $db->setQuery($query);
        $forms['forms'] = $db->loadAssocList();
        $menu['forms'] = $forms;

        
        $query = "SELECT * FROM `#__menu` WHERE link = 'index.php?option=com_fabrik&view=details&formid=". (int)$data->id."'";
        $db->setQuery($query);
        $details['details'] = $db->loadAssocList();
        $menu['details'] = $details;

        $query = "SELECT * FROM `#__menu` WHERE link = 'index.php?option=com_fabrik&view=csv&listid=". (int)$listId."'";
        $db->setQuery($query);
        $csv['csvs'] = $db->loadAssocList();
        $menu['csvs'] = $csv;

        $query = "SELECT * FROM `#__menu` WHERE link = 'index.php?option=com_fabrik&view=visualization&id=". (int)$listId."'";
        $db->setQuery($query);
        $visualization['visualizations'] = $db->loadAssocList();
        $menu['visualizations'] = $visualization;
        
        return $menu;
    }

    public function importList()
    {
        $app = JFactory::getApplication();

        if ($_FILES["listFile"]["type"] !== 'application/json') {
            $app->enqueueMessage(FText::_('COM_ADMINISTRATIVETOOLS_PACKAGES_CONTROLLER_IMPORTLIST_ERROR'), 'error');
            return;
        }

        $path = JPATH_BASE . '/components/com_administrativetools/importFile.json';
        JFile::move($_FILES["listFile"]["tmp_name"], $path);
        $json = file_get_contents($path);
        JFile::delete($path);
        $listsToImport = json_decode($json);

        if (empty($listsToImport)) {
            $app->enqueueMessage(FText::_('COM_ADMINISTRATIVETOOLS_PACKAGES_CONTROLLER_IMPORTLIST_ERROR'), 'error');
            return;
        }

        $this->user = JFactory::getUser();

        foreach ($listsToImport as $list) {
            $this->importClone_process($list);
        }

        $this->checkDatabaseJoins();
        $app->enqueueMessage(FText::_('COM_ADMINISTRATIVETOOLS_PACKAGES_CONTROLLER_IMPORTLIST_SUCCESS'), 'message');
        $this->setRedirect(JRoute::_('index.php?option=com_administrativetools&view=tools&tab=4', false));
    }

    public function importClone_process($list)
    {
        $this->clones_info = (array)$list->clones_info;

        $this->clones_info[$list->oldListId]->db_table_name = $this->clones_info[$list->oldListId]->old_db_table_name;
        $this->tableNames[$this->clones_info[$list->oldListId]->old_db_table_name] = $this->clones_info[$list->oldListId]->db_table_name;

        $this->importCloneForm($list->form, $list->oldListId);
        $this->importCloneList($list->list, $list->oldListId);
        $this->importCloneGroupsAndElements($list->groups, $list->oldListId,$list->groups_repeat,$list->groups_repeat_data);
        $this->importCreateTable($list->oldListId, $list->table);
        $this->importCreateTablesRepeat($list->oldListId, $list->tables_repeat);
        $this->importCreateTableData($list->oldListId, $list->table_data);
        $this->importCreateTablesRepeatData($list->oldListId, $list->tables_repeat_data);
        $this->importCreateMenuFabrik($list->oldListId,$list->menu);

        $this->replaceElementsIdFormParams($list->oldListId);
        $this->replaceElementsIdListParams($list->oldListId);
    }

    protected function importCloneForm($data, $listId)
    {
        $db = JFactory::getDbo();

        $data->created_by = $this->user->id;
        $data->created_by_alias = $this->user->username;

        $query = $db->getQuery(true);
        $query = "SELECT id FROM `#__fabrik_forms` WHERE label = '$data->label'";
        $db->setQuery($query);
        $existId = $db->loadResult();

        if($existId) {
            $data->id = $existId;
            $insert = $db->updateObject('#__fabrik_forms', $data, 'id');
        } else {
            $insert = $db->insertObject('#__fabrik_forms', $data, 'id');
        }

        if (!$insert) {
            return false;
        }

        $existId ? $this->clones_info[$listId]->formId = $existId : $this->clones_info[$listId]->formId = $db->insertid();

        return true;
    }

    protected function importCloneList($data, $listId)
    {
        $db = JFactory::getDbo();

        $data->form_id = $this->clones_info[$listId]->formId;
        $data->db_table_name = $this->clones_info[$listId]->db_table_name;
        $data->db_primary_key = $this->clones_info[$listId]->db_table_name . '.id';
        $data->modified_by = $this->user->id;

        $query = $db->getQuery(true);
        $query = "SELECT id FROM `#__fabrik_lists` WHERE label = '$data->label'";
        $db->setQuery($query);
        $existId = $db->loadResult();

        if($existId) {
            $data->id = $existId;
            $insert = $db->updateObject('#__fabrik_lists', $data, 'id');
        } else {
            $insert = $db->insertObject('#__fabrik_lists', $data, 'id');
        }

        if (!$insert) {
            return false;
        }

        $existId ? $this->clones_info[$listId]->listId = $existId : $this->clones_info[$listId]->listId = $db->insertid();

        return true;
    }

    protected function importCloneGroupsAndElements($groups, $listId,$groups_repeat = null, $groups_repeat_data = null)
    {
        $db = JFactory::getDbo();
        $ordering = 1;

        foreach ($groups as $group) {
            $repeat = $group->group->params;
            $repeat = json_decode($repeat);
            
            $cloneData = $group->group;
            unset($cloneData->join_id);
            $cloneData = (object)$cloneData;
            $oldId = $cloneData->id;
            $cloneData->id = 0;
            $cloneData->created_by = $this->user->id;
            $cloneData->created_by_alias = $this->user->username;

            $query = $db->getQuery(true);
            $query = "SELECT id FROM `#__fabrik_groups` WHERE `name` = '$cloneData->name'";
            $db->setQuery($query);
            $existId = $db->loadResult();

            if($existId) {
                $cloneData->id = $existId;
                $insert1 = $db->updateObject('#__fabrik_groups', $cloneData, 'id');
                $groupId = $existId;
            } else {
                $insert1 = $db->insertObject('#__fabrik_groups', $cloneData, 'id');
                $groupId = $db->insertId();
            }

            $obj = new stdClass();
            $obj->id = 0;
            $obj->form_id = $this->clones_info[$listId]->formId;
            $obj->group_id = $groupId;
            $obj->ordering = $ordering;
            $existId ? $insert2 = true : $insert2 = $db->insertObject('#__fabrik_formgroup', $obj, 'id');
            
            $ordering++;
            $elementsModel = $group->elements;

            if ($repeat->repeat_group_button == "1" and ($groups_repeat) ){
                foreach ($elementsModel as $element){
                    if(preg_match("/{$element->name}/", (string)$groups_repeat[0])){
                        echo 'Existe no array.';
                    } else {
                        echo 'Elemento Não Existe no grupo.';
                    }
                }
                $listname = $this->clones_info[$listId]->old_db_table_name;
                $newTableNameSql = $listname . '_' . ($obj->group_id) . '_repeat';
                $oldTableNameSql = explode('`', $groups_repeat[0])[1];
                    
                $newSql = str_replace($oldTableNameSql, $newTableNameSql, $groups_repeat[0]);

                $query = $db->getQuery(true);
                $query->clear()->select($db->qn('table_name'))
                    ->from($db->qn('information_schema.tables'))
                    ->where($db->qn('table_name') . ' = ' . $db->q($newTableNameSql));
                $db->setQuery($query);
                if($db->loadResult()) {
                    $db->setQuery("SHOW CREATE TABLE $newTableNameSql");
                    $actualTable = $db->loadAssoc()["Create Table"];
                    if($newSql != $actualTable) {
                        $db->setQuery("RENAME TABLE $newTableNameSql TO " . $this->checkTableName($newTableNameSql));
                        $db->execute();
                    }
                }

                $newSql = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $newSql);
                $db->setQuery($newSql);
                try {
                    $db->execute();
                } catch (RuntimeException $e) {
                    $err = new stdClass;
                    $err->error = $e->getMessage();
                    echo json_encode($err);
                    exit;
                }

                $cloneData = new stdClass();
                $cloneData->id = 0;
                $cloneData->list_id = $this->clones_info[$listId]->listId;
                $cloneData->element_id = 0;
                $cloneData->join_from_table = $listname;
                $cloneData->table_join = $newTableNameSql;
                $cloneData->table_key = 'id';
                $cloneData->table_join_key = 'parent_id';
                $cloneData->join_type = 'left';
                $cloneData->group_id = $obj->group_id;

                $query = $db->getQuery(true);
                $query = "SELECT id FROM `#__fabrik_joins` WHERE `list_id` = '$cloneData->list_id' AND `join_from_table` = '$cloneData->join_from_table' AND `table_join` = '$cloneData->table_join'";
                $db->setQuery($query);
                $existId = $db->loadResult();

                if($existId) {
                    $cloneData->id = $existId;
                    $insert = $db->updateObject('#__fabrik_joins', $cloneData, 'id');
                } else {
                    $insert = $db->insertObject('#__fabrik_joins', $cloneData, 'id');
                }
                
                $cloneElementsFromThisGroup = $this->importCloneElements($elementsModel, $obj->group_id, $listId, $repeat->repeat_group_button);
                
                if ($groups_repeat_data){
                    $sql = str_replace("$oldTableNameSql", "$newTableNameSql", $groups_repeat_data);
                    $sql = str_replace("\\\\", "\\", $sql[0]);
                    $db->setQuery($sql);
    
                    try {
                        $db->execute();
                    } catch (RuntimeException $e) {
                        $err = new stdClass;
                        $err->error = $e->getMessage();
                        echo json_encode($err);
                        exit;
                    }
                }

            } else {
                $cloneElementsFromThisGroup = $this->importCloneElements($elementsModel, $obj->group_id, $listId);
                foreach ($group->joins as $key => $join) {
                    //$this->importCloneJoin($join, $key, '', $listId, $obj->group_id, 'list_join');
                }
            }

            if ((!$insert1) || (!$insert2) || (!$cloneElementsFromThisGroup)) {
                return false;
            }
        }

        return true;
    }

    protected function importCloneElements($elements, $group_id, $listId)
    {
        $db = JFactory::getDbo();

        foreach ($elements as $element) {
            $cloneData = $element;
            $cloneData = (object)$cloneData;
            $oldId = $cloneData->id;
            $cloneData->id = 0;
            $cloneData->group_id = $group_id;
            $cloneData->created_by = $this->user->id;
            $cloneData->created_by_alias = $this->user->username;

            $params = json_decode($cloneData->params);
            $joinOfElement = '';

            if ($cloneData->joinOfElement) {
                $joinOfElement = $cloneData->joinOfElement;
                unset($cloneData->joinOfElement);
            }

            $query = $db->getQuery(true);
            $query = "SELECT id FROM `#__fabrik_elements` WHERE `name` = '$cloneData->name' AND `group_id` = '$group_id'";
            $db->setQuery($query);
            $existId = $db->loadResult();

            if($existId) {
                $cloneData->id = $existId;
                $insert = $db->updateObject('#__fabrik_elements', $cloneData, 'id');
                $element_id = $existId;
            } else {
                $insert = $db->insertObject('#__fabrik_elements', $cloneData, 'id');
                $element_id = $db->insertId();
            }

            $this->clones_info[$listId]->mappedElements[(string)$oldId] = $element_id;

            if ($cloneData->plugin === 'databasejoin') {
                $this->elementsId[] = $element_id;
                $dbJoinMulti = array('checkbox', 'multilist');

                if (in_array($params->database_join_display_type, $dbJoinMulti)) {
                    $this->clones_info[$listId]->elementsRepeat[] = $cloneData->name;
                    $this->importCloneJoin($joinOfElement, $element_id, $cloneData->name, $listId, $group_id);
                } else {
                    $this->importCloneJoin($joinOfElement, $element_id, $cloneData->name, $listId, $group_id, 'dbjoin_single');
                }
            } else if (($cloneData->plugin === 'fileupload') && ((bool)$params->ajax_upload)) {
                $this->clones_info[$listId]->elementsRepeat[] = $cloneData->name;
                $this->importCloneJoin($joinOfElement, $element_id, $cloneData->name, $listId, $group_id);
            } else if ($cloneData->plugin === 'tags') {
                $this->clones_info[$listId]->elementsRepeat[] = $cloneData->name;
                $this->importCloneJoin($joinOfElement, $element_id, $cloneData->name, $listId, $group_id);
            } else if ($cloneData->plugin === 'user') {
                $this->importCloneJoin($joinOfElement, $element_id, $cloneData->name, $listId, $group_id, 'user');
            } else if ($cloneData->plugin === 'survey') {
                $this->clones_info[$listId]->elementsRepeat[] = $cloneData->name;
                $this->importCloneJoin($joinOfElement, $element_id, $cloneData->name, $listId, $group_id);
            } else if ($cloneData->plugin === 'suggest') {
                $this->suggestElementId = $element_id;
            }

            if (!$insert) {
                return false;
            }
        }

        return true;
    }

    protected function importCloneJoin($data, $element_id, $element_name, $listId, $group_id, $type = '')
    {
        $db = JFactory::getDbo();
        $cloneData = new stdClass();
        $cloneData->id = 0;

        if ($type === 'user') {
            $cloneData->list_id = 0;
            $cloneData->element_id = $element_id;
            $cloneData->join_from_table = '';
            $cloneData->table_join = '#__users';
            $cloneData->table_key = $data->table_key;
            $cloneData->table_join_key = $data->table_join_key;
            $cloneData->join_type = $data->join_type;
            $cloneData->group_id = $group_id;

            $params = array();
            $params['join-label'] = 'name';
            $params['type'] = 'element';
            $params['pk'] = '`#__users`.`id`';
            $params = (object)$params;
            $cloneData->params = json_encode($params);

        } else if ($type === 'list_join') {
            $cloneData->list_id = $this->clones_info[$listId]->listId;
            $cloneData->element_id = 0;
            $cloneData->join_from_table = $this->clones_info[$listId]->db_table_name;
            $cloneData->table_join = $this->clones_info[$listId]->listParams->table_join[$element_id];
            $cloneData->table_key = $this->clones_info[$listId]->listParams->table_key[$element_id];
            $cloneData->table_join_key = $this->clones_info[$listId]->listParams->table_join_key[$element_id];
            $cloneData->join_type = $this->clones_info[$listId]->listParams->join_type[$element_id];
            $cloneData->group_id = $group_id;
            $cloneData->params = $data;
        } else if ($type === 'dbjoin_single') {
            $cloneData->list_id = 0;
            $cloneData->element_id = $element_id;
            $cloneData->join_from_table = '';
            $cloneData->table_join = $data->table_join;
            $cloneData->table_key = $data->table_key;
            $cloneData->table_join_key = $data->table_join_key;
            $cloneData->join_type = $data->join_type;
            if ($data->group_id !== 0) {
                $cloneData->group_id = $group_id;
            } else {
                $cloneData->group_id = 0;
            }
            $params = $data->params;
            $cloneData->params = json_encode($params);
        } else {
            $cloneData->list_id = $this->clones_info[$listId]->listId;
            $cloneData->element_id = $element_id;
            $cloneData->join_from_table = $this->clones_info[$listId]->db_table_name;
            $cloneData->table_join = $this->clones_info[$listId]->db_table_name . '_repeat_' . $element_name;
            $cloneData->table_key = $data->table_key;
            $cloneData->table_join_key = $data->table_join_key;
            $cloneData->join_type = $data->join_type;

            if ($data->group_id !== 0) {
                $cloneData->group_id = $group_id;
            } else {
                $cloneData->group_id = 0;
            }

            $params = $data->params;
            $params->pk = str_replace($data->table_join, $cloneData->table_join, $params->pk);
            $cloneData->params = json_encode($params);
        }

        $insert = $db->insertObject('#__fabrik_joins', $cloneData, 'id');
        $this->clones_info[$listId]->newListJoinsIds[] = $db->insertid();

        return $insert;
    }

    protected function importCreateTable($listId, $tableSql)
    {
        $db = JFactory::getDbo();
        $tableName = $this->clones_info[$listId]->db_table_name;
        $oldTableName = $this->clones_info[$listId]->old_db_table_name;
      
        $query = $db->getQuery(true);
        $query->clear()->select($db->qn('table_name'))
            ->from($db->qn('information_schema.tables'))
            ->where($db->qn('table_name') . ' = ' . $db->q($tableName));
        $db->setQuery($query);
        if($db->loadResult()) {
            $db->setQuery("SHOW CREATE TABLE $tableName");
            $actualTable = $db->loadAssoc()["Create Table"];
            if($tableSql != $actualTable) {
                $db->setQuery("RENAME TABLE $tableName TO " . $this->checkTableName($tableName));
                $db->execute();
            }
        }
        $tableSql = str_replace("CREATE TABLE `$oldTableName`", "CREATE TABLE `$tableName`", $tableSql);
        $tableSql = str_replace("CREATE TABLE", "CREATE TABLE IF NOT EXISTS", $tableSql);

        $db->setQuery($tableSql);

        try {
            $db->execute();
        } catch (RuntimeException $e) {
            $err = new stdClass;
            $err->error = $e->getMessage();
            echo json_encode($err);
            exit;
        }

        return true;
    }

    protected function importCreateTableData($listId, $tableSql)
    {
        if ($tableSql){
            $db = JFactory::getDbo();
            $tableName = $this->clones_info[$listId]->db_table_name;
            $oldTableName = $this->clones_info[$listId]->old_db_table_name;
            $tableSql = str_replace("INSERT INTO $oldTableName", "INSERT INTO $tableName", $tableSql);
            $tableSql = str_replace("\\\\", "\\", $tableSql);
            $db->setQuery($tableSql);
            try {
                $db->execute();
            } catch (RuntimeException $e) {
                $err = new stdClass;
                $err->error = $e->getMessage();
                echo json_encode($err);
                exit;
            }
        }
        return true;
    }

    protected function importCreateTablesRepeat($listId, $tablesRepeatSql)
    {
        $db = JFactory::getDbo();
        $tableName = $this->clones_info[$listId]->db_table_name;
        $oldTableName = $this->clones_info[$listId]->old_db_table_name;

        foreach ($tablesRepeatSql as $tableRepeat) {
            preg_match("/CREATE TABLE `([^`]+)`/", $tableRepeat, $matches);
            $tableName = $matches[1];
            $query = $db->getQuery(true);
            $query->clear()->select($db->qn('table_name'))
                ->from($db->qn('information_schema.tables'))
                ->where($db->qn('table_name') . ' = ' . $db->q($tableName));
            $db->setQuery($query);
            if($db->loadResult()) {
                $db->setQuery("SHOW CREATE TABLE $tableName");
                $actualTable = $db->loadAssoc()["Create Table"];
                if($tableRepeat != $actualTable) {
                    $db->setQuery("RENAME TABLE $tableName TO " . $this->checkTableName($tableName));
                    $db->execute();
                }
            }
            
            $sql = str_replace("CREATE TABLE `$oldTableName`", "CREATE TABLE `$tableName`", $tableRepeat);
            $sql = str_replace("CREATE TABLE", "CREATE TABLE IF NOT EXISTS", $tableRepeat);
            $db->setQuery($sql);

            try {
                $db->execute();
            } catch (RuntimeException $e) {
                $err = new stdClass;
                $err->error = $e->getMessage();
                echo json_encode($err);
                exit;
            }
        }

        return true;
    }

    protected function importCreateTablesRepeatData($listId, $tablesRepeatSql)
    {
        if ($tablesRepeatSql){
            $db = JFactory::getDbo();
            $tableName = $this->clones_info[$listId]->db_table_name;
            $oldTableName = $this->clones_info[$listId]->old_db_table_name;
            foreach ($tablesRepeatSql as $tableRepeat) {
                $sql = str_replace("INSERT INTO $oldTableName", "INSERT INTO $tableName", $tableRepeat);
                $sql = str_replace("\\\\", "\\", $sql);
                $db->setQuery($sql);
                try {
                    $db->execute();
                } catch (RuntimeException $e) {
                    $err = new stdClass;
                    $err->error = $e->getMessage();
                    echo json_encode($err);
                    exit;
                }
            }
        }
        return true;
    }

    protected function importCreateMenuFabrik($listId, $menus){
        $db = JFactory::getDbo();
        
        // BEGIN - Solved problem with menu
        $menuItem = new MenusModelItem();
        // END - Solved problem with menu

        foreach ($menus as $menu){
            $cloneData = new stdClass();
            $cloneData = $menu;
            $cloneData = (object)$cloneData;
            $cloneData->id = 0;
            $list_id = $this->clones_info[$listId]->listId;
            $form_id = $this->clones_info[$listId]->listId;

            if ($menu->lists){
                foreach ($menu->lists as $list){
                    $list->id = 0;
                    $new_link = explode("listid=",$list->link)[0];
                    $list->link = $new_link . 'listid='.$list_id;

                    // BEGIN - Solved problem with menu
                    //$insert = $db->insertObject('#__menu', $list, 'id');
                    $menuItem->save((array) $list);
                    // END - Solved problem with menu
                }
            } elseif ($menu->forms){
                foreach ($menu->forms as $form){
                    $new_link = explode("formid=",$form->link)[0];
                    $form->link = $new_link . 'formid='.$form_id;
                    // BEGIN - Solved problem with menu
                    //$insert = $db->insertObject('#__menu', $form, 'id');
                    $menuItem->save((array) $form);
                    // END - Solved problem with menu
                }       
            } elseif ($menu->details){
                foreach ($menu->details as $detail){
                    $new_link = explode("formid=",$detail->link)[0];
                    $detail->link = $new_link . 'formid='.$form_id;
                    // BEGIN - Solved problem with menu
                    //$insert = $db->insertObject('#__menu', $detail, 'id');
                    $menuItem->save((array) $detail);
                    // END - Solved problem with menu
                }
            } elseif ($menu->csvs){
                foreach ($menu->csvs as $csv){
                    $new_link = explode("listid=",$csv->link)[0];
                    $csv->link = $new_link . 'listid='.$list_id;
                    // BEGIN - Solved problem with menu
                    //$insert = $db->insertObject('#__menu', $csv, 'id');
                    $menuItem->save((array) $csv);
                    // END - Solved problem with menu
                }
            } elseif ($menu->visualizations){
                foreach ($menu->visualizations as $visualization){
                    $new_link = explode("listid=",$visualization->link)[0];
                    $visualization->link = $new_link . 'listid='.$list_id;
                    // BEGIN - Solved problem with menu
                    //$insert = $db->insertObject('#__menu', $visualization, 'id');
                    $menuItem->save((array) $visualization);
                    // END - Solved problem with menu
                }
            }
        }
        return true;
    }

    protected function replaceElementsIdFormParams($listId)
    {
        $formParams = (array)$this->clones_info[$listId]->formParams;
        $mappedElements = $this->clones_info[$listId]->mappedElements;
        $mappedGroups = $this->clones_info[$listId]->mappedGroups;

        //Registration_certificate
        if (key_exists('arquivo', $formParams)) {
            $formParams['arquivo'] = (string)$mappedElements[$formParams['arquivo']];
            $formParams['hash'] = (string)$mappedElements[$formParams['hash']];
            $formParams['certificado'] = (string)$mappedElements[$formParams['certificado']];
            $formParams['grupo_pdf'] = (string)$mappedGroups[$formParams['grupo_pdf']];
        }

        //Online_contracts
        if (key_exists('element_dbjoin', $formParams)) {
            $formParams['element_dbjoin'] = (string)$mappedElements[$formParams['element_dbjoin']];
            $formParams['groupid_modelo'] = (string)$mappedGroups[$formParams['groupid_modelo']];
            $formParams['groupid_form'] = (string)$mappedGroups[$formParams['groupid_form']];
        }

        //Textextract
        if (key_exists('textextract_file_from', $formParams)) {
            $textextract_file_from = (array)$formParams['textextract_file_from'];
            $newTextExtract = array();
            foreach ($textextract_file_from as $key => $item) {
                $newTextExtract[$key] = (string)$mappedElements[$item];
            }
            if (is_object($formParams['textextract_file_from'])) {
                $formParams['textextract_file_from'] = (object)$newTextExtract;
            } else {
                $formParams['textextract_file_from'] = $newTextExtract;
            }
            $formParams['textextract_destination'] = (string)$mappedElements[$formParams['textextract_destination']];
        }

        //Url_capture
        if (key_exists('campo_field', $formParams)) {
            $formParams['campo_field'] = (string)$mappedElements[$formParams['campo_field']];
        }

        //Review
        if (key_exists('review_id_master', $formParams)) {
            $formParams['review_id_master'] = (string)$mappedElements[$formParams['review_id_master']];
            $formParams['review_status'] = (string)$mappedElements[$formParams['review_status']];
        }

        //Upsert
        if (key_exists('upsert_insert_only', $formParams)) {
            $keys = array('primary_key', 'upsert_fields', 'upsert_key');
            foreach ($this->clones_info as $id_list => $item) {
                $old_id = (array)$formParams['table'];
                if (in_array($id_list, $old_id)) {
                    $old_id[1] = $item->listId;
                }
                $formParams['table'] = (object)$old_id;
                foreach ($keys as $key) {
                    $old = json_encode($formParams[$key]);
                    if (strpos($old, $item->old_db_table_name) !== false) {
                        $new = str_replace($item->old_db_table_name, $item->db_table_name, $old);
                        $formParams[$key] = json_decode($new);
                    }
                }
            }
        }

        //Recursive_tree
        if (key_exists('list_elemento_origem', $formParams)) {
            $data = json_decode($formParams['list_elemento_origem']);
            $newData = array();
            foreach ($data->elemento_origem as $item) {
                $newData[] = (string)$mappedElements[$item];
            }
            $data->elemento_origem = $newData;
            $formParams['list_elemento_origem'] = json_encode($data);
            $data2 = json_decode($formParams['list_elemento_destino']);
            $newData2 = array();
            foreach ($data2->elemento_destino as $item) {
                $newData2[] = (string)$mappedElements[$item];
            }
            $data2->elemento_destino = $newData2;
            $formParams['list_elemento_destino'] = json_encode($data2);
        }

        //Metadata_Extract
        if (key_exists('thumb', $formParams)) {
            $keys = array('thumb', 'link', 'title', 'description', 'subject', 'creator', 'date', 'format', 'coverage', 'publisher', 'identifier', 'language', 'type', 'contributor', 'relation', 'rights', 'source');
            foreach ($keys as $key) {
                $formParams[$key] = (string)$mappedElements[$formParams[$key]];
            }
        }

        $formParams = (object)$formParams;

        $obj = new stdClass();
        $obj->id = $this->clones_info[$listId]->formId;
        $obj->params = json_encode($formParams);
        $update = JFactory::getDbo()->updateObject('#__fabrik_forms', $obj, 'id');

        if (!$update) {
            return false;
        }

        return true;
    }

    protected function replaceElementsIdListParams($listId)
    {
        $listParams = (array)$this->clones_info[$listId]->listParams;
        $mappedElements = $this->clones_info[$listId]->mappedElements;
        $mappedGroups = $this->clones_info[$listId]->mappedGroups;

        //Access
        $listParams['allow_edit_details'] = (string)$this->permissionLevel;
        $listParams['allow_delete'] = (string)$this->permissionLevel;

        //List Search Elements
        $data = json_decode($listParams['list_search_elements']);
        $newData = array();
        foreach ($data->search_elements as $item) {
            $newData[] = (string)$mappedElements[$item];
        }
        $data->search_elements = $newData;
        $listParams['list_search_elements'] = json_encode($data);

        //Thumbnail
        if (key_exists('thumbnail', $listParams)) {
            $listParams['thumbnail'] = (string)$mappedElements[$listParams['thumbnail']];
        }

        //Titulo
        if (key_exists('titulo', $listParams)) {
            $listParams['titulo'] = (string)$mappedElements[$listParams['titulo']];
        }

        //Feed title
        if (key_exists('feed_title', $listParams)) {
            $listParams['feed_title'] = (string)$mappedElements[$listParams['feed_title']];
        }

        //Feed date
        if (key_exists('feed_date', $listParams)) {
            $listParams['feed_date'] = (string)$mappedElements[$listParams['feed_date']];
        }

        //Feed image
        if (key_exists('feed_image_src', $listParams)) {
            $listParams['feed_image_src'] = (string)$mappedElements[$listParams['feed_image_src']];
        }

        //Open Archive Elements
        if ($listParams['open_archive_elements']) {
            $data2 = json_decode($listParams['open_archive_elements']);
            $newData2 = array();
            foreach ($data2->dublin_core_element as $item) {
                $newData2[] = (string)$mappedElements[$item];
            }
            $data2->dublin_core_element = $newData2;
            $listParams['open_archive_elements'] = json_encode($data2);
        }

        //Search Title
        if (key_exists('search_title', $listParams)) {
            $listParams['search_title'] = (string)$mappedElements[$listParams['search_title']];
        }

        //Search Description
        if (key_exists('search_description', $listParams)) {
            $listParams['search_description'] = (string)$mappedElements[$listParams['search_description']];
        }

        //Search Date
        if (key_exists('search_date', $listParams)) {
            $listParams['search_date'] = (string)$mappedElements[$listParams['search_date']];
        }

        //Filter fields
        if ($listParams['filter-fields']) {
            $filter_fields = $listParams['filter-fields'];
            $newFields = array();
            foreach ($filter_fields as $field) {
                $newFields[] = $field;
            }
            $listParams['filter-fields'] = $newFields;
        }

        //Order by
        $order_by = json_decode($this->clones_info[$listId]->orderByList);
        $newOrder_by = array();
        foreach ($order_by as $item) {
            $newOrder_by[] = (string)$mappedElements[$item];
        }

        //List Joins
        $listParams['join_id'] = $this->clones_info[$listId]->newListJoinsIds;
        if ($listParams['join_from_table']) {
            foreach ($listParams['join_from_table'] as $key => $item) {
                if ($item === $this->clones_info[$listId]->old_db_table_name) {
                    $listParams['join_from_table'][$key] = $this->clones_info[$listId]->db_table_name;
                }
            }
        }

        $listParams = (object)$listParams;

        $obj = new stdClass();
        $obj->id = $this->clones_info[$listId]->listId;
        $obj->order_by = JFactory::getDbo()->escape(json_encode($newOrder_by));
        $obj->params = json_encode($listParams);
        $update = JFactory::getDbo()->updateObject('#__fabrik_lists', $obj, 'id');

        if (!$update) {
            return false;
        }

        return true;
    }

    /**
     * Criar grupo repetitivel.
     *
     * @since V0.1
     *
     * @author Renan Aquino
     * @version V0.2
     */
    protected function exportCreateGroupsRepeat($groups, $listId)
    {
        $db = JFactory::getDbo();
        $groupsRepeat = array();
        $oldTableName = $this->clones_info[$listId]->old_db_table_name;

        foreach ($groups as $groupModel) {
            if ($groupModel->group->is_join == 1) {
                $groupName = $oldTableName . '_' . $groupModel->group->id . '_repeat';
                $db->setQuery("SHOW CREATE TABLE $groupName");
                $res = $db->loadAssoc()["Create Table"];
                $groupsRepeat[] = $res;
            }
        }

        return $groupsRepeat;
    }

    protected function importCreateGroupsRepeat($groups_repeat, $list)
    {
        $db = JFactory::getDbo();

        foreach ($groups_repeat as $groupRepeat) {
            $sql = 'SELECT * FROM `#__fabrik_groups` ORDER BY id DESC LIMIT 1';
            $db->setQuery($sql);
            $lastIdGroup = $db->loadResult();
            if (!$lastIdGroup){
                $lastIdGroup = 1;
            } 

            $idList = ($list->oldListId);
            $listname = $list->clones_info->$idList->old_db_table_name;
            $newTableNameSql = $listname . '_' . ($lastIdGroup) . '_repeat';
            $oldTableNameSql = explode('`', $groupRepeat)[1];
            $newSql = str_replace($oldTableNameSql, $newTableNameSql, $groupRepeat);
            $db->setQuery($newSql);

            try {
                $db->execute();
            } catch (RuntimeException $e) {
                $err = new stdClass;
                $err->error = $e->getMessage();
                echo json_encode($err);
                exit;
            }

            $cloneData = new stdClass();
            $cloneData->id = 0;
            $cloneData->list_id = $this->clones_info[$idList]->listId;
            $cloneData->element_id = 0;
            $cloneData->join_from_table = $listname;
            $cloneData->table_join = $newTableNameSql;
            $cloneData->table_key = 'id';
            $cloneData->table_join_key = 'parent_id';
            $cloneData->join_type = 'left';
            $cloneData->group_id = $lastIdGroup;
            $insert = $db->insertObject('#__fabrik_joins', $cloneData, 'id');
        }

        return true;
    }

    /**
     * Method that performs the change of the list/table by the typed name.
     *
     * @return void
     * @throws Exception
     * @since V0.1
     *
     * @author Hirlei Carlos
     * @version V0.2
     */
    public function submitChangeList()
    {
        $db = JFactory::getDbo();
        $app = JFactory::getApplication();
        $id_list = $app->input->getInt("nameList", 0);
        $new_name = $app->input->getString("name", null);

        if (!empty($new_name) && ($id_list != 0)) {
            $new_name_treated = strtolower(str_replace(" ", "_", $this->removeAccentsSpecialCharacters($new_name)));

            $select = "SELECT * FROM #__fabrik_lists AS `table` WHERE `table`.id = $id_list;";
            $db->setQuery($select);
            $list = $db->loadObject();

            if (!empty($list)) {
                $name_list = $list->db_table_name;
                $fabrik = $this->tableFabrikParams('params', 'fabrik', $name_list, $new_name_treated, $id_list);

                if ($fabrik) {
                    $select = "SELECT table_name FROM INFORMATION_SCHEMA.TABLES
                                WHERE table_schema in (SELECT DATABASE())
                                and (table_name = '$name_list' or table_name like '{$name_list}_%');";
                    $db->setQuery($select);
                    $obj_tables = $db->loadObjectList();

                    $update = "";

                    try {
                        $db->transactionStart();

                        if (!empty($obj_tables)) {
                            $update = "RENAME TABLE ";

                            foreach ($obj_tables as $key => $obj_table) {
                                $new_table = str_replace($name_list, $new_name_treated, $obj_table->table_name);

                                if ($key == 0) {
                                    $update .= "$obj_table->table_name TO $new_table";
                                } else {
                                    $update .= ", $obj_table->table_name TO $new_table";
                                }
                            }

                            $update .= ";";
                        }

                        $db->setQuery($update);
                        $db->execute();

                        $db->transactionCommit();
                        $message = JText::_("COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_SUCCESS5");
                        $type_message = JText::_("COM_ADMINISTRATIVETOOLS_SUCCESS");
                    } catch (Exception $exc) {
                        $db->transactionRollback();
                        $message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR5');
                        $type_message = JText::_("COM_ADMINISTRATIVETOOLS_ERROR");
                    }
                } else {
                    $message = JText::_("COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR7");
                    $type_message = JText::_("COM_ADMINISTRATIVETOOLS_ERROR");
                }
            } else {
                $message = JText::_("COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR6");
                $type_message = JText::_("COM_ADMINISTRATIVETOOLS_WARNING");
            }
        } else {
            $message = JText::_("COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR6");
            $type_message = JText::_("COM_ADMINISTRATIVETOOLS_WARNING");
        }

        $site_message = JUri::base() . "index.php?option=com_administrativetools&view=tools&tab=5";
        $this->setRedirect($site_message, $message, $type_message);
    }

    /**
     * Method that takes the database tables all related to fabrik except the list and join that are differentiated
     *
     * @param $col_name
     * @param $fabrik
     * @param $name_list
     * @param $new_name_treated
     * @param $list_id
     * @return bool
     * @author Hirlei Carlos
     * @version V0.2
     * @since V0.1
     */
    public function tableFabrikParams($col_name, $fabrik, $name_list, $new_name_treated, $list_id)
    {
        $config = JFactory::getConfig();
        $db = JFactory::getDbo();

        try {
            $db->transactionStart();

            $select = "SELECT table_name FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE table_schema in (SELECT DATABASE()) 
                        and COLUMN_NAME='$col_name' 
                        and table_name like '%$fabrik%';";
            $db->setQuery($select);
            $obj_tables = $db->loadObjectList();

            foreach ($obj_tables as $obj_table) {
                $table = str_replace($config->get("dbprefix"), "", $obj_table->table_name);
                $select1 = "SELECT DISTINCT * FROM $obj_table->table_name AS `table` WHERE ";

                if ($table === "fabrik_lists") {
                    $select1 .= "`table`.db_table_name LIKE '%$name_list%' or `table`.params LIKE '%$name_list%';";
                } elseif ($table === "fabrik_joins") {
                    $select1 .= "`table`.table_join LIKE '%$name_list%' or `table`.params LIKE '%$name_list%';";
                } elseif ($table === "fabrik_elements") {
                    $select1 .= "`table`.default LIKE '%$name_list%' or `table`.params LIKE '%$name_list%';";
                } else {
                    $select1 .= "`table`.params LIKE '%$name_list%';";
                }

                $db->setQuery($select1);
                $obj_lists = $db->loadObjectList();

                if (!empty($obj_lists)) {
                    foreach ($obj_lists as $item) {
                        $params = str_replace($name_list, $new_name_treated, $item->params);
                        $update = "UPDATE $obj_table->table_name SET ";

                        if ($table === "fabrik_lists") {
                            $db_primary_key = str_replace($name_list, $new_name_treated, $item->db_primary_key);
                            $update .= "db_table_name = '$new_name_treated', db_primary_key = '$db_primary_key', ";
                        } elseif ($table === "fabrik_joins") {
                            if (!empty($item->join_from_table)) {
                                $table_join = str_replace($name_list, $new_name_treated, $item->table_join);
                                $update .= "join_from_table = '$new_name_treated', table_join = '$table_join', ";
                            } else {
                                $update .= "table_join = '$new_name_treated', ";
                            }
                        } elseif ($table === "fabrik_elements") {
                            if (!empty($item->default)) {
                                $default = str_replace($name_list, $new_name_treated, $item->default);
                                $update .= "`default` = '{$db->escape($default)}', ";
                            }
                        }

                        $update .= "params = '{$db->escape($params)}' WHERE id = $item->id";
                        $db->setQuery($update);
                        $db->execute();
                    }
                }
            }

            $db->transactionCommit();
            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();
            return false;
        }
    }

    /**
     * Fabrik sync lists 1.0
     * 
     * Method that process the submit of sync list
     *
     */
    public function submitSyncLists()
    {
        $app = JFactory::getApplication();
        $model = $this->getModel();
        $input = $app->input;

        $data = new stdClass();
        $data->host = $input->getString('host');
        $data->port = $input->getString('port');
        $data->name = $input->getString('name');
        $data->prefix = $input->getString('prefix');
        $data->user = $input->getString('user');
        $data->password = $input->getString('password');
        $data->model_type = $input->getString('model_type');
        $data->data_type = $input->getString('data_type');
        $data->connectSync = $input->getString('connectSync', false);
        $data->saveConfiguration = $input->getString('saveConfiguration', false);
        $data->syncLists = $input->getString('syncLists', false);
        $data->joomla_menus = $input->getBool('joomla_menus', false);
        $data->joomla_modules = $input->getBool('joomla_modules', false);
        $data->joomla_themes = $input->getBool('joomla_themes', false);
        $data->joomla_extensions = $input->getBool('joomla_extensions', false);

        foreach($data as $key => $value) {
            if($key == 'saveConfiguration' || $key == 'connectSync' || $key == 'syncLists') {
                if($value) {
                    $method = $key;    
                } else {
                    unset($data->$key);
                }
            }
        }

        $result = $this->$method($data);

        $site_message = JUri::base() . 'index.php?option=com_administrativetools&tab=6';
        $this->setRedirect($site_message, $result->message, $result->type_message);
    }

    /**
     * Fabrik sync lists 1.0
     * 
     * Method that process the submit of sync list
     *
     */
    private function syncLists($data)
    {
        $model = $this->getModel();
        $resultSync = new stdClass();

        if(!$data->syncLists) {
            return false;
        }

        $sync = $model->syncLists($data);

        if (!$sync) {
            $resultSync->message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR_SYNC_LISTS');
            $resultSync->type_message = 'error';
        } else {
            $resultSync->message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_SUCCESS_SYNC_LISTS');
            $resultSync->type_message = 'success';
        }

        return $resultSync;
    }

    /**
     * Fabrik sync lists 1.0
     * 
     * Method that save the configuration of sync list
     *
     */
    private function saveConfiguration($data)
    {
        $model = $this->getModel();
        $resultSave = new stdClass();

        if(!$data->saveConfiguration) {
            return false;
        }

        $saved = $model->saveConfiguration($data);

        if (!$saved) {
            $resultSave->message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR_SAVE_CONFIGURATION');
            $resultSave->type_message = 'error';
        } else {
            $resultSave->message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_SUCCESS_SAVE_CONFIGURATION');
            $resultSave->type_message = 'success';
        }

        return $resultSave;
    }

    /**
     * Fabrik sync lists 1.0
     * 
     * Method that test the connection of the configuration of sync list
     *
     */
    private function connectSync($data)
    {
        $model = $this->getModel();
        $resultConnection = new stdClass();

        if(!$data->connectSync) {
            return false;
        }

        $connection = $model->connectSync($data);

        if (!$connection) {
            $resultConnection->message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR_CONNECT_SYNC');
            $resultConnection->type_message = 'error';
        } else {
            $resultConnection->message = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_SUCCESS_CONNECT_SYNC');
            $resultConnection->type_message = 'success';
        }

        return $resultConnection;
    }

    /**
     * Method that performs database detection and cleaning.
     *
     * @author Marcelo Miranda
     * 2023-02
     */
    public function showDifferentTablesInDatabase()
    {
        $app    = JFactory::getApplication();
        $db     = JFactory::getDbo();
        $config = JFactory::getConfig();
        
        $sql = "SELECT TABLE_NAME AS nome_tabela FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = 'devcett' 
                AND table_name NOT IN (SELECT db_table_name FROM joomla_fabrik_lists) 
                AND table_name NOT like 'joomla_%' 
                ORDER BY 1";
        
        $db->setQuery($sql);
        $lista = $db->loadObjectList();

        echo json_encode($lista);
        $app->close();
    }

    /**
     * Method that performs database detection and cleaning.
     *
     * @author Marcelo Miranda
     * 2023-02
     */
    public function showDifferentFiledsInTables()
    {
        $app    = JFactory::getApplication();
        $db     = JFactory::getDbo();
        $config = JFactory::getConfig();
        
        /* recupera os nomes de tabela no BANCO DE DADOS */
        $sql = " SELECT  d1.TABLE_NAME nome_tabela  
                FROM INFORMATION_SCHEMA.TABLES d1  
                WHERE TABLE_schema ='devcett'  
                AND table_name NOT like 'joomla_%' 
                and d1.table_name != ''  
                ORDER BY 1 ";

        $db->setQuery($sql);
        $lista_bd = $db->loadColumn();

        /* recupera os nomes de tabela no FABRIK */
        $sql = " SELECT db_table_name nome_tabela 
        FROM joomla_fabrik_lists 
        where db_table_name NOT like 'joomla_%'   
        and db_table_name != ''  
        ORDER BY 1 ;";

        $db->setQuery($sql);
        $lista_fa = $db->loadColumn();


        $colunasNaoExistentes = [];

        /* percorre pelas tabelas no banco de dados */
        foreach ($lista_bd as $key => $nome_tabela_banco) {

            /* se a tabela no banco existir no fabrik continua a rotina de colunas */
            if (array_search($nome_tabela_banco, $lista_fa)) {

                /* recupera as colunas da tabela seleciona no banco de dados */
                $sql1 = "SELECT d1.COLUMN_NAME
                    FROM information_schema.columns d1 
                    WHERE TABLE_schema ='devcett' 
                    AND d1.TABLE_NAME =  '$nome_tabela_banco' ORDER BY 1";
                $db->setQuery($sql1);
                $colunas_bd = $db->loadColumn();

                /* ======================================================================= */

                /* recupera as colunas da tabela seleciona no fabrik */
                $sql2 = "SELECT t1.name
                        FROM joomla_fabrik_elements t1, joomla_fabrik_lists t2
                        WHERE t1.group_id = t2.id
                        AND t2.db_table_name =  '$nome_tabela_banco' ORDER BY 1";
                        
                $db->setQuery($sql2);
                $colunas_fabrik = $db->loadColumn();

                /* ======================================================================= */

                /* percorre pelos colunas da tabela no banco de dados para verificar se existe no fabrik */
                foreach ($colunas_bd as $key => $nome_coluna_banco) {

                    /* se a coluna da tabela do banco existir no fabrik continua, senão registra a diferença */
                    if ( ! array_search($nome_coluna_banco, $colunas_fabrik)) {
                
                        array_push($colunasNaoExistentes,$nome_tabela_banco ."." .$nome_coluna_banco) ;
                    }

                }
                
            }
            
        }
        echo json_encode($colunasNaoExistentes);
        $app->close();
    }

    public function deleteTablesAndFieldsBd() 
    {
        $retorno = [];
        try {
            $app    = JFactory::getApplication();
            $db     = JFactory::getDbo();
            $config = JFactory::getConfig();
    
            $tbs = $app->input->getModel("tbs");
            $fds = $app->input->getModel("fds");

            array_push($retorno, "===== TABELAS =====");
            foreach ($tbs as $key => $value) {
                if( $db->dropTable($value) ){
                    array_push($retorno, $value);
                };
                array_push($retorno, $value);
            }

            array_push($retorno, "===== CAMPOS =====");
            foreach ($fds as $key => $value) {
                
                $tab_field = explode(".",$value);

                $query = "ALTER TABLE $tab_field[0] DROP COLUMN $tab_field[1];";
                $db->setQuery($query);
                if( $db->execute() ){
                    array_push($retorno, $value);
                };
                array_push($retorno, $value);
            }
    
            $user       = JFactory::getUser();
            $path       = getcwd() .'/components/com_administrativetools/logs/';
            $file_log   = 'cleandb.log';
 
            if (!file_exists($path .$file_log)) {
                mkdir($path, 0777, true);
            }

            $fp = fopen($path .$file_log, 'a+'); 

            $textoLog = "\n" . "Limpeza realizada por: " .$user->name . " em " .date("d/m/Y") . " - " . date("h:i:sa");
            fwrite($fp,  $textoLog ) ; 

            foreach ($retorno as $key => $value) {
                fwrite($fp, "\n" . $value); 
            }
            fwrite($fp, "\n\n\n" ); 

            fclose($fp);

            echo json_encode($retorno);



        } catch (Exception $e) {
            echo $e->getMessage();
        }

        
        $app->close();
    }

    public function pluginsManagerListElement()
    {
        $app = JFactory::getApplication();
        $db = JFactory::getDbo();

        $tipo = $app->input->getInt("typeName");

        if ($tipo == 1) {
            $sql = "SELECT form.id,form.label FROM joomla_fabrik_forms AS form ORDER BY form.label ASC;";
        } elseif ($tipo == 2)  {
            $sql = "SELECT list.id, list.label FROM joomla_fabrik_lists AS list ORDER BY list.label ASC;";
        } else {
            $sql = "SELECT 0 ;";
        }
        
        $db->setQuery($sql);

        $list = $db->loadObjectList();

        if (count($list) > 0) {
            foreach ($list as $key => $value) {
                $list[$key]->params     = ($value->params);
                $list[$key]->paramList  = ($value->paramList);
            }

            echo json_encode($list);
        } else {
            echo '0';
        }

        $app->close();
    }

    public function pluginsManagerTypeParams()
    {
        $app = JFactory::getApplication();
        $db = JFactory::getDbo();

        $typeName = $app->input->getInt("typeName");
        $idList = $app->input->getInt("idList");


        if ($typeName == 1) {
            $sql = "SELECT form.params FROM joomla_fabrik_forms AS form where form.id = $idList;";
        } elseif ($typeName == 2)  {
            $sql = "SELECT list.params FROM joomla_fabrik_lists AS list where list.id = $idList;";
        } else {
            $sql = "SELECT 0 ;";
        }
        
        $db->setQuery($sql);

        $list = $db->loadObjectList();

      
        if (count($list) > 0) {
            foreach ($list as $key => $value) {
                $list[$key]->params     = json_decode($value->params);
                $list[$key]->paramList  = json_decode($value->paramList);
            }

            echo json_encode($list);
        } else {
            echo '0';
        }
        
        $app->close();
    }

    public function pluginsManagerListObjects()
    {
        $app    = JFactory::getApplication();
        $db     = JFactory::getDbo();
        $config = JFactory::getConfig();
        
        $typeName    = $app->input->getInt("typeName");
        $idList      = $app->input->getString("idList");
        $pluginName  = $app->input->getString("pluginName");
        $action      = $app->input->getString("action");
        
        //echo($typeName);

        if($typeName == "1"){ 
            //formulário
            $sql = " SELECT id, label
            FROM joomla_fabrik_forms 
            ORDER BY 2 ;";
        }elseif($typeName == "2"){ 
            //Lista
            $sql = " SELECT id, label
            FROM joomla_fabrik_lists 
            where db_table_name NOT like 'joomla_%'   
            and db_table_name != ''  
            ORDER BY 2 ;";
        }

        $db->setQuery($sql);
        $sqlReturn = $db->loadRowList();

        $objetos = [['0','Todos']];
        foreach ($sqlReturn as $key => $linha) {
            array_push($objetos,$linha) ;
        }
        echo json_encode($objetos);
        $app->close();
    }


    public function pluginsManagerModifyForms() 
    {
        $app    = JFactory::getApplication();
        $db     = JFactory::getDbo();
        $config = JFactory::getConfig();

        //$typeName           = $app->input->getInt("typeName");
        $idList             = $app->input->getString("idList");
        $pluginName         = $app->input->getString("pluginName");
        $action             = $app->input->getString("action");
        $selected_objects   = $app->input->getString("selected_objects");
        
        $plugin_selecionado =  explode(";", $pluginName) ;
        $log     = [];   

        //log
        array_push($log, "===== FORMULÁRIO(S) =====");
        if($action == 1){ 
            array_push($log, "Adicionado o  $plugin_selecionado[1]  - $plugin_selecionado[2]   em:");
        }else if($action == 2){
            array_push($log, "Removido o  $plugin_selecionado[1]  - $plugin_selecionado[2]  de:");
        }


        //significa que foi marcada a opção "TODOS" na caixa de seleção
        //busca todos os objetos da tabela
        if($selected_objects[0] == 0){
            $selected_objects = [];
            $sql = " SELECT t1.id FROM joomla_fabrik_forms as t1 order by t1.label ;";
            $db->setQuery($sql);
            $resultado = $db->loadObjectList();
            foreach ($resultado as $key => $object) {
                array_push($selected_objects, $object->id);
            }
        }
        

        //loop principal pelos objetos
        foreach ($selected_objects as $key => $object_id) {
            if ($object_id == 26){
                $a= 1;
            }
            
            //busca o nome do objeto para colocar na log
            $sql = " SELECT t1.label FROM joomla_fabrik_forms as t1 where t1.id = $object_id ;";
            $db->setQuery($sql);
            $objeto = $db->loadObject();
            array_push($log, $objeto->label);

            
            $sql = " SELECT t1.params FROM joomla_fabrik_forms as t1 where t1.id = $object_id ;";
            $db->setQuery($sql);
            $return_params = $db->loadObjectList();

            $campo = [];
            array_push($campo, $return_params);
            $campo_params_array = json_decode($campo[0][0]->params, true);

            //lista dos plugins que já possui, usado no teste  
            $pluginsQuePossui               = json_decode($campo[0][0]->params)->plugins ;
            $plugin_descriptionQuePossui    = json_decode($campo[0][0]->params)->plugin_description ;
                        
            //qtd de plugins existentes
            $qtdPlugins                     = count(json_decode($campo[0][0]->params)->plugins);


            if($action == 1){ //opção ADICIONAR plugin
                if( $qtdPlugins == 0){
                    $query = "UPDATE joomla_fabrik_forms t1 
                            SET t1.params = 
                            JSON_INSERT(t1.params, 
                                '$.plugin_condition[$qtdPlugins]',      '$plugin_selecionado[0]',
                                '$.plugin_description[$qtdPlugins]',    '$plugin_selecionado[1]',
                                '$.plugin_events[$qtdPlugins]',         '$plugin_selecionado[2]',
                                '$.plugin_locations[$qtdPlugins]',      '$plugin_selecionado[3]',
                                '$.plugin_state[$qtdPlugins]',          '$plugin_selecionado[4]',
                                '$.plugins[$qtdPlugins]',               '$plugin_selecionado[5]'
                            )
                            WHERE t1.id = $object_id";
        
                    $db->setQuery($query);
                    $db->execute();

                }else{
                    $adiciona = true;

                    for ($i=0; $i < $qtdPlugins ; $i++) { 
                        //testa para verificar se ele já possui o novo plugin
                        if( ($plugin_selecionado[5] == $pluginsQuePossui[$i]) && ($plugin_selecionado[1] == $plugin_descriptionQuePossui[$i]) ){
                            $adiciona = false;
                        }
                    }
                    
                    
                    if($adiciona == TRUE){
        
                        $query = "UPDATE joomla_fabrik_forms t1 
                                SET t1.params = 
                                JSON_INSERT(t1.params, 
                                    '$.plugin_condition[$qtdPlugins]',      '$plugin_selecionado[0]',
                                    '$.plugin_description[$qtdPlugins]',    '$plugin_selecionado[1]',
                                    '$.plugin_events[$qtdPlugins]',         '$plugin_selecionado[2]',
                                    '$.plugin_locations[$qtdPlugins]',      '$plugin_selecionado[3]',
                                    '$.plugin_state[$qtdPlugins]',          '$plugin_selecionado[4]',
                                    '$.plugins[$qtdPlugins]',               '$plugin_selecionado[5]'
                                )
                                WHERE t1.id = $object_id";
            
                        $db->setQuery($query);
                        $db->execute();
                        array_push($log, "                  Adicionado");
                    } else{
                        array_push($log, "                  Já possuía o plugin");
                    }  
    
                    
                }

            }else if($action == 2){  //opção REMOVER plugin


                if( $qtdPlugins == 0){
                    array_push($log, "Não possuia nenhum plugin!");
                }else{
                    for ($i=0; $i < $qtdPlugins ; $i++) { 
                        $busca  = [];
                        $remove = [];

                        $sql = "SELECT t1.params from joomla_fabrik_forms t1 WHERE  t1.id = $object_id;";
                        $db->setQuery($sql);
                        $campo_params = $db->loadObjectList();

                        if( ($plugin_selecionado[5] == $pluginsQuePossui[$i]) && ($plugin_selecionado[1] == $plugin_descriptionQuePossui[$i]) ){
                            try {

                                
                                $busca[0]  = "SELECT JSON_CONTAINS_PATH(t1.params,'one', '$.plugin_condition') from joomla_fabrik_forms t1 WHERE  t1.id = $object_id";
                                $busca[1]  = "SELECT JSON_CONTAINS_PATH(t1.params,'one', '$.plugin_description') from joomla_fabrik_forms t1 WHERE  t1.id = $object_id";
                                $busca[2]  = "SELECT JSON_CONTAINS_PATH(t1.params,'one', '$.plugin_events') from joomla_fabrik_forms t1 WHERE  t1.id = $object_id";
                                $busca[3]  = "SELECT JSON_CONTAINS_PATH(t1.params,'one', '$.plugin_locations') from joomla_fabrik_forms t1 WHERE  t1.id = $object_id";
                                $busca[4]  = "SELECT JSON_CONTAINS_PATH(t1.params,'one', '$.plugin_state') from joomla_fabrik_forms t1 WHERE  t1.id = $object_id";
                                $busca[5]  = "SELECT JSON_CONTAINS_PATH(t1.params,'one', '$.plugins') from joomla_fabrik_forms t1 WHERE  t1.id = $object_id";
        


                                $remove[0] = "update joomla_fabrik_forms t1 set t1.params = JSON_REMOVE(t1.params,'$.plugin_condition[$i]') WHERE  t1.id = $object_id";
                                $remove[1] = "update joomla_fabrik_forms t1 set t1.params = JSON_REMOVE(t1.params,'$.plugin_description[$i]') WHERE  t1.id = $object_id";
                                $remove[2] = "update joomla_fabrik_forms t1 set t1.params = JSON_REMOVE(t1.params,'$.plugin_events[$i]') WHERE  t1.id = $object_id";
                                $remove[3] = "update joomla_fabrik_forms t1 set t1.params = JSON_REMOVE(t1.params,'$.plugin_locations[$i]') WHERE  t1.id = $object_id";
                                $remove[4] = "update joomla_fabrik_forms t1 set t1.params = JSON_REMOVE(t1.params,'$.plugin_state[$i]') WHERE  t1.id = $object_id";
                                $remove[5] = "update joomla_fabrik_forms t1 set t1.params = JSON_REMOVE(t1.params,'$.plugins[$i]') WHERE  t1.id = $object_id";
                                

                                for ($j=0; $j < 6 ; $j++) { 
                                    $db->setQuery($busca[$j]);
                                    $result[$j] = $db->loadResult();

                                    $db->transactionStart();
                                    if (count($result[$j]) > 0) {
                                        $db->setQuery($remove[$j]);
                                        $campo_alterado = $db->execute();
                                        
                                    }
                                }


                                //monta os sqls para verificar se o obleto contem o path a ser removido o json
                                // $busca = [];
                                // $busca[0]  = "SELECT JSON_CONTAINS_PATH(t1.params,'one', '$.plugin_condition') from joomla_fabrik_forms t1 WHERE  t1.id = $object_id";
                                // $busca[1]  = "SELECT JSON_CONTAINS_PATH(t1.params,'one', '$.plugin_description') from joomla_fabrik_forms t1 WHERE  t1.id = $object_id";
                                // $busca[2]  = "SELECT JSON_CONTAINS_PATH(t1.params,'one', '$.plugin_events') from joomla_fabrik_forms t1 WHERE  t1.id = $object_id";
                                // $busca[3]  = "SELECT JSON_CONTAINS_PATH(t1.params,'one', '$.plugin_locations') from joomla_fabrik_forms t1 WHERE  t1.id = $object_id";
                                // $busca[4]  = "SELECT JSON_CONTAINS_PATH(t1.params,'one', '$.plugin_state') from joomla_fabrik_forms t1 WHERE  t1.id = $object_id";
                                // $busca[5]  = "SELECT JSON_CONTAINS_PATH(t1.params,'one', '$.plugins') from joomla_fabrik_forms t1 WHERE  t1.id = $object_id";
        
                                // $remove = [];
                                // $remove[0] = "UPDATE joomla_fabrik_forms t1 SET t1.params = JSON_REMOVE(t1.params,'$.plugin_condition[$i]') WHERE t1.id = $object_id";
                                // $remove[1] = "UPDATE joomla_fabrik_forms t1 SET t1.params = JSON_REMOVE(t1.params,'$.plugin_description[$i]') WHERE t1.id = $object_id";
                                // $remove[2] = "UPDATE joomla_fabrik_forms t1 SET t1.params = JSON_REMOVE(t1.params,'$.plugin_events[$i]') WHERE t1.id = $object_id";
                                // $remove[3] = "UPDATE joomla_fabrik_forms t1 SET t1.params = JSON_REMOVE(t1.params,'$.plugin_locations[$i]') WHERE t1.id = $object_id";
                                // $remove[4] = "UPDATE joomla_fabrik_forms t1 SET t1.params = JSON_REMOVE(t1.params,'$.plugin_state[$i]') WHERE t1.id = $object_id";
                                // $remove[5] = "UPDATE joomla_fabrik_forms t1 SET t1.params = JSON_REMOVE(t1.params,'$.plugins[$i]') WHERE t1.id = $object_id";



                                // for ($j=0; $j < 6 ; $j++) { 
                                //     $db->setQuery($busca[$j]);
                                //     $result[$j] = $db->loadResult();

                                //     $db->transactionStart();
                                //     if (count($result[$j]) > 0) {
                                //         $db->setQuery($remove[$j]);
                                //         $db->execute();
                                //     }
                                // }




                                $db->transactionCommit();
                            } catch (Exception $exc) {
                                $db->transactionRollback();
                                print_r($exc);
                                die();
                            }
                
                            
                        }else{

                            if($i == $qtdPlugins - 1){
                                array_push($log, "Não possuia.");
                            }
                        }
                    }
                }
            }
        }

        //geração do arquivo de LOG
        $user       = JFactory::getUser();
        $path       = getcwd() .'/components/com_administrativetools/logs/';
        $file_log   = 'pluginsManager.log';

        if (!file_exists($path .$file_log)) {
            mkdir($path, 0777, true);
        }

        $fp = fopen($path .$file_log, 'a+'); 

        $textoLog = "\n" . "Alteração realizada por: " .$user->name . " em " .date("d/m/Y") . " - " . date("h:i:sa");
        fwrite($fp,  $textoLog ) ; 

        foreach ($log as $key => $value) {
            fwrite($fp, "\n" . $value); 
        }
        fwrite($fp, "\n\n\n" ); 

        fclose($fp);
        
        echo json_encode($log);
        $app->close();

    }
}
