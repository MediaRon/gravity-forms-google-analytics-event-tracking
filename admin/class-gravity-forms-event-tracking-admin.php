<?php
/**
 * Gravity forms event tracking
 *
 * @package   Gravity_Forms_Event_Tracking_Admin
 * @author    Nathan Marks <nmarks@nvisionsolutions.ca>
 * @license   GPL-2.0+
 * @link      http://www.nvisionsolutions.ca
 * @copyright 2014 Nathan Marks
 */

/**
 *
 * @package Gravity_Forms_Event_Tracking_Admin
 * @author  Nathan Marks <nmarks@nvisionsolutions.ca>
 */
class Gravity_Forms_Event_Tracking_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		$plugin = Gravity_Forms_Event_Tracking::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
		
		//Add items to Gravity Forms settings
		add_filter( 'gform_form_settings', array( $this, 'form_settings' ), 10, 2 );
		add_filter( 'gform_tooltips', array( $this, 'add_gforms_tooltips' ) );
		add_filter( 'gform_pre_form_settings_save', array( $this, 'save_gforms_data' ), 10, 1 );
	}

	/**
	 * Save Gravity Forms Data
	 *
	 * @since     1.0.0
	 *
	 * @return    array    sanitized gravity form settings
	 */
	public function save_gforms_data( $form_data ) {

		$form_data[ 'gaEventCategory' ] = rgpost( 'ga_event_category' );
		$form_data[ 'gaEventLabel' ] = rgpost( 'ga_event_label' );
		$form_data[ 'gaEventAction' ] = rgpost( 'ga_event_action' );

		return $form_data;
	}
	
	
	/**
	 * Add Gravity Forms Tooltips
	 *
	 * @since     1.0.0
	 *
	 * @return    array    Gravity Form tooltips
	 */
	public function add_gforms_tooltips( $tooltips ) {
		$tooltips[ 'ga_event_category' ] = sprintf( '<h6>%s</h6>%s', __( 'Event Category', 'gravity-forms-google-analytics-event-tracking' ), __( 'Enter your Google Analytics goal event category', 'gravity-forms-google-analytics-event-tracking' ) );
		$tooltips[ 'ga_event_label' ] = sprintf( '<h6>%s</h6>%s', __( 'Event Label', 'gravity-forms-google-analytics-event-tracking' ), __( 'Enter your Google Analytics goal event label', 'gravity-forms-google-analytics-event-tracking' ) );
		$tooltips[ 'ga_event_action' ] = sprintf( '<h6>%s</h6>%s', __( 'Event Action', 'gravity-forms-google-analytics-event-tracking' ), __( 'Enter your Google Analytics goal event action', 'gravity-forms-google-analytics-event-tracking' ) );
		$tooltips[ 'ga_event_value' ] = sprintf( '<h6>%s</h6>%s', __( 'Event Value', 'gravity-forms-google-analytics-event-tracking' ), __( 'Enter your Google Analytics goal event value', 'gravity-forms-google-analytics-event-tracking' ) );
		return $tooltips;
	}
	
	/**
	 * Add settings to the form settings page
	 *
	 * @since     1.0.0
	 *
	 * @return    array    Gravity Form settings
	 */
	public function form_settings( $form_settings, $form ) {
		$event_category = ' 
        <tr>
            <th>
                <label for="ga_event_category" style="display:block;">' .
                    __("Event Category", "gravity-forms-google-analytics-event-tracking") . ' ' .
                    gform_tooltip("ga_event_category", "", true) .
                '</label>
            </th>
            <td>
                <input type="text" id="ga_event_category" name="ga_event_category" class="fieldwidth-3" value="' . esc_attr(rgar($form, 'gaEventCategory')) . '" />
            </td>
        </tr>';
        $event_label = ' 
        <tr>
            <th>
                <label for="ga_event_label" style="display:block;">' .
                    __("Event Label", "gravity-forms-google-analytics-event-tracking") . ' ' .
                    gform_tooltip("ga_event_label", "", true) .
                '</label>
            </th>
            <td>
                <input type="text" id="ga_event_label" name="ga_event_label" class="fieldwidth-3" value="' . esc_attr(rgar($form, 'gaEventLabel')) . '" />
            </td>
        </tr>';
        $event_action = ' 
        <tr>
            <th>
                <label for="ga_event_action" style="display:block;">' .
                    __("Event Action", "gravity-forms-google-analytics-event-tracking") . ' ' .
                    gform_tooltip("ga_event_action", "", true) .
                '</label>
            </th>
            <td>
                <input type="text" id="ga_event_action" name="ga_event_action" class="fieldwidth-3" value="' . esc_attr(rgar($form, 'gaEventAction')) . '" />
            </td>
        </tr>';
        $event_value = ' 
        <tr>
            <th>
                <label for="ga_event_value" style="display:block;">' .
                    __("Event Value", "gravity-forms-google-analytics-event-tracking") . ' ' .
                    gform_tooltip("ga_event_value", "", true) .
                '</label>
            </th>
            <td>
                <input type="text" id="ga_event_value" name="ga_event_value" class="fieldwidth-3" value="' . esc_attr(rgar($form, 'gaEventValue')) . '" />
            </td>
        </tr>';
        $event_settings = array(
	      	'cat' => $event_category,
	      	'action' => $event_action,  
	      	'label' => $event_label,
	      	'value' => $event_value
	    );
		$event_tracking = array( __( 'Event Tracking', 'gravity-forms-google-analytics-event-tracking' ) => $event_settings );
		$form_settings = $form_settings + $event_tracking;
		return $form_settings;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=gf_settings&subview=gravity-forms-event-tracking' ) ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}


	

}