/*
 * @package    Profiles Component
 * @version    1.3.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

(function ($) {
	$(document).ready(function () {
		var params = Joomla.getOptions('admin-user.params', ''),
			profileText = params.profileText;

		$('#jform_about').closest('.control-group').attr('id', 'about-field');
		$('#jform_tags').closest('.control-group').attr('id', 'tags-field');
		$('#jform_jobs').closest('.control-group').attr('id', 'jobs-field');
		$('#user-form >h2').remove();

		var setProfileText = setInterval(function () {
			var tab = $('#myTabTabs').find('a[href="#details"]');
			if ($(tab).length > 0) {
				$(tab).text(profileText);
				clearInterval(setProfileText);
			}
		}, 3);

		$('#groups').appendTo($('#groups-container'));
		var removeGroupsTab = setInterval(function () {
			var tab = $('#myTabTabs').find('a[href="#groups"]');
			if ($(tab).length > 0) {
				$(tab).parent().remove();
				clearInterval(removeGroupsTab);
			}
		}, 3);

		var publishingdata = $('#attrib-publishingdata'),
			metadata = $('#attrib-metadata');

		$('#attrib-publishing').html('<div class="row-fluid form-horizontal-desktop">\n' +
			'<div id="publishingdata" class="span6">' + $(publishingdata).html() + '</div>\n' +
			'<div id="metadata" class="span6">' + $(metadata).html() + '</div>\n' +
			'</div>');
		$(publishingdata).remove();
		$(metadata).remove();
		var removePublishingTabs = setInterval(function () {
			var publishingdata = $('#myTabTabs').find('a[href="#attrib-publishingdata"]'),
				metadata = $('#myTabTabs').find('a[href="#attrib-metadata"]');
			if ($(publishingdata).length > 0 && $(metadata).length > 0) {
				$(publishingdata).parent().remove();
				$(metadata).parent().remove();
				clearInterval(removePublishingTabs);
			}
		}, 3);

		var notes = $('#attrib-notes'),
			information = $('#jform_information').closest('.controls'),
			html = '<div class="row-fluid">\n' +
				'<div id="information" class="span9">' + $(information).html() + '</div>\n' +
				'<div id="note" class="span3 form-vertical"></div>\n' +
				'</div>';
		$(html).appendTo($('#details'));
		$(notes).appendTo($('#note'));
		var removeInfoTabs = setInterval(function () {
			var notes = $('#myTabTabs').find('a[href="#attrib-notes"]'),
				information = $('#myTabTabs').find('a[href="#attrib-information"]');
			if ($(notes).length > 0 && $(information).length > 0) {
				$(notes).parent().remove();
				$(information).parent().remove();
				clearInterval(removeInfoTabs);
			}
		}, 3);

		var form = $('#user-form');
		$(form).prepend($('<div id="header" class="form-inline form-inline-header"></div>'));
		$(form).find('> h4').remove();
		$(form).removeClass('form-horizontal');
		$(form).find('> fieldset').addClass('form-horizontal');
		var appendHeader = setInterval(function () {
			if ($('#header').length > 0) {
				$('#jform_name').closest('.control-group').appendTo($('#header'));
				$('#jform_alias').closest('.control-group').appendTo($('#header'));
				clearInterval(appendHeader);
			}
		}, 3);

		// Set cancel redirect
		$(form).on('submit', function () {
			if ($('[name="task"]').val() == 'user.cancel') {
				$('[name="task"]').val('profile.cancel');
				var action = $(form).attr('action').replace('com_users', 'com_profiles');
				$(form).attr('action', action);
			}
		});

	});
})(jQuery);