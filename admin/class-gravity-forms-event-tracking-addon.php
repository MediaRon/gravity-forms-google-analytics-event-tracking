<?php
/**
 * Gravity forms event tracking
 *
 * @package   Gravity_Forms_Event_Tracking_Addon
 * @author    Nathan Marks <nmarks@nvisionsolutions.ca>
 * @author    Ronald Huereca <ronalfy@gmail.com>
 * @license   GPL-2.0+
 * @link      http://www.nvisionsolutions.ca
 * @copyright 2014 Nathan Marks
 */

 if ( class_exists( "GFForms" ) ) {

 	GFForms::include_addon_framework();

	class Gravity_Forms_Event_Tracking_Addon extends GFAddOn {
		protected $_version = "1.5.0";
        protected $_min_gravityforms_version = "1.7.9";

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

        /**
         * Overriding init function to change the load_plugin_textdomain call.
         * See comment above for explanation.
         */
		public function init() {

			load_plugin_textdomain( $this->_slug, false, $this->_text_domain . '/languages' );

			add_filter( 'gform_logging_supported', array( $this, 'set_logging_supported' ) );

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
         * @return array Array of form settings
         */
		public function form_settings_fields() {
			return array(
                array(
                    "title"  => __( 'Basic Settings', $this->_text_domain ),
                    "fields" => array(
                        array(
                            "label"   => __( 'Disable Event Tracking', $this->_text_domain ),
                            "type"    => "checkbox",
                            "name"    => "ga_event_tracking_disabled",
                            "tooltip" => sprintf( '<h6>%s</h6>%s', __( 'Disable Event Tracking', $this->_text_domain ), __( 'Check this if you don\'t want this form to send any events to Google Analytics.', $this->_text_domain ) ),
                            "choices" => array(
                                array(
                                    "label" => "Disabled",
                                    "name"  => "disabled"
                                )
                            )
                        )
                    )
                ),
                array(
                    "title"  => __( 'Event Tracking Settings', $this->_text_domain ),
                    "fields" => array(
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
                            "tooltip" => sprintf( '<h6>%s</h6>%s', __( 'Event Value', $this->_text_domain ), __( 'Enter your Google Analytics event value. Leave blank to omit pushing a value to Google Analytics. Or to use the purchase value of a payment based form.', $this->_text_domain ) ),
                        ),
                    )
                )
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
				$this->log_error( __( 'Invalid UA ID', $this->_text_domain ) );
			    return false;
			}
		}

	}
	
	new Gravity_Forms_Event_Tracking_Addon();
}
