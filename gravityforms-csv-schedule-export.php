<?php
/*
Plugin Name: Gravity Forms CVS Scheduled Export
Plugin URI:
Description: Exports a days worth of submissions and sends the csv export to an email address.
Version: 1.0
Author: Hall Internet Marketing
Author URI: http://hallme.com
License: GPL2
*/

/*
 * Create admin page where site admin can set which forms to export and who to send them to.
 * Add the admin page to the gravity forms submenu.
 * Look into if start_export() function needs to be modded
 * Create loop that calls the start_export() function and set the post vars for the forms that need to be exported
 * Create mail function within the loop to email CSV created by start_export() function
 */

// Hook for adding sub admin menus -- finish updating function
add_submenu_page($parent_menu["name"], __("Scheduled Export", "gravityforms"), __("Scheduled Export", "gravityforms"), $has_full_access ? "gform_full_access" : "gravityforms_export_entries", "gf_export", array("RGForms", "export_page"));

// action function for above hook
function mt_add_pages() {
    // Add a new submenu under Settings:
    add_options_page(__('Test Settings','menu-test'), __('Test Settings','menu-test'), 'manage_options', 'testsettings', 'mt_settings_page');

    // Add a new submenu under Tools:
    add_management_page( __('Test Tools','menu-test'), __('Test Tools','menu-test'), 'manage_options', 'testtools', 'mt_tools_page');

    // Add a new top-level menu (ill-advised):
    add_menu_page(__('Test Toplevel','menu-test'), __('Test Toplevel','menu-test'), 'manage_options', 'mt-top-level-handle', 'mt_toplevel_page' );

    // Add a submenu to the custom top-level menu:
    add_submenu_page('mt-top-level-handle', __('Test Sublevel','menu-test'), __('Test Sublevel','menu-test'), 'manage_options', 'sub-page', 'mt_sublevel_page');

    // Add a second submenu to the custom top-level menu:
    add_submenu_page('mt-top-level-handle', __('Test Sublevel 2','menu-test'), __('Test Sublevel 2','menu-test'), 'manage_options', 'sub-page2', 'mt_sublevel_page2');
}

// mt_settings_page() displays the page content for the Test settings submenu
function mt_settings_page() {
    echo "<h2>" . __( 'Test Settings', 'menu-test' ) . "</h2>";
}

// mt_tools_page() displays the page content for the Test Tools submenu
function mt_tools_page() {
    echo "<h2>" . __( 'Test Tools', 'menu-test' ) . "</h2>";
}

// mt_toplevel_page() displays the page content for the custom Test Toplevel menu
function mt_toplevel_page() {
    echo "<h2>" . __( 'Test Toplevel', 'menu-test' ) . "</h2>";
}

// mt_sublevel_page() displays the page content for the first submenu
// of the custom Test Toplevel menu
function mt_sublevel_page() {
    echo "<h2>" . __( 'Test Sublevel', 'menu-test' ) . "</h2>";
}

// mt_sublevel_page2() displays the page content for the second submenu
// of the custom Test Toplevel menu
function mt_sublevel_page2() {
    echo "<h2>" . __( 'Test Sublevel2', 'menu-test' ) . "</h2>";
}



/*This methoid might not work it is based off piggy backing on the export class*/
$_POST["export_form"]; //id of form
$_POST["export_lead"]; //Set to type of type of export in this case it should be "Download Export File"
$_POST["export_field"]; //array of field ids and slugs

$_POST["export_date_start"]; //date in Y-d-m format. example"2013-10-02"
$_POST["export_date_end"];

$_POST["rg_start_export_nonce"]; //? idk

//maybe_export($form); //call this function to start the export

//forms
$form_id=$_POST["export_form"];
$form = RGFormsModel::get_form_meta($form_id);

//Create file
$fileatt_type = "text/csv";
$filename = sanitize_title_with_dashes($form["title"]) . "-" . gmdate("Y-m-d", GFCommon::get_local_timestamp(time())) . ".csv";
$file_size = filesize($filename);

$charset = get_option('blog_charset');


$handle = fopen($filename, "r");
$content = fread($handle, $file_size);

fclose($handle);

$content = chunk_split(base64_encode($content));

$message = "<html>
<head>
<title>List of New Price Changes</title>
</head>
<body><table><tr><td>MAKE</td></tr></table></body></html>";

$uid = md5(uniqid(time()));

