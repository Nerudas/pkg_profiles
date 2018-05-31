<?php
/**
 * @package    Profiles Component
 * @version    1.0.10
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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Application\SiteApplication;

class ProfilesModelProfile extends AdminModel
{
	/**
	 * Profile jobs array
	 *
	 * @var    array
	 *
	 * @since 1.0.0
	 */
	protected $_jobs = null;

	/**
	 * Profile information
	 *
	 * @var    array
	 *
	 * @since 1.0.0
	 */
	protected $_information = null;

	/**
	 * Imagefolder helper
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
			// Convert the notes field to an array.
			$registry    = new Registry($item->notes);
			$item->notes = $registry->toArray();

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

			// Get jobs
			$item->jobs = $this->getJobs($item->id);

			// Get Info
			$item->information = $this->getInformation($item);

		}

		return $item;
	}

	/**
	 * Method to get information.
	 *
	 * @param   object $item Profile object
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function getInformation($item)
	{
		if (!is_array($this->_information))
		{
			$information = array();
			if (!empty($item->id))
			{
				$db = Factory::getDbo();
				BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/models', 'ProfilesModel');
				$userModel = BaseDatabaseModel::getInstance('User', 'ProfilesModel', array('ignore_request' => false));
				$userInfo  = Factory::getUser($item->id);

				$information['id']   = $item->id;
				$information['name'] = $item->name;

				JLoader::register('ProfilesHelperRoute', JPATH_SITE . '/components/com_profiles/helpers/route.php');
				$siteRouter          = SiteApplication::getRouter();
				$link                = $siteRouter->build(ProfilesHelperRoute::getProfileRoute($item->id))->toString();
				$information['link'] = str_replace('administrator/', '', $link);

				$avatar                = (!empty($item->avatar) && JFile::exists(JPATH_ROOT . '/' . $item->avatar)) ?
					$item->avatar : 'media/com_profiles/images/no-avatar.jpg';
				$information['avatar'] = Uri::root(true) . '/' . $avatar;

				$information['email'] = $userInfo->email;


				$phone                = $userModel->getPhone($item->id);
				$information['phone'] = ($phone) ? $phone->code . $phone->number : '';

				$socials                      = $userModel->getSocial($item->id);
				$information['vk']            = (!empty($socials['vk'])) ? $socials['vk']->social_id : '';
				$information['facebook']      = (!empty($socials['facebook'])) ? $socials['facebook']->social_id : '';
				$information['odnoklassniki'] = (!empty($socials['odnoklassniki'])) ? $socials['odnoklassniki']->social_id : '';

				$contacts = (!empty($item->contacts)) ? $item->contacts : array();
				if (!empty($contacts['email']))
				{
					$information['contacts_email'] = $contacts['email'];
				}
				if (!empty($contacts['phones']))
				{
					$phones = array();
					foreach ($contacts['phones'] as $phone)
					{
						$phones[] = $phone['code'] . $phone['number'];
					}
					$information['contacts_phones'] = implode(',', $phones);
				}
				if (!empty($contacts['site']))
				{
					$information['contacts_site'] = $contacts['site'];
				}
				if (!empty($contacts['vk']))
				{
					$information['contacts_vk'] = $contacts['vk'];
				}
				if (!empty($contacts['facebook']))
				{
					$information['contacts_facebook'] = $contacts['facebook'];
				}
				if (!empty($contacts['instagram']))
				{
					$information['contacts_instagram'] = $contacts['instagram'];
				}
				if (!empty($contacts['odnoklassniki']))
				{
					$information['contacts_odnoklassniki'] = $contacts['odnoklassniki'];
				}

				// Get Job
				$query = $db->getQuery(true)
					->select(array('company.id as id', 'company.name', 'company.logo', 'employees.position'))
					->from($db->quoteName('#__companies_employees', 'employees'))
					->join('LEFT', '#__companies AS company ON company.id = employees.company_id')
					->where('employees.user_id = ' . $item->id)
					->where($db->quoteName('employees.key') . ' = ' . $db->quote(''))
					->where('company.state = 1');
				$db->setQuery($query);
				$job = $db->loadObject();

				$information['job_id'] = (!empty($job)) ? $job->id : '';
				if (!empty($job->id))
				{
					$information['job_name'] = $job->name;

					JLoader::register('CompaniesHelperRoute', JPATH_SITE . '/components/com_companies/helpers/route.php');
					$job_link                    = $siteRouter->build(CompaniesHelperRoute::getCompanyRoute($job->id))->toString();
					$information['job_link']     = str_replace('administrator/', '', $job_link);
					$information['job_position'] = $job->position;
					$information['job_logo']     = (!empty($job->logo) && JFile::exists(JPATH_ROOT . '/' . $job->logo)) ?
						Uri::root(true) . '/' . $job->logo : '';
				}

				// Check as_company
				$query = $db->getQuery(true)
					->select('COUNT(*)')
					->from($db->quoteName('#__companies_employees', 'employees'))
					->where('employees.user_id = ' . $item->id)
					->where('employees.as_company = ' . 1)
					->where($db->quoteName('employees.key') . ' = ' . $db->quote(''));
				$db->setQuery($query);
				$information['as_company'] = ($db->loadResult() > 0);

				// Get publishing info
				$information['registerDate']  = $userInfo->registerDate;
				$information['lastvisitDate'] = $userInfo->lastvisitDate;
				$information['modified']      = $item->modified;

				if ($item->region == '*')
				{
					$information['region'] = Text::_('JGLOBAL_FIELD_REGIONS_ALL');
				}
				else
				{
					$query = $db->getQuery(true)
						->select('name')
						->from('#__regions')
						->where('id = ' . $item->region);
					$db->setQuery($query);
					$region                = $db->loadResult();
					$information['region'] = (!empty($region)) ? $region : Text::_('JGLOBAL_FIELD_REGIONS_NULL');
				}

				// Get Tags
				$tags = '';
				if ((!empty($item->tags->tags)))
				{
					$query = $db->getQuery(true)
						->select('title')
						->from('#__tags')
						->where('id IN (' . $item->tags->tags . ')');
					$db->setQuery($query);
					$tags = implode(',', $db->loadColumn());
				}
				$information['tags'] = $tags;
			}
			$this->_information = $information;
		}

		return $this->_information;
	}

	/**
	 * Method to get jobs.
	 *
	 * @param   integer $pk The id of the primary key.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function getJobs($pk = null)
	{
		$app = Factory::getApplication();
		if (!is_array($this->_jobs))
		{
			$pk = (!empty($pk)) ? $pk : (int) $this->getState('profile.id');

			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('ce.company_id as id', 'ce.position', 'c.name', 'c.logo', 'ce.key', 'ce.as_company'))
				->from($db->quoteName('#__companies_employees', 'ce'))
				->join('LEFT', '#__companies AS c ON c.id = ce.company_id')
				->where('user_id = ' . $pk);
			$db->setQuery($query);
			$companies = $db->loadObjectList('id');

			JLoader::register('CompaniesHelperEmployees', JPATH_SITE . '/components/com_companies/helpers/employees.php');
			JLoader::register('CompaniesHelperRoute', JPATH_SITE . '/components/com_companies/helpers/route.php');
			foreach ($companies as &$company)
			{
				$company->logo = (!empty($company->logo) && JFile::exists(JPATH_ROOT . '/' . $company->logo)) ?
					Uri::root(true) . '/' . $company->logo : false;

				$company->confirm = CompaniesHelperEmployees::keyCheck($company->key, $company->id, $pk);
				unset($company->key);

				$link = 'index.php?option=com_companies&view=company&layout=edit&id=' . $company->id;
				if ($app->isSite())
				{
					$link = ($company->confirm == 'confirm') ? Route::_(CompaniesHelperRoute::getFormRoute($company->id))
						: Route::_(CompaniesHelperRoute::getCompanyRoute($company->id));
				}
				$company->link = $link;

				$company->as_company = ($company->as_company == 1);
			}

			$this->_jobs = $companies;
		}

		return $this->_jobs;
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

		if (isset($data['notes']) && is_array($data['notes']))
		{
			$registry      = new Registry($data['notes']);
			$data['notes'] = (string) $registry;
		}

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

		if (empty($data['created']))
		{
			$data['created'] = (!empty($data['registerDate'])) ? $data['registerDate'] : Factory::getDate()->toSql();
		}
		$data['modified'] = Factory::getDate()->toSql();

		$data['imagefolder'] = (!empty($data['imagefolder'])) ? $data['imagefolder'] :
			$this->imageFolderHelper->getItemImageFolder($data['id']);

		$data['tags'] = (!empty($data['tags']) && !is_object($data['tags'])) ? $data['tags'] : array();
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
			$data['imagefolder'] = (!empty($data['imagefolder'])) ? $data['imagefolder'] :
				$this->imageFolderHelper->getItemImageFolder($id);
			if (isset($data['avatar']))
			{
				$this->imageFolderHelper->saveItemImages($id, $data['imagefolder'], '#__profiles', 'avatar', $data['avatar']);
			}
			if (isset($data['header']))
			{
				$this->imageFolderHelper->saveItemImages($id, $data['imagefolder'], '#__profiles', 'header', $data['header']);
			}

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

			if (!empty($data['job']) && !empty($data['job']['company_name']))
			{
				BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_companies/models');
				$companyModel = BaseDatabaseModel::getInstance('Company', 'CompaniesModel', array('ignore_request' => true));

				$company               = array();
				$company['name']       = $data['job']['company_name'];
				$company['created_by'] = $id;
				$company['position']   = $data['job']['position'];
				$company['as_company'] = $data['job']['as_company'];
				$company['state']      = 1;
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

			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__companies_employees'))
				->where($db->quoteName('user_id') . ' IN (' . implode(',', $pks) . ')');
			$db->setQuery($query)->execute();

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

	/**
	 * Method to set in_work to one or more records.
	 *
	 * @param   array &$pks An array of record primary keys.
	 *
	 * @return true
	 *
	 * @since 1.0.0
	 */
	public function toWork($pks = array())
	{
		try
		{
			$db = $this->getDbo();
			foreach ($pks as $pk)
			{
				$update          = new stdClass();
				$update->id      = $pk;
				$update->in_work = 1;

				$db->updateObject('#__profiles', $update, 'id');
			}
		}
		catch (Exception $e)
		{
			$this->setError($e);

			return false;
		}

		return true;
	}

	/**
	 * Method to unset in_work to one or more records.
	 *
	 * @param   array &$pks An array of record primary keys.
	 *
	 * @return true
	 *
	 * @since 1.0.7
	 */
	public function unWork($pks = array())
	{
		try
		{
			$db = $this->getDbo();
			foreach ($pks as $pk)
			{
				$update          = new stdClass();
				$update->id      = $pk;
				$update->in_work = 0;

				$db->updateObject('#__profiles', $update, 'id');
			}
		}
		catch (Exception $e)
		{
			$this->setError($e);

			return false;
		}

		return true;
	}
}