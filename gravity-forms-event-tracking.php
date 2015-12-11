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
 * Version:           1.6.5
 * Author:            Ronald Huereca
 * Author URI:        http://mediaron.com
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

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-gravity-forms-event-tracking-feed.php' );

		GFAddOn::register( 'Gravity_Forms_Event_Tracking' );

	}

}

add_action( 'gform_loaded', array( 'Gravity_Forms_Event_Tracking_Bootstrap', 'load' ), 5 );