#$header = "From: ".$from_name." <".$from_mail.">\r\n";
#$header .= "Reply-To: ".$replyto."\r\n";
$header .= "MIME-Version: 1.0\r\n";
$header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
$header .= "This is a multi-part message in MIME format.\r\n";
$header .= "--".$uid."\r\n";
$header .= "Content-type:text/html; charset=iso-8859-1\r\n";
$header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$header .= $message."\r\n\r\n";
$header .= "--".$uid."\r\n";
$header .= "Content-Type: text/csv; name=\"".$filename."\"\r\n"; // use diff. tyoes here
$header .= "Content-Transfer-Encoding: base64\r\n";
$header .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
$header .= $content."\r\n\r\n";
$header .= "--".$uid."--";

mail($to, $subject, $message, $header);


    public static function start_export($form){

        $form_id = $form["id"];
        $fields = $_POST["export_field"];

        $start_date = empty($_POST["export_date_start"]) ? "" : self::get_gmt_date($_POST["export_date_start"] . " 00:00");
        $end_date = empty($_POST["export_date_end"]) ? "" : self::get_gmt_date($_POST["export_date_end"] . " 23:59:59");

        GFCommon::log_debug("start date: {$start_date}");
        GFCommon::log_debug("end date: {$end_date}");

        $form = self::add_default_export_fields($form);

        $entry_count = RGFormsModel::get_lead_count($form_id, "", null, null, $start_date, $end_date);

        $page_size = 200;
        $offset = 0;

        //Adding BOM marker for UTF-8
        $lines = chr(239) . chr(187) . chr(191);

        // set the separater
        $separator = apply_filters('gform_export_separator_' . $form_id, apply_filters('gform_export_separator', ',', $form_id), $form_id);

        $field_rows = self::get_field_row_count($form, $fields, $entry_count);

        //writing header
        foreach($fields as $field_id){
            $field = RGFormsModel::get_field($form, $field_id);
            $value = str_replace('"', '""', GFCommon::get_label($field, $field_id)) ;

            $subrow_count = isset($field_rows[$field_id]) ? intval($field_rows[$field_id]) : 0;
            if($subrow_count == 0){
                $lines .= '"' . $value . '"' . $separator;
            }
            else{
                for($i = 1; $i <= $subrow_count; $i++){
                    $lines .= '"' . $value . " " . $i . '"' . $separator;
                }
            }
        }
        $lines = substr($lines, 0, strlen($lines)-1) . "\n";

        //paging through results for memory issues
        while($entry_count > 0){
            $leads = RGFormsModel::get_leads($form_id,"date_created", "DESC", "", $offset, $page_size, null, null, false, $start_date, $end_date);

            foreach($leads as $lead){
                foreach($fields as $field_id){
                    switch($field_id){
                        case "date_created" :
                            $lead_gmt_time = mysql2date("G", $lead["date_created"]);
                            $lead_local_time = GFCommon::get_local_timestamp($lead_gmt_time);
                            $value = date_i18n("Y-m-d H:i:s", $lead_local_time, true);
                        break;
                        default :
                            $long_text = "";
                            if(strlen($lead[$field_id]) >= (GFORMS_MAX_FIELD_LENGTH-10)){
                                $long_text = RGFormsModel::get_field_value_long($lead, $field_id, $form);
                            }

                            $value = !empty($long_text) ? $long_text : $lead[$field_id];
                            $value = apply_filters("gform_export_field_value", $value, $form_id, $field_id, $lead);
                        break;
                    }

                    if(isset($field_rows[$field_id])){
                        $list = empty($value) ? array() : unserialize($value);

                        foreach($list as $row){
                            $row_values = array_values($row);
                            $row_str = implode("|", $row_values);
                            $lines .= '"' . str_replace('"', '""', $row_str) . '"' . $separator;
                        }

                        //filling missing subrow columns (if any)
                        $missing_count = intval($field_rows[$field_id]) - count($list);
                        for($i=0; $i<$missing_count; $i++)
                            $lines .= '""' . $separator;

                    }
                    else{
                        $value = maybe_unserialize($value);
                        if(is_array($value))
                            $value = implode("|", $value);

                        $lines .= '"' . str_replace('"', '""', $value) . '"' . $separator;
                    }
                }
                $lines = substr($lines, 0, strlen($lines)-1);
                $lines.= "\n";
            }

            $offset += $page_size;
            $entry_count -= $page_size;

            if ( !seems_utf8( $lines ) )
                $lines = utf8_encode( $lines );

            echo $lines;
            $lines = "";
        }
    }
