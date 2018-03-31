<?php
/**
 * @package    Profiles Component
 * @version    1.0.2
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
use Joomla\CMS\Uri\Uri;

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
$return    = urlencode(base64_encode((string) Uri::getInstance()));

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
				<th width="30px" class="nowrap hidden-phone center">
					<i class="icon-image"></i>
				</th>
				<th class="nowrap">
					<?php echo HTMLHelper::_('searchtools.sort', 'COM_PROFILES_PROFILE_NAME', 'p.name', $listDirn, $listOrder); ?>
				</th>
				<th class="nowrap">
					<?php echo Text::_('JGLOBAL_EMAIL'); ?>
				</th>
				<th class="nowrap">
					<?php echo Text::_('COM_PROFILES_PROFILE_PHONE'); ?>
				</th>
				<th class="nowrap">
					<?php echo Text::_('COM_PROFILES_SOCIALS'); ?>
				</th>
				<th width="10%" class="nowrap hidden-phone">
					<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_REGION', 'region_name', $listDirn, $listOrder); ?>
				</th>
				<th width="10%" class="nowrap hidden-phone">
					<?php echo HTMLHelper::_('searchtools.sort', 'COM_PROFILES_PROFILE_LAST_VISIT', 'last_visit', $listDirn, $listOrder); ?>
				</th>
				<th width="10%" class="nowrap hidden-phone">
					<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_CREATED_DATE', 'p.created', $listDirn, $listOrder); ?>
				</th>
				<th width="1%" class="nowrap hidden-phone">
					<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_HITS', 'p.hits', $listDirn, $listOrder); ?>
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
						<div class="avatar<?php echo ($item->online) ? ' online' : '' ?>"
							 style="background-image: url(' <?php echo $item->avatar; ?>')">
						</div>
					</td>
					<td class="nowrap">
						<div>
							<?php if ($canDo->get('core.edit')) : ?>
								<a class="hasTooltip nowrap" title="<?php echo Text::_('JACTION_EDIT'); ?>"
								   href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id=' . $item->id
									   . '&return=' . $return); ?>">
									<?php echo $this->escape($item->name); ?>
								</a>
							<?php else : ?>
								<span class="nowrap"><?php echo $this->escape($item->name); ?></span>
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
					</td>
					<td class="nowrap">
						<?php echo $item->user_email; ?>
					</td>
					<td class="nowrap">
						<?php echo preg_replace('/(\\+\\d{1})(\\d{3})(\\d{3})(\\d{2})(\\d{2})/',
							'$1($2)$3-$4-$5', $item->user_phone); ?>
					</td>
					<td class="nowrap">
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
						</div>
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
							Text::_('DATE_FORMAT_LC2')) : '-' ?>
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
