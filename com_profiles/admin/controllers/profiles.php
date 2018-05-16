<?php
/**
 * @package    Profiles Component
 * @version    1.0.8
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Utilities\ArrayHelper;

JLoader::register('UsersControllerUsers', JPATH_ADMINISTRATOR . '/components/com_users/controllers/users.php');


class ProfilesControllerProfiles extends UsersControllerUsers
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since 1.0.0
	 */
	protected $text_prefix = 'COM_PROFILES';

	/**
	 *
	 * Proxy for getModel.
	 *
	 * @param   string $name   The model name. Optional.
	 * @param   string $prefix The class prefix. Optional.
	 * @param   array  $config The array of possible config values. Optional.
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getModel($name = 'User', $prefix = 'UsersModel', $config = array('ignore_request' => true))
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_users/models', 'UsersModel');

		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Method to change the block status on a record.
	 *
	 * @return  void
	 *
	 * @since   1.0.3
	 */
	public function changeBlock()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$ids    = $this->input->get('cid', array(), 'array');
		$values = array('block' => 1, 'unblock' => 0);
		$task   = $this->getTask();
		$value  = ArrayHelper::getValue($values, $task, 0, 'int');

		if (empty($ids))
		{
			JError::raiseWarning(500, Text::_('COM_PROFILES_ERROR_NO_ITEM_SELECTED'));
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Change the state of the records.
			if (!$model->block($ids, $value))
			{
				JError::raiseWarning(500, $model->getError());
			}
			else
			{
				if ($value == 1)
				{
					$this->setMessage(Text::plural('COM_PROFILES_N_ITEMS_BLOCKED', count($ids)));
				}
				elseif ($value == 0)
				{
					$this->setMessage(Text::plural('COM_PROFILES_N_ITEMS_UNBLOCKED', count($ids)));
				}
			}
		}

		$this->setRedirect('index.php?option=com_profiles&view=profiles');
	}


	/**
	 * Method to activate a record.
	 *
	 * @return  void
	 *
	 * @since   1.0.3
	 */
	public function activate()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$ids = $this->input->get('cid', array(), 'array');

		if (empty($ids))
		{
			JError::raiseWarning(500, Text::_('COM_PROFILES_ERROR_NO_ITEM_SELECTED'));
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Change the state of the records.
			if (!$model->activate($ids))
			{
				JError::raiseWarning(500, $model->getError());
			}
			else
			{
				$this->setMessage(Text::plural('COM_PROFILES_N_ITEMS_ACTIVATED', count($ids)));
			}
		}

		$this->setRedirect('index.php?option=com_profiles&view=profiles');
	}


	/**
	 * Method to set in_work to one or more records.
	 *
	 * @return  void
	 *
	 * @since   1.0.3
	 */
	public function toWork()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$ids = $this->input->get('cid', array(), 'array');

		if (empty($ids))
		{
			JError::raiseWarning(500, Text::_('COM_PROFILES_ERROR_NO_ITEM_SELECTED'));
		}
		else
		{
			// Get the model.
			$model = $this->getModel('Profile', 'ProfilesModel');;

			// Change the state of the records.
			if (!$model->toWork($ids))
			{
				JError::raiseWarning(500, $model->getError());
			}
			else
			{
				$this->setMessage(Text::plural('COM_PROFILES_N_ITEMS_IN_WORK', count($ids)));
			}
		}

		$this->setRedirect('index.php?option=com_profiles&view=profiles');
	}

	/**
	 * Method to unset in_work to one or more records.
	 *
	 * @return  void
	 *
	 * @since   1.0.7
	 */
	public function unWork()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$ids = $this->input->get('cid', array(), 'array');

		if (empty($ids))
		{
			JError::raiseWarning(500, Text::_('COM_PROFILES_ERROR_NO_ITEM_SELECTED'));
		}
		else
		{
			// Get the model.
			$model = $this->getModel('Profile', 'ProfilesModel');;

			// Change the state of the records.
			if (!$model->unWork($ids))
			{
				JError::raiseWarning(500, $model->getError());
			}
			else
			{
				$this->setMessage(Text::plural('COM_PROFILES_N_ITEMS_UN_WORK', count($ids)));
			}
		}

		$this->setRedirect('index.php?option=com_profiles&view=profiles');
	}

	/**
	 * Method to clone an existing item.
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	public function synchronize()
	{
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		try
		{
			$model       = $this->getModel('Profile', 'ProfilesModel');
			$synchronize = $model->synchronizeItems();
			$this->setMessage(Text::plural('COM_PROFILES_N_ITEMS_SYNCHRONIZE', $synchronize));
		}
		catch (Exception $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}

		$this->setRedirect('index.php?option=com_profiles&view=profiles');
	}
}

