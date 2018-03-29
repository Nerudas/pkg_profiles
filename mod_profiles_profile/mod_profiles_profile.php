<?php
/**
 * @package    Profiles - Profile Module
 * @version    1.0.1
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;

JLoader::register('ProfilesHelperRoute', JPATH_SITE . '/components/com_profiles/helpers/route.php');
JLoader::register('CompaniesHelperRoute', JPATH_SITE . '/components/com_companies/helpers/route.php');

// Include Module Helper
require_once __DIR__ . '/helper.php';
$helper = new modProfilesProfileHelper($params);

// Get Profile
$profile = $helper->getProfile();

require ModuleHelper::getLayoutPath($module->module, $params->get('layout', 'default'));