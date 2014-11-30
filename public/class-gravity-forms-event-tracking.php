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
	 * Load the UA settings and add the tracking action if successful
	 * 
	 * @since 1.4.0
	 */
	public function init() {

		if ( ! is_admin() && $this->load_ua_settings() ) {
			$this->load_measurement_client();

			// Tracking hooks
			add_action( 'gform_after_submission', array( $this, 'track_form_after_submission' ), 10, 2 );

			// IPN hook for paypal standard!
			if ( class_exists( 'GFPayPal' ) ) {
				add_action( 'gform_paypal_post_ipn', array( $this, 'paypal_track_form_post_ipn' ), 10, 2 );
			}
		}

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
	 * @param array $entry Gravity Forms entry object
	 * @param array $form Gravity Forms form object
	 */
	public function track_form_after_submission( $entry, $form ) {

		// Temporary until Gravity fix a bug
		$entry = GFAPI::get_entry( $entry['id'] );

		// We need to check if this form is using paypal standard before we push a conversion.
		if ( class_exists( 'GFPayPal' ) ) {
			$paypal = GFPayPal::get_instance();

			// See if a PayPal standard feed exists for this form and the condition is met.
			// If it is we need to save the GA cookie to the entry instead for return from the IPN
			if ( $feed = $paypal->get_payment_feed( $entry ) && $paypal->is_feed_condition_met( $feed, $form, $entry ) ) {
				gform_update_meta( $entry['id'], 'ga_cookie', $_COOKIE['_ga'] );
				return;
			}
		}

		// Push the event to google
		$this->push_event( $entry, $form );
	}

	/**
	 * Handle the IPN response for pushing the event
	 * 
	 * @since 1.4.0
	 * @param array $post_object global post array from the IPN
	 * @param array $entry Gravity Forms entry object
	 */
	public function paypal_track_form_post_ipn( $post_object, $entry ) {
		// Check if the payment was completed before continuing
		if ( strtolower( $entry['payment_status'] ) != 'completed' ) {
			return;
		}

		// Fetch the cookie we saved previously and set it into the cookie global
		// The php analytics library looks for this
		$_COOKIE['_ga'] = gform_get_meta( $entry['ID'], 'ga_cookie' );

		$form = GFFormsModel::get_form_meta( $entry['form_id'] );

		// Push the event to google
		$this->push_event( $entry, $form );
	}

	/**
	 * Push the Google Analytics Event!
	 * 
	 * @since 1.4.0
	 * @param array $event Gravity Forms event object
	 * @param array $form Gravity Forms form object
	 */
	private function push_event( $entry, $form ) {

		// Init tracking object
		$this->tracking = new \Racecore\GATracking\GATracking( apply_filters( 'gform_ua_id', $this->ua_id, $form ), false );
		$event = new \Racecore\GATracking\Tracking\Event();
		
		// Get event defaults
		$event_category = 'Forms';
		$event_label    = sprintf( "Form: %s ID: %s", $form['title'], $form['id'] );
		$event_action   = 'Submission';

		// IF this form has payment, we should use that for the value
		// as long a custom value hasn't been set
		$event_value = $this->get_event_value( $entry, $form );
		
		// Overwrite with Gravity Form Settings if necessary
		if ( function_exists( 'rgar' ) ) {
			// Event category
			$gf_event_category = rgar( $form, 'gaEventCategory' );
			if ( !empty( $gf_event_category ) ) {
				$event_category = 	$gf_event_category;
			}
			
			// Event label
			$gf_event_label = rgar( $form, 'gaEventLabel' );
			if ( !empty( $gf_event_label ) ) {
				$event_label =  $gf_event_label;
			}
			
			// Event action
			$gf_event_action = rgar( $form, 'gaEventAction' );
			if ( !empty( $gf_event_action ) ) {
				$event_action =  $gf_event_action;
			}

			// Event value
			$gf_event_value = rgar( $form, 'gaEventValue' );
			if ( !empty( $gf_event_value ) ) {
				$event_value =  $gf_event_value;
			}
		}

		// Set our event object variables
		$event->setEventCategory( apply_filters( 'gform_event_category', $event_category, $form ) );
		$event->setEventAction( apply_filters( 'gform_event_action', $event_action, $form ) );
		$event->setEventLabel( apply_filters( 'gform_event_label', $event_label, $form ) );
		
		if ( $event_value = apply_filters( 'gform_event_value', $event_value, $form ) ) {
			$event->setEventValue( $event_value );
		}

		// Pppp Push it!
		$this->tracking->addTracking( $event );

		try {
		    $this->tracking->send();
		} catch (Exception $e) {
		    echo 'Error: ' . $e->getMessage() . '<br />' . "\r\n";
		    echo 'Type: ' . get_class($e);
		}
	}

	/**
	 * Get the event value for payment entries
	 * 
	 * @since 1.4.0
	 * @param array $event Gravity Forms event object
	 * @return string/boolean Event value or false if not a payment form
	 */
	private function get_event_value( $entry ) {
		$value = rgar( $entry, 'payment_amount' );

		if ( ! empty( $value ) && intval( $value ) ) {
			return intval( $value );
		}

		return false;
	}

}
