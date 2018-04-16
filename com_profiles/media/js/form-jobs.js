/*
 * @package    Profiles Component
 * @version    1.0.4
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

(function ($) {
	$(document).ready(function () {
		$('[data-input-jobs]').each(function () {
			var field = $(this),
				id = field.attr('id'),
				employees = field.find('[data-company]'),
				job_data = employees.find('input'),
				job_actions = employees.find('.actions'),
				job_confirm = job_actions.find('.confirm'),
				job_delete = job_actions.find('.delete'),
				params = Joomla.getOptions(id, ''),
				user_id = params.user_id;

			// Confirm employee
			$(job_confirm).on('click', function () {
				var popupURL = params.confirmURL + '&' +
					$.param({
						'company_id': $(this).closest('[data-company]').data('company'),
						'user_id': user_id,
						'popup': 1
					});

				var popupWidth = $(window).width() / 2,
					popupHeight = $(window).height() / 2;

				if (popupWidth < 320) {
					popupWidth = 320;
				}
				if (popupHeight < 200) {
					popupHeight = 200;
				}
				var popupParams = 'height=' + popupHeight + ',width=' + popupWidth +
					',menubar=no,toolbar=no,location=no,directories=no,status=no,resizable=no,scrollbars=no';

				window.open(popupURL, null, popupParams);

			});

			// Delete employee
			$(job_delete).on('click', function () {
				if (confirm($(this).attr('title') + '?')) {
					var popupURL = params.deleteURL + '&' +
						$.param({
							'company_id': $(this).closest('[data-company]').data('company'),
							'user_id': user_id,
							'popup': 1
						});

					var popupWidth = $(window).width() / 2,
						popupHeight = $(window).height() / 2;

					if (popupWidth < 320) {
						popupWidth = 320;
					}
					if (popupHeight < 200) {
						popupHeight = 200;
					}
					var popupParams = 'height=' + popupHeight + ',width=' + popupWidth +
						',menubar=no,toolbar=no,location=no,directories=no,status=no,resizable=no,scrollbars=no';

					window.open(popupURL, null, popupParams);
				}
			});

			// Change data
			$(job_data).on('change', function () {
				var item = $(this).closest('[data-company]'),
					changeURL = params.changeURL,
					ajaxData = {};
				ajaxData.user_id = user_id;
				ajaxData.company_id = $(item).data('company');
				ajaxData.position = $(item).find('[name*="position"]').val();
				ajaxData.as_company = 0;
				if ($(item).find('[name*="as_company"]').prop('checked')) {
					ajaxData.as_company = 1;
				}
				$.ajax({
					type: 'POST',
					dataType: 'json',
					url: changeURL,
					data: ajaxData
				});
			});

		});
	});
})(jQuery);