<?php
/**
 * Gravity Forms Google Analytics Event Tracking
 *
 * @package GravityForms\GoogleAnalyticsEventTracking
 *
 * @inheritdoc GFFeedAddOn
 * @inheritdoc GFAddOn
 * @inheritdoc \Gravity_Forms\Gravity_Forms_Google_Analytics
 */
GFForms::include_addon_framework();

class GFGAET_UA extends GFAddOn {
	protected $_version                  = '2.4.0';
	protected $_min_gravityforms_version = '1.8.20';
	protected $_slug                     = 'GFGAET_UA';
	protected $_path                     = 'gravity-forms-google-analytics-event-tracking/gravity-forms-event-tracking.php';
	protected $_full_path                = GFGAET_FILE;
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

	public function init_ajax() {
		add_action( 'wp_ajax_gfgaet_install_plugin', array( $this, 'ajax_install_ga_plugin' ) );
		add_action( 'wp_ajax_gfgaet_activate_plugin', array( $this, 'ajax_activate_ga_plugin' ) );
	}

	/**
	 * Installs the official GA Google Analytics plugin.
	 */
	public function ajax_install_ga_plugin() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'gfgaet_ga_install_nonce' ) ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce.', 'gravity-forms-google-analytics-event-tracking' ),
				)
			);
		}
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => __( 'You do not have permission to install plugins.', 'gravity-forms-google-analytics-event-tracking' ),
				)
			);
		}

		$version_info      = GFCommon::get_version_info();
		$version_offerings = rgar( $version_info, 'offerings', false );
		if ( ! $version_offerings ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => __( 'Google Analytics is not available on your current plan.', 'gravity-forms-google-analytics-event-tracking' ),
				)
			);
		}

		$google_analytics_data = rgar( $version_offerings, 'gravityformsgoogleanalytics' );
		if ( ! $google_analytics_data ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => __( 'Google Analytics is not available on your current plan.', 'gravity-forms-google-analytics-event-tracking' ),
				)
			);
		}

		$is_available = (bool) rgar( $google_analytics_data, 'is_available', false );
		if ( ! $is_available ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => __( 'Google Analytics is not available on your current plan.', 'gravity-forms-google-analytics-event-tracking' ),
				)
			);
		}

		$download_url = rgar( $google_analytics_data, 'url', false );
		if ( ! $download_url ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => __( 'Google Analytics download could not be found.', 'gravity-forms-google-analytics-event-tracking' ),
				)
			);
		}

		// Include plugin installation dependencies.
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		// Build title and match variables.
		$title  = 'Installing Gravity Forms Google Analytics Add-On...';
		$url    = \GFCommon::truncate_url( $download_url, 10 );
		$plugin = 'gravityformsgoogleanalytics';

		// Build the installer.
		$upgrader        = new \Plugin_Upgrader( new \Plugin_Installer_Skin( compact( 'title', 'url', 'nonce', 'plugin' ) ) );
		$install_results = $upgrader->install( esc_url_raw( $download_url ) );

		if ( is_wp_error( $install_results ) ) {
			wp_send_json_error(
				array(
					'message'     => __( 'Could not install plugin.', 'wp-ajaxify-comments' ),
					'type'        => 'error',
					'dismissable' => true,
				)
			);
		}

		wp_send_json_success(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Activates the official GA Google Analytics plugin.
	 */
	public function ajax_activate_ga_plugin() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'gfgaet_ga_activate_nonce' ) ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce.', 'gravity-forms-google-analytics-event-tracking' ),
				)
			);
		}
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => __( 'You do not have permission to activate plugins.', 'gravity-forms-google-analytics-event-tracking' ),
				)
			);
		}

		activate_plugin( 'gravityformsgoogleanalytics/googleanalytics.php', '', false, true );

		wp_send_json_success(
			array(
				'success' => true,
			)
		);
	}

	public function scripts() {
		$deps = require $this->get_base_path() . '/dist/gfgaet-install-migrator.asset.php';

		// Check if user can install GA add-on.
		$version_info           = GFCommon::get_version_info();
		$can_install_ga         = false;
		$version_info_offerings = rgar( $version_info, 'offerings', false );
		if ( $version_info_offerings ) {
			$google_analytics_data = rgar( $version_info_offerings, 'gravityformsgoogleanalytics' );
			if ( $google_analytics_data ) {
				$can_install_ga = (bool) rgar( $google_analytics_data, 'is_available', false );
			}
		}

		$is_gtm_installed = false;
		if ( function_exists( 'gf_google_analytics' ) ) {
			$google_analytics = gf_google_analytics();
			$options          = $google_analytics::get_options();
			$is_connected     = ! empty( $options['connected'] ) && $options['connected'] === true;
			$is_gtm_installed = (bool) ( rgar( $options, 'mode', '' ) === 'gtm' );
		}

		$scripts = array(
			array(
				'handle'    => 'gforms_gfgaet_admin_settings',
				'src'       => $this->get_base_url() . '/dist/gfgaet-install-migrator.js',
				'version'   => $deps['version'],
				'deps'      => $deps['dependencies'],
				'enqueue'   => array(
					array(
						'query' => 'page=gf_settings&subview=GFGAET_UA',
					),
				),
				'strings'   => array(
					'home_url'               => esc_url_raw( home_url() ),
					'is_gforms_ga_installed' => $this->is_gforms_ga_installed(),
					'is_gforms_ga_activated' => $this->is_gforms_ga_activated(),
					'get_nonce'              => wp_create_nonce( 'gfgaet_get_plugin_status' ),
					'install_nonce'          => wp_create_nonce( 'gfgaet_ga_install_nonce' ),
					'activate_nonce'         => wp_create_nonce( 'gfgaet_ga_activate_nonce' ),
					'ga_plugin_icon'         => $this->get_base_url() . '/img/gformsga-addon.png',
					'can_install_ga'         => $can_install_ga,
					'is_gtm_installed'       => $is_connected && $is_gtm_installed,
				),
				'in_footer' => true,
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}

	public function styles() {
		return array_merge(
			parent::styles(),
			array(
				array(
					'handle'  => 'gforms_gfgaet_admin_settings',
					'enqueue' => array(
						array(
							'query' => 'page=gf_settings&subview=GFGAET_UA',
						),
					),
					'src'     => $this->get_base_url() . '/dist/gfgaet-css.css',
				),
			)
		);
	}

	/**
	 * Checks to see if a plugin is installed or not.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Path to the asset.
	 *
	 * @return bool true if installed, false if not.
	 */
	public function is_gforms_ga_installed() {

		// Get all plugins for current site.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$all_plugins = get_plugins();

		if ( array_key_exists( 'gravityformsgoogleanalytics/googleanalytics.php', $all_plugins ) ) {
			return true;
		}

		return false;
	}

	public function is_gforms_ga_activated() {
		return is_plugin_active( 'gravityformsgoogleanalytics/googleanalytics.php' );
	}

	public function settings_gforms_beta_cta() {
		ob_start();
		?>
		
		<div class="gfgaet-install-migration-notice" id="gfgaet-install-migration-notice"></div>
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
				'description' => '<p>' . __( 'To support Google Analytics 4, only Google Tag Manager is supported.', 'gravity-forms-google-analytics-event-tracking' ) . '</p>',
				'fields'      => array(
					array(
						'name' => 'gravityforms_ga',
						'type' => 'gforms_beta_cta',
					),
					array(
						'type'          => 'radio',
						'name'          => 'mode',
						'horizontal'    => true,
						'default_value' => 'gtm',
						'label'         => 'How would you like to send events?',
						'choices'       => array(
							array(
								'name'     => 'gmp_on',
								'label'    => esc_html__( 'Measurement Protocol (Deprecated)', 'gravity-forms-google-analytics-event-tracking' ),
								'value'    => 'gmp',
								'icon'     => GFGAET::get_plugin_url( '/img/google-brands.png' ),
								'tooltip'  => esc_html__( 'This option will send analytics server-to-server using the measurement protocol', 'gravity-forms-google-analytics-event-tracking' ),
								'disabled' => true,
							),
							array(
								'name'     => 'ga_on',
								'label'    => esc_html__( 'Google Analytics (Deprecated)', 'gravity-forms-google-analytics-event-tracking' ),
								'value'    => 'ga',
								'icon'     => GFGAET::get_plugin_url( '/img/analytics.png' ),
								'tooltip'  => esc_html__( 'Send form data via JavaScript using an existing Google Analytics account.', 'gravity-forms-google-analytics-event-tracking' ),
								'disabled' => true,
							),
							array(
								'name'    => 'gtm_on',
								'label'   => esc_html__( 'Google Tag Manager (Ajax only forms)', 'gravity-forms-google-analytics-event-tracking' ),
								'value'   => 'gtm',
								'icon'    => GFGAET::get_plugin_url( '/img/gtm.png' ),
								'tooltip' => esc_html__( 'Send form data using GA4 to your Google Tag Manager account.', 'gravity-forms-google-analytics-event-tracking' ),
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
				'title'  => __( 'Matomo Open Analytics Platform (Deprecated)', 'gravity-forms-google-analytics-event-tracking' ),
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
