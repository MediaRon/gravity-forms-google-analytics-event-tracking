<?php
class GFGAET_Pagination {
	/**
	 * Holds the class instance.
	 *
	 * @since 2.0.0
	 * @access private
	 */
	private static $instance = null;

	/**
	 * Retrieve a class instance.
	 *
	 * @since 2.0.0
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	} //end get_instance

	/**
	 * Class constructor.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {

	}

	/**
	 * Send pagination events.
	 *
	 * @since 2.0.0
	 *
	 * @param array $form                The form arguments
	 * @param int   @source_page_number  The original page number
	 * @param int   $current_page_number The new page number
	 */
	public function paginate( $form, $source_page_number, $current_page_number ) {

		$ua_code = GFGAET::get_ua_code();
		if ( false !== $ua_code ) {
			$event = new GFGAET_Measurement_Protocol();
			$event->init();

			/**
			 * Filter: gform_pagination_event_category
			 *
			 * Filter the event category dynamically
			 *
			 * @since 2.0.0
			 *
			 * @param string $category              Event Category
			 * @param array  $form                  Gravity Form form array
			 * @param int    $source_page_number    Source page number
			 * @param int    $current_page_number   Current Page Number
			 */
			$event_category = 'form';
			if ( isset( $form['pagination_category'] ) ) {
				$pagination_category = trim( $form['pagination_category'] );
				if( ! empty( $pagination_category ) ) {
					$event_category = $pagination_category;
				}
			}
			$event_category = apply_filters( 'gform_pagination_event_category', $event_category, $form, $source_page_number, $current_page_number );

			/**
			 * Filter: gform_pagination_event_action
			 *
			 * Filter the event action dynamically
			 *
			 * @since 2.0.0
			 *
			 * @param string $action                Event Action
			 * @param array  $form                  Gravity Form form array
			 * @param int    $source_page_number    Source page number
			 * @param int    $current_page_number   Current Page Number
			 */
			$event_action = 'pagination';
			if ( isset( $form['pagination_action'] ) ) {
				$pagination_action = trim( $form['pagination_action'] );
				if( ! empty( $pagination_action ) ) {
					$event_action = $pagination_action;
				}
			}
			$event_action = apply_filters( 'gform_pagination_event_action', $event_action, $form, $source_page_number, $current_page_number );

			/**
			 * Filter: gform_pagination_event_label
			 *
			 * Filter the event label dynamically
			 *
			 * @since 2.0.0
			 *
			 * @param string $label                 Event Label
			 * @param array  $form                  Gravity Form form array
			 * @param int    $source_page_number    Source page number
			 * @param int    $current_page_number   Current Page Number
			 */
			$event_label = sprintf( '%s::%d::%d', esc_html( $form['title'] ), absint( $source_page_number ), absint( $current_page_number ) );
			if ( isset( $form['pagination_label'] ) ) {
				$pagination_label = trim( $form['pagination_label'] );
				if( ! empty( $pagination_label ) ) {
					$pagination_label = str_replace( '{form_title}', esc_html( $form['title'] ), $pagination_label );
					$pagination_label = str_replace( '{source_page_number}', absint( $source_page_number ), $pagination_label );
					$pagination_label = str_replace( '{current_page_number}', absint( $current_page_number ), $pagination_label );
					$event_label = $pagination_label;
				}
			}
			$event_label = apply_filters( 'gform_pagination_event_label', $event_label, $form, $source_page_number, $current_page_number );

			/**
			 * Filter: gform_pagination_event_value
			 *
			 * Filter the event value dynamically
			 *
			 * @since 2.2.0
			 *
			 * @param int    $event_value           Event Value
			 * @param array  $form                  Gravity Form form array
			 * @param int    $source_page_number    Source page number
			 * @param int    $current_page_number   Current Page Number
			 */
			$event_value = 0;
			if ( isset( $form['pagination_value'] ) ) {
				$pagination_value = trim( $form['pagination_value'] );
				if( ! empty( $pagination_value ) ) {
					$event_value = $pagination_value;
				}
			}
			// Value is rounded up (Google likes integers only) before given an absolute value
			$event_value = absint( round( GFCommon::to_number( apply_filters( 'gform_pagination_event_value', $event_value, $form, $source_page_number, $current_page_number  ) ) ) );

			// Set environmental variables for the measurement protocol
			$event->set_event_category( $event_category );
			$event->set_event_action( $event_action );
			$event->set_event_label( $event_label );
			if ( 0 !== $event_value ) {
				$event->set_event_value( $event_value );
			}

			if ( GFGAET::is_ga_only() ) {
				?>
				<script>
				if ( typeof window.parent.ga == 'undefined' ) {
					if ( typeof window.parent.__gaTracker != 'undefined' ) {
						window.parent.ga = window.parent.__gaTracker;
					}
				}
				if ( typeof window.parent.ga != 'undefined' ) {

					// Try to get original UA code from third-party plugins or tag manager
					var default_ua_code = null;
					window.parent.ga(function(tracker) {
						default_ua_code = tracker.get('trackingId');
					});

					// If UA code matches, use that tracker
					if ( default_ua_code == '<?php echo esc_js( $ua_code ); ?>' ) {
						window.parent.ga( 'send', 'event', '<?php echo esc_js( $event_category ); ?>', '<?php echo esc_js( $event_action ); ?>', '<?php echo esc_js( $event_label ); ?>' );
					} else {
						// UA code doesn't match, use another tracker
						window.parent.ga( 'create', '<?php echo esc_js( $ua_code ); ?>', 'auto', 'GTGAET_Tracker' );
						window.parent.ga( 'GTGAET_Tracker.send', 'event', '<?php echo esc_js( $event_category );?>', '<?php echo esc_js( $event_action ); ?>', '<?php echo esc_js( $event_label ); ?>'<?php if ( 0 !== $event_value ) { echo ',' . "'" . esc_js( $event_value ) . "'"; } ?>);
					}
				}
				</script>
				<?php
				return;
			} else if ( GFGAET::is_gtm_only() ) {
				?>
				<script>
				if ( typeof( window.parent.dataLayer ) != 'undefined' ) {
			    	window.parent.dataLayer.push({'event': 'GFTrackEvent',
						'GFTrackCategory':'<?php echo esc_js( $event_category ); ?>',
						'GFTrackAction':'<?php echo esc_js( $event_action ); ?>',
						'GFTrackLabel':'<?php echo esc_js( $event_label ); ?>',
						'GFTrackValue': <?php echo absint( $event_value ); ?>
						});
				}
				</script>
				<?php
				return;
			}

			// Submit the event
			$event->send( $ua_code );
		}

	}

