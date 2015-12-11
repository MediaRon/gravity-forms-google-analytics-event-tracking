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


GFForms::include_feed_addon_framework();

class Gravity_Forms_Event_Tracking extends GFFeedAddOn {

	protected $_version = "1.6.3";
	protected $_min_gravityforms_version = "1.8.20";

	/**
	 * The actual slug of this plugin is too long for GF settings to save properly.
	 * With the appended/prepended string it attempts to insert an option over 
	 * 64 chars in length.
	 * 
	 * @TODO Resolve this in 2.0 somehow...
	 */
	protected $_slug = "gravity-forms-event-tracking";
	protected $_path = "gravity-forms-google-analytics-event-tracking/gravity-forms-event-tracking.php";
	protected $_full_path = __FILE__;
	protected $_url = "https://wordpress.org/plugins/gravity-forms-google-analytics-event-tracking";
	protected $_title = "Gravity Forms Google Analytics Event Tracking";
	protected $_short_title = "Event Tracking";

	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_event_tracking', 'gravityforms_event_tracking_uninstall' );

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_event_tracking';
	protected $_capabilities_form_settings = 'gravityforms_event_tracking';
	protected $_capabilities_uninstall = 'gravityforms_event_tracking_uninstall';

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

		load_plugin_textdomain( 'gravity-forms-google-analytics-event-tracking', false, dirname( plugin_basename( __FILE__ ) )  . '/languages' );

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

		parent::init_frontend();

		// Move this hook so everything else is all done and dusted first!
		remove_filter( 'gform_entry_post_save', array( $this, 'maybe_process_feed' ) );
		

