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

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

class JFormFieldCategories extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var string
	 *
	 * @since 1.5.0
	 */
	protected $type = 'categories';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since 1.5.0
	 */
	protected function getOptions()
	{
		// Get categories
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(array('id', 'title', 'parent_id', 'level'))
			->from('#__profiles_categories')
			->order($db->escape('lft') . ' ' . $db->escape('asc'));
		$db->setQuery($query);
		$categories = $db->loadObjectList('id');

		// Check admin category view
		$app           = Factory::getApplication();
		$component     = $app->input->get('option', 'com_centers');
		$view          = $app->input->get('view', 'district');
		$id            = $app->input->getInt('id', 0);
		$category_view = ($app->isAdmin() && $component == 'com_profiles' && $view == 'category');

		// Unset root category
		if ($category_view && ($id == 0 || $id > 3))
		{
			unset($categories[1]);
		}
		if ($category_view && $id == 1)
		{
			$root            = new stdClass();
			$root->id        = 0;
			$root->title     = Text::_('JGLOBAL_ROOT');
			$root->parent_id = 0;
			$root->level     = 0;

			$categories[0] = $root;
		}

		// Prepare options
		$options = parent::getOptions();
		foreach ($categories as $i => $category)
		{
			$option        = new stdClass();
			$option->value = $category->id;
			$option->text  = $category->title;

			if ($category->level > 1)
			{
				$option->text = str_repeat('- ', ($category->level - 1)) . $option->text;
			}

			if ($category_view && $id !== 0 && ($category->id == $id || $category->parent_id == $id))
			{
				$option->disable = true;
			}

			if ($id == 0 && $category->id = 1)
			{
				$option->selected = true;
			}

			$options[] = $option;
		}

		return $options;
	}
}
