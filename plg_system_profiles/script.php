<?php
/**
 * @package    System - Profiles Plugin
 * @version    1.5.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class PlgSystemProfilesInstallerScript
{

	/**
	 * Activate plugin
	 *
	 * @param  string $type      Type of PostFlight action. Possible values are:
	 *                           - * install
	 *                           - * update
	 *                           - * discover_install
	 * @param         $parent    Parent object calling object.
	 *
	 * @return bool
	 *
	 * @since 1.5.0
	 */
	function postflight($type, $parent)
	{
		$plugin          = new stdClass();
		$plugin->type    = 'plugin';
		$plugin->element = 'profiles';
		$plugin->folder  = 'system';
		$plugin->enabled = 1;

		Factory::getDbo()->updateObject('#__extensions', $plugin, array('type', 'element', 'folder'));

		return true;
	}
}