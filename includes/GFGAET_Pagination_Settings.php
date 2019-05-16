<?php
GFForms::include_addon_framework();
class GFGAET_Pagination_Settings extends GFAddOn {
	protected $_version = '2.0';
	protected $_min_gravityforms_version = '1.8.20';
	protected $_slug = 'GFGAET_Pagination_Settings';
	protected $_path = 'gravity-forms-google-analytics-event-tracking/gravity-forms-event-tracking.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms Google Analytics Event Tracking';
	protected $_short_title = 'Event Tracking';
	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_event_tracking', 'gravityforms_event_tracking_uninstall' );
	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_event_tracking';
	protected $_capabilities_form_settings = 'gravityforms_event_tracking';
	protected $_capabilities_uninstall = 'gravityforms_event_tracking_uninstall';

	private static $_instance = null;

	/**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @return object $_instance An instance of this class.
	 */
	public static function get_instance() {
	    if ( self::$_instance == null ) {
	        self::$_instance = new self();
	    }

	    return self::$_instance;
	}

	public function init() {
		parent::init();

		add_filter( 'gform_form_settings', array( $this, 'add_pagination_form_settings' ), 10, 2 );
		add_filter( 'gform_pre_form_settings_save', array( $this, 'save_pagination_settings' ), 10, 1 );
	}

	/**
	 * Save pagination settings.
	 *
	 * @since 2.3.5
	 *
	 * @param array $form     The form
	 *
	 * @return array Updated form values
	 */
	public function save_pagination_settings( $form ) {
		$form['pagination_category'] = rgpost( 'pagination_category' );
		$form['pagination_action'] = rgpost( 'pagination_action' );
		$form['pagination_label'] = rgpost( 'pagination_label' );
		$form['pagination_value'] = rgpost( 'pagination_value' );
		return $form;
	}

	/**
	 * Add pagination form settings to Gravity Forms.
	 *
	 * @since 2.3.5
	 *
	 * @param array $settings The form settings
	 * @param array $form     The form
	 *
	 * @return array Updated form settings
	 */
	public function add_pagination_form_settings( $settings, $form ) {
		$settings[ __( 'Pagination Event Tracking', 'gravity-forms-google-analytics-event-tracking' ) ]['pagination_description'] = sprintf( '
		<tr>
			<th colspan="2">
				%s<br /><br />
					<strong>%s:</strong> form<br />
					<strong>%s:</strong> pagination<br />
					<strong>%s:</strong> %s<br />
					<strong>%s:</strong> 0
			</th>
		</tr>', __( 'If left blank, the following values are used:', 'gravity-forms-google-analytics-event-tracking' ), __( 'Category', 'gravity-forms-google-analytics-event-tracking' ), __( 'Action', 'gravity-forms-google-analytics-event-tracking' ), __( 'Label', 'gravity-forms-google-analytics-event-tracking' ), __( '{form_title}::{source_page_number}::{current_page_number}', 'gravity-forms-google-analytics-event-tracking' ), __( 'Value', 'gravity-forms-google-analytics-event-tracking' ) );
		$settings[ __( 'Pagination Event Tracking', 'gravity-forms-google-analytics-event-tracking' ) ]['pagination_category'] = sprintf( '
			<tr>
				<th><label for="pagination_category">%s</label></th>
				<td><input value="%s" name="pagination_category" id="pagination_category" class="fieldwidth-3" /></td>
			</tr>', __( 'Pagination Category', 'gravity-forms-google-analytics-event-tracking' ), esc_attr( rgar( $form, 'pagination_category' ) ) );
		$settings[ __( 'Pagination Event Tracking', 'gravity-forms-google-analytics-event-tracking' ) ]['pagination_action'] = sprintf( '
		<tr>
			<th><label for="pagination_action">%s</label></th>
			<td><input value="%s" name="pagination_action" id="pagination_action" class="fieldwidth-3" /></td>
		</tr>', __( 'Pagination Action', 'gravity-forms-google-analytics-event-tracking' ), esc_attr( rgar( $form, 'pagination_action' ) ) );
		$settings[ __( 'Pagination Event Tracking', 'gravity-forms-google-analytics-event-tracking' ) ]['pagination_label'] = sprintf( '
		<tr>
			<th><label for="pagination_label">%s</label></th>
			<td><input value="%s" name="pagination_label" id="pagination_label" class="fieldwidth-3" /></td>
		</tr>', __( 'Pagination Label', 'gravity-forms-google-analytics-event-tracking' ), esc_attr( rgar( $form, 'pagination_label' ) ) );
		$settings[ __( 'Pagination Event Tracking', 'gravity-forms-google-analytics-event-tracking' ) ]['pagination_value'] = sprintf( '
		<tr>
			<th><label for="pagination_value">%s</label></th>
			<td><input value="%s" type="number" name="pagination_value" id="pagination_value" class="fieldwidth-3" /></td>
		</tr>', __( 'Pagination Value', 'gravity-forms-google-analytics-event-tracking' ), esc_attr( rgar( $form, 'pagination_value' ) ) );
		return $settings;
	}
}
