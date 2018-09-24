<?php
/**
 * @package    Profiles - Profile Module
 * @version    1.3.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class modProfilesProfileHelper
{
	/**
	 * Module params
	 *
	 * @var    Registry
	 *
	 * @since 1.0.0
	 */
	public $params = null;

	/**
	 * Method to instantiate the Profiles Profile Module Helper
	 *
	 * @param Registry $params Module params
	 *
	 * @since 1.0.0
	 */
	public function __construct($params = null)
	{
		$this->params = (!empty($params)) ? $params : new Registry();
	}

	/**
	 * Method to get Profile
	 *
	 * @param null $pk
	 *
	 * @return Registry
	 *
	 * @since 1.0.0
	 */
	public function getProfile($pk = null)
	{
		$pk      = (!empty($pk)) ? $pk : $this->params->get('profile_id', '');
		$user    = (!empty($pk)) ? Factory::getUser($pk) : Factory::getUser();
		$profile = (!empty($user->id)) ? $this->getUserProfile($user->id) : $this->getGuestProfile();
		$avatar  = (!empty($profile->avatar) && JFile::exists(JPATH_ROOT . '/' . $profile->avatar)) ?
			$profile->avatar : 'media/com_profiles/images/no-avatar.jpg';
		$header  = (!empty($profile->header) && JFile::exists(JPATH_ROOT . '/' . $profile->header)) ?
			$profile->header : 'media/com_profiles/images/no-header.jpg';

		$profile->avatar   = Uri::root(true) . '/' . $avatar;
		$profile->header   = Uri::root(true) . '/' . $header;
		$profile->link     = Route::_(ProfilesHelperRoute::getProfileRoute($profile->id));
		$profile->editLink = (!$user->guest && $user->id == $profile->id) ?
			Route::_('index.php?option=com_users&view=profile&layout=edit') : '';
		$profile->job_link = ($profile->job) ? Route::_(CompaniesHelperRoute::getCompanyRoute($profile->job_id)) : false;

		$profile->job_logo = (!empty($profile->job_logo) && JFile::exists(JPATH_ROOT . '/' . $profile->job_logo)) ?
			Uri::root(true) . '/' . $profile->job_logo : false;

		return new Registry($profile);
	}

	/**
	 * Method to get User Profile Object
	 *
	 * @param int $pk user_id
	 *
	 * @return object
	 *
	 * @since 1.0.0
	 */
	protected function getUserProfile($pk)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(array('p.id', 'p.name', 'p.avatar', 'p.header', 'p.status'))
			->from($db->quoteName('#__profiles', 'p'))
			->where('p.id = ' . $pk);

		// Join over the sessions.
		$offline      = (int) ComponentHelper::getParams('com_profiles')->get('offline_time', 5) * 60;
		$offline_time = Factory::getDate()->toUnix() - $offline;
		$query->select('(session.time IS NOT NULL) AS online')
			->join('LEFT', '#__session AS session ON session.userid = p.id AND session.time > ' . $offline_time);

		// Join over the companies.
		$query->select(array('(company.id IS NOT NULL) AS job', 'company.id as job_id', 'company.name as job_name', 'company.logo as job_logo', 'employees.position'))
			->join('LEFT', '#__companies_employees AS employees ON employees.user_id = p.id AND ' .
				$db->quoteName('employees.key') . ' = ' . $db->quote(''))
			->join('LEFT', '#__companies AS company ON company.id = employees.company_id AND company.state = 1');

		$db->setQuery($query);
		$profile = $db->loadObject();

		return (!empty($profile)) ? $profile : $this->getGuestProfile();
	}

	/**
	 * Method to get Guest Profile Object
	 *
	 * @return object
	 *
	 * @since 1.0.0
	 */
	protected function getGuestProfile()
	{
		$profile         = new stdClass();
		$profile->guest  = true;
		$profile->id     = 0;
		$profile->name   = Text::_('COM_PROFILES_GUEST');
		$profile->avatar = '';
		$profile->header = '';
		$profile->status = '';
		$profile->online = 0;
		$profile->job    = 0;

		return $profile;
	}
}