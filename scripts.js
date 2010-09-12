(function($) {

	$(function() {
		try {
			if (typeof _wpcf7 == 'undefined' || _wpcf7 === null)
				_wpcf7 = {};

			_wpcf7 = $.extend({ cached: 0 }, _wpcf7);

			$('div.wpcf7 > form').ajaxForm({
				beforeSubmit: function(formData, jqForm, options) {
					jqForm.clearResponseOutput();
					jqForm.find('img.ajax-loader').css({ visibility: 'visible' });
					return true;
				},
				beforeSerialize: function(jqForm, options) {
					jqForm.find('.wpcf7-use-title-as-watermark.watermark').each(function(i, n) {
						$(n).val('');
					});
					return true;
				},
				data: { '_wpcf7_is_ajax_call': 1 },
				dataType: 'json',
				success: function(data) {
					var wpcf7ResponseOutput = $(data.into).find('div.wpcf7-response-output');
					$(data.into).clearResponseOutput();

					if (data.invalids) {
						$.each(data.invalids, function(i, n) {
							wpcf7NotValidTip($(data.into).find(n.into), n.message);
						});
						wpcf7ResponseOutput.addClass('wpcf7-validation-errors');
					}

					if (data.captcha)
						wpcf7RefillCaptcha(data.into, data.captcha);

					if (data.quiz)
						wpcf7RefillQuiz(data.into, data.quiz);

					if (1 == data.spam)
						wpcf7ResponseOutput.addClass('wpcf7-spam-blocked');

					if (1 == data.mailSent) {
						$(data.into).find('form').resetForm().clearForm();
						wpcf7ResponseOutput.addClass('wpcf7-mail-sent-ok');

						if (data.onSentOk)
							$.each(data.onSentOk, function(i, n) { eval(n) });
					} else {
						wpcf7ResponseOutput.addClass('wpcf7-mail-sent-ng');
					}

					if (data.onSubmit)
						$.each(data.onSubmit, function(i, n) { eval(n) });

					$(data.into).find('.wpcf7-use-title-as-watermark.watermark').each(function(i, n) {
						$(n).val($(n).attr('title'));
					});

					wpcf7ResponseOutput.append(data.message).slideDown('fast');
				}
			});

			$('div.wpcf7 > form').each(function(i, n) {
				if (_wpcf7.cached)
					wpcf7OnloadRefill($(n));

				$(n).toggleSubmit();

				$(n).find('.wpcf7-acceptance').click(function() {
					$(n).toggleSubmit();
				});

				$(n).find('.wpcf7-exclusive-checkbox').each(function(i, n) {
					$(n).find('input:checkbox').click(function() {
						$(n).find('input:checkbox').not(this).removeAttr('checked');
					});
				});

				$(n).find('.wpcf7-use-title-as-watermark').each(function(i, n) {
					var input = $(n);
					input.val(input.attr('title'));
					input.addClass('watermark');

					input.focus(function() {
						if ($(this).hasClass('watermark')) {
							$(this).val('');
							$(this).removeClass('watermark');
						}
					});

					input.blur(function() {
						if ('' == $(this).val()) {
							$(this).val($(this).attr('title'));
							$(this).addClass('watermark');
						}
					});
				});
			});

		} catch (e) {
		}
	});

	$.fn.toggleSubmit = function() {
		return this.each(function() {
			var form = $(this);
			if (this.tagName.toLowerCase() != 'form')
				form = $(this).find('form').first();

			if (form.hasClass('wpcf7-acceptance-as-validation'))
				return;

			var submit = form.find('input:submit');
			if (! submit.length) return;

			var acceptances = form.find('input:checkbox.wpcf7-acceptance');
			if (! acceptances.length) return;

			submit.removeAttr('disabled');
			acceptances.each(function(i, n) {
				n = $(n);
				if (n.hasClass('wpcf7-invert') && n.is(':checked')
				|| ! n.hasClass('wpcf7-invert') && ! n.is(':checked'))
					submit.attr('disabled', 'disabled');
			});
		});
	};

	function wpcf7NotValidTip(into, message) {
		$(into).append('<span class="wpcf7-not-valid-tip">' + message + '</span>');
		$('span.wpcf7-not-valid-tip').mouseover(function() {
			$(this).fadeOut('fast');
		});
		$(into).find(':input').mouseover(function() {
			$(into).find('.wpcf7-not-valid-tip').not(':hidden').fadeOut('fast');
		});
		$(into).find(':input').focus(function() {
			$(into).find('.wpcf7-not-valid-tip').not(':hidden').fadeOut('fast');
		});
	}

	function wpcf7OnloadRefill(form) {
		var url = $(form).attr('action');
		if (0 < url.indexOf('#'))
			url = url.substr(0, url.indexOf('#'));

		var id = $(form).find('input[name="_wpcf7"]').val();
		var unitTag = $(form).find('input[name="_wpcf7_unit_tag"]').val();

		$.getJSON(url,
			{ _wpcf7_is_ajax_call: 1, _wpcf7: id },
			function(data) {
				if (data && data.captcha) {
					wpcf7RefillCaptcha('#' + unitTag, data.captcha);
				}
				if (data && data.quiz) {
					wpcf7RefillQuiz('#' + unitTag, data.quiz);
				}
			}
		);
	}

	function wpcf7RefillCaptcha(form, captcha) {
		$.each(captcha, function(i, n) {
			$(form).find(':input[name="' + i + '"]').clearFields();
			$(form).find('img.wpcf7-captcha-' + i).attr('src', n);
			var match = /([0-9]+)\.(png|gif|jpeg)$/.exec(n);
			$(form).find('input:hidden[name="_wpcf7_captcha_challenge_' + i + '"]').attr('value', match[1]);
		});
	}

	function wpcf7RefillQuiz(form, quiz) {
		$.each(quiz, function(i, n) {
			$(form).find(':input[name="' + i + '"]').clearFields();
			$(form).find(':input[name="' + i + '"]').siblings('span.wpcf7-quiz-label').text(n[0]);
			$(form).find('input:hidden[name="_wpcf7_quiz_answer_' + i + '"]').attr('value', n[1]);
		});
	}

	$.fn.clearResponseOutput = function() {
		return this.each(function() {
			$(this).find('div.wpcf7-response-output').hide().empty().removeClass('wpcf7-mail-sent-ok wpcf7-mail-sent-ng wpcf7-validation-errors wpcf7-spam-blocked');
			$(this).find('span.wpcf7-not-valid-tip').remove();
			$(this).find('img.ajax-loader').css({ visibility: 'hidden' });
		});
	};

})(jQuery);