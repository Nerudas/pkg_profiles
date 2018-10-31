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

class ProfilesModelCategories extends ListModel
{
	/**
	 * Categories tags array
	 *
	 * @var array
	 *
	 * @since 1.5.0
	 */
	protected $_tags = null;

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
				'id', 'c.id',
				'title', 'c.title',
				'published', 'state', 'c.state',
				'access_level', 'ag.title', 'access', 'c.access',
				'parent_id', 'c.parent_id', 'parent'
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

		// Set parent filter state
		$parent = $this->getUserStateFromRequest($this->context . '.filter.parent', 'filter_parent', '');
		$this->setState('filter.parent', $parent);

		// List state information.
		$ordering  = empty($ordering) ? 'c.lft' : $ordering;
		$direction = empty($direction) ? 'asc' : $direction;

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
		$id .= ':' . $this->getState('filter.parent');

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
			->select('c.*')
			->from($db->quoteName('#__profiles_categories', 'c'));

		// Join over the asset groups.
		$query->select('ag.title AS access_level')
			->join('LEFT', '#__viewlevels AS ag ON ag.id = c.access');

		// Filter by access level.
		$access = $this->getState('filter.access');
		if (is_numeric($access))
		{
			$query->where('c.access = ' . (int) $access);
		}

		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published))
		{
			$query->where('c.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(c.state = 0 OR c.state = 1)');
		}

		// Filter by parent state
		$parent = $this->getState('filter.parent');
		if (is_numeric($parent))
		{
			// Create a subquery for the sub-items list
			$subQuery = $db->getQuery(true)
				->select('sub.id')
				->from('#__profiles_categories as sub')
				->join('INNER', '#__profiles_categories as this ON sub.lft > this.lft AND sub.rgt < this.rgt')
				->where('this.id = ' . (int) $parent);

			// Add the subquery to the main query
			$query->where('(c.parent_id = ' . (int) $parent . ' OR ' . 'c.id =' . (int) $parent .
				' OR c.parent_id IN (' . (string) $subQuery . '))');
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
				$columns = array('c.title', 'c.description', 'c.metadata');

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
		$query->group(array('c.id'));

		// Add the list ordering clause.
		$ordering  = $this->state->get('list.ordering', 'c.lft');
		$direction = $this->state->get('list.direction', 'asc');
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
			$pks  = ArrayHelper::getColumn($items, 'items_tags');
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
}