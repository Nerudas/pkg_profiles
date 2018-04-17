/*
 * @package    Profiles Component
 * @version    1.0.5
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

(function ($) {
	$(document).ready(function () {
		var params = Joomla.getOptions('user.social.params', ''),
			user_id = params.user_id,
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

		$('[data-user-social-authorization]').each(function () {
			$(this).on('click', function () {
				var popupURL = params.authorizationURL + '&' +
					$.param({
						'user_id': user_id,
						'provider': $(this).data('user-social-authorization'),
						'popup': 1
					})
				;
				window.open(popupURL, null, popupParams);
			});
		});
		$('[data-user-social-disconnect]').each(function () {
			$(this).on('click', function () {
				var popupURL = params.disconnectURL + '&' +
					$.param({
						'user_id': user_id,
						'provider': $(this).data('user-social-disconnect'),
						'popup': 1
					})
				;
				window.open(popupURL, null, popupParams);
			});
		});
	});
})(jQuery);
