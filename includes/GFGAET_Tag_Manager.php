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

	/**
	 * Initialize the tag manager push.
	 *
	 * @since 2.0.0
	 *
	 * @param array $entry   The entry array
	 * @param array $form    The form array
	 */
	public function send( $entry, $form ) {
		if ( GFGAET::is_js_only() ) {
			?>
			<script>
			var form_submission = sessionStorage.getItem('entry_<?php echo absint( $entry[ 'id' ] ); ?>');
			if ( null == form_submission ) {
				if ( typeof( dataLayer ) != 'undefined' ) {
			    	dataLayer.push({'event': 'GFTrackEvent',
						'GFTrackCategory':'form',
						'GFTrackAction':'submission',
						'GFTrackLabel':'{{<?php echo esc_js( $form['title'] ); ?>}}::{{<?php echo esc_js( $entry['id'] ); ?>}}',
						'GFEntryData':<?php echo json_encode( $entry ); ?>
						});
					sessionStorage.setItem("entry_<?php echo absint( $entry[ 'id' ] ); ?>", "true");
				}
			}
			</script>
			<?php
		}
	}
}