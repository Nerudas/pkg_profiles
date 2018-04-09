<?php
/**
 * @package    Profiles - Latest Module
 * @version    1.0.3
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

// Include route helper
JLoader::register('ProfilesHelperRoute', JPATH_SITE . '/components/com_profiles/helpers/route.php');
JLoader::register('CompaniesHelperRoute', JPATH_SITE . '/components/com_companies/helpers/route.php');

// Initialize model
BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/components/com_profiles/models');
$model = BaseDatabaseModel::getInstance('List', 'ProfilesModel', array('ignore_request' => true));
$model->setState('list.limit', $params->get('limit', 5));
if ($params->get('avatar_first', 1))
{
	$model->setState('list.ordering', 'avatar');
}
if ((!Factory::getUser()->authorise('core.edit.state', 'com_profiles.profile')) &&
	(!Factory::getUser()->authorise('core.edit', 'com_profiles.profile')))
{
	$model->setState('filter.published', 1);
}
else
{
	$model->setState('filter.published', array(0, 1));
}

// Variables
$items    = $model->getItems();
$listLink = Route::_(ProfilesHelperRoute::getListRoute());

require ModuleHelper::getLayoutPath($module->module, $params->get('layout', 'default'));