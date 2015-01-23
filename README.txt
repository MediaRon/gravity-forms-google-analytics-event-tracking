=== Gravity Forms Google Analytics Event Tracking ===
Contributors: nmarks
Tags: gravity forms, google analytics, event tracking
Requires at least: 3.5.2
Tested up to: 4.1
Stable tag: 1.6.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add Google Analytics Event Tracking to your Gravity Forms in less than 5 minutes!

== Description ==

This plugin provides an easy way to add Google Analytics event tracking to your Gravity Forms, allowing you to properly track form submissions as events/conversions within Google Analytics.

= Setup Guide =
Looking for help setting things up? [Read My Setup Guide](http://nvis.io/x8fld)

= Minimum Requirements =
- PHP 5.3+
- Gravity Forms 1.8.20+
- Google Analytics Universal Analytics (classic is not supported by Google's new fancy stuff this plugin leverages)

= Features =
- Automatically send form submission events to Google Analytics
- Add multiple event feeds with conditionals
- Custom event categories, actions, labels and even values
- Dynamic event value on payment forms (integration with the payment add-ons including Paypal Standard, PayPal Pro, Stripe, etc...)
- Awesomeness

= Configuration =
After installing, you setup your UA ID in the Event Tracking tab on Gravity Forms' settings page and then customize your event category/action/label/value on the form event tracking settings page (see screenshots for more information).

For payment based forms, you can leave the value blank to convert using the total payment amount.

= Hooks/Filters =
Check out the documentation on [github](https://github.com/nathanmarks/wordpress-gravity-forms-event-tracking)

== Installation ==

= Minimum Requirements =
- PHP 5.3+
- Gravity Forms 1.8.20+

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for gravity-forms-google-analytics-event-tracking
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `gravity-forms-event-tracking.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `gravity-forms-event-tracking.zip`
2. Extract the `gravity-forms-event-tracking` directory to your computer
3. Upload the `gravity-forms-event-tracking` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard


== Frequently Asked Questions ==

= How do I configure the event category, action and label for the form? =

Looking for help setting things up? [Read My Setup Guide](http://nvis.io/x8fld)

= Are there any filters/hooks? =

Check out the documentation on [github](https://github.com/nathanmarks/wordpress-gravity-forms-event-tracking)

== Screenshots ==

1. The Gravity Forms setting screen where you setup your UA ID.
2. The form settings feed list.
3. The feed settings page

== Changelog ==

= 1.6.0 =
* Refactored the plugin to use feeds. Now you can have multiple feeds with conditions!

= 1.5.5 =
* Hotfix for issue with paypal standard converting early

= 1.5.3 =
* Ensured page title and location are properly being sent to Google

= 1.5.2 =
* Hotfix for PHP strict standards warning

= 1.5.0 =
* Moved the form specific settings to their own tab.
* Re-structured the plugin code to fall in line with the official Gravity Forms plugins.
* Added a disable option to prevent a form from tracking any events.
* Added merge tag (choose a form field dropdown) to the settings fields for more dynamic tracking capabilities.

= 1.4.5 =
* Fixed a bug where the source/medium was not being tracked correctly for PayPal Standard IPN Notification based conversions.

= 1.4.4 =
* Added some information to the event settings section

= 1.4.3 =
* Fixed backwards-compat issue

= 1.4 =
* Added value for events
* Properly integrated with payment based forms

= 1.3 =
* Properly integrated with Gravity Forms settings API (thanks ronalfy!)
* Enabled custom category/action/label on a per form basis.