		if ( $this->load_ua_settings() ) {
			$this->load_measurement_client();

			add_filter( 'gform_after_submission', array( $this, 'maybe_process_feed' ), 10, 2 );

			// IPN hook for paypal standard!
			if ( class_exists( 'GFPayPal' ) ) {
				add_action( 'gform_paypal_post_ipn', array( $this, 'paypal_track_form_post_ipn' ), 10, 2 );
			}
		}

	}

	/**
	 * Process the feed!
	 * @param  array $feed  feed data and settings
	 * @param  array $entry gf entry object
	 * @param  array $form  gf form data
	 */
	public function process_feed( $feed, $entry, $form ) {

		$paypal_feeds = $this->get_feeds_by_slug( 'gravityformspaypal', $form['id'] );
		$has_paypal_feed = false;

		foreach ( $paypal_feeds as $paypal_feed ){
			if ( $paypal_feed['is_active'] && $this->is_feed_condition_met( $paypal_feed, $form, $entry ) ){
				$has_paypal_feed = true;
				break;
			}
		}

		$ga_event_data = $this->get_event_data( $feed, $entry, $form );

		if ( $has_paypal_feed ) {
			gform_update_meta( $entry['id'], 'ga_event_data', maybe_serialize( $ga_event_data ) );
		}
		else {
			$this->track_form_after_submission( $entry, $form, $ga_event_data );
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
	 * Load UA Settings
	 * 
	 * @since 1.4.0
	 * @return bool Returns true if UA ID is loaded, false otherwise
	 */
	 private function get_ga_id() {
        $this->load_ua_settings();
        if ( $this->ua_id == false ) {
            return '';
        } else {
            return $this->ua_id;   
        }
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
	 * Get data required for processing
	 * @param  array $feed  feed
	 * @param  array $entry GF Entry object
	 * @param  array $form  GF Form object
	 */
	private function get_event_data( $feed, $entry, $form ) {
		global $post;

		// Paypal will need this cookie for the IPN
		$ga_cookie = isset( $_COOKIE['_ga'] ) ? $_COOKIE['_ga'] : '';

		// Location
		$document_location = 'http' . ( isset( $_SERVER['HTTPS'] ) ? 's' : '' ) . '://' . $_SERVER['HTTP_HOST'] . '/' . $_SERVER['REQUEST_URI'];

		// Title
		$document_title = isset( $post ) && get_the_title( $post ) ? get_the_title( $post ) : 'no title';

		// Store everything we need for later
		$ga_event_data = array(
			'ga_cookie' => $ga_cookie,
			'document_location' => $document_location,
			'document_title' => $document_title,
			'gaEventUA' => $this->get_event_var( 'gaEventUA', $feed, $entry, $form ),
			'gaEventCategory' => $this->get_event_var( 'gaEventCategory', $feed, $entry, $form ),
			'gaEventAction' => $this->get_event_var( 'gaEventAction', $feed, $entry, $form ),
			'gaEventLabel' => $this->get_event_var( 'gaEventLabel', $feed, $entry, $form ),
			'gaEventValue' => $this->get_event_var( 'gaEventValue', $feed, $entry, $form ),
		);

		return $ga_event_data;
	}

	/**
	 * Get our event vars
	 */
	private function get_event_var( $var, $feed, $entry, $form ) {

		if ( isset( $feed['meta'][ $var ] ) && ! empty( $feed['meta'][ $var ] ) ) {
			return $feed['meta'][ $var ];
		}
		else {
			switch ( $var ) {
				case 'gaEventCategory':
					return 'Forms';

				case 'gaEventAction':
					return 'Submission';

				case 'gaEventLabel':
					return 'Form: {form_title} ID: {form_id}';

				case 'gaEventValue':
					return false;

				default:
					return false;
			}
		}

	}

	/**
	 * Handle the form after submission before sending to the event push
	 * 
	 * @since 1.4.0
	 * @param array $entry Gravity Forms entry object
	 * @param array $form Gravity Forms form object
	 */
	private function track_form_after_submission( $entry, $form, $ga_event_data ) {

		// Try to get payment amount
		// This needs to go here in case something changes with the amount
		if ( ! $ga_event_data['gaEventValue'] ) {
			$ga_event_data['gaEventValue'] = $this->get_event_value( $entry, $form );
		}

		$event_vars = array( 'gaEventUA', 'gaEventCategory', 'gaEventAction', 'gaEventLabel', 'gaEventValue' );

		foreach ( $event_vars as $var ) {
			if ( $ga_event_data[ $var ] ) {
				$ga_event_data[ $var ] = GFCommon::replace_variables( $ga_event_data[ $var ], $form, $entry, false, false, true, 'text' );
			}
		}
		

		// Push the event to google
		$this->push_event( $entry, $form, $ga_event_data );
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

		$form = GFFormsModel::get_form_meta( $entry['form_id'] );

		$ga_event_data = maybe_unserialize( gform_get_meta( $entry['id'], 'ga_event_data' ) );

		// Override this coming from paypal IPN
		$_COOKIE['_ga'] = $ga_event_data['ga_cookie'];

		// Push the event to google
		$this->push_event( $entry, $form, $ga_event_data );
	}

	/**
	 * Push the Google Analytics Event!
	 * 
	 * @since 1.4.0
	 * @param array $event Gravity Forms event object
	 * @param array $form Gravity Forms form object
	 */
	private function push_event( $entry, $form, $ga_event_data ) {
        
        //Get all analytics codes to send
        $google_analytics_codes = array();
        if ( !empty( $ga_event_data[ 'gaEventUA' ] ) ) {
            $ga_ua = explode( ',', $ga_event_data[ 'gaEventUA' ] );
            if ( is_array( $ga_ua ) ) {
                foreach( $ga_ua as &$value ) {
                    $value = trim( $value );   
                } 
            }
            $google_analytics_codes = $ga_ua;
        }
        if( $this->ua_id ) {
            $google_analytics_codes[] = $this->ua_id;
        }
        $google_analytics_codes = array_unique( $google_analytics_codes );
        
        /**
		* Filter: gform_ua_ids
		*
		* Filter all outgoing UA IDs to send events to
		*
		* @since 1.6.5
		*
		* @param array  $google_analytics_codes UA codes
		* @param object $form Gravity Form form object
		* @param object $entry Gravity Form Entry Object
		*/
        $google_analytics_codes = apply_filters( 'gform_ua_ids', $google_analytics_codes, $form, $entry ); 
        
		$event = new \Racecore\GATracking\Tracking\Event();

		// Set some defaults
		$event->setDocumentLocation( $ga_event_data['document_location'] );
		$event->setDocumentTitle( $ga_event_data['document_title'] );
		
		// Set our event object variables
		$event->setEventCategory( apply_filters( 'gform_event_category', $ga_event_data['gaEventCategory'], $form, $entry ) );
		$event->setEventAction( apply_filters( 'gform_event_action', $ga_event_data['gaEventAction'], $form, $entry ) );
		$event->setEventLabel( apply_filters( 'gform_event_label', $ga_event_data['gaEventLabel'], $form, $entry ) );
		
		
		if ( $event_value = apply_filters( 'gform_event_value', $ga_event_data['gaEventValue'], $form, $entry ) ) {
			// Event value must be a valid float!
			$event_value = GFCommon::to_number( $event_value );
			$event->setEventValue( $event_value );
		}
		
		//Push out the event to each UA code
		foreach( $google_analytics_codes as $ua_code ) {
    		$tracking = new \Racecore\GATracking\GATracking( $ua_code );
    		$tracking->addTracking( $event );
    		
    		try {
    		    $tracking->send();
    		} catch (Exception $e) {
    		    error_log( $e->getMessage() . ' in ' . get_class( $e ) );
    		}
        }// Init tracking object
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
				'title'       => __( 'Google Analytics', 'gravity-forms-google-analytics-event-tracking' ),
				'description' => __( 'Enter your UA code (UA-XXXX-Y). Find it <a href="https://support.google.com/analytics/answer/1032385" target="_blank">using this guide</a>.', 'gravity-forms-google-analytics-event-tracking' ),
				'fields'      => array(
					array(
						'name'              => 'gravity_forms_event_tracking_ua',
						'label'             => __( 'UA Tracking ID', 'gravity-forms-google-analytics-event-tracking' ),
						'type'              => 'text',
						'class'             => 'medium',
						'tooltip' 			=> 'UA-XXXX-Y',
					),
				)
			),
		);
	}

	//---------- Form Settings Pages --------------------------

	/**
	 * Form settings page title
	 * 
	 * @since 1.5.0
	 * @return string Form Settings Title
	 */
	public function feed_settings_title() {
		return __( 'Event Tracking Feed Settings', 'gravity-forms-google-analytics-event-tracking' );
	}

	/**
	 * Form settings fields
	 * 
	 * @since 1.5.0
	 * @return array Array of form settings
	 */
	public function feed_settings_fields() {
    	$ga_id_placeholder = $this->get_ga_id();
		return array(
			array(
				"title"  => __( 'Feed Settings', 'gravity-forms-google-analytics-event-tracking' ),
				"fields" => array(
					array(
						'label'    => __( 'Feed Name', 'gravity-forms-google-analytics-event-tracking' ),
						'type'     => 'text',
						'name'     => 'feedName',
						'class'    => 'medium',
						'required' => true,
						'tooltip'  => '<h6>' . __( 'Feed Name', 'gravity-forms-google-analytics-event-tracking' ) . '</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravity-forms-google-analytics-event-tracking' )
					)
				),
			),
			array(
				"title"  => __( 'Event Tracking Settings', 'gravity-forms-google-analytics-event-tracking' ),
				"fields" => array(
					array(
						"label"   => "",
						"type"    => "instruction_field",
						"name"    => "instructions"
					),
					array(
						"label"   => __( 'Event UA Code', 'gravity-forms-google-analytics-event-tracking' ),
						"type"    => "text",
						"name"    => "gaEventUA",
						"class"   => "medium",
						"tooltip" => sprintf( '<h6>%s</h6>%s', __( 'Google Analytics UA Code', 'gravity-forms-google-analytics-event-tracking' ), __( 'Leave empty to use global GA Code. You can enter multiple UA codes as long as they are comma separated.', 'gravity-forms-google-analytics-event-tracking' ) ),
						"placeholder" => $ga_id_placeholder,
					),
					array(
						"label"   => __( 'Event Category', 'gravity-forms-google-analytics-event-tracking' ),
						"type"    => "text",
						"name"    => "gaEventCategory",
						"class"   => "medium merge-tag-support mt-position-right",
						"tooltip" => sprintf( '<h6>%s</h6>%s', __( 'Event Category', 'gravity-forms-google-analytics-event-tracking' ), __( 'Enter your Google Analytics event category', 'gravity-forms-google-analytics-event-tracking' ) ),
					),
					array(
						"label"   => __( 'Event Action', 'gravity-forms-google-analytics-event-tracking' ),
						"type"    => "text",
						"name"    => "gaEventAction",
						"class"   => "medium merge-tag-support mt-position-right",
						"tooltip" => sprintf( '<h6>%s</h6>%s', __( 'Event Action', 'gravity-forms-google-analytics-event-tracking' ), __( 'Enter your Google Analytics event action', 'gravity-forms-google-analytics-event-tracking' ) ),
					),
					array(
						"label"   => __( 'Event Label', 'gravity-forms-google-analytics-event-tracking' ),
						"type"    => "text",
						"name"    => "gaEventLabel",
						"class"   => "medium merge-tag-support mt-position-right",
						"tooltip" => sprintf( '<h6>%s</h6>%s', __( 'Event Label', 'gravity-forms-google-analytics-event-tracking' ), __( 'Enter your Google Analytics event label', 'gravity-forms-google-analytics-event-tracking' ) ),
					),
					array(
						"label"   => __( 'Event Value', 'gravity-forms-google-analytics-event-tracking' ),
						"type"    => "text",
						"name"    => "gaEventValue",
						"class"   => "medium merge-tag-support mt-position-right",
						"tooltip" => sprintf( '<h6>%s</h6>%s', __( 'Event Value', 'gravity-forms-google-analytics-event-tracking' ), __( 'Enter your Google Analytics event value. Leave blank to omit pushing a value to Google Analytics. Or to use the purchase value of a payment based form. <strong>Note:</strong> This must be a number (int/float).', 'gravity-forms-google-analytics-event-tracking' ) ),
					),
				)
			),
			array(
				"title"  => __( 'Other Settings', 'gravity-forms-google-analytics-event-tracking' ),
				"fields" => array(
					array(
						'name'    => 'conditionalLogic',
						'label'   => __( 'Conditional Logic', 'gravity-forms-google-analytics-event-tracking' ),
						'type'    => 'feed_condition',
						'tooltip' => '<h6>' . __( 'Conditional Logic', 'gravity-forms-google-analytics-event-tracking' ) . '</h6>' . __( 'When conditions are enabled, events will only be sent to google when the conditions are met. When disabled, all form submissions will trigger an event.', 'gravity-forms-google-analytics-event-tracking' )
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
					<p>' . __( "If you leave these blank, the following defaults will be used when the event is tracked", 'gravity-forms-google-analytics-event-tracking' ) . ':</p>
					<p>
						<strong>' . __( "Event Category", 'gravity-forms-google-analytics-event-tracking' ) . ':</strong> Forms<br>
						<strong>' . __( "Event Action", 'gravity-forms-google-analytics-event-tracking' ) . ':</strong> Submission<br>
						<strong>' . __( "Event Label", 'gravity-forms-google-analytics-event-tracking' ) . ':</strong> Form: {form_title} ID: {form_id}<br>
						<strong>' . __( "Event Value", 'gravity-forms-google-analytics-event-tracking' ) . ':</strong> Payment Amount (on payment forms only, otherwise nothing is sent by default)
					</p>
				</td>
			</tr>';
	}

	/**
	 * Return the feed list columns
	 * @return array columns
	 */
	public function feed_list_columns() {
		return array(
			'feedName'        => __( 'Name', 'gravity-forms-google-analytics-event-tracking' ),
			'gaEventCategory' => __( 'Category', 'gravity-forms-google-analytics-event-tracking' ),
			'gaEventAction'   => __( 'Action', 'gravity-forms-google-analytics-event-tracking' ),
			'gaEventLabel'    => __( 'Label', 'gravity-forms-google-analytics-event-tracking' ),
			'gaEventValue'    => __( 'Value', 'gravity-forms-google-analytics-event-tracking' ),
		);
	}

	//--------------  Setup  ---------------

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

		// If the version is below 1.6.0, we need to convert any form settings to a feed
		if ( version_compare( $previous_version, "1.6.0" ) == -1 ) {
			$forms = GFAPI::get_forms( true );

			foreach ( $forms as $form ) {
				$this->upgrade_settings_to_feed( $form );
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
	 * Upgrade settings and convert them into a feed
	 *
	 * @since 1.6.0
	 * @param array $form GF Form object Array
	 */
	public function upgrade_settings_to_feed( $form ) {
		if ( isset( $form['gravity-forms-event-tracking'] ) && $previous_settings = $form['gravity-forms-event-tracking'] ) {

			$field_names = array( 'gaEventCategory', 'gaEventAction', 'gaEventLabel', 'gaEventValue' );

			$previous_was_setup = false;

			$previous_was_enabled = isset( $previous_settings['gaEventTrackingDisabled'] ) ? ! $previous_settings['gaEventTrackingDisabled'] : true;

			$settings = array();

			foreach ($field_names as $field_name) {
				if ( isset( $previous_settings[ $field_name ] ) && $previous_settings[ $field_name ] ) {
					$settings[ $field_name ] = $previous_settings[ $field_name ];
					$previous_was_setup = true;
				}

				unset( $form['gravity-forms-event-tracking'] );

				GFFormsModel::update_form_meta( $form['id'], $form );
			}

			if ( ! $previous_was_setup && ! $previous_was_enabled ) {
				return;
			}

			$settings['feedName'] = __( 'Event Tracking Feed', 'gravity-forms-google-analytics-event-tracking' );
			
			$feed_id = $this->save_feed_settings( 0, $form['id'], $settings );
			
			if ( ! $previous_was_enabled ) {
				$this->update_feed_active( $feed_id, false );
			}
			
		}
		else {
			return;
		}
		
	}

}
