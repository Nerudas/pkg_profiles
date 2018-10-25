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
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Table\Table;
use Joomla\String\StringHelper;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

JLoader::register('FieldTypesHelperFolder', JPATH_PLUGINS . '/system/fieldtypes/helpers/folder.php');

class ProfilesModelCategory extends AdminModel
{
	/**
	 * Base categories ids
	 *
	 * @var array
	 *
	 * @since 1.5.0
	 */
	protected $baseCategories = array(1, 2, 3);

	/**
	 * Images root path
	 *
	 * @var string
	 *
	 * @since 1.5.0
	 */
	protected $images_root = 'images/profiles/categories';

	/**
	 * Method to get a single record.
	 *
	 * @param   integer $pk The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since 1.5.0
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Convert the params field value to array.
			$registry     = new Registry($item->params);
			$item->params = $registry->toArray();

			// Convert the metadata field value to array.
			$registry       = new Registry($item->metadata);
			$item->metadata = $registry->toArray();

			// Convert the items_tags field value to array.
			$item->items_tags = explode(',', $item->items_tags);
		}

		return $item;
	}

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

		// Get item id
		$id = (int) $this->getState('category.id', Factory::getApplication()->input->get('id', 0));

		// Modify the form based on Edit State access controls.
		if ($id != 0 && !Factory::getUser()->authorise('core.edit.state', 'com_profiles.category.' . $id))
		{
			$form->setFieldAttribute('state', 'disabled', 'true');
			$form->setFieldAttribute('state', 'filter', 'unset');
		}

		/// Modify the form based on base categories
		if (in_array($id, $this->baseCategories))
		{
			// Set readonly
			$form->setFieldAttribute('state', 'readonly', 'true');
			$form->setFieldAttribute('parent_id', 'readonly', 'true');
		}

		// Set images_folder field root attribute
		$form->setFieldAttribute('images_folder', 'root', $this->images_root);

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since 1.5.0
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_profiles.edit.category.data', array());
		if (empty($data))
		{
			$data = $this->getItem();
		}
		$this->preprocessData('com_profiles.category', $data);

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array $data The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since 1.5.0
	 */
	public function save($data)
	{
		$pk         = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
		$table      = $this->getTable();
		$isNew      = true;
		$context    = $this->option . '.' . $this->name;
		$dispatcher = JEventDispatcher::getInstance();

		// Include plugins for save events.
		PluginHelper::importPlugin($this->events_map['save']);

		// Load the row if saving an existing item.
		if ($pk > 0)
		{
			$table->load($pk);
			$isNew = false;
		}

		// Prepare alias field data.
		$alias = (!empty($data['alias'])) ? $data['alias'] : $data['title'];
		if (Factory::getConfig()->get('unicodeslugs') == 1)
		{
			$alias = OutputFilter::stringURLUnicodeSlug($alias);
		}
		else
		{
			$alias = OutputFilter::stringURLSafe($alias);
		}

		// Check alias is already exist
		$checkAlias = $this->getTable();
		$checkAlias->load(array('alias' => $alias));
		if (!empty($checkAlias->id) && ($checkAlias->id != $pk || $isNew))
		{
			$alias = $this->generateNewAlias($alias);
			Factory::getApplication()->enqueueMessage(Text::_('COM_PROFILES_ERROR_ALIAS_EXIST'), 'warning');
		}
		$data['alias'] = $alias;

		// Prepare params field data.
		if (isset($data['params']))
		{
			$registry       = new Registry($data['params']);
			$data['params'] = $registry->toString('json', array('bitmask' => JSON_UNESCAPED_UNICODE));
		}

		// Prepare metadata field data.
		if (isset($data['metadata']))
		{
			$registry         = new Registry($data['metadata']);
			$data['metadata'] = $registry->toString('json', array('bitmask' => JSON_UNESCAPED_UNICODE));
		}

		// Prepare items_tags field data.
		if (isset($data['items_tags']))
		{
			$data['items_tags'] = implode(',', $data['items_tags']);
		}

		// Set new parent id if parent id not matched OR while New.
		if ($table->parent_id != $data['parent_id'] || $data['id'] == 0)
		{
			$table->setLocation($data['parent_id'], 'last-child');
		}

		// Bind data.
		if (!$table->bind($data))
		{
			$this->setError($table->getError());

			return false;
		}

		// Check data.
		if (!$table->check())
		{
			$this->setError($table->getError());

			return false;
		}

		// Trigger before save event.
		$result = $dispatcher->trigger($this->event_before_save, array($context, &$table, $isNew, $data));
		if (in_array(false, $result, true))
		{
			$this->setError($table->getError());

			return false;
		}

		// Store data.
		if (!$table->store())
		{
			$this->setError($table->getError());

			return false;
		}

		// Trigger after save event.
		$dispatcher->trigger($this->event_after_save, array($context, &$table, $isNew, $data));

		// Rebuild path.
		if (!$table->rebuildPath($table->id))
		{
			$this->setError($table->getError());

			return false;
		}

		// Rebuild children paths.
		if (!$table->rebuild($table->id, $table->lft, $table->level, $table->path))
		{
			$this->setError($table->getError());

			return false;
		}

		// Set id state
		$id = $table->id;
		$this->setState($this->getName() . '.id', $id);

		// Clear cache
		$this->cleanCache();

		// Move images folder if new item
		if ($isNew && !empty($data['images_folder']))
		{
			$folderHelper = new FieldTypesHelperFolder();
			$folderHelper->moveTemporaryFolder($data['images_folder'], $id, $this->images_root);
		}

		return $id;
	}


	/**
	 * Method to generate new alias if alias already exist
	 *
	 * @param   string $alias The alias.
	 *
	 * @return  string  Contains the modified title and alias.
	 *
	 * @since 1.5.0
	 */
	protected function generateNewAlias($alias)
	{
		$table = $this->getTable();
		while ($table->load(array('alias' => $alias)))
		{
			$alias = StringHelper::increment($alias, 'dash');
		}

		return $alias;
	}

	/**
	 * Method to save the reordered nested set tree.
	 * First we save the new order values in the lft values of the changed ids.
	 * Then we invoke the table rebuild to implement the new ordering.
	 *
	 * @param   array   $idArray   An array of primary key ids.
	 * @param   integer $lft_array The lft value
	 *
	 * @return  boolean  False on failure or error, True otherwise
	 *
	 * @since 1.5.0
	 */
	public function saveorder($idArray = null, $lft_array = null)
	{
		$table = $this->getTable();
		if (!$table->saveorder($idArray, $lft_array))
		{
			$this->setError($table->getError());

			return false;
		}

		$this->cleanCache();

		return true;
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
			if (in_array($pk, $this->baseCategories))
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
			if (in_array($pk, $this->baseCategories))
			{
				$showWarning = true;
				unset($pks[$key]);
			}
		}
		if ($showWarning)
		{
			Factory::getApplication()->enqueueMessage(Text::_('COM_PROFILES_ERROR_BASE_CATEGORIES_STATE'), 'warning');
		}

		// Delete images
		$folderHelper = new FieldTypesHelperFolder();
		foreach ($pks as $pk)
		{
			$folderHelper->deleteItemFolder($pk, $this->images_root);
		}

		return parent::delete($pks);
	}
}