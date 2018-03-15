<?php
/**
 * @package    Profiles Component
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\RouteHelper;

class ProfilesHelperRoute extends RouteHelper
{
	/**
	 * Fetches the list route
	 *
	 * @return  string
	 *
	 * @since 1.0.0
	 */
	public static function getListRoute()
	{
		return 'index.php?option=com_profiles&view=list&key=1';
	}

	/**
	 * Fetches the profile route
	 *
	 * @param int $id Profile ID
	 *
	 * @return  string
	 *
	 * @since 1.0.0
	 */
	public static function getProfileRoute($id = null)
	{
		return 'index.php?option=com_profiles&view=profile&key=1&id=' . $id;
	}
}