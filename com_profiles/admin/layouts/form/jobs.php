<?php
/**
 * @package    Profiles Component
 * @version    1.0.5
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

extract($displayData);

HTMLHelper::_('jquery.framework');
HTMLHelper::_('stylesheet', 'media/com_profiles/css/form-jobs.min.css', array('version' => 'auto'));
HTMLHelper::_('script', 'media/com_profiles/js/form-jobs.min.js', array('version' => 'auto'));

?>
<div id="<?php echo $id; ?>" data-input-jobs="<?php echo $id; ?>">
	<div class="list clearfix">
		<?php foreach ($jobs as $company): ?>
			<div class="item well <?php echo ($company->confirm == 'company') ? 'wait-company' : ''; ?>"
				 data-company="<?php echo $company->id; ?>">
				<div class="inner">
					<a class="logo" href="<?php echo $company->link; ?>" target="_blank">
						<?php if ($company->logo): ?>
							<img src="<?php echo $company->logo; ?>" alt="">
						<?php else: ?>
							<div class="text">
								<?php echo $company->name; ?>
							</div>
						<?php endif; ?>
					</a>
					<div class="content span12">
						<div class="position">
							<input type="text" id="<?php echo $id; ?>_<?php echo $company->id; ?>_position"
								   name="<?php echo $name; ?>[<?php echo $company->id; ?>][position]"
								   value="<?php echo $company->position; ?>"
								   placeholder="<?php echo Text::_('COM_PROFILES_PROFILE_JOB_POSITION'); ?>"
								   class="span12" <?php echo ($company->confirm !== 'confirm') ? ' readonly' : ''; ?>>
						</div>
						<div class="as_company">
							<label for="<?php echo $id; ?>_<?php echo $company->id; ?>_as_company" class="checkbox">
								<input type="checkbox"
									   value="1"<?php echo ($company->as_company) ? ' checked ' : ' '; ?>
									   id="<?php echo $id; ?>_<?php echo $company->id; ?>_as_company"
									   name="<?php echo $name; ?>[<?php echo $company->id; ?>][as_company]"
									<?php echo ($company->confirm !== 'confirm') ? ' disabled="disabled" ' : ''; ?>>
								<?php echo Text::_('COM_PROFILES_PROFILE_JOB_AS_COMPANY'); ?>
							</label>
						</div>
					</div>
					<div class="actions">
						<a class="delete btn btn-mini btn-danger"
						   title="<?php echo Text::sprintf('COM_PROFILES_PROFILE_JOBS_DELETE_LABEL', $company->name); ?>">
							<i class="icon-remove"></i>
						</a>
						<?php if ($company->confirm == 'user'): ?>
							<a class="confirm btn btn-mini btn-success">
								<?php echo Text::_('COM_PROFILES_PROFILE_JOBS_CONFIRM_SUBMIT'); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
				<?php if ($company->confirm !== 'confirm'): ?>
					<div class="confirm">
						<?php if ($company->confirm == 'user'): ?>
							<div class="text-warning text-small">
								<?php echo Text::_('COM_PROFILES_PROFILE_JOBS_CONFIRM_NEED_USER'); ?>
							</div>
						<?php elseif ($company->confirm == 'company'): ?>
							<div class="text-warning text-small">
								<?php echo Text::_('COM_PROFILES_PROFILE_JOBS_CONFIRM_NEED_COMPANY'); ?>
							</div>
						<?php elseif ($company->confirm == 'error'): ?>
							<div class="text-error text-small">
								<?php echo Text::_('COM_PROFILES_PROFILE_JOBS_CONFIRM_ERROR'); ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
