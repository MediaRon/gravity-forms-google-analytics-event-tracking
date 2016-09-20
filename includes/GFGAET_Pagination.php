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
	}
}