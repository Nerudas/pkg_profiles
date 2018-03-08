<?php
/**
 * @package    Profiles Package
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

class ProfilesControllerProfiles extends AdminController
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
	 * @return  JModelLegacy
	 *
	 * @since 1.0.0
	 */
	public function getModel($name = 'Profile', $prefix = 'ProfilesModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
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
			$model       = $this->getModel();
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

