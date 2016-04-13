<?php
/**
 * @package   Gravity_Forms_Event_Tracking
 * @author    Nathan Marks <nmarks@nvisionsolutions.ca>
 * @license   GPL-2.0+
 * @link      http://www.nvisionsolutions.ca
 * @copyright 2014-2015 Nathan Marks
 *
 * @wordpress-plugin
 * Plugin Name:       Gravity Forms Google Analytics Event Tracking
 * Plugin URI:        https://wordpress.org/plugins/gravity-forms-google-analytics-event-tracking/
 * Description:       Add Google Analytics event tracking to your Gravity Forms with ease.
 * Version:           1.7.3
 * Author:            Ronald Huereca
 * Author URI:        https://mediaron.com
 * Text Domain:       gravity-forms-google-analytics-event-tracking
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/nathanmarks/wordpress-gravity-forms-event-tracking
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Gravity_Forms_Event_Tracking_Bootstrap {

	public static function load(){
		if ( ! gravity_forms_event_tracking_has_minimum_php() ) return;
		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-gravity-forms-event-tracking-feed.php' );

		GFAddOn::register( 'Gravity_Forms_Event_Tracking' );

	}

}

add_action( 'gform_loaded', array( 'Gravity_Forms_Event_Tracking_Bootstrap', 'load' ), 5 );



//Make sure minimum version of PHP version is supported
//Returns true if minimum PHP is met, false if not
function gravity_forms_event_tracking_has_minimum_php() {
	if( ! version_compare( '5.3', PHP_VERSION, '<=' ) ) {
		return false;
	}
	return true;
}

//Spit out error if user isn't using minimum version of PHP
function gravity_forms_event_tracking_activation() {
	if( !gravity_forms_event_tracking_has_minimum_php() ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		exit( sprintf( esc_html__( 'Gravity Forms Event Tracking requires PHP version 5.3 and up. You are currently running PHP version %s.', 'gravity-forms-google-analytics-event-tracking' ), esc_html( PHP_VERSION ) ) );
	}
}
register_activation_hook( __FILE__, 'gravity_forms_event_tracking_activation' );