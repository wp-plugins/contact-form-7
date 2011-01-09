(function($) {

	$(function() {
		try {
			$.extend($.tgPanes, _wpcf7.tagGenerators);
			$('#taggenerator').tagGenerator(_wpcf7L10n.generateTag,
				{ dropdownIconUrl: _wpcf7.pluginUrl + '/images/dropdown.gif' });

			$('input#wpcf7-title:enabled').css({
				cursor: 'pointer'
			});

			$('input#wpcf7-title').mouseover(function() {
				$(this).not('.focus').css({
					'background-color': '#ffffdd'
				});
			});

			$('input#wpcf7-title').mouseout(function() {
				$(this).css({
					'background-color': '#fff'
				});
			});

			$('input#wpcf7-title').focus(function() {
				$(this).addClass('focus');
				$(this).css({
					cursor: 'text',
					color: '#333',
					border: '1px solid #777',
					font: 'normal 13px Verdana, Arial, Helvetica, sans-serif',
					'background-color': '#fff'
				});
			});

			$('input#wpcf7-title').blur(function() {
				$(this).removeClass('focus');
				$(this).css({
					cursor: 'pointer',
					color: '#555',
					border: 'none',
					font: 'bold 20px serif',
					'background-color': '#fff'
				});
			});

			$('input#wpcf7-title').change(function() {
				updateTag();
			});

			updateTag();

			if ($.support.objectAll) {
				if (! $('#wpcf7-mail-2-active').is(':checked'))
					$('#mail-2-fields').hide();

				$('#wpcf7-mail-2-active').click(function() {
					if ($('#mail-2-fields').is(':hidden')
					&& $('#wpcf7-mail-2-active').is(':checked')) {
						$('#mail-2-fields').slideDown('fast');
					} else if ($('#mail-2-fields').is(':visible')
					&& $('#wpcf7-mail-2-active').not(':checked')) {
						$('#mail-2-fields').slideUp('fast');
					}
				});
			}

			$('#message-fields-toggle-switch').text(_wpcf7L10n.show);
			$('#message-fields').hide();

			$('#message-fields-toggle-switch').click(function() {
				if ($('#message-fields').is(':hidden')) {
					$('#message-fields').slideDown('fast');
					$('#message-fields-toggle-switch').text(_wpcf7L10n.hide);
				} else {
					$('#message-fields').hide('fast');
					$('#message-fields-toggle-switch').text(_wpcf7L10n.show);
				}
			});

			if ('' == $.trim($('#wpcf7-additional-settings').text())) {
				$('#additional-settings-fields-toggle-switch').text(_wpcf7L10n.show);
				$('#additional-settings-fields').hide();
			} else {
				$('#additional-settings-fields-toggle-switch').text(_wpcf7L10n.hide);
				$('#additional-settings-fields').show();
			}

			$('#additional-settings-fields-toggle-switch').click(function() {
				if ($('#additional-settings-fields').is(':hidden')) {
					$('#additional-settings-fields').slideDown('fast');
					$('#additional-settings-fields-toggle-switch').text(_wpcf7L10n.hide);
				} else {
					$('#additional-settings-fields').hide('fast');
					$('#additional-settings-fields-toggle-switch').text(_wpcf7L10n.show);
				}
			});

		} catch (e) {
		}
	});

	function updateTag() {
		var title = $('input#wpcf7-title').val();

		if (title)
			title = title.replace(/["'\[\]]/g, '');

		$('input#wpcf7-title').val(title);
		var current = $('input#wpcf7-id').val();
		var tag = '[contact-form ' + current + ' "' + title + '"]';

		$('input#contact-form-anchor-text').val(tag);
	}

})(jQuery);