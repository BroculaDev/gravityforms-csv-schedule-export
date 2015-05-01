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
    GFForms::include_addon_framework();

    class GFScheduledExport extends GFAddOn {

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
		 * TODO: Add default email address
		 *
		 * @since    1.0.0
		 *
		 */
        public function form_settings_fields($form) {
            return array(
                array(
					'title' => "Scheduled Entries Export",
					'description' => "The settings below will automatically export new entries and send them to the emails below based on the set time frame.",
					'fields' => array(
						// Activate the schedule field
						array(
							'label'   => "Activate the Schedule",
							'type'    => "checkbox",
							'name'    => "enabled",
							'tooltip' => "Enabling the schedule based on the sets below. This runs off WP Cron.",
							'choices' => array(
								array(
									'label' => "",
									'name'  => "enabled"
								)
							)
						),
						// Set the time frame drop-down
						array(
                            'label'   => "Time Frame",
                            'type'    => "select",
                            'name'    => "time_frame",
                            'tooltip' => "Set how frequently it the entries are exported and emailed",
                            'choices' => array(
                                array(
                                    'label' => "Hourly",
                                    'value' => "hourly"
                                ),
                                array(
                                    'label' => "Twice Daily",
                                    'value' => "twicedaily"
                                ),
                                array(
                                    'label' => "Daily",
                                    'value' => "daily"
                                ),
                                array(
                                    'label' => "Weekly",
                                    'value' => "weekly"
                                ),
                                array(
                                    'label' => "Monthly - Every 30 Days",
                                    'value' => "monthly"
                                )
                            )
                        ),
                        // Set the destination email for the exported files
                        array(
							'type'          => "text",
							'name'          => "email",
							'label'         => "Email Address",
							'class'         => "medium",
							'tooltip'       => "Enter a comma separated list of emails you would like to receive the exported entries file."
					   )
                    )
                )
            );
        } //END form_settings_fields();

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
	 		'display' => __( "Weekly" )
	 	);
	 	// Add monthly
	 	$schedules['monthly'] = array(
	 		'interval' => 2592000,
	 		'display' => __( "Monthly - Every 30 Days" )
	 	);
	 	return $schedules;
	}
}