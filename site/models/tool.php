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

jimport('joomla.application.component.modelitem');
jimport('joomla.event.dispatcher');

use \Joomla\CMS\Factory;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Table\Table;

/**
 * Administrativetools model.
 *
 * @since  1.6
 */
class AdministrativetoolsFEModelTool extends \Joomla\CMS\MVC\Model\ItemModel
{
    /**
     * Fabrik sync lists 2.0
     * 
     * Method that generate the base file for API
     *
     */
    public function generateBaseFile($data_type, $model_type, $othersTables)
    {
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_administrativetools/models', 'AdministrativetoolsFEModel');
        $model = JModelLegacy::getInstance('Tool', 'AdministrativetoolsModel', array('ignore_request' => true));

        $data = new stdClass();
        $data->data_type = $data_type;
        $data->model_type = $model_type;
        $data->othersTables = json_decode($othersTables);
        $url = $model->searchListsAPI($data);

        return $url;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that authenticate the API
     *
     */
    public function authenticateApi($auth) 
    {
        $db = JFactory::getDbo();

        $key = $auth->key;
        $secret = $auth->secret;
        $access_token = $auth->access_token;

        $query = $db->getQuery(true);
        $query
            ->select('id')
            ->from('#__fabrik_api_access')
            ->where("client_id = '{$key}'")
            ->where("client_secret = '{$secret}'")
            ->where("access_token = '{$access_token}'");
        $db->setQuery($query);
        $result = $db->loadResult();

        return (bool) $result;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that generate the base file for API
     *
     */
    public function getChangesSqlFile($changes, $path, $type='user_change')
    {
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_administrativetools/models', 'AdministrativetoolsFEModel');
        $model = JModelLegacy::getInstance('Tool', 'AdministrativetoolsModel', array('ignore_request' => true));

        $db = JFactory::getDbo();
        $nameFile = 'sqlChanges.sql';
        $pathWithPrefix = JPATH_SITE . $path;
        $pathName = $pathWithPrefix . $nameFile;
        $strSql = '';
        $strSql = "SET sql_mode = 'NO_ENGINE_SUBSTITUTION';<ql>\n\n";

        if($type == 'adding') {
            //For data mode
            foreach($changes['data'] as $staGroupment => $funcs) {
                foreach($funcs as $idList => $rowsFunc) {
                    if($staGroupment == 'PG') {
                        if(is_array($rowsFunc)) {
                            foreach($rowsFunc as $func => $rows) {
                                $ids = array_keys($rows);
                                $strSql .= $this->buildStrSqlToDataMode($ids, $type, $func);
                                $strSql .= "<ql>\n\n";
                            }
                        }
                    }

                    if($staGroupment == 'SG') {
                        $ids = array_keys($rowsFunc);
                        $func = $idList;
                        $strSql .= $this->buildStrSqlToDataMode($ids, $type, $func);
                        $strSql .= "<ql>\n\n";
                    }

                    if($staGroupment == 'lists') {
                        $type = 'adding-list';
                        $strSql .= $this->buildStrSqlToDataMode($idList, $type);
                    }
                }
            }

            //For model mode
            foreach ($changes['model'] as $joint) {
                foreach ($joint as $table => $columns) {
                    $arrOpts = Array();
                    $arrOpts[1] = $table;
                    $arrOpts[2] = array_keys($columns);
                    $type = 'added';
                    $strSql .= $this->buildStrSqlToModelMode($type, $arrOpts);
                    $strSql .= "<ql>\n\n";
                }
            }
        }

        if($type == 'user_change') {
            //Separating the modes
            $arrChanges = Array();
            $rows = Array();
            foreach ($changes as $key => $value) {
                $arrChanges[$value[0]][] = $value;
            }

            //For data mode
            foreach($arrChanges['data'] as $row) {
                if($row[1] == 'PG' || $row[1] == 'SG') {
                    $rows[$row[2]][$row[4]][] = $row[3];
                }
            }

            foreach($rows as $func => $rowsMembers) {
                foreach ($rowsMembers as $alteration => $values) {
                    $ids = array_values($values);
                    $typeSql = 'removing-member';
                    $strSql .= $this->buildStrSqlToDataMode($ids, $typeSql, $func);
                    $strSql .= "<ql>\n\n";

                    if($alteration == 'changed') {
                        $typeSql = 'adding';
                        $strSql .= $this->buildStrSqlToDataMode($ids, $typeSql, $func);
                        $strSql .= "<ql>\n\n";
                    }
                }
            }

            //For model mode
            foreach ($arrChanges['model'] as $change) {
                $type = $change[3];
                $strSql .= $this->buildStrSqlToModelMode($type, $change);
                $strSql .= "<ql>\n\n";
            }
        }

        $strSql .= "SET sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';<ql>\n\n";

        $sqlFile = $model->writeFile($strSql, $pathName);

        $fullUrl = JURI::base();
        $parsedUrl = parse_url($fullUrl);
        $pathUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

        return $sqlFile ? $pathUrl . $path . $nameFile : false;
    }

    /**
     * Fabrik sync lists 2.0
     *
     * Method that build the string for sql file to API
     *
     */
    private function buildStrSqlToDataMode($idEl, $type, $func='')
    {
        $strQuery = '';

        if(is_array($idEl)) {
            $idEl = implode('","', $idEl);
        }

        switch ($type) {
            case 'adding':
                $query = $this->buildQueryDataMembers($idEl, $func);
                break;
            
            case 'adding-list':
                $query = $this->buildQueryNewLists($idEl);
                break;

            case 'removing-member':
                $query = $this->buildQueryRemovingMember($idEl, $func);
                break;
        }

        return (string) $query;
    }


    /**
     * Fabrik sync lists 2.0
     *
     * Method that build the query for sql file, in case of data members
     *
     */
    private function buildQueryDataMembers($idEl, $func)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->clear()
            ->select('*')
            ->from($db->qn('#__fabrik_'.$func))
            ->where('id IN ("' . $idEl . '")');
        $db->setQuery($query);
        $values = $db->loadAssocList();

        if(empty($values)) {
            return;
        }

        $query->clear()
            ->insert($db->qn('#__fabrik_'.$func));
        foreach ($values as $arrRow) {
            $query->values(implode(",", array_map(
                function($vlr) {
                    $db = JFactory::getDbo();
                    return $db->q($vlr);
                },
                $arrRow)));
        }

        return $query;
    }

    /**
     * Fabrik sync lists 2.0
     *
     * Method that build the query for sql file, in case of new lists
     *
     */
    private function buildQueryNewLists($idList)
    {
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_administrativetools/models', 'AdministrativetoolsFEModel');
        $modelAdmin = JModelLegacy::getInstance('Tool', 'AdministrativetoolsModel', array('ignore_request' => true));

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $strQuerys = '';

        // List grouping hash
        $query->clear()
            ->select('*')
            ->from($db->qn('#__fabrik_lists'))
            ->order('id')
            ->where('id = ' . $db->q($idList));
        $db->setQuery($query);
        $list = $db->loadAssocList();

        if(empty($list)) {
            return;
        }

        //Getting the create table of main table
        $tableName = $list[0]['db_table_name'];
        $db->setQuery("SHOW CREATE TABLE " . $db->qn($tableName));
        $createTable = $db->loadColumn(1)[0];
        $strQuerys .= (string) $createTable;
        $strQuerys .= "<ql>\n\n";

        $query->clear()
            ->insert($db->qn('#__fabrik_lists'));
        foreach ($list as $arrRow) {
            $query->values(implode(",", array_map(
                function($vlr) {
                    $db = JFactory::getDbo();
                    return $db->q($vlr);
                },
                $arrRow)));
        }

        $strQuerys .= (string) $query;
        $strQuerys .= "<ql>\n\n";

        $queryG0 = $modelAdmin->buildQueryGroupments('G0', $idList, '#__fabrik_lists', 'nm');
        $db->setQuery($queryG0);
        $rowGroupments = $db->loadAssocList();

        foreach ($rowGroupments as $row) {
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
        }

        foreach ($members as $func => $els) {
            $columns = Array();
            $query->clear()
                ->insert($db->qn('#__fabrik_'.$func));
            foreach($els as $el) {
                $query->values(implode(",", array_map(
                    function($vlr) {
                        $db = JFactory::getDbo();
                        return $db->q($vlr);
                    },
                    $el)));
                empty($columns) ? $columns = array_keys($el) : '';
            }
            $query->columns(implode(",", array_map(
                function($columns) {
                    $db = JFactory::getDbo();
                    return $db->qn($columns);
                }, $columns)
            ));

            $strQuerys .= (string) $query;
            $strQuerys .= "<ql>\n\n";
        }

        return $strQuerys;
    }

    /**
     * Fabrik sync lists 2.0
     *
     * Method that build the query for sql file, in case of removing members
     *
     */
    private function buildQueryRemovingMember($idEl, $func)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->clear()
            ->delete($db->qn('#__fabrik_'.$func))
            ->where('id IN ("' . $idEl . '")');

        return $query;
    }

