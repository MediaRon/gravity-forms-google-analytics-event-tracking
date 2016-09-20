<?php
class GFGAET_Tag_Manager {
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

	public function send() {
		
	}
}