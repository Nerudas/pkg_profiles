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

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class ProfilesModelCategory extends AdminModel
{
	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string $type   The table type to instantiate
	 * @param   string $prefix A prefix for the table class name. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 *
	 * @since 1.5.0
	 */
	public function getTable($type = 'Categories', $prefix = 'ProfilesTable', $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array   $data     Data for the form.
	 * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return Joomla\CMS\Form\Form|boolean  A Form object on success, false on failure
	 *
	 * @since 1.5.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_profiles.category', 'category', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}


	/**
	 * Method rebuild the entire nested set tree.
	 *
	 * @return  boolean  False on failure or error, true otherwise.
	 *
	 * @since 1.5.0
	 */
	public function rebuild()
	{
		$table = $this->getTable();
		if (!$table->rebuild())
		{
			$this->setError($table->getError());

			return false;
		}

		$this->cleanCache();

		return true;
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array   &$pks  A list of the primary keys to change.
	 * @param   integer $value The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since 1.5.0
	 */
	public function publish(&$pks, $value = 1)
	{
		// Check base categories
		$showWarning = false;
		foreach ($pks as $key => $pk)
		{
			if (in_array($pk, array(1, 2, 3)))
			{
				$showWarning = true;
				unset($pks[$key]);
			}
		}
		if ($showWarning)
		{
			Factory::getApplication()->enqueueMessage(Text::_('COM_PROFILES_ERROR_BASE_CATEGORIES_STATE'), 'warning');
		}

		return parent::publish($pks, $value);
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   array &$pks An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since 1.5.0
	 */
	public function delete(&$pks)
	{
		// Check base categories
		$showWarning = false;
		foreach ($pks as $key => $pk)
		{
			if (in_array($pk, array(1, 2, 3)))
			{
				$showWarning = true;
				unset($pks[$key]);
			}
		}
		if ($showWarning)
		{
			Factory::getApplication()->enqueueMessage(Text::_('COM_PROFILES_ERROR_BASE_CATEGORIES_STATE'), 'warning');
		}

		return parent::delete($pks);
	}
}