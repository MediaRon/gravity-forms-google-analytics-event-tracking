<?php
/**
 * @package   Gravity_Forms_Event_Tracking
 * @author    Nathan Marks <nmarks@nvisionsolutions.ca>
 * @license   GPL-2.0+
 * @link      http://www.nvisionsolutions.ca
 * @copyright 2014 Nathan Marks
 *
 * @wordpress-plugin
 * Plugin Name:       nvision Gravity Forms Event Tracking
 * Plugin URI:        http://www.nvisionsolutions.ca
 * Description:       Track me baby one more time!
 * Version:           1.0.0
 * Author:            Nathan Marks
 * Author URI:        http://www.nvisionsolutions.ca
 * Text Domain:       gf-event-tracking
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/nathanmarks/wordpress-gravity-forms-event-tracking
 * WordPress-Plugin-Boilerplate: v2.6.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-gravity-forms-event-tracking.php' );

add_action( 'plugins_loaded', array( 'Gravity_Forms_Event_Tracking', 'get_instance' ) );