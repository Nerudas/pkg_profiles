<?php
/**
 * @package    Profiles - Tags Module
 * @version    1.1.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

class modProfilesTagsHelper
{
	/**
	 * Method to get tags data.
	 *
	 * @param  \Joomla\Registry\Registry $params module params
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public static function getTags($params)
	{
		$app    = Factory::getApplication();
		$user   = Factory::getUser();
		$params = ComponentHelper::getParams('com_profiles');
		$tags   = $params->get('tags');
		$items  = array();

		if (!empty($tags) && is_array($tags))
		{
			// Get tags
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('t.id', 't.title'))
				->from($db->quoteName('#__tags', 't'))
				->where($db->quoteName('t.alias') . ' <>' . $db->quote('root'))
				->where('t.id IN (' . implode(',', $tags) . ')');

			if (!$user->authorise('core.admin'))
			{
				$query->where('t.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');
			}
			if (!$user->authorise('core.manage', 'com_tags'))
			{
				$query->where('t.published =  1');
			}

			$query->order($db->escape('t.lft') . ' ' . $db->escape('asc'));

			$db->setQuery($query);
			$items = $db->loadObjectList();
		}

		// Add root
		$root        = new stdClass();
		$root->title = Text::_($params->get('root_title', 'COM_PROFILES'));
		$root->id    = 1;
		array_unshift($items, $root);

		foreach ($items as &$tag)
		{
			$tag->link   = Route::_(ProfilesHelperRoute::getListRoute($tag->id));
			$tag->active = ($app->isSite() && $app->input->get('option') == 'com_profiles'
				&& $app->input->get('view') == 'list' && $app->input->get('id') == $tag->id);
		}

		return $items;
	}
}