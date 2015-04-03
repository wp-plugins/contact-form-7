(function($) {

	$(function() {
		try {
			var welcomePanel = $('#welcome-panel');
			var updateWelcomePanel;

			updateWelcomePanel = function( visible ) {
				$.post( ajaxurl, {
					action: 'wpcf7-update-welcome-panel',
					visible: visible,
					welcomepanelnonce: $( '#welcomepanelnonce' ).val()
				});
			};

			$('a.welcome-panel-close', welcomePanel).click(function(event) {
				event.preventDefault();
				welcomePanel.addClass('hidden');
				updateWelcomePanel( 0 );
			});

			$('div.cf7com-links').insertAfter($('div.wrap h2:first'));

			$.extend($.tgPanes, _wpcf7.tagGenerators);
			$('#taggenerator').tagGenerator(_wpcf7.generateTag, {
				dropdownIconUrl: _wpcf7.pluginUrl + '/admin/images/dropdown.gif',
				fadebuttImageUrl: _wpcf7.pluginUrl + '/admin/images/fade-butt.png' });

			$('#show-all-messages').click(function() {
				$('#messagesdiv .hide-initially').slideDown();
				$(this).closest('p').remove();
				return false;
			});

			$('.check-if-these-fields-are-active').each(function(index) {
				if (! $(this).is(':checked'))
					$(this).parent().siblings('.mail-fields').hide();

				$(this).click(function() {
					if ($(this).parent().siblings('.mail-fields').is(':hidden')
					&& $(this).is(':checked')) {
						$(this).parent().siblings('.mail-fields').slideDown('fast');
					} else if ($(this).parent().siblings('.mail-fields').is(':visible')
					&& $(this).not(':checked')) {
						$(this).parent().siblings('.mail-fields').slideUp('fast');
					}
				});
			});

			postboxes.add_postbox_toggles(_wpcf7.screenId);

		} catch (e) {
		}
	});

})(jQuery);