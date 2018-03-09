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

class ProfilesModelProfile extends ItemModel
{
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

		$user = Factory::getUser();

		// Published state
		if ((!$user->authorise('core.manage', 'com_profiles')))
		{
			// Limit to published for people who can't edit or edit.state.
			$this->setState('filter.published', 1);
		}
		else
		{
			$this->setState('filter.published', array(0, 1));
		}

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
			$errorRedirect = Route::_(ProfilesHelperRoute::getProfilesRoute());
			$errorMsg      = Text::_('COM_PROFILER_ERROR_PROFILE_NOT_FOUND');
			try
			{
				$db   = $this->getDbo();
				$user = Factory::getUser();

				$query = $db->getQuery(true)
					->select('p.*')
					->from('#__profiles AS p')
					->where('p.id = ' . (int) $pk);

				// Join over the sessions.
				$offline      = (int) $component->get('offline_time', 5) * 60;
				$offline_time = Factory::getDate()->toUnix() - $offline;
				$query->select('(session.time IS NOT NULL) AS online')
					->join('LEFT', '#__session AS session ON session.userid = p.id AND session.time > ' . $offline_time);

				// Join over the regions.
				$query->select(array('r.id as region_id', 'r.name AS region_name', 'r.latitude as region_latitude',
					'r.longitude as region_longitude', 'r.zoom as region_zoom'))
					->join('LEFT', '#__regions AS r ON r.id = 
					(CASE p.region WHEN ' . $db->quote('*') . ' THEN 100 ELSE p.region END)');

				// Filter by published state.
				$published = $this->getState('filter.published');
				if (!empty($published))
				{
					if (is_numeric($published))
					{
						$query->where('( p.state = ' . (int) $published .
							' OR ( p.id = ' . $user->id . ' AND p.state IN (0,1)))');
					}
					elseif (is_array($published))
					{
						$query->where('p.state IN (' . implode(',', $published) . ')');
					}
				}

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

				$avatar = (!empty($data->avatar) && JFile::exists(JPATH_ROOT . '/' . $data->avatar)) ?
					$data->avatar : 'media/com_profiles/images/noavatar.jpg';

				$data->avatar = Uri::root(true) . '/' . $avatar;

				$header = (!empty($data->header) && JFile::exists(JPATH_ROOT . '/' . $data->header)) ?
					$data->header : 'media/com_profiles/images/noheader.jpg';

				$data->header = Uri::root(true) . '/' . $header;

				// Convert the metadata field
				$data->metadata = new Registry($data->metadata);

				// Get Tags
				$data->tags = new TagsHelper;
				$data->tags->getItemTags('com_profiles.profile', $data->id);

				// Convert parameter fields to objects.
				$registry     = new Registry($data->attribs);
				$data->params = clone $this->getState('params');
				$data->params->merge($registry);

				// If no access, the layout takes some responsibility for display of limited information.
				$data->params->set('access-view', in_array($data->access, Factory::getUser()->getAuthorisedViewLevels()));

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
}