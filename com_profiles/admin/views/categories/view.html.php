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

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;

class ProfilesViewCategories extends HtmlView
{
	/**
	 * The model state
	 *
	 * @var  Joomla\CMS\Object\CMSObject
	 *
	 * @since 1.5.0
	 */
	protected $state;

	/**
	 * Items array
	 *
	 * @var  array
	 *
	 * @since 1.5.0
	 */
	protected $items;

	/**
	 * The pagination object
	 *
	 * @var  \Joomla\CMS\Pagination\Pagination
	 *
	 * @since 1.5.0
	 */
	protected $pagination;

	/**
	 * Form object for search filters
	 *
	 * @var  \Joomla\CMS\Form\Form
	 *
	 * @since 1.5.0
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var  array
	 *
	 * @since 1.5.0
	 */
	public $activeFilters;

	/**
	 * View sidebar
	 *
	 * @var  string
	 *
	 * @since 1.5.0
	 */
	public $sidebar;

	/**
	 * Ordering divisions
	 *
	 * @var  array
	 *
	 * @since 1.5.0
	 */
	public $ordering;

	/**
	 * Display the view
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return mixed A string if successful, otherwise an Error object.
	 *
	 * @throws Exception
	 *
	 * @since 1.5.0
	 */
	public function display($tpl = null)
	{
		$this->state         = $this->get('State');
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Add title and toolbar
		$this->addToolbar();

		// Prepare sidebar
		ProfilesHelper::addSubmenu('categories');
		$this->sidebar = JHtmlSidebar::render();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode('\n', $errors), 500);
		}

		// Preprocess the list of items to find ordering divisions.
		if (!empty($this->items))
		{
			foreach ($this->items as &$item)
			{
				$this->ordering[$item->parent_id][] = $item->id;
			}
		}

		return parent::display($tpl);
	}

	/**
	 * Add title and toolbar.
	 *
	 * @return  void
	 *
	 * @since 1.5.0
	 */
	protected function addToolbar()
	{
		$user  = Factory::getUser();
		$canDo = ProfilesHelper::getActions('com_profiles', 'categories');

		// Set page title
		ToolbarHelper::title(Text::_('COM_PROFILES') . ': ' . Text::_('COM_PROFILES_CATEGORIES'),
			'users');

		// Add create button
		if ($canDo->get('core.create'))
		{
			ToolbarHelper::addNew('category.add');
		}

		// Add edit button
		if ($canDo->get('core.edit'))
		{
			ToolbarHelper::editList('category.edit');
		}

		// Add publish & unpublish buttons
		if ($canDo->get('core.edit.state'))
		{
			ToolbarHelper::publish('categories.publish', 'JTOOLBAR_PUBLISH', true);
			ToolbarHelper::unpublish('categories.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}

		// Add delete/trash buttons
		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'categories.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			ToolbarHelper::trash('categories.trash');
		}

		// Add rebuild button
		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::custom('categories.rebuild', 'refresh.png', 'refresh_f2.png', 'JTOOLBAR_REBUILD', false);
		}

		// Add preferences button
		if ($user->authorise('core.admin', 'com_profiles') || $user->authorise('core.options', 'com_profiles'))
		{
			$return = urlencode(base64_encode(Uri::getInstance()->toString()));
			$link   = 'index.php?option=com_config&view=component&return=' . $return . '&component=';

			ToolbarHelper::link($link . 'com_profiles', Text::_('COM_PROFILES_CONFIG_PROFILES'), 'options-profiles');
			ToolbarHelper::link($link . 'com_users', Text::_('COM_PROFILES_CONFIG_USERS'), 'options-users');
		}
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since 1.5.0
	 */
	protected function getSortFields()
	{
		return [
			'c.state'      => Text::_('JSTATUS'),
			'c.id'         => Text::_('JGRID_HEADING_ID'),
			'c.title'      => Text::_('JGLOBAL_TITLE'),
			'access_level' => Text::_('JGRID_HEADING_ACCESS'),
		];
	}
}