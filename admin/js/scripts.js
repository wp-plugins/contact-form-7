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

			$.extend($.tgPanes, _wpcf7.tagGenerators);
			$('#taggenerator').tagGenerator(_wpcf7.generateTag, {
				dropdownIconUrl: _wpcf7.pluginUrl + '/admin/images/dropdown.gif',
				fadebuttImageUrl: _wpcf7.pluginUrl + '/admin/images/fade-butt.png' });

			$('#show-all-messages').click(function() {
				$('#messagesdiv .hide-initially').slideDown();
				$(this).closest('p').remove();
				return false;
			});

			postboxes.add_postbox_toggles(_wpcf7.screenId);

			$('#postbox-container-2').tabs();

		} catch (e) {
		}
	});

})(jQuery);