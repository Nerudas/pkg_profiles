<?php
/**
 * @package    Profiles Component
 * @version    1.0.8
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

	/**
	 * Fetches the socials authorization route
	 *
	 * @param int    $user_id  User ID
	 * @param string $provider Social network name
	 *
	 * @return  string
	 *
	 * @since 1.0.0
	 */
	public static function getSocialsAuthorizationRoute($user_id = null, $provider = null)
	{
		$link = 'index.php?option=com_profiles&task=social.authorization';

		if (!empty($user_id))
		{
			$link .= '&user_id=' . $user_id;
		}

		if (!empty($provider))
		{
			$link .= '&provider=' . $provider;
		}

		return $link;
	}

	/**
	 * Fetches the socials disconnect route
	 *
	 * @param int    $user_id  User ID
	 * @param string $provider Social network name
	 *
	 * @return  string
	 *
	 * @since 1.0.0
	 */
	public static function getSocialsDisconnectRoute($user_id = null, $provider = null)
	{
		$link = 'index.php?option=com_profiles&task=social.disconnect';

		if (!empty($user_id))
		{
			$link .= '&user_id=' . $user_id;
		}

		if (!empty($provider))
		{
			$link .= '&provider=' . $provider;
		}

		return $link;
	}
}