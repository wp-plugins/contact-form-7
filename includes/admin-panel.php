<?php if (isset($updated_message)) : ?>
<div id="message" class="updated fade"><p><strong><?php echo $updated_message; ?></strong></p></div>
<?php endif; ?>
<div class="wrap">
	<h2><?php _e('Contact Form 7', 'wpcf7'); ?></h2>
	<ul class="cfmenu">
		<?php foreach ($contact_forms as $key => $cf) : if (! $cf['initial']) : ?>
		<li class="<?php if ($key == $current) echo 'current' ?>">
			<?php if ($key == $current) : ?>
			<?php echo $cf['title']; ?>
			<?php else : ?>
			<a href="<?php echo $base_url . '&contactform=' . $key; ?>"><?php echo $cf['title']; ?></a>
			<?php endif; ?>
		</li>
		<?php endif; endforeach; ?>
		
		<li class="add-new <?php if ($initial) echo 'current' ?>">
			<?php if ($initial) : ?>
				<?php _e('Add new', 'wpcf7'); ?>
			<?php else : ?>
			<a href="<?php echo $base_url . '&contactform=new'; ?>">
				<?php _e('Add new', 'wpcf7'); ?>
			</a>
			<?php endif; ?>
		</li>
	</ul>
	<?php $cf = stripslashes_deep($contact_forms[$current]); ?>
	<div class="cfdiv">

		<form method="post" action="<?php echo $base_url . '&contactform=' . $current; ?>" id="wpcf7-admin-form-element">
			<input type="hidden" name="wpcf7-id" value="<?php echo $current; ?>" />

			<div class="fieldset">
				<label for="wpcf7-title"><?php _e('Title', 'wpcf7'); ?></label>
				<input type="text" id="wpcf7-title" name="wpcf7-title" size="40" value="<?php echo htmlspecialchars($cf['title']); ?>" onchange="update_anchor(this.value);" />
				
				<?php if (! $initial) : ?>
				<p class="important">
				<?php _e('Copy and paste this code into your post content.', 'wpcf7'); ?> &raquo;
				<input type="text" id="contact-form-anchor-text" size="50" onfocus="this.select();" readonly="readonly" />
				</p>
				<?php endif; ?>
			</div>

			<div class="fieldset"><div class="legend"><?php _e('Form content', 'wpcf7'); ?></div>
				<input type="button" value="text" class="quick-button" onclick="quick_panel('text');" />
				<input type="button" value="text*" class="quick-button" onclick="quick_panel('text*');" />
				<input type="button" value="email" class="quick-button" onclick="quick_panel('email');" />
				<input type="button" value="email*" class="quick-button" onclick="quick_panel('email*');" />
				<input type="button" value="textarea" class="quick-button" onclick="quick_panel('textarea');" />
				<input type="button" value="textarea*" class="quick-button" onclick="quick_panel('textarea*');" />
				<input type="button" value="submit" class="quick-button" onclick="quick_panel('submit');" />
				<div id="quick-panel"></div>
				<textarea id="wpcf7-form" name="wpcf7-form" cols="100" rows="16"><?php echo htmlspecialchars($cf['form']); ?></textarea>
			</div>

			<div class="fieldset"><div class="legend"><?php _e('Mail template', 'wpcf7'); ?></div>
				<label for="wpcf7-mail-subject"><?php _e('Subject field:', 'wpcf7'); ?></label><br />
				<input type="text" id="wpcf7-mail-subject" name="wpcf7-mail-subject" class="wide" size="70" value="<?php echo htmlspecialchars($cf['mail']['subject']); ?>" /><br />
				<label for="wpcf7-mail-sender"><?php _e('Sender field:', 'wpcf7'); ?></label><br />
				<input type="text" id="wpcf7-mail-sender" name="wpcf7-mail-sender" class="wide" size="70" value="<?php echo htmlspecialchars($cf['mail']['sender']); ?>" /><br />
				<label for="wpcf7-mail-body"><?php _e('Message body:', 'wpcf7'); ?></label><br />
				<textarea id="wpcf7-mail-body" name="wpcf7-mail-body" cols="100" rows="16"><?php echo htmlspecialchars($cf['mail']['body']); ?></textarea>
			</div>

			<div class="fieldset"><div class="legend"><?php _e('Options', 'wpcf7'); ?></div>
				<label for="wpcf7-options-recipient"><?php _e('Recipient address:', 'wpcf7'); ?></label><br />
				<input type="text" id="wpcf7-options-recipient" name="wpcf7-options-recipient" class="wide" size="70" value="<?php echo htmlspecialchars($cf['options']['recipient']); ?>" />
			</div>

			<p class="submit">
				<input type="submit" class="cfsave" name="wpcf7-save" value="<?php _e('Save', 'wpcf7'); ?>" />
				<?php wp_nonce_field('wpcf7-save_' . $current); ?>
			</p>

			<p class="submit" style="text-align: left;">
				<?php if (! $cf['initial']) : ?>
				<?php $delete_nonce = wp_create_nonce('wpcf7-delete_' . $current); ?>
				<input type="submit" name="wpcf7-delete" class="cfdelete" value="<?php _e('Delete this contact form', 'wpcf7'); ?>"
					<?php echo "onclick=\"if (confirm('" . js_escape(__("You are about to delete this contact form.\n  'Cancel' to stop, 'OK' to delete.", 'wpcf7')) . "')) {this.form._wpnonce.value = '$delete_nonce'; return true;} return false;\""; ?> />
				<?php endif; ?>
			</p>
		</form>

		<script type="text/javascript">
			//<![CDATA[
			
			function quick_panel(type) {
				var quick_panel = document.getElementById('quick-panel');
				if (! quick_panel) return;
				
				var valid_types = /^(text[*]?|email[*]?|textarea[*]?|submit)$/;
				if (! valid_types.test(type)) return;
				
				quick_panel.innerHTML = '<div class="close" style="float: right;"><span onclick="document.getElementById(\'quick-panel\').style.display = \'none\';">X</span></div>';
				quick_panel.innerHTML += '<div style="text-align: center; font-weight: bold; color: #555;"><code>[' + type + ']</code></div>';
				quick_panel.innerHTML += '<input type="hidden" name="qp-type" value="' + type + '" />';
				
				switch (type) {
					case 'text':
					case 'text*':
					case 'email':
					case 'email*':
						quick_panel.innerHTML += '<table><tbody>'
							+ '<tr><td>name= <input type="text" name="qp-name" id="qp-name" class="required" onchange="create_tag();" /></td></tr>'
							+ '<tr><td>size= <input type="text" name="qp-size" onchange="create_tag();" /></td>'
							+ '<td>maxlength= <input type="text" name="qp-maxlength" onchange="create_tag();" /></td></tr>'
							+ '<tr><td>id= <input type="text" name="qp-id" onchange="create_tag();" /></td>'
							+ '<td>class= <input type="text" name="qp-class" onchange="create_tag();" /></td></tr>'
							+ '<tr><td><?php _e('Label', 'wpcf7'); ?> <input type="text" name="qp-label" onchange="create_tag();" /></td>'
							+ '<td><?php _e('Default value', 'wpcf7'); ?> <input type="text" name="qp-default" onchange="create_tag();" /></td></tr>'
							+ '<tr><td><?php _e('Create &lt;label&gt; for this ?', 'wpcf7'); ?> <input type="checkbox" name="qp-label-tag" value="1" onchange="create_tag();" /></td></tr>'
							+ '</tbody></table>';
						break;
					case 'textarea':
					case 'textarea*':
						quick_panel.innerHTML += '<table><tbody>'
							+ '<tr><td>name= <input type="text" name="qp-name" id="qp-name" class="required" onchange="create_tag();" /></td></tr>'
							+ '<tr><td>cols= <input type="text" name="qp-cols" onchange="create_tag();" /></td>'
							+ '<td>rows= <input type="text" name="qp-rows" onchange="create_tag();" /></td></tr>'
							+ '<tr><td>id= <input type="text" name="qp-id" onchange="create_tag();" /></td>'
							+ '<td>class= <input type="text" name="qp-class" onchange="create_tag();" /></td></tr>'
							+ '<tr><td><?php _e('Label', 'wpcf7'); ?> <input type="text" name="qp-label" onchange="create_tag();" /></td>'
							+ '<td><?php _e('Default value', 'wpcf7'); ?> <input type="text" name="qp-default" onchange="create_tag();" /></td></tr>'
							+ '<tr><td><?php _e('Create &lt;label&gt; for this ?', 'wpcf7'); ?> <input type="checkbox" name="qp-label-tag" value="1" onchange="create_tag();" /></td></tr>'
							+ '</tbody></table>';
						break;
					case 'submit':
						quick_panel.innerHTML += '<table><tbody>'
							+ '<tr><td><?php _e('Label', 'wpcf7'); ?> <input type="text" name="qp-label" onchange="create_tag();" /></td></tr>'
							+ '</tbody></table>';
						break;
				}
				
				quick_panel.innerHTML += '<div style="margin: 10px 0 0; text-align: center;">'
					+ '<input type="text" name="qp-insert" style="width: 80%; border: none; font-family: monospace;" /> '
					+ '<input type="button" value="<?php _e('Insert', 'wpcf7'); ?>" class="qp-button" onclick="insert_tag(this.form.elements[\'qp-insert\'].value);" />'
					+ '</div>';
				quick_panel.style.display = 'block';
				create_tag(true);
				var qp_name = document.getElementById('qp-name');
				if (qp_name)
					qp_name.focus();
			}
			
			function create_tag(initial) {
				var form = document.getElementById('wpcf7-admin-form-element');
				if (! form) return;
				
				var insert = form.elements['qp-insert'];
				if (! insert) return;
				
				var type = form.elements['qp-type'];
				if (! type) return;
				
				type = type.value;
				var valid_types = /^(text[*]?|email[*]?|textarea[*]?|submit)$/;
				if (! valid_types.test(type)) return;
				
				var tag = '[' + type;
				
				if ('submit' == type) {
					var label = form.elements['qp-label'];
					if (label && '' != label.value)
						tag += ' ' + wrap_quote(label.value);
				} else {
					var name = form.elements['qp-name'];
					if (name && ! initial) {
						name.value = name.value.replace(/[^0-9a-zA-Z:._-]/g, '');
						name.value = name.value.replace(/^[^a-zA-Z]+/, '');
						if ('' == name.value)
							name.value = 'form_' + Math.floor(Math.random() * 1000);
						tag += ' ' + name.value;
					}
					
					var label = form.elements['qp-label'];
					if (label && '' != label.value)
						tag += ' ' + wrap_quote(label.value);
					else if (! initial)
						tag += ' ""';
					
					if (/^(text[*]?|email[*]?)$/.test(type)) {
					
						var size = form.elements['qp-size'];
						if (size)
							size.value = integer(size.value);
						var maxlength = form.elements['qp-maxlength'];
						if (maxlength)
							maxlength.value = integer(maxlength.value);
						if (size && '' != size.value && maxlength && '' != maxlength.value)
							tag += ' ' + size.value + '/' + maxlength.value;
						else if (size && '' != size.value)
							tag += ' ' + size.value + '/';
						else if (maxlength && '' != maxlength.value)
							tag += ' ' + '/' + maxlength.value;
					
					} else if (/^textarea[*]?$/.test(type)) {
					
						var cols = form.elements['qp-cols'];
						if (cols)
							cols.value = integer(cols.value);
						var rows = form.elements['qp-rows'];
						if (rows)
							rows.value = integer(rows.value);
						if (cols && '' != cols.value && rows && '' != rows.value)
							tag += ' ' + cols.value + 'x' + rows.value;
						else if (cols && '' != cols.value)
							tag += ' ' + cols.value + 'x';
						else if (rows && '' != rows.value)
							tag += ' ' + 'x' + rows.value;
					
					}
					
					var id = form.elements['qp-id'];
					if (id) {
						id.value = cdata(id.value);
						if ('' != id.value)
							tag += ' id:' + id.value;
					}
					
					var klass = form.elements['qp-class'];
					if (klass) {
						var klass_list = klass.value.split(' ');
						for (var i = 0; i < klass_list.length; i++) {
							var klass_value = cdata(klass_list[i]);
							if ('' != klass_value)
								tag += ' class:' + klass_value;
						}
					}
					
					var default_value = form.elements['qp-default'];
					if (default_value && '' != default_value.value)
						tag += ' ' + wrap_quote(default_value.value);
				}
				
				tag += ']';
				
				var label_tag = form.elements['qp-label-tag'];
				if (label_tag && label_tag.checked) {
					var required = (-1 != type.indexOf('*')) ? ' (*)' : '';
					var label_text = (label && '' != label.value) ? (label.value + required) : '';
					
					if (id && '' != id.value)
						tag = '<label for="' + id.value + '">' + label_text + '</label> ' + tag;
					else
						tag = '<label>' + label_text + ' ' + tag + ' </label>';
				}
				
				insert.value = tag;
			}
			
			function integer(str) {
				return str.replace(/[^0-9]/g, '');
			}
			
			function cdata(str) {
				return str.replace(/[^-0-9a-zA-Z_]/g, '');
			}
			
			function wrap_quote(str) {
				if (-1 == str.indexOf('"'))
					return '"' + str + '"';
				else if (-1 == str.indexOf("'"))
					return "'" + str + "'";
				else
					return '"' + str.replace('"', '') + '"';
			}
			
			function insert_tag(tag) {
				var f = document.getElementById('wpcf7-form');
				if (! f) return;
				
				if (document.selection) {
					f.focus();
					var sel = document.selection.createRange();
					sel.text = tag;
					f.focus();
				} else if (f.selectionStart || f.selectionStart == '0') {
					var startPos = f.selectionStart;
					var endPos = f.selectionEnd;
					var cursorPos = endPos;
					var scrollTop = f.scrollTop;
					f.value = f.value.substring(0, startPos) + tag + f.value.substring(endPos, f.value.length);
					cursorPos = startPos + tag.length;
					f.focus();
					f.selectionStart = cursorPos;
					f.selectionEnd = cursorPos;
					f.scrollTop = scrollTop;
				} else {
					f.value += tag;
				}
			}
			
			function update_anchor(title) {
				if (title == null) title = document.getElementById('wpcf7-title').value;
				var anchor = document.getElementById('contact-form-anchor-text');
				if (anchor) {
					title = title.replace(/-+/g, '-');
					title = title.replace(/["'\[\]<>]/g, '');
					anchor.value = '[contact-form <?php echo $current; ?> "' + title + '"]';
				}
			}

			update_anchor();
			//]]>
		</script>

	</div>
</div>