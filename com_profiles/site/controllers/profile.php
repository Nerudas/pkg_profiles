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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class ProfilesControllerProfile extends FormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since 1.0.0
	 */
	protected $text_prefix = 'COM_PROFILES_PROFILE';

	/**
	 * Method to update profile Images
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since 1.0.0
	 */
	public function updateImages()
	{
		$app   = Factory::getApplication();
		$id    = $app->input->get('id', 0, 'int');
		$value = $app->input->get('value', '', 'raw');
		$field = $app->input->get('field', '', 'raw');
		if (!empty($id) & !empty($field))
		{
			JLoader::register('imageFolderHelper', JPATH_PLUGINS . '/fieldtypes/ajaximage/helpers/imagefolder.php');
			$helper = new imageFolderHelper('images/profiles');
			$helper->saveImagesValue($id, '#__profiles', $field, $value);
		}

		$app->close();

		return true;
	}

	/**
	 * Method to update profile Images
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since 1.0.0
	 */
	public function checkAlias()
	{
		$app  = Factory::getApplication();
		$data = $this->input->post->get('jform', array(), 'array');

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/models', 'ProfilesModel');
		$model = BaseDatabaseModel::getInstance('Profile', 'ProfilesModel', array('ignore_request' => false));
		$check = $model->checkAlias($data['id'], $data['alias']);

		echo new JsonResponse($check->data, $check->msg, ($check->status == 'error'));

		$app->close();

		return true;
	}
}