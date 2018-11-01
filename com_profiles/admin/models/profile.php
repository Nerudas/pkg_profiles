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
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

JLoader::register('FieldTypesHelperFolder', JPATH_PLUGINS . '/system/fieldtypes/helpers/folder.php');

class ProfilesModelProfile extends AdminModel
{
	/**
	 * Images root path
	 *
	 * @var string
	 *
	 * @since 1.5.0
	 */
	protected $images_root = 'images/profiles';

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

			// Convert the item_tags field value to array.
			$item->item_tags = explode(',', $item->item_tags);
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
	public function getTable($type = 'Profiles', $prefix = 'ProfilesTable', $config = array())
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
		$form = $this->loadForm('com_profiles.profile', 'profile', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to synchronize users and profiles
	 *
	 * @return  array|JException  Profiles ids array on success, JException instance on error
	 *
	 * @since 1.5.0
	 */
	public function synchronize()
	{
		// Access checks.
		if (!Factory::getUser()->authorise('core.create', 'com_profiles'))
		{
			throw new Exception(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
		}

		// Synchronize profiles
		try
		{
			$db = Factory::getDbo();

			// Get users
			$query = $db->getQuery(true)
				->select('*')
				->from('#__users');
			$db->setQuery($query);
			$users = $db->loadObjectList('id');

			// Get profiles ids
			$query = $db->getQuery(true)
				->select('id')
				->from('#__profiles');
			$db->setQuery($query);
			$profiles = $db->loadColumn();

			// Synchronize
			$newIDs       = array();
			$folderHelper = new FieldTypesHelperFolder();
			foreach ($users as $id => $user)
			{
				if (!in_array($id, $profiles))
				{
					// Prepare profile object
					$profile           = new stdClass();
					$profile->id       = $user->id;
					$profile->name     = $user->name;
					$profile->alias    = 'id' . $user->id;
					$profile->state    = ($user->block == 1) ? -2 : 1;
					$profile->in_work  = 0;
					$profile->type     = 'natural';
					$profile->created  = $user->registerDate;
					$profile->modified = Factory::getDate()->toSql();
					$profile->avatar   = 0;
					$profile->access   = 1;
					$profile->hits     = 0;
					$profile->region   = '*';

					// Insert profile
					if ($db->insertObject('#__profiles', $profile))
					{
						// Create images folder
						$folderHelper->getItemFolder($id, $this->images_root);

						$newIDs[] = $id;
					}
				}
			}

			// Clear cache
			$this->cleanCache();

			return $newIDs;
		}
		catch (Exception $e)
		{
			throw new Exception(Text::_($e->getMessage()));
		}
	}

}