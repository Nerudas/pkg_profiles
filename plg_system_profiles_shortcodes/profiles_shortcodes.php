<?php
/**
 * @package    System - Profiles Shortcodes Plugin
 * @version    1.2.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Layout\LayoutHelper;

class plgSystemProfiles_Shortcodes extends CMSPlugin
{

	/**
	 * Listener for the `onAfterRender` event
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onAfterRender()
	{

		$app = Factory::getApplication();
		if ($app->isSite())
		{
			$body = JResponse::getBody();
			preg_match_all('/{profile id="(.*?)" layout="(.*?)"}/', $body, $matches);
			$shortcodes = (!empty($matches[0])) ? $matches[0] : array();
			$ids        = (!empty($matches[1])) ? $matches[1] : array();
			$layouts    = (!empty($matches[2])) ? $matches[2] : array();


			if (!empty($ids))
			{
				JLoader::register('ProfilesHelperRoute', JPATH_SITE . '/components/com_profiles/helpers/route.php');
				JLoader::register('CompaniesHelperRoute', JPATH_SITE . '/components/com_companies/helpers/route.php');

				// Load Language
				$language = Factory::getLanguage();
				$language->load('com_companies', JPATH_SITE, $language->getTag());

				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_profiles/models', 'ProfilesModel');
				$model = BaseDatabaseModel::getInstance('List', 'ProfilesModel', array('ignore_request' => true));
				$model->setState('list.limit', 0);
				$model->setState('filter.published', 1);
				$model->setState('filter.item_id', $ids);
				$items = $model->getItems();

				foreach ($shortcodes as $key => $shortcode)
				{
					$id     = (!empty($ids[$key])) ? $ids[$key] : false;
					$layout = (!empty($layouts[$key])) ? $layouts[$key] : false;
					$item   = ($id && !empty($items[$id])) ? $items[$id] : false;

					$replace = ($id && $layout && $item) ?
						LayoutHelper::render('components.com_profiles.shortcodes.' . $layout, $item) : '';

					$body = str_replace($shortcode, $replace, $body);
				}

				JResponse::setBody($body);
			}
		}
	}
}