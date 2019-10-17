<?php
// Credit: https://stevegrunwell.com/blog/custom-field-ids-gravity-forms/
GFForms::include_addon_framework();
class GFGAET_Partial_Entries extends GFAddOn {
	protected $_version = '2.0';
	protected $_min_gravityforms_version = '1.8.20';
	protected $_slug = 'GFGAET_Partial_Entries';
	protected $_path = 'gravity-forms-google-analytics-event-tracking/gravity-forms-event-tracking.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms Google Analytics Partial Entries';
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
	 * @since 2.3.0
	 *
	 * @return object $_instance An instance of this class.
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Initailizes partial entries updated and saved state
	 *
	 * @since 2.3.0
	 *
	 * @return void
	 */
	public function init() {
		parent::init();
		add_action( 'gform_partialentries_post_entry_saved', array( $this, 'partial_entry_saved' ), 10, 2 );
		add_action( 'gform_partialentries_post_entry_updated', array( $this, 'partial_entry_saved' ), 10, 2 );
	}

	/**
	 * Sends the event via the measurement protocol
	 *
	 * @since 2.3.0
	 *
	 * @param array $partial_entry The partial entry to be parsed
	 * @param array $form          The form to be parsed
	 *
	 * @return void
	 */
	public function partial_entry_saved( $partial_entry, $form ) {
		$form_fields = $this->get_mapped_fields( $partial_entry, $form );
		foreach( $form_fields as $gform_index => $gform_values ) {
			if( isset( $gform_values['event_category']) && !empty( $gform_values['event_category'] ) ) {

				// Get defaults
				$value = $gform_values['value'];
				if ( empty( $value ) ) {
					continue;
				}
				$label = strtolower( 'label: ' . $gform_values['label'] ) . " EntryID: {$partial_entry['id']}";

				// Get category/action/label
				$event_category = trim( $gform_values['event_category'] );
				$event_action = ( empty( $gform_values['event_action'] ) ? 'partial' : trim( $gform_values['event_action'] ) );
				$event_label = ( empty( $gform_values['event_label'] ) ? trim( $label ) : trim( $gform_values['event_label'] ) );

				// Get event value
				$event_value = ( empty( $gform_values['event_value'] ) ? trim( $value ) : trim( $gform_values['event_value'] ) );
				if( !is_numeric( $event_value ) ) {
					$event_value = 0;
				}
				$event_value = absint( round( GFCommon::to_number( $event_value ) ) );


				/**
				 * Filter: gform_partial_event_category
				 *
				 * Filter the event category dynamically
				 *
				 * @since 2.3.0
				 *
				 * @param string $event_category Event Category
				 * @param array  $form           Gravity Form form array
				 * @param array  $partial_entry  Gravity Form Partial Entry array
				 * @param string $value          Gravity Forms Field value
				 * @param string label           Label of the form entry
				 */
				$event_category = apply_filters( 'gform_partial_event_category', $event_category, $form, $partial_entry, $value, $label );

				/**
				 * Filter: gform_partial_event_action
				 *
				 * Filter the event action dynamically
				 *
				 * @since 2.3.0
				 *
				 * @param string $event_action   Event action
				 * @param array  $form           Gravity Form form array
				 * @param array  $partial_entry  Gravity Form Partial Entry array
				 * @param string $value          Gravity Forms Field value
				 * @param string label           Label of the form entry
				 */
				$event_action = apply_filters( 'gform_partial_event_action', $event_action, $form, $partial_entry, $value, $label );

				/**
				 * Filter: gform_partial_event_label
				 *
				 * Filter the event label dynamically
				 *
				 * @since 2.3.0
				 *
				 * @param string $event_label    Event label
				 * @param array  $form           Gravity Form form array
				 * @param array  $partial_entry  Gravity Form Partial Entry array
				 * @param string $value          Gravity Forms Field value
				 * @param string label           Label of the form entry
				 */
				$event_label = apply_filters( 'gform_partial_event_label', $event_label, $form, $partial_entry, $value, $label );

				/**
				 * Filter: gform_partial_event_value
				 *
				 * Filter the event value dynamically
				 *
				 * @since 2.3.0
				 *
				 * @param int    $event_value    Event value
				 * @param array  $form           Gravity Form form array
				 * @param array  $partial_entry  Gravity Form Partial Entry array
				 * @param string $value          Gravity Forms Field value
				 * @param string label           Label of the form entry
				 */
				$event_value = absint( round( GFCommon::to_number( apply_filters( 'gform_partial_event_value', $event_value, $form, $partial_entry, $value, $label ) ) ) );

				// Let's set up the measurement protocol
				$ua_code = GFGAET::get_ua_code();
				$event = new GFGAET_Measurement_Protocol();
				$event->init();
				$event->set_event_category( $event_category );
				$event->set_event_action( $event_action );
				$event->set_event_label( $event_label );
				$event->set_event_value( $event_value );
				$event->send( $ua_code );
			}
		}
	}

