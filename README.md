Gravity Forms Event Tracker
======================
Gravity Forms Event Tracker for WordPress

## Requirements
To use this WordPress plugin, you will need:
<ul>
<li>WordPress 3.5 or greater</li>
<li>PHP 5.3 or greater</li>
<li><a href="http://www.gravityforms.com/">Gravity Forms</a> v1.8 or greater</li>
<li>A <a href="http://www.google.com/analytics/">Google Analytics</a> account</li>
</ul>

## Description

Gravity Forms Event Tracker is a free <a href="http://www.gravityforms.com/">Gravity Forms</a> add-on that allows you to track submission events with <a href="http://www.google.com/analytics/">Google Analytics</a>.

## Installation

If you meet all the requirements of this plugin, you can simply install this plugin in your WordPress plugins folder.

Looking for help setting things up? <a href="https://bigwing.com/nest/gravity-forms-event-tracking-google-analytics">Read Our Setup Guide</a>.

## Filters

### gform_ua_ids

```php
/**
* Filter: gform_ua_ids
*
* Filter all outgoing UA IDs to send events to
*
* @since 1.6.5
*
* @param  array  $google_analytics_codes UA codes
* @param  object $form Gravity Form form object
* @param  object $entry Gravity Form Entry Object
* @return array  $gacodes Array of GA codes
*/
```
Example:
```php
add_filter( 'gform_ua_ids', function( $ga_codes, $form, $entry) {
	return array( 'UA-XXXX-Y' );
}, 10, 3 );
```

### gform_event_category

```php
/**
* Filter: gform_event_category
*
* Filter the event category dynamically
*
* @since 1.6.5
*
* @param  string $category Event Category
* @param  object $form     Gravity Form form object
* @param  object $entry    Gravity Form Entry Object
* @return string $category New Category
*/
```
Example:
```php
add_filter( 'gform_event_category', function( $event_category, $form, $entry) {
	return 'new_category';
}, 10, 3 );
```

### gform_event_action

```php
/**
* Filter: gform_event_action
*
* Filter the event action dynamically
*
* @since 1.6.5
*
* @param  string $action   Event Action
* @param  object $form     Gravity Form form object
* @param  object $entry    Gravity Form Entry Object
* @return string $action   New Action
*/
```
Example:
```php
add_filter( 'gform_event_action', function( $event_action, $form, $entry) {
	return 'new_action';
}, 10, 3 );
```

### gform_event_label

```php
/**
* Filter: gform_event_label
*
* Filter the event label dynamically
*
* @since 1.6.5
*
* @param  string $label    Event label
* @param  object $form     Gravity Form form object
* @param  object $entry    Gravity Form Entry Object
* @return string $label    New label
*/
```
Example:
```php
add_filter( 'gform_event_label', function( $event_label, $form, $entry) {
	return 'new_label';
}, 10, 3 );
```

### gform_event_value

```php
/**
* Filter: gform_event_value
*
* Filter the event value dynamically
*
* @since 1.6.5
*
* @param  string $value     Event value
* @param  object $form      Gravity Form form object
* @param  object $entry     Gravity Form Entry Object
* @return float  $value     Floating point number value
*/
```
Example:
```php
add_filter( 'gform_event_value', function( $event_value, $form, $entry) {
	return 2.0;
}, 10, 3 );
```

### gform_pagination_event_action

```php
/**
 * Filter: gform_pagination_event_action
 *
 * Filter the event action dynamically
 *
 * @since 2.0.0
 *
 * @param string $action                Event Action
 * @param array  $form                  Gravity Form form array
 * @param int    $source_page_number    Source page number
 * @param int    $current_page_number   Current Page Number
 */
```
Example:
```php
add_filter( 'gform_pagination_event_action', 'gf_form_action_pagination_1', 10, 4 );

// The 1 at the end is the form ID. Optional, but keep the function name unique
// $action_name is default as: form
function gf_form_action_pagination_1( $action_name, $form, $source_page_number, $current_page_number ) {

	// Ensure the filter is only run on Form ID 1
	if ( 1 == $form[ 'id' ] ) {
		return 'pagination' . $current_page_number;
	}
	return $action_name;
}
```

### gform_pagination_event_category

```php
/**
 * Filter: gform_pagination_event_category
 *
 * Filter the event category dynamically
 *
 * @since 2.0.0
 *
 * @param string $category              Event Category
 * @param array  $form                  Gravity Form form array
 * @param int    $source_page_number    Source page number
 * @param int    $current_page_number   Current Page Number
 */
```
Example:
```php
add_filter( 'gform_pagination_event_category', 'gf_form_category_pagination_1', 10, 4 );

// The 1 at the end is the form ID. Optional, but keep the function name unique
// $category_name is default as: submission
function gf_form_category_pagination_1( $category_name, $form, $source_page_number, $current_page_number ) {

	// Ensure the filter is only run on Form ID 1
	if ( 1 == $form[ 'id' ] ) {
		return 'custom category name';
	}
	return $category_name;
}
```

### gform_pagination_event_label

```php
/**
 * Filter: gform_pagination_event_label
 *
 * Filter the event label dynamically
 *
 * @since 2.0.0
 *
 * @param string $label                 Event Label
 * @param array  $form                  Gravity Form form array
 * @param int    $source_page_number    Source page number
 * @param int    $current_page_number   Current Page Number
 */
```
Example:
```php
add_filter( 'gform_pagination_event_label', 'gf_form_label_pagination_1', 10, 4 );

// The 1 at the end is the form ID. Optional, but keep the function name unique
// $label is dynamic as: {form title}::{source page number}::{current page number}
function gf_form_label_pagination_1( $label, $form, $source_page_number, $current_page_number ) {

	// Ensure the filter is only run on Form ID 1
	if ( 1 == $form[ 'id' ] ) {

		// Change the label to have the page's URL embedded in the label
		$label = sprintf( '%s::%d::%d', esc_html( $_SERVER['REQUEST_URI'] ), absint( $source_page_number ), absint( $current_page_number ) );
	}
	return $label;
}
```

### gform_pagination_event_value

```php
/**
 * Filter: gform_pagination_event_value
 *
 * Filter the event value dynamically
 *
 * @since 2.1.3
 *
 * @param int    $event_value           Event Value
 * @param array  $form                  Gravity Form form array
 * @param int    $source_page_number    Source page number
 * @param int    $current_page_number   Current Page Number
 */
```
Example:
```php
add_filter( 'gform_pagination_event_value', 'gf_form_label_pagination_1_page_2', 10, 4 );
add_filter( 'gform_pagination_event_value', 'gf_form_label_pagination_1_page_3', 10, 4 );

// The 1 at the end is the form ID. Optional, but keep the function name unique
// $label is dynamic as: 0 - If 0, the event value will not be sent
// Assign a value for reaching page 2
function gf_form_label_pagination_1_page_2( $value, $form, $source_page_number, $current_page_number ) {

	// Ensure the filter is only run on Form ID 1
	if ( 1 == $form[ 'id' ] && 2 == $current_page_number ) {

		// Assign the value as 5 for reaching page 2 of the form
		$value = 5;
	}
	return $value;
}

// The 1 at the end is the form ID. Optional, but keep the function name unique
// $label is dynamic as: 0 - If 0, the event value will not be sent
// Assign a value for reaching page 3
function gf_form_label_pagination_1_page_3( $value, $form, $source_page_number, $current_page_number ) {

	// Ensure the filter is only run on Form ID 1
	if ( 1 == $form[ 'id' ] && 3 == $current_page_number ) {

		// Assign the value as 5 for reaching page 3 of the form
		$value = 10;
	}
	return $value;
}
```


