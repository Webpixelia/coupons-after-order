=== Coupons after order for WooCommerce ===
Contributors: marocweb
Donate link: https://buymeacoffee.com/webpixelia
Tags: ecommerce, woocommerce, coupon, woocommerce order
Stable tag: 1.3.5
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Coupons after order for WooCommerce

== Description ==
Generate coupons after order completion. The sum of the coupons will be equal to the amount of the order.

It is possible to adjust the parameters of the generated coupons as well as different contents of the email that will be sent to the person who placed the order.

Coupons after order for WooCommerce is a free WordPress plugin by <a href="https://webpiwelia.com" title="Jonathan Webpixelia">Jonathan Webpixelia</a>.

== Installation ==
= Minimum Requirements =

* WooCommerce 5.0 or later
* WordPress 5.0 or later

= Automatic installation =
Automatic installation is the easiest option as WordPress handles the file transfers itself and you don\'t even need to leave your web browser. To do an automatic install of Coupons after order for WooCommerce, log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.

In the search field type \"Coupons after order for WooCommerce\" and click Search Plugins. You can install it by simply clicking Install Now. After clicking that link you will be asked if you\'re sure you want to install the plugin. Click yes and WordPress will automatically complete the installation. After installation has finished, click the \'activate plugin\' link.

= Manual installation via the WordPress interface =
1. Download the plugin zip file to your computer
2. Go to the WordPress admin panel menu Plugins > Add New
3. Choose upload
4. Upload the plugin zip file, the plugin will now be installed
5. After installation has finished, click the \'activate plugin\' link

= Manual installation via FTP =
1. Download the plugin file to your computer and unzip it
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation\'s wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu within the WordPress admin.

== Frequently Asked Questions ==
= Is this plugin free? =
Yes, completely
= Is it possible to activate/deactivate the generation of coupons after an order? =
Yes, simply by checking or unchecking the “Enable Coupon after order” box in the plugin settings

== Screenshots ==
1. Settings access
2. Settings Tab
3. Email Tab
4. Misc Tab
5. Version Tab
6. Example of coupons generated
7. Example of email sent
8. "My coupons" on the account page

== Changelog ==
= 1.3.5 =
* Added ability to set a future publication date for generated coupons ("Scheduled" status)
* Improved translation
* Fixed bug on calculation of coupons with VAT
* Tested up with PHP 8.3
* Tested up with WooCommerce 8.4

= 1.3.4 =
* Improved PHP and JS code
* Fixed a bug where deleted coupons were not being removed from user metadata.
* Fixed minor bugs: JS warning, CSS in account page

= 1.3.3 =
* Added Manual bulk coupon generation option in Misc tab
* Fixed bug on $order->get_billing_email() in the case of a test email
* Fixed PHP Warning: strtotime(): Passing null to parameter

= 1.3.2 =
* Added ability to limit usage to billing email address
* Added metabox with qrcode in single coupon page
* Added new section "My coupons" in the WooCommerce Account page
* Added pop-up link to display customer's available coupons in cart and checkout pages
* Fixed CSS Bugs

= 1.3.1 =
* Added send mail test
* Added a link in the email coupons to redirect to the website and apply the coupon directly
* Improved email design

= 1.3.0 =
* Added administration by tabs
* Added Misc tab and Version tab
* Added choice to delete or not data
* Added shortcodes for fully customizable email
* Added CSS customizations to the CTA button
* Fixed minor bugs

= 1.2.1 =
* Fixed a bug in the calculation of $min_order that resulted in a string instead of a float value
* Adjusted $min_order calculation to exclude doubling when $min_amount is empty, ensuring a more accurate minimum order value

= 1.2.0 =
* Added the option to allow or disallow coupon stacking
* Added support for WooCommerce High-Performance Order Storage
* Added CSS for improved UI
* Added "translators:" comment to clarify placeholders meaning
* Improved JS code
* Improved control of values entered in fields
* Improved maintainability and readability code
* Improved security
* Fixed coupon rounding based on woocommerce setting
* Fixed Deprecated: Creation of dynamic property
* Fixed minor bugs: Returned date language, Minimum order amount
* Tested up with PHP 8.2
* Tested up with WooCommerce 8.3

= 1.1.5 =
* Added class constant
* Deleted old content
* Fix: Exclusion of any coupon amount from the purchase during the calculation of generated coupons
* Minor bugs

= 1.1.4 =
* Added a check that personalized content exists in the email
* Added live viewing of sent email
* Added coupon suffix customization
* Improved code for displaying text editors

= 1.1.3 =
* Added Licence in readme.txt

= 1.1.2 =
* Added full description and FAQ in readme.txt
* Addes screenshots

= 1.1.1 =
* Fixed bug "min_amount" in email
* UX Improvement

= 1.1.0 =
* Added new coupon options
* UI Improvement
* Rewritten email with new content
* Deleted bad content in email

= 1.0.0 =
* Added new PHP Functions and Classes
* Code cleanup
* Miscellaneous internal fixes and improvements
* More translation support

= 0.2.1 Beta =
* Added 2 TinyMCE Editor to customize email

= 0.2.0 Beta =
* Refactored code
* Added new options 
* Tested with WooCommerce 8.0, WordPress 6.4.

= 0.1.0 Beta =
* Initial release