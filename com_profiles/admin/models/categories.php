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

class ProfilesModelCategories extends ListModel
{
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
				'access_level', 'ag.title',
				'access', 'c.access',
				'ordering', 'c.ordering'
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
				$text_columns = array('c.title', 'c.description', 'c.metadata');

				$sql = array();
				foreach ($text_columns as $column)
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
}