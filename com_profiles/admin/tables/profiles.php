<?php
/**
 * @package    Profiles Component
 * @version    1.2.1
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\CMS\Table\Observer\Tags;
use Joomla\CMS\Factory;

class ProfilesTableProfiles extends Table
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver &$db Database connector object
	 *
	 * @since 1.0.0
	 */
	function __construct(&$db)
	{
		parent::__construct('#__profiles', 'id', $db);

		// Set the alias since the column is called state
		$this->setColumnAlias('published', 'state');

		Tags::createObserver($this, array('typeAlias' => 'com_profiles.profile'));
	}

	/**
	 * Validate that the primary key has been set.
	 *
	 * @return  boolean  True if the primary key(s) have been set.
	 *
	 * @since 1.0.0
	 */
	public function hasPrimaryKey()
	{
		if (!parent::hasPrimaryKey())
		{
			return false;
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('id')
			->from('#__profiles');
		foreach ($this->_tbl_keys as $key)
		{
			$query->where($db->quoteName($key) . ' = ' . $db->quote($this->$key));
		}
		$db->setQuery($query);

		return (!empty($db->loadResult()));
	}
}