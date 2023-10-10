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

/**
 * Begin - Fabrik sync lists 2.0
 * Id Task: 13
 *  
 */
include JPATH_ADMINISTRATOR . '/components/com_fabrik/models/administrativetools.php';
// End - Fabrik sync lists 2.0

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
    private $encripty = 'sha256';

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
            $syncData = $this->syncSqlFile($pathNameVersion);
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
    private function syncSqlFile($pathNameVersion) 
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
		$values->label = FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_LABEL_VERSION');
		$values->link = '';
		$values->date_creation = date('Y-m-d H:i:s');
		$values->user_id = $user->id;
		$values->text = FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_TEXT_VERSION');;
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

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that search the lists.
     *
     */
    public function searchLists($data)
    {
        //Initial configurations
        $writeFile = Array();
        $hashOk = true;

        $path = '/media/com_administrativetools/merge/';
        $pathWithPrefix = JPATH_SITE . $path;
        $nameFile = 'fileHashBaseSourceEnv.json';
        $nameFileChanges = 'sqlChanges.json';
        $fullUrl = JURI::base();
        $parsedUrl = parse_url($fullUrl);

        $pathUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $path . $nameFile;
        $pathUrlChanges = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $path . $nameFile;
        $pathName = $pathWithPrefix . $nameFile;
        $pathNameChanges = $pathWithPrefix . $nameFileChanges;

        $data->data_type == 'merge' ? $dataType = true : $dataType = false;
        $data->model_type == 'merge' ? $modelType = true : $modelType = false;
        if(!$dataType && !$modelType) {
            return false;
        }

        //Getting the base file from source environment
        $opts = new stdClass();
        $opts->url = $data->urlApi;
        $opts->key = $data->keyApi;
        $opts->secret = $data->secretApi;
        $opts->data_type = $data->data_type;
        $opts->model_type = $data->model_type;
        $opts->format = 'json';
        $opts->task = 'getBaseFile';
        $fileSourceEnv = $this->getBaseFileApi($pathWithPrefix, $opts);
        if(!$fileSourceEnv) {
            return false;
        }

        //Getting the changes from source environment
        $changes = $this->readAndVerifyFile($pathName, true);
        $changesFile = $this->writeFile(json_encode($changes), $pathNameChanges);

        //With mapped changes, sync the members adds
        $addsNews = Array(
            'data' => $changes['data']['add'],
            'model' => $changes['model']['add']
        );
        if(!empty($changes['data']['add']) || !empty($changes['model']['add'])) {
            $sqlFile = $this->getChangesApi($addsNews, $path, $opts);
            $adds = $this->syncSqlFile($sqlFile);
            if(!$adds) {
                return false;
            }
        }

        return $hashOk && $fileSourceEnv && $changes;
    }

    /**
	 * Fabrik sync lists 2.0
	 * 
     * Method that returns tables to process
     *
     * @return boolean
     */
    private function tablesVersions($others = Array(), $principals=false) 
    {
		//Defaults
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

        $arrPrincipals = [
            '#__fabrik_forms',
            '#__fabrik_formgroup',
            '#__fabrik_groups',
            '#__fabrik_elements',
            '#__fabrik_joins',
            '#__fabrik_validations',
            '#__fabrik_jsactions'
        ];

        if($principals) {
            return $arrPrincipals;
        }

		if(empty($others)) {
			return $arrFabrik;
		}

        return array_merge($arrFabrik, $others);;
	}

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that get the columns from information_schema and format to select query.
     *
     */
    private function getColumnsOfTableToQueryJoin($tables, $prefix='', $order='hash')
    {
        $db = $this->getDbo();
        $columnsFormat = Array();

        if(is_array($tables)) {
            $searchTables = str_replace('#__', $db->getPrefix(), implode($tables, '","'));
        } else {
            $searchTables = $db->q($tables);
        }
        
        $query = $db->getQuery(true)
            ->clear()
            ->select([
                $db->qn('column_name') . ' AS column_name', 
                $db->qn('table_name') . ' AS table_name',
                'MD5(CONCAT(table_name, column_name)) AS row_hash'
            ])
            ->from($db->qn('information_schema') . '.' . $db->qn('columns'))
            ->where($db->qn('table_schema') . ' = (SELECT DATABASE())')
            ->where($db->qn('table_name') . ' IN ("' . $searchTables . '")');
        
        if($order == 'hash') {
            $query->order('row_hash ASC');
        } else {
            $query->order('table_name');
        }

        $db->setQuery($query);
        $columns = $db->loadObjectList();

        if(!is_array($prefix)) {
            foreach ($columns as $column) {
                if($column->column_name != 'ordering' || $order == 'nm') {
                    $prefix != '' ? $pre = $db->qn($prefix) : $pre = $db->qn($column->table_name);
                    $p = $pre . '.' . $db->qn($column->column_name);
                    $columnsFormat[] = $p . ' AS ' . $db->q(str_replace('`', '', $p));
                }
            }
        }

        return $columnsFormat;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that hash the general groupment
     *
     */
    private function hashGroupmentsDataType(&$arrHashsData, $idsNewLists=false)
    {
        $db = $this->getDbo();
        $tables = $this->tablesVersions(Array(), true);

        $arrGroupments = $this->getTypeGroupments();

        // List grouping hash
        $query = $db->getQuery(true)
            ->clear()
            ->select('*')
            ->from($db->qn('#__fabrik_lists'))
            ->order('id');

        if($idsNewLists) {
            $idsNewLists = (array) $idsNewLists;
            $query->where($db->qn('id') . ' IN ("' . implode($idsNewLists, '","') . '")');
        }

        $db->setQuery($query);
        $lists = $db->loadAssocList('id');

        foreach ($lists as $idList => $list) {
            $list = Array($list);

            $query = $this->buildQueryGroupments('G0', $idList);
            $db->setQuery($query);
            $rowGroupments = $db->loadAssocList();

            $arrHashsData['PG'][$idList]['G0']['hash'] = hash($this->encripty, json_encode($rowGroupments)); // Hash general groupment
            $arrHashsData['PG'][$idList]['G1']['hash'] = hash($this->encripty, json_encode($list)); // Hash first groupment

            foreach ($rowGroupments as $row) {
                $members = Array();
                foreach ($row as $tableColumn => $value) {
                    $exColumn = explode('.', $tableColumn);
                    $exTable = explode('_', $exColumn[0]);
                    $table = $exTable[count($exTable)-1];
                    $column = $exColumn[1];
                    $id = $row[$db->getPrefix() . 'fabrik_' . $table . '.id'];

                    if(!isset($id)) {
                        continue;
                    }

                    $members[$table][$id][$column] = $value;
                }

                foreach ($arrGroupments as $groupment => $tbl) {
                    foreach ($tbl as $t) {
                        $id = $row[$db->getPrefix() . 'fabrik_' . $t . '.id'];

                        if(!isset($id)) {
                            continue;
                        }

                        !isset($arrHashsData['PG'][$idList][$groupment][$t][$id]) ? 
                        $arrHashsData['PG'][$idList][$groupment][$t][$id] = hash($this->encripty, json_encode($members[$t])) : 
                        '';
                    }
                }
            }
            
            $this->hashGroupments($arrHashsData, $idList);
        }

        return $tables;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that hash the groupments with data from members
     *
     */
    private function hashGroupments(&$arrHashsData, $idList)
    {
        $arrGroupments = $this->getTypeGroupments();

        foreach ($arrGroupments as $groupment => $arrFuncs) {
            foreach ($arrFuncs as $func) {
                $temp = Array();
                $temp['hash'] = hash($this->encripty, json_encode($arrHashsData['PG'][$idList][$groupment][$func]));
                $temp['rows'] = $arrHashsData['PG'][$idList][$groupment][$func];
                unset($arrHashsData['PG'][$idList][$groupment][$func]);
                $arrHashsData['PG'][$idList][$groupment][$func] = $temp;
            }

            $tempG = Array();
            $tempG['hash'] = hash($this->encripty, json_encode($arrHashsData['PG'][$idList][$groupment]));
            $tempG['functionality'] = $arrHashsData['PG'][$idList][$groupment];
            unset($arrHashsData['PG'][$idList][$groupment]);
            $arrHashsData['PG'][$idList][$groupment] = $tempG;
        }
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that hash the groupment with data from members
     *
     */
    private function hashGroupment(&$arrHashsData, $idList)
    {
        // Forms groupment hash
        $g2Forms['hash'][] = hash($this->encripty, json_encode($arrHashsData['PG'][$idList]['G2']['forms']));
        $g2Forms['hash'][] = $arrHashsData['PG'][$idList]['G2']['forms'];
        unset($arrHashsData['PG'][$idList]['G2']['forms']);
        $arrHashsData['PG'][$idList]['G2'] = $g2Forms;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that hash the secondary groupments 
     * like tables #__fabrik_visualizations or #__fabrik_cron
     *
     */
    private function hashGroupmentsDataTypeSecondary(&$arrHashsData, $tablesHashed)
    {
        $db = $this->getDbo();
        $tablesFabrik = $this->tablesVersions();

        $g = 5;
        $tablesHashed[] = '#__fabrik_lists';

        foreach ($tablesFabrik as $table) {
            if(in_array($table, $tablesHashed)) {
                continue;
            }

            $query = $db->getQuery(true)
                ->clear()
                ->select('*')
                ->from($db->qn($table))
                ->order('id');
            $db->setQuery($query);
            $allData = $db->loadAssocList('id');

            $arrHashsData['SG']['G'.$g]['hash'] = hash($this->encripty, json_encode($allData));

            foreach ($allData as $id => $row) {
                $arrHashsData['SG']['G'.$g]['rows'][$id] = hash($this->encripty, json_encode($row));
            }

            if(empty($allData)) {
                $arrHashsData['SG']['G'.$g]['rows'] = null;
            }

            $g++;
        }
    }

    /**
	 * Fabrik sync lists 2.0
	 * 
     * Method that write at files to search lists
     *
     * @return boolean
     */
    public function writeFile(&$string, $pathName) 
    {
        $aux = explode('/', $pathName);

        for ($i=1; $i < count($aux)-1; $i++) {
            $path .= '/'.$aux[$i];

            if(!is_dir($path)) {
                mkdir($path);
            }
        }
        
        if(is_file($pathName)) {
            unlink($pathName);
        }

        $handle = fopen($pathName, 'x+');
        if(!isset($handle)) {
            return false;
        }

        fwrite($handle, $string);
        fclose($handle);

        return true;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that verify actual hash to update him
     *
     */
    private function verifyActualHash(&$arrHashs, $getChanges)
    {
        $db = JFactory::getDbo();
        $changes = Array();
        $arrChanges = Array();

        isset($arrHashs['model']) ? $modelType = true : '';
        isset($arrHashs['data']) ? $dataType = true : '';

        if($dataType) {
            //Verifing id new lists were add
            $query = $db->getQuery(true)
                ->clear()
                ->select('id')
                ->from($db->qn('#__fabrik_lists'))
                ->order('id');
            $db->setQuery($query);
            $listsDb = array_keys($db->loadAssocList('id'));
            $listsHash = array_keys($arrHashs['data']['PG']);

            if($getChanges) {
                $listsAdd = array_diff_key($listsHash, $listsDb);
                if(!empty($listsAdd)) {
                    foreach ($listsAdd as $idList) {
                        $arrChanges['data']['add']['lists'][$idList] = 'added';
                    }
                }
            } else {
                $listsAdd = array_diff_key($listsDb, $listsHash);
                if(!empty($listsAdd)) {
                    $this->hashGroupmentsDataType($arrHashs['data'], $listsAdd);                    
                }
            }

            $changedData = $this->verifyActualHashData($arrHashs['data'], $getChanges, $arrChanges);
        }

        if($modelType) {
            $changedModel = $this->verifyActualHashModel($arrHashs['model'], $getChanges, $arrChanges);
        }

        if($getChanges) {
            $changes['data'] = $changedData['data'];
            $changes['model'] = $changedModel['model'];
        }

        return $getChanges ? $changes : true;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that verify actual data hash to update him
     *
     */
    private function verifyActualHashData(&$generalData, $getChanges=false, $arrChanges=Array())
    {
        //Initial configurations
        $changed = false;

        foreach ($generalData as $staGroupment => &$rows) {
            if($staGroupment == 'PG') {
                foreach ($rows as $idList => &$groupments) {
                    foreach ($groupments as $keyG => &$valuesGroupment) {
                        foreach ($valuesGroupment as $key => &$funcionality) {
                            if($key == 'hash' && !is_array($funcionality)) {
                                $query = $this->buildQueryGroupments($keyG, $idList);
                                $actualHash = $this->verifyHashGroupments($query, $keyG);

                                if(!$actualHash && $keyG == 'G0') {
                                    break 2;
                                }

                                if(is_array($actualHash)) {
                                    $actualFuncs = $actualHash['functionality'];
                                    $actualHash = $actualHash['hash'];
                                }

                                if(hash_equals($funcionality, $actualHash)) {
                                    if($keyG == 'G0') {
                                        break 2;
                                    } else {
                                        break;
                                    }
                                } else {
                                    if($keyG == 'G1') {
                                        $getChanges ? $arrChanges['data'][$staGroupment][$idList][$keyG]['list'][$idList] = true : $changed = true;
                                    }
                                    $funcionality = $actualHash;
                                }
                            }

                            foreach ($funcionality as $funcName => &$valuesFunc) {
                                foreach ($valuesFunc as $key2 => &$rowsFunc) {
                                    if($key2 == 'hash' && !is_array($rowsFunc)) {
                                        $actualHashFunc = $actualFuncs[$funcName][$key2];
                                        if(hash_equals($rowsFunc, $actualHashFunc)) {
                                            break;
                                        } else {
                                            $rowsFunc = $actualHashFunc;
                                        }
                                    } 
                                    
                                    foreach ($rowsFunc as $idFunc => &$hashMember) {
                                        $actualHashMember = $actualFuncs[$funcName][$key2][$idFunc];
                                        if(hash_equals($hashMember, $actualHashMember) || !isset($actualHashMember)) {
                                            continue;
                                        } else {
                                            $hashMember = $actualHashMember;
                                            $getChanges ? $arrChanges['data'][$staGroupment][$idList][$keyG][$funcName][$idFunc] = true : $changed = true;
                                        }
                                    }

                                    if($key2 != 'hash') {
                                        $removes = array_diff_key($actualFuncs[$funcName][$key2], $rowsFunc);
                                        $adds = array_diff_key($rowsFunc, $actualFuncs[$funcName][$key2]);

                                        if($getChanges) {
                                            if(is_array($removes) && !empty($removes)) {
                                                $arrRemoves = array_fill_keys(array_keys($removes), 'removed');
                                                $arrActual = (array) $arrChanges['data'][$staGroupment][$idList][$keyG][$funcName];
                                                $arrSum = $arrRemoves + $arrActual;
                                                $arrChanges['data'][$staGroupment][$idList][$keyG][$funcName] = $arrSum;
                                            }
                                                is_array($adds) && !empty($adds) ? $arrChanges['data']['add'][$staGroupment][$idList][$funcName] =  array_fill_keys(array_keys($adds), 'added') : '';
                                        } else {
                                            if(!empty($removes) || !empty($adds)) {
                                                $changed = true;
                                            }

                                            foreach ($removes as $idMemberR => $hMemberR) {
                                                $generalData[$staGroupment][$idList][$keyG][$key][$funcName][$key2][$idMemberR] = $hMemberR;
                                            }

                                            foreach ($adds as $idMemberA => $hMemberA) {
                                                unset($generalData[$staGroupment][$idList][$keyG][$key][$funcName][$key2][$idMemberA]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if($staGroupment == 'SG') {
                foreach ($rows as $keyG => &$values) {
                    switch ($keyG) {
                        case 'G5':
                            $table = '#__fabrik_cron';
                            $prefixFunc = 'cron';
                            break;
                        case 'G6':
                            $table = '#__fabrik_visualizations';
                            $prefixFunc = 'visualizations';
                            break;
                    }

                    foreach ($values as $key => &$value) {
                        if($key == 'hash' && !is_array($value)) {
                            $query = $this->buildQueryGroupments($keyG, '', $table);
                            $groupmentHash = $this->verifyHashGroupments($query, $keyG);

                            $actualRows = $groupmentHash['rows'];
                            $groupmentHash = $groupmentHash['hash'];

                            if(hash_equals($value, $groupmentHash)) {
                                continue;
                            } else {
                                $value = $groupmentHash;
                            }
                        }

                        foreach ($value as $id => &$val) {
                            if(hash_equals($val, $actualRows[$id]) || !isset($actualRows[$id])) {
                                continue;
                            } else {
                                $val = $actualRows[$id];
                                $getChanges ? $arrChanges['data'][$staGroupment][$keyG][$id] = true : $changed = true;
                            }
                        }

                        if($key != 'hash') {
                            $removes = array_diff_key($actualRows, $value);
                            $adds = array_diff_key($value, $actualRows);

                            if($getChanges) {
                                if(is_array($removes) && !empty($removes)) {
                                    $arrRemoves = array_fill_keys(array_keys($removes), 'removed');
                                    $arrActual = (array) $arrChanges['data'][$staGroupment][$keyG];
                                    $arrSum = $arrRemoves + $arrActual;
                                    $arrChanges['data'][$staGroupment][$keyG] = $arrSum;
                                }

                                is_array($adds) && !empty($adds) ? $arrChanges['data']['add'][$staGroupment][$prefixFunc] =  array_fill_keys(array_keys($adds), 'added') : '';
                            } else {
                                if(!empty($removes) || !empty($adds)) {
                                    $changed = true;
                                }
                                
                                foreach ($removes as $idMemberR => $hMemberR) {
                                    $generalData[$staGroupment][$keyG][$key][$idMemberR] = $hMemberR;
                                }

                                foreach ($adds as $idMemberA => $hMemberA) {
                                    unset($generalData[$staGroupment][$keyG][$key][$idMemberA]);
                                }
                            }
                        }
                    }
                }
            }
        }

        unset($rows, $groupments, $valuesGroupment, $funcionality, $valuesFunc);
        unset($hashMember, $values, $value, $val);

        return $getChanges ? $arrChanges : $changed;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that verify actual model hash to update him
     *
     */
    private function verifyActualHashModel(&$generalData, $getChanges=false, $arrChanges=Array())
    {
        //Initial configurations
        $changed = false;

        foreach ($generalData as $jointName => &$joints) {
            foreach ($joints as $keyG => &$valuesJoints) {
                foreach ($valuesJoints as $key => &$func) {
                    if($key == 'hash' && !is_array($func)) {
                        $query = $this->buildQueryJoints($keyG, $jointName);
                        $actualHash = $this->verifyHashJoints($query, $keyG, $jointName);

                        if(!$actualHash && $keyG == 'J0') {
                            break 2;
                        }

                        if(is_array($actualHash)) {
                            $actualFuncs = $actualHash['tables'];
                            $actualHash = $actualHash['hash'];
                        }

                        if(hash_equals($func, $actualHash)) {
                            if($keyG == 'J0') {
                                break 2;
                            } else {
                                break;
                            }
                        } else {
                            $func = $actualHash;
                        }
                    }

                    foreach ($func as $funcName => &$valuesFunc) {
                        foreach ($valuesFunc as $key2 => &$rowsFunc) {
                            if($key2 == 'hash' && !is_array($rowsFunc)) {
                                $actualHashFunc = $actualFuncs[$funcName][$key2];
                                if(hash_equals($rowsFunc, $actualHashFunc)) {
                                    break;
                                } else {
                                    $rowsFunc = $actualHashFunc;
                                }
                            } 

                            foreach ($rowsFunc as $nameColumn => &$hashMember) {
                                $actualHashMember = $actualFuncs[$funcName][$key2][$nameColumn];
                                if(hash_equals($hashMember, $actualHashMember) || !isset($actualHashMember)) {
                                    continue;
                                } else {
                                    $hashMember = $actualHashMember;
                                    $getChanges ? $arrChanges['model'][$jointName][$funcName][$nameColumn] = true : $changed = true;
                                }
                            }

                            if($key2 != 'hash') {
                                $removes = array_diff_key($actualFuncs[$funcName][$key2], $rowsFunc);
                                $adds = array_diff_key($rowsFunc, $actualFuncs[$funcName][$key2]);

                                if($getChanges) {
                                    if(is_array($removes) && !empty($removes)) {
                                        $arrRemoves = array_fill_keys(array_keys($removes), 'removed');
                                        $arrActual = $arrChanges['model'][$jointName][$funcName];
                                        $arrSum = $arrRemoves + $arrActual;
                                        $arrChanges['model'][$jointName][$funcName] = $arrSum;
                                    }

                                    is_array($adds) && !empty($adds) ? $arrChanges['model']['add'][$jointName][$funcName] =  array_fill_keys(array_keys($adds), 'added') : '';
                                } else {
                                    if(!empty($removes) || !empty($adds)) {
                                        $changed = true;
                                    }

                                    foreach ($removes as $nColumnR => $hMemberR) {
                                        $generalData[$jointName][$keyG][$key][$funcName][$key2][$nColumnR] = $hMemberR;
                                    }

                                    foreach ($adds as $nColumnA => $hMemberA) {
                                        unset($generalData[$jointName][$keyG][$key][$funcName][$key2][$nColumnA]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        unset($rows, $groupments, $valuesGroupment, $func, $valuesFunc);
        unset($hashMember, $values, $value, $val);

        return $getChanges ? $arrChanges : $changed;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that build the query to groupments
     *
     */
    public function buildQueryGroupments($groupment, $idList, $table='', $order='hash')
    {
        //Initial configurations
        $db = $this->getDbo();
        $query = $db->getQuery(true)->clear();

        if(empty($table)) {
            $table = '#__fabrik_lists';
        }

        switch ($groupment) {
            case 'G0':
                $tables = $this->tablesVersions(Array(), true);
                $columns = $this->getColumnsOfTableToQueryJoin($tables, '', $order);
                $query->select($columns)
                    ->from($db->qn($table) . ' AS ' . $table)
                    ->join('LEFT', $db->qn('#__fabrik_forms') . ' AS #__fabrik_forms ON #__fabrik_forms.id = #__fabrik_lists.form_id')
                    ->join('LEFT', $db->qn('#__fabrik_formgroup') . ' AS #__fabrik_formgroup ON #__fabrik_formgroup.form_id = #__fabrik_forms.id')
                    ->join('LEFT', $db->qn('#__fabrik_groups') . ' AS #__fabrik_groups ON #__fabrik_groups.id = #__fabrik_formgroup.group_id')
                    ->join('LEFT', $db->qn('#__fabrik_elements') . ' AS #__fabrik_elements ON #__fabrik_elements.group_id = #__fabrik_groups.id')
                    ->join('LEFT', $db->qn('#__fabrik_joins') . ' AS #__fabrik_joins ON #__fabrik_joins.element_id = #__fabrik_elements.id')
                    ->join('LEFT', $db->qn('#__fabrik_jsactions') . ' AS #__fabrik_jsactions ON #__fabrik_jsactions.element_id = #__fabrik_elements.id')
                    ->join('LEFT', $db->qn('#__fabrik_validations') . ' AS #__fabrik_validations ON #__fabrik_validations.element_id = #__fabrik_elements.id')
                    ->order('#__fabrik_elements.id ASC');
                break;
            
            case 'G2':
                $tables = ['#__fabrik_forms', '#__fabrik_formgroup', '#__fabrik_groups'];
                $columns = $this->getColumnsOfTableToQueryJoin($tables);
                $query->select($columns)
                    ->from($db->qn($table) . ' AS ' . $table)
                    ->join('LEFT', $db->qn('#__fabrik_forms') . ' AS #__fabrik_forms ON #__fabrik_forms.id = #__fabrik_lists.form_id')
                    ->join('LEFT', $db->qn('#__fabrik_formgroup') . ' AS #__fabrik_formgroup ON #__fabrik_formgroup.form_id = #__fabrik_forms.id')
                    ->join('LEFT', $db->qn('#__fabrik_groups') . ' AS #__fabrik_groups ON #__fabrik_groups.id = #__fabrik_formgroup.group_id')
                    ->order('#__fabrik_groups.id ASC');
                break;

            case 'G3':
                $tables = ['#__fabrik_elements', '#__fabrik_joins'];
                $columns = $this->getColumnsOfTableToQueryJoin($tables);
                $query->select($columns)
                    ->from($db->qn($table) . ' AS ' . $table)
                    ->join('LEFT', $db->qn('#__fabrik_forms') . ' AS #__fabrik_forms ON #__fabrik_forms.id = #__fabrik_lists.form_id')
                    ->join('LEFT', $db->qn('#__fabrik_formgroup') . ' AS #__fabrik_formgroup ON #__fabrik_formgroup.form_id = #__fabrik_forms.id')
                    ->join('LEFT', $db->qn('#__fabrik_groups') . ' AS #__fabrik_groups ON #__fabrik_groups.id = #__fabrik_formgroup.group_id')
                    ->join('LEFT', $db->qn('#__fabrik_elements') . ' AS #__fabrik_elements ON #__fabrik_elements.group_id = #__fabrik_groups.id')
                    ->join('LEFT', $db->qn('#__fabrik_joins') . ' AS #__fabrik_joins ON #__fabrik_joins.element_id = #__fabrik_elements.id')
                    ->order('#__fabrik_elements.id ASC');
                break;
            case 'G4':
                $tables = ['#__fabrik_jsactions', '#__fabrik_validations'];
                $columns = $this->getColumnsOfTableToQueryJoin($tables);
                $query->select($columns)
                    ->from($db->qn($table) . ' AS ' . $table)
                    ->join('LEFT', $db->qn('#__fabrik_forms') . ' AS #__fabrik_forms ON #__fabrik_forms.id = #__fabrik_lists.form_id')
                    ->join('LEFT', $db->qn('#__fabrik_formgroup') . ' AS #__fabrik_formgroup ON #__fabrik_formgroup.form_id = #__fabrik_forms.id')
                    ->join('LEFT', $db->qn('#__fabrik_groups') . ' AS #__fabrik_groups ON #__fabrik_groups.id = #__fabrik_formgroup.group_id')
                    ->join('LEFT', $db->qn('#__fabrik_elements') . ' AS #__fabrik_elements ON #__fabrik_elements.group_id = #__fabrik_groups.id')
                    ->join('LEFT', $db->qn('#__fabrik_jsactions') . ' AS #__fabrik_jsactions ON #__fabrik_jsactions.element_id = #__fabrik_elements.id')
                    ->join('LEFT', $db->qn('#__fabrik_validations') . ' AS #__fabrik_validations ON #__fabrik_validations.element_id = #__fabrik_elements.id')
                    ->order('#__fabrik_elements.id ASC');
                break;

            case 'G1':
            case 'G5':
            case 'G6':
                $query->select('*')->from($db->qn($table))->order('id');
                break;
        }

        if(in_array($groupment, ['G0', 'G1', 'G2', 'G3', 'G4'])) {
            $query->where($table . '.id = ' . $idList);
        }

        return $query;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that verify actual groupments data hash
     *
     */
    private function verifyHashGroupments($query, $groupment)
    {
        //Initial configurations
        $db = $this->getDbo();
        $db->setQuery($query);

        $rows = $db->loadAssocList();

        if(empty($rows)) {
            return false;
        }

        if(in_array($groupment, ['G0', 'G1'])) {
            $gData = hash($this->encripty, json_encode($rows));
        }

        if(in_array($groupment, ['G5', 'G6'])) {
            $rows = $db->loadAssocList('id');
            $gData['hash'] = hash($this->encripty, json_encode($rows));
            foreach ($rows as $id => $row) {
                $gData['rows'][$id] = hash($this->encripty, json_encode($row));
            }
        }

        if(in_array($groupment, ['G2', 'G3', 'G4'])) {
            $gData = $this->verifyHashGroupmentsPrimary($rows, $groupment);
        }

        return $gData;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that verify actual groupments data hash
     *
     */
    private function verifyHashGroupmentsPrimary($rows, $groupment)
    {
        //Initial configurations
        $db = $this->getDbo();
        $arrGroupments = $this->getTypeGroupments();

        foreach ($rows as $row) {
            $members = Array();
            foreach ($row as $tableColumn => $value) {
                $exColumn = explode('.', $tableColumn);
                $exTable = explode('_', $exColumn[0]);
                $table = $exTable[count($exTable)-1];
                $column = $exColumn[1];
                $id = $row[$db->getPrefix() . 'fabrik_' . $table . '.id'];

                if(!isset($id)) {
                    continue;
                }

                $members[$table][$id][$column] = $value;
            }

            foreach ($arrGroupments[$groupment] as $table) {
                $id = $row[$db->getPrefix() . 'fabrik_' . $table . '.id'];

                if(!isset($id)) {
                    continue;
                }

                !isset($gData[$table][$id]) ?
                $gData[$table][$id] = hash($this->encripty, json_encode($members[$table])) : 
                '';
            }
        }

        foreach ($arrGroupments[$groupment] as $func) {
            $temp = Array();
            $temp['hash'] = hash($this->encripty, json_encode($gData[$func]));
            $temp['rows'] = $gData[$func];
            unset($gData[$func]);
            $gData[$func] = $temp;
        }

        $tempG = Array();
        $tempG['hash'] = hash($this->encripty, json_encode($gData));
        $tempG['functionality'] = $gData;
        unset($gData);
        $gData = $tempG;

        return $gData;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that return the array with associations
     *
     */
    private function getTypeGroupments()
    {
        $arrGroupments = Array(
            'G2' => ['forms', 'groups', 'formgroup'], 
            'G3' => ['elements', 'joins'],
            'G4' => ['jsactions', 'validations']
        );

        return $arrGroupments;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that get the base file from api
     *
     */
    private function getBaseFileApi($pathToSave, $opts)
    {
        $nameFile = 'fileHashBaseSourceEnv.json';
        $pathName = $pathToSave . $nameFile;
        $return = true;

        $response = $this->callApi($opts);
        if($response->error) {
            $return = false;
        }

        //Downloading the file
        $urlToFile = $response->data;
        $content = file_get_contents($urlToFile);
        if ($content !== false) {
            if(is_file($pathName)) {
                unlink($pathName);
            }

            $save = file_put_contents($pathName, $content);
            if (!$save) {
                $return = false;
            }
        } else {
            $return = false;
        }

        return $return ? $pathName : $return;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that read a file and verify if hash ok
     *
     */
    private function readAndVerifyFile($pathName, $getChanges=false, $dataType=false, $modelType=false) 
    {
        $hashOk = true;

        try {
            $handle = fopen($pathName, 'r');
            while($row = fgets($handle)) {
                if(trim($row) != '') {
                    $jsonFile .= $row;
                }
            }
            $arrHashs = json_decode($jsonFile, true);

            //Analisar depois: Quando solicita para fazer merge de apenas um, sendo que o arquivo json já está mapeado os dois
            /*if($dataType === false) {
                unset($arrHashs['data']);
            }
            if($modelType === false) {
                unset($arrHashs['model']);
            }*/

            $changed = $this->verifyActualHash($arrHashs, $getChanges);
        } catch (\Throwable $th) {
            $hashOk = false;
        }

        if(!$hashOk) {
            return false;
        }

        return $getChanges ? $changed : $arrHashs;
    }

    /**
     * Fabrik sync lists 2.0
     *
     * Method that get the lists for API.
     *
     */
    public function searchListsAPI($data)
    {
        $writeFile = Array();
        $hashOk = true;
        $baseFileExists = false;

        $path = '/media/com_administrativetools/merge/';
        $nameFile = 'fileHashBase.json';
        $fullUrl = JURI::base();
        $parsedUrl = parse_url($fullUrl);
        $pathUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $path . $nameFile;
        $pathName = JPATH_SITE . $path . $nameFile;

        $data->data_type == 'merge' ? $dataType = true : $dataType = false;
        $data->model_type == 'merge' ? $modelType = true : $modelType = false;

        if(!$dataType && !$modelType) {
            return false;
        }
        
        if(is_file($pathName)) {
            $writeFile = $this->readAndVerifyFile($pathName, false, $dataType, $modelType);
            $writeFile ? '' : $hashOk = false;
            $baseFileExists = true;
        }

        if(!$baseFileExists) {
            //Encripting type data
            if($dataType == 'merge' || $dataType) {
                $arrHashsData = Array();

                try {
                    $tablesHashed = $this->hashGroupmentsDataType($arrHashsData);
                    $this->hashGroupmentsDataTypeSecondary($arrHashsData, $tablesHashed);
                    $test = 'tentou';
                } catch (\Throwable $th) {
                    $hashOk = false;
                }
                
                $writeFile['data'] = $arrHashsData;
            }

            if($modelType == 'merge' || $modelType) {
                $arrHashsModel = Array();

                try {
                    $this->hashJointModelType($arrHashsModel);
                    //$this->hashJointModelTypeSecondary($arrHashsModel);
                } catch (\Throwable $th) {
                    $hashOk = false;
                }

                $writeFile['model'] = $arrHashsModel;
            }
        }

        $file = $this->writeFile(json_encode($writeFile), $pathName);

        return ($hashOk && $file) ? $pathUrl : false;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that call the api with method post
     *
     */
    public function callApi($opts) 
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $opts->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => (array) $opts,
        ));

        $response = json_decode(curl_exec($curl));
        curl_close($curl);

        return $response;
    }

    /**
	 * Fabrik sync lists 2.0
	 * 
     * Method that get the changes from API in sql format
     *
     * @return boolean
     */
    private function getChangesApi($changes, $path, $opts) 
    {
        $nameFile = 'sqlChangesSourceEnv.sql';
        $pathName = JPATH_SITE . $path . $nameFile;
        $return = true;

        if(empty($changes)) {
            return false;
        }

        $opts->type = 'adding';
        $opts->task = 'getChangesSqlFile';
        $opts->path = $path;
        $opts->changes = json_encode($changes);
        $response = $this->callApi($opts);
        if($response->error) {
            $return = false;
        }

        //Downloading the file
        $urlToFile = $response->data;
        $content = file_get_contents($urlToFile);
        if ($content !== false) {
            if(is_file($pathName)) {
                unlink($pathName);
            }

            $save = file_put_contents($pathName, $content);
            if (!$save) {
                $return = false;
            }
        } else {
            $return = false;
        }

        return $return ? $pathName : $return;
    }

    /**
     * Fabrik sync lists 2.0
     *
     * Method that get the file with some changes and update the changes
     *
     */
    public function setChangesUser($urlFile)
    {
        $nameFile = 'sqlChangesSourceEnv.sql';
        $pathName = JPATH_SITE . '/media/com_administrativetools/merge/' . $nameFile;
        $return = true;

        //Downloading the file
        $content = file_get_contents($urlFile);
        if ($content !== false) {
            if(is_file($pathName)) {
                unlink($pathName);
            }

            $save = file_put_contents($pathName, $content);
            if (!$save) {
                $return = false;
            }
        } else {
            $return = false;
        }

        if(!$this->syncSqlFile($pathName)) {
            $return = false;
        }

        return $return;
    }

    /**
     * Fabrik sync lists 2.0
     *
     * Method that hash the general joint
     *
     */
    private function hashJointModelType(&$arrHashsModel, $tablesModel=false)
    {
        $db = $this->getDbo();

        if(!$tablesModel) {
            // General joint hash
            $query = $db->getQuery(true)
                ->clear()
                ->select($db->qn('db_table_name'))
                ->from($db->qn('#__fabrik_lists'));

            $db->setQuery($query);
            $tablesModel = array_unique($db->loadColumn(), SORT_STRING);
        }

        foreach ($tablesModel as $table) {
            $joints = Array();
            $query = $this->buildQueryJoints('J0', $table);
            $db->setQuery($query);
            $generalJoint = $db->loadAssocList();

            $arrHashsModel[$table]['J0']['hash'] = hash($this->encripty, json_encode($generalJoint)); // Hash general joint

            foreach ($generalJoint as $columnData) {
                $joint = '';
                $tableName = $columnData['table_name'];
                if($table == $tableName) {
                    $joint = 'J1';
                } else if(strpos($tableName, '_repeat_')) {
                    $joint = 'J2';
                } else if(strpos($tableName, '_repeat')) {
                    $joint = 'J3';
                }

                $joint != '' ? $joints[$joint][$tableName][] = $columnData : ''; 
            }

            ksort($joints, SORT_STRING);
            foreach ($joints as $joint => $columnInfo) {
                $arrHashsModel[$table][$joint]['hash'] = hash($this->encripty, json_encode($columnInfo));

                foreach($columnInfo as $tblName => $columns) {
                    $arrHashsModel[$table][$joint]['tables'][$tblName]['hash'] = hash($this->encripty, json_encode($columnInfo));

                    foreach ($columns as $column) {
                        $arrHashsModel[$table][$joint]['tables'][$tblName]['members'][$column['column_name']] = hash($this->encripty, json_encode($column));
                    }
                }
            }
        }
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that build the query to joints
     *
     */
    public function buildQueryJoints($joint, $table)
    {
        //Initial configurations
        $db = $this->getDbo();
        $query = $db->getQuery(true)->clear();
        $columns = [
            $db->qn('column_name') . ' AS column_name', 
            $db->qn('table_name') . ' AS table_name',
            $db->qn('column_type') . ' AS column_type',
            'MD5(CONCAT(table_name, column_name)) AS row_hash'
        ];

        $query->select($columns)
            ->from($db->qn('information_schema') . '.' . $db->qn('columns'))
            ->where($db->qn('table_schema') . ' = (SELECT DATABASE())')
            ->order($db->qn('row_hash') . ' ASC');

        switch ($joint) {
            case 'J0':
                    $query->where($db->qn('table_name') . ' LIKE ' . $db->q($table . '%'));
                break;
            
            case 'J1':
                    $query->where($db->qn('table_name') . ' = ' . $db->q($table));
                break;
            
            case 'J2':
                    $query->where($db->qn('table_name') . ' LIKE ' . $db->q($table . '_repeat_%'));
                break;
            
            case 'J3':
                    $query->where($db->qn('table_name') . ' LIKE ' . $db->q($table . '%_repeat'));
                break;
        }

        return $query;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that verify actual joints data hash
     *
     */
    private function verifyHashJoints($query, $joint, $table)
    {
        //Initial configurations
        $db = $this->getDbo();
        $db->setQuery($query);

        $rows = $db->loadAssocList();

        if(empty($rows)) {
            return false;
        }

        if(in_array($joint, ['J0'])) {
            $gData = hash($this->encripty, json_encode($rows));
        }
        
        if(in_array($joint, ['J1','J2', 'J3', 'J4'])) {
            $gData = $this->verifyHashJointsPrimary($rows, $joint, $table);
        }

        return $gData;
    }

        /**
     * Fabrik sync lists 2.0
     * 
     * Method that verify actual Joints data hash
     *
     */
    private function verifyHashJointsPrimary($rows, $joint, $table)
    {
        //Initial configurations
        $db = $this->getDbo();
        $arrJoints = '';

        foreach ($rows as $columnData) {
            $joint = '';
            $tableName = $columnData['table_name'];
            if($table == $tableName) {
                $joint = 'J1';
            } else if(strpos($tableName, '_repeat_')) {
                $joint = 'J3';
            } else if(strpos($tableName, '_repeat')) {
                $joint = 'J2';
            }

            $joint != '' ? $joints[$joint][$tableName][] = $columnData : ''; 
        }

        ksort($joints, SORT_STRING);
        foreach ($joints as $joint => $columnInfo) {
            $gData[$joint]['hash'] = hash($this->encripty, json_encode($columnInfo));

            foreach($columnInfo as $tblName => $columns) {
                $gData[$joint]['tables'][$tblName]['hash'] = hash($this->encripty, json_encode($columnInfo));

                foreach ($columns as $column) {
                    $gData[$joint]['tables'][$tblName]['members'][$column['column_name']] = hash($this->encripty, json_encode($column));
                }
            }
        }

        return $gData[$joint];
    }
}