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

            $inputs = array(
                array(
					'title' => "Scheduled Entries Export",
					'description' => "The settings below will automatically export new entries and send them to the emails below based on the set time frame.",
					'fields' => array(
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
					   ),
						array(
							'label'   => "Form Fields",
							'type'    => "checkbox",
							'name'    => "fields",
							'tooltip' => "Select the fields you would like to include in the export. Caution: Make sure you are not sending any sensitive information.",
							'choices' => $choices
						),
						array(
                            "name" => "condition",
                            "label" => __("Condition", "simplefeedaddon"),
                            "type" => "feed_condition",
                            "checkbox_label" => __('Enable Condition', 'simplefeedaddon'),
                            "instructions" => __("Process this simple feed if", "simplefeedaddon")
                        ),
                    )
                )
            );
            return $inputs;
        } //END feed_settings_fields();

        protected function feed_list_columns() {
            return array(
                'feedName' => __('Name', 'simplefeedaddon'),
                'mytextbox' => __('My Textbox', 'simplefeedaddon')
            );
        }
        // customize the value of mytextbox before it's rendered to the list
        public function get_column_value_mytextbox($feed){
            return "<b>" . $feed["meta"]["mytextbox"] ."</b>";
        }
        public function plugin_settings_fields() {
            return array(
                array(
                    "title"  => "Simple Add-On Settings",
                    "fields" => array(
                        array(
                            "name"    => "textbox",
                            "tooltip" => "This is the tooltip",
                            "label"   => "This is the label",
                            "type"    => "text",
                            "class"   => "small"
                        )
                    )
                )
            );
        }
        public function scripts() {
            $scripts = array(
                array("handle"  => "my_script_js",
                      "src"     => $this->get_base_url() . "/js/my_script.js",
                      "version" => $this->_version,
                      "deps"    => array("jquery"),
                      "strings" => array(
                          'first'  => __("First Choice", "simplefeedaddon"),
                          'second' => __("Second Choice", "simplefeedaddon"),
                          'third'  => __("Third Choice", "simplefeedaddon")
                      ),
                      "enqueue" => array(
                          array(
                              "admin_page" => array("form_settings"),
                              "tab"        => "simplefeedaddon"
                          )
                      )
                ),
            );
            return array_merge(parent::scripts(), $scripts);
        }
        public function styles() {
            $styles = array(
                array("handle"  => "my_styles_css",
                      "src"     => $this->get_base_url() . "/css/my_styles.css",
                      "version" => $this->_version,
                      "enqueue" => array(
                          array("field_types" => array("poll"))
                      )
                )
            );
            return array_merge(parent::styles(), $styles);
        }
        public function process_feed($feed, $entry, $form){
            $feedName = $feed["meta"]["feedName"];
            $mytextbox = $feed["meta"]["mytextbox"];
            $checkbox = $feed["meta"]["mycheckbox"];
            $mapped_email = $feed["meta"]["mappedFields_email"];
            $mapped_name = $feed["meta"]["mappedFields_name"];
            $email = $entry[$mapped_email];
            $name = $entry[$mapped_name];
        }
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

	//TODO: Schedule the Cron Event
	//TODO: Review the Entries Export and add to Cron Event
	//TODO: Add Message Last Schedule Run
}

// SAMPLE ----------------------DELET BELOW -------------------------------------
if (class_exists("GFForms")) {
    GFForms::include_feed_addon_framework();
    class GFSimpleFeedAddOn extends GFFeedAddOn {
        protected $_version = "1.0";
        protected $_min_gravityforms_version = "1.7.9999";
        protected $_slug = "simplefeedaddon";
        protected $_path = "simplefeedaddon/simplefeedaddon.php";
        protected $_full_path = __FILE__;
        protected $_title = "Gravity Forms Simple Feed Add-On";
        protected $_short_title = "Simple Feed Add-On";

        public function feed_settings_fields() {
            return array(
                array(
                    "title"  => "Simple Feed Settings",
                    "fields" => array(
                        array(
                            "label"   => "Feed name",
                            "type"    => "text",
                            "name"    => "feedName",
                            "tooltip" => "This is the tooltip",
                            "class"   => "small"
                        ),
                        array(
                            "label"   => "Textbox",
                            "type"    => "text",
                            "name"    => "mytextbox",
                            "tooltip" => "This is the tooltip",
                            "class"   => "small"
                        ),
                        array(
                            "label"   => "My checkbox",
                            "type"    => "checkbox",
                            "name"    => "mycheckbox",
                            "tooltip" => "This is the tooltip",
                            "choices" => array(
                                array(
                                    "label" => "Enabled",
                                    "name"  => "mycheckbox"
                                )
                            )
                        ),
                        array(
                            "name" => "mappedFields",
                            "label" => "Map Fields",
                            "type" => "field_map",
                            "field_map" => array(   array("name" => "email", "label" => "Email", "required" => 0),
                                                    array("name" => "name", "label" => "Name", "required" => 0)
                            )
                        ),
                        array(
                            "name" => "condition",
                            "label" => __("Condition", "simplefeedaddon"),
                            "type" => "feed_condition",
                            "checkbox_label" => __('Enable Condition', 'simplefeedaddon'),
                            "instructions" => __("Process this simple feed if", "simplefeedaddon")
                        ),
                    )
                )
            );
        }
        protected function feed_list_columns() {
            return array(
                'feedName' => __('Name', 'simplefeedaddon'),
                'mytextbox' => __('My Textbox', 'simplefeedaddon')
            );
        }
        // customize the value of mytextbox before it's rendered to the list
        public function get_column_value_mytextbox($feed){
            return "<b>" . $feed["meta"]["mytextbox"] ."</b>";
        }
        public function plugin_settings_fields() {
            return array(
                array(
                    "title"  => "Simple Add-On Settings",
                    "fields" => array(
                        array(
                            "name"    => "textbox",
                            "tooltip" => "This is the tooltip",
                            "label"   => "This is the label",
                            "type"    => "text",
                            "class"   => "small"
                        )
                    )
                )
            );
        }
        public function scripts() {
            $scripts = array(
                array("handle"  => "my_script_js",
                      "src"     => $this->get_base_url() . "/js/my_script.js",
                      "version" => $this->_version,
                      "deps"    => array("jquery"),
                      "strings" => array(
                          'first'  => __("First Choice", "simplefeedaddon"),
                          'second' => __("Second Choice", "simplefeedaddon"),
                          'third'  => __("Third Choice", "simplefeedaddon")
                      ),
                      "enqueue" => array(
                          array(
                              "admin_page" => array("form_settings"),
                              "tab"        => "simplefeedaddon"
                          )
                      )
                ),
            );
            return array_merge(parent::scripts(), $scripts);
        }
        public function styles() {
            $styles = array(
                array("handle"  => "my_styles_css",
                      "src"     => $this->get_base_url() . "/css/my_styles.css",
                      "version" => $this->_version,
                      "enqueue" => array(
                          array("field_types" => array("poll"))
                      )
                )
            );
            return array_merge(parent::styles(), $styles);
        }
        public function process_feed($feed, $entry, $form){
            $feedName = $feed["meta"]["feedName"];
            $mytextbox = $feed["meta"]["mytextbox"];
            $checkbox = $feed["meta"]["mycheckbox"];
            $mapped_email = $feed["meta"]["mappedFields_email"];
            $mapped_name = $feed["meta"]["mappedFields_name"];
            $email = $entry[$mapped_email];
            $name = $entry[$mapped_name];
        }
    }
    new GFSimpleFeedAddOn();
}