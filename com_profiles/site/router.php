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

use Joomla\CMS\Factory;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Component\ComponentHelper;


class ProfilesRouter extends RouterView
{
	/**
	 * Router constructor
	 *
	 * @param   JApplicationCms $app  The application object
	 * @param   JMenu           $menu The menu object to work with
	 *
	 * @since 1.0.0
	 */
	public function __construct($app = null, $menu = null)
	{
		// List route
		$list = new RouterViewConfiguration('list');
		$list->setKey('id')->setNestable();
		$this->registerView($list);

		// Profiles route
		$profile = new RouterViewConfiguration('profile');
		$profile->setKey('id')->setParent($list, 'tag_id');
		$this->registerView($profile);

		parent::__construct($app, $menu);

		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}

	/**
	 * Method to get the segment(s) for list view
	 *
	 * @param   string $id    ID of the item to retrieve the segments for
	 * @param   array  $query The request that is built right now
	 *
	 * @return  array|string  The segments of this item
	 *
	 * @since 1.0.0
	 */
	public function getListSegment($id, $query)
	{
		$path = array();
		if ($id > 0)
		{
			$db      = Factory::getDbo();
			$dbquery = $db->getQuery(true)
				->select(array('id', 'alias', 'parent_id'))
				->from('#__tags')
				->where('id =' . $id);
			$db->setQuery($dbquery);
			$tag = $db->loadObject();
			if ($tag)
			{
				$path[$tag->id] = $tag->alias;
			}
		}
		$path[1] = 'root';

		return $path;
	}

	/**
	 * Method to get the segment(s) for profile view
	 *
	 * @param   string $id    ID of the item to retrieve the segments for
	 * @param   array  $query The request that is built right now
	 *
	 * @return  array|string  The segments of this item
	 *
	 * @since 1.0.0
	 */
	public function getProfileSegment($id, $query)
	{
		if (!strpos($id, ':'))
		{
			$db      = Factory::getDbo();
			$dbquery = $db->getQuery(true)
				->select('alias')
				->from('#__profiles')
				->where('id = ' . (int) $id);
			$db->setQuery($dbquery);
			$alias = $db->loadResult();

			return array($id => $alias);
		}

		return false;
	}

	/**
	 * Method to get the id for a list view
	 *
	 * @param   string $segment Segment to retrieve the ID for
	 * @param   array  $query   The request that is parsed right now
	 *
	 * @return  mixed   The id of this item or false
	 *
	 * @since 1.0.0
	 */
	public function getListId($segment, $query)
	{
		if (isset($query['id']))
		{
			$tags = ComponentHelper::getParams('com_profiles')->get('tags');

			// Get tags
			if (!empty($tags) && is_array($tags))
			{
				$db      = Factory::getDbo();
				$dbquery = $db->getQuery(true)
					->select('t.id')
					->from($db->quoteName('#__tags', 't'))
					->where($db->quoteName('t.alias') . ' <>' . $db->quote('root'))
					->where('t.id IN (' . implode(',', $tags) . ')')
					->where($db->quoteName('alias') . ' = ' . $db->quote($segment));
				$db->setQuery($dbquery);
				$id = $db->loadResult();

				return (!empty($id)) ? $id : false;
			}
		}

		return false;
	}

	/**
	 * Method to get the id for a profile view
	 *
	 * @param   string $segment Segment to retrieve the ID for
	 * @param   array  $query   The request that is parsed right now
	 *
	 * @return  mixed   The id of this item or false
	 *
	 * @since 1.0.0
	 */
	public function getProfileId($segment, $query)
	{
		if (!empty($segment))
		{
			preg_match('/^id(.*)/', $segment, $matches);
			$id = (!empty($matches[1])) ? (int) $matches[1] : 0;
			if (!empty($id))
			{
				return $id;
			}

			$db      = Factory::getDbo();
			$dbquery = $db->getQuery(true)
				->select('id')
				->from('#__profiles')
				->where($db->quoteName('alias') . ' = ' . $db->quote($segment));
			$db->setQuery($dbquery);

			return (int) $db->loadResult();
		}

		return false;
	}
}

function profilesBuildRoute(&$query)
{
	$app    = Factory::getApplication();
	$router = new ProfilesRouter($app, $app->getMenu());

	return $router->build($query);
}

function profilesParseRoute($segments)
{
	$app    = Factory::getApplication();
	$router = new ProfilesRouter($app, $app->getMenu());

	return $router->parse($segments);
}