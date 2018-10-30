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

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

class plgSystemProfiles extends CMSPlugin
{
	/**
	 * Redirect from com_users to com_profiles
	 *
	 * @return  void
	 *
	 * @since 1.5.0
	 */
	public function onAfterRoute()
	{
		$app       = Factory::getApplication();
		$component = $app->input->get('option');
		$view      = $app->input->get('view');
		$id        = $app->input->get('id');
		$task      = $app->input->get('task');
		$redirect  = false;

		// Set admin redirects
		if ($app->isAdmin())
		{
			if ($component == 'com_users')
			{
				if (empty($view) && empty($task))
				{
					$view = 'users';
				}

				// Users list redirect
				if ($view == 'users')
				{
					$redirect = 'index.php?option=com_profiles&view=profiles';
					if (!empty($app->input->get('layout')))
					{
						$redirect .= '&layout=' . $app->input->get('layout');
					}
					if (!empty($app->input->get('tmpl')))
					{
						$redirect .= '&tmpl=' . $app->input->get('tmpl');
					}
					if (!empty($app->input->get('required')))
					{
						$redirect .= '&required=' . $app->input->get('required');
					}
					if (!empty($app->input->get('field')))
					{
						$redirect .= '&field=' . $app->input->get('field');
					}
					if (!empty($app->input->get('ismoo')))
					{
						$redirect .= '&ismoo=' . $app->input->get('ismoo');
					}
				}

				// User redirect
				if ($view == 'user')
				{
					$redirect = 'index.php?option=com_profiles&task=profile.edit&id=' . $id;
				}

				// Create user redirect
				if ($task == 'user.add')
				{
					$redirect = 'index.php?option=com_profiles&task=profile.add';
				}

				// Edit user redirect
				if ($task == 'user.edit')
				{
					$redirect = 'index.php?option=com_profiles&task=profile.edit&id=' . $id;
				}
			}
		}
		// Redirect
		if ($redirect)
		{
			$app->redirect($redirect);
		}
	}

	/**
	 * Change users toolbar
	 *
	 * @return  void
	 *
	 * @since 1.5.0
	 */
	public function onBeforeRender()
	{
		$app               = Factory::getApplication();
		$component         = $app->input->get('option');
		$view              = $app->input->get('view');
		$isNew             = ($app->input->get('id', 0, 'int') > 0);
		$loadLanguage      = array('levels', 'level', 'groups', 'group');
		$addOptionsButtons = false;

		if ($app->isAdmin() && $component == 'com_users')
		{
			// Load language
			if (in_array($view, $loadLanguage))
			{
				$language = Factory::getLanguage();
				$language->load('com_profiles', JPATH_ADMINISTRATOR, $language->getTag(), true);
			}

			// Groups view
			if ($view == 'groups')
			{
				$addOptionsButtons = true;
				ToolbarHelper::title(Text::_('COM_PROFILES') . ': ' . Text::_('COM_PROFILES_USERS_GROUPS'),
					'users');
			}

			// Group view
			if ($view == 'group')
			{
				$title = ($isNew) ? Text::_('COM_PROFILES_USERS_GROUPS_ADD') : Text::_('COM_PROFILES_USERS_GROUPS_EDIT');
				ToolbarHelper::title(Text::_('COM_PROFILES') . ': ' . $title, 'users');
			}

			// Levels view
			if ($view == 'levels')
			{
				$addOptionsButtons = true;
				ToolbarHelper::title(Text::_('COM_PROFILES') . ': ' . Text::_('COM_PROFILES_USERS_LEVELS'),
					'users');
			}

			// Group view
			if ($view == 'level')
			{
				$title = ($isNew) ? Text::_('COM_PROFILES_USERS_LEVELS_ADD') : Text::_('COM_PROFILES_USERS_LEVELS_EDIT');
				ToolbarHelper::title(Text::_('COM_PROFILES') . ': ' . $title, 'users');
			}

			// Set options buttons
			if ($addOptionsButtons)
			{
				HTMLHelper::_('stylesheet', 'media/com_profiles/css/admin-general.min.css', array('version' => 'auto'));

				$return = urlencode(base64_encode(Uri::getInstance()->toString()));
				$link   = 'index.php?option=com_config&view=component&return=' . $return . '&component=';

				ToolbarHelper::link($link . 'com_profiles', Text::_('COM_PROFILES_CONFIG_PROFILES'), 'options-profiles');
				ToolbarHelper::link($link . 'com_users', Text::_('COM_PROFILES_CONFIG_USERS'), 'options-users');
			}
		}
	}

	/**
	 * Change sidebar on admin com_users
	 *
	 * @return  void
	 *
	 * @since 1.5.0
	 */
	public function onAfterRender()
	{
		$app       = Factory::getApplication();
		$component = $app->input->get('option');
		$view      = $app->input->get('view');

		if ($app->isAdmin() && $component == 'com_users' && ($view == 'levels' || $view == 'groups'))
		{
			JLoader::register('ProfilesHelper', JPATH_ADMINISTRATOR . '/components/com_profiles/helpers/profiles.php');

			$body = str_replace(JHtmlSidebar::render(), ProfilesHelper::renderSideBar($view), JResponse::getBody());
			JResponse::setBody($body);
		}
	}
}