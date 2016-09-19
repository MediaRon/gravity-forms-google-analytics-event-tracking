<?php
class GFGAET_Events {
	public function __construct() {
		require_once( 'vendor/ga-mp/src/Racecore/GATracking/Autoloader.php');
		Racecore\GATracking\Autoloader::register( dirname(__FILE__) . '/vendor/ga-mp/src/' );
	}
	public function send_event( $ua, $action, $category, $label, $value ) {
		
	}
}