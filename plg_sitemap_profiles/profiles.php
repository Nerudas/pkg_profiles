<?php
/**
 * @package    Sitemap - Profiles  Plugin
 * @version    1.2.1
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

class plgSitemapProfiles extends CMSPlugin
{

	/**
	 * Urls array
	 *
	 * @var    array
	 *
	 * @since  1.0.0
	 */
	protected $_urls = null;

	/**
	 * Method to get Links array
	 *
	 * @return array
	 *
	 * @since 1.1.1
	 */
	public function getUrls()
	{
		if ($this->_urls === null)
		{

			// Include route helper
			JLoader::register('ProfilesHelperRoute', JPATH_SITE . '/components/com_profiles/helpers/route.php');

			$db   = Factory::getDbo();
			$user = Factory::getUser(0);

			// Get items
			$query = $db->getQuery(true)
				->select(array('p.id', 'p.modified'))
				->from($db->quoteName('#__profiles', 'p'))
				->join('LEFT', '#__users AS user ON user.id = p.id')
				->where('user.block = 0')
				->where('user.activation IN (' . $db->quote('') . ', ' . $db->quote('0') . ')')
				->order('p.modified DESC');

			$db->setQuery($query);
			$profiles = $db->loadObjectList('id');

			$profile_changefreq = $this->params->def('profile_changefreq', 'weekly');
			$profile_priority   = $this->params->def('profile_priority', '0.5');


			foreach ($profiles as $profile)
			{
				$url             = new stdClass();
				$url->loc        = ProfilesHelperRoute::getProfileRoute($profile->id);
				$url->changefreq = $profile_changefreq;
				$url->priority   = $profile_priority;
				$url->lastmod    = $profile->modified;

				$profiles_urls[] = $url;
			}

			// Get Tags
			$navtags        = ComponentHelper::getParams('com_profiles')->get('tags', array());
			$tag_changefreq = $this->params->def('tag_changefreq', 'weekly');
			$tag_priority   = $this->params->def('tag_priority', '0.5');

			$tags              = array();
			$tags[1]           = new stdClass();
			$tags[1]->id       = 1;
			$tags[1]->modified = array_shift($profiles)->modified;

			if (!empty($navtags))
			{
				$query = $db->getQuery(true)
					->select(array('tm.tag_id as id', 'max(tm.tag_date) as modified'))
					->from($db->quoteName('#__contentitem_tag_map', 'tm'))
					->join('LEFT', '#__tags AS t ON t.id = tm.tag_id')
					->where($db->quoteName('tm.type_alias') . ' = ' . $db->quote('com_profiles.profile'))
					->where('tm.tag_id IN (' . implode(',', $navtags) . ')')
					->where('t.published = 1')
					->where('t.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')')
					->group('t.id');
				$db->setQuery($query);

				$tags = $tags + $db->loadObjectList('id');
			}

			$tags_urls = array();
			foreach ($tags as $tag)
			{
				$url             = new stdClass();
				$url->loc        = ProfilesHelperRoute::getListRoute($tag->id);
				$url->changefreq = $tag_changefreq;
				$url->priority   = $tag_priority;
				$url->lastmod    = $tag->modified;

				$tags_urls[] = $url;
			}

			$this->_urls = array_merge($tags_urls, $profiles_urls);
		}

		return $this->_urls;

	}
}