<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Administrativetools
 * @author     Hirlei Carlos Pereira de Araújo <prof.hirleicarlos@gmail.com>
 * @copyright  2020 Hirlei Carlos Pereira de Araújo
 * @license    GNU General Public License versão 2 ou posterior; consulte o arquivo License. txt
 */

defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\MVC\Controller\BaseController;

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::registerPrefix('AdministrativetoolsFE', JPATH_COMPONENT);
JLoader::register('AdministrativetoolsFEController', JPATH_COMPONENT . '/controller.php');


// Execute the task.
$controller = BaseController::getInstance('AdministrativetoolsFE');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
