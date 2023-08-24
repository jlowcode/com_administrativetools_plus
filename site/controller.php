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

jimport('joomla.application.component.controller');

use \Joomla\CMS\Factory;

/**
 * Class AdministrativetoolsFEController
 *
 * @since  1.6
 */
class AdministrativetoolsFEController extends \Joomla\CMS\MVC\Controller\BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean $cachable  If true, the view output will be cached
	 * @param   mixed   $urlparams An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController   This object to support chaining.
	 *
	 * @since    1.5
     * @throws Exception
	 */
	public function display($cachable = false, $urlparams = false)
	{
        $app  = Factory::getApplication();
        $view = $app->input->getCmd('view', 'tools');
		$app->input->set('view', $view);

		parent::display($cachable, $urlparams);

		return $this;
	}

	/**
     * Fabrik sync lists 2.0
     * 
     * Method that redirect to function to generate the base file for API
     *
     */
    public function getBaseFile()
    {
		$app = JFactory::getApplication();
		$input = $app->input;
		$response = Array();
		$arrNeeded = ['format','key', 'secret', 'data_type', 'model_type'];

		foreach ($arrNeeded as $value) {
			$$value = $input->getString($value);
		}
		
		$auth = $this->authenticateApi($key, $secret);
		if (!$auth) {
			$response['error'] = true;
			$response['msg'] = 'Acesso não permitido, falha na autenticação';
		}

		if($auth) {
			$model = $this->getModel('Tool', 'AdministrativetoolsFEModel');
			$url = $model->generateBaseFile($data_type, $model_type);
			if($url) {
				$response['error'] = false;
				$response['msg'] = 'Arquivo gerado com sucesso!';
				$response['data'] = $url;
			}
		}

		if($format == 'json') {
			echo json_encode($response);
		}
    }

	/**
     * Fabrik sync lists 2.0
     * 
     * Method that authenticate the API
     *
     */
	public function authenticateApi($key, $secret) 
	{
		$access_token = base64_encode("$key:$secret");

		$auth = new stdClass();
		$auth->key = $key;
		$auth->secret = $secret;
		$auth->access_token = $access_token;

		$model = $this->getModel('Tool', 'AdministrativetoolsFEModel');

		return $model->authenticateApi($auth);
	}

	/**
     * Fabrik sync lists 2.0
     *
     * Method that redirect to function to generate the sql file with changes for API
     *
     */
    public function getChangesSqlFile()
    {
		$app = JFactory::getApplication();
		$input = $app->input;
		$response = Array();
		$arrNeeded = ['format','key', 'secret', 'changes', 'type', 'path'];

		foreach ($arrNeeded as $value) {
			$$value = $input->getString($value);
		}
		
		$auth = $this->authenticateApi($key, $secret);
		if (!$auth) {
			$response['error'] = true;
			$response['msg'] = JText::_('COM_ADMINISTRATIVETOOLS_EXCEPTION_MESSAGE_INFO_NO_OPTION_MERGE');;
		}

		if($auth) {
			$model = $this->getModel('Tool', 'AdministrativetoolsFEModel');
			$url = $model->getChangesSqlFile(json_decode($changes, true), $path, $type);
			if($url) {
				$response['error'] = false;
				$response['msg'] = 'Arquivo gerado com sucesso!';
				$response['data'] = $url;
			}
		}

		if($format == 'json') {
			echo json_encode($response);
		}
    }
}
