<?php
/**
 * @package    Authentication - Profiles Plugin
 * @version    1.0.6
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;


class plgAuthenticationProfiles extends CMSPlugin
{
	/**
	 * The name of the plugin
	 *
	 * @var    string
	 * @since 1.0.0
	 */
	public $name = 'Profiles';

	/**
	 * This method should handle any authentication and report back to the subject
	 *
	 * @param    array    Array holding the user credentials
	 * @param    array    Array of extra options
	 * @param    object    Authentication response object
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	function onUserAuthenticate(&$credentials, $options, &$response)
	{
		$result = false;

		if (empty($credentials['noPlugin']))
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/models', 'ProfilesModel');
			$model = BaseDatabaseModel::getInstance('User', 'ProfilesModel');

			if (empty($credentials['password']))
			{
				$response->status        = JAuthentication::STATUS_FAILURE;
				$response->error_message = Text::_('JGLOBAL_AUTH_EMPTY_PASS_NOT_ALLOWED');

				return false;
			}

			// Social Authenticate
			if (!empty($credentials['social']) && !empty($credentials['user_id']))
			{
				$user_id   = $credentials['user_id'];
				$passwords = $model->getSocialPasswords($user_id);
				if (!empty($passwords) && in_array($credentials['password'], $passwords))
				{
					$user               = Factory::getUser($user_id); // Bring this in line with the rest of the system
					$response->email    = $user->email;
					$response->fullname = $user->name;
					if (Factory::getApplication()->isAdmin())
					{
						$response->language = $user->getParam('admin_language');
					}
					else
					{
						$response->language = $user->getParam('language');
					}
					$response->status        = JAuthentication::STATUS_SUCCESS;
					$response->error_message = '';

					return true;
				}
				else
				{
					$response->status        = JAuthentication::STATUS_FAILURE;
					$response->error_message = Text::_('JGLOBAL_AUTH_INVALID_PASS');

					return false;
				}

			}
			// Password Authenticate
			else
			{
				// Check email
				if ($username = $model->getUsername('email', $credentials['username']))
				{
					$result = $username;
				}

				// Check phone
				elseif ($username = $model->getUsername('phone', $credentials['username']))
				{
					$result = $username;
				}
				if ($result)
				{
					JLoader::register('PlgAuthenticationJoomla', JPATH_PLUGINS . '/authentication/joomla/joomla.php', false);
					$config = PluginHelper::getPlugin('authentication', 'joomla');
					$class  = new PlgAuthenticationJoomla($this, (array) $config);

					$credentials['username'] = $result;
					$class->onUserAuthenticate($credentials, $options, $response);

					return true;
				}
				else
				{
					$response->status        = JAuthentication::STATUS_FAILURE;
					$response->error_message = Text::_('JGLOBAL_AUTH_NO_USER');

					return false;
				}
			}
		}

		return false;
	}

	/**
	 **
	 * Attach an observer object
	 *
	 * @param   object $observer An observer object to attach
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	public function attach($observer)
	{
	}
}