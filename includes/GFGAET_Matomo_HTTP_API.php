<?php
class GFGAET_Matomo_HTTP_API {

	private $endpoint = ''; // Tracking HTTP API Endpoint
	private $_id = ''; // Unique Visitor ID
	private $idsite = ''; // Site ID
	private $rec = 1; // Required for tracking
	private $apiv = 1; // API Version (currently v1)
	private $rand = ''; // Random value to help avoid the tracking request being cached by the browser or a proxy
	private $action_name = ''; // Event category
	private $e_c = ''; // Event category
	private $e_a = ''; // Event action
	private $e_n = ''; // Event Label
	private $e_v = ''; // Event Value
	private $url = ''; // Full URL

	public function init() {
		$this->_id = $this->create_client_id();

		if ( GFGAET::is_matomo_configured() ) {

			$gravity_forms_add_on_settings = get_option( 'gravityformsaddon_GFGAET_UA_settings', false );

			$this->endpoint = rtrim($gravity_forms_add_on_settings[ 'gravity_forms_event_tracking_matomo_url' ],'/').'/piwik.php'; // Provided Tracking HTTP API Endpoint (auto-remove trailing slash in provided URL to then include our own [prevent worrying if the trailing slash was or wasn't provided.])

			$this->idsite = $gravity_forms_add_on_settings[ 'gravity_forms_event_tracking_matomo_siteid' ]; // Site ID
		}

		$this->rand = uniqid('',false); // Random value to help avoid the tracking request being cached by the browser or a proxy

	}

	public function set_matomo_event_category( $event_category ) {
		$this->action_name = urlencode($event_category);
		$this->e_c = urlencode($event_category);
	}

	public function set_matomo_event_action( $event_action ) {
		$this->e_a = urlencode($event_action);
	}

	public function set_matomo_event_label( $event_label ) {
		$this->e_n = urlencode($event_label);
	}

	public function set_matomo_event_value( $event_value ) {
		$this->e_v = urlencode($event_value);
	}

	public function set_matomo_document_location( $document_location ) {
		$this->url = urlencode($document_location);
	}

	public function send_matomo() {

		if ( GFGAET::is_matomo_configured() ) {

			// Get variables in wp_remote_post body format
			$matomo_mp_vars = array(
				//'_id',
				'idsite',
				'rec',
				'apiv',
				'rand',
				'action_name',
				'e_c',
				'e_a',
				'e_n',
				'e_v',
				'url',
			);
			$matomo_mp_body = array();
			foreach( $matomo_mp_vars as $index => $matomo_mp_var ) {
				if ( empty( $this->{$matomo_mp_vars[$index]} ) ) continue; // Empty params cause the payload to fail in testing
				$matomo_mp_body[$matomo_mp_var] = $this->{$matomo_mp_vars[$index]};
			}

			// Add Payload
			$payload = add_query_arg( $matomo_mp_body, $this->endpoint );

			// Perform the POST
			$response = wp_remote_get( esc_url_raw( $payload ) );

		}

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
