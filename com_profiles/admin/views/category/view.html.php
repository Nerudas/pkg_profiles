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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

class ProfilesViewCategory extends HtmlView
{
	/**
	 * Form object
	 *
	 * @var  \Joomla\CMS\Form\Form
	 *
	 * @since 1.5.0
	 */
	protected $form;

	/**
	 * Active item object
	 *
	 * @var  object
	 *
	 * @since 1.5.0
	 */
	protected $item;

	/**
	 * Model state
	 *
	 * @var  Joomla\CMS\Object\CMSObject
	 *
	 * @since 1.5.0
	 */
	protected $state;

	/**
	 * Execute and display a template script.
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
		$this->form  = $this->get('Form');
		$this->item  = $this->get('Item');
		$this->state = $this->get('State');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode('\n', $errors), 500);
		}

		// Add title and toolbar.
		$this->addToolbar();

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
		$isNew = ($this->item->id == 0);
		$canDo = ProfilesHelper::getActions('com_profiles', 'category', $this->item->id);

		// Disable menu
		Factory::getApplication()->input->set('hidemainmenu', true);

		// Set page title
		$title = ($isNew) ? Text::_('COM_PROFILES_CATEGORY_ADD') : Text::_('COM_PROFILES_CATEGORY_EDIT');
		ToolbarHelper::title(Text::_('COM_PROFILES') . ': ' . $title, 'location');

		// Add apply & save buttons
		if ($canDo->get('core.edit'))
		{
			ToolbarHelper::apply('category.apply');
			ToolbarHelper::save('category.save');
		}

		// Add save and new button
		if ($canDo->get('core.create'))
		{
			ToolbarHelper::save2new('category.save2new');
		}

		// Add cancel button
		ToolbarHelper::cancel('category.cancel', 'JTOOLBAR_CLOSE');
	}
}