<?php
GFForms::include_addon_framework();

class GFGAET_UA extends GFAddOn {
	protected $_version = '2.0'; 
	protected $_min_gravityforms_version = '1.8.20';
	protected $_slug = 'GFGAET_UA';
	protected $_path = 'gravity-forms-google-analytics-event-tracking/gravity-forms-event-tracking.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms Google Analytics Event Tracking';
	protected $_short_title = 'Event Tracking';
	
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
	
	/**
	 * Plugin settings fields
	 * 
	 * @return array Array of plugin settings
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'       => __( 'Google Analytics', 'gravity-forms-google-analytics-event-tracking' ),
				'fields'      => array(
					array(
						'name'              => 'gravity_forms_event_tracking_ua',
						'tooltip' 			=> __( 'Enter your UA code (UA-XXXX-Y) Find it <a href="https://support.google.com/analytics/answer/1032385" target="_blank">using this guide</a>.', 'gravity-forms-google-analytics-event-tracking' ),
						'label'             => __( 'UA Tracking ID', 'gravity-forms-google-analytics-event-tracking' ),
						'type'              => 'text',
						'class'             => 'small',
						
					),
				)
			),
		);
	}
}