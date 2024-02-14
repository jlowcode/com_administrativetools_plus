<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Administrativetools
 * @author     Hirlei Carlos Pereira de Araújo <prof.hirleicarlos@gmail.com>
 * @copyright  2020 Hirlei Carlos Pereira de Araújo
 * @license    GNU General Public License versão 2 ou posterior; consulte o arquivo License. txt
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');

use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Plugin\PluginHelper;

/**
 * Administrativetools model.
 *
 * @since  1.6
 */
class AdministrativetoolsModelTool extends \Joomla\CMS\MVC\Model\AdminModel
{
	public function getForm ($data = array(), $loadData = true)
	{
	
	}

	/**
     * Fabrik sync lists 1.0
     * 
     * Method that sync the lists.
     *
     */
    public function syncLists($data)
    {
        //Initial configurations
        include JPATH_ADMINISTRATOR . '/components/com_fabrik/models/administrativetools.php';
        $db = $this->getDbo();
        $resultReturn = true;

        //Configuring the tables needed
        $dbExternal = $this->connectSync($data);
        $data->data_type == 'identical' ? $arrFabrikTables = FabrikAdminModelAdministrativetools::tablesVersions() : $arrFabrikTables = Array();
        $data->model_type == 'identical' ? $arrModelTables = $this->tablesOnlyModel($dbExternal, $data->name) : $arrModelTables = Array('external' => Array(), 'internal' => Array());
        $arrOthersTables = $this->othersTablesOnlyData($data);

        //Without external connection doesn't work
        if(!$dbExternal) {
            return false;
        }

        //Calling the fabrik version control
        $nameVersion = FabrikAdminModelAdministrativetools::generateSql();
        $idVersion = substr($nameVersion, strpos($nameVersion, '_')+1, strpos($nameVersion, '.sql')-strpos($nameVersion, '_')-1);
        $newVersion = $this->newVersion($idVersion, $nameVersion);
        $arrTables = array_merge($arrFabrikTables, $arrModelTables['external'], $arrOthersTables);

        if(!empty($arrTables)) {
            //Paths needed
            $path = JPATH_SITE . '/media/com_administrativetools';
            $pathVersion = $path . '/versions';
            $pathNameVersion = $pathVersion . '/' . $nameVersion;

            if(!is_dir($path)) {
                mkdir($path);
            }

            if(!is_dir($pathVersion)) {
                mkdir($pathVersion);
            }
            
            if(is_file($pathNameVersion)) {
                unlink($pathNameVersion);
            }

            $handle = fopen($pathNameVersion, 'x+');
            $numtypes = array('tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'float', 'double', 'decimal', 'real');
            $sqlFile = "SET sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';<ql>\n\n";

            //Cycle through the table(s)
            foreach ($arrTables as $table) {
                //If the tables are only_data execute truncate and insert into like fabrik version control
                if(substr($table, 0, strlen('#__')) == '#__') {
                    $sqlFile .= "TRUNCATE TABLE " . $db->qn($table) . ";<ql>\n\n";
                    $dbExternal->setQuery("SELECT * FROM $table");
                    $result = $dbExternal->loadObjectList();

                    if(!empty($result)) {
                        $sqlFile .= 'INSERT INTO `' . $table . '` (';
                        $dbExternal->setQuery("SHOW COLUMNS FROM $table");
                        $pstm3 = $dbExternal->loadObjectList('Field');
                        $count = 0;
                        $type = array();
                        $count3 = count($pstm3);

                        foreach($pstm3 as $column => $rows) {
                            if (stripos($column, '(')) {
                                $type[$table][] = stristr($rows->Type, '(', true);
                            } else {
                                $type[$table][] = $rows->Type;
                            }
                            $sqlFile .= "`" . $column . "`";
                            $count++;
                            if ($count < $count3) {
                                $sqlFile .= ", ";
                            }
                        }

                        $sqlFile .= ")" . ' VALUES';
                        fwrite($handle, $sqlFile);
                        $sqlFile = "";

                        $counter = 0;
                        foreach($result as $j => $row) {
                            $sqlFile = "\n\t(";
                            $count4 = count((array) $row);
                            $count5 = 0;
                            $count6 = count((array) $result);
                            foreach($row as $r) {
                                if (isset($r)) {
                                    //if number, take away "". else leave as string
                                    if ((in_array($type[$table][$count5], $numtypes)) && (!empty($r))) {
                                        $sqlFile .= $r;
                                    } else {
                                        $sqlFile .= $db->quote($r);
                                    }
                                } else {
                                    $sqlFile .= 'NULL';
                                }
                                if ($count5 < $count4 - 1) {
                                    $sqlFile .= ',';
                                }
                                $count5++;
                            }

                            $counter++;
                            if ($counter < $count6) {
                                $sqlFile .= "),";
                            } else {
                                $sqlFile .= ");<ql>\n\n";
                            }

                            fwrite($handle, $sqlFile);
                            $sqlFile = "";
                        }
                    } else {
                        $sqlFile = "TRUNCATE TABLE " . $db->qn($table) . ";<ql>\n\n";
                        fwrite($handle, $sqlFile);
                        $sqlFile = "";
                    }
                }

                //If the tables are only_model execute drop temps, rename table, create table and insert into
                if(substr($table, 0, strlen('#__')) != '#__') {
                    $tempTable = 'ztmp_' . $table;
                    $query = $db->getQuery(true)
                        ->clear()
                        ->select($db->qn('table_name'))
                        ->from($db->qn('information_schema') . '.' . $db->qn('tables'))
                        ->where($db->qn('table_schema') . ' = (SELECT DATABASE())')
                        ->where($db->qn('table_name') . ' = ' . $db->q($tempTable));
                    $db->setQuery($query);
                    if($db->loadResult()) {
                        $sqlFile .= "DROP TABLE " . $db->qn($tempTable) . ";<ql>\n\n";
                    }

                    if(in_array($table, $arrModelTables['internal'])) {
                        $sqlFile .= "RENAME TABLE $table TO $tempTable;<ql>\n\n";
                    }
                    
                    $dbExternal->setQuery("SHOW CREATE TABLE $table");
                    $createTable = $dbExternal->loadColumn(1)[0];
                    $sqlFile .= $createTable . ";<ql>\n\n";

                    if(in_array($table, $arrModelTables['internal'])) {
                        $dbExternal->setQuery(
                            "SELECT column_name, column_type from INFORMATION_SCHEMA.COLUMNS WHERE table_schema = (SELECT DATABASE()) AND table_name = '$table';"
                        );
                        $columnsExternal = $dbExternal->loadAssocList('column_name', 'column_type');

                        $db->setQuery(
                            "SELECT column_name, column_type from INFORMATION_SCHEMA.COLUMNS WHERE table_schema = (SELECT DATABASE()) AND table_name = '$table';"
                        );
                        $columnsInternal = $db->loadAssocList('column_name', 'column_type');

                        $samesColumns = array_intersect_assoc($columnsExternal, $columnsInternal);
                        $columns = array_keys($samesColumns);

                        $sqlFile .= "INSERT INTO " . $db->qn($table) . " (`" . implode("`,`", $columns) . "`)\n";
                        $sqlFile .= "SELECT `" . implode("`,`", $columns) . "`\n";
                        $sqlFile .= "FROM " . $db->qn($tempTable) . ";<ql>\n\n";
                    }

                    fwrite($handle, $sqlFile);
                    $sqlFile = "";
                }
            }

            fclose($handle);
            $syncData = $this->syncSqlFile($idVersion, $nameVersion ,$pathNameVersion);
            $resultReturn = $newVersion && $syncData;
        }

        return $resultReturn;
    }

    /**
     * Fabrik sync lists 1.0
     * 
     * Method that save the configuration of sync list.
     *
     */
    public function saveConfiguration($data)
    {
        $db = JFactory::getDbo();
        $arrColumns = Array();
        $arrValues = Array();
        $arrUseless = Array('model_type', 'data_type', 'saveConfiguration', 'joomla_menus', 'joomla_modules', 'joomla_themes','joomla_extensions');

        foreach($data as $key => $value) {
            if(in_array($key, $arrUseless)) {
                continue;
            }
            $arrColumns[] = $key;
            $arrValues[] = $db->quote($value);
        }

        $arrColumns[] = 'checked_out_time';
        $arrValues[] = $db->quote(date('Y-m-d H:i:s'));

        $query = $db->getQuery(true)
            ->insert($db->qn('#__fabrik_sync_lists_connections'))
            ->columns($db->qn($arrColumns))
            ->values(implode(',', $arrValues));

        $db->setQuery($query);

        return $db->execute();
    }

    /**
     * Fabrik sync lists 1.0
     * 
     * Method that test the connection of the configuration of sync list
     *
     */
    public function connectSync($data)
    {
        //Conexão com banco de dados externo
		$option = array();
		$option['driver']   = 'mysql';
		$option['host']     = $data->host;
		$option['user']     = $data->user;
		$option['password'] = $data->password;
		$option['database'] = $data->name;
		$option['prefix']   = $data->prefix;
        $option['port']     = $data->port;
		$db = JDatabaseDriver::getInstance($option);

        //Only for testing the connection
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->qn('#__extensions'))
            ->order('extension_id DESC LIMIT 1');

        $db->setQuery($query);
        $db->execute();

        return $db;
    }

