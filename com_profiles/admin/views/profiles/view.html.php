<?php
/**
 * @package    Profiles Component
 * @version    1.0.3
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class ProfilesViewProfiles extends HtmlView
{
	/**
	 * An array of items
	 *
	 * @var  array
	 *
	 * @since 1.0.0
	 */
	protected $items;

	/**
	 * The pagination object
	 *
	 * @var  JPagination
	 *
	 * @since 1.0.0
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var  object
	 *
	 * @since 1.0.0
	 */
	protected $state;

	/**
	 * Form object for search filters
	 *
	 * @var  JForm
	 *
	 * @since 1.0.0
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var  array
	 *
	 * @since 1.0.0
	 */
	public $activeFilters;

	/**
	 * Display the view
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return mixed A string if successful, otherwise an Error object.
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function display($tpl = null)
	{
		ProfilesHelper::addSubmenu('profiles');

		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->state         = $this->get('State');

		$this->addToolbar();
		$this->sidebar = JHtmlSidebar::render();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		return parent::display($tpl);
	}


	/**
	 * Add the extension title and toolbar.
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	protected function addToolbar()
	{
		$user  = Factory::getUser();
		$canDo = ProfilesHelper::getActions('com_users');

		JToolBarHelper::title(Text::_('COM_PROFILES'), 'list');

		if ($canDo->get('core.create'))
		{
			JToolbarHelper::addNew('profile.add');
		}

		if ($canDo->get('core.edit'))
		{
			JToolbarHelper::editList('profile.edit');
		}

		$state = $this->state->get('filter.state');
		if ($state == 'blocked')
		{
			if ($canDo->get('core.edit.state'))
			{
				JToolbarHelper::custom('profiles.unblock', 'unblock.png', 'unblock_f2.png',
					'COM_PROFILES_TOOLBAR_UNBLOCK', true);
			}
			if ($canDo->get('core.delete'))
			{
				JToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'profiles.delete', 'JTOOLBAR_DELETE');
				JToolbarHelper::divider();
			}
		}
		elseif ($state == 'not_activated' && $canDo->get('core.edit.state'))
		{
			JToolbarHelper::unpublish('profiles.block', 'COM_PROFILES_TOOLBAR_BLOCK', true);
			JToolbarHelper::publish('profiles.activate', 'COM_PROFILES_TOOLBAR_ACTIVATE', true);
		}
		else
		{
			JToolbarHelper::unpublish('profiles.block', 'COM_PROFILES_TOOLBAR_BLOCK', true);
		}

		if ($canDo->get('core.create'))
		{
			JToolbarHelper::custom('export.excel', 'file-add', 'file-add',
				'COM_PROFILES_TOOLBAR_EXPORT_EXCEL', false);
			JToolbarHelper::custom('profiles.synchronize', 'loop', 'loop',
				'COM_PROFILES_TOOLBAR_SYNCHRONIZE', false);
		}

		if ($user->authorise('core.admin', 'com_profiles') || $user->authorise('core.options', 'com_profiles'))
		{
			JToolbarHelper::preferences('com_profiles');
		}
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since 1.0.0
	 */
	protected function getSortFields()
	{
		return [
			'p.id'        => Text::_('JGRID_HEADING_ID'),
			'p.name'      => Text::_('COM_PROFILES_PROFILE_NAME'),
			'p.created'   => Text::_('JGLOBAL_CREATED_DATE'),
			'p.hits'      => Text::_('JGLOBAL_HITS'),
			'region_name' => Text::_('JGRID_HEADING_REGION'),
			'last_visit'  => Text::_('COM_PROFILES_PROFILE_LAST_VISIT'),
		];
	}
}