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

use Joomla\CMS\MVC\Controller\FormController;

class ProfilesControllerCategory extends FormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var string
	 *
	 * @since 1.5.0
	 */
	protected $text_prefix = 'COM_PROFILES_CATEGORY';

	/**
	 * Method to run batch operations.
	 *
	 * @param   \Joomla\CMS\MVC\Model\BaseDatabaseModel $model The model.
	 *
	 * @return  boolean  True if successful, false otherwise and internal error is set.
	 *
	 * @since 1.5.0
	 */
	public function batch($model = null)
	{
		$this->checkToken();

		// Set the model
		$model = $this->getModel('Category');

		// Preset the redirect
		$this->setRedirect('index.php?option=com_profiles&view=categories');

		return parent::batch($model);
	}
}