	/**
	 * Map fields for parsing
	 *
	 * @since 2.3.0
	 *
	 * @param array $entry The partial entry to be parsed
	 * @param array $form  The form to be parsed
	 *
	 * @return array Mapped fields
	 */
	public function get_mapped_fields( $entry, $form ) {
		$mapping = array();

		foreach ( $form['fields'] as $field ) {
			if ( ! isset( $field['id'] ) || ! $field['id'] ) {
			continue;
			}

			// Explode field IDs.
			$field_ids = explode( ',', $field['id'] );
			$field_ids = array_map( 'trim', $field_ids );
			$event_category = isset( $field['field_partial_entries_category'] ) ? $field['field_partial_entries_category'] : '' ;
			$event_action = isset( $field['field_partial_entries_action'] ) ? $field['field_partial_entries_action'] : '';
			$event_label = isset( $field['field_partial_entries_label'] ) ? $field['field_partial_entries_label'] : '';
			$event_value = isset( $field['field_partial_entries_value'] ) ? $field['field_partial_entries_value'] : '0';
			$event_category = GFCommon::replace_variables( $event_category, $form, $entry );
			$event_action = GFCommon::replace_variables( $event_action, $form, $entry );
			$event_label = GFCommon::replace_variables( $event_label, $form, $entry );
			$event_value = GFCommon::replace_variables( $event_value, $form, $entry );

			// We have a complex field, with multiple inputs.
			if ( ! empty( $field['inputs'] ) ) {
				foreach ( $field['inputs'] as $input ) {
					if ( isset( $input['isHidden'] ) && $input['isHidden'] ) {
					continue;
					}

					$field_id = array_shift( $field_ids );

					// If $field_id is empty, don't map this input.
					if ( ! $field_id ) {
					continue;
					}

					/*
					* Finally, map this value based on the $field_id
					* and $input['id'].
					*/
					$mapping[ $field_id ] = array(
						'value'          => $entry[ $input['id'] ],
						'label'          => $field['label'],
						'event_category' => $event_category,
						'event_action'   => $event_action,
						'event_label'    => $event_label,
						'event_value'    => $event_value
					);
				}
			} else {
				$mapping[ $field_ids[0] ] = array(
					'value'          => ( isset( $entry[ $field['id'] ] ) ) ? $entry[ $field['id'] ] : '',
					'label'          => ( isset( $field['label'] ) ) ? $field['label'] : '',
					'event_category' => $event_category,
					'event_action'   => $event_action,
					'event_label'    => $event_label,
					'event_value'    => $event_value
				);
			}
		}

		return $mapping;
	}

	/**
	 * Set up actions and filters for the add-on
	 *
	 * @since 2.3.0
	 *
	 * @return void
	 */
	public function init_admin() {
		parent::init_admin();
		add_action( 'gform_field_advanced_settings', array( $this, 'advanced_settings' ), 10, 2 );
		add_action( 'gform_editor_js', array( $this, 'editor_script' ) );
		add_filter( 'gform_tooltips', array( $this, 'add_tooltips' ) );
	}

	/**
	 * Set up tooltips for the advanced settings
	 *
	 * @since 2.3.0
	 *
	 * @param array $tooltips Array of tooltips
	 *
	 * @return array Updated Tooltips
	 */
	public function add_tooltips( $tooltips ) {
		$tooltips['gfgaet_category'] = __( 'Event category which you would like to send to Google Analytics using Partial Entries. Merge tags are not allowed.', 'gravity-forms-google-analytics-event-tracking' );
		$tooltips['gfgaet_action'] = __( 'Event action which you would like to send to Google Analytics using Partial Entries. Merge tags are not allowed.', 'gravity-forms-google-analytics-event-tracking' );
		$tooltips['gfgaet_label'] = __( 'Event label which you would like to send to Google Analytics using Partial Entries. Merge tags are not allowed. If left blank, the form value will be used.', 'gravity-forms-google-analytics-event-tracking' );
		$tooltips['gfgaet_value'] = __( 'Event value (Integers only) which you would like to send to Google Analytics using Partial Entries. Merge tags are not allowed. If left blank, the form value will be used assuming it is an integer.', 'gravity-forms-google-analytics-event-tracking' );
		return $tooltips;
	}

