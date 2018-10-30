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

use Joomla\CMS\Helper\RouteHelper;

class ProfilesHelperRoute extends RouteHelper
{
	/**
	 * Fetches profiles view route
	 *
	 * @param  int $id Category ID
	 *
	 * @return  string
	 *
	 * @since 1.5.0
	 */
	public static function getProfilesRoute($id = 1)
	{
		return 'index.php?option=com_profiles&view=profiles&id=' . $id;
	}
}