	/**
	 * Send pagination events.
	 *
	 * @since 2.0.0
	 *
	 * @param array $form                The form arguments
	 * @param int   @source_page_number  The original page number
	 * @param int   $current_page_number The new page number
	 */
	public function matomo_paginate( $form, $source_page_number, $current_page_number ) {

		if ( GFGAET::is_matomo_configured() ) {
			$event = new GFGAET_Matomo_HTTP_API();
			$event->init();

			/**
			 * Filter: gform_pagination_event_category
			 *
			 * Filter the event category dynamically
			 *
			 * @since 2.0.0
			 *
			 * @param string $category              Event Category
			 * @param array  $form                  Gravity Form form array
			 * @param int    $source_page_number    Source page number
			 * @param int    $current_page_number   Current Page Number
			 */
			$event_category = 'form';
			if ( isset( $form['pagination_category'] ) ) {
				$pagination_category = trim( $form['pagination_category'] );
				if( ! empty( $pagination_category ) ) {
					$event_category = $pagination_category;
				}
			}
			$event_category = apply_filters( 'gform_pagination_event_category', $event_category, $form, $source_page_number, $current_page_number );

			/**
			 * Filter: gform_pagination_event_action
			 *
			 * Filter the event action dynamically
			 *
			 * @since 2.0.0
			 *
			 * @param string $action                Event Action
			 * @param array  $form                  Gravity Form form array
			 * @param int    $source_page_number    Source page number
			 * @param int    $current_page_number   Current Page Number
			 */
			$event_action = 'pagination';
			if ( isset( $form['pagination_action'] ) ) {
				$pagination_action = trim( $form['pagination_action'] );
				if( ! empty( $pagination_action ) ) {
					$event_action = $pagination_action;
				}
			}
			$event_action = apply_filters( 'gform_pagination_event_action', $event_action, $form, $source_page_number, $current_page_number );

			/**
			 * Filter: gform_pagination_event_label
			 *
			 * Filter the event label dynamically
			 *
			 * @since 2.0.0
			 *
			 * @param string $label                 Event Label
			 * @param array  $form                  Gravity Form form array
			 * @param int    $source_page_number    Source page number
			 * @param int    $current_page_number   Current Page Number
			 */
			$event_label = sprintf( '%s::%d::%d', esc_html( $form['title'] ), absint( $source_page_number ), absint( $current_page_number ) );
			if ( isset( $form['pagination_label'] ) ) {
				$pagination_label = trim( $form['pagination_label'] );
				if( ! empty( $pagination_label ) ) {
					$pagination_label = str_replace( '{form_title}', esc_html( $form['title'] ), $pagination_label );
					$pagination_label = str_replace( '{source_page_number}', absint( $source_page_number ), $pagination_label );
					$pagination_label = str_replace( '{current_page_number}', absint( $current_page_number ), $pagination_label );
					$event_label = $pagination_label;
				}
			}
			$event_label = apply_filters( 'gform_pagination_event_label', $event_label, $form, $source_page_number, $current_page_number );

			/**
			 * Filter: gform_pagination_event_value
			 *
			 * Filter the event value dynamically
			 *
			 * @since 2.2.0
			 *
			 * @param int    $event_value           Event Value
			 * @param array  $form                  Gravity Form form array
			 * @param int    $source_page_number    Source page number
			 * @param int    $current_page_number   Current Page Number
			 */
			$event_value = 0;
			if ( isset( $form['pagination_value'] ) ) {
				$pagination_value = trim( $form['pagination_value'] );
				if( ! empty( $pagination_value ) ) {
					$event_value = $pagination_value;
				}
			}
			if ( 0 !== $event_value ) {
				$event->set_matomo_event_value( $event_value );
			}

			$event->set_matomo_event_category( $event_category );
			$event->set_matomo_event_action( $event_action );
			$event->set_matomo_event_label( $event_label );

			if ( GFGAET::is_matomo_js_only() ) {
				?>
				<script>
				if ( typeof window.parent._paq != 'undefined' ) {

					window.parent._paq.push(['trackEvent', '<?php echo esc_js( $event_category ); ?>', '<?php echo esc_js( $event_action ); ?>', '<?php echo esc_js( $event_label ); ?>'<?php if ( 0 !== $event_value ) { echo ',' . "'" . esc_js( $event_value ) . "'"; } ?>]);

				}
				</script>
				<?php
				return;
			}

			// Submit the Matomo (formerly Piwik) event
			$event->send_matomo();
		}

	}
}
