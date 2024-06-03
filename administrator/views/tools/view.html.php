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
jimport('joomla.application.component.view');

use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Factory;

/**
 * View class for a list of Administrativetools.
 *
 * @since  1.6
 */
class AdministrativetoolsViewTools extends \Joomla\CMS\MVC\View\HtmlView
{
    protected $items;
    protected $pagination;
    protected $state;

    /**
     * Display the view
     *
     * @param string $tpl Template name
     *
     * @return void
     *
     * @throws Exception
     * @since    1.6
     */
    public function display($tpl = null)
    {
        $this->state = $this->get('State');
        $db = Factory::getContainer()->get('DatabaseDriver');
        $config = JFactory::getConfig();
        $app = JFactory::getApplication();
        $doc = JFactory::getDocument();
        $input = $app->input;

        $this->list = $this->get('ListsProjectPITT');
        $exist_table = $this->get('ExistTablePkgs');

        // Fabrik sync lists 1.0
        $this->connection = $this->get('connectionSyncLists');

        if ($exist_table === NULL) {
            $this->get('CreateTablePackages');
        }

        $this->text_message = $string_array = implode("|", array(Text::_('JYES'), Text::_('JNO'), Text::_('JMESSAGE'),
            Text::_('COM_ADMINISTRATIVETOOLS_PACKAGES_CREATE_MESSAGE_QUESTION_FILE'), Text::_('COM_ADMINISTRATIVETOOLS_PACKAGES_CREATE_MESSAGE_QUESTION_PACKAGE'),
            Text::_('JSUCCESS'), Text::_('COM_ADMINISTRATIVETOOLS_PACKAGES_CREATE_MESSAGE_SUCCESS'), Text::_('COM_ADMINISTRATIVETOOLS_PACKAGES_LIST_TABLE_MESSAGE_SUCCESS')));

        $folder_path = pathinfo($_SERVER['SCRIPT_FILENAME']);

        $this->folder = $folder_path['dirname'] . '/components/com_administrativetools/packagesupload';

        $this->files = scandir($this->folder);

        $dbprefix = $config->get('dbprefix');
        $database = $config->get('db');
        $sql_show = "SHOW TABLES FROM {$db->qn($database)}"; // TODAS AS TABELAS INDEPENDENTE DE PREFIXO
        // $sql_show = "SHOW TABLES FROM {$database} LIKE '{$dbprefix}%'";
        $db->setQuery($sql_show);
        $allTables = $db->loadColumn();
        $joomlaTables = array();

        foreach ($allTables as $table) {
            if (strpos($table, 'fabrik') === false) {
                $joomlaTables[] = $table;
            }
        }

        $this->joomlaTables = $joomlaTables;

        $sql_show = "SELECT DISTINCT list.db_table_name, list.id, list.label
                FROM #__fabrik_lists AS list ;";
        $db->setQuery($sql_show);
        $fabrikLists = $db->loadObjectList();
        $this->fabrikLists = $this->sortLists($fabrikLists);

        $this->list_packages = $this->get('ListPackages');

        $exist_table = $this->getModel()->checkTableExists(Text::_('COM_ADMINISTRATIVETOOLS_TABLE_NAME_HARVESTING'));

        if ($exist_table === NULL) {
            try {
                $this->tb_harvest = $this->get('CreateTableHarvesting');
                $app->enqueueMessage(Text::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_SUCCESS0') . Text::_('COM_ADMINISTRATIVETOOLS_TABLE_NAME_HARVESTING'));
            } catch (Exception $e) {
                $message = FabrikAdminController::handlePossibleExceptions($e->getCode(), $e->getMessage());
                $app->enqueueMessage($message, 'warning');
            }
        }

        $this->dados_tb_harvest = $this->get('ListTableHarvesting');

        $this->linksCssJs($doc);
        $this->jsScriptTranslation();

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors));
        }

        AdministrativetoolsHelper::addSubmenu('tools');

        $this->addToolbar();

        $this->sidebar = JHtmlSidebar::render();
        parent::display($tpl);
    }

    private function sortLists($lists)
    {
        usort(
            $lists,
            function ($a, $b) {
                if ($a->label == $b->label) return 0;
                return (($a->label < $b->label) ? -1 : 1);
            }
        );

        return $lists;
    }

    /**
     * Function that groups all link links with css and js in the system.
     *
     * @param $doc
     * @since    1.6
     */
    function linksCssJs($doc)
    {
        $doc->addStyleSheet('components/com_administrativetools/assets/css/alertify.min.css');
        $doc->addStyleSheet('components/com_administrativetools/assets/css/bootstrap.min.css');
        $doc->addStyleSheet('components/com_administrativetools/assets/css/administrativetools.css');
        JHtml::_('jquery.framework');
        $doc->addScript('components/com_administrativetools/assets/js/alertify.min.js');
        $doc->addScript('components/com_administrativetools/assets/js/administrativetools.js');
    }

    /**
     * Function sends message texts to javascript file
     *
     * @since version
     */
    function jsScriptTranslation()
    {
        Text::script('COM_ADMINISTRATIVETOOLS_MESSAGE_TITLE_ALERT');
        Text::script('COM_ADMINISTRATIVETOOLS_MESSAGE_TITLE_ALERT1');
        Text::script('COM_ADMINISTRATIVETOOLS_MESSAGE_LABEL_ALERT_REQUIRED_FIELDS');
        Text::script('COM_ADMINISTRATIVETOOLS_MESSAGE_LABEL_ALERT_REQUIRED_FIELD');
        Text::script('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_FIELD_VALUE0');
        Text::script('COM_ADMINISTRATIVETOOLS_MESSAGE_ALERT_ERRO_SELECT_LIST');
        Text::script('COM_ADMINISTRATIVETOOLS_HARVESTING_OPTION_REPOSITORY_1');
        Text::script('COM_MEDIA_PITT_OPTION_1');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_LABEL');
        Text::script('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_ERROR2');
        Text::script('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_FIELD_ELEMENT_VALUE0');
        Text::script('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_SUCCESS4');

        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION0');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION1');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION2');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION3');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION4');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION5');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION6');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION7');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION8');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION9');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION10');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION11');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION12');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION13');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION14');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION15');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION16');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION17');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION18');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION19');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION20');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION21');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION22');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION23');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION24');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION25');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION26');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION27');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION28');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION29');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION30');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION31');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION32');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION33');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION34');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION35');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION36');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION37');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION38');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION39');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION40');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION41');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION42');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION43');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION44');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION45');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION46');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION47');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION48');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION49');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION50');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION51');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION52');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION53');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION54');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION55');
        Text::script('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION56');
        Text::script('COM_ADMINISTRATIVETOOLS_CLEANDB_CONFIRM_DELETE');
        Text::script('COM_ADMINISTRATIVETOOLS_CLEANDB_ATTENCTION');
    }

    /**
     * Function that checks the tabs to always open the one you are currently using.
     *
     * @param $id
     *
     * @since version
     * @deprecated  4.0.0
     */
    function activateTab($id)
    {
        $this->tab1 = "";
        $this->tab2 = "";
        $this->tab3 = "";
        $this->tab4 = "";
        $this->tab5 = "";
        //Fabrik sync lists 1.0
        $this->tab6 = "";
        $this->tab7 = "";

        if ($id === 1) {
            $this->tab1 = "active";
        } elseif ($id === 2) {
            $this->tab2 = "active";
        } elseif ($id === 3) {
            $this->tab3 = "active";
        } elseif ($id === 4) {
            $this->tab4 = "active";
        } elseif ($id === 5) {
            $this->tab5 = "active";
        } elseif ($id === 6) {
            //Fabrik sync lists 1.0
            $this->tab6 = "active";
        } elseif ($id === 7) {
            $this->tab7 = "active";
        }
    }

    /**
     * Add the page title and toolbar.
     *
     * @return void
     *
     * @since    1.6
     */
    protected function addToolbar()
    {
        $state = $this->get('State');
        $canDo = AdministrativetoolsHelper::getActions();

        JToolBarHelper::title(Text::_('COM_ADMINISTRATIVETOOLS_TITLE_TOOLS'), 'tools.png');

        // Check if the form exists before showing the add/edit buttons
        $formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/tool';

        if ($canDo->get('core.admin')) {
            JToolBarHelper::preferences('com_administrativetools');
        }

        // Set sidebar action - New in 3.0
        JHtmlSidebar::setAction('index.php?option=com_administrativetools&view=tools');
    }

    /**
     * Method to order fields
     *
     * @return void
     * @since    1.6
     */
    protected function getSortFields()
    {
        return array();
    }

    /**
     * Check if state is set
     *
     * @param mixed $state State
     *
     * @return bool
     * @since    1.6
     */
    public function getState($state)
    {
        return isset($this->state->{$state}) ? $this->state->{$state} : false;
    }
}