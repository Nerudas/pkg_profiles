<?php
/**
 * @package    Profiles Component
 * @version    1.0.9
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

class com_ProfilesInstallerScript
{
	/**
	 * Runs right after any installation action is preformed on the component.
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	function postflight()
	{
		$path = '/components/com_profiles';

		$this->fixTables($path);
		$this->tagsIntegration();
		$this->createImageFolder();
		$this->moveLayouts($path);
		$this->createSecret();

		return true;
	}

	/**
	 * Create or image folders
	 *
	 * @since 1.0.0
	 */
	protected function createImageFolder()
	{
		$folder = JPATH_ROOT . '/images/profiles';
		if (!JFolder::exists($folder))
		{
			JFolder::create($folder);
			JFile::write($folder . '/index.html', '<!DOCTYPE html><title></title>');
		}
	}

	/**
	 * Create or update tags integration
	 *
	 * @since 1.0.0
	 */
	protected function tagsIntegration()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('type_id')
			->from($db->quoteName('#__content_types'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_profiles.profile'));
		$db->setQuery($query);
		$current_id = $db->loadResult();

		$profile                                               = new stdClass();
		$profile->type_id                                      = (!empty($current_id)) ? $current_id : '';
		$profile->type_title                                   = 'Profiles Profile';
		$profile->type_alias                                   = 'com_profiles.profile';
		$profile->table                                        = new stdClass();
		$profile->table->special                               = new stdClass();
		$profile->table->special->dbtable                      = '#__profiles';
		$profile->table->special->key                          = 'id';
		$profile->table->special->type                         = 'Profiles';
		$profile->table->special->prefix                       = 'ProfilesTable';
		$profile->table->special->config                       = 'array()';
		$profile->table->common                                = new stdClass();
		$profile->table->common->dbtable                       = '#__ucm_content';
		$profile->table->common->key                           = 'ucm_id';
		$profile->table->common->type                          = 'Corecontent';
		$profile->table->common->prefix                        = 'JTable';
		$profile->table->common->config                        = 'array()';
		$profile->table                                        = json_encode($profile->table);
		$profile->rules                                        = '';
		$profile->field_mappings                               = new stdClass();
		$profile->field_mappings->common                       = new stdClass();
		$profile->field_mappings->common->core_content_item_id = 'id';
		$profile->field_mappings->common->core_title           = 'name';
		$profile->field_mappings->common->core_state           = 'null';
		$profile->field_mappings->common->core_alias           = 'alias';
		$profile->field_mappings->common->core_created_time    = 'created';
		$profile->field_mappings->common->core_modified_time   = 'modified';
		$profile->field_mappings->common->core_body            = 'status';
		$profile->field_mappings->common->core_hits            = 'hits';
		$profile->field_mappings->common->core_publish_up      = 'created';
		$profile->field_mappings->common->core_publish_down    = 'null';
		$profile->field_mappings->common->core_access          = 'null';
		$profile->field_mappings->common->core_params          = 'null';
		$profile->field_mappings->common->core_featured        = 'null';
		$profile->field_mappings->common->core_metadata        = 'metadata';
		$profile->field_mappings->common->core_language        = 'null';
		$profile->field_mappings->common->core_images          = 'avatar';
		$profile->field_mappings->common->core_urls            = 'null';
		$profile->field_mappings->common->core_version         = 'null';
		$profile->field_mappings->common->core_ordering        = 'created';
		$profile->field_mappings->common->core_metakey         = 'metakey';
		$profile->field_mappings->common->core_metadesc        = 'metadesc';
		$profile->field_mappings->common->core_catid           = 'null';
		$profile->field_mappings->common->core_xreference      = 'null';
		$profile->field_mappings->common->asset_id             = 'null';
		$profile->field_mappings->special                      = new stdClass();
		$profile->field_mappings->special->contacts            = 'contacts';
		$profile->field_mappings->special->region              = 'region';
		$profile->field_mappings                               = json_encode($profile->field_mappings);
		$profile->router                                       = 'ProfilesHelperRoute::getProfileRoute';
		$profile->content_history_options                      = '';

		(!empty($current_id)) ? $db->updateObject('#__content_types', $profile, 'type_id')
			: $db->insertObject('#__content_types', $profile);
	}

	/**
	 * Move layouts folder
	 *
	 * @param string $path path to files
	 *
	 * @since 1.0.0
	 */
	protected function moveLayouts($path)
	{
		$component = JPATH_ADMINISTRATOR . $path . '/layouts';
		$layouts   = JPATH_ROOT . '/layouts' . $path;
		if (!JFolder::exists(JPATH_ROOT . '/layouts/components'))
		{
			JFolder::create(JPATH_ROOT . '/layouts/components');
		}
		if (JFolder::exists($layouts))
		{
			JFolder::delete($layouts);
		}
		JFolder::move($component, $layouts);
	}

	/**
	 *
	 * Called on uninstallation
	 *
	 * @param   JAdapterInstance $adapter The object responsible for running this script
	 *
	 * @since 1.0.0
	 */
	public function uninstall(JAdapterInstance $adapter)
	{
		$db = Factory::getDbo();
		// Remove content_type
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__content_types'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_profiles.profile'));
		$db->setQuery($query)->execute();

		// Remove tag_map
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__contentitem_tag_map'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_profiles.profile'));
		$db->setQuery($query)->execute();

		// Remove ucm_content
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__ucm_content'))
			->where($db->quoteName('core_type_alias') . ' = ' . $db->quote('com_profiles.profile'));
		$db->setQuery($query)->execute();

		// Remove images
		JFolder::delete(JPATH_ROOT . '/images/profiles');

		// Remove layouts
		JFolder::delete(JPATH_ROOT . '/layouts/components/com_profiles');
	}

	/**
	 * Method to fix tables
	 *
	 * @param string $path path to component directory
	 *
	 * @since 1.0.0
	 */
	protected function fixTables($path)
	{
		$file = JPATH_ADMINISTRATOR . $path . '/sql/install.mysql.utf8.sql';
		if (!empty($file))
		{
			$sql = JFile::read($file);

			if (!empty($sql))
			{
				$db      = Factory::getDbo();
				$queries = $db->splitSql($sql);
				foreach ($queries as $query)
				{
					$db->setQuery($db->convertUtf8mb4QueryToUtf8($query));
					try
					{
						$db->execute();
					}
					catch (JDataBaseExceptionExecuting $e)
					{
						JLog::add(Text::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $e->getMessage()),
							JLog::WARNING, 'jerror');
					}
				}
			}
		}
	}

	/**
	 * Method to create secret key
	 *
	 * @since 1.0.0
	 */
	function createSecret()
	{
		$component = ComponentHelper::getComponent('com_profiles');
		$params    = $component->getParams();
		if (empty($params->get('secret')))
		{
			$secret = '';
			$array  = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's',
				't', 'u', 'v', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
				'P', 'R', 'S', 'T', 'U', 'V', 'X', 'Y', 'Z');
			for ($i = 0; $i < 15; $i++)
			{
				$key    = rand(0, count($array) - 1);
				$secret .= $array[$key];
			}
			$params->set('secret', $secret);

			$object               = new stdClass();
			$object->extension_id = $component->id;
			$object->params       = (string) $params;
			Factory::getDbo()->updateObject('#__extensions', $object, 'extension_id');
		}
	}
}