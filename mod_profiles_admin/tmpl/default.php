<?php
/**
 * @package    Profiles - Administrator Module
 * @version    1.1.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('jquery.framework');
HTMLHelper::_('script', 'media/mod_profiles_admin/js/ajax.min.js', array('version' => 'auto'));
HTMLHelper::_('stylesheet', 'media/mod_profiles_admin/css/default.min.css', array('version' => 'auto'));
?>

<div data-mod-profiles-admin="<?php echo $module->id; ?>">
	<?php echo HTMLHelper::_('bootstrap.startTabSet', 'modProfilesAdmin' . $module->id, array('active' => 'new')); ?>

	<?php echo HTMLHelper::_('bootstrap.addTab', 'modProfilesAdmin' . $module->id, 'new',
		Text::_('MOD_PROFILES_ADMIN_TABS_NEW')); ?>
	<div data-mod-profiles-admin-tab="new">
		<div class="loading">
			<?php echo Text::_('MOD_PROFILES_ADMIN_LOADING'); ?>
		</div>
		<div class="result">
			<div class="items"></div>
		</div>
	</div>
	<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

	<?php echo HTMLHelper::_('bootstrap.addTab', 'modProfilesAdmin' . $module->id, 'modified',
		Text::_('MOD_PROFILES_ADMIN_TABS_MODIFIED')); ?>
	<div data-mod-profiles-admin-tab="modified">
		<div class="loading">
			<?php echo Text::_('MOD_PROFILES_ADMIN_LOADING'); ?>
		</div>
		<div class="result">
			<div class="items"></div>
		</div>
	</div>
	<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

	<?php echo HTMLHelper::_('bootstrap.addTab', 'modProfilesAdmin' . $module->id, 'online',
		Text::_('MOD_PROFILES_ADMIN_TABS_ONLINE')); ?>
	<div data-mod-profiles-admin-tab="online">
		<div class="loading">
			<?php echo Text::_('MOD_PROFILES_ADMIN_LOADING'); ?>
		</div>
		<div class="result">
			<div class="items"></div>
		</div>
	</div>
	<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

	<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
	<div class="actions ">
		<div class="btn-group">
			<a class="btn"
			   href="<?php echo Route::_('index.php?option=com_profiles'); ?>">
				<?php echo Text::_('MOD_PROFILES_ADMIN_TO_COMPONENT'); ?>
			</a>
			<a class="btn"
			   data-mod-profiles-admin-reload="<?php echo $module->id; ?>"
			   title="<?php echo Text::_('MOD_PROFILES_ADMIN_REFRESH'); ?>">
				<i class="icon-loop"></i>
			</a>
		</div>
	</div>
</div>
