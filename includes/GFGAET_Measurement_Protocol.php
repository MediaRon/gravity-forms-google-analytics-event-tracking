<?php
class GFGAET_Measurement_Protocol {

	private $endpoint = 'https://www.google-analytics.com/collect'; // Measurement Protocol Endpoint
	private $cid = ''; // Client ID
	private $tid = ''; // Tracking ID (UA-XXXX-YY)
	private $v = 1; // Protocol Version
	private $t = 'event'; //hit type
	private $ec = ''; // event category
	private $ea = ''; // Event action
	private $el = ''; // Event Label
	private $ev = ''; // Event Value
	private $dp = ''; // Document Path
	private $dl = ''; // Document Location
	private $dt = ''; // Document Title
	private $dh = ''; // Document Host Name

	public function init() {
		$this->cid = $this->create_client_id();
	}

	public function set_event_category( $event_category ) {
		$this->ec = $event_category;
	}

	public function set_event_action( $event_action ) {
		$this->ea = $event_action;
	}

	public function set_event_label( $event_label ) {
		$this->el = $event_label;
	}

	public function set_event_value( $event_value ) {
		$this->ev = $event_value;
	}

	public function set_document_path( $document_path ) {
		$this->dp = $document_path;
	}

	public function set_document_host( $document_host ) {
		$this->dh = $document_host;
	}

	public function set_document_location( $document_location ) {
		$this->dl = $document_location;
	}

	public function set_document_title( $document_title ) {
		$this->dt = $document_title;
	}

	public function send( $ua_code ) {

		// Get variables in wp_remote_post body format
		$mp_vars = array(
			'cid',
			'v',
			't',
			'ec',
			'ea',
			'el',
			'ev',
			'dp',
			'dl',
			'dt',
			'dh',
		);
		$mp_body = array(
			'tid' => $ua_code,
		);
		foreach( $mp_vars as $index => $mp_var ) {
			if ( empty( $this->{$mp_vars[$index]} ) ) continue; // Empty params cause the payload to fail in testing
			$mp_body[$mp_var] = $this->{$mp_vars[$index]};
		}
		// Add Payload
		$payload = add_query_arg( $mp_body, $this->endpoint );

		// Perform the POST
		$response = wp_remote_get( esc_url_raw( $payload ) );

	}


	/**
	 * Create a GUID on Client specific values
	 *
	 * @return string
	 */
	private function create_client_id() {

		// collect user specific data
		if ( isset( $_COOKIE['_ga'] ) ) {

			$ga_cookie = explode( '.', $_COOKIE['_ga'] );
			if( isset( $ga_cookie[2] ) ) {

				// check if uuid
				if( $this->check_UUID( $ga_cookie[2] ) ) {

					// uuid set in cookie
					return $ga_cookie[2];
				}
				elseif( isset( $ga_cookie[2]) && isset( $ga_cookie[3] ) ) {

					// google default client id
					return $ga_cookie[2] . '.' . $ga_cookie[3];
				}
			}
		}

		// nothing found - return random uuid client id
		return $this->generate_UUID();
	}

	/**
	 * Check if is a valid UUID v4
	 *
	 * @param $uuid
	 * @return int
	 */
	private function check_UUID( $uuid ) {
		return preg_match('#^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$#i', $uuid );
	}

	/**
	 * Generate UUID v4 function - needed to generate a CID when one isn't available
	 *
	 * @author Andrew Moore http://www.php.net/manual/en/function.uniqid.php#94959
	 * @return string
	 */
	private function generate_UUID() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

			// 16 bits for "time_mid"
			mt_rand( 0, 0xffff ),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand( 0, 0x0fff ) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand( 0, 0x3fff ) | 0x8000,

			// 48 bits for "node"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}
}

?>
