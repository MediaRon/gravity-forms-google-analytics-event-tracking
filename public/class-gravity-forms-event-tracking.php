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
	protected $plugin_slug = 'gravity-forms-google-analytics-event-tracking';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Constructor
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		add_action( 'init', array( $this, 'init' ) );

	}

	/**
	 * Load the UA settings and add the tracking action if successful
	 * 
	 * @since 1.4.0
	 */
	public function init() {

		if ( $this->load_ua_settings() ) {
			$this->load_measurement_client();
			add_action( 'gform_after_submission', array( $this, 'track_form_after_submission' ), 10, 2 );
		}

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
	 * Load UA Settings
	 * 
	 * @since 1.4.0
	 * @return bool Returns true if UA ID is loaded, false otherwise
	 */
	private function load_ua_settings() {
		$gravity_forms_add_on_settings = get_option( 'gravityformsaddon_gravity-forms-event-tracking_settings', array() );
		$this->ua_id = $ua_id = false;

		if ( !isset( $gravity_forms_add_on_settings[ 'gravity_forms_event_tracking_ua' ] ) ) {
			$ua_id = get_option('gravity_forms_event_tracking_ua', false ); //Backwards compat
		}
		else {
			$ua_id = $gravity_forms_add_on_settings[ 'gravity_forms_event_tracking_ua' ];
		}

		$ua_regex = "/^UA-[0-9]{5,}-[0-9]{1,}$/";

		if ( preg_match( $ua_regex, $ua_id ) ) {
			$this->ua_id = $ua_id;
			return true;
		}

		if (!$this->ua_id)
			return false;
	}

	/**
	 * Load the google measurement protocol PHP client.
	 * 
	 * @since 1.4.0
	 */
	private function load_measurement_client() {

		require_once( 'includes/ga-mp/src/Racecore/GATracking/Autoloader.php');
		Racecore\GATracking\Autoloader::register(dirname(__FILE__).'/includes/ga-mp/src/');
				
	}

	/**
	 * Handle the form after submission before sending to the event push
	 * 
	 * @since 1.4.0
	 * @param object $entry Gravity Forms entry object
	 * @param object $form Gravity Forms form object
	 */
	public function track_form_after_submission( $entry, $form ) {

		$this->push_event( $form );

	}

	/**
	 * Push the Google Analytics Event!
	 * 
	 * @since 1.4.0
	 * @param object $form Gravity Forms form object
	 */
	private function push_event( $form ) {
		
		// Init tracking object
		$this->tracking = new \Racecore\GATracking\GATracking( apply_filters( 'gform_ua_id', $this->ua_id, $form ), false );

		$event = new \Racecore\GATracking\Tracking\Event();
		
		//Get event defaults
		$event_category = 'Forms';
		$event_label    = sprintf( "Form: %s ID: %s", $form['title'], $form['id'] );
		$event_action   = 'Submission';
		
		//Overwrite with Gravity Form Settings if necessary
		if ( function_exists( 'rgar' ) ) {

			//Event category
			$gf_event_category = rgar( $form, 'gaEventCategory' );
			if ( !empty( $gf_event_category ) ) {
				$event_category = 	$gf_event_category;
			}
			
			//Event label
			$gf_event_label = rgar( $form, 'gaEventLabel' );
			if ( !empty( $gf_event_label ) ) {
				$event_label =  $gf_event_label;
			}
			
			//Event action
			$gf_event_action = rgar( $form, 'gaEventAction' );
			if ( !empty( $gf_event_action ) ) {
				$event_action =  $gf_event_action;
			}
		}
				
		$event->setEventCategory( apply_filters( 'gform_event_category', $event_category, $form ) );
		$event->setEventLabel( apply_filters( 'gform_event_label', $event_label, $form ) );
		$event->setEventAction( apply_filters( 'gform_event_action', $event_action, $form ) );

		$this->tracking->addTracking( $event );

		try {
		    $this->tracking->send();
		} catch (Exception $e) {
		    echo 'Error: ' . $e->getMessage() . '<br />' . "\r\n";
		    echo 'Type: ' . get_class($e);
		}
	}

}
