<?php
/**
 * Gravity forms event tracking
 *
 * @package   Gravity_Forms_Event_Tracking_Admin
 * @author    Nathan Marks <nmarks@nvisionsolutions.ca>
 * @license   GPL-2.0+
 * @link      http://www.nvisionsolutions.ca
 * @copyright 2014 Nathan Marks
 */

/**
 *
 * @package Gravity_Forms_Event_Tracking_Addon
 * @author  Ronald Huereca <ronalfy@gmail.com>
 */
 if ( class_exists( "GFForms" ) ) {
 	GFForms::include_addon_framework();

	class Gravity_Forms_Event_Tracking_Addon extends GFAddOn {
		protected $_version = "1.0";
        protected $_min_gravityforms_version = "1.7.9999";
        protected $_slug = "gravity-forms-google-analytics-event-tracking";
        protected $_path = "gravity-forms-google-analytics-event-tracking/gravity-forms-event-tracking.php";
        protected $_full_path = __FILE__;
        protected $_url = "https://github.com/nathanmarks/wordpress-gravity-forms-event-tracking";
        protected $_title = "Gravity Forms Event Tracking";
        protected $_short_title = "Event Tracking";
		
		// ------- Plugin settings -------

		public function plugin_settings_fields() {
			return array(
				array(
					'title'       => __( 'Google Analytics', 'gravity-forms-google-analytics-event-tracking' ),
					'description' => __( 'Enter the UA code (UA-XXXX-Y) here. Make sure to setup your goal properly!', 'gravity-forms-google-analytics-event-tracking' ),
					'fields'      => array(
						array(
							'name'              => 'gravity_forms_event_tracking_ua',
							'label'             => __( 'UA Tracking ID', 'gravity-forms-google-analytics-event-tracking' ),
							'type'              => 'text',
							'class'             => 'medium',
							'tooltip' => 'UA-XXXX-Y',
							'feedback_callback' => array( $this, 'ua_validation' )
						),
					)
				),
			);
		}
		/**
		 * Basic Validation
		 */
		public function ua_validation($input ) {
			$input = strip_tags( stripslashes( $input ) );
			$ua_regex = "/^UA-[0-9]{5,}-[0-9]{1,}$/";
			if (preg_match($ua_regex, $input)) {
				return true;
			} else {
				$this->log_error( __( 'Invalid UA ID', 'gravity-forms-google-analytics-event-tracking' ) );
			    return false;
			}
		}
	}
	new Gravity_Forms_Event_Tracking_Addon();
}
