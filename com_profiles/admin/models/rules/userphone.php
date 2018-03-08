<?php
/**
 * @package    Profiles Package
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;

class JFormRuleUserPhone extends JFormRule
{
	/**
	 * Method to test if two fields have a value in order to use only one field.
	 * To use this rule, the form
	 * XML needs a validate attribute of logoutuniquefield and a field attribute
	 * that is equal to the field to test against.
	 *
	 * @param   SimpleXMLElement $element   The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed            $value     The form field value to validate.
	 * @param   string           $group     The field name group control value. This acts as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 * @param   Registry         $input     An optional Registry object with the entire data set to validate against the entire form.
	 * @param   JForm            $form      The form object for which the field is being tested.
	 *
	 * @return  boolean  True if the value is valid, false otherwise.
	 *
	 * @since 1.0.0
	 */
	public function test(SimpleXMLElement $element, $value, $group = null, Registry $input = null, JForm $form = null)
	{
		$id = $input->get('id', 0);

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/models', 'ProfilesModel');
		$model = BaseDatabaseModel::getInstance('User', 'ProfilesModel');

		if (!$model->validatePhone($id, $value))
		{
			JError::raiseWarning(100, Text::_('COM_PROFILES_ERROR_PHONE_EXIST'));

			return false;
		}

		return true;
	}
}