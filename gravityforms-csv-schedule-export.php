<?php
/*
Plugin Name: Gravity Forms CVS Scheduled Export
Plugin URI:
Description: Exports submissions and sends the csv export to an email address on a schedule.
Version: 1.0
Author: Hall Internet Marketing
Author URI: http://hallme.com
License: GPL2
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


class GFScheduledExport {
	/**
	 * Instance of this class.
	 *
	 * @since	 1.0.0
	 *
	 * @var		object
	 */
	protected static $instance = null;

	/**
	 * Plugin Name
	 *
	 * @since	 1.0.0
	 *
	 * @var		string
	 */
	public $plugin_name;

	/**
	 * Version
	 *
	 * @since	 1.0.0
	 *
	 * @var		string
	 */
	public $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks.
	 *
	 * @since	1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'gravityformscheduledexport';
		$this->version = '1.0.0';

		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_styles' ) );

		// You may add any other hooks here
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since	  1.0.0
	 *
	 * @return	 object	 A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @since	  1.0.0
	 *
	 */
	public function scripts_styles() {
		wp_enqueue_script( $this->plugin_name, plugins_url( '/js/public.js', __FILE__ ), array( 'jquery' ), $this->version, false );

		wp_enqueue_style( $this->plugin_name, plugins_url( '/css/public.css', __FILE__ ), array(), $this->version, 'all' );
	}

}

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
add_action('plugins_loaded', array( 'Plugin_Name', 'get_instance' ) );


// Run the admin class only when doing admin.
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	require_once ( dirname( __FILE__ ) . '/plugin-name-admin.php' );
	/**
	 * Begins execution of the admin class.
	 *
	 * @since    1.0.0
	 */
	add_action('plugins_loaded', array( 'Plugin_Name_Admin', 'get_instance' ) );
}
