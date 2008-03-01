jQuery(document).ready(function() {
	jQuery('div.wpcf7 > form').ajaxForm({
		beforeSubmit: wpcf7BeforeSubmit,
		dataType: 'json',
		success: wpcf7ProcessJson
	});
});

// Exclusive checkbox
function wpcf7ExclusiveCheckbox(elem) {
  jQuery(elem.form).find('input:checkbox[@name="' + elem.name + '"]').not(elem).removeAttr('checked');
}

// Toggle submit button
function wpcf7ToggleSubmit(elem) {
  var submit = jQuery(elem.form).find('input:submit');
  var acceptances = jQuery(elem.form).find('input:checkbox.wpcf7-acceptance');
  
  submit.removeAttr('disabled');
  acceptances.each(function(i, n) {
    n = jQuery(n);
    if (n.hasClass('wpcf7-invert') && n.is(':checked') || ! n.hasClass('wpcf7-invert') && ! n.is(':checked'))
      submit.attr('disabled', 'disabled');
  });
}

function wpcf7BeforeSubmit(formData, jqForm, options) {
	wpcf7ClearResponseOutput();
	jQuery('img.ajax-loader', jqForm[0]).css({ visibility: 'visible' });
  
  formData.push({name: '_wpcf7_is_ajax_call', value: 1});
  
	return true;
}

function wpcf7NotValidTip(input, message) {
	jQuery(input).after('<span class="wpcf7-not-valid-tip">' + message + '</span>');
	jQuery('span.wpcf7-not-valid-tip').mouseover(function() {
		jQuery(this).fadeOut('fast');
	});
	jQuery(input).mouseover(function() {
		jQuery(input).siblings('.wpcf7-not-valid-tip').not(':hidden').fadeOut('fast');
	});
	jQuery(input).focus(function() {
		jQuery(input).siblings('.wpcf7-not-valid-tip').not(':hidden').fadeOut('fast');
	});
}

function wpcf7ProcessJson(data) {
	var wpcf7ResponseOutput = jQuery(data.into).find('div.wpcf7-response-output');
	wpcf7ClearResponseOutput();
	if (data.invalids) {
		jQuery.each(data.invalids, function(i, n) {
			wpcf7NotValidTip(jQuery(data.into).find(n.into), n.message);
		});
		wpcf7ResponseOutput.addClass('wpcf7-validation-errors');
	}
	if (data.captcha) {
		jQuery.each(data.captcha, function(i, n) {
			jQuery(data.into).find(':input[@name="' + i + '"]').clearFields();
			jQuery(data.into).find('img.wpcf7-captcha-' + i).attr('src', n);
			var match = /([0-9]+)\.(png|gif|jpeg)$/.exec(n);
			jQuery(data.into).find('input:hidden[@name="_wpcf7_captcha_challenge_' + i + '"]').attr('value', match[1]);
		});
	}
	if (1 == data.spam) {
		wpcf7ResponseOutput.addClass('wpcf7-spam-blocked');
	}
	if (1 == data.mailSent) {
		jQuery(data.into).find('form').resetForm().clearForm();
		wpcf7ResponseOutput.addClass('wpcf7-mail-sent-ok');
	} else {
		wpcf7ResponseOutput.addClass('wpcf7-mail-sent-ng');
	}
	wpcf7ResponseOutput.append(data.message).fadeIn('fast');
}

function wpcf7ClearResponseOutput() {
	jQuery('div.wpcf7-response-output').hide().empty().removeClass('wpcf7-mail-sent-ok wpcf7-mail-sent-ng wpcf7-validation-errors wpcf7-spam-blocked');
	jQuery('span.wpcf7-not-valid-tip').remove();
	jQuery('img.ajax-loader').css({ visibility: 'hidden' });
}