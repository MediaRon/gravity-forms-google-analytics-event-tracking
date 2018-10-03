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
		add_action( 'gform_partialentries_post_entry_saved', array( $this, 'partial_entry_saved' ), 10, 2 );
		add_action( 'gform_partialentries_post_entry_updated', array( $this, 'partial_entry_saved' ), 10, 2 );
	}

	public function partial_entry_saved( $partial_entry, $form ) {
		//error_log( print_r( $form, true ) );
		$form_fields = $this->get_mapped_fields( $partial_entry, $form );
		error_log( print_r( $form_fields, true ) );
		foreach( $form['fields'] as $index => $values ) {
			if( array_key_exists( 'field_partial_entries_category', $values ) ) {
				$event_category = $values['field_partial_entries_category'];
				$event_action = $values['field_partial_entries_action'];
				$event_label = $values['field_partial_entries_label'];
				$field_id = $values['id'];
			}
		}
		foreach( $partial_entry as $id => $value ) {
			//error_log( print_r( $this->get_field_value( $form, $partial_entry, $id ), true ) );
		}
		//error_log(print_r( $partial_entry, true ) );
		//error_log(print_r($form, true ) );
	}

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
						'value' => $entry[ $input['id'] ],
						'category' => $event_category,
						'action' => $event_action,
						'label' => $event_label,
						'value' => $event_value
					);
				}
			} else {
				$mapping[ $field_ids[0] ] = array(
					'value' => $entry[ $field['id'] ],
					'category' => $event_category,
					'action' => $event_action,
					'label' => $event_label,
					'value' => $event_value
				);
			}
		}
		
		return $mapping;
	}

	public function init_admin() {
		parent::init_admin();
		add_action( 'gform_field_advanced_settings', array( $this, 'advanced_settings' ), 10, 2 );
		add_action( 'gform_editor_js', array( $this, 'editor_script' ) );
		add_filter( 'gform_tooltips', array( $this, 'add_tooltips' ) );
	}

	public function add_tooltips( $tooltips ) {
		$tooltips['gfgaet_category'] = __( 'Event category which you would like to send to Google Analytics using Partial Entries', 'gravity-forms-google-analytics-event-tracking' );
		$tooltips['gfgaet_action'] = __( 'Event action which you would like to send to Google Analytics using Partial Entries', 'gravity-forms-google-analytics-event-tracking' );
		$tooltips['gfgaet_label'] = __( 'Event label which you would like to send to Google Analytics using Partial Entries', 'gravity-forms-google-analytics-event-tracking' );
		$tooltips['gfgaet_value'] = __( 'Event value (Integers only) which you would like to send to Google Analytics using Partial Entries', 'gravity-forms-google-analytics-event-tracking' );
		return $tooltips;
	}
	public function minimum_requirements() {
		return array(
			// Require other add-ons to be present.
			'add-ons' => array(			
				'gravityformspartialentries',
			),
		);
	}
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
	public function advanced_settings( $position, $form_id ) {
		if( 100 !== $position ) return;
		?>
		<li class="admin_partial_entry_category field_setting">
            <label for="field_partial_entries_category" class="section_label">
                <?php _e( 'Event Category', 'gravity-forms-google-analytics-event-tracking' ); ?>
                <?php gform_tooltip( 'gfgaet_category' ); ?>
            </label>
            <input type="text" id="field_partial_entries_category" class="partial-entries-category fieldwidth-2" onChange="SetFieldProperty( 'field_partial_entries_category', this.value );" oninput="SetFieldProperty( 'field_partial_entries_label', this.value );" />
		</li>
		<li class="admin_partial_entry_action field_setting">
            <label for="field_partial_entries_action" class="section_label">
                <?php _e( 'Event Action', 'gravity-forms-google-analytics-event-tracking' ); ?>
                <?php gform_tooltip( 'gfgaet_action' ); ?>
            </label>
            <input type="text" id="field_partial_entries_action" class="partial-entries-action fieldwidth-2" onChange="SetFieldProperty( 'field_partial_entries_action', this.value );" oninput="SetFieldProperty( 'field_partial_entries_label', this.value );" />
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
            <input type="text" id="field_partial_entries_value" class="partial-entries-value fieldwidth-2" onChange="SetFieldProperty( 'field_partial_entries_label', this.value );" oninput="SetFieldProperty( 'field_partial_entries_value', this.value );" />
        </li>
		<?php
	}
}