    /**
     * Fabrik sync lists 2.0
     *
     * Method that build the string to data mode for sql file to API
     *
     */
    private function buildStrSqlToModelMode($type, $opts)
    {
        $table = $opts[1];
        $column = $opts[2];

        switch ($type) {
            case 'changed':
                $query = $this->buildQueryChangedColumn($table, $column);
                break;
            
            case 'removed':
                $query = $this->buildQueryRemovedColumn($table, $column);
                break;

            case 'added':
                $query = $this->buildQueryAddedColumn($table, $column);
                break;
        }

        return (string) $query;
    }

    /**
     * Fabrik sync lists 2.0
     *
     * Method that build the query for sql file, in case of columns that were change
     *
     */
    private function buildQueryChangedColumn($table, $column)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->clear()
            ->select($db->qn('column_type') . ' AS column_type')
            ->from($db->qn('information_schema') . '.' . $db->qn('columns'))
            ->where($db->qn('table_schema') . ' = (SELECT DATABASE())')
            ->where($db->qn('table_name') . ' = ' . $db->q($table))
            ->where($db->qn('column_name') . ' = ' . $db->q($column));
        $db->setQuery($query);
        $typeColumn = $db->loadResult();

        $query = "ALTER TABLE $table MODIFY $column $typeColumn;";

