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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

jimport('joomla.filesystem.file');
JLoader::register('ProfilesHelperRoute', JPATH_SITE . '/components/com_profiles/helpers/route.php');

extract($displayData);

Factory::getLanguage()->load('com_profiles', JPATH_SITE);

HTMLHelper::_('jquery.framework');
HTMLHelper::stylesheet('media/com_profiles/css/sociallogin.min.css', array('version' => 'auto'));
HTMLHelper::script('media/com_profiles/js/sociallogin.min.js', array('version' => 'auto'));

$user_id = (!empty($user_id)) ? $user_id : Factory::getUser()->id;
$actives = (!empty($actives)) ? $actives : array();

$root = Uri::root(true) . '/';
Factory::getDocument()->addScriptOptions('user.social.params', array(
	'user_id'          => $user_id,
	'authorizationURL' => $root . ProfilesHelperRoute::getSocialsAuthorizationRoute(),
	'disconnectURL'    => $root . ProfilesHelperRoute::getSocialsDisconnectRoute()
));

?>
<div>
	<?php foreach (array('vk', 'facebook', 'instagram', 'odnoklassniki') as $provider):
		$class = '';
		if ($active = (in_array($provider, $actives) && !empty($user_id)))
		{
			$class .= ' active';
		};
		if ($notActive = (!$active && !empty($user_id)))
		{
			$class .= ' not-active';
		}
		$data  = ($active) ? 'data-user-social-disconnect="' . $provider . '"'
			: 'data-user-social-authorization="' . $provider . '"';
		$title = Text::_('COM_PROFILES_SOCIALS_' . mb_strtoupper($provider));

		?>
		<a class="<?php echo $class; ?>" <?php echo $data; ?> title="<?php echo $title; ?>">
			<?php
			$svg = JPATH_ROOT . '/media/com_profiles/images/' . $provider . '.svg';
			if (JFile::exists($svg))
			{
				echo JFile::read($svg);
			}
			else
			{
				echo $title;
			}
			?>
		</a>
	<?php endforeach; ?>
</div>