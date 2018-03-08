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

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class ProfilesHelper extends ContentHelper
{
	public static $extension = 'com_profiles';

	/**
	 * Configure the Linkbar.
	 *
	 * @param   string $vName The name of the active view.
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	static function addSubmenu($vName)
	{
		$uri    = (string) Uri::getInstance();
		$return = urlencode(base64_encode($uri));

		JHtmlSidebar::addEntry(Text::_('COM_PROFILES'),
			'index.php?option=com_profiles&view=profiles',
			$vName == 'profiles');
		JHtmlSidebar::addEntry(Text::_('COM_PROFILES_CONFIG'),
			'index.php?option=com_config&view=component&component=com_profiles&return=' . $return,
			$vName == 'config');

	}
}