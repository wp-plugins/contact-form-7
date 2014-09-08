=== Contact Form 7 ===
Contributors: takayukister
Donate link: http://contactform7.com/donate/
Tags: contact, form, contact form, feedback, email, ajax, captcha, akismet, multilingual
Requires at least: 3.8
Tested up to: 4.0
Stable tag: 3.9.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Just another contact form plugin. Simple but flexible.

== Description ==

Contact Form 7 can manage multiple contact forms, plus you can customize the form and the mail contents flexibly with simple markup. The form supports Ajax-powered submitting, CAPTCHA, Akismet spam filtering and so on.

= Docs & Support =

You can find [docs](http://contactform7.com/docs/), [FAQ](http://contactform7.com/faq/) and more detailed information about Contact Form 7 on [contactform7.com](http://contactform7.com/). If you were unable to find the answer to your question on the FAQ or in any of the documentation, you should check the [support forum](http://wordpress.org/support/plugin/contact-form-7) on WordPress.org. If you can't locate any topics that pertain to your particular issue, post a new topic for it.

= Contact Form 7 Needs Your Support =

It is hard to continue development and support for this free plugin without contributions from users like you. If you enjoy using Contact Form 7 and find it useful, please consider [__making a donation__](http://contactform7.com/donate/). Your donation will help encourage and support the plugin's continued development and better user support.

= Recommended Plugins =

The following are other recommended plugins by the author of Contact Form 7.

* [Flamingo](http://wordpress.org/extend/plugins/flamingo/) - With Flamingo, you can save submitted messages via contact forms in the database.
* [Really Simple CAPTCHA](http://wordpress.org/extend/plugins/really-simple-captcha/) - Really Simple CAPTCHA is a simple CAPTCHA module which works well with Contact Form 7.
* [Bogo](http://wordpress.org/extend/plugins/bogo/) - Bogo is a straight-forward multilingual plugin that doesn't cause headaches.

== Installation ==

1. Upload the entire `contact-form-7` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

You will find 'Contact' menu in your WordPress admin panel.

For basic usage, you can also have a look at the [plugin homepage](http://contactform7.com/).

== Frequently Asked Questions ==

Do you have questions or issues with Contact Form 7? Use these support channels appropriately.

1. [Docs](http://contactform7.com/docs/)
1. [FAQ](http://contactform7.com/faq/)
1. [Support Forum](http://wordpress.org/support/plugin/contact-form-7)

[Support](http://contactform7.com/support/)

== Screenshots ==

1. screenshot-1.png 

== Changelog ==

For more information, see [Releases](http://contactform7.com/category/releases/).

= 3.9.3 =

* Fixed: file uploading was disabled in some of server environments because of wrong use of mt_rand() function.
* Translations for Hungarian has been updated.

= 3.9.2 =

* Fixed: incorrect behavior seen in demo mode.
* Fixed: Flamingo saved submitter's contact info even when the submission was spam.
* New: introduce wpcf7_skip_mail filter.
* Enhancement: add a random-named directory to each uploaded file's temporary file path in order to make the path harder for a submitter to guess.
* Translation for Punjabi has been created.
* Translations for Turkish, Korean and Slovak have been updated.

= 3.9.1 =

* Fix: options with empty values didn't work correctly in a drop-down menu.
* Fix: broke layout of input fields after validation by an incorrect jQuery use.
* Fix: couldn't enqueue JavaScript manually with wpcf7_enqueue_scripts() when WPCF7_LOAD_JS was false.
* Fix: couldn't enqueue CSS manually with wpcf7_enqueue_styles() when WPCF7_LOAD_CSS was false.
* Translations for Greek and Hungarian have been updated.

= 3.9 =

* A major change has been made to the internal structure. For details, see [beta release announcement](http://contactform7.com/2014/07/02/contact-form-7-39-beta/).
* The exclude_blank mail option has been introduced.
* The wpcf7_load_js and wpcf7_load_css (functions and filter hooks) have been introduced.
* The jQuery Form Plugin (jquery.form.js) has been updated to 3.51.0.
* Translations for Persian and Slovak have been updated.
* WordPress 3.8 or higher is required.
