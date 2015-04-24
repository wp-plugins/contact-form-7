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

			$('#contact-form-editor').tabs({
				active: _wpcf7.activeTab,
				activate: function(event, ui) {
					$('#active-tab').val(ui.newTab.index());
				}
			});

			$('input:checkbox.toggle-form-table').click(function(event) {
				$(this).wpcf7ToggleFormTable();
			}).wpcf7ToggleFormTable();

			if ('' == $('#title').val()) {
				$('#title').focus();
			}

			$.wpcf7TitleHint();

			$('.contact-form-editor-box-mail span.mailtag').click(function(event) {
				var range = document.createRange();
				range.selectNodeContents(this);
				window.getSelection().addRange(range);
			});

		} catch (e) {
		}
	});

	$.fn.wpcf7ToggleFormTable = function() {
		return this.each(function() {
			var formtable = $(this).closest('.contact-form-editor-box-mail').find('fieldset');

			if ($(this).is(':checked')) {
				formtable.removeClass('hidden');
			} else {
				formtable.addClass('hidden');
			}
		});
	};

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
