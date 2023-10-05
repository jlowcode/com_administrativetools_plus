<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Administrativetools
 * @author     Hirlei Carlos Pereira de Araújo <prof.hirleicarlos@gmail.com>
 * @copyright  2020 Hirlei Carlos Pereira de Araújo
 * @license    GNU General Public License versão 2 ou posterior; consulte o arquivo License. txt
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;

/**
 * Administrativetools helper.
 *
 * @since  1.6
 */
class AdministrativetoolsHelper {

    /**
     * Configure the Linkbar.
     *
     * @param string $vName  string
     * @return void
     * @since  1.6
     */
    public static function addSubmenu(string $vName = '') 
    {
        JHtmlSidebar::addEntry(
                JText::_('COM_ADMINISTRATIVETOOLS_TITLE_TOOLS'),
                'index.php?option=com_administrativetools&view=tools', $vName == 'tools');
    }

    /**
     * Gets the files attached to an item
     *
     * @param int $pk     The item's id
     * @param string $table  The table's name
     * @param string $field  The field's name
     * @return  array  The files
     * @since  1.6
     */
    public static function getFiles(int $pk, string $table, string $field): array
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query
                ->select($field)
                ->from($table)
                ->where('id = ' . (int) $pk);

        $db->setQuery($query);

        return explode(',', $db->loadResult());
    }

    /**
     * Gets a list of the actions that can be performed.
     *
     * @return    JObject
     * @since    1.6
     */
    public static function getActions()
    {
        $user = Factory::getUser();
        $result = new JObject;

        $assetName = 'com_administrativetools';

        $actions = array(
            'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
        );

        foreach ($actions as $action) {
            $result->set($action, $user->authorise($action, $assetName));
        }

        return $result;
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that generate the configurations of data to show details for user
     *
     */
    public function generateDataTable(&$changesToTable, $x, $opts) 
    {
        foreach ((array) $opts as $var => $value) {
            $$var = $value;
        }

        //Trocar depois para função que retire o plural
        switch ($funcionality) {
            case 'elements':
                $view = 'element';
                break;
            case 'lists':
                $view = 'list';
                break;
            case 'forms':
                $view = 'form';
                break;
            case 'visualizations':
                $view = 'visualization';
                break;
            case 'groups':
                $view = 'group';
                break;
            case 'cron':
                $view = $funcionality;
                break;
            default:
                $view = '';
                break;
        }

        $val === true ? $val = 'changed' : '';
        $value = $mode . '--' . $mod . '--' . $funcionality . '--' . $idFunc . '--' . $val;
        $urlPrefix = JUri::root();
        $urlPath = "administrator/index.php?option=com_fabrik&view=$view&layout=edit&id=$idFunc";
        $uri = $urlPrefix  . $urlPath;
        $uri = "<a href='" . $uri . "'>" . $uri . "<a>";
        $checkbox = '<input type="checkbox" class="changes" name="changes[]" value="' . $value .'">';
        if($val === 'removed') {
            $msg = FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_FUNC_REMOVED');
        } else if($val === 'added') {
            $msg = FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_FUNC_ADDED');
            $checkbox = 'Adicionado';
        } else {
            $msg = FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_FUNC_CHANGED');
        }
        
        $changesToTable[$idList][$x][] = $idList;
        $changesToTable[$idList][$x][] = $idFunc;
        $changesToTable[$idList][$x][] = $funcionality;
        $changesToTable[$idList][$x][] = $msg;
        $changesToTable[$idList][$x][] = $uri;
        $changesToTable[$idList][$x][] = '<p class="check">' . $checkbox . '</p>';
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that construct the struct to generate the data table for data mod
     *
     */
    public function constructDataTableDataMod($type, $value, &$changesToTable, &$x, $mod=false, $mode=false) 
    {
        $mod ? '' : $mod = $type;

        if($type == 'add') {
            foreach($value as $idList => $vals) {
                if($x == 0) $first = $idList;
                foreach($vals as $funcionality => $row) {
                    foreach($row as $idFunc => $val) {
                        $opts = new stdClass();
                        $opts->idList = $idList;
                        $opts->idFunc = $idFunc;
                        $opts->funcionality = $funcionality;
                        $opts->val = $val;
                        $opts->mod = $mod;
                        $opts->mode = $mode;
                        $this->generateDataTable($changesToTable, $x, $opts);
                        $x++;
                    }
                }
            }
        }

        if($type == 'PG') {
            foreach($value as $idList => $groupments) {
                if($x == 0) $first = $idList;
                foreach($groupments as $groupment) {
                    foreach($groupment as $funcionality => $row) {
                        foreach($row as $idFunc => $val) {
                            $opts = new stdClass();
                            $opts->idList = $idList;
                            $opts->idFunc = $idFunc;
                            $opts->funcionality = $funcionality;
                            $opts->val = $val;
                            $opts->mod = $mod;
                            $opts->mode = $mode;
                            $this->generateDataTable($changesToTable, $x, $opts);
                            $x++;
                        }
                    }
                }
            }
        }

        if($type == 'SG') {
            foreach($value as $groupment => $rows) {
                switch ($groupment) {
                    case 'G5':
                        $funcionality = 'cron';
                        break;
                    case 'G6':
                        $funcionality = 'visualization';
                        break;
                }
                if($x == 0) $first = $idList;
                foreach($rows as $idRow => $val) {
                    $opts = new stdClass();
                    $opts->idList = FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_NOT_APPLY');;
                    $opts->idFunc = $idRow;
                    $opts->funcionality = $funcionality;
                    $opts->val = $val;
                    $opts->mod = $mod;
                    $opts->mode = $mode;
                    $this->generateDataTable($changesToTable, $x, $opts);
                    $x++;
                }
            }
        }
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that construct the struct to generate the data table for model mod
     *
     */
    public function constructDataTableModelMod($type, $value, $joint, &$changesToTable, &$x, $mode=false) 
    {
        if($joint === 'add') {
            foreach($value as $table => $vals) {
                if($x == 0) $first = $idList;
                foreach($vals as $column => $mod) {
                    if($x == 0) $first = $column;
                    $opts = new stdClass();
                    $opts->table = $table;
                    $opts->joint = $type;
                    $opts->column = $column;
                    $opts->mod = $mod;
                    $opts->val = $mod;
                    $opts->mode = $mode;
                    $this->generateDataTableToModelMode($changesToTable, $x, $opts);
                    $x++;
                }
            }
        }

        if($joint != 'add') {
            foreach($value as $column => $mod) {
                $mod === true ? $val = 'changed' : $val = 'removed'; 
                if($x == 0) $first = $column;
                $opts = new stdClass();
                $opts->table = $type;
                $opts->joint = $joint;
                $opts->column = $column;
                $opts->mod = $mod;
                $opts->val = $val;
                $opts->mode = $mode;
                $this->generateDataTableToModelMode($changesToTable, $x, $opts);
                $x++;
            }
        }
    }

    /**
     * Fabrik sync lists 2.0
     * 
     * Method that generate the configurations of data to show details for user
     *
     */
    public function generateDataTableToModelMode(&$changesToTable, $x, $opts) 
    {
        foreach ((array) $opts as $var => $value) {
            $$var = $value;
        }

        $mod === true ? $mod = 'changed' : '';
        $value = $mode . '--' . $table . '--' . $column . '--' . $mod;
        $checkbox = '<input type="checkbox" class="changes" name="changes[]" value="' . $value .'">';
        if($val === 'removed') {
            $msg = FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_COLUMN_REMOVED');
        } else if($val === 'added') {
            $msg = FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_COLUMN_ADDED');
            $checkbox = 'Adicionado';
        } else {
            $msg = FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_COLUMN_CHANGED');
        }

        $changesToTable[$joint][$x][] = $joint;
        $changesToTable[$joint][$x][] = $table;
        $changesToTable[$joint][$x][] = $column;
        $changesToTable[$joint][$x][] = $msg;
        $changesToTable[$joint][$x][] = '';
        $changesToTable[$joint][$x][] = '<p class="check">' . $checkbox . '</p>';
    }
}
