<?php
/**
 * Gravity forms event tracking
 *
 * @package   Gravity_Forms_Event_Tracking
 * @author    Nathan Marks <nmarks@nvisionsolutions.ca>
 * @license   GPL-2.0+
 * @link      http://www.nvisionsolutions.ca
 * @copyright 2014 Nathan Marks
 */


GFForms::include_addon_framework();

class Gravity_Forms_Event_Tracking extends GFAddOn {
	protected $_version = "1.5.1";
    protected $_min_gravityforms_version = "1.8.20";

    /**
     * The actual slug of this plugin is too long for GF settings to save properly.
     * With the appended/prepended string it attempts to insert an option over 
     * 64 chars in length.
     * 
     * @TODO Resolve this in 2.0 somehow...
     */
    protected $_slug = "gravity-forms-event-tracking";
    protected $_text_domain = "gravity-forms-google-analytics-event-tracking";
    protected $_path = "gravity-forms-google-analytics-event-tracking/gravity-forms-event-tracking.php";
    protected $_full_path = __FILE__;
    protected $_url = "https://wordpress.org/plugins/gravity-forms-google-analytics-event-tracking";
    protected $_title = "Gravity Forms Google Analytics Event Tracking";
    protected $_short_title = "Event Tracking";

    public $ua_id = false;

