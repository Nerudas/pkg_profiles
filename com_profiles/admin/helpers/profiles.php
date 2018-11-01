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

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

class ProfilesHelper extends ContentHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string $vName The name of the active view.
	 *
	 * @return  void
	 *
	 * @since 1.5.0
	 */
	static function addSubmenu($vName)
	{
		foreach (self::getSidebarEntries($vName) as $view)
		{
			JHtmlSidebar::addEntry($view['name'], $view['link'], $view['active']);
		}
	}

	/**
	 * Method to render profiles sidebar
	 *
	 * @param   string $vName The name of the active view.
	 *
	 * @return  string
	 *
	 * @since 1.5.0
	 */
	public static function renderSideBar($vName)
	{
		// Prepare entries
		$list = array();
		foreach (self::getSidebarEntries($vName) as $view)
		{
			$list[] = array_values($view);
		}

		// Prepare layout data
		$data                 = new stdClass;
		$data->list           = $list;
		$data->filters        = array();
		$data->action         = '';
		$data->displayMenu    = count($data->list);
		$data->displayFilters = count($data->filters);
		$data->hide           = Factory::getApplication()->input->getBool('hidemainmenu');

		return LayoutHelper::render('joomla.sidebars.submenu', $data);
	}

	/**
	 * Method to get sidebar entries
	 *
	 * @param   string $vName The name of the active view.
	 *
	 * @return  array
	 *
	 * @since 1.5.0
	 */
	static function getSidebarEntries($vName)
	{
		$entries = array();

		// Profiles view
		$entries['profiles'] = array(
			'name'   => Text::_('COM_PROFILES'),
			'link'   => 'index.php?option=com_profiles&view=profiles',
			'active' => ($vName == 'profiles'));

		// Categories view
		$entries['categories'] = array(
			'name'   => Text::_('COM_PROFILES_CATEGORIES'),
			'link'   => 'index.php?option=com_profiles&view=categories',
			'active' => ($vName == 'categories'));

		if (self::getActions('com_users')->get('core.admin'))
		{
			// Users groups view
			$entries['groups'] = array(
				'name'   => Text::_('COM_PROFILES_USERS_GROUPS'),
				'link'   => 'index.php?option=com_users&view=groups',
				'active' => ($vName == 'groups')
			);

			// Users levels view
			$entries['levels'] = array(
				'name'   => Text::_('COM_PROFILES_USERS_LEVELS'),
				'link'   => 'index.php?option=com_users&view=levels',
				'active' => ($vName == 'levels')
			);
		}

		return $entries;
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @param   string  $component The component name.
	 * @param   string  $section   The access section name.
	 * @param   integer $id        The item ID.
	 *
	 * @return  \Joomla\CMS\Object\CMSObject
	 *
	 * @since 1.5.0
	 */
	public static function getActions($component = '', $section = '', $id = 0)
	{
		$component = ($component !== 'com_profiles') ? $component : 'com_users';

		return parent::getActions($component, $section, $id);
	}
}