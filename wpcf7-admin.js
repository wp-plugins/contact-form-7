jQuery(document).ready(function() {
  jQuery('#form-content-fieldset').append(tagGenerator());
});

function tagGenerator() {
  var menu = jQuery('<div></div>');
  menu.css({
    position: 'relative',
    border: '1px solid #f8f8f8',
    background: '#fff',
    padding: '10px'
  });
  
  var dropdown_icon = jQuery('<img />');
  dropdown_icon.attr('src', _wpcf7.siteurl + '/wp-content/plugins/contact-form-7/images/dropdown.gif');
  dropdown_icon.css({'vertical-align': 'bottom'});
  
  var selector = jQuery('<span>' + _wpcf7.l10n.generateTag + '</span>');
  selector.append(dropdown_icon);
  selector.css({
    border: '1px solid #ddd',
    padding: '2px 4px',
    background: '#fff url( ' + _wpcf7.siteurl + '/wp-admin/images/fade-butt.png ) repeat-x 0 0'
  });
  selector.mouseover(function() {
    jQuery(this).css({'border-color': '#bbb'});
  });
  selector.mouseout(function() {
    jQuery(this).css({'border-color': '#ddd'});
  });
  selector.mousedown(function() {
    jQuery(this).css({background: '#ddd'});
  });
  selector.mouseup(function() {
    jQuery(this).css({background: '#fff url( ' + _wpcf7.siteurl + '/wp-admin/images/fade-butt.png ) repeat-x 0 0'});
  });
  selector.click(function() {
    dropdown.show();
    return false;
  });
  jQuery('body').click(function() {
    dropdown.hide();
  });
  menu.append(selector);
  
  var pane = jQuery('<div></div>');
  pane.css({
    border: '1px solid #ddd',
    background: '#fbfbfb',
    margin: '1ex 0 0',
    padding: '10px'
  });
  pane.hide();
  menu.append(pane);
  
  var dropdown = jQuery('<div></div>');
  dropdown.css({
    position: 'absolute',
    top: '32px',
    'z-index': 10,
    border: '1px solid #ddd'
  });
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
      jQuery(this).css({background: '#d4f2f2'});
    });
    submenu.mouseout(function() {
      jQuery(this).css({background: '#fff'});
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
  closeButtonDiv.css({'float': 'right'});
  var closeButton = jQuery('<span>&#215;</span>');
  closeButton.css({
    color: '#777',
    font: 'bold 16px monospace',
    padding: '1px 4px',
    cursor: 'pointer'
  });
  closeButton.click(function() {
    pane.hide().empty();
  });
  closeButtonDiv.append(closeButton);
  pane.append(closeButtonDiv);

  var paneTitle = jQuery('<div>' + _wpcf7.l10n[tagType] + '</div>');
  paneTitle.css({
    font: 'bold 132% sans-serif',
    margin: '0 0 10px',
    color: '#777'
  });
  pane.append(paneTitle);
  
  var inputs = jQuery('<div class="tg-inputs"></div>');
  pane.append(inputs);
  
  var tagField = jQuery('<div class="tg-tag"></div>');
  pane.append(tagField);
  tagField.hide();
  
  var tgInputs = {};
  tgInputs.tagType = jQuery('<input type="hidden" />');
  jQuery.each([ 'tagName', 'tagId', 'tagClasses', 'defaultValue', 'tagSize', 'tagMaxLength' ], function(i, n) {
    tgInputs[n] = jQuery('<input type="text" size="40" />').css({'font-size': 'smaller'});
    tgInputs[n].change(function() {
      tgCreateTag();
    });
  });
  jQuery.each([ 'isRequiredField', 'akismetAuthor', 'akismetAuthorEmail', 'akismetAuthorUrl' ], function(i, n) {
    tgInputs[n] = jQuery('<input type="checkbox" />');
    tgInputs[n].change(function() {
      tgCreateTag();
    });
  });
  
  switch (tagType) {
    case 'textField':
      inputs.append(jQuery('<div> ' + _wpcf7.l10n.isRequiredField + '</div>').prepend(tgInputs.isRequiredField));
      inputs.append(jQuery('<div>' + _wpcf7.l10n.tagName + '<br /></div>').append(tgInputs.tagName));
      jQuery.each([ 'tagSize', 'tagMaxLength', 'defaultValue', 'tagId', 'tagClasses' ], function(i, n) {
        inputs.append(jQuery('<div>' + _wpcf7.l10n[n] + ' (' + _wpcf7.l10n.optional + ')' + '<br /></div>').append(tgInputs[n]));
      });
      var akismetOpts = jQuery('<div>' + _wpcf7.l10n.akismet + ' (' + _wpcf7.l10n.optional + ')' + '<br /></div>');
      akismetOpts.append(tgInputs.akismetAuthor).append(' ' + _wpcf7.l10n.akismetAuthor);
      akismetOpts.append('&emsp;');
      akismetOpts.append(tgInputs.akismetAuthorUrl).append(' ' + _wpcf7.l10n.akismetAuthorUrl);
      inputs.append(akismetOpts);
      break;
    case 'emailField':
      break;
    case 'textArea':
      break;
    case 'menu':
      break;
    case 'captcha':
      break;
    case 'submit':
      break;
  }
  
  jQuery('div.tg-inputs > div').css({
    margin: '0.2em 0'
  });
  
}

function tgCreateTag() {

}