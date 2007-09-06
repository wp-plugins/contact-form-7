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
				<input type="text" id="wpcf7-title" name="wpcf7-title" size="40" value="<?php echo htmlspecialchars($cf['title']); ?>" onchange="wpcf7UpdateAnchor(this.value);" />
				
				<?php if (! $initial) : ?>
				<p class="important">
				<?php _e('Copy and paste this code into your post content.', 'wpcf7'); ?> &raquo;
				<input type="text" id="contact-form-anchor-text" size="50" onfocus="this.select();" readonly="readonly" />
				</p>
				<?php endif; ?>
			</div>

			<div class="fieldset"><div class="legend"><?php _e('Form content', 'wpcf7'); ?></div>
				<textarea id="wpcf7-form" name="wpcf7-form" cols="100" rows="16"><?php echo htmlspecialchars($cf['form']); ?></textarea>
				<input type="button" value="text" class="quick-button" onclick="wpcf7QuickPanel('text');" style="background-color: #cee4d4;" />
				<input type="button" value="text*" class="quick-button" onclick="wpcf7QuickPanel('text*');" style="background-color: #cee4d4;" />
				<input type="button" value="email" class="quick-button" onclick="wpcf7QuickPanel('email');" style="background-color: #e6edb4;" />
				<input type="button" value="email*" class="quick-button" onclick="wpcf7QuickPanel('email*');" style="background-color: #e6edb4;" />
				<input type="button" value="textarea" class="quick-button" onclick="wpcf7QuickPanel('textarea');" style="background-color: #bfe7e5;" />
				<input type="button" value="textarea*" class="quick-button" onclick="wpcf7QuickPanel('textarea*');" style="background-color: #bfe7e5;" />
				<input type="button" value="select" class="quick-button" onclick="wpcf7QuickPanel('select');" style="background-color: #cbd5e8;" />
				<input type="button" value="submit" class="quick-button" onclick="wpcf7QuickPanel('submit');" style="background-color: #e5d6c2;" />
				<div id="quick-panel"></div>
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
			<?php if (function_exists('akismet_http_post') && (get_option('wordpress_api_key') || $wpcom_api_key)) : ?>
				<br /><br /><?php $checked = ($cf['options']['akismet']) ? ' checked="checked"' : ''; ?>
				<input type="checkbox" id="wpcf7-options-akismet" name="wpcf7-options-akismet" value="1"<?php echo $checked; ?> />
				<label for="wpcf7-options-akismet"><?php _e('Apply Akismet spam filter', 'wpcf7'); ?></label>
			<?php else : ?>
				<br /><br />
				<input type="checkbox" id="wpcf7-options-akismet" name="wpcf7-options-akismet" value="1" disabled="disabled" />
				<label for="wpcf7-options-akismet" class="disabled"><?php _e('Apply Akismet spam filter', 'wpcf7'); ?></label>
				<br /><span class="notice"><?php _e('Akismet plugin is not active. You need Akismet to use this feature.', 'wpcf7'); ?></span>
			<?php endif; ?>
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
			
			function wpcf7ValidateType(type) {
				var valid_types = /^(text[*]?|email[*]?|textarea[*]?|select|submit)$/;
				return valid_types.test(type);
			}
			
			function wpcf7QuickPanel(type) {
				var quick_panel = document.getElementById('quick-panel');
				if (! quick_panel) return;
				
				if (! wpcf7ValidateType(type)) return;
				
				// Style
				switch (type) {
					case 'text':
					case 'text*':
						quick_panel.style.backgroundColor = '#cee4d4';
						break;
					case 'email':
					case 'email*':
						quick_panel.style.backgroundColor = '#e6edb4';
						break;
					case 'textarea':
					case 'textarea*':
						quick_panel.style.backgroundColor = '#bfe7e5';
						break;
					case 'select':
						quick_panel.style.backgroundColor = '#cbd5e8';
						break;
					case 'submit':
						quick_panel.style.backgroundColor = '#e5d6c2';
						break;
				}
				
				quick_panel.innerHTML = '<div class="close" style="float: right;"><span onclick="document.getElementById(\'quick-panel\').style.display = \'none\';">&#215;</span></div>';
				quick_panel.innerHTML += '<div style="text-align: center; font-weight: bold; color: #555;"><code>[' + type + ']</code></div>';
				quick_panel.innerHTML += '<input type="hidden" name="qp-type" value="' + type + '" />';
				
				switch (type) {
					case 'text':
					case 'text*':
					case 'email':
					case 'email*':
						quick_panel.innerHTML += '<table><tbody>'
							+ '<tr><th>name=</th><td><input type="text" name="qp-name" id="qp-name" class="required" value="edit-me" onchange="wpcf7CreateTag();" /></td></tr>'
							+ '<tr><th>size=</th><td><input type="text" name="qp-size" onchange="wpcf7CreateTag();" /></td>'
							+ '<th>maxlength=</th><td><input type="text" name="qp-maxlength" onchange="wpcf7CreateTag();" /></td></tr>'
							+ '<tr><th>id=</th><td><input type="text" name="qp-id" onchange="wpcf7CreateTag();" /></td>'
							+ '<th>class=</th><td><input type="text" name="qp-class" onchange="wpcf7CreateTag();" /></td></tr>'
							+ '<tr><th><?php _e('Default value', 'wpcf7'); ?></th><td><input type="text" name="qp-default" onchange="wpcf7CreateTag();" /></td></tr>'
							+ '</tbody></table>';
						break;
					case 'textarea':
					case 'textarea*':
						quick_panel.innerHTML += '<table><tbody>'
							+ '<tr><th>name=</th><td><input type="text" name="qp-name" id="qp-name" class="required" value="edit-me" onchange="wpcf7CreateTag();" /></td></tr>'
							+ '<tr><th>cols=</th><td><input type="text" name="qp-cols" onchange="wpcf7CreateTag();" /></td>'
							+ '<th>rows=</th><td><input type="text" name="qp-rows" onchange="wpcf7CreateTag();" /></td></tr>'
							+ '<tr><th>id=</th><td><input type="text" name="qp-id" onchange="wpcf7CreateTag();" /></td>'
							+ '<th>class=</th><td><input type="text" name="qp-class" onchange="wpcf7CreateTag();" /></td></tr>'
							+ '<tr><th><?php _e('Default value', 'wpcf7'); ?></th><td><input type="text" name="qp-default" onchange="wpcf7CreateTag();" /></td></tr>'
							+ '</tbody></table>';
						break;
					case 'select':
						quick_panel.innerHTML += '<table><tbody>'
							+ '<tr><th>name=</th><td><input type="text" name="qp-name" id="qp-name" class="required" value="edit-me" onchange="wpcf7CreateTag();" /></td></tr>'
							+ '<tr><th>id=</th><td><input type="text" name="qp-id" onchange="wpcf7CreateTag();" /></td>'
							+ '<th>class=</th><td><input type="text" name="qp-class" onchange="wpcf7CreateTag();" /></td></tr>'
							+ '<tr><th><?php _e('Choices', 'wpcf7'); ?></th><td><textarea name="qp-default" onchange="wpcf7CreateTag();" rows="6"></textarea><br />'
							+ '<span style="font-size: smaller;"><?php _e('* One choice per line.', 'wpcf7'); ?></span></td></tr>'
							+ '</tbody></table>';
						break;
					case 'submit':
						quick_panel.innerHTML += '<table><tbody>'
							+ '<tr><th><?php _e('Label', 'wpcf7'); ?></th><td><input type="text" name="qp-label" onchange="wpcf7CreateTag();" /></td></tr>'
							+ '</tbody></table>';
						break;
				}
				
				quick_panel.innerHTML += '<div style="margin: 10px 0 0; text-align: center;">'
					+ '<input type="text" name="qp-insert" style="width: 80%; border: none; font-family: monospace;" tabindex="32767" /> '
					+ '<input type="button" value="<?php _e('Insert', 'wpcf7'); ?>" class="qp-button" onclick="wpcf7InsertTag(this.form.elements[\'qp-insert\'].value);" style="width: auto;" />'
					+ '</div>';
				quick_panel.style.display = 'block';
				wpcf7CreateTag();
				var qp_name = document.getElementById('qp-name');
				if (qp_name)
					qp_name.focus();
			}
			
			function wpcf7CreateTag() {
				var form = document.getElementById('wpcf7-admin-form-element');
				if (! form) return;
				
				var insert = form.elements['qp-insert'];
				if (! insert) return;
				
				var type = form.elements['qp-type'];
				if (! type) return;
				
				type = type.value;
				if (! wpcf7ValidateType(type)) return;
				
				var tag = '[' + type;
				
				if ('submit' == type) {
					var label = form.elements['qp-label'];
					if (label && '' != label.value)
						tag += ' ' + wpcf7WrapQuote(label.value);
				} else {
					var name = form.elements['qp-name'];
					if (name) {
						name.value = name.value.replace(/[^0-9a-zA-Z:._-]/g, '');
						name.value = name.value.replace(/^[^a-zA-Z]+/, '');
						if ('' == name.value)
							name.value = type.replace(/[*]$/, '') + '_' + Math.floor(Math.random() * 1000);
						tag += ' ' + name.value;
					}
					
					switch (type) {
						case 'text':
						case 'text*':
						case 'email':
						case 'email*':
							var size = form.elements['qp-size'];
							if (size)
								size.value = wpcf7Integer(size.value);
							var maxlength = form.elements['qp-maxlength'];
							if (maxlength)
								maxlength.value = wpcf7Integer(maxlength.value);
							if (size && '' != size.value && maxlength && '' != maxlength.value) {
								tag += ' ' + size.value + '/' + maxlength.value;
							} else if (size && '' != size.value) {
								tag += ' ' + size.value + '/';
							} else if (maxlength && '' != maxlength.value) {
								tag += ' ' + '/' + maxlength.value;
							}
							break;
						case 'textarea':
						case 'textarea*':
							var cols = form.elements['qp-cols'];
							if (cols)
								cols.value = wpcf7Integer(cols.value);
							var rows = form.elements['qp-rows'];
							if (rows)
								rows.value = wpcf7Integer(rows.value);
							if (cols && '' != cols.value && rows && '' != rows.value) {
								tag += ' ' + cols.value + 'x' + rows.value;
							} else if (cols && '' != cols.value) {
								tag += ' ' + cols.value + 'x';
							} else if (rows && '' != rows.value) {
								tag += ' ' + 'x' + rows.value;
							}
							break;
					}
					
					var id = form.elements['qp-id'];
					if (id) {
						id.value = wpcf7Cdata(id.value);
						if ('' != id.value) {
							tag += ' id:' + id.value;
						}
					}
					
					var klass = form.elements['qp-class'];
					if (klass) {
						var klass_list = klass.value.split(' ');
						for (var i = 0; i < klass_list.length; i++) {
							var klass_value = wpcf7Cdata(klass_list[i]);
							if ('' != klass_value) {
								tag += ' class:' + klass_value;
							}
						}
					}
					
					var default_value = form.elements['qp-default'];
					if (default_value && '' != default_value.value) {
						var default_values = default_value.value.split("\n");
						for (var i = 0; i < default_values.length; i++) {
							default_values[i] = default_values[i].replace(/^\s+/, '').replace(/\s+$/, '');
							if ('' != default_values[i])
								tag += ' ' + wpcf7WrapQuote(default_values[i]);
						}
					}
				}
				
				tag += ']';
				insert.value = tag;
			}
			
			function wpcf7Integer(str) {
				return str.replace(/[^0-9]/g, '');
			}
			
			function wpcf7Cdata(str) {
				return str.replace(/[^-0-9a-zA-Z_]/g, '');
			}
			
			function wpcf7WrapQuote(str) {
				if (-1 == str.indexOf('"'))
					return '"' + str + '"';
				else if (-1 == str.indexOf("'"))
					return "'" + str + "'";
				else
					return '"' + str.replace('"', '') + '"';
			}
			
			function wpcf7InsertTag(tag) {
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
			
			function wpcf7UpdateAnchor(title) {
				if (title == null) title = document.getElementById('wpcf7-title').value;
				var anchor = document.getElementById('contact-form-anchor-text');
				if (anchor) {
					title = title.replace(/-+/g, '-');
					title = title.replace(/["'\[\]<>]/g, '');
					anchor.value = '[contact-form <?php echo $current; ?> "' + title + '"]';
				}
			}

			wpcf7UpdateAnchor();
			//]]>
		</script>

	</div>
</div>