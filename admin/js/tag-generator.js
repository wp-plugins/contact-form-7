(function($) {

	if (typeof _wpcf7 == 'undefined' || _wpcf7 === null) {
		_wpcf7 = {};
	}

	_wpcf7.taggen = {};

	$(function() {
		$('form.tag-generator-panel').each(function() {
			_wpcf7.taggen.update($(this));
		});
	});

	$('form.tag-generator-panel').submit(function(event) {
		return false;
	});

	$('form.tag-generator-panel .control-box :input').change(function(event) {
		var $form = $(this).closest('form.tag-generator-panel');
		_wpcf7.taggen.normalize($(this));
		_wpcf7.taggen.update($form);
	});

	_wpcf7.taggen.update = function($form) {
		var required = $form.find(':input[name="required"]').is(':checked');
		$form.find('input.tag').val('[test]');
	};

	_wpcf7.taggen.normalize = function($input) {
		var val = $input.val();

		if ($input.is('input[name="name"]')) {
			val = val.replace(/[^0-9a-zA-Z:._-]/g, '').replace(/^[^a-zA-Z]+/, '');
		}

		if ($input.is('.numeric')) {
			val = val.replace(/[^0-9.-]/g, '');
		}

		if ($input.is('.idvalue')) {
			val = val.replace(/[^-0-9a-zA-Z_]/g, '');
		}

		if ($input.is('.classvalue')) {
			val = $.map(val.split(' '), function(n) {
				return n.replace(/[^-0-9a-zA-Z_]/g, '');
			}).join(' ');

			val = $.trim(val.replace(/\s+/g, ' '));
		}

		if ($input.is('.color')) {
			val = val.replace(/[^0-9a-fA-F]/g, '');
		}

		if ($input.is('.filesize')) {
			val = val.replace(/[^0-9kKmMbB]/g, '');
		}

		if ($input.is('.filetype')) {
			val = val.replace(/[^0-9a-zA-Z.,|\s]/g, '');
		}

		if ($input.is('.date')) {
			if (! val.match(/^\d{4}-\d{2}-\d{2}$/)) { // 'yyyy-mm-dd' ISO 8601 format
				val = '';
			}
		}

		if ($input.is(':input[name="values"]')) {
			val = $.trim(val);
		}

		$input.val(val);

		if ($input.is(':checkbox.exclusive')) {
			_wpcf7.taggen.exclusiveCheckbox($input);
		}
	}

	_wpcf7.taggen.exclusiveCheckbox = function($cb) {
		if ($cb.is(':checked')) {
			$cb.siblings(':checkbox.exclusive').prop('checked', false);
		}
	};

})(jQuery);
