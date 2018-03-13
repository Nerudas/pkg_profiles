/*
 * @package    Profiles Component
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

(function ($) {
	$(document).ready(function () {
		var register_as = $('[name="jform[register_as]"]');

		$(register_as).on('change', function () {
			setRequiredFields($(this).val());
		});

		setRequiredFields($(register_as).val());

		function setRequiredFields(type) {
			var user = ['jform[name]'],
				company = ['jform[company_name]', 'jform[company_position]'];
			$(user).each(function (key, name) {
				var field = $('[name="' + name + '"]');
				if (type == 'user') {
					field.attr('required', 'required');
				}
				else {
					field.removeAttr('required');
				}
				console.log(field);
			});
			$(company).each(function (key, name) {
				var field = $('[name="' + name + '"]');
				if (type == 'company') {
					field.attr('required', 'required');
				}
				else {
					field.removeAttr('required');
				}
			});

		}
	});
})(jQuery);
