/*
 * @package    Profiles - Administrator Module
 * @version    1.0.7
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

(function ($) {
	$(document).ready(function () {
		$('[data-mod-profiles-admin]').each(function () {
			var block = $(this),
				module_id = $(block).data('mod-profiles-admin'),
				tabNew = $(block).find('[data-mod-profiles-admin-tab="new"]'),
				tabModified = $(block).find('[data-mod-profiles-admin-tab="modified"]'),
				tabOnline = $(block).find('[data-mod-profiles-admin-tab="online"]'),
				reload = $(block).find('[data-mod-profiles-admin-reload]');

			getItems(module_id, tabNew, 'new');
			getItems(module_id, tabModified, 'modified');
			getItems(module_id, tabOnline, 'online');

			$(reload).on('click', function () {
				getItems(module_id, tabNew, 'new');
				getItems(module_id, tabModified, 'modified');
				getItems(module_id, tabOnline, 'online');
			});
		});

		function getItems(module_id, block, tab) {
			var loading = block.find('.loading'),
				items = block.find('.items'),
				result = block.find('.result');
			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: 'index.php?option=com_ajax&module=profiles_admin&format=json',
				data: {module_id: module_id, tab: tab},
				beforeSend: function () {
					loading.slideDown(750);
					result.slideUp(750);
					items.html('');
				},
				success: function (response) {
					if (response.success) {
						items.html(response.data);
					}
					else {
						items.html(response.message);
					}
				},
				complete: function () {
					loading.slideUp(750);
					result.slideDown(750);
				}
			});
		}
	});
})(jQuery);