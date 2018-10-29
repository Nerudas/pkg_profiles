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

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

class ProfilesControllerCategories extends AdminController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var string
	 *
	 * @since 1.5.0
	 */
	protected $text_prefix = 'COM_PROFILES_CATEGORIES';

	/**
	 * Rebuild the nested set tree.
	 *
	 * @return  boolean  False on failure or error, true on success.
	 *
	 * @since 1.5.0
	 */
	public function rebuild()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$this->setRedirect(Route::_('index.php?option=com_profiles&view=categories', false));

		if ($this->getModel()->rebuild())
		{
			// Rebuild succeeded.
			$this->setMessage(Text::_('COM_PROFILES_CATEGORIES_REBUILD_SUCCESS'));

			return true;
		}

		// Rebuild failed.
		$this->setMessage(Text::_('COM_PROFILES_CATEGORIES_REBUILD_FAILURE'));

		return false;
	}

	/**
	 * Method to clone an existing item.
	 *
	 * @return  boolean  False on failure or error, true on success.
	 *
	 * @since 1.5.0
	 */
	public function duplicate()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$this->setRedirect(Route::_('index.php?option=com_profiles&view=categories', false));

		try
		{
			$pks = $this->input->post->get('cid', array(), 'array');
			$pks = ArrayHelper::toInteger($pks);

			if (empty($pks))
			{
				throw new Exception(Text::_('COM_PROFILES_ERROR_NO_CATEGORIES_SELECTED'));
			}

			$model = $this->getModel();
			$model->duplicate($pks);

			$this->setMessage(Text::plural('COM_PROFILES_CATEGORIES_N_ITEMS_DUPLICATED', count($pks)));

			return true;
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage(), 500);
		}
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string $name   The model name. Optional.
	 * @param   string $prefix The class prefix. Optional.
	 * @param   array  $config The array of possible config values. Optional.
	 *
	 * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
	 *
	 * @since 1.5.0
	 */
	public function getModel($name = 'Category', $prefix = 'ProfilesModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}
}