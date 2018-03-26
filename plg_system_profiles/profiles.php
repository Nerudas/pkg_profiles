<?php
/**
 * @package    System - Profiles Plugin
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

class plgSystemProfiles extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since 1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Listener for the `onAfterInitialise` event
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	function onAfterInitialise()
	{
		$app       = Factory::getApplication();
		$component = $app->input->get('option', '');
		$view      = $app->input->get('view', '');

		// Set ordering on admin users view
		if ($app->isAdmin() && $component == 'com_users' && $view == 'users')
		{
			$context = 'com_users.users.default.list';
			$list    = $app->getUserStateFromRequest($context, 'list', array(), 'array');
			if (empty($list))
			{
				$list['fullordering'] = 'a.registerDate DESC';
				$list['limit']        = 25;
				$app->setUserState($context, $list);
			}
		}

		// Load languages files
		$language = Factory::getLanguage();
		$path     = ($app->isSite()) ? JPATH_SITE : JPATH_ADMINISTRATOR;
		$language->load('com_profiles', $path, $language->getTag(), true);

	}

	/**
	 * Listener for the `onAfterRoute` event
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	public function onAfterRoute()
	{
		$app       = Factory::getApplication();
		$component = $app->input->get('option');
		$view      = $app->input->get('view');
		$layout    = $app->input->get('layout', '');

		if ($app->isSite() && $component == 'com_users' && $view == 'profile')
		{
			$uri        = Uri::getInstance();
			$currentURL = $uri->toString(array('path'));
			$menus      = $app->getMenu('site');
			$com_users  = ComponentHelper::getComponent('com_users');
			$items      = $menus->getItems('component_id', $com_users->id);
			foreach ($items as $item)
			{
				$query           = (!empty($item->query)) ? $item->query : array();
				$query['layout'] = (isset($query['layout'])) ? $query['layout'] : '';

				if (!empty($query['view']) && $query['view'] == 'profile' && $query['layout'] == $layout)
				{
					$url = Route::_('index.php?Itemid=' . $item->id);
					if ($currentURL != $url)
					{
						$app->redirect($url);
					}
				}
			}
		}
	}

	/**
	 * Listener for the `onAfterRender` event
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	public function onBeforeRender()
	{
		$app       = Factory::getApplication();
		$component = $app->input->get('option', '');
		$view      = $app->input->get('view', '');

		// Add style for admin user view
		if ($app->isAdmin() && $component == 'com_users' && $view == 'user')
		{
			HTMLHelper::_('jquery.framework');
			HTMLHelper::stylesheet('media/com_profiles/css/admin-user.min.css', array('version' => 'auto'));
			HTMLHelper::script('media/com_profiles/js/admin-user.min.js', array('version' => 'auto'));
			Factory::getDocument()->addScriptOptions('admin-user.params', array(
				'profileText' => Text::_('COM_PROFILES_PROFILE_ABOUT')));
		}

		if ($app->isSite() && $component == 'com_users' && $view == 'registration')
		{
			HTMLHelper::_('jquery.framework');
			HTMLHelper::script('media/com_profiles/js/registration.min.js', array('version' => 'auto'));
		}
	}

	/**
	 * Change forms
	 *
	 * @param  Form  $form The form to be altered.
	 * @param  mixed $data The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since 1.0.0
	 */
	function onContentPrepareForm($form, $data)
	{
		$app       = Factory::getApplication();
		$user      = Factory::getUser();
		$component = $app->input->get('option', '');
		$formName  = $form->getName();

		if ($component == 'com_users')
		{
			$config = ComponentHelper::getParams('com_profiles');

			if ($app->isSite())
			{
				$path = JPATH_ADMINISTRATOR . '/components/com_profiles/models';
				Form::addFieldPath($path . '/fields');
				Form::addRulePath($path . '/rules');
			}

			$path = ($app->isSite()) ? JPATH_SITE : JPATH_ADMINISTRATOR;
			$path .= '/components/com_profiles/models';
			Form::addFieldPath($path . '/fields');
			Form::addRulePath($path . '/rules');
			Form::addFormPath($path . '/forms');

			$user_id = 0;
			if (is_array($data) && !empty($data['id']))
			{
				$user_id = $data['id'];
			}
			elseif (is_object($data) && !empty($data->id))
			{
				$user_id = $data->id;
			}

			// Change admin com_users.user form
			if ($app->isAdmin() && $formName == 'com_users.user')
			{
				// Load form
				$form->reset(true);
				$form->loadFile('profile', true);

				// Set password required in new user
				if (empty($user_id))
				{
					$form->setFieldAttribute('password', 'required', 'true');
					$form->setFieldAttribute('password2', 'required', 'true');
				}

				// Set user_id for fields
				$form->setFieldAttribute('socials', 'user_id', $user_id);

				// Set Tags parents
				if ($config->get('profile_tags'))
				{
					$form->setFieldAttribute('tags', 'parents', implode(',', $config->get('profile_tags')));
				}

				// Set Check alias link
				$form->setFieldAttribute('alias', 'checkurl',
					Uri::base(true) . '/index.php?option=com_profiles&task=profile.checkAlias');

				// Set update images links
				$saveurl = Uri::base(true) . '/index.php?option=com_profiles&task=profile.updateImages&id='
					. $user_id . '&field=';
				$form->setFieldAttribute('avatar', 'saveurl', $saveurl . 'avatar');
				$form->setFieldAttribute('header', 'saveurl', $saveurl . 'header');

				// Set return
				if ($return = $app->input->get('return', false, 'base64'))
				{
					$form->setValue('return', '', $return);
				}
			}

			// Change site com_users.login form
			if ($app->isSite() && $formName == 'com_users.login')
			{
				if (!$user->guest)
				{
					$redirect = (Factory::getSession()->get('afterLoginReturn')) ?
						base64_decode(Factory::getSession()->get('afterLoginReturn')) :
						Route::_('index.php?option=com_users&view=profile');
					Factory::getSession()->set('afterLoginReturn', '');

					$app->redirect($redirect);
				}
				elseif ($user->guest && !empty($app->input->getBase64('return')))
				{
					$return = $app->input->getBase64('return', '');
					Factory::getSession()->set('afterLoginReturn', $return);
				}
				$form->reset(true);
				$form->loadFile('login', true);

			}

			// Change site com_users.registration form
			if ($app->isSite() && $formName == 'com_users.registration')
			{
				$form->reset(true);
				$form->loadFile('registration', true);
			}

			// Change site com_users.reset_request form
			if ($app->isSite() && $formName == 'com_users.reset_request')
			{
				$form->setFieldAttribute('email', 'label', 'JGLOBAL_EMAIL');
			}

			// Change site com_users.reset_confirm form
			if ($app->isSite() && $formName == 'com_users.reset_confirm')
			{
				$form->setFieldAttribute('username', 'label', 'JGLOBAL_EMAIL');
			}

			// Change site com_users.profile form
			if ($app->isSite() && $formName == 'com_users.profile')
			{
				$form->reset(true);
				$form->loadFile('profile', true);

				// Set readonly for email
				if (!ComponentHelper::getParams('com_users')->get('change_login_name'))
				{
					$form->setFieldAttribute('email', 'readonly', 'true');
				}

				// Set Check alias link
				$form->setFieldAttribute('alias', 'checkurl',
					Uri::base(true) . '/index.php?option=com_profiles&task=profile.checkAlias');

				// Set update images links
				$saveurl = Uri::base(true) . '/index.php?option=com_profiles&task=profile.updateImages&id='
					. $user_id . '&field=';
				$form->setFieldAttribute('avatar', 'saveurl', $saveurl . 'avatar');
				$form->setFieldAttribute('header', 'saveurl', $saveurl . 'header');

				// Set Tags parents
				if ($config->get('profile_tags'))
				{
					$form->setFieldAttribute('tags', 'parents', implode(',', $config->get('profile_tags')));
				}
			}
		}

		return true;
	}

	/**
	 * Runs on content preparation
	 *
	 * @param   string $context The context for the data
	 * @param   object $data    An object containing the data for the form.
	 *
	 * @return  boolean
	 *
	 * @since 1.0.0
	 */
	public function onContentPrepareData($context, $data)
	{
		if (!in_array($context, array('com_users.profile', 'com_users.user', 'com_users.registration', 'com_admin.profile')))
		{
			return true;
		}
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/models', 'ProfilesModel');
		$userModel    = BaseDatabaseModel::getInstance('User', 'ProfilesModel', array('ignore_request' => false));
		$profileModel = BaseDatabaseModel::getInstance('Profile', 'ProfilesModel', array('ignore_request' => false));

		if (is_object($data))
		{
			$user_id = 0;
			if (is_array($data) && !empty($data['id']))
			{
				$user_id = $data['id'];
			}
			elseif (is_object($data) && !empty($data->id))
			{
				$user_id = $data->id;
			}

			// Get Phone
			if ($phone = $userModel->getPhone($user_id))
			{
				$data->phone = $userModel->preparePhone($phone, 'get');
			}

			$data->socials = $userModel->getSocial($user_id);

			// Get Profile
			if ($profile = $profileModel->getItem($user_id))
			{
				$profile = ArrayHelper::fromObject($profile, false);

				foreach ($profile as $key => $value)
				{
					if (empty($data->$key) || $key == 'tags')
					{
						$data->$key = $value;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Change forms data
	 *
	 * @param  Form  $form The form to be altered.
	 * @param  mixed $data The associated data for the form.
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	function onUserBeforeDataValidation($form, &$data)
	{
		$app      = Factory::getApplication();
		$formName = $form->getName();

		if (!in_array($formName, array('com_users.profile', 'com_users.user', 'com_users.registration', 'com_admin.profile')))
		{
			return true;
		}

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/models', 'ProfilesModel');
		$userModel = BaseDatabaseModel::getInstance('User', 'ProfilesModel', array('ignore_request' => false));

		$user_id = (!empty($data['id'])) ? $data['id'] : 0;
		$isBot   = ($formName == 'com_users.registration' &&
			(!empty($data['username']) || !empty($data['email1']) || !empty($data['email2'])));
		// Set user name;
		if (in_array($formName, array('com_users.user', 'com_users.registration', 'com_users.profile')))
		{
			$data['username'] = (!$isBot) ? $data['email'] : '';
			$form->setField(new SimpleXMLElement('<field name="username" type="hidden"/>'));
		}

		// Bot check
		if ($isBot)
		{
			JError::raiseWarning(100, Text::_('COM_PROFILES_ERROR_BOT'));

			return false;
		}

		if (!empty($user_id))
		{
			$form->setFieldAttribute('password', 'required', 'false');
			$form->setFieldAttribute('password2', 'required', 'false');
		}

		// Set registration data
		if ($formName == 'com_users.registration')
		{
			// Register as
			$register_as = (!empty($data['register_as'])) ? $data['register_as'] : 'user';
			if ($register_as == 'company')
			{
				$form->setFieldAttribute('name', 'required', 'false');
				$form->setFieldAttribute('company_name', 'required', 'true');
				$form->setFieldAttribute('company_position', 'required', 'true');
				if (!empty($data['company_name']) && !empty($data['company_position']))
				{
					$data['name'] = $data['company_position'] . ' ' . $data['company_name'];
				}
			}
			else
			{
				$form->setFieldAttribute('name', 'required', 'true');
				$form->setFieldAttribute('company_name', 'required', 'false');
				$form->setFieldAttribute('company_position', 'required', 'false');
			}

			$data['email1'] = $data['email'];
			$data['email2'] = $data['email'];
			$form->setField(new SimpleXMLElement('<field name="email1" type="hidden"/>'));
			$form->setField(new SimpleXMLElement('<field name="email2" type="hidden"/>'));
			$form->setField(new SimpleXMLElement('<field name="imagefolder" type="hidden"/>'));
			$form->setField(new SimpleXMLElement('<field name="avatar" type="hidden"/>'));
			$form->setField(new SimpleXMLElement('<field name="region" type="hidden"/>'), 'params');
		}

		// Set Profile data
		if ($formName == 'com_users.profile')
		{
			$data['email1'] = $data['email'];
			$data['email2'] = $data['email'];
			$form->setField(new SimpleXMLElement('<field name="email1" type="hidden"/>'));
			$form->setField(new SimpleXMLElement('<field name="email2" type="hidden"/>'));
		}

		$data['params'] = (!empty($data['params'])) ? $data['params'] : array();
		if (empty($data['params']['region']))
		{
			$data['params']['region'] = $app->input->cookie->get('region');
		}
		if (empty($data['region']))
		{
			$data['region'] = $data['params']['region'];
		}

		if (!empty($data['phone']))
		{
			$data['phone'] = $userModel->preparePhone($data['phone'], 'save');
		}

		$form->bind($data);

		return true;
	}

	/**
	 * Saves user  data
	 *
	 * @param   array   $data   entered user data
	 * @param   boolean $isNew  true if this is a new user
	 * @param   boolean $result true if saving the user worked
	 * @param   string  $error  error message
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function onUserAfterSave($data, $isNew, $result, $error)
	{
		if ($result)
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/models', 'ProfilesModel');
			$userModel    = BaseDatabaseModel::getInstance('User', 'ProfilesModel', array('ignore_request' => false));
			$profileModel = BaseDatabaseModel::getInstance('Profile', 'ProfilesModel', array('ignore_request' => false));

			$userId = ArrayHelper::getValue($data, 'id', 0, 'int');

			// Save Phone
			$userModel->deletePhone($userId);
			if (!empty($data['phone']))
			{
				$userModel->addPhone($userId, $data['phone']);
			}


			// Registration as company
			if ($isNew && !empty($data['register_as']) && $data['register_as'] == 'company')
			{
				$data['job']                 = array();
				$data['job']['company_name'] = $data['company_name'];
				$data['job']['position']     = $data['company_position'];
				$data['job']['as_company']   = 1;

			}
			// Save profile
			$profileModel->save($data);

			// Login after registration
			$app  = Factory::getApplication();
			$user = Factory::getUser();
			if ($app->isSite() && $isNew && $user->guest)
			{
				$credentials = array(
					'username' => $data['username'],
					'password' => $data['password1'],
					'noPlugin' => true
				);
				$options     = array(
					'remember' => 1
				);
				$app->login($credentials, $options);
			}
		}

		return $result;
	}

	/**
	 * Remove all user information for the given user ID
	 *
	 * Method is called after user data is deleted from the database
	 *
	 * @param   array   $user    Holds the user data
	 * @param   boolean $success True if user was succesfully stored in the database
	 * @param   string  $msg     Message
	 *
	 * @return  boolean
	 *
	 * @since 1.0.0
	 */
	public function onUserAfterDelete($user, $success, $msg)
	{
		if (!$success)
		{
			return false;
		}

		$userId = ArrayHelper::getValue($user, 'id', 0, 'int');

		if (!empty($userId))
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/models', 'ProfilesModel');
			$userModel    = BaseDatabaseModel::getInstance('User', 'ProfilesModel', array('ignore_request' => false));
			$profileModel = BaseDatabaseModel::getInstance('Profile', 'ProfilesModel', array('ignore_request' => false));

			// Delete phone
			$userModel->deletePhone($userId);

			// Delete socials
			$userModel->deleteSocial($userId);

			// Delete jobs
			$userModel->deleteJobs($userId);

			// Delete Profile
			$profileModel->delete($userId);
		}

		return true;
	}

	/**
	 * Set allays remember my
	 *
	 * @param   array $options Array holding options
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function onUserAfterLogin($options)
	{
		JLoader::register('PlgAuthenticationCookie', JPATH_PLUGINS . '/authentication/joomla/joomla.php', false);
		$config = PluginHelper::getPlugin('authentication', 'cookie');
		$class  = new PlgAuthenticationCookie($this, (array) $config);

		$options['remember'] = 1;

		return $class->onUserAfterLogin($options);
	}

	/**
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