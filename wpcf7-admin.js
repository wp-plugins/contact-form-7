jQuery(document).ready(function() {
  jQuery('#form-content-fieldset').append(tagGenerator());
});

function tagGenerator() {
  var menu = jQuery('<div class="tag-generator"></div>');
  
  var dropdown_icon = jQuery('<img src="../wp-content/plugins/contact-form-7/images/dropdown.gif" />');
  dropdown_icon.css({ 'vertical-align': 'bottom' });
  
  var selector = jQuery('<span>' + _wpcf7.l10n.generateTag + '</span>');
  selector.append(dropdown_icon);
  selector.css({
    border: '1px solid #ddd',
    padding: '2px 4px',
    background: '#fff url( ../wp-admin/images/fade-butt.png ) repeat-x 0 0'
  });
  selector.mouseover(function() {
    jQuery(this).css({ 'border-color': '#bbb' });
  });
  selector.mouseout(function() {
    jQuery(this).css({ 'border-color': '#ddd' });
  });
  selector.mousedown(function() {
    jQuery(this).css({ background: '#ddd' });
  });
  selector.mouseup(function() {
    jQuery(this).css({ background: '#fff url( ../wp-admin/images/fade-butt.png ) repeat-x 0 0' });
  });
  selector.click(function() {
    dropdown.show();
    return false;
  });
  jQuery('body').click(function() {
    dropdown.hide();
  });
  menu.append(selector);
  
  var pane = jQuery('<div class="tg-pane"></div>');
  pane.hide();
  menu.append(pane);
  
  var dropdown = jQuery('<div class="tg-dropdown"></div>');
  dropdown.hide();
  
  jQuery.each([ 'textField', 'emailField', 'textArea', 'menu', 'captcha', 'submit' ], function(i, n) {
    var submenu = jQuery('<div>' + _wpcf7.l10n[n] + '</div>');
    submenu.css({
      margin: 0,
      padding: '0 4px',
      'line-height': '200%',
      background: '#fff'
    });
    submenu.mouseover(function() {
      jQuery(this).css({ background: '#d4f2f2' });
    });
    submenu.mouseout(function() {
      jQuery(this).css({ background: '#fff' });
    });
    submenu.click(function() {
      dropdown.hide();
      pane.empty();
      tgPane(pane, n);
      pane.show();
      return false;
    });
    dropdown.append(submenu);
  });
  
  menu.append(dropdown);
  
  return menu;
}

