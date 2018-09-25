<?php
/**
 * @package    Profiles Component
 * @version    1.3.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;

JLoader::register('FieldTypesFilesHelper', JPATH_PLUGINS . '/fieldtypes/files/helper.php');


class ProfilesModelProfile extends ItemModel
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
	 * Comments
	 *
	 * @var    object
	 * @since  1.1.0
	 */
	protected $_comments = array();

	/**
	 * Model context string.
	 *
	 * @var        string
	 *
	 * @since 1.0.0
	 */
	protected $_context = 'com_profiles.profile';

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function populateState()
	{
		$app = Factory::getApplication('site');

		// Load state from the request.
		$pk = $app->input->getInt('id');
		$this->setState('profile.id', $pk);

		$offset = $app->input->getUInt('limitstart');
		$this->setState('list.offset', $offset);

		// Load the parameters.
		$params = $app->getParams();
		$this->setState('params', $params);

	}

	/**
	 * Method to get type data for the current type
	 *
	 * @param   integer $pk The id of the type.
	 *
	 * @return  mixed object|false
	 *
	 * @since 1.0.0
	 */
	public function getItem($pk = null)
	{
		$app       = Factory::getApplication();
		$pk        = (!empty($pk)) ? $pk : (int) $this->getState('profile.id');
		$component = ComponentHelper::getParams('com_profiles');

		if (!isset($this->_item[$pk]))
		{
			$errorRedirect = Route::_(ProfilesHelperRoute::getListRoute());
			$errorMsg      = Text::_('COM_PROFILES_ERROR_PROFILE_NOT_FOUND');
			try
			{
				$db   = $this->getDbo();
				$user = Factory::getUser();

				$query = $db->getQuery(true)
					->select('p.*')
					->from('#__profiles AS p')
					->where('p.id = ' . (int) $pk);

				// Join over the users
				$query->join('LEFT', '#__users AS user ON user.id = p.id')
					->where('user.block = 0')
					->where('user.activation IN (' . $db->quote('') . ', ' . $db->quote('0') . ')');

				// Join over the discussions.
				$query->select('(CASE WHEN dt.id IS NOT NULL THEN dt.id ELSE 0 END) as discussions_topic_id')
					->join('LEFT', '#__discussions_topics AS dt ON dt.item_id = p.id AND ' .
						$db->quoteName('dt.context') . ' = ' . $db->quote('com_profiles.profile'));

				// Join over the sessions.
				$offline      = (int) $component->get('offline_time', 5) * 60;
				$offline_time = Factory::getDate()->toUnix() - $offline;
				$query->select('(session.time IS NOT NULL) AS online')
					->join('LEFT', '#__session AS session ON session.userid = p.id AND session.time > ' . $offline_time);

				// Join over the regions.
				$query->select(array('r.id as region_id', 'r.name as region_name'))
					->join('LEFT', '#__location_regions AS r ON r.id = p.region');

				// Join over the companies.
				$query->select(array('(company.id IS NOT NULL) AS job', 'company.id as job_id', 'company.name as job_name', 'employees.position'))
					->join('LEFT', '#__companies_employees AS employees ON employees.user_id = p.id AND ' .
						$db->quoteName('employees.key') . ' = ' . $db->quote(''))
					->join('LEFT', '#__companies AS company ON company.id = employees.company_id AND company.state = 1');


				$db->setQuery($query);
				$data = $db->loadObject();

				if (empty($data))
				{
					$app->redirect($url = $errorRedirect, $msg = $errorMsg, $msgType = 'error', $moved = true);

					return false;
				}

				// Link
				$data->link     = Route::_(ProfilesHelperRoute::getProfileRoute($data->id));
				$data->editLink = (!$user->guest && $user->id == $data->id) ?
					Route::_('index.php?option=com_users&view=profile&layout=edit') : '';

				// Convert the contacts field from json.
				$data->contacts = new Registry($data->contacts);
				if ($phones = $data->contacts->get('phones'))
				{
					$phones = ArrayHelper::fromObject($phones, false);
					$data->contacts->set('phones', $phones);
				}

				$imagesHelper = new FieldTypesFilesHelper();
				$imagesFolder = 'images/profiles/' . $data->id;

				$data->avatar = $imagesHelper->getImage('avatar', $imagesFolder, 'media/com_profiles/images/no-avatar.jpg', false);
				$data->header = $imagesHelper->getImage('header', $imagesFolder, 'media/com_profiles/images/no-header.jpg', false);

				if ($data->job)
				{
					$data->job_logo = $imagesHelper->getImage('header', 'images/companies/' . $data->job_id, false, false);
					$data->job_link = Route::_(CompaniesHelperRoute::getCompanyRoute($data->job_id));
				}

				// Convert the metadata field
				$data->metadata = new Registry($data->metadata);
				$data->metadata->set('image', $imagesHelper->getImage('meta', $imagesFolder, false, false));

				// Get Tags
				$data->tags = new TagsHelper;
				$data->tags->getItemTags('com_profiles.profile', $data->id);

				// Convert parameter fields to objects.
				$registry     = new Registry($data->attribs);
				$data->params = clone $this->getState('params');
				$data->params->merge($registry);

				$this->_item[$pk] = $data;
			}
			catch (Exception $e)
			{
				if ($e->getCode() == 404)
				{
					$app->redirect($url = $errorRedirect, $msg = $errorMsg, $msgType = 'error', $moved = true);
				}
				else
				{
					$this->setError($e);
					$this->_item[$pk] = false;
				}
			}
		}

		return $this->_item[$pk];
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
		if (!is_array($this->_jobs))
		{
			$pk = (!empty($pk)) ? $pk : (int) $this->getState('profile.id');

			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('ce.company_id as id', 'ce.position', 'c.name', 'c.logo', 'c.about', 'c.hits', 'c.region'))
				->from($db->quoteName('#__companies_employees', 'ce'))
				->join('LEFT', '#__companies AS c ON c.id = ce.company_id')
				->where('user_id = ' . $pk);

			// Join over the regions.
			$query->select(array('r.id as region_id', 'r.name as region_name'))
				->join('LEFT', '#__location_regions AS r ON r.id = c.region');

			$db->setQuery($query);
			$companies = $db->loadObjectList('id');

			foreach ($companies as &$company)
			{
				$company->logo = (!empty($company->logo) && JFile::exists(JPATH_ROOT . '/' . $company->logo)) ?
					Uri::root(true) . '/' . $company->logo : false;

				// Get region
				$company->region_icon = (!empty($company->region_icon) && JFile::exists(JPATH_ROOT . '/' . $company->region_icon)) ?
					Uri::root(true) . $company->region_icon : false;
				if ($company->region == '*')
				{
					$company->region_icon = false;
					$company->region_name = Text::_('JGLOBAL_FIELD_REGIONS_ALL');
				}

				$company->link = Route::_(CompaniesHelperRoute::getCompanyRoute($company->id));
			}

			$this->_jobs = $companies;
		}

		return $this->_jobs;
	}

	/**
	 * Increment the hit counter for the article.
	 *
	 * @param   integer $pk Optional primary key of the article to increment.
	 *
	 * @return  boolean  True if successful; false otherwise and internal error set.
	 *
	 * @since 1.0.0
	 */
	public function hit($pk = 0)
	{
		$app      = Factory::getApplication();
		$hitcount = $app->input->getInt('hitcount', 1);

		if ($hitcount)
		{
			$pk = (!empty($pk)) ? $pk : (int) $this->getState('profile.id');

			$table = Table::getInstance('Profiles', 'ProfilesTable');
			$table->load($pk);
			$table->hit($pk);
		}

		return true;
	}

	/**
	 * Method to get Related items
	 *
	 * @param int $pk Item ID
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getComments($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('item.id');
		if (!isset($this->_comments[$pk]))
		{
			$item = $this->getItem($pk);

			JLoader::register('DiscussionsHelperTopic', JPATH_SITE . '/components/com_discussions/helpers/topic.php');
			$data             = array();
			$data['context']  = 'com_profiles.profile';
			$data['item_id']  = $item->id;
			$data['topic_id'] = $item->discussions_topic_id;

			$data['create_topic'] = array(
				'context'    => 'com_profiles.profile',
				'item_id'    => $item->id,
				'title'      => $item->name,
				'text'       => '{profile id="' . $item->id . '" layout="discussions"}',
				'state'      => 1,
				'access'     => 1,
				'created_by' => $item->id,
				'region'     => $item->region,
				'tags'       => (!empty($item->tags->itemTags)) ?
					implode(',', ArrayHelper::getColumn($item->tags->itemTags, 'tag_id')) : ''
			);

			$comments             = DiscussionsHelperTopic::getIntegration($data);
			$this->_comments[$pk] = $comments;
		}

		return $this->_comments[$pk];
	}
}