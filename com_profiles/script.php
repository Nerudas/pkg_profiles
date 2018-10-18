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

use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class com_profilesInstallerScript
{
	/**
	 * Runs right after any installation action.
	 *
	 * @return bool
	 *
	 * @since 1.5.0
	 */
	function postflight()
	{
		// Check folders
		$this->checkFolders();

		// Check database tables
		$this->checkTables();

		// Check base sections
		$this->checkSections();

		// Move layouts
		$this->moveLayouts();

		return true;
	}

	/**
	 * Method to create images folders in not exist
	 *
	 * @since 1.5.0
	 */
	protected function checkFolders()
	{
		$folders = array(
			JPATH_ROOT . '/images',
			JPATH_ROOT . '/images/profiles',
			JPATH_ROOT . '/images/profiles/sections',
			JPATH_ROOT . '/images/profiles/sections/1',
			JPATH_ROOT . '/images/profiles/sections/2',
			JPATH_ROOT . '/images/profiles/sections/3',
		);

		foreach ($folders as $folder)
		{
			if (!Folder::exists($folder))
			{
				Folder::create($folder);
				File::write($folder . '/index.html', '<!DOCTYPE html><title></title>');
			}
		}
	}

	/**
	 * Method to create database tables in not exist
	 *
	 * @since 1.5.0
	 */
	protected function checkTables()
	{
		$file = JPATH_ADMINISTRATOR . '/components/com_profiles/sql/install.mysql.utf8.sql';
		$sql  = File::read($file);

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

	/**
	 * Method to create base sections in not exist
	 *
	 * @since 1.5.0
	 */
	protected function checkSections()
	{
		$db      = Factory::getDbo();
		$table   = '#__profiles_sections';
		$rebuild = false;

		// Get base sections
		$query = $db->getQuery(true)
			->select('id')
			->from($table)
			->where($db->quoteName('id') . 'IN (1,2,3)');
		$db->setQuery($query);
		$exist = $db->loadColumn();

		// Check all section
		if (!in_array(1, $exist))
		{
			$section            = new stdClass();
			$section->id        = 1;
			$section->title     = Text::_('COM_PROFILES_SECTIONS_ALL');
			$section->parent_id = 0;
			$section->alias     = 'all';
			$section->state     = 1;
			$section->access    = 1;

			$rebuild = true;
			$db->insertObject($table, $section);
		}

		// Check natural section
		if (!in_array(2, $exist))
		{
			$section            = new stdClass();
			$section->id        = 2;
			$section->title     = Text::_('COM_PROFILES_SECTIONS_NATURAL');
			$section->parent_id = 1;
			$section->alias     = 'natural';
			$section->state     = 1;
			$section->access    = 1;

			$rebuild = true;
			$db->insertObject($table, $section);
		}

		// Check legal section
		if (!in_array(3, $exist))
		{
			$section            = new stdClass();
			$section->id        = 3;
			$section->title     = Text::_('COM_PROFILES_SECTIONS_LEGAL');
			$section->parent_id = 1;
			$section->alias     = 'legal';
			$section->state     = 1;
			$section->access    = 1;

			$rebuild = true;
			$db->insertObject($table, $section);
		}

		// Rebuild sections
		if ($rebuild)
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/models');
			$model = BaseDatabaseModel::getInstance('Section', 'ProfilesModel', array('ignore_request' => true));
			$model->rebuild();
		}
	}

	/**
	 * Method to move component layouts
	 *
	 * @since 1.5.0
	 */
	protected function moveLayouts()
	{
		$src  = JPATH_ADMINISTRATOR . '/components/com_profiles/layouts';
		$dest = JPATH_ROOT . '/layouts/components';

		// Components layouts path check
		if (!Folder::exists($dest))
		{
			Folder::create($dest);
		}
		$dest .= '/profiles';

		// Delete old layouts
		if (Folder::exists($dest))
		{
			Folder::delete($dest);
		}

		// Move layouts
		Folder::move($src, $dest);
	}

	/**
	 * Called on uninstallation
	 *
	 * @param   JAdapterInstance $adapter The object responsible for running this script
	 *
	 * @since 1.5.0
	 */
	public function uninstall(JAdapterInstance $adapter)
	{
		// Remove images
		Folder::delete(JPATH_ROOT . '/images/profiles');

		// Remove layouts
		Folder::delete(JPATH_ROOT . '/layouts/components/profiles');
	}
}