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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

HTMLHelper::_('jquery.framework');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('formbehavior.chosen', 'select');

Factory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function(task)
	{
		if (task == "category.cancel" || document.formvalidator.isValid(document.getElementById("item-form")))
		{
			Joomla.submitform(task, document.getElementById("item-form"));
		}
	};
');
?>
<form action="<?php echo Route::_('index.php?option=com_profiles&view=category&id=' . $this->item->id); ?>"
      method="post" name="adminForm" id="item-form" class="form-validate" enctype="multipart/form-data">
	<div class="form-inline form-inline-header">
		<?php echo $this->form->renderFieldset('header'); ?>
	</div>
	<div class="row-fluid">
		<div class="span9">
			<fieldset class="form-horizontal">
				<?php echo $this->form->getInput('description'); ?>
			</fieldset>
		</div>
		<div class="span3">
			<div class="well">
				<fieldset class="form-horizontal form-horizontal-desktop">
					<div class="control-group">
						<?php echo $this->form->getInput('image'); ?>
					</div>
					<?php echo $this->form->renderFieldset('global'); ?>
				</fieldset>
			</div>
		</div>
	</div>
	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="return" value="<?php echo Factory::getApplication()->input->getCmd('return'); ?>"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>