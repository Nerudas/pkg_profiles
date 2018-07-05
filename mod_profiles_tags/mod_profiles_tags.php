<?php
/**
 * @package    Profiles - Tags Module
 * @version    1.1.1
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Factory;

// Include Route Helper
JLoader::register('ProfilesHelperRoute', JPATH_SITE . '/components/com_companies/helpers/route.php');

// Include Module Helper
require_once __DIR__ . '/helper.php';

// Load languages
$language = Factory::getLanguage();
$language->load('com_profiles', JPATH_SITE, $language->getTag(), true);

$tags = modProfilesTagsHelper::getTags($params);


require ModuleHelper::getLayoutPath($module->module, $params->get('layout', 'default'));