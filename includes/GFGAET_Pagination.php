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
		require_once( 'vendor/ga-mp/src/Racecore/GATracking/Autoloader.php');
		Racecore\GATracking\Autoloader::register( dirname(__FILE__) . '/vendor/ga-mp/src/' );
		
		$ua_code = GFGAET::get_ua_code();
		if ( false !== $ua_code ) {
			$event = new \Racecore\GATracking\Tracking\Event();
		
			$event_category = apply_filters( 'gform_pagination_event_category', 'form', $form, $source_page_number, $current_page_number );
			$event_action = apply_filters( 'gform_pagination_event_action', 'pagination', $form, $source_page_number, $current_page_number );
			
			$event_label = sprintf( '%s::%s::%d', esc_html( $form['title'] ), absint( $source_page_number ), absint( $current_page_number ) );
			$event_label = apply_filters( 'gform_pagination_event_label', $event_label, $form, $source_page_number, $current_page_number );
			
			// Set the event meta
			$event->setEventCategory( $event_category );
			$event->setEventAction( $event_action );
			$event->setEventLabel( $event_label );
			
			// Submit the event
			$tracking = new \Racecore\GATracking\GATracking( $ua_code );
			try {
				$tracking->sendTracking( $event );
			} catch (Exception $e) {
				error_log( $e->getMessage() . ' in ' . get_class( $e ) );
			}
		}
		
	}
}