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

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action('admin_init',array($this,'add_settings_fields'));

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

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
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Gravity Forms Event Tracking', $this->plugin_slug ),
			__( 'Gravity Forms Event Tracking', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Adds the settings fields for the options page
	 * 
	 * @since 1.0.0
	 */
	public function add_settings_fields() {

		/**
		 * Add the settings section
		 */
		add_settings_section(
			'gravity_forms_event_tracking_settings_section',
			'',
			array($this,'render_settings_description'),
			$this->plugin_slug
		);

		/**
		 * Add the tracking ID input
		 */
		add_settings_field(
			'gravity_forms_event_tracking_ua',
			'UA Tracking ID',
			array($this,'render_ua_field'),
			$this->plugin_slug,
			'gravity_forms_event_tracking_settings_section',
			array('UA-XXXX-Y')
		);

		/**
		 * Register our settings
		 */
		register_setting($this->plugin_slug,'gravity_forms_event_tracking_ua',array($this,'ua_validation'));

	}

	/**
	 * Render the UA Field
	 * 
	 * @since  1.0.0
	 * @param array $args arguments sent via the add_settings_field function
	 */
	public function render_ua_field($args) {

		$html = '<input type="text" id="gravity_forms_event_tracking_ua" class="regular-text" name="gravity_forms_event_tracking_ua" value="'.get_option('gravity_forms_event_tracking_ua').'">';

		$html .= '<p class="description">'.$args[0].'</p>';

		echo $html;
	}

	/**
	 * Render the settings section description
	 * 
	 * @since 1.0.0
	 */
	public function render_settings_description() {
		echo '<p>Yup</p>';
	}

	/**
	 * Basic Validation
	 */
	public static function ua_validation($input ) {
		$input = strip_tags( stripslashes( $input ) );
		$ua_regex = "/UA-[0-9]{5,}-[0-9]{1,}/";

		if (preg_match($ua_regex, $input)) {
			return $input;
		}
		else {
			add_settings_error(
		    	'gravity_forms_event_tracking_ua',
		    	'gravity_forms_event_tracking_invalid_ua',
		    	'Invalid UA ID'
		    );
		    return false;
		}
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}


	

}
