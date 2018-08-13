<?php
/**
 * @package    Profiles Component
 * @version    1.2.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Router\Route;

class ProfilesControllerSocial extends BaseController
{
	/**
	 * Component config
	 *
	 * @var   Registry
	 *
	 * @since 1.0.0
	 */
	protected $config = null;

	/**
	 * User ID
	 *
	 * @var   int
	 *
	 * @since 1.0.0
	 */
	protected $user_id = null;

	/**
	 * Providers
	 *
	 * @var   array
	 *
	 * @since 1.0.0
	 */
	protected $providers = null;

	/**
	 * Providers config
	 *
	 * @var   Registry
	 *
	 * @since 1.0.0
	 */
	protected $providersConfig = null;

	/**
	 * Current provider
	 *
	 * @var   array
	 *
	 * @since 1.0.0
	 */
	protected $provider = null;

	/**
	 * Current provider config
	 *
	 * @var   Registry
	 *
	 * @since 1.0.0
	 */
	protected $providerConfig = null;

	/**
	 * Redirect_uri for providers
	 *
	 * @var   string
	 *
	 * @since 1.0.0
	 */
	protected $redirect_uri = null;

	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *                        Recognized key values include 'name', 'default_task', 'model_path', and
	 *                        'view_path' (this list is not meant to be comprehensive).
	 *
	 * @since 1.0.0
	 */
	public function __construct(array $config = array())
	{
		$app = Factory::getApplication();

		$this->config = ComponentHelper::getParams('com_profiles');;

		$this->providers       = array('vk', 'facebook', 'instagram', 'odnoklassniki');
		$this->providersConfig = new Registry($this->config->get('social_providers'));

		$this->user_id = $app->input->get('user_id');

		$this->provider       = $app->input->get('provider');
		$this->providerConfig = new Registry($this->providersConfig->get($this->provider));

		$this->redirect_uri = trim(Uri::root(), '/');

		parent::__construct($config);
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string $name   The model name. Optional.
	 * @param   string $prefix The class prefix. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  BaseDatabaseModel|boolean  Model object on success; otherwise false on failure.
	 *
	 * @since 1.0.0
	 */
	public function getModel($name = '', $prefix = 'ProfilesModel', $config = array())
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/models', 'ProfilesModel');
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_users/models', 'UsersModel');

		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Method to authorization user
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function authorization()
	{
		$app      = Factory::getApplication();
		$user     = Factory::getUser();
		$user_id  = $app->input->get('user_id');
		$provider = $app->input->get('provider');

		$this->redirect_uri .= Route::_(ProfilesHelperRoute::getSocialsAuthorizationRoute($user_id, $provider));

		// Check if this my account
		if (!empty($user_id) && $user->id != $user_id)
		{
			return $this->setResponse('error', 'COM_PROFILES_ERROR_SOCIAL_NOT_YOUR_ACCOUNT');
		}
		elseif (!$user->guest)
		{
			$user_id = $user->id;
		}

		// Get social user
		$socialFunction = 'get' . $provider . 'Profile';
		if (method_exists($this, $socialFunction))
		{
			$socialProfile = $this->$socialFunction();
		}
		else
		{
			return $this->setResponse('error', 'COM_PROFILES_ERROR_SOCIAL_PROVIDER_NOT_FOUND');
		}

		// Check social profile
		if (empty($socialProfile))
		{
			return $this->setResponse('error', 'COM_PROFILES_ERROR_SOCIAL_PROVIDER_NOT_LOAD');
		}
		// Get user Social
		$social_id = $socialProfile->get('id');
		$email     = $socialProfile->get('email');
		$phone     = $socialProfile->get('phone');
		if ($userSocial = $this->getUserSocial($user_id, $provider, $social_id, $email, $phone))
		{
			if (($user->guest && $this->login($userSocial)) || !$user->guest)
			{
				$text = ($user->guest) ? 'COM_PROFILES_SOCIALS_LOGIN_SUCCESS' : 'COM_PROFILES_SOCIALS_COMPARE_SUCCESS';

				return $this->setResponse('success', $text);
			}
		}
		elseif ($user->guest && $this->registration($socialProfile))
		{
			return $this->setResponse('success', 'COM_PROFILES_SOCIALS_REGISTRATION_SUCCESS');
		}

		return $this->setResponse('error');
	}

	/**
	 * Method to login guest
	 *
	 * @param $data User social data
	 *
	 * @return bool|JException
	 *
	 * @since 1.0.0
	 */
	protected function login($data)
	{
		Factory::getSession()->set('socialConnectData', $this->provider);
		$app       = Factory::getApplication();
		$model     = $this->getModel('User');
		$user_id   = $data->user_id;
		$provider  = $this->provider;
		$social_id = $data->social_id;
		$username  = Factory::getUser($user_id)->get('username');
		$password  = $model->generateSocialPassword($user_id, $provider, $social_id);

		// Prepare login data
		$credentials = array(
			'username' => $username,
			'password' => $password,
			'social'   => true,
			'user_id'  => $data->user_id,
		);
		$options     = array(
			'silent'   => false,
			'remember' => 1
		);

		return $app->login($credentials, $options);
	}

	/**
	 * Method to registration user
	 *
	 * @param $profile Social Profile data
	 *
	 * @return bool|JException
	 *
	 * @since 1.0.0
	 */
	protected function registration($profile)
	{
		// If registration is disabled
		if (ComponentHelper::getParams('com_users')->get('allowUserRegistration') == 0)
		{
			echo Text::_('COM_PROFILES_ERROR_SOCIAL_REGISTRATION_NOT_ALLOW');

			return false;
		}

		$password    = UserHelper::genRandomPassword();
		$email       = $profile->get('email');
		$social_id   = $profile->get('id');
		$avatar      = $profile->get('avatar');
		$imagefolder = '';
		$provider    = $this->provider;

		// Prepare avatar
		if (!empty($avatar))
		{

			JLoader::register('imageFolderHelper', JPATH_PLUGINS . '/fieldtypes/ajaximage/helpers/imagefolder.php');
			$helper      = new imageFolderHelper('images/profiles');
			$imagefolder = $helper->createTemporaryFolder();

			$avatarExt = preg_replace('/\?(.?)*/', '', JFile::getExt($avatar));
			$avatarExt = str_replace('me/image', '', $avatarExt);
			$newAvatar = $imagefolder . '/' . 'avatar.';
			$newAvatar .= (!empty($avatarExt)) ? $avatarExt : 'jpg';
			$avatar    = (Factory::getStream()->copy($avatar, JPATH_ROOT . '/' . $newAvatar, null, false)) ?
				$newAvatar : '';
		}

		$model = $this->getModel('Registration', 'UsersModel');

		// Validate the user data.
		$form = $model->getForm();
		if (!$form)
		{
			echo $model->getError() . '<br />';

			return false;
		}
		$form->removeField('captcha');
		$form->removeField('personaldata');

		$data = $model->validate($form, array(
			'name'        => $profile->get('name'),
			'password1'   => $password,
			'password2'   => $password,
			'email'       => $email,
			'imagefolder' => $imagefolder,
			'avatar'      => $avatar
		));

		// Check for validation errors.
		if ($data === false)
		{
			foreach ($model->getErrors() as $error)
			{
				echo $error . '<br/>';
			}
		}

		$user_id = $model->register($data);
		if (!$user_id)
		{
			foreach ($model->getErrors() as $error)
			{
				echo $error . '<br/>';
			}

			return false;
		}

		$this->getModel('User')->addSocial($user_id, $provider, $social_id);

		return true;
	}

	/**
	 * Method to login guest
	 *
	 * @param int    $user_id   User ID
	 * @param string $provider  Social provider
	 * @param int    $social_id Social profile id
	 * @param string $email     User email
	 * @param string $phone     User Phone
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	protected function getUserSocial($user_id, $provider, $social_id, $email, $phone)
	{
		$model    = $this->getModel('User');
		$provider = $this->provider;

		if (!empty($user_id))
		{
			if (empty($social = $model->getSocial($user_id, $provider)))
			{
				$model->addSocial($user_id, $provider, $social_id);

				return $this->getUserSocial($user_id, $provider, $social_id, $email, $phone);
			}

			return $social;
		}

		if ($user_id = $model->getID('social', array('provider' => $provider, 'social_id' => $social_id)))
		{
			return $this->getUserSocial($user_id, $provider, $social_id, $email, $phone);
		}
		if ($user_id = $model->getID('email', $email))
		{
			return $this->getUserSocial($user_id, $provider, $social_id, $email, $phone);
		}
		if ($user_id = $model->getID('phone', $phone))
		{
			return $this->getUserSocial($user_id, $provider, $social_id, $email, $phone);
		}

		return false;
	}

	/**
	 * Method to get Profile form vk.com
	 *
	 * @return bool|Registry
	 *
	 * @since 1.0.0
	 */
	protected function getVkProfile()
	{
		$app    = Factory::getApplication();
		$config = $this->providerConfig;

		// Return if empty social provider config
		if (empty($config) || empty($config->get('client_id')) || empty($config->get('client_secret')))
		{
			return false;
		}

		$params = array(
			'client_id'     => $config->get('client_id'),
			'client_secret' => $config->get('client_secret'),
			'redirect_uri'  => $this->redirect_uri,
			'scope'         => 'email,contacts'
		);

		// Get code
		if (!$code = $app->input->get('code', 0, 'raw'))
		{
			$app->redirect('https://oauth.vk.com/authorize?' . http_build_query($params) . '&response_type=code');

			return false;
		}

		// Get access_token
		if (function_exists('curl_init') && $curl = curl_init())
		{
			$param = parse_url('https://oauth.vk.com/access_token?' . http_build_query($params) . '&code=' . $code);
			curl_setopt($curl, CURLOPT_URL, $param['scheme'] . '://' . $param['host'] . $param['path']);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $param['query']);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$getToken = new Registry(curl_exec($curl));
			curl_close($curl);
		}
		else
		{
			echo 'Call to undefined function curl_init() <br />';

			return false;
		}

		if (!empty($getToken->get('error')))
		{
			echo $getToken->get('error_description') . '<br />';

			return false;
		}

		// Get social profile
		$social_id  = $getToken->get('user_id');
		$email      = $getToken->get('email', $social_id . '@vk.com');
		$token      = $getToken->get('access_token');
		$params     = array(
			'uids'         => $social_id,
			'fields'       => 'uid,email,first_name,last_name,contacts,photo_big',
			'access_token' => $token,
			'v'            => '5.8'
		);
		$getProfile = new Registry(JFile::read('https://api.vk.com/method/users.get' . '?' . http_build_query($params)));

		if (!empty($getProfile->get('error')))
		{
			echo $getProfile->get('error')->error_msg . '<br />';

			return false;
		}
		$profile = new Registry($getProfile->get('response')[0]);

		$data         = new stdClass();
		$data->id     = $social_id;
		$data->name   = $profile->get('first_name') . ' ' . $profile->get('last_name');
		$data->email  = $email;
		$data->phone  = $profile->get('mobile_phone');
		$data->avatar = $profile->get('photo_big');

		return new Registry($data);
	}

	/**
	 * Method to get Profile form facebook.com
	 *
	 * @return bool|Registry
	 *
	 * @since 1.0.0
	 */
	protected function getFacebookProfile()
	{
		$app     = Factory::getApplication();
		$config  = $this->providerConfig;
		$user_id = $app->input->get('user_id', $app->input->get('amp;user_id'));

		// Return if empty social provider config
		if (empty($config) || empty($config->get('client_id')) || empty($config->get('client_secret')))
		{
			return false;
		}

		$params = array(
			'client_id'     => $config->get('client_id'),
			'client_secret' => $config->get('client_secret'),
			'redirect_uri'  => Uri::root() . ProfilesHelperRoute::getSocialsAuthorizationRoute($user_id, 'facebook'),
			'scope'         => 'email,public_profile'
		);

		// Get code
		if (!$code = $app->input->get('code', 0, 'raw'))
		{
			$app->redirect('https://www.facebook.com/v2.12/dialog/oauth?' . http_build_query($params) . '&response_type=code');

			return false;
		}

		// Get token
		$getToken = ($try = @JFile::read('https://graph.facebook.com/v2.12/oauth/access_token?' .
			http_build_query($params) . '&code=' . $code)) ? new Registry($try) : false;

		if (!$getToken)
		{
			echo Text::_('COM_PROFILES_ERROR_SOCIAL_TOKEN') . '<br />';

			return false;
		}

		// Get social profile
		$params = array(
			'fields'       => 'id,first_name,last_name,email,picture.height(320)',
			'access_token' => $getToken->get('access_token')
		);

		$profile = ($try = @JFile::read('https://graph.facebook.com/v2.12/me?' . http_build_query($params))) ?
			new Registry($try) : false;

		if (!$profile)
		{
			echo Text::_('COM_PROFILES_ERROR_SOCIAL_PROVIDER_PROFILE_NOT_FOUND') . '<br />';

			return false;
		}

		$social_id = $profile->get('id');
		$email     = $profile->get('email', $social_id . '@facebook.com');
		$avatar    = ($profile->get('picture') && !empty($profile->get('picture')->data) &&
			$profile->get('picture')->data->is_silhouette == false) ? $profile->get('picture')->data->url : '';

		$data         = new stdClass();
		$data->id     = $social_id;
		$data->name   = $profile->get('first_name') . ' ' . $profile->get('last_name');
		$data->email  = $email;
		$data->avatar = $avatar;

		return new Registry($data);
	}

	/**
	 * Method to get Profile form instagram.com
	 *
	 * @return bool|Registry
	 *
	 * @since 1.0.0
	 */
	protected function getInstagramProfile()
	{
		$app    = Factory::getApplication();
		$config = $this->providerConfig;

		// Return if empty social provider config
		if (empty($config) || empty($config->get('client_id')) || empty($config->get('client_secret')))
		{
			return false;
		}

		$params = array(
			'client_id'     => $config->get('client_id'),
			'client_secret' => $config->get('client_secret'),
			'redirect_uri'  => $this->redirect_uri,
			'scope'         => 'basic'
		);

		// Get code
		if (!$code = $app->input->get('code', 0, 'raw'))
		{
			$app->redirect('https://api.instagram.com/oauth/authorize/?' . http_build_query($params) . '&response_type=code');

			return false;
		}

		// Get profile
		if (function_exists('curl_init') && $curl = curl_init())
		{
			$param = parse_url('https://api.instagram.com/oauth/access_token?' .
				http_build_query($params) . '&grant_type=authorization_code&code=' . $code);
			curl_setopt($curl, CURLOPT_URL, $param['scheme'] . '://' . $param['host'] . $param['path']);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $param['query']);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$getProfile = new Registry(curl_exec($curl));
			curl_close($curl);
		}
		else
		{
			echo 'Call to undefined function curl_init() <br />';

			return false;
		}
		if (!empty($getProfile->get('error_type')))
		{
			echo $getProfile->get('error_message') . '<br />';

			return false;
		}

		$profile = new Registry($getProfile->get('user'));
		$avatar  = $profile->get('profile_picture');

		$data         = new stdClass();
		$data->id     = $profile->get('id');
		$data->name   = $profile->get('full_name');
		$data->email  = $profile->get('username', $profile->get('id')) . '@instagram.com';
		$data->avatar = $avatar;

		return new Registry($data);
	}

	/**
	 * Method to get Profile form ok.ru
	 *
	 * @return bool|Registry
	 *
	 * @since 1.0.0
	 */
	protected function getOdnoklassnikiProfile()
	{
		$app    = Factory::getApplication();
		$config = $this->providerConfig;

		// Return if empty social provider config
		if (empty($config) || empty($config->get('client_id')) || empty($config->get('client_secret'))
			|| empty($config->get('public_key')))
		{
			return false;
		}

		$params = array(
			'client_id'     => $config->get('client_id'),
			'client_secret' => $config->get('client_secret'),
			'redirect_uri'  => $this->redirect_uri,
			'scope'         => 'GET_EMAIL'
		);

		// Get code
		if (!$code = $app->input->get('code', 0, 'raw'))
		{
			$app->redirect('https://www.ok.ru/oauth/authorize?' . http_build_query($params) . '&response_type=code');

			return false;
		}

		// Get access_token
		if (function_exists('curl_init') && $curl = curl_init())
		{
			$param = parse_url('https://api.ok.ru/oauth/token.do?' .
				http_build_query($params) . '&grant_type=authorization_code&code=' . $code);
			curl_setopt($curl, CURLOPT_URL, $param['scheme'] . '://' . $param['host'] . $param['path']);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $param['query']);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$getToken = new Registry(curl_exec($curl));
			curl_close($curl);
		}
		else
		{
			echo 'Call to undefined function curl_init() <br />';

			return false;
		}

		if (!empty($getToken->get('error')))
		{
			echo $getToken->get('error_description') . '<br />';

			return false;
		}

		// Get Profile
		$access_token = $getToken->get('access_token');
		$public_key   = $config->get('public_key');

		$sign = 'application_key=' . $public_key;
		$sign .= 'format=json';
		$sign .= 'method=users.getCurrentUser';
		$sign .= md5($access_token . $config->get('client_secret'));

		$params = array(
			'method'          => 'users.getCurrentUser',
			'access_token'    => $access_token,
			'application_key' => $public_key,
			'format'          => 'json',
			'sig'             => md5($sign)
		);

		$getProfile = new Registry(JFile::read('https://api.ok.ru/fb.do?' . http_build_query($params)));

		if (!empty($getToken->get('error_code')))
		{
			echo $getToken->get('error_msg') . '<br />';

			return false;
		}

		$profile = $getProfile;

		$data         = new stdClass();
		$data->id     = $profile->get('uid');
		$data->name   = $profile->get('name');
		$data->email  = $profile->get('uid') . '@ok.ru';
		$data->avatar = $profile->get('pic_3', $profile->get('pic_2', $profile->get('pic_1', '')));

		return new Registry($data);
	}

	/**
	 * Method to disconnect user social
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function disconnect()
	{
		$model    = $this->getModel('User');
		$user_id  = Factory::getApplication()->input->get('user_id');
		$provider = $this->provider;

		if (empty($provider))
		{
			return $this->setResponse('error', 'COM_PROFILES_ERROR_SOCIAL_PROVIDER_NOT_FOUND');
		}
		if (empty($user_id))
		{
			return $this->setResponse('error', 'COM_PROFILES_ERROR_SOCIAL_USER_NOT_FOUND');
		}

		$delete = $model->deleteSocial($user_id, $provider);
		$status = ($delete) ? 'success' : 'error';
		$text   = ($delete) ? 'COM_PROFILES_SOCIALS_DELETE_SUCCESS' : 'COM_PROFILES_ERROR_SOCIAL_DELETE';

		return $this->setResponse($status, $text);
	}

	/**
	 * Method to set Response
	 *
	 * @param  string $status Response status
	 * @param string  $text   Response text
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	protected function setResponse($status, $text = '')
	{
		// Set no cache
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');

		if (!empty($text))
		{
			echo Text::_($text);
		}

		if ($status == 'success')
		{
			echo '<script>setTimeout(function(){ window.opener.location.reload();window.close();},1000)</script>';
		}

		Factory::getApplication()->close();

		return ($status !== 'error');
	}
}