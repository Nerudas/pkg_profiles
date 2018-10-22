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

use Joomla\CMS\Factory;
use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\HTML\HTMLHelper;

JLoader::register('ProfilesHelper', __DIR__ . '/helpers/centers.php');

HTMLHelper::_('behavior.tabstate');
HTMLHelper::_('stylesheet', 'media/com_profiles/css/admin-general.min.css', array('version' => 'auto'));

if (!Factory::getUser()->authorise('core.manage', 'com_centers'))
{
	throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

$controller = BaseController::getInstance('Profiles');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();