<?php
/**
 * Plugin Name: Gravity Forms Scheduled Entries Export
 * Plugin URI:
 * Description: Exports entries to a.csv and sends them in an email on a schedule.
 * Version: 1.0
 * Author: Hall Internet Marketing
 * Author URI: http://hallme.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Text Domain: gfscheduledexport
 * PHP version 5.6.16
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 *
 * @since 1.0.0
 */
function activate_gfscheduledexport() {
	// TODO:reschedule any saved feed in cron.
}
register_activation_hook( __FILE__, 'activate_gfscheduledexport' );

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 */
function deactivate_gfscheduledexport() {
	// TODO: clear cron jobs.
}
register_deactivation_hook( __FILE__, 'deactivate_gfscheduledexport' );

/*
 * The Add-On Framework handles much of the uninstall for the class unless custom settings or database tables where added.
 * https://www.gravityhelp.com/documentation/article/gfaddon/#uninstalling
 */

// Make sure that gravity forms in installed and active.
if ( class_exists( 'GFForms' ) ) {

	// Add the Gravity Fomrs Add-on Framework - http://www.gravityhelp.com/documentation/gravity-forms/extending-gravity-forms/add-on-framework/gfaddon/
	GFForms::include_feed_addon_framework();

	class GFScheduledExport extends GFFeedAddOn {

		// Basic plugin information.
		protected $_version = '1.0';
		protected $_min_gravityforms_version = '1.7.9999';
		protected $_slug = 'gfscheduledexport';
		protected $_path = 'gravityformsscheduledexport/scheduled-export.php';
		protected $_full_path = __FILE__;
		protected $_title = 'Gravity Forms Scheduled Entries Export';
		protected $_short_title = 'Scheduled Export';

		public function pre_init() {
			parent::pre_init();
			// add tasks or filters here that you want to perform during the class constructor - before WordPress has been completely initialized.
		}

		public function init() {
			parent::init();
			// add tasks or filters here that you want to perform both in the backend and frontend and for ajax requests.
		}

		public function init_admin() {
			parent::init_admin();
			// add tasks or filters here that you want to perform only in admin.
			add_action('gform_after_save_form', array( &$this, 'log_form_saved' ), 10, 2);
		}

		public function init_frontend() {
			parent::init_frontend();
			// add tasks or filters here that you want to perform only in the front end.
		}

		public function init_ajax() {
			parent::init_ajax();
			// add tasks or filters here that you want to perform only during ajax requests.
		}

		/**
		 * Add a custom inputs for the field used on the form.
		 *
		 * @since 1.0.0
		 */
		public function settings_export_form_feilds() {

			// collect the form id from the schedule export settings page url for the current form.
			$form_id = $_REQUEST['id'];
			$form = RGFormsModel::get_form_meta($form_id);

			// collect an array the saved field settings.
			$saved_settings = GFAddOn::get_setting('export_field');

			// apply filter
			$form = apply_filters( "gform_form_export_page_{$form_id}", apply_filters( 'gform_form_export_page', $form ) );

			// collect and add the default export fields.
			$form = GFExport::add_default_export_fields( $form );

			// loop through the fields and format all the inputs in to an array to be rendered as checkboxes.
			if (is_array( $form['fields'] ) ) {
				foreach ( $form['fields'] as $field ) {
					$inputs = $field->get_entry_inputs();
					if (is_array($inputs) ) {
						foreach ( $inputs as $input ) {
							$choices[] = array(
							'label' => GFCommon::get_label($field, $input['id']),
							'value' => $input['id'],
							'name' => 'export_field[]'
							);
						}
					} else if (! $field->displayOnly ) {
						$choices[] = array(
						'label' => GFCommon::get_label($field),
						'value' => $field->id,
						'name' => 'export_field[]'
						);
					}
				}
			}

			// loop variables.
			$field_name = "export_field";
			$choice_count = 0;

			?>
		 <ul id="export_field_list">
		  <li>
		   <input id="select_all" type="checkbox" onclick="jQuery('.gform_export_field').attr('checked', this.checked); jQuery('#gform_export_check_all').html(this.checked ? '<strong>Deselect All</strong>' : '<strong>Select All</strong>'); ">
		   <label id="gform_export_check_all" for="select_all"><strong>Select All</strong></label>
		  </li>
			<?php

			// loop through and output the choices.
			foreach ( $choices as $choice ){

				$field_id = $field_name . '_' . $choice_count;
				$checked = in_array($choice['value'], $saved_settings)  ? ' checked="checked"' : '';

				echo '<li>';
				echo '<input type="checkbox"'. $checked .' id="'. $field_id .'" name="_gaddon_setting_'. $choice['name'] .'" value="'. $choice['value'] .'" class="gform_'. $field_name .'">';
				echo '<label for="'. $field_id .'">'. $choice['label'] .'</label>';
				echo '</li>';

				$choice_count++;
			}
			?>
		 </ul>
			<?php
		}

		/**
		 * Add form settings page with schedule export options.
		 *
		 * TODO: Add default email address - admin email if empty?
		 *
		 * @since 1.0.0
		 */
		public function feed_settings_fields() {

			// Collect the form id from the schedule export settings page url for the current form.
			$form_id = $_REQUEST['id'];

			// Collect the inputs to print using the API.
			$inputs = array(
						// Main Settings.
						array(
							'title' => __("Scheduled Entries Export", $this->_slug),
							'description' => __("The settings below will automatically export new entries and send them to the emails below based on the set time frame.", $this->_slug),
							'fields' => array(
							// Give the schedule a name.
						array(
							'name'	=> 'export_feed_name',
							'type'	=> 'text',
							'class'   => 'small',
							'label'   => __("Name", $this->_slug),
							'tooltip' => __("Enter a name to identify the schedule", $this->_slug)
						),
					   // Set the time frame drop-down.
					   array(
							'name'	=> 'export_schedule',
							'type'	=> 'select',
							'label'   => __("Time Frame", $this->_slug),
							'tooltip' => __("Set how frequently it the entries are exported and emailed", $this->_slug),
							'choices' => array(
								array(
									'label' => __("Every 2 min for testing", $this->_slug),
									'value' => 'mins'
								),
								array(
									'label' => __("Hourly", $this->_slug),
									'value' => 'hourly'
								),
								array(
									'label' => __("Twice Daily", $this->_slug),
									'value' => 'twicedaily'
								),
								array(
									'label' => __("Daily", $this->_slug),
									'value' => 'daily'
								),
								array(
									'label' => __("Weekly", $this->_slug),
									'value' => 'weekly'
								)
							)
						),
						// Set the destination email for the exported files.
						array(
						   'name'	  => 'receive_email',
						   'type'	  => 'text',
						   'class'	  => 'medium',
						   'label'	  => __("Email Address", $this->_slug),
						   'tooltip' => __("Enter a comma separated list of emails you would like to receive the exported entries file.", $this->_slug)
						),
						array(
							'label'   => "Form Fields",
							'type'	=> "export_form_feilds",
							'name'	=> "export_field",
							'tooltip' => "Select the fields you would like to include in the export. Caution: Make sure you are not sending any sensitive information."

						),
						array(
							'name'			 => 'condition',
							'type'			 => 'feed_condition',
							'label'			 => __("Condition", $this->_slug),
							'tooltip'		  => __("Set conditional logic that must be met before sending the export.", $this->_slug),
							'checkbox_label' => __("Enable Condition", $this->_slug),
							'instructions'	 => __("Process this simple feed if", $this->_slug)
						),
						array(
							'type'		  => 'hidden',
							'name'		  => 'export_form',
							'label'		 => 'Export Form',
							'default_value' => $form_id,
						)
					)
				)
			);
			return $inputs;
		}

		/**
		 * Set the column names on the feed list
		 *
		 * @since 1.0.0
		 */
		public function feed_list_columns() {

			return array(
				'export_feed_name' => __("Name", $this->_slug),
				'export_schedule'  => __("Time Frame", $this->_slug),
				'email'			   => __("Email", $this->_slug)
			);
		}

		/**
		 * Customize the value of before it's rendered to the list
		 *
		 * @since 1.0.0
		 *

		public function get_column_value_email($feed){
			// return "<b>" . $feed["meta"]["email"] ."</b>";
		}
		*/

		//public function process_feed($feed, $entry, $form){
			//$feedName = $feed["meta"]["feedName"];
			//$timeFrame = $feed["meta"]["timeFrame"];
			//$email = $feed["meta"]["email"];
			//$fields = $feed["meta"]["fields"];
		//}

		/**
		 * Fire on saving the feed settings
		 *
		 * @since 1.0.0
		 */
		public function save_feed_settings( $feed_id, $form_id, $settings ) {

			// Save the feed settings to the database.
			if ($feed_id ) {
				$this->update_feed_meta( $feed_id, $settings );
				$result = $feed_id;
			} else {
				$result = $this->insert_feed( $form_id, true, $settings );
			}

			// TODO: Admin Nonce!
			// check_admin_referer( 'rg_start_export', 'rg_start_export_nonce' );

			// set_cron_gfscheduledexport( $feed_id, $settings['export_schedule'] );

			//$getstuff = parent::get_feed($feed_id);

			//echo "<pre>";
			//var_dump($_POST);
			//echo "</pre>";

			self::gfscheduledexport_cron_job( $feed_id, $form_id );

			return $result;
		}

		/**
		 * Schedules the export
		 *
		 * @since 1.0.0
		 */
		public function set_cron_gfscheduledexport( $feed_id, $time_frame ) {

			if(!wp_next_scheduled('gfscheduledexport_cron_job') ) {
				wp_schedule_event(time(), $time_frame, 'gfscheduledexport_cron_job', $feed_id);
			}
		}

		/**
		 * Fire on the cron job and runs the email
		 *
		 * @since 1.0.0
		 */
		public function gfscheduledexport_cron_job( $feed_id, $form_id ) {

			self::build_csv( $form_id );

		}

		/**
		 * Create the CSV to attach to the email
		 *
		 * @since 1.0.0
		 */
		public function build_csv( $form_id ) {

			// Get the feed setting an load them into the $_POST var to us the start_export() filter.
			$_POST = null;
			$feed_data = parent::get_feed( $feed_id );
			foreach( $feed_data['meta'] as $key => $value ) {
				$_POST["$key"] = $value;
			}

			$form = RGFormsModel::get_form_meta( $form_id );

			$filename = sanitize_title_with_dashes( $form['title'] ) . '-' . gmdate( 'Y-m-d', GFCommon::get_local_timestamp( time() ) ) . '.csv';
			$charset  = get_option( 'blog_charset' );

			header( 'Content-Description: File Transfer' );
			header( "Content-Disposition: attachment; filename=$filename" );
			header( 'Content-Type: text/csv; charset=' . $charset, true );

			$buffer_length = ob_get_length(); //length or false if no buffer
			if ( $buffer_length > 1 ) {
				ob_clean();
			}

			GFExport::start_export( $form );

		}

	} // END GFScheduledExport Class.

	new GFScheduledExport();

	/**
	 * TODO: Use alternative to that it can be sent at the first of the Week/Month
	 * Add time frame options to the cron schedule
	 *
	 * TODO: Check if this should be in the class and if show where it should init?
	 *
	 * @reference https://codex.wordpress.org/Function_Reference/wp_get_schedules
	 * @since 1.0.0
	 * @param array $schedules
	 */
	function scheduled_export_cron_add_times( $schedules ) {
		// Adds once weekly to the existing schedules.
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display' => __( 'Weekly' ),
		);
		// Adds once weekly to the existing schedules.
		$schedules['mins'] = array(
			'interval' => 120,
			'display' => __( '2 Minutes' ),
		);
		return $schedules;
	}
	add_filter( 'cron_schedules', 'scheduled_export_cron_add_times' );
}