    private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new Gravity_Forms_Event_Tracking();
		}

		return self::$_instance;
	}


    /**
     * Overriding init function to change the load_plugin_textdomain call.
     * See comment above for explanation.
     */
	public function init() {

		load_plugin_textdomain( $this->_text_domain, false, $this->_text_domain . '/languages' );

		add_filter( 'gform_logging_supported', array( $this, 'set_logging_supported' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ) );

		if ( RG_CURRENT_PAGE == 'admin-ajax.php' ) {

			//If gravity forms is supported, initialize AJAX
			if ( $this->is_gravityforms_supported() ) {
				$this->init_ajax();
			}
		} else if ( is_admin() ) {

			$this->init_admin();

		} else {

			if ( $this->is_gravityforms_supported() ) {
				$this->init_frontend();
			}
			
		}

	}

	/**
	 * Public facing init
	 * 
	 * @since 1.5.0
	 */
	public function init_frontend() {

		$this->setup();

		add_filter( 'gform_preview_styles', array( $this, 'enqueue_preview_styles' ), 10, 2 );
		add_filter( 'gform_print_styles', array( $this, 'enqueue_print_styles' ), 10, 2 );
		add_action( 'gform_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 2 );

		if ( $this->load_ua_settings() ) {
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

		$ua_id = $gravity_forms_add_on_settings[ 'gravity_forms_event_tracking_ua' ];

		$ua_regex = "/^UA-[0-9]{5,}-[0-9]{1,}$/";

		if ( preg_match( $ua_regex, $ua_id ) ) {
			$this->ua_id = $ua_id;
			return true;
		}

		if ( ! $this->ua_id )
			return false;
	}

	/**
	 * Load the google measurement protocol PHP client.
	 * 
	 * @since 1.4.0
	 */
	private function load_measurement_client() {
		require_once( 'vendor/ga-mp/src/Racecore/GATracking/Autoloader.php');
		Racecore\GATracking\Autoloader::register( dirname(__FILE__) . '/vendor/ga-mp/src/' );
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
		if ( strtolower( $entry['payment_status'] ) != 'paid' ) {
			return;
		}

		// Fetch the cookie we saved previously and set it into the cookie global
		// The php analytics library looks for this
		$_COOKIE['_ga'] = gform_get_meta( $entry['id'], 'ga_cookie' );

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
		if ( isset( $form['gravity-forms-event-tracking'] ) && $is_disabled = rgar( $form['gravity-forms-event-tracking'], 'gaEventTrackingDisabled' ) ) {
			return false;
		}

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
		if ( function_exists( 'rgar' ) && isset( $form['gravity-forms-event-tracking'] ) ) {
			// Event category
			$gf_event_category = rgar( $form['gravity-forms-event-tracking'], 'gaEventCategory' );
			if ( !empty( $gf_event_category ) ) {
				$event_category = GFCommon::replace_variables( $gf_event_category, $form, $entry, false, false, true, 'text' );
			}
			
			// Event label
			$gf_event_label = rgar( $form['gravity-forms-event-tracking'], 'gaEventLabel' );
			if ( !empty( $gf_event_label ) ) {
				$event_label =  GFCommon::replace_variables( $gf_event_label, $form, $entry, false, false, true, 'text' );
			}
			
			// Event action
			$gf_event_action = rgar( $form['gravity-forms-event-tracking'], 'gaEventAction' );
			if ( !empty( $gf_event_action ) ) {
				$event_action =  GFCommon::replace_variables( $gf_event_action, $form, $entry, false, false, true, 'text' );
			}

			// Event value
			$gf_event_value = rgar( $form['gravity-forms-event-tracking'], 'gaEventValue' );
			if ( !empty( $gf_event_value ) ) {
				$event_value =  GFCommon::replace_variables( $gf_event_value, $form, $entry, false, false, true, 'text' );
			}
		}

		// Set our event object variables
		$event->setEventCategory( apply_filters( 'gform_event_category', $event_category, $form ) );
		$event->setEventAction( apply_filters( 'gform_event_action', $event_action, $form ) );
		$event->setEventLabel( apply_filters( 'gform_event_label', $event_label, $form ) );
		
		
		if ( $event_value = apply_filters( 'gform_event_value', $event_value, $form ) ) {
			// Event value must be a valid float!
			$event_value = (float) $event_value;
			$event->setEventValue( $event_value );
		}

		// Pppp Push it!
		$this->tracking->addTracking( $event );

		try {
		    $this->tracking->send();
		} catch (Exception $e) {
		    error_log( $e->getMessage() . ' in ' . get_class( $e ) );
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

    /**
     * Plugin settings fields
     * 
     * @return array Array of plugin settings
     */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'       => __( 'Google Analytics', $this->_text_domain ),
				'description' => __( 'Enter your UA code (UA-XXXX-Y) here.', $this->_text_domain ),
				'fields'      => array(
					array(
						'name'              => 'gravity_forms_event_tracking_ua',
						'label'             => __( 'UA Tracking ID', $this->_text_domain ),
						'type'              => 'text',
						'class'             => 'medium',
						'tooltip' 			=> 'UA-XXXX-Y',
						'feedback_callback' => array( $this, 'ua_validation' )
					),
				)
			),
		);
	}

	/**
	 * Form settings page title
	 * 
	 * @since 1.5.0
	 * @return string Form Settings Title
	 */
	public function form_settings_page_title() {
		return __( 'Event Tracking', $this->_text_domain );
	}

	/**
	 * Form settings icon
	 * 
	 * @since 1.5.0
	 * @return string HTML Markup for icon
	 */
	public function form_settings_icon() {
		return '<i class="fa fa-crosshairs"></i>';
	}

	/**
     * Form settings fields
     * 
     * @since 1.5.0
     * @return array Array of form settings
     */
	public function form_settings_fields() {
		return array(
            array(
                "title"  => __( 'Event Tracking Settings', $this->_text_domain ),
                "fields" => array(
                	array(
                        "label"   => "",
                        "type"    => "instruction_field",
                        "name"    => "instructions"
                    ),
                    array(
                        "label"   => __( 'Event Category', $this->_text_domain ),
                        "type"    => "text",
                        "name"    => "gaEventCategory",
                        "class"   => "medium merge-tag-support mt-position-right",
                        "tooltip" => sprintf( '<h6>%s</h6>%s', __( 'Event Category', $this->_text_domain ), __( 'Enter your Google Analytics event category', $this->_text_domain ) ),
                    ),
                    array(
                        "label"   => __( 'Event Action', $this->_text_domain ),
                        "type"    => "text",
                        "name"    => "gaEventAction",
                        "class"   => "medium merge-tag-support mt-position-right",
                        "tooltip" => sprintf( '<h6>%s</h6>%s', __( 'Event Action', $this->_text_domain ), __( 'Enter your Google Analytics event action', $this->_text_domain ) ),
                    ),
                    array(
                        "label"   => __( 'Event Label', $this->_text_domain ),
                        "type"    => "text",
                        "name"    => "gaEventLabel",
                        "class"   => "medium merge-tag-support mt-position-right",
                        "tooltip" => sprintf( '<h6>%s</h6>%s', __( 'Event Label', $this->_text_domain ), __( 'Enter your Google Analytics event label', $this->_text_domain ) ),
                    ),
                    array(
                        "label"   => __( 'Event Value', $this->_text_domain ),
                        "type"    => "text",
                        "name"    => "gaEventValue",
                        "class"   => "medium merge-tag-support mt-position-right",
                        "tooltip" => sprintf( '<h6>%s</h6>%s', __( 'Event Value', $this->_text_domain ), __( 'Enter your Google Analytics event value. Leave blank to omit pushing a value to Google Analytics. Or to use the purchase value of a payment based form. <strong>Note:</strong> This must be a number (int/float).', $this->_text_domain ) ),
                    ),
                )
            ),
            array(
                "title"  => __( 'Other Settings', $this->_text_domain ),
                "fields" => array(
                    array(
                        "label"   => __( 'Disable Event Tracking', $this->_text_domain ),
                        "type"    => "checkbox",
                        "name"    => "gaDisableEventTracking",
                        "tooltip" => sprintf( '<h6>%s</h6>%s', __( 'Disable Event Tracking', $this->_text_domain ), __( 'Check this if you don\'t want this form to send any events to Google Analytics.', $this->_text_domain ) ),
                        "choices" => array(
                            array(
                                "label" => "Disabled",
                                "name"  => "gaEventTrackingDisabled"
                            )
                        )
                    )
                )
            ),
        );
	}

	/**
	 * Instruction field
	 * 
	 * @since 1.5.0
	 */
	public function single_setting_row_instruction_field(){
		echo '
			<tr>
				<th colspan="2">
					<p>' . __( "If you leave these blank, the following defaults will be used when the event is tracked", $this->_text_domain ) . ':</p>
					<p>
						<strong>' . __( "Event Category", $this->_text_domain ) . ':</strong> Forms<br>
						<strong>' . __( "Event Action", $this->_text_domain ) . ':</strong> Submission<br>
						<strong>' . __( "Event Label", $this->_text_domain ) . ':</strong> Form: {form_title} ID: {form_id}<br>
						<strong>' . __( "Event Value", $this->_text_domain ) . ':</strong> Payment Amount (on payment forms only, otherwise nothing is sent by default)
					</p>
				</td>
			</tr>';
	}

	/**
	 * Basic Validation
	 * 
	 * @since 1.3.0
	 */
	public function ua_validation($input ) {
		$input = strip_tags( stripslashes( $input ) );
		$ua_regex = "/^UA-[0-9]{5,}-[0-9]{1,}$/";
		if ( preg_match( $ua_regex, $input ) ) {
			return true;
		} else {
			$this->log_error( __( 'Invalid UA Tracking ID', $this->_text_domain ) );
		    return false;
		}
	}

	/**
	 * Upgrading functions
	 * 
	 * @since 1.5.0
	 */
	public function upgrade( $previous_version ) {

		// If the version is below 1.5.0, we need to move the form specific settings
		if ( version_compare( $previous_version, "1.5.0" ) == -1 ) {
			$forms = GFAPI::get_forms( true );

			foreach ( $forms as $form ) {
				$this->upgrade_old_form_settings( $form );	
			}
		}

	}

	/**
	 * Upgrade old settings created prior to new settings tab
	 * 
	 * @since 1.5.0
	 * @param array $form GF Form Object Array
	 */
	public function upgrade_old_form_settings( $form ) {
		$settings = array( 'gaEventCategory', 'gaEventAction', 'gaEventLabel', 'gaEventValue' );

		foreach( $settings as $key => $setting ) {
			if ( isset( $form[ $setting ] ) && ( ! isset( $form[ $this->_slug ] ) || ! isset( $form[ $this->_slug ][ $setting ] ) || empty( $form[ $this->_slug ][ $setting ] ) ) ) {
				$form[ $this->_slug ][ $setting ] = $form[ $setting ];
			}
			unset( $form[ $setting ] );
		}

		GFFormsModel::update_form_meta( $form['id'], $form );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=gf_settings&subview=gravity-forms-event-tracking' ) ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);
	}

}