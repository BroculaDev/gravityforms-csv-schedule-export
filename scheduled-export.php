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
		 * Add form settings page with schedule export options.
		 *
		 * TODO: Add default email address - admin email if empty?
		 *
		 * @since    1.0.0
		 *
		 */
		 public function feed_settings_fields() {

            //collect the form id from the schedule export settings page url for the current form
			$form_id = $_REQUEST['id'];
			$form = GFAPI::get_form( $form_id ); // Get the form obj
			$form = apply_filters( "gform_form_export_page_{$form_id}", apply_filters( 'gform_form_export_page', $form ) );

			//collect filter settings TODO: these are currently not used.
			//$filter_settings      = GFCommon::get_field_filter_settings( $form );
			//$filter_settings_json = json_encode( $filter_settings );

			//collect and add the default export fields
			$form = GFExport::add_default_export_fields( $form );
			$form_fields = $form['fields'];

			//loop through the fields and format all the inputs in to an array to be rendered as checkboxes
			foreach($form_fields as $field) {
				$inputs = $field->get_entry_inputs();
				if ( is_array( $inputs ) ) {
					foreach ( $inputs as $input ) {
						$choices[] = array(
							'label' => GFCommon::get_label( $field, $input['id'] ),
							'name' => $input['id'],
						);
					}
				} else if ( ! $field->displayOnly ) {
					$choices[] = array(
						'label' => GFCommon::get_label( $field ),
						'name' => $field->id,
					);
				}
			}

			// Collect the inputs to print using the API
            $inputs = array(
                // Main Settings
                array(
					'title' => __( "Scheduled Entries Export", $this->_slug ),
					'description' => __( "The settings below will automatically export new entries and send them to the emails below based on the set time frame.", $this->_slug ),
					'fields' => array(
						// Give the schedule a name
					    array(
                            'name'    => 'feedName',
                            'type'    => 'text',
                            'class'   => 'small',
                            'label'   => __( "Name", $this->_slug ),
                            'tooltip' => __( "Enter a name to identify the schedule", $this->_slug )
                        ),
						// Set the time frame drop-down
						array(
                            'name'    => 'timeFrame',
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
                                ),
                                array(
                                    'label' => __( "Monthly", $this->_slug ),
                                    'value' => 'monthly'
                                )
                            )
                        ),
                        // Set the destination email for the exported files
                        array(
							'name'	  => 'email',
							'type'	  => 'text',
							'class'	  => 'medium',
							'label'	  => __( "Email Address", $this->_slug ),
							'tooltip' => __( "Enter a comma separated list of emails you would like to receive the exported entries file.", $this->_slug )
						),
						array(
							'name'    => 'fields',
							'type'    => 'checkbox',
							'label'   => __( "Form Fields", $this->_slug ),
							'tooltip' => __( "Select the fields you would like to include in the export. Caution: Make sure you are not sending any sensitive information.", $this->_slug ),
							'choices' => $choices
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
        } //END feed_settings_fields();

		/**
		 * Set the column names on the feed list
		 *
		 * @since    1.0.0
		 *
		 */
        public function feed_list_columns() {
            return array(
                'feedName'  => __( "Name", $this->_slug ),
                'timeFrame' => __( "Time Frame", $this->_slug ),
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

        /**
		 * Add custom script
		 *
		 * @since    1.0.0
		 *

        public function scripts() {
            $scripts = array(
                array('handle'  => 'my_script_js',
                      'src'     => $this->get_base_url() . '/js/my_script.js',
                      'version' => $this->_version,
                      'deps'    => array("jquery"),
                      "strings" => array(
                          'first'  => __( "First Choice", $this->_slug ),
                          'second' => __( "Second Choice", $this->_slug ),
                          'third'  => __( "Third Choice", $this->_slug )
                      ),
                      'enqueue' => array(
                          array(
                              'admin_page' => array("form_settings"),
                              'tab'        => $this->_slug
                          )
                      )
                ),
            );
            return array_merge(parent::scripts(), $scripts);
        }
		*/

		/**
		 * Add custom styles
		 *
		 * @since    1.0.0
		 *

        public function styles() {
            $styles = array(
                array('handle'  => 'my_styles_css',
                      'src'     => $this->get_base_url() . '/css/my_styles.css',
                      'version' => $this->_version,
                      'enqueue' => array(
                          array('field_types' => array("poll"))
                      )
                )
            );
            return array_merge(parent::styles(), $styles);
        }
		*/

        /**
		 * Process the Feed
		 *
		 * @since    1.0.0
		 *

        public function process_feed($feed, $entry, $form){
            $feedName = $feed["meta"]["feedName"];
            $timeFrame = $feed["meta"]["timeFrame"];
            $email = $feed["meta"]["email"];
            $fields = $feed["meta"]["fields"];
            $condition = $feed["meta"]["condition"];
        }
        */
    }

    new GFScheduledExport();

	/**
	 * Add time frame options to the cron schedule
	 *
	 * TODO: Check if this should be in the class and if show where it should init?
	 *
	 * @reference https://codex.wordpress.org/Function_Reference/wp_get_schedules
	 * @since    1.0.0
	 */
	add_filter( 'cron_schedules', 'scheduled_export_cron_add_times' );
	function scheduled_export_cron_add_times( $schedules ) {
	 	// Adds once weekly to the existing schedules.
	 	$schedules['weekly'] = array(
	 		'interval' => 604800,
	 		'display' => __( "Weekly", $this->_slug )
	 	);
	 	// Add monthly
	 	$schedules['monthly'] = array(
	 		'interval' => 2592000,
	 		'display' => __( "Monthly", $this->_slug )
	 	);
	 	return $schedules;
	}

	//TODO: Schedule the Cron Event
	//TODO: Review the Entries Export and add to Cron Event
	//TODO: Add Message Last Schedule Run
}