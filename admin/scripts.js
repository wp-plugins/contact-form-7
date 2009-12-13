jQuery(document).ready(function() {
	try {
		jQuery('#taggenerator').tagGenerator(_wpcf7L10n.generateTag,
			{ dropdownIconUrl: _wpcf7.pluginUrl + '/images/dropdown.gif' });

		jQuery('input#wpcf7-title:enabled').css({
			cursor: 'pointer'
		});

		jQuery('input#wpcf7-title').mouseover(function() {
			jQuery(this).not('.focus').css({
				'background-color': '#ffffdd'
			});
		});

		jQuery('input#wpcf7-title').mouseout(function() {
			jQuery(this).css({
				'background-color': '#fff'
			});
		});

		jQuery('input#wpcf7-title').focus(function() {
			jQuery(this).addClass('focus');
			jQuery(this).css({
				cursor: 'text',
				color: '#333',
				border: '1px solid #777',
				font: 'normal 13px Verdana, Arial, Helvetica, sans-serif',
				'background-color': '#fff'
			});
		});

		jQuery('input#wpcf7-title').blur(function() {
			jQuery(this).removeClass('focus');
			jQuery(this).css({
				cursor: 'pointer',
				color: '#555',
				border: 'none',
				font: 'bold 20px serif',
				'background-color': '#fff'
			});
		});

		jQuery('input#wpcf7-title').change(function() {
			updateTag();
		});

		updateTag();

		if (! jQuery('#wpcf7-mail-2-active').is(':checked'))
			jQuery('#mail-2-fields').hide();

		jQuery('#wpcf7-mail-2-active').click(function() {
			if (jQuery('#wpcf7-mail-2-active').is(':checked')) {
				if (jQuery('#mail-2-fields').is(':hidden'))
					jQuery('#mail-2-fields').slideDown('fast');
			} else {
				if (jQuery('#mail-2-fields').is(':visible'))
					jQuery('#mail-2-fields').hide('fast');
			}
		});

		jQuery('#message-fields-toggle-switch').text(_wpcf7L10n.show);
		jQuery('#message-fields').hide();

		jQuery('#message-fields-toggle-switch').click(function() {
			if (jQuery('#message-fields').is(':hidden')) {
				jQuery('#message-fields').slideDown('fast');
				jQuery('#message-fields-toggle-switch').text(_wpcf7L10n.hide);
			} else {
				jQuery('#message-fields').hide('fast');
				jQuery('#message-fields-toggle-switch').text(_wpcf7L10n.show);
			}
		});

		if ('' == jQuery.trim(jQuery('#wpcf7-additional-settings').text())) {
			jQuery('#additional-settings-fields-toggle-switch').text(_wpcf7L10n.show);
			jQuery('#additional-settings-fields').hide();
		} else {
			jQuery('#additional-settings-fields-toggle-switch').text(_wpcf7L10n.hide);
			jQuery('#additional-settings-fields').show();
		}

		jQuery('#additional-settings-fields-toggle-switch').click(function() {
			if (jQuery('#additional-settings-fields').is(':hidden')) {
				jQuery('#additional-settings-fields').slideDown('fast');
				jQuery('#additional-settings-fields-toggle-switch').text(_wpcf7L10n.hide);
			} else {
				jQuery('#additional-settings-fields').hide('fast');
				jQuery('#additional-settings-fields-toggle-switch').text(_wpcf7L10n.show);
			}
		});

	} catch (e) {
	}
});

function updateTag() {
	var title = jQuery('input#wpcf7-title').val();

	if (title)
		title = title.replace(/["'\[\]]/g, '');

	jQuery('input#wpcf7-title').val(title);
	var current = jQuery('input#wpcf7-id').val();
	var tag = '[contact-form ' + current + ' "' + title + '"]';

	jQuery('input#contact-form-anchor-text').val(tag);
}


/* Tag Generator */

jQuery.extend(jQuery.tgPanes, {
	text: {
		title: _wpcf7L10n.textField,
		content: 'wpcf7-tg-pane-text'
	},

	email: {
		title: _wpcf7L10n.emailField,
		content: 'wpcf7-tg-pane-email'
	},

	textarea: {
		title: _wpcf7L10n.textArea,
		content: 'wpcf7-tg-pane-textarea'
	},

	menu: {
		title: _wpcf7L10n.menu,
		content: 'wpcf7-tg-pane-menu'
	},

	checkbox: {
		title: _wpcf7L10n.checkboxes,
		content: 'wpcf7-tg-pane-checkbox'
	},

	radio: {
		title: _wpcf7L10n.radioButtons,
		content: 'wpcf7-tg-pane-radio'
	},

	acceptance: {
		title: _wpcf7L10n.acceptance,
		content: 'wpcf7-tg-pane-acceptance'
	},

	quiz: {
		title: _wpcf7L10n.quiz,
		content: 'wpcf7-tg-pane-quiz'
	},

	captcha: {
		title: _wpcf7L10n.captcha,
		content: 'wpcf7-tg-pane-captcha',
		change: jQuery.tgCreateTagForCaptcha
	},

	file: {
		title: _wpcf7L10n.fileUpload,
		content: 'wpcf7-tg-pane-file'
	},

	submit: {
		title: _wpcf7L10n.submit,
		content: 'wpcf7-tg-pane-submit',
		nameless: 1
	}
});