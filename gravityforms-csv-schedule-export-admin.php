<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name_Admin
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class GFScheduledExport_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since	 1.0.0
	 *
	 * @var		object
	 */
	protected static $instance = null;

	/**
	 * Plugin object
	 *
	 * @since	 1.0.0
	 *
	 * @var		Woocommerce_Swatches
	 */
	public $plugin;

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
		$this->plugin = Plugin_Name::get_instance();

		$this->plugin_name = $this->plugin->plugin_name;
		$this->version = $this->plugin->version;

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts_styles' ) );
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
	 * Enqueue all admin scripts
	 *
	 * @since	  1.0.0
	 */
	public function admin_scripts_styles() {
		wp_enqueue_script( $this->plugin_name, plugins_url( '/js/admin.js', __FILE__ ), array( 'jquery' ), $this->version, false );

		wp_enqueue_style( $this->plugin_name, plugins_url( '/css/admin.css', __FILE__ ), array(), $this->version, 'all' );
	}

}