	/**
	 * Ensure the add-on only works with Partial Entries
	 *
	 * @since 2.3.0
	 *
	 * @return array Minimum requirements
	 */
	public function minimum_requirements() {
		return array(
			// Require other add-ons to be present.
			'add-ons' => array(
				'gravityformspartialentries',
			),
		);
	}

	/**
	 * Allow advanced options to be visible and map values to their parameters
	 *
	 * @since 2.3.0
	 *
	 * @return void
	 */
	public function editor_script() {
		/*
		 * Add .field_id_setting onto the end of each field
		 * type's properties.
		 */
		?>
		<script type="text/javascript">
		jQuery.map(fieldSettings, function (el, i) {
			fieldSettings[i] += ', .admin_partial_entry_category, .admin_partial_entry_action, .admin_partial_entry_label, .admin_partial_entry_value';
		});
		// Populate field settings on initialization.
		jQuery(document).on('gform_load_field_settings', function(ev, field){
			jQuery(document.getElementById('field_partial_entries_category')).val(field.field_partial_entries_category || '');
			jQuery(document.getElementById('field_partial_entries_action')).val(field.field_partial_entries_action || '');
			jQuery(document.getElementById('field_partial_entries_label')).val(field.field_partial_entries_label || '');
			jQuery(document.getElementById('field_partial_entries_value')).val(field.field_partial_entries_value || '');
		});
		</script>
		<?php
	}

	/**
	 * Set up advanced settings
	 *
	 * @since 2.3.0
	 *
	 * @param int $position The position of the advanced settings
	 * @param int $form_id  The form ID to perform the action on
	 *
	 * @return string HTML for advanced settings
	 */
	public function advanced_settings( $position, $form_id ) {
		if( 100 !== $position ) return;
		?>
		<li class="admin_partial_entry_category field_setting">
			<label for="field_partial_entries_category" class="section_label">
				<?php _e( 'Event Category', 'gravity-forms-google-analytics-event-tracking' ); ?>
				<?php gform_tooltip( 'gfgaet_category' ); ?>
			</label>
			<input type="text" id="field_partial_entries_category" class="partial-entries-category fieldwidth-2" onChange="SetFieldProperty( 'field_partial_entries_category', this.value );" oninput="SetFieldProperty( 'field_partial_entries_category', this.value );" />
		</li>
		<li class="admin_partial_entry_action field_setting">
			<label for="field_partial_entries_action" class="section_label">
				<?php _e( 'Event Action', 'gravity-forms-google-analytics-event-tracking' ); ?>
				<?php gform_tooltip( 'gfgaet_action' ); ?>
			</label>
			<input type="text" id="field_partial_entries_action" class="partial-entries-action fieldwidth-2" onChange="SetFieldProperty( 'field_partial_entries_action', this.value );" oninput="SetFieldProperty( 'field_partial_entries_action', this.value );" />
		</li>
		<li class="admin_partial_entry_label field_setting">
			<label for="field_partial_entries_label" class="section_label">
				<?php _e( 'Event Label', 'gravity-forms-google-analytics-event-tracking' ); ?>
				<?php gform_tooltip( 'gfgaet_label' ); ?>
			</label>
			<input type="text" id="field_partial_entries_label" class="partial-entries-label fieldwidth-2" onChange="SetFieldProperty( 'field_partial_entries_label', this.value );" oninput="SetFieldProperty( 'field_partial_entries_label', this.value );" />
		</li>
		<li class="admin_partial_entry_value field_setting">
			<label for="field_partial_entries_value" class="section_label">
				<?php _e( 'Event Value', 'gravity-forms-google-analytics-event-tracking' ); ?>
				<?php gform_tooltip( 'gfgaet_value' ); ?>
			</label>
			<input type="text" id="field_partial_entries_value" class="partial-entries-value fieldwidth-2" onChange="SetFieldProperty( 'field_partial_entries_value', this.value );" oninput="SetFieldProperty( 'field_partial_entries_value', this.value );" />
		</li>
		<?php
	}
}
