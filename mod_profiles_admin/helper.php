<?php
/**
 * @package    Profiles - Administrator Module
 * @version    1.0.10
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

JLoader::register('ProfilesHelper', JPATH_ADMINISTRATOR . '/components/com_profiles/helpers/profiles.php');

class ModProfilesAdminHelper
{
	/**
	 * Get Items
	 *
	 * @return bool|string
	 *
	 * @since 1.0.3
	 */
	public static function getAjax()
	{
		$app = Factory::getApplication();
		$tab = $app->input->get('tab', 'new');

		if ($params = self::getModuleParams($app->input->get('module_id', 0)))
		{
			$language = Factory::getLanguage();
			$language->load('com_profiles', JPATH_ADMINISTRATOR, $language->getTag(), true);

			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/models', 'ProfilesModel');
			$model = BaseDatabaseModel::getInstance('Profiles', 'ProfilesModel', array('ignore_request' => false));

			$list                 = array();
			$list['limit']        = $params->get('limit', 5);
			$list['fullordering'] = ($tab == 'modified') ? 'p.modified DESC' : 'p.created DESC';
			$app->setUserState('com_profiles.profiles.list', $list);

			$filter = array();
			if ($tab == 'online')
			{
				$filter['online'] = 1;
			}
			$app->setUserState('com_profiles.profiles.filter', $filter);

			$items = $model->getItems();
			$app->setUserState('com_profiles.profiles.list', '');
			$app->setUserState('com_profiles.profiles.filter', '');

			if (count($items))
			{
				ob_start();
				require ModuleHelper::getLayoutPath('mod_' . $app->input->get('module'),
					$params->get('layout', 'default') . '_items');
				$response = ob_get_contents();
				ob_end_clean();

				return $response;
			}
			else
			{
				throw new Exception(Text::_('JGLOBAL_NO_MATCHING_RESULTS'), 404);
			}
		}

		throw new Exception(Text::_('MOD_PROFILES_ADMIN_ERROR_MODULE_NOT_FOUND'), 404);
	}

	/**
	 * Get Module parameters
	 *
	 * @param int $pk module id
	 *
	 * @return bool|Registry
	 *
	 * @since 1.0.3
	 */
	protected static function getModuleParams($pk = null)
	{
		$pk = (empty($pk)) ? Factory::getApplication()->input->get('module_id', 0) : $pk;
		if (empty($pk))
		{
			return false;
		}

		// Get Params
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('params')
			->from('#__modules')
			->where('id =' . $pk);
		$db->setQuery($query);
		$params = $db->loadResult();

		return (!empty($params)) ? new Registry($params) : false;
	}
}