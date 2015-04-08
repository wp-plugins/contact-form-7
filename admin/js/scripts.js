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

			postboxes.add_postbox_toggles(_wpcf7.screenId);

			$('#contact-form-editor').tabs({
				active: _wpcf7.activeTab,
				activate: function(event, ui) {
					$('#active-tab').val(ui.newTab.index());
				}
			});

			if ('' == $('#title').val()) {
				$('#title').focus();
			}

			$.wpcf7TitleHint();

		} catch (e) {
		}
	});

	/**
	 * Copied from wptitlehint() in wp-admin/js/post.js
	 */
	$.wpcf7TitleHint = function() {
		var title = $('#title');
		var titleprompt = $('#title-prompt-text');

		if ('' == title.val()) {
			titleprompt.removeClass('screen-reader-text');
		}

		titleprompt.click(function() {
			$(this).addClass('screen-reader-text');
			title.focus();
		});

		title.blur(function() {
			if ('' == $(this).val()) {
				titleprompt.removeClass('screen-reader-text');
			}
		}).focus(function() {
			titleprompt.addClass('screen-reader-text');
		}).keydown(function(e) {
			titleprompt.addClass('screen-reader-text');
			$(this).unbind(e);
		});
	};

})(jQuery);