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

			//add_action( "wp_ajax_gf_feed_is_active_{$this->_slug}", array( $this, 'ajax_toggle_is_active' ) );
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
				$checked = in_array($choice['value'], ( array ) $saved_settings)  ? ' checked="checked"' : '';

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
									'label' => __("Hourly", $this->_slug),
									'value' => 'hourly'
								),
								array(
									'label' => __("Daily", $this->_slug),
									'value' => 'daily'
								),
								array(
									'label' => __("Weekly", $this->_slug),
									'value' => 'weekly'
								),
								array(
									'label' => __("Monthly", $this->_slug),
									'value' => 'monthly'
								)
							)
						),
						// Set the to email address.
						array(
						   'name'	  => 'export_email_to',
						   'type'	  => 'text',
						   'class'	  => 'medium',
						   'label'	  => __("To Email Addresses", $this->_slug),
						   'tooltip' => __("Enter a comma separated list of email addresses you would like to receive the exported entries file.", $this->_slug)
						),
						// Set the from email address.
						array(
						   'name'	  => 'export_email_from',
						   'type'	  => 'text',
						   'class'	  => 'medium',
						   'label'	  => __("From Email Address", $this->_slug),
						   'tooltip' => __("Enter the email address you would like the exported entries file sent from.", $this->_slug)
						),
						// Set the from email address.
						array(
						   'name'	  => 'export_email_subject',
						   'type'	  => 'text',
						   'class'	  => 'medium',
						   'label'	  => __("Email Subject", $this->_slug),
						   'tooltip' => __("Enter a subject for the export email.", $this->_slug)
						),
						// Set the email message.
						array(
						   'name'	  => 'export_email_message',
						   'type'	  => 'textarea',
						   'class'	  => 'medium',
						   'label'	  => __("Message", $this->_slug),
						   'tooltip' => __("Enter a message for the export email.", $this->_slug)
						),
						// Custom field to set the form fields to export.
						array(
							'label'   => "Form Fields",
							'type'	=> "export_form_feilds",
							'name'	=> "export_field",
							'tooltip' => "Select the fields you would like to include in the export. Caution: Make sure you are not sending any sensitive information."

						),
						// Set conditional logic.
						array(
							'name'			 => 'condition',
							'type'			 => 'feed_condition',
							'label'			 => __("Condition", $this->_slug),
							'tooltip'		  => __("Set conditional logic that must be met before sending the export.", $this->_slug),
							'checkbox_label' => __("Enable Condition", $this->_slug),
							'instructions'	 => __("Process this simple feed if", $this->_slug)
						),
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
				'export_email_to'  => __("Email Recipient", $this->_slug)
			);
		}

		/**
		 * Set/Remove cron jobs when list feed is toggled active/inactive.
		 *
		 * @since 1.0.0
		 */
		public function ajax_toggle_is_active() {
			$feed_id   = rgpost( 'feed_id' );
			$is_active = rgpost( 'is_active' );

			// Update the feed's status.
			$this->update_feed_active( $feed_id, $is_active );

			// Set/Remove cron job
			$this->schedule_cron_gfscheduledexport( $feed_id );

			die();
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

			$this->schedule_cron_gfscheduledexport( $feed_id );

			return $result;
		}

		/**
		 * Schedules the export
		 *
		 * @since 1.0.0
		 */
		public function schedule_cron_gfscheduledexport( $feed_id ) {

			// Collect the feed settings.
			$feed_data = parent::get_feed( $feed_id );
			$feed_settings = $feed_data['meta'];
			$time_frame = $feed_settings['export_schedule'];


			// Check the time frame and get the next time to schedule.
			switch ( $time_frame ) {
				case 'hourly':
					$next_time = floor( ( time() + 3600 ) / 3600 ) * 3600;
				break;
				case 'daily':
					$next_time = floor( ( time() + 86400 ) / 86400 ) * 86400;
				break;
				case 'weekly':
					$next_time = strtotime( 'next Monday', strtotime( 'today' ) );
				break;
				case 'monthly':
					$next_time = strtotime( 'first day of next month', strtotime( 'today' ) );
				break;
				default:
				// TODO: Default case to through an error.
				//add_feed_error();
			}

			// Check if the event is already been scheduled.
			if ( $timestamp = wp_next_scheduled( 'gfscheduledexport_cron_job', array( $feed_id ) ) ) {

				// Un-schedule...
				wp_unschedule_event( $timestamp, 'gfscheduledexport_cron_job', array( $feed_id ) );

			}

			// Check if the feed is active.
			if ( $feed_data['is_active'] ) {

				// and then re-schedule the cron event.//TODO update call
				wp_schedule_single_event( $next_time, 'gfscheduledexport_cron_job', array( $feed_id, $next_time ) );

			}

		}

		/**
		 * Fire on the cron job and request the function for exporting and sending the emails on the correct schedule.
		 *
		 * TODO update name
		 * @since 1.0.0
		 */
		public function gfscheduledexport_cron_job( $feed_id, $scheduled_time ){

			// Collect the feed settings.
			$feed_data = parent::get_feed( $feed_id );
			$feed_settings = $feed_data['meta'];
			$time_frame = $feed_settings['export_schedule'];

			// Check the time gap.
			$current_time = time();
			$time_gap = $current_time - $scheduled_time;

			// Set the first exports end time.
			$export_end = $scheduled_time;

			// Set the time missed.
			$time_missed = 0;

			// Check the time frame and get the next time to schedule.
			switch ( $time_frame ) {

				case 'hourly':

					// Find the start time from when the job was scheduled.
					$export_start = strtotime( '-1 hour', $export_end );

					// String used in the time gap loop below
					$time_frame_text = '+1 hour';

					// Seconds in 1 hour.
					$time_frame_sec = 3600;

				break;

				case 'daily':

					// Find the start time from when the job was scheduled.
					$export_start = strtotime( '-1 day', $export_end );

					// String used in the time gap loop below
					$time_frame_text = '+1 day';

					// Seconds in 1 days.
					$time_frame_sec = 86400;

				break;

				case 'weekly':

					// Find the start time from when the job was scheduled.
					$export_start = strtotime( 'last Monday', $export_end );

					// String used in the time gap loop below
					$time_frame_text = 'next Monday';

					// Seconds in 7 days.
					$time_frame_sec = 604800;

				break;

				case 'monthly':

					// Find the start time from when the job was scheduled.
					$export_start = strtotime( 'first day of last month', $export_end );

					// String used in the time gap loop below
					$time_frame_text = 'first day of next month';

					// Seconds in 30 days.
					$time_frame_sec = 2592000;

				break;

				default:
					// TODO: Default case to through an error.
					//add_feed_error();
			}

			// Get the number of days missed. This code should not need to run.
			$time_missed = (int) floor ( $time_gap / $time_frame_sec );

			// Run the first export and email that was scheduled.
			$this->export_email( $feed_id, $export_start, $export_end );

			if ( $time_missed > 1 ) {

				while ( $time_missed > 0 ) {

					// Update the export start and end time based on the set time frame.
					$export_start = $export_end;
					$export_end = strtotime( $time_frame_text, $export_start );

					// Run the export email for the missed time.
					$this->export_email( $feed_id, $export_start, $export_end );
				}
			}

			// Reschedule the event.
			$this->schedule_cron_gfscheduledexport( $feed_id );

		}

		/**
		 * Fire on the cron job and runs the email
		 *
		 * @since 1.0.0
		 */
		public function export_email ( $feed_id, $export_start, $export_end ) {

			// Get the feed setting an load them into the $_POST var to us the start_export() filter.
			$_POST = null;
			$feed_data = parent::get_feed( $feed_id );
			foreach( $feed_data['meta'] as $key => $value ) {
				$_POST["$key"] = $value;
			}

			// Call and collect the CSV data.
			$form = RGFormsModel::get_form_meta( $feed_data['form_id'] );
			ob_start();
			GFExport::start_export( $form );
			$data = ob_get_clean();

			// Load the CSV data into a file.
			$name_prefix = sanitize_title_with_dashes( $feed_data['meta']['export_feed_name'] ) . '-' . gmdate( 'Y-m-d', GFCommon::get_local_timestamp( time() ) ) . '_';
			$filename = tempnam( sys_get_temp_dir(), $name_prefix ) . '.csv';
			$attachment = file_put_contents( $filename , $data );

			// Send the email and attachment.
			$recipient = $feed_data['meta']['export_email_to'];
			$sender = $feed_data['meta']['export_email_from'];
			$headers[] = "From: $sender \r\n";
			$subject = $feed_data['meta']['export_email_subject'];
			$message = $feed_data['meta']['export_email_message'];
			wp_mail( $recipient, $subject, $message, $headers, $filename );

			// Delete the temp CSV file.
			@unlink( $filename );
		}

	} // END GFScheduledExport Class.

	new GFScheduledExport();
}
