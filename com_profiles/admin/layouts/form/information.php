<?php
/**
 * @package    Profiles Component
 * @version    1.0.3
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

extract($displayData);

use Joomla\CMS\Language\Text;

?>
<div id="<?php echo $id; ?>">
	<?php if (!empty($value)): ?>
		<div class="well">
			<div class="row-fluid form-horizontal-desktop">
				<div class="span6">
					<div class="avatar">
						<div class="image" style="background-image: url('<?php echo $value['avatar']; ?>')"></div>
					</div>
					<div class="name">
						<a href="<?php echo $value['link']; ?>" target="_blank"><?php echo $value['name']; ?></a>
					</div>
					<div class="region center muted">
						(<?php echo $value['region']; ?>)
					</div>
					<?php if (!empty($value['job_id'])): ?>
						<div class="job">
							<div><strong><?php echo Text::_('COM_PROFILES_PROFILE_JOBS'); ?></strong></div>
						</div>
						<dl class="dl-horizontal">
							<dt>
								<?php echo Text::_('COM_PROFILES_PROFILE_JOB_COMPANY_NAME'); ?>
							</dt>
							<dd>
							<dd>
								<a href="<?php echo $value['job_link']; ?>"
								   target="_blank"><?php echo $value['job_name']; ?></a>
							</dd>
							<?php if (!empty($value['job_position'])): ?>
								<dt>
									<?php echo Text::_('COM_PROFILES_PROFILE_JOB_POSITION'); ?>
								</dt>
								<dd>
								<dd>
									<?php echo $value['job_position']; ?>
								</dd>
							<?php endif; ?>
						</dl>
					<?php endif; ?>
					<?php if (!empty($value['tags'])): ?>
						<hr>
						<div class="tags">
							<?php foreach (explode(',', $value['tags']) as $tag): ?>
								<span class="label label-inverse"><?php echo $tag; ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
				<div class="span6">
					<div class="site_access">
						<div><strong><?php echo Text::_('COM_PROFILES_PROFILE_SITE_ACCESS'); ?></strong></div>
						<dl class="dl-horizontal">
							<dt>
								<?php echo Text::_('JGLOBAL_FIELD_ID_LABEL'); ?>
							</dt>
							<dd>
								<a href="<?php echo $value['link']; ?>" target="_blank"><?php echo $value['id']; ?></a>
							</dd>
							<dt>
								<?php echo Text::_('JGLOBAL_EMAIL'); ?>
							</dt>
							<dd>
								<a href="mailto:<?php echo $value['email']; ?>"><?php echo $value['email']; ?></a>
							</dd>
							<?php if (!empty($value['phone'])): ?>
								<dt>
									<?php echo Text::_('COM_PROFILES_PROFILE_PHONE'); ?>
								</dt>
								<dd>
									<a href="tel:<?php echo $value['phone']; ?>"><?php echo $value['phone']; ?></a>
								</dd>
							<?php endif; ?>
							<?php if (!empty($value['vk'])): ?>
								<dt>
									<?php echo Text::_('JGLOBAL_FIELD_SOCIAL_LABEL_VK'); ?>
								</dt>
								<dd>
									<a href="https://vk.com/id<?php echo $value['vk']; ?>" target="_blank">
										vk.com/<?php echo $value['vk']; ?>
									</a>
								</dd>
							<?php endif; ?>
							<?php if (!empty($value['facebook'])): ?>
								<dt>
									<?php echo Text::_('JGLOBAL_FIELD_SOCIAL_LABEL_FB'); ?>
								</dt>
								<dd>
									<a href="https://www.facebook.com/<?php echo $value['facebook']; ?>"
									   target="_blank">
										facebook.com/<?php echo $value['facebook']; ?>
									</a>
								</dd>
							<?php endif; ?>
							<?php if (!empty($value['odnoklassniki'])): ?>
								<dt>
									<?php echo Text::_('JGLOBAL_FIELD_SOCIAL_LABEL_OK'); ?>
								</dt>
								<dd>
									<a href="https://ok.ru/profile/<?php echo $value['odnoklassniki']; ?>"
									   target="_blank">
										ok.ru/<?php echo $value['odnoklassniki']; ?>
									</a>
								</dd>
							<?php endif; ?>
						</dl>
					</div>
					<hr>
					<div class="contacts">
						<div><strong><?php echo Text::_('COM_PROFILES_PROFILE_CONTACTS'); ?></strong></div>
						<dl class="dl-horizontal">
							<?php if (!empty($value['contacts_email'])): ?>
								<dt>
									<?php echo Text::_('JGLOBAL_EMAIL'); ?>
								</dt>
								<dd>
									<a href="mailto:<?php echo $value['email']; ?>"><?php echo $value['email']; ?></a>
								</dd>
							<?php endif; ?>
							<?php if (!empty($value['contacts_phones'])): ?>
								<dt>
									<?php echo Text::_('JGLOBAL_FIELD_PHONES_LABEL'); ?>
								</dt>
								<?php foreach (explode(',', $value['contacts_phones']) as $phone): ?>
									<dd>
										<a href="tel:<?php echo $phone; ?>"><?php echo $phone; ?></a>
									</dd>
								<?php endforeach; ?>
							<?php endif; ?>
							<?php if (!empty($value['contacts_site'])): ?>
								<dt>
									<?php echo Text::_('COM_PROFILES_PROFILE_SITE'); ?>
								</dt>
								<dd>
									<a href="<?php echo $value['contacts_site']; ?>" target="_blank">
										<?php echo $value['contacts_site']; ?>
									</a>
								</dd>
							<?php endif; ?>
							<?php if (!empty($value['contacts_vk'])): ?>
								<dt>
									<?php echo Text::_('JGLOBAL_FIELD_SOCIAL_LABEL_VK'); ?>
								</dt>
								<dd>
									<a href="https://vk.com/<?php echo $value['contacts_vk']; ?>" target="_blank">
										vk.com/<?php echo $value['contacts_vk']; ?>
									</a>
								</dd>
							<?php endif; ?>
							<?php if (!empty($value['contacts_facebook'])): ?>
								<dt>
									<?php echo Text::_('JGLOBAL_FIELD_SOCIAL_LABEL_FB'); ?>
								</dt>
								<dd>
									<a href="https://www.facebook.com/<?php echo $value['contacts_facebook']; ?>"
									   target="_blank">
										facebook.com/<?php echo $value['contacts_facebook']; ?>
									</a>
								</dd>
							<?php endif; ?>
							<?php if (!empty($value['contacts_instagram'])): ?>
								<dt>
									<?php echo Text::_('JGLOBAL_FIELD_SOCIAL_LABEL_INST'); ?>
								</dt>
								<dd>
									<a href="https://instagram.com/<?php echo $value['contacts_instagram']; ?>"
									   target="_blank">
										instagram.com/<?php echo $value['contacts_instagram']; ?>
									</a>
								</dd>
							<?php endif; ?>
							<?php if (!empty($value['contacts_odnoklassniki'])): ?>
								<dt>
									<?php echo Text::_('JGLOBAL_FIELD_SOCIAL_LABEL_OK'); ?>
								</dt>
								<dd>
									<a href="https://ok.ru/<?php echo $value['contacts_odnoklassniki']; ?>"
									   target="_blank">
										ok.ru/<?php echo $value['contacts_odnoklassniki']; ?>
									</a>
								</dd>
							<?php endif; ?>
						</dl>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>
	<?php foreach ($value as $key => $val): ?>
		<input type="hidden" name="<?php echo $name . '[' . $key . ']'; ?>" value="<?php echo $val; ?>">
	<?php endforeach; ?>
</div>
