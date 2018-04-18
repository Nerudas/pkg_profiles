<?php
/**
 * @package    Profiles Component
 * @version    1.0.6
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;

JLoader::register('UsersControllerUser', JPATH_ADMINISTRATOR . '/components/com_users/controllers/user.php');

class ProfilesControllerProfile extends UsersControllerUser
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
		$app   = Factory::getApplication();
		$data  = $this->input->post->get('jform', array(), 'array');
		$model = $this->getModel();
		$check = $model->checkAlias($data['id'], $data['alias']);

		echo new JsonResponse($check->data, $check->msg, ($check->status == 'error'));

		$app->close();

		return true;
	}

	/**
	 * Method to add a new record.
	 *
	 * @return  void
	 *
	 * @since   1.0.3
	 */
	public function add()
	{
		Factory::getApplication()->redirect('index.php?option=com_users&task=user.add');
	}

	/**
	 * Method to edit an existing record.
	 *
	 * @param   string $key      The name of the primary key of the URL variable.
	 * @param   string $urlVar   The name of the URL variable if different from the primary key
	 *                           (sometimes required to avoid router collisions).
	 *
	 * @return  void
	 *
	 * @since   1.0.3
	 */
	public function edit($key = null, $urlVar = null)
	{
		$cid = $this->input->post->get('cid', array(), 'array');
		$id  = (!empty($cid[0])) ? $cid[0] : 0;

		Factory::getApplication()->redirect('index.php?option=com_users&task=user.edit&id=' . $id);
	}

	/**
	 * Method to cancel an edit.
	 *
	 * @param   string $key The name of the primary key of the URL variable.
	 *
	 * @return  boolean  True if access level checks pass, false otherwise.
	 *
	 * @since   1.6
	 */
	public function cancel($key = null)
	{
		parent::cancel($key);

		return true;
	}
}