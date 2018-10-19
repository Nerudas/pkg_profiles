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
}