<?php
/**
 * @package    Profiles Component
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

extract($displayData);

?>

<label for="<?php echo $id; ?>" class="checkbox">
	<input id="" type="checkbox" name="<?php echo $name; ?>"
		   value="<?php echo $value; ?>"/>
	<?php echo $text; ?>
</label>