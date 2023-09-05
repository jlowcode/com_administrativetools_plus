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
 * Class AdministrativetoolsController
 *
 * @since  1.6
 */
class AdministrativetoolsController extends \Joomla\CMS\MVC\Controller\BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   mixed    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return   JController This object to support chaining.
	 *
	 * @since    1.5
     * @throws Exception
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$view = Factory::getApplication()->input->getCmd('view', 'tools');
		Factory::getApplication()->input->set('view', $view);

		parent::display($cachable, $urlparams);

		return $this;
	}

	/**
     * Fabrik sync lists 2.0
     *
     * Method that redirect to function that get the file with changes and update the changes
     *
     */
    public function setChangesUser()
    {
		$app = JFactory::getApplication();
		$input = $app->input;
		$response = Array();
		$arrNeeded = ['urlFile', 'format'];

		foreach ($arrNeeded as $value) {
			$$value = $input->getString($value);
		}

		$model = $this->getModel('Tool', 'AdministrativetoolsModel');
		$sync = $model->setChangesUser($urlFile);
		if($sync) {
			$response['error'] = false;
			$response['msg'] = JText::_('COM_ADMINISTRATIVETOOLS_SYNC_CHANGES_USER');
		} else {
			$response['error'] = true;
			$response['msg'] = JText::_('COM_ADMINISTRATIVETOOLS_NOT_SYNC_CHANGES_USER');
		}

		if($format == 'json') {
			echo json_encode($response);
			die();
		}
    }
}
