<?php
/**
 * @package   Gravity_Forms_Event_Tracking
 * @author    Nathan Marks <nmarks@nvisionsolutions.ca>
 * @license   GPL-2.0+
 * @link      http://www.nvisionsolutions.ca
 * @copyright 2014 Nathan Marks
 */

/**
 *
 * @package   Gravity_Forms_Event_Tracking
 * @author    Nathan Marks <nmarks@nvisionsolutions.ca>
 */
class Gravity_Forms_Event_Tracking {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'gravity-forms-event-tracking';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * GA Tracking Object
	 * 
	 * @since 1.1.0
	 */

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		$this->init_measurement_client();
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
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
	 * Setup the google measurement protocol PHP client.
	 * 
	 * @since 1.1.0
	 */
	private function init_measurement_client(){
		require_once( 'includes/ga-mp/src/Racecore/GATracking/Autoloader.php');
		Racecore\GATracking\Autoloader::register(dirname(__FILE__).'/includes/ga-mp/src/');

		$ua_id = get_option('gravity_forms_event_tracking_ua');

		if (!$ua_id)
			return;

		// init tracking
		$this->tracking = new \Racecore\GATracking\GATracking($ua_id,false);

		add_action('gform_after_submission',array($this,'track_form'),10,2);
	}

	/**
	 * Track the form
	 * 
	 * @since 1.1.0
	 */
	public function track_form($entry,$form){

		$event = new \Racecore\GATracking\Tracking\Event();
		$event->setEventCategory('Forms');
		$event->setEventLabel('Form: '.$form['title'].' ID: '.$form['id']);
		$event->setEventAction('Submission');

		$this->tracking->addTracking($event);

		try {
		    $this->tracking->send();
		} catch (Exception $e) {
		    echo 'Error: ' . $e->getMessage() . '<br />' . "\r\n";
		    echo 'Type: ' . get_class($e);
		}

	}


}
