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

use Joomla\CMS\Table\Nested;

class ProfilesTableCategories extends Nested
{
	/**
	 * Cache for the root ID
	 *
	 * @var integer
	 *
	 * @since 1.5.0
	 */
	protected static $root_id = 1;

	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver &$db Database connector object
	 *
	 * @since 1.5.0
	 */
	function __construct(&$db)
	{
		parent::__construct('#__profiles_categories', 'id', $db);

		// Set the alias since the column is called state
		$this->setColumnAlias('published', 'state');
	}

	/**
	 * Checks that the object is valid and able to be stored.
	 *
	 * This method checks that the parent_id is non-zero and exists in the database.
	 * Note that the root node (parent_id = 0) cannot be manipulated with this class.
	 *
	 * @return  boolean  True if all checks pass.
	 *
	 * @since 1.5.0
	 */
	public function check()
	{
		return ($this->id == 1 && $this->parent_id == 0) ? true : parent::check();
	}
}