function tgPane(pane, tagType) {
  var closeButtonDiv = jQuery('<div></div>');
  closeButtonDiv.css({ float: 'right' });
  var closeButton = jQuery('<span class="tg-closebutton">&#215;</span>');
  closeButton.click(function() {
    pane.hide().empty();
  });
  closeButtonDiv.append(closeButton);
  pane.append(closeButtonDiv);

  var paneTitle = jQuery('<div class="tg-panetitle">' + _wpcf7.l10n[tagType] + '</div>');
  pane.append(paneTitle);
  
  var tgInputs = {};
  jQuery.each([ 'tagName', 'tagId', 'tagClasses', 'tagId2', 'tagClasses2', 'defaultValue',
    'tagSize', 'tagMaxLength', 'tagCols', 'tagRows', 'label', 'fgColor', 'bgColor' ], function(i, n) {
    tgInputs[n] = jQuery('<input type="text" size="40" />');
    tgInputs[n].css({ width: '80%', 'font-size': 'smaller' });
    tgInputs[n].change(function() {
      tgCreateTag(tagType, tgInputs, n);
    });
  });
  tgInputs.tagName.css({ 'border-color': '#555' });
  jQuery.each([ 'isRequiredField', 'akismetAuthor', 'akismetAuthorEmail', 'akismetAuthorUrl',
    'imageSizeSmall', 'imageSizeMedium', 'imageSizeLarge' ], function(i, n) {
    tgInputs[n] = jQuery('<input type="checkbox" />');
    tgInputs[n].change(function() {
      tgCreateTag(tagType, tgInputs, n);
    });
  });
  jQuery.each([ 'menuChoices' ], function(i, n) {
    tgInputs[n] = jQuery('<textarea></textarea>');
    tgInputs[n].css({ width: '80%', 'font-size': 'smaller' });
    tgInputs[n].change(function() {
      tgCreateTag(tagType, tgInputs, n);
    });
  });
  jQuery.each([ 'tag1st', 'tag2nd' ], function(i, n) {
    tgInputs[n] = jQuery('<input type="text" readonly="readonly" onfocus="this.select()" />');
    tgInputs[n].css({ width: '96%', 'border-color': '#555' });
  });
  
  switch (tagType) {
    case 'textField':
    case 'emailField':
      var table = jQuery('<table></table>');
      pane.append(table);
      
      table.append(tgTr(
        jQuery('<span>&nbsp;' + _wpcf7.l10n.isRequiredField + '</span>').prepend(tgInputs.isRequiredField)
      ));
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagName + '<br /></span>').append(tgInputs.tagName)
      ));
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagSize + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagSize),
        jQuery('<span>' + _wpcf7.l10n.tagMaxLength + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagMaxLength)
      ));
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagId + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagId),
        jQuery('<span>' + _wpcf7.l10n.tagClasses + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagClasses)
      ));
      var akismetOpts = jQuery('<span>' + _wpcf7.l10n.akismet + ' (' + _wpcf7.l10n.optional + ')<br /></span>');
      if ('textField' == tagType) {
        akismetOpts.append(tgInputs.akismetAuthor).append('&nbsp;' + _wpcf7.l10n.akismetAuthor);
        akismetOpts.append('<br />');
        akismetOpts.append(tgInputs.akismetAuthorUrl).append('&nbsp;' + _wpcf7.l10n.akismetAuthorUrl);
      } else if ('emailField' == tagType) {
        akismetOpts.append(tgInputs.akismetAuthorEmail).append('&nbsp;' + _wpcf7.l10n.akismetAuthorEmail);
      }
      table.append(tgTr(
        akismetOpts,
        jQuery('<span>' + _wpcf7.l10n.defaultValue + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.defaultValue)
      ));
      table.append(jQuery('<td colspan="2"></td>').append(_wpcf7.l10n.generatedTag + '<br />').append(tgInputs.tag1st).wrap('<tr></tr>'));
      break;
    case 'textArea':
      var table = jQuery('<table></table>');
      pane.append(table);
      
      table.append(tgTr(
        jQuery('<span>&nbsp;' + _wpcf7.l10n.isRequiredField + '</span>').prepend(tgInputs.isRequiredField)
      ));
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagName + '<br /></span>').append(tgInputs.tagName)
      ));
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagCols + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagCols),
        jQuery('<span>' + _wpcf7.l10n.tagRows + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagRows)
      ));
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagId + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagId),
        jQuery('<span>' + _wpcf7.l10n.tagClasses + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagClasses)
      ));
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.defaultValue + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.defaultValue)
      ));
      table.append(jQuery('<td colspan="2"></td>').append(_wpcf7.l10n.generatedTag + '<br />').append(tgInputs.tag1st).wrap('<tr></tr>'));
      break;
    case 'menu':
      var table = jQuery('<table></table>');
      pane.append(table);
      
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagName + '<br /></span>').append(tgInputs.tagName)
      ));
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagId + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagId),
        jQuery('<span>' + _wpcf7.l10n.tagClasses + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagClasses)
      ));
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.menuChoices + '<br /></span>').append(tgInputs.menuChoices)
      ));
      table.append(jQuery('<td colspan="2"></td>').append(_wpcf7.l10n.generatedTag + '<br />').append(tgInputs.tag1st).wrap('<tr></tr>'));
      break;
    case 'captcha':
      var table = jQuery('<table></table>');
      pane.append(table);
      
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagName + '<br /></span>').append(tgInputs.tagName)
      ));
      table.append(tgTr('<em>' + _wpcf7.l10n.imageSettings + '</em>'));
      var imageSizeOpts = jQuery('<span>' + _wpcf7.l10n.imageSize + ' (' + _wpcf7.l10n.optional + ')<br /></span>');
      imageSizeOpts.append(tgInputs.imageSizeSmall).append('&nbsp;' + _wpcf7.l10n.imageSizeSmall);
      imageSizeOpts.append('<br />');
      imageSizeOpts.append(tgInputs.imageSizeMedium).append('&nbsp;' + _wpcf7.l10n.imageSizeMedium);
      imageSizeOpts.append('<br />');
      imageSizeOpts.append(tgInputs.imageSizeLarge).append('&nbsp;' + _wpcf7.l10n.imageSizeLarge);
      table.append(tgTr(
        imageSizeOpts
      ));
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.fgColor + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.fgColor),
        jQuery('<span>' + _wpcf7.l10n.bgColor + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.bgColor)
      ));
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagId + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagId),
        jQuery('<span>' + _wpcf7.l10n.tagClasses + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagClasses)
      ));
      table.append(tgTr('<em>' + _wpcf7.l10n.inputFieldSettings + '</em>'));
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagSize + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagSize),
        jQuery('<span>' + _wpcf7.l10n.tagMaxLength + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagMaxLength)
      ));
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagId + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagId2),
        jQuery('<span>' + _wpcf7.l10n.tagClasses + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagClasses2)
      ));
      table.append(jQuery('<td colspan="2"></td>').append(_wpcf7.l10n.generatedTag + '<br />').append(tgInputs.tag1st).wrap('<tr></tr>'));
      table.append(jQuery('<td colspan="2"></td>').append(_wpcf7.l10n.generatedTag + '<br />').append(tgInputs.tag2nd).wrap('<tr></tr>'));
      break;
    case 'submit':
      var table = jQuery('<table></table>');
      pane.append(table);
      
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.label + '<br /></span>').append(tgInputs.label)
      ));
      table.append(jQuery('<td colspan="2"></td>').append(_wpcf7.l10n.generatedTag + '<br />').append(tgInputs.tag1st).wrap('<tr></tr>'));
      break;
  }
  
  tgCreateTag(tagType, tgInputs);
  pane.slideDown('normal');
}

