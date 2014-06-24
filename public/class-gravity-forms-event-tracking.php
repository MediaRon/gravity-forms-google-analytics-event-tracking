<?php
/**
 * @package   Gravity_Forms_Event_Tracking
 * @author    Nathan Marks <nmarks@nvisionsolutions.ca>
 * @license   GPL-2.0+
 * @link      http://www.nvisionsolutions.ca
 * @copyright 2014 Nathan Marks
 */

/**
 *
 * @package   Gravity_Forms_Event_Tracking
 * @author    Nathan Marks <nmarks@nvisionsolutions.ca>
 */
class Gravity_Forms_Event_Tracking {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 * @TODO - Rename "gf-event-tracking" to the name of your plugin
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'gravity-forms-event-tracking';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter('gform_get_form_filter',array($this,'gform_js_event_tracking'),10,2);
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-script', plugins_url( 'assets/js/gf-event-tracking.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

	/**
	 * Modify the Gravity Forms JS output
	 * 
	 * @since 1.0.0
	 */
	public function gform_js_event_tracking($form_string,$form) {

		$tracking_injection = "if(window['gformRedirect']) { gf_event_track(".$form['id'].",gformRedirect()); }";

		$form_string = str_replace("if(window['gformRedirect']) {gformRedirect();}", $tracking_injection, $form_string);

		$script = '<script>';
	    $script .= 'if (window.gf_event_form_labels === undefined){ window.gf_event_form_labels = new Object(); }';
	    $script .= 'window.gf_event_form_labels['.$form['id'].'] = "Form: '.strip_tags($form['title']).' ID: '.$form['id'].'";';
	    $script .= '</script>';
	    return $form_string.$script;

		return $form_string;
	}

	

}
