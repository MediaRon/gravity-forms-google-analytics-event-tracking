=== Gravity Forms Google Analytics Event Tracking ===
Contributors: nmarks, ronalfy
Tags: gravity forms, google analytics, event tracking
Requires at least: 3.5.2
Tested up to: 4.1
Stable tag: 1.4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add Google Analytics event tracking to your Gravity Forms in less than 5 minutes!

== Description ==

This plugin provides an easy way to add Google Analytics event tracking to your Gravity Forms, allowing you to properly track form submissions as goals within Google Analytics.

= Features =
- Automatically send form submission events to Google Analytics
- Custom event categories, actions, labels and even values
- Dynamic event value on payment forms (integration with the payment add-ons including Paypal Standard)
- Awesomeness

= Configuration =
After installing, you setup your UA ID in the Event Tracking tab on Gravity Forms' settings page and then customize your event category/action/label/value on the form settings page (see screenshots for more information).

For payment based forms, you can leave the value blank to convert using the total payment amount.

= Hooks/Filters =
Check out the documentation on [github](https://github.com/nathanmarks/wordpress-gravity-forms-event-tracking)

== Installation ==

= Minimum Requirements =
- PHP 5.3+
- Gravity Forms 1.7+

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

An answer to that question.

= Are there any filters/hooks? =

Check out the documentation on [github](https://github.com/nathanmarks/wordpress-gravity-forms-event-tracking)

== Screenshots ==

1. The Gravity Forms setting screen where you setup your UA ID.
2. The form settings where you set your category, action and label.

== Changelog ==

= 1.4 =
* Added value for events
* Properly integrated with payment based forms

= 1.3 =
* Properly integrated with Gravity Forms settings API (thanks ronalfy!)
* Enabled custom category/action/label on a per form basis.
