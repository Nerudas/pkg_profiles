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
				if (empty($view))
				{
					$view = 'users';
				}

				// Users list redirect
				if ($view == 'users')
				{
					$redirect = 'index.php?option=com_profiles&view=profiles';
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
}