        return $query;
    }

    /**
     * Fabrik sync lists 2.0
     *
     * Method that build the query for sql file, in case of columns that were add
     *
     */
    private function buildQueryAddedColumn($table, $column)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        if(is_array($column)) {
            $column = implode("','", $column);
        }

        $query->clear()
            ->select($db->qn('column_name') . ' AS column_name')
            ->select($db->qn('column_type') . ' AS column_type')
            ->select($db->qn('is_nullable') . ' AS is_nullable')
            ->select($db->qn('column_default') . ' AS column_default')
            ->from($db->qn('information_schema') . '.' . $db->qn('columns'))
            ->where($db->qn('table_schema') . ' = (SELECT DATABASE())')
            ->where($db->qn('table_name') . ' = ' . $db->q($table))
            ->where($db->qn('column_name') . " IN ('" . $column . "')");
        $db->setQuery($query);
        $columns = $db->loadObjectList('column_name');

        $qtnColumns = count($columns);
        if($qtnColumns > 0) {
            $queryReturn = "ALTER TABLE `$table`\n";
            $x = 0;
            foreach ($columns as $column => $dataColumn) {
                $queryReturn .= "ADD COLUMN `$column` " . $dataColumn->column_type;
                isset($dataColumn->column_default) ? $queryReturn .= ' DEFAULT ' . $dataColumn->column_default : '';
                $dataColumn->is_nullable == 'NO' ? $queryReturn .= ' NOT NULL' : '';

                $x != $qtnColumns - 1 ? $queryReturn .= ",\n" : $queryReturn .= ';';
                $x++;
            }
        }

        return $queryReturn;
    }

    /**
     * Fabrik sync lists 2.0
     *
     * Method that build the query for sql file, in case of columns that were remove
     *
     */
    private function buildQueryRemovedColumn($table, $column)
    {
        return "ALTER TABLE `$table` DROP COLUMN `$column`;";
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that sync the lists.
     *
     */
    public function syncListsIdentical($data)
    {
        //Initial configurations
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_administrativetools/models', 'AdministrativetoolsFEModel');
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/models', 'FabrikAdminModelAdministrativetools');
        $adminModel = JModelLegacy::getInstance('Tool', 'AdministrativetoolsModel', array('ignore_request' => true));
        $adminFabrik = JModelLegacy::getInstance('Administrativetools', 'FabrikAdminModel', array('ignore_request' => true));
        $db = $this->getDbo();

        //Configuring the tables needed
        $data->data_type == 'identical' ? $arrFabrikTables = $adminModel->tablesVersions() : $arrFabrikTables = Array();
        $data->model_type == 'identical' ? $arrModelTables = $this->tablesOnlyModel() : $arrModelTables = Array();
        $arrOthersTables = json_decode($data->othersTables);

        //Calling the fabrik version control
        if(method_exists('FabrikAdminModelAdministrativetools', 'generateSql')) {
            $nameVersion = $adminFabrik->generateSql();
            $idVersion = substr($nameVersion, strpos($nameVersion, '_')+1, strpos($nameVersion, '.sql')-strpos($nameVersion, '_')-1);
            $newVersion = $this->newVersion($idVersion, $nameVersion);
        }

        $arrTables = array_merge($arrFabrikTables, $arrModelTables, $arrOthersTables);
        if(!empty($arrTables)) {
            //Paths needed
            $nameFile = 'dumpEnv.sql';
            $path = JPATH_SITE . $data->path;
            $pathName = $path . '/' . $nameFile;

            $adminModel->cleanThePath($pathName);

            if(is_file($pathName)) {
                unlink($pathName);
            }

            $handle = fopen($pathName, 'x+');
            $numtypes = array('tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'float', 'double', 'decimal', 'real');
            $sqlFile = "SET sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';<ql>\n\n";

            //Cycle through the table(s)
            foreach ($arrTables as $table) {
                //If the tables are only_data execute truncate and insert into like fabrik version control
                if(substr($table, 0, strlen('#__')) == '#__') {
                    $sqlFile .= "TRUNCATE TABLE " . $db->qn($table) . ";<ql>\n\n";
                    $db->setQuery("SELECT * FROM $table");
                    $result = $db->loadObjectList();

                    if(!empty($result)) {
                        $sqlFile .= 'INSERT INTO `' . $table . '` (';
                        $db->setQuery("SHOW COLUMNS FROM $table");
                        $pstm3 = $db->loadObjectList('Field');
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
                    $sqlFile .= "DROP TABLE IF EXISTS " . $db->qn($tempTable) . ";<ql>\n\n";

                    /* USAR NA SINCRONIZAÇÃO DO ARQUIVO SQL
                    if(in_array($table, $arrModelTables['internal'])) {
                        $sqlFile .= "RENAME TABLE $table TO $tempTable;<ql>\n\n";
                    }*/

                    $db->setQuery("SHOW CREATE TABLE $table");
                    $createTable = $db->loadColumn(1)[0];
                    $sqlFile .= $createTable . ";<ql>\n\n";
                    $sqlFile .= "SINCRONIZACAO $table;<ql>\n\n";

                    /* USAR NA SINCRONIZAÇÃO DO ARQUIVO SQL
                    if(in_array($table, $arrMo;<ql>\n\ndelTables['internal'])) {
                        $db->setQuery(
                            "SELECT column_name AS column_name, column_type AS column_type from INFORMATION_SCHEMA.COLUMNS WHERE table_schema = (SELECT DATABASE()) AND table_name = '$table';"
                        );
                        $columnsExternal = $db->loadAssocList('column_name', 'column_type');

                        $db->setQuery(
                            "SELECT column_name AS column_name, column_type AS column_type from INFORMATION_SCHEMA.COLUMNS WHERE table_schema = (SELECT DATABASE()) AND table_name = '$table';"
                        );
                        $columnsInternal = $db->loadAssocList('column_name', 'column_type');

                        $samesColumns = array_intersect_assoc($columnsExternal, $columnsInternal);
                        $columns = array_keys($samesColumns);

                        $sqlFile .= "INSERT INTO " . $db->qn($table) . " (`" . implode("`,`", $columns) . "`)\n";
                        $sqlFile .= "SELECT `" . implode("`,`", $columns) . "`\n";
                        $sqlFile .= "FROM " . $db->qn($tempTable) . ";<ql>\n\n";
                    }*/

                    fwrite($handle, $sqlFile);
                    $sqlFile = "";
                }
            }

            fclose($handle);
        }

        return JURI::base() . $path . $nameFile;
    }

    /**
     * Fabrik sync lists 1.0
     * 
     * Method that return the database tables that the sync will be only with model.
     * 
     */ 
    private function tablesOnlyModel()
    {
        $db = $this->getDbo();
		$query = $db->getQuery(true)
            ->clear()
            ->select($db->qn('db_table_name'))
            ->from($db->qn('#__fabrik_lists'));

        $db->setQuery($query);
        $tables = array_unique($db->loadColumn(), SORT_STRING);

        foreach($tables as $table) {
            $query = $db->getQuery(true)
                ->clear()
                ->select($db->qn('table_name'))
                ->from($db->qn('information_schema') . '.' . $db->qn('tables'))
                ->where($db->qn('table_schema') . ' = (SELECT DATABASE())')
                ->where($db->qn('table_name') . ' LIKE ' . $db->q($table . '%'));
            $db->setQuery($query);
            $relationTables = $db->loadColumn();
            foreach($relationTables as $relationTable) {
                !in_array($relationTable, $tables) ? $tables[] = $relationTable : false;
            }
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
}