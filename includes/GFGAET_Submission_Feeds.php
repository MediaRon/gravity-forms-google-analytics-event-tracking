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

class GFGAET_Submission_Feeds extends GFFeedAddOn {

	protected $_version                  = GFGAET_VERSION;
	protected $_min_gravityforms_version = GFGAET_MIN_GFORMS_VERSION;
	protected $_slug                     = 'gravity-forms-event-tracking';
	protected $_path                     = 'gravity-forms-google-analytics-event-tracking/gravity-forms-event-tracking.php';
	protected $_full_path                = __FILE__;
	protected $_title                    = 'Gravity Forms Google Analytics Event Tracking';
	protected $_short_title              = 'Event Tracking';

	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_event_tracking', 'gravityforms_event_tracking_uninstall' );

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_event_tracking';
	protected $_capabilities_form_settings = 'gravityforms_event_tracking';
	protected $_capabilities_uninstall     = 'gravityforms_event_tracking_uninstall';

	public $ua_id = false;

	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Overriding init function to change the load_plugin_textdomain call.
	 * See comment above for explanation.
	 */
	public function init() {
		parent::init();

		// GTM UTM Variable Tracking Script.
		add_action( 'wp_enqueue_scripts', array( $this, 'load_utm_gtm_script' ) );

		// Load analytics?
		add_action( 'wp_head', array( $this, 'maybe_install_analytics' ) );

		// Load tag manager?
		add_action( 'wp_head', array( $this, 'maybe_install_tag_manager_header' ) );
		add_action( 'wp_body_open', array( $this, 'tag_manager_after_body' ) );

		if ( $this->is_preview() ) {
			add_action( 'gform_preview_header', array( $this, 'preview_header' ) );
			add_action( 'gform_preview_body_open', array( $this, 'tag_manager_after_body' ) );
		}

		add_filter(
			'plugin_action_links_' . plugin_basename( GFGAET_FILE ),
			array( $this, 'add_settings_link' )
		);

		// Delay until payment.
		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Process the Event Tracking feed only when payment is received.', 'gravity-forms-google-analytics-event-tracking' ),
			)
		);
	}

	/**
	 * Add a settings link to the plugin's options.
	 *
	 * Add a settings link on the WordPress plugin's page.
	 *
	 * @since 2.4.5
	 * @access public
	 *
	 * @see init
	 *
	 * @param array $links Array of plugin options.
	 * @return array $links Array of plugin options
	 */
	public function add_settings_link( $links ) {

		$settings_url = admin_url( 'admin.php?page=gf_settings&subview=GFGAET_UA' );
		if ( current_user_can( 'manage_options' ) ) {
			$options_link = sprintf( '<a href="%s">%s</a>', esc_url( $settings_url ), _x( 'Settings', 'Gravity Forms Event Tracking Settings page', 'gravity-forms-google-analytics-event-tracking' ) );
			$links[]      = $options_link;
		}
		$docs_link = sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( 'https://mediaron.com/event-tracking-for-gravity-forms/?utm_source=wordpress_plugins_page&utm_medium=documentation&utm_campaign=event_tracking' ), _x( 'Documentation', 'Gravity Forms Event Tracking Documentation page', 'gravity-forms-google-analytics-event-tracking' ) );

		$beta_link = sprintf( '<a href="%s" target="_blank" style="color: green; font-weight: 700;">%s</a>', esc_url( 'https://www.gravityforms.com/add-ons/google-analytics/' ), _x( 'Get the Google Analytics Add-on', 'Gravity Forms Google Analytics Page', 'gravity-forms-google-analytics-event-tracking' ) );
		$links[]   = $docs_link;
		$links[]   = $beta_link;

		return $links;
	}

	/**
	 * Callback for the preview header action.
	 *
	 * Load analytics or tag manager in the form preview.
	 *
	 * @since 2.4.0
	 *
	 * @param int $form_id The Form ID being previewed.
	 */
	public function preview_header( $form_id ) {

		/**
		 * Filter: gform_ua_load_preview
		 *
		 * Allow Google Analytics and Tag Manager to load on the preview screen.
		 *
		 * @since 2.4.0
		 *
		 * @param bool $load_on_preview Whether to load analytics/tag manager on the preview screen.
		 */
		$load_on_preview        = true;
		$google_analytics_codes = (bool) apply_filters( 'gform_ua_load_preview', $load_on_preview );

		if ( $load_on_preview ) {
			$this->maybe_install_analytics();
			$this->maybe_install_tag_manager_header();
		}
	}

	/**
	 * Run after the body tag in preview mode and front-end.
	 *
	 * @since 2.4.0
	 *
	 * @param int $form_id The form ID.
	 */
	public function tag_manager_after_body( $form_id ) {
		$this->maybe_install_tag_manager_after_body();
	}

	/**
	 * Installs Tag Manager after the body tag if enabled.
	 *
	 * @since  2.4.0
	 *
	 * @param bool $force Whether to force install tag manager.
	 */
	public function maybe_install_tag_manager_after_body( $force = false ) {
		$ua_options = get_option( 'gravityformsaddon_GFGAET_UA_settings', array() );

		// Check mode. Return if mode is not set.
		if ( ! isset( $ua_options['mode'] ) ) {
			return;
		}

		// Prevent index errors.
		if ( ! isset( $ua_options['gravity_forms_event_tracking_gtm_install'] ) ) {
			return;
		}

		// Only load if GA is set in the mode.
		if ( 'gtm_install_on' !== $ua_options['gravity_forms_event_tracking_gtm_install'] ) {
			return;
		}

		// Get GTM container information. Return if not set.
		$gtm_code = isset( $ua_options['gravity_forms_event_tracking_gtm_account_id'] ) ? sanitize_text_field( $ua_options['gravity_forms_event_tracking_gtm_account_id'] ) : '';
		if ( empty( $gtm_code ) ) {
			return;
		}

		if ( isset( $ua_options['gravity_forms_event_tracking_gtm_install'] ) ) {
			if ( 'gtm_install_on' === $ua_options['gravity_forms_event_tracking_gtm_install'] ) {
				/**
				 * Allow third-parties to enable/disable GTM loading.
				 *
				 * @since 2.4.0
				 *
				 * @param bool  Output GTM script (default: true).
				 * @param bool  Whether the output is in preview mode.
				 * @param array Saved settings.
				 */
				$enable_gtm_output = apply_filters( 'gform_gtm_script_enable', true, $this->is_preview(), $ua_options );

				if ( ! $enable_gtm_output ) {
					return;
				}

				// User has requested GTM installation. Proceed.
				ob_start();
				echo "\r\n";
				?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_js( $gtm_code ); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
				<?php
				/**
				 * Allow custom scripting for Google Tag Manager
				 *
				 * @since 2.4.0
				 *
				 * @param string $gtm_code Tag Manager code.
				 */
				do_action( 'gform_install_tag_manager_after_body', $gtm_code );

				/**
				 * Allow third-parties to modify the JavaScript that is returned.
				 *
				 * @since 2.4.0
				 *
				 * @param string JavaScript to output.
				 *
				 * @return string JavaScript to output.
				 */
				echo wp_kses( apply_filters( 'gform_output_tag_manager_body', ob_get_clean() ), $this->get_javascript_kses() );
			}
		}
	}

	/**
	 * Installs Tag Manager if the user has selected that option in settings.
	 *
	 * @since  2.4.0
	 *
	 * @param bool $force Whether to force install tag manager.
	 */
	public function maybe_install_tag_manager_header( $force = false ) {
		$ua_options = get_option( 'gravityformsaddon_GFGAET_UA_settings', array() );

		// Check mode. Return if mode is not set.
		if ( ! isset( $ua_options['mode'] ) ) {
			return;
		}

		// Prevent index errors.
		if ( ! isset( $ua_options['gravity_forms_event_tracking_gtm_install'] ) ) {
			return;
		}

		// Only load if GA is set in the mode.
		if ( 'gtm_install_on' !== $ua_options['gravity_forms_event_tracking_gtm_install'] ) {
			return;
		}

		// Get GTM container information. Return if not set.
		$gtm_code = isset( $ua_options['gravity_forms_event_tracking_gtm_account_id'] ) ? sanitize_text_field( $ua_options['gravity_forms_event_tracking_gtm_account_id'] ) : '';
		if ( empty( $gtm_code ) ) {
			return;
		}

		if ( isset( $ua_options['gravity_forms_event_tracking_gtm_install'] ) ) {
			if ( 'gtm_install_on' === $ua_options['gravity_forms_event_tracking_gtm_install'] ) {
				/**
				 * Allow third-parties to enable/disable GTM loading.
				 *
				 * @since 2.4.0
				 *
				 * @param bool  Output GTM script (default: true).
				 * @param bool  Whether the output is in preview mode.
				 * @param array Saved settings.
				 */
				$enable_gtm_output = apply_filters( 'gform_gtm_script_enable', true, $this->is_preview(), $ua_options );

				if ( ! $enable_gtm_output ) {
					return;
				}

				// User has requested GTM installation. Proceed.
				ob_start();
				echo "\r\n";
				?>
	<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js( $gtm_code ); ?>');</script>
<!-- End Google Tag Manager -->
				<?php
				/**
				 * Allow custom scripting for Google Tag Manager
				 *
				 * @since 2.4.0
				 *
				 * @param string $gtm_code Tag Manager code.
				 */
				do_action( 'gform_install_tag_manager_header', $gtm_code );

				/**
				 * Allow third-parties to modify the JavaScript that is returned.
				 *
				 * @since 2.4.0
				 *
				 * @param string JavaScript to output.
				 *
				 * @return string JavaScript to output.
				 */
				echo wp_kses( apply_filters( 'gform_output_tag_manager_header', ob_get_clean() ), $this->get_javascript_kses() );
			}
		}
	}

	/**
	 * Installs GTAG Google Analytics if user has selected that option in settings.
	 *
	 * @since  2.4.0
	 *
	 * @param bool $force Whether to force install analytics.
	 */
	public function maybe_install_analytics( $force = false ) {
		$ua_options = get_option( 'gravityformsaddon_GFGAET_UA_settings', array() );

		// Check mode. Return if mode is not set.
		if ( ! isset( $ua_options['mode'] ) ) {
			return;
		}

		// Only load if GA is set in the mode.
		if ( 'ga_on' !== $ua_options['mode'] ) {
			return;
		}

		if ( isset( $ua_options['gravity_forms_event_tracking_ua_gtag_install'] ) ) {
			if ( 'gtag_on' === $ua_options['gravity_forms_event_tracking_ua_gtag_install'] ) {
				/**
				 * Allow third-parties to enable/disable gtag loading.
				 *
				 * @since 2.4.0
				 *
				 * @param bool  Output gtag analytics (default: true).
				 * @param bool  Whether the output is in preview mode.
				 * @param array Saved settings.
				 *
				 * @return bool true to force load analytics, false if not.
				 */
				$enable_gtag = apply_filters( 'gform_ua_gtag_enable', true, $this->is_preview(), $ua_options );

				// Return early if gtag output is disabled via filter.
				if ( ! $enable_gtag ) {
					return;
				}

				// Return if no Analytics tracking code is active.
				if ( ! isset( $ua_options['gravity_forms_event_tracking_ua'] ) ) {
					return;
				}

				// Return if GA code is empty.
				$ga_code = $ua_options['gravity_forms_event_tracking_ua'];
				if ( empty( $ga_code ) ) {
					return;
				}

				// Sanitize ga code.
				$ga_code = sanitize_text_field( $ga_code );

				// User has requested GA installation. Proceed.
				ob_start();
				echo "\r\n";
				?>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_html( $ga_code ); ?>"></script> <?php //phpcs:ignore ?>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());

gtag('config', '<?php echo esc_js( $ga_code ); ?>');
				<?php
				/**
				 * Allow custom scripting for Google Analytics GTAG
				 *
				 * @since 2.4.0
				 *
				 * @param string $ga_code Google Analytics Property ID
				 */
				do_action( 'gform_ga_install_analytics', $ga_code );
				?>
</script>
				<?php
				/**
				 * Allow third-parties to modify the JavaScript that is returned.
				 *
				 * @since 2.4.0
				 *
				 * @param string JavaScript to output.
				 *
				 * @return string JavaScript to output.
				 */
				echo wp_kses( apply_filters( 'gform_ga_output_analytics', ob_get_clean() ), $this->get_javascript_kses() );
			}
		}
	}

	/**
	 * Load a UTM tracking script for Google Tag Manager.
	 */
	public function load_utm_gtm_script() {
		$ua_options = get_option( 'gravityformsaddon_GFGAET_UA_settings', array() );
		if ( isset( $ua_options['gravity_forms_event_tracking_gtm_utm_vars'] ) ) {
			if ( 'utm_on' === $ua_options['gravity_forms_event_tracking_gtm_utm_vars'] ) {
				$utm_vars            = array(
					'utm_id',
					'utm_source',
					'utm_medium',
					'utm_campaign',
					'utm_term',
					'utm_content',
				);
				$can_load_utm_script = false;
				foreach ( $utm_vars as $utm_var ) {
					if ( isset( $_GET[ $utm_var ] ) ) { // phpcs:ignore
						$can_load_utm_script = true;
						break;
					}
				}
				if ( $can_load_utm_script || $this->has_form() ) {
					$script_location = GFGAET::get_plugin_url( '/js/utm-tag-manager.min.js' );
					if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
						$script_location = GFGAET::get_plugin_url( '/js/utm-tag-manager.js' );
					}
					wp_enqueue_script(
						'gforms_event_tracking_utm_gtm',
						$script_location,
						array( 'jquery', 'wp-ajax-response' ),
						$this->_version,
						true
					);
				}
			}
		}
	}

	/**
	 * Determines if a page has a form on it.
	 *
	 * @return bool Whether post/page has gravity form.
	 */
	private function has_form() {
		if ( ! class_exists( 'GFCommon' ) || ! is_singular() ) {
			return;
		}
		require_once GFCommon::get_base_path() . '/form_display.php';
		GFFormDisplay::parse_forms( get_queried_object()->post_content, $forms, $blocks );
		return ! empty( $forms );
	}

	/**
	 * Outputs admin scripts to handle form submission in back-end.
	 *
	 * @since  2.4.5
	 *
	 * @return array
	 */
	public function styles() {
		$min    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || rgget( 'gform_debug' ) ? '' : '.min';
		$styles = array(
			array(
				'handle'  => 'gfgaet_admin',
				'enqueue' => array(
					array( 'query' => 'page=gf_settings&subview=GFGAET_UA' ),
				),
				'src'     => esc_url( GFGAET::get_plugin_url( '/css/admin.css' ) ),
			),
		);
		return array_merge( parent::styles(), $styles );
	}

	/**
	 * Retrieve KSES allowed tags for Google Analytics and/or Tag Manager.
	 *
	 * @since 2.4.0
	 */
	public function get_javascript_kses() {
		$allowed_tags = array(
			'iframe'   => array(
				'src'    => true,
				'style'  => true,
				'width'  => true,
				'height' => true,
			),
			'noscript' => array(),
			'script'   => array(
				'data-cfasync' => true,
				'async'        => true,
				'src'          => true,
			),
		);
		/**
		 * Allow third-parties to add or substract allowed tags.
		 *
		 * @since 2.4.0
		 *
		 * @param array $allowed_tags KSES allowed tags.
		 *
		 * @return array updated KSES allowed tags.
		 */
		$allowed_tags = apply_filters( 'gform_ga_javascript_kses', $allowed_tags );
		return $allowed_tags;
	}

	/**
	 * Public facing init
	 *
	 * @since 2.0.0
	 */
	public function init_frontend() {

		parent::init_frontend();

		// IPN hook for paypal standard!
		if ( class_exists( 'GFPayPal' ) ) {
			add_action( 'gform_paypal_post_ipn', array( $this, 'paypal_track_form_post_ipn' ), 10, 2 );
		}

	}

	/**
	 * Process the feed!
	 *
	 * @param  array $feed  feed data and settings
	 * @param  array $entry gf entry object
	 * @param  array $form  gf form data
	 */
	public function process_feed( $feed, $entry, $form ) {

		$paypal_feeds    = $this->get_feeds_by_slug( 'gravityformspaypal', $form['id'] );
		$has_paypal_feed = false;

		foreach ( $paypal_feeds as $paypal_feed ) {
			if ( $paypal_feed['is_active'] && $this->is_feed_condition_met( $paypal_feed, $form, $entry ) ) {
				$has_paypal_feed = true;
				break;
			}
		}

		$ga_event_data = $this->get_event_data( $feed, $entry, $form );

		if ( $has_paypal_feed ) {
			gform_update_meta( $entry['id'], 'ga_event_data', maybe_serialize( $ga_event_data ) );
		} else {
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

		$this->ua_id = $ua_id = GFGAET::get_ua_code();

		if ( false !== $this->ua_id ) {
			return true;
		}
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
	 * Get data required for processing
	 *
	 * @param  array $feed  feed
	 * @param  array $entry GF Entry object
	 * @param  array $form  GF Form object
	 */
	private function get_event_data( $feed, $entry, $form ) {
		global $post;

		// Paypal will need this cookie for the IPN
		$ga_cookie = isset( $_COOKIE['_ga'] ) ? $_COOKIE['_ga'] : '';

		// Location
		$document_location = str_replace( home_url(), '', $entry['source_url'] );

		// Title
		$document_title = isset( $post ) && get_the_title( $post ) ? get_the_title( $post ) : 'no title';

		// Store everything we need for later
		$ga_event_data = array(
			'feed_id'           => $feed['id'],
			'entry_id'          => $entry['id'],
			'ga_cookie'         => $ga_cookie,
			'document_location' => $document_location,
			'document_title'    => $document_title,
			'gaEventUA'         => $this->get_event_var( 'gaEventUA', $feed, $entry, $form ),
			'gaEventCategory'   => $this->get_event_var( 'gaEventCategory', $feed, $entry, $form ),
			'gaEventAction'     => $this->get_event_var( 'gaEventAction', $feed, $entry, $form ),
			'gaEventLabel'      => $this->get_event_var( 'gaEventLabel', $feed, $entry, $form ),
			'gaEventValue'      => $this->get_event_var( 'gaEventValue', $feed, $entry, $form ),
		);

		return $ga_event_data;
	}

	/**
	 * Get our event vars
	 */
	private function get_event_var( $var, $feed, $entry, $form ) {

		if ( isset( $feed['meta'][ $var ] ) && ! empty( $feed['meta'][ $var ] ) ) {
			return $feed['meta'][ $var ];
		} else {
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
		// Push the event to matomo
		$this->push_matomo_event( $entry, $form, $ga_event_data );
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
		// Push the event to matomo
		$this->push_matomo_event( $entry, $form, $ga_event_data );
	}

	/**
	 * Push the Google Analytics Event!
	 *
	 * @since 1.4.0
	 * @param array $event Gravity Forms event object
	 * @param array $form Gravity Forms form object
	 */
	private function push_event( $entry, $form, $ga_event_data ) {

		// Get all analytics codes to send
		$google_analytics_codes = $this->get_ua_codes( $ga_event_data['gaEventUA'], $this->get_ga_id() );

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

		if ( ! is_array( $google_analytics_codes ) || empty( $google_analytics_codes ) ) {
			// If GTM, no need to have a GA code.
			if ( ! GFGAET::is_gtm_only() ) {
				return;
			}
		}
		$google_analytics_codes = array_unique( $google_analytics_codes );

		$event = new GFGAET_Measurement_Protocol();
		$event->init();

		// Set some defaults
		$event->set_document_path( str_replace( home_url(), '', $entry['source_url'] ) );
		$event_url_parsed = parse_url( home_url() );
		$event->set_document_host( $event_url_parsed['host'] );
		$event->set_document_location( str_replace( '//', '/', 'http' . ( isset( $_SERVER['HTTPS'] ) ? 's' : '' ) . '://' . $_SERVER['HTTP_HOST'] . '/' . $_SERVER['REQUEST_URI'] ) );
		$event->set_document_title( $ga_event_data['document_title'] );

		// Set our event object variables
		/**
		 * Filter: gform_event_category
		 *
		 * Filter the event category dynamically
		 *
		 * @since 1.6.5
		 *
		 * @param string $category Event Category
		 * @param object $form     Gravity Form form object
		 * @param object $entry    Gravity Form Entry Object
		 */
		$event_category = apply_filters( 'gform_event_category', $ga_event_data['gaEventCategory'], $form, $entry );
		$event->set_event_category( $event_category );

		/**
		 * Filter: gform_event_action
		 *
		 * Filter the event action dynamically
		 *
		 * @since 1.6.5
		 *
		 * @param string $action Event Action
		 * @param object $form   Gravity Form form object
		 * @param object $entry  Gravity Form Entry Object
		 */
		$event_action = apply_filters( 'gform_event_action', $ga_event_data['gaEventAction'], $form, $entry );
		$event->set_event_action( $event_action );

		/**
		 * Filter: gform_event_label
		 *
		 * Filter the event label dynamically
		 *
		 * @since 1.6.5
		 *
		 * @param string $label Event Label
		 * @param object $form  Gravity Form form object
		 * @param object $entry Gravity Form Entry Object
		 */
		$event_label = apply_filters( 'gform_event_label', $ga_event_data['gaEventLabel'], $form, $entry );
		$event->set_event_label( $event_label );

		/**
		 * Filter: gform_event_value
		 *
		 * Filter the event value dynamically
		 *
		 * @since 1.6.5
		 *
		 * @param object $form Gravity Form form object
		 * @param object $entry Gravity Form Entry Object
		 */
		$event_value = apply_filters( 'gform_event_value', $ga_event_data['gaEventValue'], $form, $entry );
		if ( $event_value ) {
			// Event value must be a valid integer!
			$event_value = absint( round( GFCommon::to_number( $event_value ) ) );
			$event->set_event_value( $event_value );
		}

		$feed_id  = absint( $ga_event_data['feed_id'] );
		$entry_id = $entry['id'];

		if ( GFGAET::is_ga_only() ) {
			$count              = 1;
			$is_interactive_hit = 'false';
			$interactive_hit    = GFGAET::get_interactive_hit_tracker();
			if ( 'interactive_off' == $interactive_hit ) {
				$is_interactive_hit = 'false';
			} else {
				$is_interactive_hit = 'true';
			}
			?>
			<script>
			<?php
			foreach ( $google_analytics_codes as $ua_code ) {
				?>

				// Check for gtab implementation
				if( typeof window.parent.gtag != 'undefined' ) {
					window.parent.gtag( 'event', '<?php echo esc_js( $event_action ); ?>', {
						nonInteraction: <?php echo esc_js( $is_interactive_hit ); ?>,
						'event_category': '<?php echo esc_js( $event_category ); ?>',
						'event_label': '<?php echo esc_js( $event_label ); ?>'
						<?php
						if ( 0 !== $event_value && ! empty( $event_value ) ) {
							echo sprintf( ",'value': '%s'", esc_js( $event_value ) ); }
						?>
						}
					);
					if ( typeof( console ) == 'object' ) {
						console.log('gtag tried');
					}
				} else {
					// Check for GA from Monster Insights Plugin
					if ( typeof window.parent.ga == 'undefined' ) {
						console.log('ga not found');
						if ( typeof window.parent.__gaTracker != 'undefined' ) {
							if( typeof( console ) == 'object' ) {
								console.log('monster insights found');
							}
							window.parent.ga = window.parent.__gaTracker;
						}
					}
					if ( typeof( console ) == 'object' ) {
						console.log('try window.parent.ga');
					}
					if ( typeof window.parent.ga != 'undefined' ) {

						var ga_tracker = '';
						var ga_send = 'send';
						// Try to get original UA code from third-party plugins or tag manager

						ga_tracker = '<?php echo esc_js( GFGAET::get_ua_tracker() ); ?>';
						if ( typeof( console ) == 'object' ) {
							console.log( 'tracker name' );
							console.log( ga_tracker );
						}
						if( ga_tracker.length > 0 ) {
							ga_send = ga_tracker + '.' + ga_send;
						}
						if ( typeof( console ) == 'object' ) {
							console.log( 'send command' );
							console.log( ga_send );
							console.log( '<?php echo $event_value; ?>' );
						}

						// Use that tracker
						window.parent.ga( ga_send, 'event', '<?php echo esc_js( $event_category ); ?>', '<?php echo esc_js( $event_action ); ?>', '<?php echo esc_js( $event_label ); ?>'
						<?php
						if ( 0 !== $event_value && ! empty( $event_value ) ) {
							echo ',' . "'" . esc_js( $event_value ) . "'"; }
						?>
						,{
							nonInteraction: <?php echo esc_js( $is_interactive_hit ); ?>
						});

					}
				}

				<?php
				$count += 1;
			}
			?>
			</script>
			<?php
			$this->add_note( $entry['id'], __( 'An event has been sent using Google Analytics.', 'gravity-forms-google-analytics-event-tracking' ), 'success' );
			return;
		} elseif ( GFGAET::is_gtm_only() ) {
			?>
			<script>
			var utmVariables = localStorage.getItem('googleAnalyticsUTM');
			var utmSource = '',
				utmMedium = '',
				utmCampaign = '',
				utmTerm = '',
				utmContent = '';
			if ( null != utmVariables ) {
				utmVariables = JSON.parse( utmVariables );
				utmSource = utmVariables.source;
				utmMedium = utmVariables.medium;
				utmCampaign = utmVariables.campaign;
				utmTerm = utmVariables.term;
				utmContent = utmVariables.content;
			}
			if ( typeof( window.parent.dataLayer ) != 'undefined' ) {
				if (typeof(window.parent.gfaetTagManagerSent) == 'undefined' ) {
					window.parent.dataLayer.push({'event': 'GFTrackEvent',
						'GFTrackCategory':'<?php echo esc_js( $event_category ); ?>',
						'GFTrackAction':'<?php echo esc_js( $event_action ); ?>',
						'GFTrackLabel':'<?php echo esc_js( $event_label ); ?>',
						'GFTrackValue': <?php echo absint( $event_value ); ?>,
						'GFEntryData':<?php echo wp_json_encode( $entry ); ?>,
						'GFTrackSource': utmSource,
						'GFTrackMedium': utmMedium,
						'GFTrackCampaign': utmCampaign,
						'GFTrackTerm': utmTerm,
						'GFTrackContent': utmContent,
						});
					}
			}
			try {
				window.parent.gfaetTagManagerSent = true; <?php // Prevent tag manager from sending multiple events. ?>
			} catch ( e ) {
				// Catch error.
			}
			</script>
			<?php
			$this->add_note( $entry['id'], __( 'An event has been sent using Google Google Tag Manager.', 'gravity-forms-google-analytics-event-tracking' ), 'success' );
			return;
		} else {
			// Push out the event to each UA code
			foreach ( $google_analytics_codes as $ua_code ) {
				// Submit the event
				$event->send( $ua_code );
			}
			$this->add_note( $entry['id'], __( 'An event has been sent using the Google Analytics Measurement Protocol.', 'gravity-forms-google-analytics-event-tracking' ), 'success' );
		}

	}

	/**
	 * Push the Matomo (formerly Piwik) Event!
	 *
	 * @since 2.1.0
	 * @param array $event Gravity Forms event object
	 * @param array $form Gravity Forms form object
	 */
	private function push_matomo_event( $entry, $form, $ga_event_data ) {

		if ( false === GFGAET::is_matomo_configured() ) {
			return;
		}

		$event = new GFGAET_Matomo_HTTP_API();
		$event->init();

		// Set some defaults
		$event->set_matomo_document_location( 'http' . ( isset( $_SERVER['HTTPS'] ) ? 's' : '' ) . '://' . str_replace( '//', '/', $_SERVER['HTTP_HOST'] . '/' . $_SERVER['REQUEST_URI'] ) );

		// Set our event object variables
		/**
		 * Filter: gform_event_category
		 *
		 * Filter the event category dynamically
		 *
		 * @since 1.6.5
		 *
		 * @param string $category Event Category
		 * @param object $form     Gravity Form form object
		 * @param object $entry    Gravity Form Entry Object
		 */
		$event_category = apply_filters( 'gform_event_category', $ga_event_data['gaEventCategory'], $form, $entry );
		$event->set_matomo_event_category( $event_category );

		/**
		 * Filter: gform_event_action
		 *
		 * Filter the event action dynamically
		 *
		 * @since 1.6.5
		 *
		 * @param string $action Event Action
		 * @param object $form   Gravity Form form object
		 * @param object $entry  Gravity Form Entry Object
		 */
		$event_action = apply_filters( 'gform_event_action', $ga_event_data['gaEventAction'], $form, $entry );
		$event->set_matomo_event_action( $event_action );

		/**
		 * Filter: gform_event_label
		 *
		 * Filter the event label dynamically
		 *
		 * @since 1.6.5
		 *
		 * @param string $label Event Label
		 * @param object $form  Gravity Form form object
		 * @param object $entry Gravity Form Entry Object
		 */
		$event_label = apply_filters( 'gform_event_label', $ga_event_data['gaEventLabel'], $form, $entry );
		$event->set_matomo_event_label( $event_label );

		/**
		 * Filter: gform_event_value
		 *
		 * Filter the event value dynamically
		 *
		 * @since 1.6.5
		 *
		 * @param object $form Gravity Form form object
		 * @param object $entry Gravity Form Entry Object
		 */
		$event_value = apply_filters( 'gform_event_value', $ga_event_data['gaEventValue'], $form, $entry );
		if ( $event_value ) {
			// Event value must be a valid integer!
			$event_value = absint( round( GFCommon::to_number( $event_value ) ) );
			$event->set_matomo_event_value( $event_value );
		}

		$feed_id  = absint( $ga_event_data['feed_id'] );
		$entry_id = $entry['id'];

		if ( GFGAET::is_matomo_js_only() ) {
			?>
			<script>
			if ( typeof window.parent._paq != 'undefined' ) {

				window.parent._paq.push(['trackEvent', '<?php echo esc_js( $event_category ); ?>', '<?php echo esc_js( $event_action ); ?>', '<?php echo esc_js( $event_label ); ?>'
				<?php
				if ( 0 !== $event_value && ! empty( $event_value ) ) {
					echo ',' . "'" . esc_js( $event_value ) . "'"; }
				?>
				]);

			}
			</script>
			<?php
			return;
		}
		// Submit the Matomo (formerly Piwik) event
		$event->send_matomo();
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

	// ---------- Form Settings Pages --------------------------

	/**
	 * Form settings page title
	 *
	 * @since 1.5.0
	 * @return string Form Settings Title
	 */
	public function feed_settings_title() {
		return __( 'Event Tracking Settings', 'gravity-forms-google-analytics-event-tracking' );
	}

	public function maybe_save_feed_settings( $feed_id, $form_id ) {
		if ( ! rgpost( 'gform-settings-save' ) ) {
			return $feed_id;
		}

		check_admin_referer( $this->_slug . '_save_settings', '_' . $this->_slug . '_save_settings_nonce' );

		if ( ! $this->current_user_can_any( $this->_capabilities_form_settings ) ) {
			GFCommon::add_error_message( esc_html__( "You don't have sufficient permissions to update the form settings.", 'gravityforms' ) );
			return $feed_id;
		}

		// store a copy of the previous settings for cases where action would only happen if value has changed
		$feed = $this->get_feed( $feed_id );
		$this->set_previous_settings( $feed['meta'] );

		$settings = $this->get_posted_settings();
		$sections = $this->get_feed_settings_fields();
		$settings = $this->trim_conditional_logic_vales( $settings, $form_id );

		$is_valid = $this->validate_settings( $sections, $settings );
		$result   = false;

		// Check for a valid UA code
		$feed_ua_code = isset( $settings['gaEventUA'] ) ? $settings['gaEventUA'] : '';
		$ua_codes     = $this->get_ua_codes( $feed_ua_code, $this->get_ga_id() );

		if ( $is_valid ) {
			$settings = $this->filter_settings( $sections, $settings );
			$feed_id  = $this->save_feed_settings( $feed_id, $form_id, $settings );
			if ( $feed_id ) {
				GFCommon::add_message( $this->get_save_success_message( $sections ) );
			} else {
				GFCommon::add_error_message( $this->get_save_error_message( $sections ) );
			}
		} else {
			GFCommon::add_error_message( $this->get_save_error_message( $sections ) );
		}

		return $feed_id;
	}

	/**
	 * Return Google Analytics GA Codes
	 *
	 * @since 1.7.0
	 * @return array Array of GA codes
	 */
	private function get_ua_codes( $feed_ua, $settings_ua ) {
		$google_analytics_codes = array();
		if ( ! empty( $feed_ua ) ) {
			$ga_ua = explode( ',', $feed_ua );
			if ( is_array( $ga_ua ) ) {
				foreach ( $ga_ua as &$value ) {
					$value = trim( $value );
				}
			}
			$google_analytics_codes = $ga_ua;
		}
		if ( $settings_ua ) {
			$google_analytics_codes[] = $settings_ua;
		}
		$google_analytics_codes = array_unique( $google_analytics_codes );
		return $google_analytics_codes;
	}

	/**
	 * Form settings fields
	 *
	 * @since 1.5.0
	 * @return array Array of form settings
	 */
	public function feed_settings_fields() {
		$ga_id_placeholder = $this->get_ga_id();
		$ua_options        = get_option( 'gravityformsaddon_GFGAET_UA_settings', array() );
		$beta_notification = rgar( $ua_options, 'beta_notification' );
		$beta_field        = array(
			'name' => 'gravityforms_ga',
			'type' => $beta_notification === 'on' || rgblank( $beta_notification ) ? 'gforms_beta_cta' : 'hidden',
		);
		return array(
			array(
				'title'  => __( 'Feed Settings', 'gravity-forms-google-analytics-event-tracking' ),
				'fields' => array(
					$beta_field,
					array(
						'label'    => __( 'Feed Name', 'gravity-forms-google-analytics-event-tracking' ),
						'type'     => 'text',
						'name'     => 'feedName',
						'class'    => 'medium',
						'required' => true,
						'tooltip'  => '<h6>' . __( 'Feed Name', 'gravity-forms-google-analytics-event-tracking' ) . '</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravity-forms-google-analytics-event-tracking' ),
					),
				),
			),
			array(
				'title'  => __( 'Event Tracking Settings', 'gravity-forms-google-analytics-event-tracking' ),
				'fields' => array(
					array(
						'label' => '',
						'type'  => 'instruction_field',
						'name'  => 'instructions',
					),
					array(
						'label'       => __( 'Event UA Code', 'gravity-forms-google-analytics-event-tracking' ),
						'type'        => 'text',
						'name'        => 'gaEventUA',
						'class'       => 'medium',
						'tooltip'     => sprintf( '<h6>%s</h6>%s', __( 'Google Analytics UA Code (Optional)', 'gravity-forms-google-analytics-event-tracking' ), __( 'Leave empty to use global GA Code. You can enter multiple UA codes as long as they are comma separated.', 'gravity-forms-google-analytics-event-tracking' ) ),
						'placeholder' => $ga_id_placeholder,
					),
					array(
						'label'   => __( 'Event Category', 'gravity-forms-google-analytics-event-tracking' ),
						'type'    => 'text',
						'name'    => 'gaEventCategory',
						'class'   => 'medium merge-tag-support mt-position-right',
						'tooltip' => sprintf( '<h6>%s</h6>%s', __( 'Event Category', 'gravity-forms-google-analytics-event-tracking' ), __( 'Enter your Google Analytics event category', 'gravity-forms-google-analytics-event-tracking' ) ),
					),
					array(
						'label'   => __( 'Event Action', 'gravity-forms-google-analytics-event-tracking' ),
						'type'    => 'text',
						'name'    => 'gaEventAction',
						'class'   => 'medium merge-tag-support mt-position-right',
						'tooltip' => sprintf( '<h6>%s</h6>%s', __( 'Event Action', 'gravity-forms-google-analytics-event-tracking' ), __( 'Enter your Google Analytics event action', 'gravity-forms-google-analytics-event-tracking' ) ),
					),
					array(
						'label'   => __( 'Event Label', 'gravity-forms-google-analytics-event-tracking' ),
						'type'    => 'text',
						'name'    => 'gaEventLabel',
						'class'   => 'medium merge-tag-support mt-position-right',
						'tooltip' => sprintf( '<h6>%s</h6>%s', __( 'Event Label', 'gravity-forms-google-analytics-event-tracking' ), __( 'Enter your Google Analytics event label', 'gravity-forms-google-analytics-event-tracking' ) ),
					),
					array(
						'label'   => __( 'Event Value', 'gravity-forms-google-analytics-event-tracking' ),
						'type'    => 'text',
						'name'    => 'gaEventValue',
						'class'   => 'medium merge-tag-support mt-position-right',
						'tooltip' => sprintf( '<h6>%s</h6>%s', __( 'Event Value', 'gravity-forms-google-analytics-event-tracking' ), __( 'Enter your Google Analytics event value. Leave blank to omit pushing a value to Google Analytics. Or to use the purchase value of a payment based form. <strong>Note:</strong> This must be a number (int). Floating numbers (e.g., 20.95) will be rounded up (e.g., 30)', 'gravity-forms-google-analytics-event-tracking' ) ),
					),
				),
			),
			array(
				'title'  => __( 'Other Settings', 'gravity-forms-google-analytics-event-tracking' ),
				'fields' => array(
					array(
						'name'    => 'conditionalLogic',
						'label'   => __( 'Conditional Logic', 'gravity-forms-google-analytics-event-tracking' ),
						'type'    => 'feed_condition',
						'tooltip' => '<h6>' . __( 'Conditional Logic', 'gravity-forms-google-analytics-event-tracking' ) . '</h6>' . __( 'When conditions are enabled, events will only be sent to google when the conditions are met. When disabled, all form submissions will trigger an event.', 'gravity-forms-google-analytics-event-tracking' ),
					),
				),
			),
		);
	}

	public function settings_gforms_beta_cta() {
		ob_start();
		?>
		
		<div class="alert info">
		<div style="padding-top: 25px; padding-bottom: 25px"><a href="https://www.gravityforms.com/add-ons/google-analytics/" target="_blank"><img src="<?php echo esc_url( GFGAET::get_plugin_url( '/img/gravity-forms-ga-addon-horizontal.png' ) ); ?>" width="800" height="214" /></a></div>
			<h3 style="font-size: 18px; line-height: 1.2; font-weight: 400">The team behind Gravity Forms has developed and released an official Google Analytics Add-on.</h3>
			<p><a class="button primary" href="https://www.gravityforms.com/add-ons/google-analytics/" target="_blank">Check out the new Google Analytics Add-On</a>
		</div>
		<?php
		echo wp_kses_post( ob_get_clean() );
	}

	/**
	 * Instruction field
	 *
	 * @since 1.5.0
	 */
	public function single_setting_row_instruction_field() {
		echo '
			<tr>
				<th colspan="2">
					<p>' . __( 'If you leave these blank, the following defaults will be used when the event is tracked', 'gravity-forms-google-analytics-event-tracking' ) . ':</p>
					<p>
						<strong>' . __( 'Event Category', 'gravity-forms-google-analytics-event-tracking' ) . ':</strong> Forms<br>
						<strong>' . __( 'Event Action', 'gravity-forms-google-analytics-event-tracking' ) . ':</strong> Submission<br>
						<strong>' . __( 'Event Label', 'gravity-forms-google-analytics-event-tracking' ) . ':</strong> Form: {form_title} ID: {form_id}<br>
						<strong>' . __( 'Event Value', 'gravity-forms-google-analytics-event-tracking' ) . ':</strong> Payment Amount (on payment forms only, otherwise nothing is sent by default)
					</p>
				</td>
			</tr>';
	}

	/**
	 * Return the feed list columns
	 *
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

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 2.4.1
	 *
	 * @return string
	 */
	public function get_menu_icon() {

		return '<svg width="22" height="20" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="analytics" class="svg-inline--fa fa-analytics fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M510.62 92.63C516.03 94.74 521.85 96 528 96c26.51 0 48-21.49 48-48S554.51 0 528 0s-48 21.49-48 48c0 2.43.37 4.76.71 7.09l-95.34 76.27c-5.4-2.11-11.23-3.37-17.38-3.37s-11.97 1.26-17.38 3.37L255.29 55.1c.35-2.33.71-4.67.71-7.1 0-26.51-21.49-48-48-48s-48 21.49-48 48c0 4.27.74 8.34 1.78 12.28l-101.5 101.5C56.34 160.74 52.27 160 48 160c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-4.27-.74-8.34-1.78-12.28l101.5-101.5C199.66 95.26 203.73 96 208 96c6.15 0 11.97-1.26 17.38-3.37l95.34 76.27c-.35 2.33-.71 4.67-.71 7.1 0 26.51 21.49 48 48 48s48-21.49 48-48c0-2.43-.37-4.76-.71-7.09l95.32-76.28zM400 320h-64c-8.84 0-16 7.16-16 16v160c0 8.84 7.16 16 16 16h64c8.84 0 16-7.16 16-16V336c0-8.84-7.16-16-16-16zm160-128h-64c-8.84 0-16 7.16-16 16v288c0 8.84 7.16 16 16 16h64c8.84 0 16-7.16 16-16V208c0-8.84-7.16-16-16-16zm-320 0h-64c-8.84 0-16 7.16-16 16v288c0 8.84 7.16 16 16 16h64c8.84 0 16-7.16 16-16V208c0-8.84-7.16-16-16-16zM80 352H16c-8.84 0-16 7.16-16 16v128c0 8.84 7.16 16 16 16h64c8.84 0 16-7.16 16-16V368c0-8.84-7.16-16-16-16z"></path></svg>';
	}

}
