(function($) {

	$.fn.tagGenerator = function() {
		var menu = $('<div class="tag-generator"></div>');

		var selector = $('<span>' + _wpcf7L10n.generateTag + '</span>');

		selector.css({
			border: '1px solid #ddd',
			padding: '2px 4px',
			background: '#fff url( ../wp-admin/images/fade-butt.png ) repeat-x 0 0',
			'-moz-border-radius': '3px',
			'-khtml-border-radius': '3px',
			'-webkit-border-radius': '3px',
			'border-radius': '3px'
		});

		selector.mouseover(function() {
			$(this).css({ 'border-color': '#bbb' });
		});
		selector.mouseout(function() {
			$(this).css({ 'border-color': '#ddd' });
		});
		selector.mousedown(function() {
			$(this).css({ background: '#ddd' });
		});
		selector.mouseup(function() {
			$(this).css({
				background: '#fff url( ../wp-admin/images/fade-butt.png ) repeat-x 0 0'
			});
		});
		selector.click(function() {
			dropdown.slideDown('fast');
			return false;
		});
		$('body').click(function() {
			dropdown.hide();
		});

		var dropdown_icon = $('<img src="' + _wpcf7.pluginUrl + '/images/dropdown.gif" />');
		dropdown_icon.css({ 'vertical-align': 'bottom' });
		selector.append(dropdown_icon);

		menu.append(selector);

		var pane = $('<div class="tg-pane"></div>');
		pane.hide();
		menu.append(pane);

		var dropdown = jQuery('<div class="tg-dropdown"></div>');
		dropdown.hide();
		menu.append(dropdown);

		$.each($.tgPanes, function(i, n) {
			var submenu = $('<div>' + $.tgPanes[i].title + '</div>');
			submenu.css({
				margin: 0,
				padding: '0 4px',
				'line-height': '180%',
				background: '#fff'
			});
			submenu.mouseover(function() {
				$(this).css({ background: '#d4f2f2' });
			});
			submenu.mouseout(function() {
				$(this).css({ background: '#fff' });
			});
			submenu.click(function() {
				dropdown.hide();
				pane.hide();
				pane.empty();
				$.tgPane(pane, i);
				pane.slideDown('fast');
				return false;
			});
			dropdown.append(submenu);
		});

		this.append(menu);
	};

	$.tgPane = function(pane, tagType) {
		var closeButtonDiv = $('<div></div>');
		closeButtonDiv.css({ float: 'right' });

		var closeButton = $('<span class="tg-closebutton">&#215;</span>');
		closeButton.click(function() {
			pane.slideUp('fast').empty();
		});
		closeButtonDiv.append(closeButton);

		pane.append(closeButtonDiv);

		var paneTitle = $('<div class="tg-panetitle">' + $.tgPanes[tagType].title + '</div>');
		pane.append(paneTitle);

		pane.append($('#' + $.tgPanes[tagType].content).clone().contents());

		pane.find(':checkbox.exclusive').change(function() {
			if ($(this).is(':checked'))
				$(this).siblings(':checkbox.exclusive').removeAttr('checked');
		});

		if ($.isFunction($.tgPanes[tagType].change))
			$.tgPanes[tagType].change(pane, tagType);
		else
			$.tgCreateTag(pane, tagType);

		pane.find(':input').change(function() {
			if ($.isFunction($.tgPanes[tagType].change))
				$.tgPanes[tagType].change(pane, tagType);
			else
				$.tgCreateTag(pane, tagType);
		});
	}

	$.tgCreateTag = function(pane, tagType) {
		pane.find(':input').empty();

		pane.find('input[name="name"]').each(function(i) {
			var val = $(this).val();
			val = val.replace(/[^0-9a-zA-Z:._-]/g, '').replace(/^[^a-zA-Z]+/, '');
			if ('' == val) {
				var rand = Math.floor(Math.random() * 1000);
				val = tagType + '-' + rand;
			}
			$(this).val(val);
		});

		pane.find(':input.numeric').each(function(i) {
			var val = $(this).val();
			val = val.replace(/[^0-9]/g, '');
			$(this).val(val);
		});

		pane.find(':input.idvalue').each(function(i) {
			var val = $(this).val();
			val = val.replace(/[^-0-9a-zA-Z_]/g, '');
			$(this).val(val);
		});

		pane.find(':input.classvalue').each(function(i) {
			var val = $(this).val();
			val = $.map(val.split(' '), function(n) {
				return n.replace(/[^-0-9a-zA-Z_]/g, '');
			}).join(' ');
			val = $.trim(val.replace(/\s+/g, ' '));
			$(this).val(val);
		});

		pane.find(':input.color').each(function(i) {
			var val = $(this).val();
			val = val.replace(/[^0-9a-fA-F]/g, '');
			$(this).val(val);
		});

		pane.find(':input.filesize').each(function(i) {
			var val = $(this).val();
			val = val.replace(/[^0-9kKmMbB]/g, '');
			$(this).val(val);
		});

		pane.find(':input.filetype').each(function(i) {
			var val = $(this).val();
			val = val.replace(/[^0-9a-zA-Z.\s]/g, '');
			$(this).val(val);
		});

		pane.find(':input[name="default-value"]').each(function(i) {
			var val = $(this).val();
			val = $.trim(val);
			$(this).val(val);
		});

		var type = pane.find(':input[name="type"]').val();
		if (pane.find(':input[name="required"]').is(':checked'))
			type += '*';

		var name = pane.find(':input[name="name"]').val();

		var options = [];

		var size = pane.find(':input[name="size"]').val();
		var maxlength = pane.find(':input[name="maxlength"]').val();
		if (size || maxlength)
			options.push(size + '/' + maxlength);

		var cols = pane.find(':input[name="cols"]').val();
		var rows = pane.find(':input[name="rows"]').val();
		if (cols || rows)
			options.push(cols + 'x' + rows);

		var idvalue = pane.find(':input[name="id"]').val();
		if (idvalue)
			options.push('id:' + idvalue);

		var classvalue = pane.find(':input[name="class"]').val();
		if (classvalue)
			$.each(classvalue.split(' '), function(i, n) { options.push('class:' + n) });

		if (pane.find(':input[name="akismet-author-name"]').is(':checked'))
			options.push('akismet:author');

		if (pane.find(':input[name="akismet-author-url"]').is(':checked'))
			options.push('akismet:author_url');

		if (pane.find(':input[name="akismet-author-email"]').is(':checked'))
			options.push('akismet:author_email');

		options = (options.length > 0) ? ' ' + options.join(' ') : '';

		var value = pane.find(':input[name="default-value"]').val();
		if (value)
			value = ' "' + value.replace(/["]/g, '&quot;') + '"';

		var tag = name ? '[' + type + ' ' + name + options + value + ']' : '';
		pane.find(':input.tag').val(tag);
	}

	$.tgPanes = {};

})(jQuery);

jQuery.tgPanes.text = {
	title: _wpcf7L10n.textField,
	content: 'wpcf7-tg-pane-text',
	change: jQuery.tgCreateTag
};

jQuery.tgPanes.email = {
	title: _wpcf7L10n.emailField,
	content: 'wpcf7-tg-pane-email',
	change: jQuery.tgCreateTag
};

jQuery.tgPanes.textarea = {
	title: _wpcf7L10n.textArea,
	content: 'wpcf7-tg-pane-textarea',
	change: jQuery.tgCreateTag
};

jQuery('#taggenerator').tagGenerator();
