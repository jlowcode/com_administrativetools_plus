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
        $strSql = "SET sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';<ql>\n\n";

        if($type == 'adding') {
            foreach($changes as $idList => $funcs) {
                foreach($funcs as $func => $rows) {
                    $ids = array_keys($rows);
                    $strSql .= $this->buildStrSql($ids, $func, $idList, $type);
                    $strSql .= "<ql>\n\n";
                }
            }
        }

        if($type == 'user_change') {

        }

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
    private function buildStrSql($idEl, $func, $idList, $type)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $str = '';
        $valColumns = $this->getValuesToSqlFile($idEl, $func, $idList, $type);

        if(is_array($idEl)) {
            $idEl = implode('","', $idEl);
        }

        switch ($type) {
            case 'adding':
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

                $str = (string) $query;

                break;
        }

        return $str;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that build the string for sql file to API
     *
     */
    private function getValuesToSqlFile($idEl, $func, $idList, $type)
    {
        $db = JFactory::getDbo();
        $valColumns = Array();



        return $valColumns;
    }
}