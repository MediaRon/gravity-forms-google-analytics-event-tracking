<?php
GFForms::include_addon_framework();
class GFGAET_UA extends GFAddOn {
	protected $_version                  = '2.4.0';
	protected $_min_gravityforms_version = '1.8.20';
	protected $_slug                     = 'GFGAET_UA';
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

	private static $_instance = null;

	/**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @return object $_instance An instance of this class.
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function init() {
		parent::init();

		// Migrate old GA Code over to new add-on
		$ga_options = get_option( 'gravityformsaddon_GFGAET_UA_settings', false );
		if ( ! $ga_options ) {
			$old_ga_option = get_option( 'gravityformsaddon_gravity-forms-event-tracking_settings', false );
			if ( $old_ga_option ) {
				update_option( 'gravityformsaddon_GFGAET_UA_settings', $old_ga_option );
			}
		}

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
	 * Settings icon for Form settings.
	 *
	 * @since 2.4.0
	 */
	public function get_menu_icon() {
		return '<svg width="22" height="20" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="analytics" class="svg-inline--fa fa-analytics fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M510.62 92.63C516.03 94.74 521.85 96 528 96c26.51 0 48-21.49 48-48S554.51 0 528 0s-48 21.49-48 48c0 2.43.37 4.76.71 7.09l-95.34 76.27c-5.4-2.11-11.23-3.37-17.38-3.37s-11.97 1.26-17.38 3.37L255.29 55.1c.35-2.33.71-4.67.71-7.1 0-26.51-21.49-48-48-48s-48 21.49-48 48c0 4.27.74 8.34 1.78 12.28l-101.5 101.5C56.34 160.74 52.27 160 48 160c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-4.27-.74-8.34-1.78-12.28l101.5-101.5C199.66 95.26 203.73 96 208 96c6.15 0 11.97-1.26 17.38-3.37l95.34 76.27c-.35 2.33-.71 4.67-.71 7.1 0 26.51 21.49 48 48 48s48-21.49 48-48c0-2.43-.37-4.76-.71-7.09l95.32-76.28zM400 320h-64c-8.84 0-16 7.16-16 16v160c0 8.84 7.16 16 16 16h64c8.84 0 16-7.16 16-16V336c0-8.84-7.16-16-16-16zm160-128h-64c-8.84 0-16 7.16-16 16v288c0 8.84 7.16 16 16 16h64c8.84 0 16-7.16 16-16V208c0-8.84-7.16-16-16-16zm-320 0h-64c-8.84 0-16 7.16-16 16v288c0 8.84 7.16 16 16 16h64c8.84 0 16-7.16 16-16V208c0-8.84-7.16-16-16-16zM80 352H16c-8.84 0-16 7.16-16 16v128c0 8.84 7.16 16 16 16h64c8.84 0 16-7.16 16-16V368c0-8.84-7.16-16-16-16z"></path></svg>';
	}

