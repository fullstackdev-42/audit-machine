<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com
 
 More info at: http://lazarusalliance.com
 ********************************************************************************/
	require('includes/init.php');
	
	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');

	require('includes/entry-functions.php');
	require('includes/users-functions.php');

	ob_clean(); //clean the output buffer

	$form_id = (int) trim($_REQUEST['form_id']);

	if(empty($form_id)){
		die("Invalid form ID.");
	}

	$ssl_suffix = la_get_ssl_suffix();

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	//check permission, is the user allowed to access this page?
	if(empty($_SESSION['la_user_privileges']['priv_administer'])){
		$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

		//this page need edit_form permission
		if(empty($user_perms['edit_form'])){
			$_SESSION['LA_DENIED'] = "You don't have permission to access this page.";
				
			header("Location: restricted.php");
			exit;
		}
	}

	$export_content = '';

	$query = "SELECT form_name FROM ".LA_TABLE_PREFIX."forms where form_id = ?";
	$params = array($form_id);
	
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);

	if(!empty($row)){
		$form_meta_obj = new StdClass();
		$form_meta_obj->form_id   		 = $form_id;
		$form_meta_obj->form_name 		 = $row['form_name'];
		$form_meta_obj->itauditmachine_version = $la_settings['itauditmachine_version'];
		$form_meta_obj->export_date 	 = date("Y-m-d H:i:s");
	}else{
		die("Error. Invalid Form ID.");
	}
	
	$form_meta_json  = json_encode($form_meta_obj);
	if(!empty($form_meta_json)){
		$export_content .= $form_meta_json."\n";
	}
	
	//export ap_form_elements
	$ap_form_elements_json = la_export_table_rows($dbh,'form_elements',$form_id);
	if(!empty($ap_form_elements_json)){
		$export_content .= $ap_form_elements_json."\n";
	}
	
	//export ap_element_options
	$ap_element_options_json = la_export_table_rows($dbh,'element_options',$form_id);
	if(!empty($ap_element_options_json)){
		$export_content .= $ap_element_options_json."\n";
	}
	
	//export ap_element_prices
	$ap_element_prices_json = la_export_table_rows($dbh,'element_prices',$form_id);
	if(!empty($ap_element_prices_json)){
		$export_content .= $ap_element_prices_json."\n";	
	}
	
	//export ap_email_logic
	$ap_email_logic_json = la_export_table_rows($dbh,'email_logic',$form_id);
	if(!empty($ap_email_logic_json)){
		$export_content .= $ap_email_logic_json."\n";	
	}

	//export ap_email_logic_conditions
	$ap_email_logic_conditions_json = la_export_table_rows($dbh,'email_logic_conditions',$form_id);
	if(!empty($ap_email_logic_conditions_json)){
		$export_content .= $ap_email_logic_conditions_json."\n";
	}
	
	//export ap_field_logic_conditions
	$ap_field_logic_conditions_json = la_export_table_rows($dbh,'field_logic_conditions',$form_id);
	if(!empty($ap_field_logic_conditions_json)){
		$export_content .= $ap_field_logic_conditions_json."\n";
	}
	
	//export ap_field_logic_elements
	$ap_field_logic_elements_json = la_export_table_rows($dbh,'field_logic_elements',$form_id);
	if(!empty($ap_field_logic_elements_json)){
		$export_content .= $ap_field_logic_elements_json."\n";
	}
	
	//export ap_grid_columns
	$ap_grid_columns_json = la_export_table_rows($dbh,'grid_columns',$form_id);
	if(!empty($ap_grid_columns_json)){
		$export_content .= $ap_grid_columns_json."\n";
	}
	
	//export ap_page_logic
	$ap_page_logic_json = la_export_table_rows($dbh,'page_logic',$form_id);
	if(!empty($ap_page_logic_json)){
		$export_content .= $ap_page_logic_json."\n";
	}

	//export ap_page_logic_conditions
	$ap_page_logic_conditions_json = la_export_table_rows($dbh,'page_logic_conditions',$form_id);
	if(!empty($ap_page_logic_conditions_json)){
		$export_content .= $ap_page_logic_conditions_json."\n";
	}

	//export ap_report_elements
	$ap_report_elements_json = la_export_table_rows($dbh,'report_elements',$form_id);
	if(!empty($ap_report_elements_json)){
		$export_content .= $ap_report_elements_json."\n";
	}

	//export ap_report_filters
	$ap_report_filters_json = la_export_table_rows($dbh,'report_filters',$form_id);
	if(!empty($ap_report_filters_json)){
		$export_content .= $ap_report_filters_json."\n";
	}

	//export ap_reports
	$ap_reports_json = la_export_table_rows($dbh,'reports',$form_id);
	if(!empty($ap_reports_json)){
		$export_content .= $ap_reports_json."\n";
	}

	//export ap_webhook_logic_conditions
	$ap_webhook_logic_conditions_json = la_export_table_rows($dbh,'webhook_logic_conditions',$form_id);
	if(!empty($ap_webhook_logic_conditions_json)){
		$export_content .= $ap_webhook_logic_conditions_json."\n";
	}

	//export ap_webhook_options
	$ap_webhook_options_json = la_export_table_rows($dbh,'webhook_options',$form_id);
	if(!empty($ap_webhook_options_json)){
		$export_content .= $ap_webhook_options_json."\n";
	}

	//export ap_webhook_parameters
	$ap_webhook_parameters_json = la_export_table_rows($dbh,'webhook_parameters',$form_id);
	if(!empty($ap_webhook_parameters_json)){
		$export_content .= $ap_webhook_parameters_json."\n";
	}

	//export ap_forms
	//we're exporting ap_forms on the last position for a purpose
	//so that when the form is being imported back, it would guarantee completed form
	$ap_forms_json 	 = la_export_table_rows($dbh,'forms',$form_id);
	if(!empty($ap_forms_json)){
		$export_content .= $ap_forms_json."\n";
	}

	$export_content = trim($export_content);

	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: public", false);
	header("Content-Description: File Transfer");
	header("Content-Type: text/plain");
	header("Content-Disposition: attachment; filename=\"form-{$form_id}.json\"");
	        
	$output_stream = fopen('php://output', 'w');
	fwrite($output_stream, $export_content);
	fclose($output_stream);
	

	/*********************************************************************************/
	/** Functions **/

	//export table rows into JSON data
	//each record into one line
	function la_export_table_rows($dbh,$table_name,$form_id){
		
		//get the data
		$complete_table_name = LA_TABLE_PREFIX.$table_name;
		
		$table_meta_obj = new StdClass();
		$table_meta_obj->table_name = $table_name;
		$table_meta_json = json_encode($table_meta_obj);

		$query  = "SELECT * FROM `{$complete_table_name}` WHERE form_id = ?";
		$params = array($form_id);

		$sth = la_do_query($query,$params,$dbh);
		
		$table_data_json = '';
		$unused_columns = array('aeo_id','aep_id','aec_id','alc_id','agc_id','apc_id','arf_id','wlc_id','awo_id','awp_id');

		while($row = la_do_fetch_result($sth)){
			foreach ($row as $column_name => $column_data) {
				if(in_array($column_name, $unused_columns)){
					continue;
				}

				$row_data[$column_name] = $column_data;
			}
			$table_data_json .= json_encode($row_data)."\n";
		}

		$table_data_json = trim($table_data_json);

		if(!empty($table_data_json)){
			$table_data_json = $table_meta_json."\n".$table_data_json;
		}

		return $table_data_json;		
	}

?>