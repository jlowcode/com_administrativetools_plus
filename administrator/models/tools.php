<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Administrativetools
 * @author     Hirlei Carlos Pereira de Araújo <prof.hirleicarlos@gmail.com>
 * @copyright  2020 Hirlei Carlos Pereira de Araújo
 * @license    GNU General Public License versão 2 ou posterior; consulte o arquivo License. txt
 */
defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Administrativetools records.
 *
 * @since  1.6
 */
class AdministrativetoolsModelTools extends \Joomla\CMS\MVC\Model\ListModel {

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   Elements order
     * @param   string  $direction  Order direction
     *
     * @return void
     *
     * @throws Exception
     */
    protected function populateState($ordering = null, $direction = null) {
        // List state information.
        parent::populateState("a.id", "ASC");

        $context = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $context);

        // Split context into component and optional section
        $parts = FieldsHelper::extract($context);

        if ($parts) {
            $this->setState('filter.component', $parts[0]);
            $this->setState('filter.section', $parts[1]);
        }
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return   string A store id.
     *
     * @since    1.6
     */
    protected function getStoreId($id = '') {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.state');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return   JDatabaseQuery
     *
     * @since    1.6
     */
    protected function getListQuery() {
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        return $query;
    }

    /**
     * Get an array of data items
     *
     * @return mixed Array of data items on success, false on failure.
     */
    public function getItems() {
        $items = parent::getItems();

        foreach ($items as &$i) {
            $n = $i->component_name . '_' . $i->version;
            $file = JPATH_ROOT . '/tmp/' . $i->component_name . '/pkg_' . $n . '.zip';
            $url = COM_FABRIK_LIVESITE . 'tmp/' . $i->component_name . '/pkg_' . $n . '.zip';

            if (JFile::exists($file)) {
                $i->file = '<a href="' . $url . '"><span class="icon-download"></span> pkg_' . $n . '.zip</a>';
            } else {
                $i->file = FText::_('COM_ADMINISTRATIVETOOLS_EXPORT_PACKAGE_TO_CREATE_ZIP');
            }
        }

        return $items;
    }

    /**
     * Method that lists the information, the database, the packages created.
     *
     * @return mixed
     */
    public function getListPackages() {
        $db = $this->getDbo();
        $query = "SELECT
                    pkg.`id`,
                    pkg.`name`,
                    pkg.file,
                    pkg.record,
                    DATE_FORMAT(pkg.date_time,'%d/%m/%Y %H:%i:%s') as date_time,
                    users.`name` AS usuario,
                    pkg.params
                    FROM
                    #__fabrik_pkgs AS pkg
                    LEFT JOIN #__users AS users ON pkg.users_id = users.id
                    ORDER BY
                    pkg.id DESC;";

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Checks whether the fabrik_pkgs table exists in the database.
     *
     * @return mixed
     *
     * @since version
     */
    public function getExistTablePkgs() {
        $db = $this->getDbo();

        $query = "SHOW TABLES LIKE '%fabrik_pkgs';";

        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Table creation structure fabrik_pkgs, if it has not already been created at installation.
     *
     * @return mixed
     *
     * @since version
     */
    public function getCreateTablePackages() {
        $db = $this->getDbo();

        $query = "CREATE TABLE IF NOT EXISTS `#__fabrik_pkgs` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `name` TEXT,
                    `file` TEXT,
                    `record` INT(255) DEFAULT NULL,
                    `date_time` datetime DEFAULT NULL,
                    `users_id` INT(11) DEFAULT NULL,
                    `params` VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY (`id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        $db->setQuery($query);

        return $db->execute();
    }
    
    /**
     * Method that searches the database and brings an object with all lists of the PITT project.
     *
     * @return mixed
     */
    public function getListsProjectPITT() {
        $db = $this->getDbo();
        $query = "SELECT
                    list.form_id AS `id`,
                    list.label
                    FROM
                    #__fabrik_lists AS list
                    WHERE
                    list.published = 1
                    GROUP BY
                    list.label;";

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Checks whether the table exists in the database.
     *
     * @return mixed
     *
     * @since version
     */
    public function checkTableExists($table){
        $db = $this->getDbo();

        $query = "SHOW TABLES LIKE '%{$table}%';";

        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Method that creates the table in the database when it does not exist.
     *
     * @return mixed
     */
    public function getCreateTableHarvesting(){
        $db = $this->getDbo();

        $db->transactionStart();

        $name = JText::_('COM_ADMINISTRATIVETOOLS_TABLE_NAME_HARVESTING');

        $query = "CREATE TABLE IF NOT EXISTS `#__fabrik_harvesting` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `repository` text DEFAULT NULL,
                  `list` varchar(255) DEFAULT NULL,
                  `dowload_file` varchar(255) DEFAULT NULL,
                  `extract` varchar(255) DEFAULT NULL,
                  `syncronism` tinyint(2) DEFAULT NULL,
                  `field1` varchar(255) DEFAULT NULL,
                  `field2` varchar(255) DEFAULT NULL,
                  `status` tinyint(1) DEFAULT 0,
                  `date_creation` datetime DEFAULT NULL,
                  `date_execution` datetime DEFAULT NULL,
                  `users_id` int(11) DEFAULT NULL,
                  `record_last` varchar(255) DEFAULT NULL,
                  `map_header` mediumtext DEFAULT NULL,
                  `map_metadata` mediumtext DEFAULT NULL,
                  `line_num` int(11) DEFAULT 0,
                  `page_xml` int(11) DEFAULT 0,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $db->setQuery($query);

        return $db->execute();
    }

    /**
     * Method that brings up a list of data from the main table of the harvesting tab.
     *
     * @return mixed
     */
    public function getListTableHarvesting(){
        $db = $this->getDbo();

        $query = "SELECT
                        harv.id, 
                        harv.repository, 
                        harv.list,
                        list.label,
                        harv.`status`, 
                        DATE_FORMAT(harv.date_execution, '%d/%m/%Y %H:%i:%s') AS date_exec,
                        harv.page_xml
                    FROM
                        #__fabrik_harvesting AS harv
                        LEFT JOIN
                        #__fabrik_lists AS list
                        ON 
                            harv.list = list.id                            
                    WHERE 
                        list.published = 1
                    ORDER BY
                        harv.id ASC;";

        $db->setQuery($query);

        return $db->loadObjectList();
    }
}
