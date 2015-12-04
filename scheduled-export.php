<?php
/*
Plugin Name: Gravity Forms Scheduled Entries Export
Plugin URI:
Description: Exports submitted entries and sends them as a .csv to an email address on a schedule.
Version: 1.0
Author: Hall Internet Marketing
Author URI: http://hallme.com
License: GPL2
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

//Make sure that gravity forms in installed and active
if (class_exists("GFForms")) {

	//Add the Gravity Fomrs Add-on Framework - http://www.gravityhelp.com/documentation/gravity-forms/extending-gravity-forms/add-on-framework/gfaddon/
	GFForms::include_feed_addon_framework();

    class GFScheduledExport extends GFFeedAddOn {

        //Basic plugin information
        protected $_version = "1.0";
        protected $_min_gravityforms_version = "1.7.9999";
        protected $_slug = "gfscheduledexport";
        protected $_path = "gravityformsscheduledexport/scheduled-export.php";
        protected $_full_path = __FILE__;
        protected $_title = "Gravity Forms Scheduled Entries Export";
        protected $_short_title = "Scheduled Export";

        public function pre_init(){
            parent::pre_init();
            // add tasks or filters here that you want to perform during the class constructor - before WordPress has been completely initialized
        }

        public function init(){
            parent::init();
            // add tasks or filters here that you want to perform both in the backend and frontend and for ajax requests
        }

        public function init_admin(){
            parent::init_admin();
            // add tasks or filters here that you want to perform only in admin
            add_action( 'gform_after_save_form', array( &$this, 'log_form_saved' ), 10, 2 );
        }

        public function init_frontend(){
            parent::init_frontend();
            // add tasks or filters here that you want to perform only in the front end
        }

        public function init_ajax(){
            parent::init_ajax();
            // add tasks or filters here that you want to perform only during ajax requests
        }


		/**
		 * Add a custom inputs for the field used on the form.
		 *
		 * TODO: Update the field name to get the name from the custom field settings
		 *
		 * @since    1.0.0
		 *
		 */
		public function settings_export_form_feilds(){

			//collect the form id from the schedule export settings page url for the current form
			$form_id = $_REQUEST['id'];
			$form = RGFormsModel::get_form_meta( $form_id );

			//collect an array the saved field settings
			$saved_settings = GFAddOn::get_setting( 'export_field' );

			//apply filter
			$form = apply_filters( "gform_form_export_page_{$form_id}", apply_filters( 'gform_form_export_page', $form ) );

			//collect and add the default export fields
			$form = GFExport::add_default_export_fields( $form );

			//loop through the fields and format all the inputs in to an array to be rendered as checkboxes
			if ( is_array( $form['fields'] ) ) {
				foreach ( $form['fields'] as $field ) {
					$inputs = $field->get_entry_inputs();
					if ( is_array( $inputs ) ) {
						foreach ( $inputs as $input ) {
							$choices[] = array(
								'label' => GFCommon::get_label( $field, $input['id'] ),
								'value' => $input['id'],
								'name' => 'export_field[]'
							);
						}
					} else if ( ! $field->displayOnly ) {
						$choices[] = array(
							'label' => GFCommon::get_label( $field ),
							'value' => $field->id,
							'name' => 'export_field[]'
						);
					}
				}
			}

			//loop variables
			$field_name = "export_field";
			$choice_count = 0;

		    ?>
			<ul id="export_field_list">
				<li>
					<input id="select_all" type="checkbox" onclick="jQuery('.gform_export_field').attr('checked', this.checked); jQuery('#gform_export_check_all').html(this.checked ? '<strong>Deselect All</strong>' : '<strong>Select All</strong>'); ">
					<label id="gform_export_check_all" for="select_all"><strong>Select All</strong></label>
				</li>
			<?php

				//loop through and output the choices
				foreach ( $choices as $choice ){

					$field_id = $field_name . '_' . $choice_count;
					$checked = in_array( $choice['value'], $saved_settings )  ? ' checked="checked"' : '';

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
		 * @since    1.0.0
		 *
		 */
		 public function feed_settings_fields() {
			// Collect the inputs to print using the API
            $inputs = array(
                // Main Settings
                array(
					'title' => __( "Scheduled Entries Export", $this->_slug ),
					'description' => __( "The settings below will automatically export new entries and send them to the emails below based on the set time frame.", $this->_slug ),
					'fields' => array(
						// Give the schedule a name
					    array(
                            'name'    => 'export_feed_name',
                            'type'    => 'text',
                            'class'   => 'small',
                            'label'   => __( "Name", $this->_slug ),
                            'tooltip' => __( "Enter a name to identify the schedule", $this->_slug )
                        ),
						// Set the time frame drop-down
						array(
                            'name'    => 'export_schedule',
                            'type'    => 'select',
                            'label'   => __( "Time Frame", $this->_slug ),
                            'tooltip' => __( "Set how frequently it the entries are exported and emailed", $this->_slug ),
                            'choices' => array(
                                array(
                                    'label' => __( "Hourly", $this->_slug ),
                                    'value' => 'hourly'
                                ),
                                array(
                                    'label' => __( "Twice Daily", $this->_slug ),
                                    'value' => 'twicedaily'
                                ),
                                array(
                                    'label' => __( "Daily", $this->_slug ),
                                    'value' => 'daily'
                                ),
                                array(
                                    'label' => __( "Weekly", $this->_slug ),
                                    'value' => 'weekly'
                                )
                            )
                        ),
                        // Set the destination email for the exported files
                        array(
							'name'	  => 'receive_email',
							'type'	  => 'text',
							'class'	  => 'medium',
							'label'	  => __( "Email Address", $this->_slug ),
							'tooltip' => __( "Enter a comma separated list of emails you would like to receive the exported entries file.", $this->_slug )
						),
						 array(
		                    'label'   => "Form Fields",
		                    'type'    => "export_form_feilds",
		                    'name'    => "export_field",
		                    'tooltip' => "Select the fields you would like to include in the export. Caution: Make sure you are not sending any sensitive information."

		                ),
						array(
                            'name'			 => 'condition',
                            'type'			 => 'feed_condition',
                            'label'			 => __( "Condition", $this->_slug ),
							'tooltip' 		 => __( "Set conditional logic that must be met before sending the export.", $this->_slug ),
                            'checkbox_label' => __( "Enable Condition", $this->_slug ),
                            'instructions'	 => __( "Process this simple feed if", $this->_slug )
                        )
                    )
                )
            );
            return $inputs;
        }

		/**
		 * Set the column names on the feed list
		 *
		 * @since    1.0.0
		 *
		 */
        public function feed_list_columns() {

            return array(
                'export_feed_name'  => __( "Name", $this->_slug ),
                'export_schedule' => __( "Time Frame", $this->_slug ),
                'email' 	=> __( "Email", $this->_slug )
            );
        }

        /**
		 * Customize the value of before it's rendered to the list
		 *
		 * @since    1.0.0
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
		 * TODO: finish mapping fields
		 * TODO: add csv file header
		 *
		 * @since    1.0.0
		 *
		 */
		function save_feed_settings( $feed_id, $form_id, $settings ) {

			if ( $feed_id ) {
				$this->update_feed_meta( $feed_id, $settings );
				$result = $feed_id;
			} else {
				$result = $this->insert_feed( $form_id, true, $settings );
			}


			//map the fields for the start_export() function
			$_POST['export_field'] = $_POST['_gaddon_setting_export_field'];

			$form = RGFormsModel::get_form_meta( $form_id );
			echo "<pre>";
			var_dump($_POST['export_field']);
			echo "</pre>";

			GFExport::start_export( $form );

			//For Testing
			//do_action( 'gform_post_export_entries', $form, $start_date, $end_date, $fields );

			return $result;
		}

	}

    new GFScheduledExport();

	/** TODO: Use alternative to that it can be sent at the first of the Week/Month
	 * Add time frame options to the cron schedule
	 *
	 * TODO: Check if this should be in the class and if show where it should init?
	 *
	 * @reference https://codex.wordpress.org/Function_Reference/wp_get_schedules
	 * @since    1.0.0
	 */
	// add_filter( 'cron_schedules', 'scheduled_export_cron_add_times' );
	function scheduled_export_cron_add_times( $schedules ) {
	 	// Adds once weekly to the existing schedules.
	 	$schedules['weekly'] = array(
	 		'interval' => 604800,
	 		'display' => __( "Weekly" )
	 	);
	 	return $schedules;
	}

}