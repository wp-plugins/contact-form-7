jQuery(document).ready(function() {
  jQuery('#form-content-fieldset textarea:first:enabled').after(tagGenerator());
  
  jQuery('input#wpcf7-title:enabled').css({
    cursor: 'pointer'
  });
  
  jQuery('input#wpcf7-title').mouseover(function() {
    jQuery(this).not('.focus').css({
      'background-color': '#ffffdd'
    });
  });
  
  jQuery('input#wpcf7-title').mouseout(function() {
    jQuery(this).css({
      'background-color': '#fff'
    });
  });
  
  jQuery('input#wpcf7-title').focus(function() {
    jQuery(this).addClass('focus');
    jQuery(this).css({
      cursor: 'text',
      color: '#333',
      border: '1px solid #777',
      font: 'normal 13px Verdana, Arial, Helvetica, sans-serif',
      'background-color': '#fff'
    });
  });
  
  jQuery('input#wpcf7-title').blur(function() {
    jQuery(this).removeClass('focus');
    jQuery(this).css({
      cursor: 'pointer',
      color: '#555',
      border: 'none',
      font: 'bold 20px serif',
      'background-color': '#fff'
    });
  });
  
  jQuery('input#wpcf7-title').change(function() {
    updateTag();
  });
  
  updateTag();
  
  if (! jQuery('#wpcf7-mail-2-active').is(':checked'))
    jQuery('#mail-2-fields').hide();
  
  jQuery('#wpcf7-mail-2-active').click(function() {
    if (jQuery('#wpcf7-mail-2-active').is(':checked')) {
        if (jQuery('#mail-2-fields').is(':hidden'))
            jQuery('#mail-2-fields').slideDown('fast');
    } else {
        if (jQuery('#mail-2-fields').is(':visible'))
            jQuery('#mail-2-fields').hide('fast');
    }
  });
  
  jQuery('#message-fields-toggle-switch').text(_wpcf7.l10n.show);
  jQuery('#message-fields').hide();
  
  jQuery('#message-fields-toggle-switch').click(function() {
    if (jQuery('#message-fields').is(':hidden')) {
        jQuery('#message-fields').slideDown('fast');
        jQuery('#message-fields-toggle-switch').text(_wpcf7.l10n.hide);
    } else {
        jQuery('#message-fields').hide('fast');
        jQuery('#message-fields-toggle-switch').text(_wpcf7.l10n.show);
    }
  });
});