    /**
	 * Fabrik sync lists 1.0
	 * 
     * Method that restore the sql file
     *
     * @return boolean
     */
    private function syncSqlFile($id, $fileSql, $pathNameVersion) 
    {
		$db = JFactory::getDbo();
		
		if(!file_exists($pathNameVersion)) {
			return false;
		}

		$handle = fopen($pathNameVersion, 'r');
		$qr = '';

		while($row = fgets($handle)) {
			if(trim($row) != '') {
				if(strpos($row, '<ql>')) {
					if(empty($qr)) {
						$exec = str_replace('<ql>', '', $row);
					} else {
						$qr .= $row;
						$exec = str_replace('<ql>', '', $qr);
					}
					$db->setQuery($exec);
					$qr = '';
					if(!$db->execute()) {
						continue;
					}
				} else {
					$qr .= $row;
				}
			}
		}

        return true;
    }

    /**
     * Fabrik sync lists 1.0
     * 
     * Method that return the main lists of fabrik.
     *
     */
    private function fabrikListsOnlyData()
    {
        $arrFabrik = Array(
            '#__fabrik_joins',
            '#__fabrik_forms',
            '#__fabrik_lists',
            '#__fabrik_cron',
            '#__fabrik_elements',
            '#__fabrik_formgroup',
            '#__fabrik_groups',
            '#__fabrik_jsactions',
            '#__fabrik_visualizations',
            '#__fabrik_validations'
        );

        return $arrFabrik;
    }

