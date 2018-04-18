<?php
/**
 * @package    Profiles Component
 * @version    1.0.6
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Registry\Registry;

class ProfilesModelProfiles extends ListModel
{
	/**
	 * User socials
	 *
	 * @var    array
	 * @since 1.0.0
	 */
	protected $_socials = null;

	/**
	 * User groups
	 *
	 * @var    array
	 * @since 1.0.0
	 */
	protected $_usergroups = null;

	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @since 1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'p.id', 'id',
				'p.name', 'name',
				'p.alias', 'alias',
				'p.about', 'about',
				'p.status', 'status',
				'p.contacts', 'contacts',
				'p.avatar', 'avatar',
				'p.header', 'header',
				'p.created', 'created',
				'p.modified', 'modified',
				'p.attribs', 'attribs',
				'p.metakey', 'metakey',
				'p.metadesc', 'metadesc',
				'p.hits', 'hits',
				'region', 'p.region', 'region_name',
				'p.metadata', 'metadata',
				'p.tags_search', 'tags_search',
				'p.tags_map', 'tags_map',
				'last_visit'
			);
		}
		parent::__construct($config);
	}


	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string $ordering  An optional ordering field.
	 * @param   string $direction An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$state = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '');
		$this->setState('filter.state', $state);

		$region = $this->getUserStateFromRequest($this->context . '.filter.region', 'filter_region', '');
		$this->setState('filter.region', $region);

		$avatar = $this->getUserStateFromRequest($this->context . '.filter.avatar', 'filter_avatar', '');
		$this->setState('filter.avatar', $avatar);

		$social = $this->getUserStateFromRequest($this->context . '.filter.social', 'filter_social', '');
		$this->setState('filter.social', $social);

		$usergroup = $this->getUserStateFromRequest($this->context . '.filter.usergroup', 'filter_usergroup', '');
		$this->setState('filter.usergroup', $usergroup);

		$usergroup = $this->getUserStateFromRequest($this->context . '.filter.online', 'filter_online', '');
		$this->setState('filter.online', $usergroup);

		$tags = $this->getUserStateFromRequest($this->context . '.filter.tags', 'filter_tags', '');
		$this->setState('filter.tags', $tags);

		// List state information.
		$ordering  = empty($ordering) ? 'last_visit' : $ordering;
		$direction = empty($direction) ? 'desc' : $direction;
		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string $id A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since 1.0.0
	 */
	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.region');
		$id .= ':' . $this->getState('filter.avatar');
		$id .= ':' . $this->getState('filter.social');
		$id .= ':' . $this->getState('filter.usergroup');
		$id .= ':' . $this->getState('filter.online');
		$id .= ':' . serialize($this->getState('filter.tags'));

		return parent::getStoreId($id);
	}


	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since 1.0.0
	 */
	protected function getListQuery()
	{
		$db        = $this->getDbo();
		$query     = $db->getQuery(true);
		$userModel = BaseDatabaseModel::getInstance('User', 'ProfilesModel');
		$component = ComponentHelper::getParams('com_profiles');

		$query->select('p.*')
			->from($db->quoteName('#__profiles', 'p'));

		// Join over the phones.
		$query->select('CONCAT(phone.code, phone.number) AS user_phone')
			->join('LEFT', '#__user_phones AS phone ON phone.user_id = p.id');

		// Join over the regions.
		$query->select(array('r.id as region_id', 'r.name AS region_name'))
			->join('LEFT', '#__regions AS r ON r.id = 
					(CASE p.region WHEN ' . $db->quote('*') . ' THEN 100 ELSE p.region END)');

		// Join over the sessions.
		$offline      = (int) $component->get('offline_time', 5) * 60;
		$offline_time = Factory::getDate()->toUnix() - $offline;
		$query->select('(session.time IS NOT NULL) AS online')
			->join('LEFT', '#__session AS session ON session.userid = p.id AND session.time > ' . $offline_time);

		// Join over the users.
		$query->select(array('user.email AS user_email', 'user.lastvisitDate as last_visit'))
			->join('LEFT', '#__users AS user ON user.id = p.id');

		// Join over the companies.
		$query->select(array('(company.id IS NOT NULL) AS job', 'company.id as job_id', 'company.name as job_name', 'company.logo as job_logo', 'employees.position'))
			->join('LEFT', '#__companies_employees AS employees ON employees.user_id = p.id AND ' .
				$db->quoteName('employees.key') . ' = ' . $db->quote(''))
			->join('LEFT', '#__companies AS company ON company.id = employees.company_id AND company.state = 1');

		// Filter by state
		$state = $this->getState('filter.state');
		if ($state == 'blocked')
		{
			$query->where('user.block = 1');
		}
		elseif ($state == 'not_activated')
		{
			$query->where($query->length('user.activation') . ' > 1');
		}
		else
		{
			$query->where('user.block = 0')
				->where('user.activation IN (' . $db->quote('') . ', ' . $db->quote('0') . ')');
		}
		if ($state == 'in_work')
		{
			$query->where('p.in_work = 1');
		}

		// Filter by regions
		$region = $this->getState('filter.region');
		if (is_numeric($region))
		{
			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_nerudas/models');
			$regionModel = JModelLegacy::getInstance('regions', 'NerudasModel');
			$regions     = $regionModel->getRegionsIds($region);
			$regions[]   = $db->quote('*');
			$regions[]   = $regionModel->getRegion($region)->parent;
			$regions     = array_unique($regions);
			$query->where($db->quoteName('p.region') . ' IN (' . implode(',', $regions) . ')');
		}

		// Filter by tags.
		$tags = $this->getState('filter.tags');
		if (is_array($tags))
		{
			$tags = ArrayHelper::toInteger($tags);
			$tags = implode(',', $tags);
			if (!empty($tags))
			{
				$query->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'tagmap')
					. ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('p.id')
					. ' AND ' . $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_profiles.profile'))
					->where($db->quoteName('tagmap.tag_id') . ' IN (' . $tags . ')');
			}
		}

		// Filter by avatar
		$avatar = $this->getState('filter.avatar');
		if (is_numeric($avatar))
		{
			$operator = ($avatar == 0) ? ' = ' : ' <>';
			$query->where($db->quoteName('p.avatar') . $operator . $db->quote(''));
		}

		// Filter by online
		$online = $this->getState('filter.online');
		if (is_numeric($online))
		{
			$operator = ($online == 1) ? ' IS NOT NULL' : ' IS NULL';
			$query->where($db->quoteName('session.time') . $operator);
		}

		// Filter by social
		$social = $this->getState('filter.social');
		if (!empty($social))
		{
			$query->join('LEFT', '#__user_socials AS social ON social.user_id = p.id')
				->where($db->quoteName('social.provider') . ' = ' . $db->quote($social));
		}

		// Filter by usergroup
		$usergroup = $this->getState('filter.usergroup');
		if (!empty($usergroup))
		{
			$query->join('LEFT', '#__user_usergroup_map AS usergroup ON usergroup.user_id = p.id')
				->where($db->quoteName('usergroup.group_id') . ' = ' . $db->quote($usergroup));
		}

		// Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('p.id = ' . (int) substr($search, 3));
			}
			else
			{
				$text_columns = array('p.name', 'p.about', 'p.status', 'p.contacts', 'r.name', 'user.email', 'p.tags_search');

				$sql = array();
				foreach ($text_columns as $column)
				{
					$sql[] = $db->quoteName($column) . ' LIKE '
						. $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				}
				$number = $userModel->clearPhoneNumber($search);
				$code   = '+7';
				if (!empty($number))
				{
					$phone         = $code . $number;
					$phone_columns = array('CONCAT(phone.code, phone.number)', 'p.contacts');
					foreach ($phone_columns as $column)
					{
						$sql[] = $column . ' LIKE ' . $db->quote('%' . $phone . '%');
					}
				}
				$query->where('(' . implode(' OR ', $sql) . ')');
			}
		}

		// Group by
		$query->group(array('p.id'));

		// Add the list ordering clause.
		$ordering  = $this->state->get('list.ordering', 'last_visit');
		$direction = $this->state->get('list.direction', 'desc');
		$query->order($db->escape($ordering) . ' ' . $db->escape($direction));

		return $query;
	}


	/**
	 * Gets an array of objects from the results of database query.
	 *
	 * @param   string  $query      The query.
	 * @param   integer $limitstart Offset.
	 * @param   integer $limit      The number of records.
	 *
	 * @return  object[]  An array of results.
	 *
	 * @since 1.0.0
	 * @throws  \RuntimeException
	 */
	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$this->getDbo()->setQuery($query, $limitstart, $limit);

		return $this->getDbo()->loadObjectList('id');
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since 1.0.0
	 */
	public function getItems()
	{
		$items      = parent::getItems();
		$socials    = $this->getSocials(array_keys($items));
		$usergroups = $this->getUserGroups(array_keys($items));
		if (!empty($items))
		{
			foreach ($items as &$item)
			{
				$avatar = (!empty($item->avatar) && JFile::exists(JPATH_ROOT . '/' . $item->avatar)) ?
					$item->avatar : 'media/com_profiles/images/no-avatar.jpg';

				$item->avatar = Uri::root(true) . '/' . $avatar;

				$item->user_socials = (isset($socials[$item->id])) ? $socials[$item->id] : array();

				$item->user_groups = (isset($usergroups[$item->id])) ? $usergroups[$item->id] : array();

				$notes      = new Registry($item->notes);
				$item->note = $notes->get('note');

				// Get Tags
				$item->tags = new TagsHelper;
				$item->tags->getItemTags('com_profiles.profile', $item->id);
			}
		}

		return $items;
	}


	/**
	 * Get the filter form
	 *
	 * @param   array   $data     data
	 * @param   boolean $loadData load current data
	 *
	 * @return  \JForm|boolean  The \JForm object or false on error
	 *
	 * @since 1.0.0
	 */
	public function getFilterForm($data = array(), $loadData = true)
	{
		$component = ComponentHelper::getParams('com_profiles');
		if ($form = parent::getFilterForm())
		{
			// Set tags Filter
			if ($component->get('profile_tags', 0))
			{
				$form->setFieldAttribute('tags', 'parents', implode(',', $component->get('profile_tags')), 'filter');
			}
		}

		return $form;
	}

	/**
	 * Method to get an array of data items.
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
					->select(array('user_id', 'provider'))
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
					$socials[$object->user_id][] = $object->provider;
				}

				$this->_socials = $socials;
			}
		}

		return $this->_socials;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @param array $pks items id
	 *
	 * @return mixed An array of data items on success, false on failure.
	 *
	 * @since 1.0.0
	 */
	public function getUserGroups($pks = array())
	{
		if (!is_array($this->_usergroups))
		{
			$usergroups = array();
			if (!empty($pks))
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select(array('m.user_id', 'g.title'))
					->from($db->quoteName('#__user_usergroup_map', 'm'))
					->join('LEFT', '#__usergroups AS g ON m.group_id = g.id')
					->where($db->quoteName('m.user_id') . ' IN (' . implode(',', $pks) . ')')
					->order('lft asc');
				$db->setQuery($query);
				$objects = $db->loadObjectList();
				foreach ($objects as $object)
				{
					if (!isset($usergroups[$object->user_id]))
					{
						$usergroups[$object->user_id] = array();
					}
					$usergroups[$object->user_id][] = $object->title;
				}

				$this->_usergroups = $usergroups;
			}
		}

		return $this->_usergroups;
	}

}