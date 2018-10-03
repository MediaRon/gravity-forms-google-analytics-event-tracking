<?php
GFForms::include_addon_framework();
class GFGAET_Partial_Entries extends GFAddOn {
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

	public function init_admin() {
		parent::init_admin();
		add_action( 'gform_field_advanced_settings', array( $this, 'advanced_settings' ), 10, 2 );
		add_action( 'gform_editor_js', array( $this, 'editor_script' ) );
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
			fieldSettings[i] += ', .admin_partial_entry_category, .admin_partial_entry_action, .admin_partial_entry_label';
		});
		// Populate field settings on initialization.
		jQuery(document).on('gform_load_field_settings', function(ev, field){
			jQuery(document.getElementById('field_partial_entries_category')).val(field.field_partial_entries_category || '');
			jQuery(document.getElementById('field_partial_entries_action')).val(field.field_partial_entries_action || '');
			jQuery(document.getElementById('field_partial_entries_label')).val(field.field_partial_entries_label || '');
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
            <input type="text" id="field_partial_entries_category" class="partial-entries-category fieldwidth-2 merge-tag-support mt-position-right mt-prepopulate" onChange="SetFieldProperty( 'field_partial_entries_category', this.value );" oninput="SetFieldProperty( 'field_partial_entries_label', this.value );" />
		</li>
		<li class="admin_partial_entry_action field_setting">
            <label for="field_partial_entries_action" class="section_label">
                <?php _e( 'Event Action', 'gravity-forms-google-analytics-event-tracking' ); ?>
                <?php gform_tooltip( 'gfgaet_action' ); ?>
            </label>
            <input type="text" id="field_partial_entries_action" class="partial-entries-action fieldwidth-2 merge-tag-support mt-position-right mt-prepopulate" onChange="SetFieldProperty( 'field_partial_entries_action', this.value );" oninput="SetFieldProperty( 'field_partial_entries_label', this.value );" />
		</li>
		<li class="admin_partial_entry_label field_setting">
            <label for="field_partial_entries_label" class="section_label">
                <?php _e( 'Event Label', 'gravity-forms-google-analytics-event-tracking' ); ?>
                <?php gform_tooltip( 'gfgaet_label' ); ?>
            </label>
            <input type="text" id="field_partial_entries_label" class="partial-entries-label fieldwidth-2 merge-tag-support mt-position-right mt-prepopulate" onChange="SetFieldProperty( 'field_partial_entries_label', this.value );" oninput="SetFieldProperty( 'field_partial_entries_label', this.value );" />
        </li>
		<?php
	}
}
