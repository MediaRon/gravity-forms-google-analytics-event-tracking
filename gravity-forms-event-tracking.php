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

class GFGAET {
	
	/**
	 * Holds the class instance.
	 *
	 * @since 2.0.0
	 * @access private
	 */
	private static $instance = null;
	
	/**
	 * Retrieve a class instance.
	 *
	 * @since 2.0.0
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	} //end get_instance
	
	/**
	 * Retrieve the plugin basename.
	 *
	 * @since 2.0.0
	 *
	 * @return string plugin basename
	 */
	public static function get_plugin_basename() {
		return plugin_basename( __FILE__ );	
	}
	
	/**
	 * Class constructor.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {
		load_plugin_textdomain( 'gravity-forms-google-analytics-event-tracking', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		
		spl_autoload_register( array( $this, 'loader' ) );
		
		add_action( 'gform_loaded', array( $this, 'gforms_loaded' ) );
	}
	
	/**
	 * Check for the minimum supported PHP version.
	 *
	 * @since 2.0.0
	 *
	 * @return bool true if meets minimum version, false if not
	 */
	public static function check_php_version() {
		if( ! version_compare( '5.3', PHP_VERSION, '<=' ) ) {
			return false;
		}
		return true;
	}
	
	/**
	 * Initialize Gravity Forms related add-ons.
	 *
	 * @since 2.0.0
	 */
	public function gforms_loaded() {
		if ( ! GFGAET::check_php_version() ) return;
		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		//require_once( plugin_dir_path( __FILE__ ) . 'includes/class-gravity-forms-event-tracking-feed.php' );

		//GFAddOn::register( 'Gravity_Forms_Event_Tracking' );
		GFAddOn::register( 'GFGAET_UA' );
	}
	
	/**
	 * Autoload class files.
	 *
	 * @since 2.0.0
	 *
	 * @param string $class_name The class name
	 */
	public function loader( $class_name ) {
		if ( class_exists( $class_name, false ) || false === strpos( $class_name, 'GFGAET' ) ) {
			return;
		}
		$file = GFGAET::get_plugin_dir( "includes/{$class_name}.php" );
		if ( file_exists( $file ) ) {
			include_once( $file );
		}
	}
	
	/**
	 * Return the absolute path to an asset.
	 *
	 * @since 2.0.0
	 *
	 * @param string @path Relative path to the asset.
	 *
	 * return string Absolute path to the relative asset.
	 */
	public static function get_plugin_dir( $path = '' ) {
		$dir = rtrim( plugin_dir_path(__FILE__), '/' );
		if ( !empty( $path ) && is_string( $path) )
			$dir .= '/' . ltrim( $path, '/' );
		return $dir;		
	}
	
	/**
	 * Check the plugin to make sure it meets the minimum requirements.
	 *
	 * @since 2.0.0
	 */
	public function check_plugin() {
		if( ! GFGAET::check_php_version() ) {
			deactivate_plugins( GFGAET::get_plugin_basename() );
			exit( sprintf( esc_html__( 'Gravity Forms Event Tracking requires PHP version 5.3 and up. You are currently running PHP version %s.', 'gravity-forms-google-analytics-event-tracking' ), esc_html( PHP_VERSION ) ) );
		}
	}
}

register_activation_hook( __FILE__, array( 'GFGAET', 'check_plugin' ) );
GFGAET::get_instance();