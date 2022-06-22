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
    public static function addSubmenu(string $vName = '') {
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

}
