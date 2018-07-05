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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

$user    = Factory::getUser();
$columns = 5;
$canDo   = ProfilesHelper::getActions('com_users');

?>
<table class="table table-striped">
	<thead>
	<tr>
		<th class="nowrap">
			<?php echo Text::_('COM_PROFILES_PROFILE_NAME'); ?>
		</th>
		<th width="10%" class="nowrap hidden-phone">
			<?php echo Text::_('JGRID_HEADING_REGION'); ?>
		</th>
		<?php if ($tab == 'online'): ?>
			<th width="10%" class="nowrap hidden-phone">
				<?php echo Text::_('COM_PROFILES_HEADING_LAST_VISIT'); ?>
			</th>
		<?php else: ?>
			<th width="10%" class="nowrap hidden-phone">
				<?php echo ($tab == 'modified') ? Text::_('COM_PROFILES_HEADING_MODIFIED') :
					Text::_('COM_PROFILES_HEADING_CREATED_DATE'); ?>
			</th>
		<?php endif; ?>
		<th width="1%" class="nowrap hidden-phone">
			<?php echo Text::_('COM_PROFILES_HEADING_HITS'); ?>
		</th>
		<th width="1%" class="nowrap hidden-phone center">
			<?php echo Text::_('JGRID_HEADING_ID'); ?>
		</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="<?php echo $columns; ?>" class="center">
		</td>
	</tr>
	<tbody>
	<?php foreach ($items as $item): ?>
		<tr>
			<td class="nowrap">
				<div class="author">
					<div class="avatar<?php echo ($item->online) ? ' online' : '' ?>"
						 style="background-image: url(' <?php echo $item->avatar; ?>')">
					</div>
					<div>
						<div>
							<?php if ($canDo->get('core.edit')) : ?>
								<a class="hasTooltip nowrap" title="<?php echo Text::_('JACTION_EDIT'); ?>"
								   href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id=' . $item->id); ?>">
									<?php echo $item->name; ?>
								</a>
							<?php else : ?>
								<span class="nowrap"><?php echo $item->name; ?></span>
							<?php endif; ?>
							<?php if ($item->in_work): ?>
								<sup class="label label-info">
									<?php echo Text::_('COM_PROFILES_PROFILE_IN_WORK'); ?>
								</sup>
							<?php endif; ?>
						</div>
						<?php if ($item->job): ?>
							<div class="job">
								<a class="hasTooltip nowrap" title="<?php echo Text::_('JACTION_EDIT'); ?>"
								   target="_blank"
								   href="<?php echo Route::_('index.php?option=com_companies&task=company.edit&id=' . $item->job_id); ?>">
									<?php echo $item->job_name; ?>
								</a>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</td>
			<td class="small hidden-phone nowrap">
				<?php echo ($item->region !== '*') ? $item->region_name :
					Text::_('JGLOBAL_FIELD_REGIONS_ALL'); ?>
			</td>
			<?php if ($tab == 'online'): ?>
				<td class="nowrap">
					<?php echo $item->last_visit > 0 ? HTMLHelper::_('date', $item->last_visit,
						Text::_('DATE_FORMAT_LC2')) : Text::_('JNEVER') ?>
				</td>
			<?php else: ?>
				<td class="nowrap small hidden-phone">
					<?php
					$date = ($tab == 'modified') ? $item->modified : $item->created;
					echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC2')) : '-' ?>
				</td>
			<?php endif; ?>
			<td class="hidden-phone center">
				<span class="badge badge-info">
					<?php echo (int) $item->hits; ?>
				</span>
			</td>
			<td class="hidden-phone center">
				<?php echo $item->id; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>