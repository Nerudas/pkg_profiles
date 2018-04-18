<?php
/**
 * @package    Profiles Component
 * @version    1.0.6
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

jimport('joomla.filesystem.file');

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', '.multipleTags', null, array('placeholder_text_multiple' => Text::_('JOPTION_SELECT_TAG')));
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::stylesheet('media/com_profiles/css/admin-profiles.min.css', array('version' => 'auto'));

$app       = Factory::getApplication();
$doc       = Factory::getDocument();
$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$canDo     = ProfilesHelper::getActions('com_users');

$doc->addScriptDeclaration("jQuery(document).ready(function () {
	jQuery('form').on('submit', function () {
		var task = jQuery(this).find('[name=\"task\"]');
		if (task.val() == 'export.excel') {
			setTimeout(function () {
				task.val('');
			}, 50);
		}
	});
});");

$columns = 10;
?>
<form action="<?php echo Route::_('index.php?option=com_profiles&view=profiles'); ?>" method="post" name="adminForm"
	  id="adminForm">
	<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
	<?php if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php else : ?>
		<table id="itemList" class="table table-striped">
			<thead>
			<tr>
				<th width="1%" class="center">
					<?php echo HTMLHelper::_('grid.checkall'); ?>
				</th>
				<th class="nowrap">
					<?php echo HTMLHelper::_('searchtools.sort', 'COM_PROFILES_PROFILE_NAME', 'p.name', $listDirn, $listOrder); ?>
				</th>
				<th class="nowrap">
					<?php echo Text::_('COM_PROFILES_PROFILE_NOTES_NOTE'); ?>
				</th>
				<th width="10%" class="nowrap">
					<?php echo Text::_('COM_PROFILES_PROFILE_SITE_ACCESS'); ?>
				</th>
				<th width="10%" class="nowrap hidden-phone">
					<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_REGION', 'region_name', $listDirn, $listOrder); ?>
				</th>
				<th width="10%" class="nowrap hidden-phone">
					<?php echo HTMLHelper::_('searchtools.sort', 'COM_PROFILES_HEADING_LAST_VISIT', 'last_visit', $listDirn, $listOrder); ?>
				</th>
				<th width="10%" class="nowrap hidden-phone">
					<?php echo HTMLHelper::_('searchtools.sort', 'COM_PROFILES_HEADING_CREATED_DATE', 'p.created', $listDirn, $listOrder); ?>
				</th>
				<th width="1%" class="nowrap hidden-phone">
					<?php echo HTMLHelper::_('searchtools.sort', 'COM_PROFILES_HEADING_HITS', 'p.hits', $listDirn, $listOrder); ?>
				</th>
				<th width="1%" class="nowrap hidden-phone center">
					<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'p.id', $listDirn, $listOrder); ?>
				</th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td colspan="<?php echo $columns; ?>">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
			</tfoot>
			<tbody>
			<?php foreach ($this->items as $i => $item) : ?>
				<tr>
					<td class="center">
						<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
					</td>
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
											<?php echo $this->escape($item->name); ?>
										</a>
									<?php else : ?>
										<span class="nowrap"><?php echo $this->escape($item->name); ?></span>
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
											<?php echo $this->escape($item->job_name); ?>
										</a>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</td>
					<td>
						<?php echo $item->note; ?>
					</td>
					<td class="nowrap">
						<div class="email">
							<?php echo $item->user_email; ?>
						</div>
						<div class="phone">
							<?php echo preg_replace('/(\\+\\d{1})(\\d{3})(\\d{3})(\\d{2})(\\d{2})/',
								'$1($2)$3-$4-$5', $item->user_phone); ?>
						</div>
						<div class="socials">
							<?php foreach ($item->user_socials as $provider)
							{
								$title = Text::_('COM_PROFILES_SOCIALS_' . mb_strtoupper($provider));
								$svg   = JPATH_ROOT . '/media/com_profiles/images/' . $provider . '.svg';
								if (JFile::exists($svg))
								{
									echo JFile::read($svg);
								}
								else
								{
									echo $title;
								}
							} ?>
					</td>
					<td class="small hidden-phone nowrap">
						<?php echo ($item->region !== '*') ? $this->escape($item->region_name) :
							Text::_('JGLOBAL_FIELD_REGIONS_ALL'); ?>
					</td>
					<td class="nowrap">
						<?php echo $item->last_visit > 0 ? HTMLHelper::_('date', $item->last_visit,
							Text::_('DATE_FORMAT_LC2')) : Text::_('JNEVER') ?>
					</td>
					<td class="nowrap small hidden-phone">
						<?php echo $item->created > 0 ? HTMLHelper::_('date', $item->created,
							Text::_('DATE_FORMAT_LC1')) : '-' ?>
					</td>
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
	<?php endif; ?>
	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="boxchecked" value="0"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