    /**
     * Fabrik sync lists 1.0
     * 
     * Method that return the others lists of joomla.
     *
     */
    private function othersTablesOnlyData($data, $tables=Array())
    {
        if($data->joomla_menus) {
            $tables[] = '#__menu';
            $tables[] = '#__menu_types';
            $tables[] = '#__modules_menu';
        }

        if($data->joomla_modules) {
            $tables[] = '#__modules';
            if(!$data->joomla_menus) {
                $tables[] = '#__modules_menu';
            }
        }

        if($data->joomla_themes) {
            $tables[] = '#__template_styles';
        }

        if($data->joomla_extensions) {
            $tables[] = '#__extensions';
        }

        return $tables;
    }
    
    /**
     * Fabrik sync lists 1.0
     * 
     * Method that apply a new version in database to multiple lists of fabrik and joomla
     *
     */
    private function newVersion($id, $name)
    {
        $newdb = JFactory::getDbo();
		$user = JFactory::getUser();

		$values = new stdClass();
		$values->id = 'default';
		$values->label = Text::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_LABEL_VERSION');
		$values->link = '';
		$values->date_creation = date('Y-m-d H:i:s');
		$values->user_id = $user->id;
		$values->text = Text::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_TEXT_VERSION');;
		$values->sql = $name;

		//Store the new version
		$newQuery = $newdb->getQuery(true)
            ->clear()
            ->insert($newdb->qn('#__fabrik_version_control'))
            ->values("'" . implode("','", (array)$values) . "'");

        $newdb->setQuery($newQuery);
        $newdb->execute();

        return true;
    }

    /**
     * Fabrik sync lists 1.0
     * 
     * Method that return the database tables that the sync will be only with model.
     * 
     */ 
    private function tablesOnlyModel($dbExternal, $database)
    {
        $db = $this->getDbo();
		$query = $dbExternal->getQuery(true)
            ->clear()
            ->select($dbExternal->qn('db_table_name'))
            ->from($dbExternal->qn('#__fabrik_lists'));

        $dbExternal->setQuery($query);
        $tables['external'] = array_unique($dbExternal->loadColumn(), SORT_STRING);

        foreach($tables['external'] as $table) {
            $query = $dbExternal->getQuery(true)
                ->clear()
                ->select($dbExternal->qn('table_name'))
                ->from($dbExternal->qn('information_schema') . '.' . $dbExternal->qn('tables'))
                ->where($dbExternal->qn('table_schema') . ' = ' . $dbExternal->q($database))
                ->where($dbExternal->qn('table_name') . ' LIKE ' . $dbExternal->q($table . '%'));
            $dbExternal->setQuery($query);
            $relationTables = $dbExternal->loadColumn();
            foreach($relationTables as $relationTable) {
                !in_array($relationTable, $tables['external']) ? $tables['external'][] = $relationTable : false;
            }
        }

        foreach($tables['external'] as $tableExt) {
            $query = $db->getQuery(true)
                ->clear()
                ->select($db->qn('table_name'))
                ->from($db->qn('information_schema') . '.' . $db->qn('tables'))
                ->where($db->qn('table_schema') . ' = (SELECT DATABASE())')
                ->where($db->qn('table_name') . ' = ' . $db->q($tableExt));
            $db->setQuery($query);
            if($db->loadResult()) {
                $tables['internal'][] = $tableExt;
            }
        }

        return $tables;
    }
}