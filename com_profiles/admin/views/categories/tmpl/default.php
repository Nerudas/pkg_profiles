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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$user      = Factory::getUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = ($listOrder == 'c.lft' && strtolower($listDirn) == 'asc');

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_profiles&task=categories.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'categoryList', 'adminForm', strtolower($listDirn), $saveOrderingUrl, false, true);
}

$columns = 6;
?>

<form action="<?php echo Route::_('index.php?option=com_profiles&view=categories'); ?>" method="post" name="adminForm"
	  id="adminForm">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<table id="categoryList" class="table table-striped">
				<thead>
				<tr>
					<th width="1%" class="nowrap center hidden-phone">
						<?php echo HTMLHelper::_('searchtools.sort', '', 'c.lft', $listDirn, $listOrder,
							null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
					</th>
					<th width="1%" class="center">
						<?php echo HTMLHelper::_('grid.checkall'); ?>
					</th>
					<th width="2%" style="min-width:100px" class="center">
						<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'c.state', $listDirn, $listOrder); ?>
					</th>
					<th class="nowrap">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'c.title', $listDirn, $listOrder); ?>
					</th>
					<th class="nowrap hidden-phone">
						<?php echo Text::_('JGLOBAL_FIELD_TAGS_LABEL'); ?>
					</th>
					<th width="10%" class="nowrap hidden-phone">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
					</th>
					<th width="1%" class="nowrap hidden-phone center">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'c.id', $listDirn, $listOrder); ?>
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
				<?php foreach ($this->items as $i => $item) :
					$orderkey = array_search($item->id, $this->ordering[$item->parent_id]);
					$canEdit = $user->authorise('core.edit', '#__profiles_categories.' . $item->id);
					$canChange = $user->authorise('core.edit.state', '#__profiles_categories.' . $item->id);
					$baseCategory = (in_array($item->id, array(1, 2, 3)));

					// Get the parents of item for sorting
					if ($item->level > 0)
					{
						$parentsStr       = '';
						$_currentParentId = $item->parent_id;
						$parentsStr       = ' ' . $_currentParentId;
						for ($i2 = 0; $i2 < $item->level; $i2++)
						{
							foreach ($this->ordering as $k => $v)
							{
								$v = implode('-', $v);
								$v = '-' . $v . '-';
								if (strpos($v, '-' . $_currentParentId . '-') !== false)
								{
									$parentsStr       .= ' ' . $k;
									$_currentParentId = $k;
									break;
								}
							}
						}
					}
					else
					{
						$parentsStr = '';
					}
					?>
					<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->parent_id; ?>"
						item-id="<?php echo $item->id ?>" parents="<?php echo $parentsStr ?>"
						level="<?php echo $item->level ?>">
						<td class="order nowrap center hidden-phone">
							<?php
							$iconClass = '';
							if (!$canChange)
							{
								$iconClass = ' inactive';
							}
							elseif (!$saveOrder)
							{
								$iconClass = ' inactive tip-top hasTooltip" title="' .
									HTMLHelper::_('tooltipText', 'JORDERINGDISABLED');
							}
							?>
							<span class="sortable-handler<?php echo $iconClass ?>">
								<span class="icon-menu"></span></span>

							<?php if ($canChange && $saveOrder) : ?>
								<input type="text" style="display:none" name="order[]" size="5"
									   value="<?php echo $orderkey + 1; ?>"/>
							<?php endif; ?>
						</td>
						<td class="center">
							<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="center nowrap">
							<div class="btn-group">
								<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'categories.',
									($canChange && !$baseCategory), 'cb'); ?>
								<?php if ($item->state != -2): ?>
									<?php echo HTMLHelper::_('jgrid.action', $i, 'categories.trash', array(
										'tip'          => Text::_('JTOOLBAR_TRASH'),
										'active_class' => 'trash', 'inactive_class' => 'trash',
										'enabled'      => ($canChange && !$baseCategory))); ?>
								<?php endif; ?>
							</div>
						</td>
						<td class="nowrap">
							<?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => ($item->level + 1))); ?>
							<?php if ($canEdit) : ?>
								<a class="hasTooltip" title="<?php echo Text::_('JACTION_EDIT'); ?>"
								   href="<?php echo Route::_('index.php?option=com_profiles&task=category.edit&id=' . $item->id); ?>">
									<?php echo $this->escape($item->title); ?>
								</a>
							<?php else : ?>
								<?php echo $this->escape($item->title); ?>
							<?php endif; ?>
						</td>
						<td class="hidden-phone table-tags">
							<?php foreach ($item->tags as $t => $tag): ?>
								<span class="tag"><?php echo $tag->title; ?></span>
							<?php endforeach; ?>
						</td>
						<td class="small hidden-phone">
							<?php echo $this->escape($item->access_level); ?>
						</td>
						<td class="hidden-phone center">
							<?php echo $item->id; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php echo LayoutHelper::render('plugins.system.fieldtypes.tags.batch.modal',
				array('task' => 'category.batch')); ?>
		<?php endif; ?>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>