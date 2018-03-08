/*
 * @package    Profiles Package
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

(function ($) {
	$(document).ready(function () {
		var params = Joomla.getOptions('user.social.params', ''),
			popupWidth = $(window).width() / 2,
			popupHeight = $(window).height() / 2;

		if (popupWidth < 320) {
			popupWidth = 320;
		}
		if (popupHeight < 200) {
			popupHeight = 200;
		}
		var popupParams = 'height=' + popupHeight + ',width=' + popupWidth +
			',menubar=no,toolbar=no,location=no,directories=no,status=no,resizable=no,scrollbars=no';

		$('[data-user-social-add]').each(function () {
			var button = $(this);
			$(button).on('click', function () {
				var provider = $(button).data('user-social-add');
				if (params.addLink) {
					window.open(params.addLink + provider, null, popupParams);
				}
			});
		});
		$('[data-user-social-delete]').each(function () {
			var button = $(this);
			$(button).on('click', function () {
				var provider = $(button).data('user-social-delete');
				if (params.deleteLink) {
					window.open(params.deleteLink + provider, null, popupParams);
				}
			});
		});
	});
})(jQuery);
