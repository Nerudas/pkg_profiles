<?php
/**
 * @package    Profiles Component
 * @version    1.5.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class ProfilesModelProfiles extends ListModel
{
	/**
	 * Profiles tags array
	 *
	 * @var array
	 *
	 * @since 1.5.0
	 */
	protected $_tags = null;

	/**
	 * Profiles includes tags ids
	 *
	 * @var array
	 *
	 * @since 1.5.0
	 */
	protected $_includesTags = null;

	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @since 1.5.0
	 */
	public function __construct($config = array())
	{
		// Add the ordering filtering fields whitelist.
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'p.id',
				'title', 'p.title',
				'published', 'state', 'p.state',
				'access_level', 'ag.title',
				'access', 'p.access',
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
	 * @since 1.5.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Set search filter state
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Set published filter state
		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		// Set access filter state
		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
		$this->setState('filter.access', $access);

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
	 * @since 1.5.0
	 */
	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.access');
		$id .= ':' . $this->getState('filter.published');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since 1.5.0
	 */
	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('p.*')
			->from($db->quoteName('#__profiles', 'p'));

		// Join over the users.
		$query->select(array('user.email AS user_email', 'user.lastvisitDate as last_visit'))
			->join('LEFT', '#__users AS user ON user.id = p.id');

		// Join over the asset groups.
		$query->select('ag.title AS access_level')
			->join('LEFT', '#__viewlevels AS ag ON ag.id = p.access');

		// Filter by access level.
		$access = $this->getState('filter.access');
		if (is_numeric($access))
		{
			$query->where('p.access = ' . (int) $access);
		}

		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published))
		{
			$query->where('p.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(p.state = 0 OR p.state = 1)');
		}

		// Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('c.id = ' . (int) substr($search, 3));
			}
			else
			{
				$columns = $columns = array('p.id', 'p.name', 'p.alias', 'p.about', 'p.status', 'p.portfolio',
					'p.contacts', 'p.requisites', 'p.notes', 'p.tags_search', 'user.email');

				$sql = array();
				foreach ($columns as $column)
				{
					$sql[] = $db->quoteName($column) . ' LIKE '
						. $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
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
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since 1.5.0
	 */
	public function getItems()
	{
		if (!empty($items = parent::getItems()))
		{
			// Get items tags
			$pks  = ArrayHelper::getColumn($items, 'item_tags');
			$pks  = implode(',', $pks);
			$pks  = explode(',', $pks);
			$pks  = array_unique($pks);
			$pks  = array_filter($pks);
			$tags = $this->getTags($pks);

			foreach ($items as &$item)
			{
				// Set tags
				$item->tags = array();
				if (!empty($item->items_tags))
				{
					foreach (array_filter(explode(',', $item->items_tags)) as $tagID)
					{
						if (!empty($tags[$tagID]))
						{
							$item->tags[] = $tags[$tagID];
						}
					}
				}
			}
		}

		return $items;
	}

	/**
	 * Method to get an array of data tags.
	 *
	 * @param  array $pks Tags ids array.
	 *
	 * @return  array Tags data array
	 *
	 * @since 1.5.0
	 */
	public function getTags($pks = null)
	{
		if (!is_array($this->_tags))
		{
			try
			{
				$tags = array();
				if (!empty($pks))
				{
					$pks   = ArrayHelper::toInteger($pks);
					$db    = Factory::getDbo();
					$query = $db->getQuery(true)
						->select(array('id', 'title', 'lft'))
						->from('#__tags')
						->where('id IN (' . implode(',', $pks) . ')')
						->where('(published = 0 OR published = 1)')
						->order($db->escape('lft') . ' ' . $db->escape('asc'));
					$db->setQuery($query);

					$tags = $db->loadObjectList('id');
				}

				$this->_tags = $tags;
			}
			catch (Exception $e)
			{
				throw new Exception(Text::_($e->getMessage()), $e->getCode());
			}
		}

		return $this->_tags;
	}

	/**
	 * Method to get profiles includes tags ids
	 *
	 * @return  array Tags ids array
	 *
	 * @since 1.5.0
	 */
	public function getIncludesTags()
	{
		if (!is_array($this->_includesTags))
		{
			try
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('items_tags')
					->from('#__profiles_categories');
				$db->setQuery($query);
				$tags = $db->loadColumn();

				$tags = implode(',', $tags);
				$tags = explode(',', $tags);
				$tags = array_unique($tags);
				$tags = array_filter($tags);

				$this->_includesTags = $tags;
			}
			catch (Exception $e)
			{
				throw new Exception(Text::_($e->getMessage()), $e->getCode());
			}
		}

		return $this->_includesTags;
	}
}