function tgTr() {
  var tr = jQuery('<tr></tr>');
  jQuery.each(arguments, function(i, n) {
    var td = jQuery('<td></td>').append(n);
    tr.append(td);
  });
  return tr;
}

function tgCreateTag(tagType, tgInputs, trigger) {
  tgInputs.tag1st.empty();
  tgInputs.tag2nd.empty();
  
  jQuery.each([ 'tagName' ], function(i, n) {
    var val = tgInputs[n].val();
    val = val.replace(/[^0-9a-zA-Z:._-]/g, '').replace(/^[^a-zA-Z]+/, '');
    tgInputs[n].val(val);
  });
  
  jQuery.each([ 'tagSize', 'tagMaxLength', 'tagCols', 'tagRows' ], function(i, n) {
    var val = tgInputs[n].val();
    val = val.replace(/[^0-9]/g, '');
    tgInputs[n].val(val);
  });
  
  jQuery.each([ 'tagId' ], function(i, n) {
    var val = tgInputs[n].val();
    val = val.replace(/[^-0-9a-zA-Z_]/g, '');
    tgInputs[n].val(val);
  });
  
  jQuery.each([ 'tagClasses' ], function(i, n) {
    var val = tgInputs[n].val();
    val = jQuery.trim(val.replace(/\s+/g, ' '));
    val = jQuery.map(val.split(' '), function(n) {
      return n.replace(/[^-0-9a-zA-Z_]/g, '');
    }).join(' ');
    tgInputs[n].val(val);
  });
  
  if ('akismetAuthor' == trigger && tgInputs.akismetAuthor.is(':checked')) {
    tgInputs.akismetAuthorUrl.removeAttr('checked');
    tgInputs.akismetAuthorEmail.removeAttr('checked');
  } else if ('akismetAuthorUrl' == trigger && tgInputs.akismetAuthorUrl.is(':checked')) {
    tgInputs.akismetAuthor.removeAttr('checked');
    tgInputs.akismetAuthorEmail.removeAttr('checked');
  } else if ('akismetAuthorEmail' == trigger && tgInputs.akismetAuthorEmail.is(':checked')) {
    tgInputs.akismetAuthor.removeAttr('checked');
    tgInputs.akismetAuthorUrl.removeAttr('checked');
  }
  
  if ('imageSizeSmall' == trigger && tgInputs.imageSizeSmall.is(':checked')) {
    tgInputs.imageSizeMedium.removeAttr('checked');
    tgInputs.imageSizeLarge.removeAttr('checked');
  } else if ('imageSizeMedium' == trigger && tgInputs.imageSizeMedium.is(':checked')) {
    tgInputs.imageSizeSmall.removeAttr('checked');
    tgInputs.imageSizeLarge.removeAttr('checked');
  } else if ('imageSizeLarge' == trigger && tgInputs.imageSizeLarge.is(':checked')) {
    tgInputs.imageSizeSmall.removeAttr('checked');
    tgInputs.imageSizeMedium.removeAttr('checked');
  }
  
  switch (tagType) {
    case 'textField':
    case 'emailField':
      var type = 'text';
      if (tgInputs.isRequiredField.is(':checked'))
        type += '*';
      var name = tgInputs.tagName.val();
      var options = [];
      if (tgInputs.tagSize.val() || tgInputs.tagMaxLength.val())
        options.push(tgInputs.tagSize.val() + '/' + tgInputs.tagMaxLength.val());
      if (tgInputs.tagId.val())
        options.push('id:' + tgInputs.tagId.val());
      if (tgInputs.tagClasses.val())
        jQuery.each(tgInputs.tagClasses.val().split(' '), function(i, n) {
          options.push('class:' + n);
        });
      if (tgInputs.akismetAuthor.is(':checked'))
        options.push('akismet:author');
      if (tgInputs.akismetAuthorUrl.is(':checked'))
        options.push('akismet:author_url');
      if (tgInputs.akismetAuthorEmail.is(':checked'))
        options.push('akismet:author_email');
      options = (options.length > 0) ? ' ' + options.join(' ') : '';
      var dv = '';
      if (tgInputs.defaultValue.val()) {
        dv = ' "' + tgInputs.defaultValue.val().replace(/["]/g, '&quot;') + '"';
      }
      var tag = name ? '[' + type + ' ' + name + options + dv +  ']' : '';
      tgInputs.tag1st.val(tag);
      break;
    case 'textArea':
      var type = 'textarea';
      if (tgInputs.isRequiredField.is(':checked'))
        type += '*';
      var name = tgInputs.tagName.val();
      var options = [];
      if (tgInputs.tagCols.val() || tgInputs.tagRows.val())
        options.push(tgInputs.tagCols.val() + 'x' + tgInputs.tagRows.val());
      if (tgInputs.tagId.val())
        options.push('id:' + tgInputs.tagId.val());
      if (tgInputs.tagClasses.val())
        jQuery.each(tgInputs.tagClasses.val().split(' '), function(i, n) {
          options.push('class:' + n);
        });
      options = (options.length > 0) ? ' ' + options.join(' ') : '';
      var dv = '';
      if (tgInputs.defaultValue.val()) {
        dv = ' "' + tgInputs.defaultValue.val().replace(/["]/g, '&quot;') + '"';
      }
      var tag = name ? '[' + type + ' ' + name + options + dv +  ']' : '';
      tgInputs.tag1st.val(tag);
      break;
    case 'menu':
      var type = 'select';
      var name = tgInputs.tagName.val();
      var options = [];
      if (tgInputs.tagId.val())
        options.push('id:' + tgInputs.tagId.val());
      if (tgInputs.tagClasses.val())
        jQuery.each(tgInputs.tagClasses.val().split(' '), function(i, n) {
          options.push('class:' + n);
        });
      options = (options.length > 0) ? ' ' + options.join(' ') : '';
      var choices = '';
      if (tgInputs.menuChoices.val())
        jQuery.each(tgInputs.menuChoices.val().split("\n"), function(i, n) {
          choices += ' "' + n.replace(/["]/g, '&quot;') + '"';
        });
      var tag = name ? '[' + type + ' ' + name + options + choices +  ']' : '';
      tgInputs.tag1st.val(tag);
      break;
    case 'captcha':
      // for captchac
      var type = 'captchac';
      var name = tgInputs.tagName.val();
      var options = [];
      if (tgInputs.imageSizeSmall.is(':checked'))
        options.push('size:s');
      if (tgInputs.imageSizeMedium.is(':checked'))
        options.push('size:m');
      if (tgInputs.imageSizeLarge.is(':checked'))
        options.push('size:l');
      options = (options.length > 0) ? ' ' + options.join(' ') : '';
      var tag = name ? '[' + type + ' ' + name + options +  ']' : '';
      tgInputs.tag1st.val(tag);
      break;
    case 'submit':
      var type = 'submit';
      var label = tgInputs.label.val();
      if (label)
        label = ' "' + label.replace(/["]/g, '&quot;') + '"';
      var tag = '[' + type + label +  ']';
      tgInputs.tag1st.val(tag);
      break;
  }
}