	/**
	 * Plugin settings fields
	 *
	 * @return array Array of plugin settings
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'       => __( 'Google Analytics and Google Tag Manager', 'gravity-forms-google-analytics-event-tracking' ),
				'description' => '<p>' . __( 'By default, events are sent using the measurement protocol. You can change to using pure Google Analytics and Google Tag Manager if your forms are Ajax only.', 'gravity-forms-google-analytics-event-tracking' ) . '</p><p>' . __( 'Do you need help? <a target="_blank" href="https://mediaron.com/event-tracking-for-gravity-forms/?utm_source=wordpress_admin&utm_medium=documentation&utm_campaign=event_tracking">Please see the documentation</a>.</p>', 'gravity-forms-google-analytics-event-tracking' ),
				'fields'      => array(
					array(
						'name'       => 'gravityforms_ga',
						'type'       => 'gforms_beta_cta',
						'dependency' => array(
							'field' => 'beta_notification',
							'values' => array(
								'on',
							),
						),
					),
					array(
						'type'          => 'radio',
						'name'          => 'mode',
						'horizontal'    => true,
						'default_value' => 'gmp',
						'label'         => 'How would you like to send events?',
						'choices'       => array(
							array(
								'name'  => 'gmp_on',
								'label' => esc_html__( 'Measurement Protocol (Default)', 'gravity-forms-google-analytics-event-tracking' ),
								'value' => 'gmp',
								'icon'  => GFGAET::get_plugin_url( '/img/google-brands.png' ),
								'tooltip' => esc_html__( 'This option will send analytics server-to-server using the measurement protocol', 'gravity-forms-google-analytics-event-tracking' ),
							),
							array(
								'name'  => 'ga_on',
								'label' => esc_html__( 'Google Analytics (Ajax only forms)', 'gravity-forms-google-analytics-event-tracking' ),
								'value' => 'ga',
								'icon'  => GFGAET::get_plugin_url( '/img/analytics.png' ),
								'tooltip' => esc_html__( 'Send form data via JavaScript using an existing Google Analytics account.', 'gravity-forms-google-analytics-event-tracking' ),
							),
							array(
								'name'  => 'gtm_on',
								'label' => esc_html__( 'Google Tag Manager (Ajax only forms)', 'gravity-forms-google-analytics-event-tracking' ),
								'value' => 'gtm',
								'icon'  => GFGAET::get_plugin_url( '/img/gtm.png' ),
								'tooltip' => esc_html__( 'Send form data via JavaScript using an existing Google Tag Manager account.', 'gravity-forms-google-analytics-event-tracking' ),
							),
						),
					),
					array(
						'name'       => 'gravity_forms_event_tracking_ua',
						'type'       => 'hidden',
						'dependency' => array(
							'field'  => 'mode',
							'values' => array( 'gmp', 'ga', 'gtm' ),
						),
					),
					array(
						'name'       => 'gravity_forms_event_tracking_ua',
						'tooltip'    => __( 'Enter your UA code (UA-XXXX-Y) Find it <a href="https://support.google.com/analytics/answer/1032385" target="_blank">using this guide</a>.', 'gravity-forms-google-analytics-event-tracking' ),
						'label'      => __( 'UA Tracking ID', 'gravity-forms-google-analytics-event-tracking' ),
						'type'       => 'text',
						'class'      => 'small',
						'dependency' => array(
							'field'  => 'mode',
							'values' => array( 'ga', 'gmp' ),
						),

					),
					array(
						'name'       => 'gravity_forms_event_tracking_ua_tracker',
						'type'       => 'hidden',
						'dependency' => array(
							'field'  => 'mode',
							'values' => array( 'gmp', 'ga', 'gtm' ),
						),
					),
					array(
						'name'       => 'gravity_forms_event_tracking_ua_tracker',
						'tooltip'    => __( 'Enter your Tracker you would like to send events from if you are using a custom Tracker (Optional)', 'gravity-forms-google-analytics-event-tracking' ),
						'label'      => __( 'UA Tracker Name (optional)', 'gravity-forms-google-analytics-event-tracking' ),
						'type'       => 'text',
						'class'      => 'small',
						'dependency' => array(
							'field'  => 'mode',
							'values' => array( 'ga' ),
						),
					),
					array(
						'name'       => 'gravity_forms_event_tracking_ua_interaction_hit',
						'type'       => 'hidden',
						'dependency' => array(
							'field'  => 'mode',
							'values' => array( 'ga', 'gtm', 'gmp' ),
						),
					),
					array(
						'name'          => 'gravity_forms_event_tracking_ua_interaction_hit',
						'tooltip'       => __( 'Enter whether the hits are interactive or not. <a href="https://support.google.com/analytics/answer/6086082?hl=en" target="_blank">Find out more</a>.', 'gravity-forms-google-analytics-event-tracking' ),
						'label'         => __( 'Non-interactive hits', 'gravity-forms-google-analytics-event-tracking' ),
						'type'          => 'radio',
						'default_value' => 'interactive_on',
						'choices'       => array(
							array(
								'name'    => 'interactive_on',
								'tooltip' => esc_html__( 'Turn on interaction hits such as event tracking hits.', 'gravity-forms-google-analytics-event-tracking' ),
								'label'   => esc_html__( 'Turn on Interactive Hits', 'gravity-forms-google-analytics-event-tracking' ),
								'value'   => 'interactive_on',
							),
							array(
								'name'    => 'interactive_off',
								'tooltip' => esc_html__( 'Turn off interaction hits such as event tracking hits.', 'gravity-forms-google-analytics-event-tracking' ),
								'label'   => esc_html__( 'Turn off Interactive Hits', 'gravity-forms-google-analytics-event-tracking' ),
								'value'   => 'interactive_off',
							),
						),
						'dependency'    => array(
							'field'  => 'mode',
							'values' => array( 'ga' ),
						),

					),
					array(
						'name'       => 'gravity_forms_event_tracking_ua_gtag_install',
						'type'       => 'hidden',
						'dependency' => array(
							'field'  => 'mode',
							'values' => array( 'ga', 'gtm', 'gmp' ),
						),
					),
					array(
						'name'          => 'gravity_forms_event_tracking_ua_gtag_install',
						'tooltip'       => __( 'Select "Install gtag" if you would like this add-on to install gtag analytics. <a href="https://developers.google.com/analytics/devguides/collection/gtagjs" target="_blank">Find out More</a>.', 'gravity-forms-google-analytics-event-tracking' ),
						'label'         => __( 'Install GTAG Universal Analytics', 'gravity-forms-google-analytics-event-tracking' ),
						'type'          => 'radio',
						'default_value' => 'gtag_off',
						'choices'       => array(
							array(
								'name'    => 'gtag_off',
								'tooltip' => esc_html__( 'You are using a different tool to add analytics.', 'gravity-forms-google-analytics-event-tracking' ),
								'label'   => esc_html__( 'Do not install gtag Universal Analytics.', 'gravity-forms-google-analytics-event-tracking' ),
								'value'   => 'gtag_off',
							),
							array(
								'name'    => 'gtag_on',
								'tooltip' => esc_html__( 'This add-on will install Google Analytics tracking for you using gtag.', 'gravity-forms-google-analytics-event-tracking' ),
								'label'   => esc_html__( 'Install gtag Universal Analytics', 'gravity-forms-google-analytics-event-tracking' ),
								'value'   => 'gtag_on',
							),
						),
						'dependency'    => array(
							'field'  => 'mode',
							'values' => array( 'ga' ),
						),
					),
					array(
						'name'       => 'gravity_forms_event_tracking_gtm_utm_vars',
						'type'       => 'hidden',
						'dependency' => array(
							'field'  => 'mode',
							'values' => array( 'ga', 'gtm', 'gmp' ),
						),
					),
					array(
						'name'          => 'gravity_forms_event_tracking_gtm_utm_vars',
						'tooltip'       => __( 'Install a script that will monitor UTM variables and pass these along to Tag Manager when a form is submitted. <a href="https://support.google.com/analytics/answer/1033863?hl=en" target="_blank">Find out more</a>.', 'gravity-forms-google-analytics-event-tracking' ),
						'label'         => __( 'Track UTM variables to send to Tag Manager', 'gravity-forms-google-analytics-event-tracking' ),
						'type'          => 'radio',
						'default_value' => 'utm_off',
						'choices'       => array(
							array(
								'name'    => 'utm_off',
								'tooltip' => esc_html__( 'The script for tracking UTM variables will be off and UTM variables will not be sent to Google Tag Manager.', 'gravity-forms-google-analytics-event-tracking' ),
								'label'   => esc_html__( 'Do not track UTM variables', 'gravity-forms-google-analytics-event-tracking' ),
								'value'   => 'utm_off',
							),
							array(
								'name'    => 'utm_on',
								'tooltip' => esc_html__( 'Track UTM variables across your site and send them to Google Tag Manager upon form submission.', 'gravity-forms-google-analytics-event-tracking' ),
								'label'   => esc_html__( 'Track UTM variables', 'gravity-forms-google-analytics-event-tracking' ),
								'value'   => 'utm_on',
							),
						),
						'dependency'    => array(
							'field'  => 'mode',
							'values' => array( 'gtm' ),
						),
					),
					array(
						'name'       => 'gravity_forms_event_tracking_gtm_install',
						'type'       => 'hidden',
						'dependency' => array(
							'field'  => 'mode',
							'values' => array( 'gtm', 'gmp', 'ga' ),
						),
					),
					array(
						'name'          => 'gravity_forms_event_tracking_gtm_install',
						'tooltip'       => __( 'Install Tag Manager for supported themes. If you already have Tag Manager installed, you can leave this option disabled. <a href="https://support.google.com/tagmanager/answer/6103696" target="_blank">Find out more</a>.', 'gravity-forms-google-analytics-event-tracking' ),
						'label'         => __( 'Install Tag Manager', 'gravity-forms-google-analytics-event-tracking' ),
						'type'          => 'radio',
						'default_value' => 'gtm_install_off',
						'choices'       => array(
							array(
								'name'    => 'gtm_install_off',
								'tooltip' => esc_html__( 'You already have Tag Manager installed.', 'gravity-forms-google-analytics-event-tracking' ),
								'label'   => esc_html__( 'Do not install Tag Manager', 'gravity-forms-google-analytics-event-tracking' ),
								'value'   => 'gtm_install_off',
							),
							array(
								'name'    => 'gtm_install_on',
								'tooltip' => esc_html__( 'Install Tag Manager for supported themes', 'gravity-forms-google-analytics-event-tracking' ),
								'label'   => esc_html__( 'Install Tag Manager', 'gravity-forms-google-analytics-event-tracking' ),
								'value'   => 'gtm_install_on',
							),
						),
						'dependency'    => array(
							'field'  => 'mode',
							'values' => array( 'gtm' ),
						),
					),
					array(
						'name'       => 'gravity_forms_event_tracking_gtm_account_id',
						'type'       => 'hidden',
						'dependency' => array(
							'field'  => 'gravity_forms_event_tracking_gtm_install',
							'values' => array( 'gtm_install_on' ),
						),
					),
					array(
						'name'       => 'gravity_forms_event_tracking_gtm_account_id',
						'tooltip'    => __( 'Enter your GTM account ID which can be found in your workspace settings in Tag Manager', 'gravity-forms-google-analytics-event-tracking' ),
						'label'      => __( 'Tag Manager Account ID', 'gravity-forms-google-analytics-event-tracking' ),
						'type'       => 'text',
						'class'      => 'small',
						'dependency' => array(
							'operator' => 'ALL', // Defaults to ALL.
							'fields'   => array(
								array(
									'field'  => 'gravity_forms_event_tracking_gtm_install',
									'values' => array( 'gtm_install_on' ),
								),
								array(
									'field'  => 'mode',
									'values' => array( 'gtm' ),
								),
							),
						),
					),
				),
			),
			array(
				'title'  => __( 'Matomo Open Analytics Platform', 'gravity-forms-google-analytics-event-tracking' ),
				'fields' => array(
					array(
						'name'    => 'gravity_forms_event_tracking_matomo_url',
						'tooltip' => __( 'Enter your Matomo (formerly Piwik) URL. This is the same URL you use to access your Matomo instance (ex. http://www.example.com/matomo/.)', 'gravity-forms-google-analytics-event-tracking' ),
						'label'   => __( 'Matomo URL', 'gravity-forms-google-analytics-event-tracking' ),
						'type'    => 'text',
						'class'   => 'small',

					),
					array(
						'name'    => 'gravity_forms_event_tracking_matomo_siteid',
						'tooltip' => __( 'Enter your Site ID (ex. 2 or J2O1NDvxzmMB if using the Protect Track ID plugin.)', 'gravity-forms-google-analytics-event-tracking' ),
						'label'   => __( 'Site ID', 'gravity-forms-google-analytics-event-tracking' ),
						'type'    => 'text',
						'class'   => 'small',

					),
					array(
						'type'          => 'radio',
						'name'          => 'matomo_mode',
						'horizontal'    => false,
						'default_value' => 'matomo_http',
						'label'         => 'How would you like to send <strong>Matomo</strong> events?',
						'choices'       => array(
							array(
								'name'    => 'matomo_js_on',
								'tooltip' => esc_html__( 'Forms must be Ajax only. Events will be sent using the <a target="_blank" href="https://matomo.org/docs/event-tracking/#javascript-trackevent">`trackEvent` JavaScript function</a>.', 'gravity-forms-google-analytics-event-tracking' ),
								'label'   => esc_html__( 'JavaScript `trackEvent` Function (Ajax only)', 'gravity-forms-google-analytics-event-tracking' ),
								'value'   => 'matomo_js',
							),
							array(
								'name'    => 'matomo_http_on',
								'tooltip' => esc_html__( 'Events will be sent using the <a target="_blank" href="https://developer.matomo.org/api-reference/tracking-api">Tracking HTTP API</a>.', 'gravity-forms-google-analytics-event-tracking' ),
								'label'   => esc_html__( 'Tracking HTTP API (Default)', 'gravity-forms-google-analytics-event-tracking' ),
								'value'   => 'matomo_http',
							),
						),
					),
				),
			),
			array(
				'title'       => __( 'Advanced', 'gravity-forms-google-analytics-event-tracking' ),
				'description' => __( 'This will make all your forms Ajax only for options that require it.', 'gravity-forms-google-analytics-event-tracking' ),
				'fields'      => array(
					array(
						'type'          => 'radio',
						'name'          => 'ajax_only',
						'horizontal'    => false,
						'default_value' => 'off',
						'label'         => 'Make all forms Ajax only?',
						'choices'       => array(
							array(
								'name'    => 'ajax_on',
								'label'   => esc_html__( 'Ajax only', 'gravity-forms-google-analytics-event-tracking' ),
								'value'   => 'on',
								'tooltip' => esc_html__( 'For Google Analytics and Tag Manager mode, forms need to be Ajax only. Choosing this option will make all forms Ajax only.', 'gravity-forms-google-analytics-event-tracking' ),
							),
							array(
								'name'    => 'ajax_off',
								'label'   => esc_html__( 'Default', 'gravity-forms-google-analytics-event-tracking' ),
								'value'   => 'off',
								'tooltip' => esc_html__( 'For Google Analytics and Tag Manager mode, forms must be Ajax only. Choose this option if the forms you need Event Tracking on are already using Ajax.', 'gravity-forms-google-analytics-event-tracking' ),
							),
						),
					),
					array(
						'type'          => 'radio',
						'name'          => 'beta_notification',
						'horizontal'    => false,
						'default_value' => 'on',
						'label'         => 'Google Analytics Add-on Banner',
						'choices'       => array(
							array(
								'name'    => 'beta_notifications_on',
								'label'   => esc_html__( 'Turn On', 'gravity-forms-google-analytics-event-tracking' ),
								'value'   => 'on',
								'tooltip' => 'If you would like to check out the official Google Analytics Add-on, <a href="https://www.gravityforms.com/add-ons/google-analytics/" target="blank">please click here</a>.',
							),
							array(
								'name'    => 'beta_notifications_off',
								'label'   => esc_html__( 'Turn Off', 'gravity-forms-google-analytics-event-tracking' ),
								'value'   => 'off',
								'tooltip' => esc_html__( 'Turn off the Google Analytics Add-on banner.', 'gravity-forms-google-analytics-event-tracking' ),
							),
						),
					),
				),
			),

		);
	}
}
