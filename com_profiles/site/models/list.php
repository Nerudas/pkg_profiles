<?php
/**
 * @package    Profiles Component
 * @version    1.3.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Registry\Registry;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

JLoader::register('FieldTypesFilesHelper', JPATH_PLUGINS . '/fieldtypes/files/helper.php');

class ProfilesModelList extends ListModel
{
	/**
	 * This tag
	 *
	 * @var    object
	 * @since  1.0.0
	 */
	protected $_tag = null;

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
		$app = Factory::getApplication();

		// Set id state
		$pk = $app->input->getInt('id', 1);
		$this->setState('tag.id', $pk);

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


		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

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
		$id .= ':' . serialize($this->getState('filter.item_id'));
		$id .= ':' . $this->getState('filter.item_id.include');

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
		$component = ComponentHelper::getParams('com_profiles');

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/models');
		$userModel = BaseDatabaseModel::getInstance('User', 'ProfilesModel');

		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('p.*')
			->from($db->quoteName('#__profiles', 'p'));

		// Join over the users
		$query->join('LEFT', '#__users AS user ON user.id = p.id')
			->where('user.block = 0')
			->where('user.activation IN (' . $db->quote('') . ', ' . $db->quote('0') . ')');

		// Join over the discussions.
		$query->select('(CASE WHEN dt.id IS NOT NULL THEN dt.id ELSE 0 END) as discussions_topic_id')
			->join('LEFT', '#__discussions_topics AS dt ON dt.item_id = p.id AND ' .
				$db->quoteName('dt.context') . ' = ' . $db->quote('com_profiles.profile'));

		// Join over the regions.
		$query->select(array('r.id as region_id', 'r.name as region_name'))
			->join('LEFT', '#__location_regions AS r ON r.id = p.region');

		// Join over the sessions.
		$offline      = (int) $component->get('offline_time', 5) * 60;
		$offline_time = Factory::getDate()->toUnix() - $offline;
		$query->select('(session.time IS NOT NULL) AS online')
			->join('LEFT', '#__session AS session ON session.userid = p.id AND session.time > ' . $offline_time);

		// Join over the companies.
		$query->select(array('(company.id IS NOT NULL) AS job', 'company.id as job_id', 'company.name as job_name', 'employees.position'))
			->join('LEFT', '#__companies_employees AS employees ON employees.user_id = p.id AND ' .
				$db->quoteName('employees.key') . ' = ' . $db->quote(''))
			->join('LEFT', '#__companies AS company ON company.id = employees.company_id AND company.state = 1');


		// Filter by tag.
		$tag = (int) $this->getState('tag.id');
		if ($tag > 1)
		{
			$query->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'tagmap')
				. ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('p.id')
				. ' AND ' . $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_profiles.profile'))
				->where($db->quoteName('tagmap.tag_id') . ' = ' . $tag);
		}

		// Filter by a single or group of items.
		$itemId = $this->getState('filter.item_id');
		if (is_numeric($itemId))
		{
			$type = $this->getState('filter.item_id.include', true) ? '= ' : '<> ';
			$query->where('p.id ' . $type . (int) $itemId);
		}
		elseif (is_array($itemId))
		{
			$itemId = ArrayHelper::toInteger($itemId);
			$itemId = implode(',', $itemId);
			$type   = $this->getState('filter.item_id.include', true) ? 'IN' : 'NOT IN';
			$query->where('p.id ' . $type . ' (' . $itemId . ')');
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
			$query->order($db->escape($ordering) . ' ' . $db->escape($direction));
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
			JLoader::register('DiscussionsHelperTopic', JPATH_SITE . '/components/com_discussions/helpers/topic.php');

			$mainTags = ComponentHelper::getParams('com_profiles')->get('tags', array());

			foreach ($items as &$item)
			{
				$imagesHelper = new FieldTypesFilesHelper();

				$item->avatar = $imagesHelper->getImage('avatar', 'images/profiles/' . $item->id, 'media/com_profiles/images/no-avatar.jpg', false);

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
				if (!empty($item->tags->itemTags))
				{
					foreach ($item->tags->itemTags as &$tag)
					{
						$tag->main = (in_array($tag->id, $mainTags));
					}
					$item->tags->itemTags = ArrayHelper::sortObjects($item->tags->itemTags, 'main', -1);
				}

				if ($item->job)
				{
					$item->job_link = Route::_(CompaniesHelperRoute::getCompanyRoute($item->job_id));
				}

				// Get region
				$item->avatar = $imagesHelper->getImage('avatar', 'images/location/regions' . $item->redion->id, false, false);

				// Discussions posts count
				$item->commentsCount = DiscussionsHelperTopic::getPostsTotal($item->discussions_topic_id);
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
		if ($form = parent::getFilterForm())
		{
			$params = $this->getState('params');
			if ($params->get('search_placeholder', ''))
			{
				$form->setFieldAttribute('search', 'hint', $params->get('search_placeholder'), 'filter');
			}

			$form->setValue('tag', 'filter', $this->getState('tag.id', 1));
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

	/**
	 * Get the current tag
	 *
	 * @param null $pk
	 *
	 * @return object|false
	 *
	 * @since 1.1.0
	 */
	public function getTag($pk = null)
	{
		if (!is_object($this->_tag))
		{
			$app    = Factory::getApplication();
			$pk     = (!empty($pk)) ? (int) $pk : (int) $this->getState('tag.id', $app->input->get('id', 1));
			$tag_id = $pk;

			$root            = new stdClass();
			$root->title     = Text::_('JGLOBAL_ROOT');
			$root->id        = 1;
			$root->parent_id = 0;
			$root->link      = Route::_(ProfilesHelperRoute::getListRoute(1));

			if ($tag_id > 1)
			{
				$errorRedirect = Route::_(ProfilesHelperRoute::getListRoute(1));
				$errorMsg      = Text::_('COM_PROFILES_ERROR_TAG_NOT_FOUND');
				try
				{
					$db    = $this->getDbo();
					$query = $db->getQuery(true)
						->select(array('t.id', 't.parent_id', 't.title', 'pt.title as parent_title'))
						->from('#__tags AS t')
						->where('t.id = ' . (int) $tag_id)
						->join('LEFT', '#__tags AS pt ON pt.id = t.parent_id');

					$user = Factory::getUser();
					if (!$user->authorise('core.admin'))
					{
						$query->where('t.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');
					}
					if (!$user->authorise('core.manage', 'com_tags'))
					{
						$query->where('t.published =  1');
					}

					$db->setQuery($query);
					$data = $db->loadObject();

					if (empty($data))
					{
						$app->redirect($url = $errorRedirect, $msg = $errorMsg, $msgType = 'error', $moved = true);

						return false;
					}

					$data->link = Route::_(ProfilesHelperRoute::getListRoute($data->id));

					$this->_tag = $data;
				}
				catch (Exception $e)
				{
					if ($e->getCode() == 404)
					{
						$app->redirect($url = $errorRedirect, $msg = $errorMsg, $msgType = 'error', $moved = true);
					}
					else
					{
						$this->setError($e);
						$this->_tag = false;
					}
				}
			}
			else
			{
				$this->_tag = $root;
			}
		}

		return $this->_tag;
	}
}