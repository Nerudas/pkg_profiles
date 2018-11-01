<?php
/**
 * @package    Profiles Component
 * @version    1.5.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;

JLoader::register('ProfilesHelper', __DIR__ . '/helpers/profiles.php');

HTMLHelper::_('behavior.tabstate');
HTMLHelper::_('stylesheet', 'media/com_profiles/css/admin-general.min.css', array('version' => 'auto'));

if (!Factory::getUser()->authorise('core.manage', 'com_profiles'))
{
	throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

if (empty(PluginHelper::getPlugin('system', 'profiles')))
{
	throw new Exception(Text::_('COM_PROFILES_ERROR_PLUGIN_NOT_ENABLED'), 404);
}

$controller = BaseController::getInstance('Profiles');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();