<?php
/**
 * @package    Profiles Component
 * @version    1.0.2
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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Form\Form;

class ProfilesModelList extends ListModel
{

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
				'p.state', 'state',
				'p.created', 'created',
				'p.modified', 'modified',
				'p.attribs', 'attribs',
				'p.metakey', 'metakey',
				'p.metadesc', 'metadesc',
				'p.access', 'access',
				'p.hits', 'hits',
				'region', 'p.region', 'region_name',
				'p.metadata', 'metadata',
				'p.tags_search', 'tags_search',
				'p.tags_map', 'tags_map',
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
		$app  = Factory::getApplication();
		$user = Factory::getUser();

		// Load the parameters. Merge Global and Menu Item params into new object
		$params     = $app->getParams();
		$menuParams = new Registry;
		$menu       = $app->getMenu()->getActive();
		if ($menu)
		{
			$menuParams->loadString($menu->getParams());
		}
		$mergedParams = clone $menuParams;
		$mergedParams->merge($params);
		$this->setState('params', $mergedParams);

		// Published state
		if ((!$user->authorise('core.manage', 'com_profiles')))
		{
			// Limit to published for people who can't edit or edit.state.
			$this->setState('filter.published', 1);
		}
		else
		{
			$this->setState('filter.published', array(0, 1));
		}

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$region = $this->getUserStateFromRequest($this->context . '.filter.region', 'filter_region', '');
		$this->setState('filter.region', $region);

		$tags = $this->getUserStateFromRequest($this->context . '.filter.tags', 'filter_tags', '');
		$this->setState('filter.tags', $tags);

		// List state information.
		parent::populateState($ordering, $direction);

		// Set limit & limitstart for query.
		$this->setState('list.limit', $params->get('profiles_limit', 10, 'uint'));
		$this->setState('list.start', $app->input->get('limitstart', 0, 'uint'));

		// Set ordering for query.
		$ordering  = empty($ordering) ? 'avatar' : $ordering;
		$direction = empty($direction) ? 'desc' : $direction;
		$this->setState('list.ordering', $ordering);
		$this->setState('list.direction', $direction);
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
		$id .= ':' . $this->getState('filter.region');
		$id .= ':' . serialize($this->getState('filter.published'));
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
		$user      = Factory::getUser();
		$component = ComponentHelper::getParams('com_profiles');

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/models');
		$userModel = BaseDatabaseModel::getInstance('User', 'ProfilesModel');

		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('p.*')
			->from($db->quoteName('#__profiles', 'p'));

		// Join over the regions.
		$query->select(array('r.id as region_id', 'r.name AS region_name'))
			->join('LEFT', '#__regions AS r ON r.id = 
					(CASE p.region WHEN ' . $db->quote('*') . ' THEN 100 ELSE p.region END)');

		// Join over the sessions.
		$offline      = (int) $component->get('offline_time', 5) * 60;
		$offline_time = Factory::getDate()->toUnix() - $offline;
		$query->select('(session.time IS NOT NULL) AS online')
			->join('LEFT', '#__session AS session ON session.userid = p.id AND session.time > ' . $offline_time);

		// Join over the companies.
		$query->select(array('(company.id IS NOT NULL) AS job', 'company.id as job_id', 'company.name as job_name', 'company.logo as job_logo', 'employees.position'))
			->join('LEFT', '#__companies_employees AS employees ON employees.user_id = p.id AND ' .
				$db->quoteName('employees.key') . ' = ' . $db->quote(''))
			->join('LEFT', '#__companies AS company ON company.id = employees.company_id AND company.state = 1');

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

		// Filter by access level.
		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('p.access IN (' . $groups . ')');
		}

		// Filter by published state.
		$published = $this->getState('filter.published');
		if (!empty($published))
		{
			if (is_numeric($published))
			{
				$query->where('( p.state = ' . (int) $published .
					' OR ( p.id = ' . $user->id . ' AND p.state IN (0,1)))');
			}
			elseif (is_array($published))
			{
				$query->where('p.state IN (' . implode(',', $published) . ')');
			}
		}

		// Filter by a single or group of tags.
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
				$text_columns = array('p.name', 'p.about', 'p.status', 'p.contacts', 'r.name', 'p.tags_search');

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
					$phone_columns = array('p.contacts');
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
		$ordering  = $this->state->get('list.ordering', 'p.created');
		$direction = $this->state->get('list.direction', 'desc');
		if ($ordering == 'avatar')
		{
			$query->order($db->escape($ordering) . ' <> ' . $db->quote('') . $db->escape($direction));
			$ordering = 'p.created';
			$query->order($db->escape($ordering) . ' ' . $db->escape($direction));
		}
		else
		{
			$query->order($db->escape($ordering) . ' ' . $db->escape($direction));
		}

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
		$items = parent::getItems();
		if (!empty($items))
		{
			foreach ($items as &$item)
			{
				$avatar = (!empty($item->avatar) && JFile::exists(JPATH_ROOT . '/' . $item->avatar)) ?
					$item->avatar : 'media/com_profiles/images/no-avatar.jpg';

				$item->avatar = Uri::root(true) . '/' . $avatar;

				// Convert the contacts field from json.
				$item->contacts = new Registry($item->contacts);
				if ($phones = $item->contacts->get('phones'))
				{
					$phones = ArrayHelper::fromObject($phones, false);
					$item->contacts->set('phones', $phones);
				}

				$item->link = Route::_(ProfilesHelperRoute::getProfileRoute($item->id));

				// Get Tags
				$item->tags = new TagsHelper;
				$item->tags->getItemTags('com_profiles.profile', $item->id);

				if ($item->job)
				{
					$item->job_link = Route::_(CompaniesHelperRoute::getCompanyRoute($item->job_id));
				}
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
	 * @return  Form|boolean  The Form object or false on error
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

			$params = $this->getState('params');
			if ($params->get('search_placeholder', ''))
			{
				$form->setFieldAttribute('search', 'hint', $params->get('search_placeholder'), 'filter');
			}
		}

		return $form;
	}

	/**
	 * Gets the value of a user state variable and sets it in the session
	 *
	 * This is the same as the method in \JApplication except that this also can optionally
	 * force you back to the first page when a filter has changed
	 *
	 * @param   string  $key       The key of the user state variable.
	 * @param   string  $request   The name of the variable passed in a request.
	 * @param   string  $default   The default value for the variable if not found. Optional.
	 * @param   string  $type      Filter for the variable, for valid values see {@link \JFilterInput::clean()}. Optional.
	 * @param   boolean $resetPage If true, the limitstart in request is set to zero
	 *
	 * @return  mixed  The request user state.
	 *
	 * @since 1.0.0
	 */
	public function getUserStateFromRequest($key, $request, $default = null, $type = 'none', $resetPage = true)
	{
		$app       = Factory::getApplication();
		$set_state = $app->input->get($request, null, $type);
		$new_state = parent::getUserStateFromRequest($key, $request, $default, $type, $resetPage);
		if ($new_state == $set_state)
		{
			return $new_state;
		}
		$app->setUserState($key, $set_state);

		return $set_state;
	}

}