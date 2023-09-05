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
    public function generateBaseFile($data_type, $model_type)
    {
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_administrativetools/models', 'AdministrativetoolsFEModel');
        $model = JModelLegacy::getInstance('Tool', 'AdministrativetoolsModel', array('ignore_request' => true));

        $data = new stdClass();
        $data->data_type = $data_type;
        $data->model_type = $model_type;
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
            foreach($changes as $staGroupment => $funcs) {
                foreach($funcs as $idList => $rowsFunc) {
                    if($staGroupment == 'PG') {
                        if(is_array($rowsFunc)) {
                            foreach($rowsFunc as $func => $rows) {
                                $ids = array_keys($rows);
                                $strSql .= $this->buildStrSql($ids, $type, $func);
                                $strSql .= "<ql>\n\n";
                            }
                        } else {
                            $type = 'adding-list';
                            $strSql .= $this->buildStrSql($idList, $type);
                        }
                    }

                    if($staGroupment == 'SG') {
                        $ids = array_keys($rowsFunc);
                        $func = $idList;
                        $strSql .= $this->buildStrSql($ids, $type, $func);
                        $strSql .= "<ql>\n\n";
                    }
                }
            }
        }

        if($type == 'user_change') {
            foreach($changes as $row) {
                if($row[0] == 'PG' || $row[0] == 'SG') {
                    $rows[$row[1]][$row[3]][] = $row[2];
                }
            }

            foreach($rows as $func => $rowsMembers) {
                foreach ($rowsMembers as $alteration => $values) {
                    $ids = array_values($values);
                    $typeSql = 'removing-member';
                    $strSql .= $this->buildStrSql($ids, $typeSql, $func);
                    $strSql .= "<ql>\n\n";

                    if($alteration == 'changed') {
                        $typeSql = 'adding';
                        $strSql .= $this->buildStrSql($ids, $typeSql, $func);
                        $strSql .= "<ql>\n\n";
                    }
                }
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
    private function buildStrSql($idEl, $type, $func='')
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
}