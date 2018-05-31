<?php
/**
 * @package    Profiles Component
 * @version    1.0.10
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Application\SiteApplication;
use Joomla\Registry\Registry;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

class ProfilesModelExport extends BaseDatabaseModel
{
	/**
	 * Items
	 *
	 * @var    array
	 *
	 * @since  1.0.0
	 */
	protected $_items = null;

	/**
	 * User socials
	 *
	 * @var    array
	 * @since 1.0.0
	 */
	protected $_socials = null;

	/**
	 * Users as company
	 *
	 * @var    array
	 * @since 1.0.0
	 */
	protected $_as_company = null;

	/**
	 * Method to get csv headers
	 * @return array
	 *
	 * @since 1.0.3
	 */
	public function getCsvHeaders()
	{
		$headers = array(
			Text::_('JGRID_HEADING_ID'),
			Text::_('COM_PROFILES_PROFILE_NAME'),
			Text::_('COM_PROFILES_PROFILE_JOB_COMPANY_NAME'),
			Text::_('COM_PROFILES_PROFILE_JOB_POSITION'),
			Text::_('COM_PROFILES_PROFILE_JOB_AS_COMPANY'),
			Text::_('JTAG'),
			Text::_('COM_PROFILES_PROFILE_NOTES_NOTE'),
			Text::_('COM_PROFILES_PROFILE_NOTES_TECH'),
			Text::_('JGRID_HEADING_REGION'),
			Text::_('COM_PROFILES_PROFILE_NOTES_CITY'),
			Text::_('JGLOBAL_EMAIL') . ' (' . Text::_('COM_PROFILES_PROFILE_SITE_ACCESS') . ')',
			Text::_('COM_PROFILES_PROFILE_PHONE') . ' (' . Text::_('COM_PROFILES_PROFILE_SITE_ACCESS') . ')',
			Text::_('JGLOBAL_FIELD_SOCIAL_LABEL_VK') . ' (' . Text::_('COM_PROFILES_PROFILE_SITE_ACCESS') . ')',
			Text::_('JGLOBAL_FIELD_SOCIAL_LABEL_FB') . ' (' . Text::_('COM_PROFILES_PROFILE_SITE_ACCESS') . ')',
			Text::_('JGLOBAL_FIELD_SOCIAL_LABEL_OK') . ' (' . Text::_('COM_PROFILES_PROFILE_SITE_ACCESS') . ')',
			Text::_('JGLOBAL_EMAIL') . ' (' . Text::_('COM_PROFILES_PROFILE_CONTACTS') . ')',
			Text::_('JGLOBAL_FIELD_PHONES_LABEL') . ' (' . Text::_('COM_PROFILES_PROFILE_CONTACTS') . ')',
			Text::_('COM_PROFILES_PROFILE_SITE') . ' (' . Text::_('COM_PROFILES_PROFILE_CONTACTS') . ')',
			Text::_('JGLOBAL_FIELD_SOCIAL_LABEL_VK') . ' (' . Text::_('COM_PROFILES_PROFILE_CONTACTS') . ')',
			Text::_('JGLOBAL_FIELD_SOCIAL_LABEL_FB') . ' (' . Text::_('COM_PROFILES_PROFILE_CONTACTS') . ')',
			Text::_('JGLOBAL_FIELD_SOCIAL_LABEL_INST') . ' (' . Text::_('COM_PROFILES_PROFILE_CONTACTS') . ')',
			Text::_('JGLOBAL_FIELD_SOCIAL_LABEL_OK') . ' (' . Text::_('COM_PROFILES_PROFILE_CONTACTS') . ')',
			Text::_('COM_PROFILES_HEADING_LAST_VISIT'),
			Text::_('COM_PROFILES_HEADING_CREATED_DATE'),
			Text::_('JGLOBAL_FIELD_AJAXALIAS_LABEL'),
			Text::_('JACTION_EDIT'),
		);

		return $headers;
	}

	/**
	 * Method to get one or more records.
	 *
	 * @param   array &$pks An array of record primary keys.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function getItems($pks = array())
	{
		if (!is_array($this->_items))
		{
			try
			{
				$db    = $this->getDbo();
				$query = $db->getQuery(true)
					->select('p.*')
					->from($db->quoteName('#__profiles', 'p'));

				// Join over the phones.
				$query->select('CONCAT(phone.code, phone.number) AS user_phone')
					->join('LEFT', '#__user_phones AS phone ON phone.user_id = p.id');

				// Join over the regions.
				$query->select(array('r.id as region_id', 'r.name AS region_name'))
					->join('LEFT', '#__regions AS r ON r.id = 
					(CASE p.region WHEN ' . $db->quote('*') . ' THEN 100 ELSE p.region END)');

				// Join over the users.
				$query->select(array('user.email AS user_email', 'user.lastvisitDate as last_visit'))
					->join('LEFT', '#__users AS user ON user.id = p.id');

				// Join over the companies.
				$query->select(array('(company.id IS NOT NULL) AS job', 'company.id as job_id', 'company.name as job_name', 'employees.position as job_position'))
					->join('LEFT', '#__companies_employees AS employees ON employees.user_id = p.id AND ' .
						$db->quoteName('employees.key') . ' = ' . $db->quote(''))
					->join('LEFT', '#__companies AS company ON company.id = employees.company_id AND company.state = 1');

				// Filter by id
				if (!empty($pks))
				{
					$query->where('p.id IN (' . implode(',', $pks) . ')');
				}

				// Group and ordering
				$query->group(array('p.id'))
					->order('p.id ASC');
				$db->setQuery($query);

				$items      = $db->loadObjectList('id');
				$socials    = $this->getSocials(array_keys($items));
				$as_company = $this->getAsCompany(array_keys($items));

				foreach ($items as $id => &$item)
				{
					$item->socials    = (isset($socials[$item->id])) ? $socials[$item->id] : array();
					$item->as_company = (in_array($item->id, $as_company));
					$item->notes      = new Registry($item->notes);
					$item->contacts   = new Registry($item->contacts);
					$item->tags       = new TagsHelper;
					$item->tags->getItemTags('com_profiles.profile', $item->id);
				}

				return $items;
			}
			catch (Exception $e)
			{
				$this->setError($e);
				$this->_items = false;
			}

		}

		return $this->_items;
	}

	/**
	 * Method to get an array of data items socials.
	 *
	 * @param array $pks items id
	 *
	 * @return mixed An array of data items on success, false on failure.
	 *
	 * @since 1.0.0
	 */
	public function getSocials($pks = array())
	{
		if (!is_array($this->_socials))
		{
			$socials = array();
			if (!empty($pks))
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select(array('user_id', 'provider', 'social_id'))
					->from('#__user_socials')
					->where($db->quoteName('user_id') . ' IN (' . implode(',', $pks) . ')');
				$db->setQuery($query);
				$objects = $db->loadObjectList();
				foreach ($objects as $object)
				{
					if (!isset($socials[$object->user_id]))
					{
						$socials[$object->user_id] = array();
					}
					$socials[$object->user_id][$object->provider] = $object->social_id;
				}
			}
			$this->_socials = $socials;
		}

		return $this->_socials;
	}

	/**
	 * Method to get an array of data items as company.
	 *
	 * @param array $pks items id
	 *
	 * @return mixed An array of data items on success, false on failure.
	 *
	 * @since 1.0.0
	 */
	public function getAsCompany($pks = array())
	{
		if (!is_array($this->_as_company))
		{
			$as_company = array();
			if (!empty($pks))
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('user_id')
					->from($db->quoteName('#__companies_employees', 'employees'))
					->where($db->quoteName('employees.user_id') . ' IN (' . implode(',', $pks) . ')')
					->where('employees.as_company = ' . 1)
					->where($db->quoteName('employees.key') . ' = ' . $db->quote(''));
				$db->setQuery($query);

				$as_company = $db->loadColumn();

			}
			$this->_as_company = $as_company;
		}

		return $this->_as_company;
	}

	/**
	 * Method to get one or more records.
	 *
	 * @param   array &$pks An array of record primary keys.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function getCsvItems($pks = array())
	{
		$rows = array();
		if ($items = $this->getItems($pks))
		{
			JLoader::register('ProfilesHelperRoute', JPATH_SITE . '/components/com_profiles/helpers/route.php');
			$siteRouter = SiteApplication::getRouter();

			foreach ($items as $item)
			{
				$row                 = array();
				$row['id']           = $item->id;
				$row['name']         = $item->name;
				$row['job_name']     = $item->job_name;
				$row['job_position'] = $item->job_position;
				$row['as_company']   = ($item->as_company) ? Text::_('JYES') : '';

				$tags = array();
				if (!empty($item->tags->itemTags))
				{
					foreach ($item->tags->itemTags as $tag)
					{
						$tags[] = $tag->title;
					}
				}
				$row['tags']   = implode(', ', $tags);
				$row['note']   = $item->notes->get('note', '');
				$row['tech']   = $item->notes->get('tech', '');
				$row['region'] = '[' . $item->region . '] ' . $item->region_name;
				$row['city']   = $item->notes->get('city', '');

				$row['access_email'] = $item->user_email;
				$row['access_phone'] = $item->user_phone;

				$row['access_vk']            = (!empty($item->socials['vk'])) ?
					'https://vk.com/id' . $item->socials['vk'] : '';
				$row['access_facebook']      = (!empty($item->socials['facebook'])) ?
					'https://www.facebook.com/' . $item->socials['facebook'] : '';
				$row['access_odnoklassniki'] = (!empty($item->socials['odnoklassniki'])) ?
					'https://ok.ru/profile/' . $item->socials['odnoklassniki'] : '';

				$row['contacts_email'] = $item->contacts->get('email', '');

				$phones = ArrayHelper::fromObject($item->contacts->get('phones', ''));

				$row['contacts_phones'] = array();
				foreach ($phones as $phone)
				{
					$row['contacts_phones'][] = $phone['code'] . $phone['number'];
				}
				$row['contacts_phones'] = implode(', ', $row['contacts_phones']);

				$row['contacts_site'] = $item->contacts->get('site', '');

				$row['contacts_vk'] = ($item->contacts->get('vk', '')) ?
					'https://vk.com/' . $item->contacts->get('vk') : '';

				$row['contacts_facebook'] = ($item->contacts->get('facebook', '')) ?
					'https://www.facebook.com/' . $item->contacts->get('facebook') : '';

				$row['contacts_instagram'] = ($item->contacts->get('instagram', '')) ?
					'https://instagram.com/' . $item->contacts->get('instagram') : '';

				$row['contacts_odnoklassniki'] = ($item->contacts->get('odnoklassniki', '')) ?
					'https://ok.ru/' . $item->contacts->get('odnoklassniki') : '';

				$row['last_visit'] = ($item->last_visit > 0) ?
					HTMLHelper::_('date', $item->last_visit, Text::_('DATE_FORMAT_LC2')) :
					Text::_('JNEVER');

				$row['created'] = ($item->created > 0) ?
					HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC1')) :
					'-';

				$link        = $siteRouter->build(ProfilesHelperRoute::getProfileRoute($item->id))->toString();
				$row['link'] = trim(Uri::root(), '/') . str_replace('administrator/', '', $link);

				$row['edit'] = trim(Uri::root(), '/') . Route::_('index.php?option=com_users&task=user.edit&id=' . $item->id);

				$rows[] = $row;
			}
		}

		return $rows;
	}
}