function updateTag() {
  var title = jQuery('input#wpcf7-title').val();
  if (title)
    title = title.replace(/["'\[\]]/g, '');
  jQuery('input#wpcf7-title').val(title);
  var current = jQuery('input#wpcf7-id').val();
  var tag = '[contact-form ' + current + ' "' + title + '"]';
  jQuery('input#contact-form-anchor-text').val(tag);
}

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
  
  jQuery.each([ 'textField', 'emailField', 'textArea', 'menu', 'checkboxes', 'radioButtons', 'acceptance', 'captcha', 'submit' ], function(i, n) {
    var submenu = jQuery('<div>' + _wpcf7.l10n[n] + '</div>');
    submenu.css({
      margin: 0,
      padding: '0 4px',
      'line-height': '180%',
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
      pane.hide();
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
    tgInputs[n] = jQuery('<input type="text" />');
    tgInputs[n].css({ width: '80%', 'font-size': 'smaller' });
    tgInputs[n].change(function() {
      tgCreateTag(tagType, tgInputs, n);
    });
  });
  tgInputs.tagName.css({ 'border-color': '#555' });
  jQuery.each([ 'isRequiredField', 'allowsMultipleSelections', 'insertFirstBlankOption', 'makeCheckboxesExclusive',
    'isAcceptanceDefaultOn', 'isAcceptanceInvert',
    'akismetAuthor', 'akismetAuthorEmail', 'akismetAuthorUrl',
    'imageSizeSmall', 'imageSizeMedium', 'imageSizeLarge' ], function(i, n) {
    tgInputs[n] = jQuery('<input type="checkbox" />');
    tgInputs[n].change(function() {
      tgCreateTag(tagType, tgInputs, n);
    });
  });
  jQuery.each([ 'menuChoices' ], function(i, n) {
    tgInputs[n] = jQuery('<textarea></textarea>');
    tgInputs[n].css({ width: '80%', height: '100px', 'font-size': 'smaller' });
    tgInputs[n].change(function() {
      tgCreateTag(tagType, tgInputs, n);
    });
  });
  jQuery.each([ 'tag1st', 'tag2nd' ], function(i, n) {
    tgInputs[n] = jQuery('<input type="text" class="tag" readonly="readonly" onfocus="this.select()" />');
    tgInputs[n].css({ width: '96%' });
  });
  
  switch (tagType) {
    case 'textField':
    case 'emailField':
      var table1 = jQuery('<table></table>');
      pane.append(table1);
      table1.append(tgTr(
        jQuery('<span>&nbsp;' + _wpcf7.l10n.isRequiredField + '</span>').prepend(tgInputs.isRequiredField)
      ));
      table1.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagName + '<br /></span>').append(tgInputs.tagName),
        jQuery('<span></span>')
      ));
      
      var table2 = jQuery('<table></table>');
      pane.append(table2);
      table2.append(tgTr(
        jQuery('<span><code>size</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagSize),
        jQuery('<span><code>maxlength</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagMaxLength)
      ));
      table2.append(tgTr(
        jQuery('<span><code>id</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagId),
        jQuery('<span><code>class</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagClasses)
      ));
      var akismetOpts = jQuery('<span>' + _wpcf7.l10n.akismet + ' (' + _wpcf7.l10n.optional + ')<br /></span>');
      if ('textField' == tagType) {
        akismetOpts.append(tgInputs.akismetAuthor).append('&nbsp;' + _wpcf7.l10n.akismetAuthor);
        akismetOpts.append('<br />');
        akismetOpts.append(tgInputs.akismetAuthorUrl).append('&nbsp;' + _wpcf7.l10n.akismetAuthorUrl);
      } else if ('emailField' == tagType) {
        akismetOpts.append(tgInputs.akismetAuthorEmail).append('&nbsp;' + _wpcf7.l10n.akismetAuthorEmail);
      }
      table2.append(tgTr(
        akismetOpts,
        jQuery('<span>' + _wpcf7.l10n.defaultValue + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.defaultValue)
      ));
      pane.append(jQuery('<div class="tg-tag">' + _wpcf7.l10n.generatedTag + '<br /></div>').append(tgInputs.tag1st));
      break;
    case 'textArea':
      var table1 = jQuery('<table></table>');
      pane.append(table1);
      table1.append(tgTr(
        jQuery('<span>&nbsp;' + _wpcf7.l10n.isRequiredField + '</span>').prepend(tgInputs.isRequiredField)
      ));
      table1.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagName + '<br /></span>').append(tgInputs.tagName),
        jQuery('<span></span>')
      ));
      
      var table2 = jQuery('<table></table>');
      pane.append(table2);
      table2.append(tgTr(
        jQuery('<span><code>cols</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagCols),
        jQuery('<span><code>rows</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagRows)
      ));
      table2.append(tgTr(
        jQuery('<span><code>id</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagId),
        jQuery('<span><code>class</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagClasses)
      ));
      table2.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.defaultValue + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.defaultValue)
      ));
      pane.append(jQuery('<div class="tg-tag">' + _wpcf7.l10n.generatedTag + '<br /></div>').append(tgInputs.tag1st));
      break;
    case 'menu':
    case 'checkboxes':
    case 'radioButtons':
      var table1 = jQuery('<table></table>');
      pane.append(table1);
      if ('radioButtons' != tagType)
        table1.append(tgTr(
          jQuery('<span>&nbsp;' + _wpcf7.l10n.isRequiredField + '</span>').prepend(tgInputs.isRequiredField)
        ));
      table1.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagName + '<br /></span>').append(tgInputs.tagName),
        jQuery('<span></span>')
      ));
      
      var table2 = jQuery('<table></table>');
      pane.append(table2);
      table2.append(tgTr(
        jQuery('<span><code>id</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagId),
        jQuery('<span><code>class</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagClasses)
      ));
      
      if ('menu' == tagType) {
        var menuOpt1 = jQuery('<span>&nbsp;' + _wpcf7.l10n.allowsMultipleSelections + '</span>').prepend(tgInputs.allowsMultipleSelections).prepend('<br />');
        var menuOpt2 = jQuery('<span>&nbsp;' + _wpcf7.l10n.insertFirstBlankOption + '</span>').prepend(tgInputs.insertFirstBlankOption).prepend('<br />');
        
        table2.append(tgTr(
          jQuery('<span>' + _wpcf7.l10n.menuChoices + '<br /></span>').append(tgInputs.menuChoices)
            .append('<br /><span style="font-size: smaller">' + _wpcf7.l10n.oneChoicePerLine + '</span>'),
          menuOpt1.append(menuOpt2)
        ));
      } else if ('checkboxes' == tagType) {
        table2.append(tgTr(
          jQuery('<span>' + _wpcf7.l10n.menuChoices + '<br /></span>').append(tgInputs.menuChoices)
            .append('<br /><span style="font-size: smaller">' + _wpcf7.l10n.oneChoicePerLine + '</span>'),
          jQuery('<span>&nbsp;' + _wpcf7.l10n.makeCheckboxesExclusive + '</span>').prepend(tgInputs.makeCheckboxesExclusive).prepend('<br />')
        ));
      } else {
        table2.append(tgTr(
          jQuery('<span>' + _wpcf7.l10n.menuChoices + '<br /></span>').append(tgInputs.menuChoices)
            .append('<br /><span style="font-size: smaller">' + _wpcf7.l10n.oneChoicePerLine + '</span>')
        ));
      }
      
      pane.append(jQuery('<div class="tg-tag">' + _wpcf7.l10n.generatedTag + '<br /></div>').append(tgInputs.tag1st));
      break;
    case 'acceptance':
      var table1 = jQuery('<table></table>');
      pane.append(table1);
      
      table1.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagName + '<br /></span>').append(tgInputs.tagName),
        jQuery('<span></span>')
      ));
      
      var table2 = jQuery('<table></table>');
      pane.append(table2);
      table2.append(tgTr(
        jQuery('<span><code>id</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagId),
        jQuery('<span><code>class</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagClasses)
      ));
      
      var menuOpt1 = jQuery('<span>&nbsp;' + _wpcf7.l10n.isAcceptanceDefaultOn + '</span>').prepend(tgInputs.isAcceptanceDefaultOn).prepend('<br />');
      var menuOpt2 = jQuery('<span>&nbsp;' + _wpcf7.l10n.isAcceptanceInvert + '</span>').prepend(tgInputs.isAcceptanceInvert).prepend('<br />');
      menuOpt2.append('<br /><span style="font-size: smaller;">' + _wpcf7.l10n.isAcceptanceInvertMeans + '</span>');
      
      table2.append(tgTr(menuOpt1.append(menuOpt2)));
      
      pane.append(jQuery('<div class="tg-tag">' + _wpcf7.l10n.generatedTag + '<br /></div>').append(tgInputs.tag1st));
      break;
    case 'captcha':
      var table1 = jQuery('<table></table>');
      pane.append(table1);
      table1.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.tagName + '<br /></span>').append(tgInputs.tagName),
        jQuery('<span></span>')
      ));
      
      var table2 = jQuery('<table></table>');
      pane.append(table2);
      table2.append('<caption>' + _wpcf7.l10n.imageSettings + '</caption>');
      var imageSizeOpts = jQuery('<span>' + _wpcf7.l10n.imageSize + ' (' + _wpcf7.l10n.optional + ')<br /></span>');
      imageSizeOpts.append(tgInputs.imageSizeSmall).append('&nbsp;' + _wpcf7.l10n.imageSizeSmall);
      imageSizeOpts.append('&emsp;');
      imageSizeOpts.append(tgInputs.imageSizeMedium).append('&nbsp;' + _wpcf7.l10n.imageSizeMedium);
      imageSizeOpts.append('&emsp;');
      imageSizeOpts.append(tgInputs.imageSizeLarge).append('&nbsp;' + _wpcf7.l10n.imageSizeLarge);
      table2.append(tgTr(
        imageSizeOpts
      ));
      table2.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.fgColor + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.fgColor),
        jQuery('<span>' + _wpcf7.l10n.bgColor + ' (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.bgColor)
      ));
      table2.append(tgTr(
        jQuery('<span><code>id</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagId),
        jQuery('<span><code>class</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagClasses)
      ));
      
      var table3 = jQuery('<table></table>');
      pane.append(table3);
      table3.append('<caption>' + _wpcf7.l10n.inputFieldSettings + '</caption>');
      table3.append(tgTr(
        jQuery('<span><code>size</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagSize),
        jQuery('<span><code>maxlength</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagMaxLength)
      ));
      table3.append(tgTr(
        jQuery('<span><code>id</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagId2),
        jQuery('<span><code>class</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagClasses2)
      ));
      pane.append(
        jQuery('<div class="tg-tag">' + _wpcf7.l10n.generatedTag + '</div>')
          .append('<br />').append('1) ' + _wpcf7.l10n.tagForImage)
          .append(tgInputs.tag1st)
          .append('<br />').append('2) ' + _wpcf7.l10n.tagForInputField)
          .append(tgInputs.tag2nd)
      );
      break;
    case 'submit':
      var table = jQuery('<table></table>');
      pane.append(table);
      
      table.append(tgTr(
        jQuery('<span><code>id</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagId),
        jQuery('<span><code>class</code> (' + _wpcf7.l10n.optional + ')<br /></span>').append(tgInputs.tagClasses)
      ));
      
      table.append(tgTr(
        jQuery('<span>' + _wpcf7.l10n.label + '<br /></span>').append(tgInputs.label),
        jQuery('<span></span>')
      ));
      pane.append(jQuery('<div class="tg-tag">' + _wpcf7.l10n.generatedTag + '<br /></div>').append(tgInputs.tag1st));
      break;
  }
  
  tgCreateTag(tagType, tgInputs);
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
    if ('' == val)
      val = tgDefaultName(tagType);
    tgInputs[n].val(val);
  });
  
  jQuery.each([ 'tagSize', 'tagMaxLength', 'tagCols', 'tagRows' ], function(i, n) {
    var val = tgInputs[n].val();
    val = val.replace(/[^0-9]/g, '');
    tgInputs[n].val(val);
  });
  
  jQuery.each([ 'tagId', 'tagId2' ], function(i, n) {
    var val = tgInputs[n].val();
    val = val.replace(/[^-0-9a-zA-Z_]/g, '');
    tgInputs[n].val(val);
  });
  
  jQuery.each([ 'tagClasses', 'tagClasses2' ], function(i, n) {
    var val = tgInputs[n].val();
    val = jQuery.map(val.split(' '), function(n) {
      return n.replace(/[^-0-9a-zA-Z_]/g, '');
    }).join(' ');
    val = jQuery.trim(val.replace(/\s+/g, ' '));
    tgInputs[n].val(val);
  });
  
  jQuery.each([ 'fgColor', 'bgColor' ], function(i, n) {
    var val = tgInputs[n].val();
    val = val.replace(/[^0-9a-fA-F]/g, '');
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
      var type = ('emailField' == tagType) ? 'email' : 'text';
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
    case 'checkboxes':
    case 'radioButtons':
      var type = '';
      if ('menu' == tagType)
        type = 'select';
      else if ('checkboxes' == tagType)
        type = 'checkbox';
      else if ('radioButtons' == tagType)
        type = 'radio';
      if (tgInputs.isRequiredField.is(':checked'))
        type += '*';
      
      var name = tgInputs.tagName.val();
      var options = [];
      if (tgInputs.allowsMultipleSelections.is(':checked'))
        options.push('multiple');
      if (tgInputs.insertFirstBlankOption.is(':checked'))
        options.push('include_blank');
      if (tgInputs.makeCheckboxesExclusive.is(':checked'))
        options.push('exclusive');
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
    case 'acceptance':
      var type = 'acceptance';
      var name = tgInputs.tagName.val();
      var options = [];
      if (tgInputs.isAcceptanceDefaultOn.is(':checked'))
        options.push('default:on');
      if (tgInputs.isAcceptanceInvert.is(':checked'))
        options.push('invert');
      if (tgInputs.tagId.val())
        options.push('id:' + tgInputs.tagId.val());
      if (tgInputs.tagClasses.val())
        jQuery.each(tgInputs.tagClasses.val().split(' '), function(i, n) {
          options.push('class:' + n);
        });
      options = (options.length > 0) ? ' ' + options.join(' ') : '';
      var tag = name ? '[' + type + ' ' + name + options +  ']' : '';
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
      if (tgInputs.fgColor.val())
        options.push('fg:#' + tgInputs.fgColor.val());
      if (tgInputs.bgColor.val())
        options.push('bg:#' + tgInputs.bgColor.val());
      if (tgInputs.tagId.val())
        options.push('id:' + tgInputs.tagId.val());
      if (tgInputs.tagClasses.val())
        jQuery.each(tgInputs.tagClasses.val().split(' '), function(i, n) {
          options.push('class:' + n);
        });
      options = (options.length > 0) ? ' ' + options.join(' ') : '';
      var tag = name ? '[' + type + ' ' + name + options +  ']' : '';
      tgInputs.tag1st.val(tag);
      // for captchar
      var type = 'captchar';
      var options = [];
      if (tgInputs.tagSize.val() || tgInputs.tagMaxLength.val())
        options.push(tgInputs.tagSize.val() + '/' + tgInputs.tagMaxLength.val());
      if (tgInputs.tagId2.val())
        options.push('id:' + tgInputs.tagId2.val());
      if (tgInputs.tagClasses2.val())
        jQuery.each(tgInputs.tagClasses2.val().split(' '), function(i, n) {
          options.push('class:' + n);
        });
      options = (options.length > 0) ? ' ' + options.join(' ') : '';
      var tag = name ? '[' + type + ' ' + name + options +  ']' : '';
      tgInputs.tag2nd.val(tag);
      break;
    case 'submit':
      var type = 'submit';
      
      var options = [];
      if (tgInputs.tagId.val())
        options.push('id:' + tgInputs.tagId.val());
      if (tgInputs.tagClasses.val())
        jQuery.each(tgInputs.tagClasses.val().split(' '), function(i, n) {
          options.push('class:' + n);
        });
      options = (options.length > 0) ? ' ' + options.join(' ') : '';
      
      var label = tgInputs.label.val();
      if (label)
        label = ' "' + label.replace(/["]/g, '&quot;') + '"';
      var tag = '[' + type + options + label +  ']';
      tgInputs.tag1st.val(tag);
      break;
  }
}

function tgDefaultName(tagType) {
  var rand = Math.floor(Math.random() * 1000);
  if ('textField' == tagType) {
    return 'text-' + rand;
  } else if ('emailField' == tagType) {
    return 'email-' + rand;
  } else if ('textArea' == tagType) {
    return 'textarea-' + rand;
  } else if ('menu' == tagType) {
    return 'menu-' + rand;
  } else if ('checkboxes' == tagType) {
    return 'checkbox-' + rand;
  } else if ('radioButtons' == tagType) {
    return 'radio-' + rand;
  } else if ('acceptance' == tagType) {
    return 'acceptance-' + rand;
  } else if ('captcha' == tagType) {
    return 'captcha-' + rand;
  }
}