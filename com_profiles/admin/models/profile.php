<?php
/**
 * @package    Profiles Component
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class ProfilesModelProfile extends AdminModel
{
	/**
	 * Imagefolder helper helper
	 *
	 * @var    new imageFolderHelper
	 *
	 * @since 1.0.0
	 */
	protected $imageFolderHelper = null;

	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @see     AdminModel
	 *
	 * @since   1.0.0
	 */
	public function __construct(array $config = array())
	{
		JLoader::register('imageFolderHelper', JPATH_PLUGINS . '/fieldtypes/ajaximage/helpers/imagefolder.php');
		$this->imageFolderHelper = new imageFolderHelper('images/profiles');

		parent::__construct($config);
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer $pk The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since 1.0.0
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Convert the metadata field to an array.
			$registry       = new Registry($item->metadata);
			$item->metadata = $registry->toArray();

			// Convert the contacts field to an array.
			$registry       = new Registry($item->contacts);
			$item->contacts = $registry->toArray();

			// Convert the attribs field to an array.
			$registry      = new Registry($item->attribs);
			$item->attribs = $registry->toArray();

			// Get Tags
			$item->tags = new TagsHelper;
			$item->tags->getTagIds($item->id, 'com_profiles.profile');

			$item->published = $item->state;
		}

		return $item;
	}

	/**
	 * Method to synchronize user and profiles
	 *
	 * @return  int count of items
	 *
	 * @since 1.0.0
	 */
	public function synchronizeItems()
	{
		$db = Factory::getDbo();

		$query = $db->getQuery(true)
			->select('*')
			->from('#__users');
		$db->setQuery($query);
		$users = $db->loadObjectList('id');

		$query = $db->getQuery(true)
			->select('id')
			->from('#__profiles');
		$db->setQuery($query);
		$profiles = $db->loadColumn();

		$synchronize = 0;

		foreach ($users as $id => &$user)
		{
			if (!in_array($id, $profiles))
			{
				$registry          = new Registry($user->params);
				$user->params      = $registry->toArray();
				$user->imagefolder = $this->imageFolderHelper->getItemImageFolder($id);
				$user              = ArrayHelper::fromObject($user);
				$this->save($user);

				$synchronize++;
			}
		}

		return $synchronize;
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array   $data     Data for the form.
	 * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return bool|JForm object on success, false on failure
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_profiles.profile', 'item', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since 1.0.0
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_profiles.edit.profile.data', array());
		if (empty($data))
		{
			$data = $this->getItem();

		}
		$this->preprocessData('com_profiles.profile', $data);

		return $data;
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string $type   The table type to instantiate
	 * @param   string $prefix A prefix for the table class name. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 * @since 1.0.0
	 */
	public function getTable($type = 'Profiles', $prefix = 'ProfilesTable', $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array $data The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since 1.0.0
	 */
	public function save($data)
	{
		$app    = Factory::getApplication();
		$pk     = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
		$filter = InputFilter::getInstance();
		$table  = $this->getTable();

		// Include the plugins for the save events.
		PluginHelper::importPlugin($this->events_map['save']);

		// Load the row if saving an existing type.
		if ($pk > 0)
		{
			$table->load($pk);
		}

		if (empty($data['region']))
		{
			$data['region'] = $app->input->cookie->get('region', '*');
		}

		$data['id']    = (!isset($data['id'])) ? 0 : $data['id'];
		$data['alias'] = (!isset($data['alias'])) ? '' : $data['alias'];
		// Check alias
		$alias = $this->checkAlias($data['id'], $data['alias']);
		if (!empty($alias->msg))
		{
			$app->enqueueMessage(Text::sprintf('COM_PROFILES_ERROR_ALIAS', $alias->msg),
				($alias->status == 'error') ? 'error' : 'warning');
		}

		$data['alias'] = $alias->data;

		if (isset($data['metadata']) && isset($data['metadata']['author']))
		{
			$data['metadata']['author'] = $filter->clean($data['metadata']['author'], 'TRIM');
		}

		if (isset($data['attribs']) && is_array($data['attribs']))
		{
			$registry        = new Registry($data['attribs']);
			$data['attribs'] = (string) $registry;
		}

		if (isset($data['metadata']) && is_array($data['metadata']))
		{
			$registry         = new Registry($data['metadata']);
			$data['metadata'] = (string) $registry;
		}

		if (isset($data['contacts']) && is_array($data['contacts']))
		{
			$registry         = new Registry($data['contacts']);
			$data['contacts'] = (string) $registry;
		}

		if (!isset($data['state']) && empty($data['block']))
		{
			$data['state'] = 1;
		}
		if (empty($data['created']))
		{
			$data['created'] = (!empty($data['registerDate'])) ? $data['registerDate'] : Factory::getDate()->toSql();
		}
		$data['modified'] = Factory::getDate()->toSql();

		$data['imagefolder'] = (!empty($data['imagefolder'])) ? $data['imagefolder'] :
			$this->imageFolderHelper->getItemImageFolder($data['id']);

		$data['tags'] = (!is_object($data['tags'])) ? $data['tags'] : array();
		if (!empty($data['tags']))
		{
			$table->newTags = $data['tags'];

			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('id', 'title'))
				->from('#__tags')
				->where('id IN (' . implode(',', $data['tags']) . ')');
			$db->setQuery($query);
			$tags        = $db->loadObjectList();
			$tags_search = array();
			$tags_map    = array();
			foreach ($tags as $tag)
			{
				$tags_search[$tag->id] = $tag->title;
				$tags_map[$tag->id]    = '[' . $tag->id . ']';
			}
			$data['tags_search'] = implode(', ', $tags_search);
			$data['tags_map']    = implode('', $tags_map);
		}
		else
		{
			$data['tags_search'] = '';
			$data['tags_map']    = '';
		}

		if (!empty($data['status']) && mb_strlen($data['status']) > 120)
		{
			$data['status'] = JHtmlString::truncate($data['status'], 197, true, false);
		}

		if (parent::save($data))
		{
			$id = $data['id'];

			// Save images
			$data['avatar']      = (!isset($data['avatar'])) ? '' : $data['avatar'];
			$data['header']      = (!isset($data['header'])) ? '' : $data['header'];
			$data['imagefolder'] = (!isset($data['imagefolder'])) ? '' : $data['imagefolder'];

			$this->imageFolderHelper->saveItemImages($id, $data['imagefolder'], '#__profiles', 'avatar', $data['avatar']);
			$this->imageFolderHelper->saveItemImages($id, $data['imagefolder'], '#__profiles', 'header', $data['header']);

			// Update contacts
			if (!empty($data['update_contacts']))
			{
				foreach ($data['update_contacts'] as $updateContact)
				{
					$table = '';
					if ($updateContact == 'board')
					{
						$table = '#__board_items';
					}
					if ($this->updateContacts($table, $id, $data['contacts']))
					{
						$app->enqueueMessage(Text::_('COM_PROFILES_UPDATE_CONTACTS_SUCCESS'));
					}
				}
			}
			if ($app->isAdmin() && !empty($data['return']))
			{
				$app->input->set('return', $data['return']);
			}

			if (!empty($data['job']) && !empty($data['job']['company_name']))
			{
				BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_companies/models');
				$companyModel = BaseDatabaseModel::getInstance('Company', 'CompaniesModel', array('ignore_request' => true));

				$company               = array();
				$company['name']       = $data['job']['company_name'];
				$company['position']   = $data['job']['position'];
				$company['as_company'] = $data['job']['as_company'];
				$company['state']      = $data['state'];
				$company['region']     = $data['region'];

				$companyModel->save($company);
			}

			return true;
		}

		return false;
	}

	/**
	 * Method to update user contacts in items
	 *
	 * @param string $table      Table name
	 * @param int    $created_by Author id
	 * @param string $value      Contacts value
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	protected function updateContacts($table = '', $created_by = 0, $value = '')
	{
		if (!empty($table) && !empty($created_by))
		{
			$update             = new stdClass();
			$update->created_by = $created_by;
			$update->contacts   = $value;

			return Factory::getDbo()->updateObject($table, $update, 'created_by');
		}

		return false;
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   array &$pks An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since 1.0.0
	 */
	public function delete(&$pks)
	{
		if (parent::delete($pks))
		{
			// Delete images
			foreach ($pks as $pk)
			{
				$this->imageFolderHelper->deleteItemImageFolder($pk);
			}

			return true;
		}

		return false;
	}

	/**
	 * Method to check alias
	 *
	 * @param  int    $id    Item Id
	 * @param  string $alias Item alias
	 *
	 * @return stdClass|string
	 *
	 * @since 1.0.0
	 */
	public function checkAlias($id = 0, $alias = null)
	{
		$response         = new stdClass();
		$response->status = 'success';
		$response->msg    = '';
		$response->data   = $alias;
		$default_alias    = 'id' . $id;
		if (empty($alias))
		{
			$response->data = $default_alias;

			return $response;
		}

		// Check idXXX
		preg_match('/^id(.*)/', $alias, $matches);
		$idFromAlias = (!empty($matches[1])) ? $matches[1] : false;
		if ($idFromAlias && $id != $idFromAlias)
		{
			$response->status = 'error';
			$response->msg    = Text::_('COM_PROFILES_ERROR_ALIAS_ID');
			$response->data   = $default_alias;

			return $response;
		}

		// Check numeric
		if (is_numeric($alias))
		{
			$response->status = 'error';
			$response->msg    = Text::_('COM_PROFILES_ERROR_ALIAS_NUMBER');
			$response->data   = $default_alias;

			return $response;
		}

		// Check slug
		if (Factory::getConfig()->get('unicodeslugs') == 1)
		{
			$slug = OutputFilter::stringURLUnicodeSlug($alias);
		}
		else
		{
			$slug = OutputFilter::stringURLSafe($alias);
		}

		if ($alias != $slug)
		{
			$response->msg  = Text::_('COM_PROFILES_ERROR_ALIAS_SLUG');
			$response->data = $slug;

			$alias = $slug;

		}

		// Check count
		if (mb_strlen($alias) < 5)
		{
			$response->status = 'error';
			$response->msg    = Text::_('COM_PROFILES_ERROR_ALIAS_LENGTH');
			$response->data   = $default_alias;

			return $response;
		}

		$table = $this->getTable();
		$table->load(array('alias' => $alias));
		if (!empty($table->id) && ($table->id != $id))
		{
			$response->status = 'error';
			$response->msg    = Text::_('COM_PROFILES_ERROR_ALIAS_EXIST');
			$response->data   = $default_alias;

			return $response;
		}

		return $response;
	}
}