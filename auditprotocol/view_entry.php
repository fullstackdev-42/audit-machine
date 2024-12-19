<?php
/***************
IT Audit Machine
  
Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
permission from http://lazarusalliance.com
 
More info at: http://lazarusalliance.com
********************
/*****************************************************************************************************************************/
	
require('includes/init.php');
require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/check-session.php');
require('includes/language.php');
require('includes/entry-functions.php');
require('includes/post-functions.php');
require('includes/users-functions.php');
require_once("../itam-shared/includes/helper-functions.php");

$form_id = (int) trim($_GET['form_id']);
$company_id = (int) trim($_GET['company_id']);
$entry_id = (int) trim($_GET['entry_id']);

//check permission, is the user allowed to access this page?
if(empty($_SESSION['la_user_privileges']['priv_administer'])){
	$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

	//this page need edit_entries or view_entries permission
	if(empty($user_perms['edit_entries']) && empty($user_perms['view_entries'])){
		$_SESSION['LA_DENIED'] = "You don't have permission to access this page.";

		$ssl_suffix = la_get_ssl_suffix();
		header("Location: restricted.php");
		exit;
	}
}

if(empty($form_id) || empty($entry_id)){
	die("Invalid Request");
}

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

$props = array('form_enable_template_wysiwyg', 'form_template_wysiwyg_id');
$form_properties = la_get_form_properties($dbh,$form_id,$props);
	
/***************************************/
/*			fetch all company		   */
/***************************************/
$query_com = "SELECT `client_id`, `company_name` FROM ".LA_TABLE_PREFIX."ask_clients WHERE `client_id` != ? ORDER BY `company_name`";
$sth_com = la_do_query($query_com, array($company_id), $dbh);
$select_ent = '<option value="0">Select</option>';
while($row = la_do_fetch_result($sth_com)){
	$select_ent .= '<option value="'.$row['client_id'].'">'.$row['company_name'].'</option>';
}
	//get company_name
	$company_name = "";
	if(strlen($company_id) > 10) {
		$company_name = "Administrator";
	} else {
		$query = "SELECT `company_name` FROM ".LA_TABLE_PREFIX."ask_clients WHERE client_id = ?";
		$sth = la_do_query($query, array($company_id), $dbh);
		$row = la_do_fetch_result($sth);
		if(!empty($row['company_name'])) {
			$company_name = $row['company_name'];
		} else {
			$company_name = "Administrator";
		}
	}
	//get previous and next entry_ids for the company
	$query = "SELECT entry_id AS current_entry_id,
				(SELECT entry_id FROM ".LA_TABLE_PREFIX."form_{$form_id} s1
					WHERE s1.entry_id < s.entry_id AND s1.company_id = s.company_id
					ORDER BY entry_id DESC LIMIT 1) AS previous_entry_id,
				(SELECT entry_id FROM ".LA_TABLE_PREFIX."form_{$form_id} s2
					WHERE s2.entry_id > s.entry_id AND s2.company_id = s.company_id
					ORDER BY entry_id ASC LIMIT 1) AS next_entry_id
			FROM ".LA_TABLE_PREFIX."form_{$form_id} s WHERE s.company_id = ? AND s.entry_id = ? GROUP BY entry_id";
	$sth = la_do_query($query, array($company_id, $entry_id), $dbh);
	$row = la_do_fetch_result($sth);
	$previous_entry_id = !empty($row['previous_entry_id']) ? $row['previous_entry_id'] : null;
	$next_entry_id = !empty($row['next_entry_id']) ? $row['next_entry_id'] : null;

	//get entry information (date created/updated/ip address/resume key)

	$statusElementArr = array();
	
	$sql_query = "SELECT `indicator`, `element_id` FROM `".LA_TABLE_PREFIX."element_status_indicator` WHERE `form_id` = ? AND `company_id` = ? AND `entry_id` = ?";
	$result = la_do_query($sql_query, array($form_id, $company_id, $entry_id),$dbh);
	
	while($row=la_do_fetch_result($result)){
		$statusElementArr[$row['element_id']] = $row['indicator'];
	}

	$query  = "SELECT * FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE `company_id` = ? AND `entry_id` = ?";
	$sth = la_do_query($query, array($company_id, $entry_id), $dbh);
	
	$totalScore = 0;
	$entry_data = array();
	$datesArr   = array();
	$reverseScoreArr = array();
	$scoreArr = array();
	$formIdArr = array($form_id);

	while($row = la_do_fetch_result($sth)){
		$entry_data[$row['field_name']] = htmlspecialchars($row['data_value'],ENT_QUOTES);
		if($row['field_name'] == 'date_created'){
			if(!empty($row['field_score'])){
				$datesArr = explode(",", trim($row['field_score']));
			}
		}else{
			if (($row['field_score'] != "") && ($row['field_name'] != "ip_address")) {
				if(strpos($row["field_name"], "other") !== false) {
					$query_default_value = "SELECT * FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE `company_id` = ? AND `entry_id` = ? AND `field_name` = ?";
					$sth_default_value = la_do_query($query_default_value, array($company_id, $entry_id, str_replace("_other", "", $row["field_name"])), $dbh);
					$res_default_value = la_do_fetch_result($sth_default_value);
					
					if(!($res_default_value)){
						$field_score = explode(",", $row['field_score']);
						if(isset($row["data_value"]) && $row["data_value"] !="") {
							for($i=count($field_score)-1; $i>-1; $i--){
								$reverseScoreArr[count($field_score)-1-$i] += (float) $field_score[$i];
							}
							$totalScore += (float) end(explode(",", trim($row['field_score'])));
						}
					} else {
						if(isset($res_default_value["data_value"]) && $res_default_value["data_value"] == "0"){
							$field_score = explode(",", $row['field_score']);
							if(isset($row["data_value"]) && $row["data_value"] !="") {
								for($i=count($field_score)-1; $i>-1; $i--){
									$reverseScoreArr[count($field_score)-1-$i] += (float) $field_score[$i];
								}
								$totalScore += (float) end(explode(",", trim($row['field_score'])));
							}
						}
					}
				} else {
					$field_score = explode(",", $row['field_score']);
					if(isset($row["data_value"]) && $row["data_value"] !="" && $row["data_value"] !="0") {
						for($i=count($field_score)-1; $i>-1; $i--){
							$reverseScoreArr[count($field_score)-1-$i] += (float) $field_score[$i];
						}
						$totalScore += (float) end(explode(",", trim($row['field_score'])));
					}
				}
			}
		}
	}

	//get scores from cascaded sub forms
	$query_cascade_forms = "SELECT `element_default_value` FROM ".LA_TABLE_PREFIX."form_elements WHERE `form_id` = ? AND `element_type` = ?";
	$sth_cascade_forms = la_do_query($query_cascade_forms, array($form_id, "casecade_form"), $dbh);
	while($row_cascade_form = la_do_fetch_result($sth_cascade_forms)) {
		array_push($formIdArr, $row_cascade_form['element_default_value']);
		$query  = "SELECT * FROM ".LA_TABLE_PREFIX."form_{$row_cascade_form['element_default_value']} WHERE `company_id` = ? AND `entry_id` = ?";
		$params = array();
		$sth = la_do_query($query, array($company_id, $entry_id), $dbh);
		while($row = la_do_fetch_result($sth)){
			if($row['field_name'] != 'date_created'){
				if (($row['field_score'] != "") && ($row['field_name'] != "ip_address")) {
					if(strpos($row["field_name"], "other") !== false) {
						$query_default_value = "SELECT * FROM ".LA_TABLE_PREFIX."form_{$row_cascade_form['element_default_value']} WHERE `company_id` = ? AND `entry_id` = ? AND `field_name` = ?";
						$sth_default_value = la_do_query($query_default_value, array($company_id, $entry_id, str_replace("_other", "", $row["field_name"])), $dbh);
						$res_default_value = la_do_fetch_result($sth_default_value);
						
						if(!($res_default_value)){
							$field_score = explode(",", $row['field_score']);
							if(isset($row["data_value"]) && $row["data_value"] !="") {
								for($i=count($field_score)-1; $i>-1; $i--){
									$reverseScoreArr[count($field_score)-1-$i] += (float) $field_score[$i];
								}
								$totalScore += (float) end(explode(",", trim($row['field_score'])));
							}
						} else {
							if(isset($res_default_value["data_value"]) && $res_default_value["data_value"] == "0"){
								$field_score = explode(",", $row['field_score']);
								if(isset($row["data_value"]) && $row["data_value"] !="") {
									for($i=count($field_score)-1; $i>-1; $i--){
										$reverseScoreArr[count($field_score)-1-$i] += (float) $field_score[$i];
									}
									$totalScore += (float) end(explode(",", trim($row['field_score'])));
								}
							}
						}
					} else {
						$field_score = explode(",", $row['field_score']);
						if(isset($row["data_value"]) && $row["data_value"] !="" && $row["data_value"] !="0") {
							for($i=count($field_score)-1; $i>-1; $i--){
								$reverseScoreArr[count($field_score)-1-$i] += (float) $field_score[$i];
							}
							$totalScore += (float) end(explode(",", trim($row['field_score'])));
						}
					}
				}
			}
		}
	}
	$scoreKeys = array_keys($reverseScoreArr);
	$scoreValues = array_values($reverseScoreArr);
	$reversed = array_reverse($scoreValues);
	$scoreArr = array_combine($scoreKeys, $reversed);

	$show_approval_deny_table = false;
	if( array_key_exists('approval_status', $entry_data) ) {
		//means approval/deny logic enabled
		$show_approval_deny_table = true;
	}
	$tArr = array();
	if(count($datesArr) > 1){
		foreach($datesArr as $key => $value){
			$tArr[] = strtotime($value);
		}
	}
	
	$date_created    = date("m/d/Y H:i:s", strtotime($entry_data['date_created']));
	$date_updated    = '&nbsp;';
	
	if(count($tArr) > 0 && !empty($tArr)){
		$date_updated = date("m/d/Y H:i:s", max($tArr));
	}
	
	$ip_address   	 = $entry_data['ip_address'];
	$entry_status 	 = $entry_data['status'];
	$form_resume_key = '';//$row['resume_key'];

	$is_incomplete_entry = false;
	if($entry_status == 2){
		$is_incomplete_entry = true;
	}

	if($is_incomplete_entry && !empty($form_resume_key)){
		$form_resume_url = "portal/view.php?id={$form_id}&entry_id={$entry_id}&la_resume={$form_resume_key}";
	}
	
	$form_full_name = '';
	//get form name
	$query 	= "select 
					 form_name,
					 payment_enable_merchant,
					 payment_merchant_type,
					 payment_price_type,
					 payment_price_amount,
					 payment_currency,
					 payment_ask_billing,
					 payment_ask_shipping,
					 payment_enable_tax,
					 payment_tax_rate,
					 payment_enable_discount,
					 payment_discount_type,
					 payment_discount_amount,
					 payment_discount_element_id  
			     from 
			     	 ".LA_TABLE_PREFIX."forms 
			    where 
			    	 form_id = ?";
	$params = array($form_id);
	
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);
	
	if(!empty($row)){
		$form_full_name = $row['form_name'];
		
		$row['form_name'] = la_trim_max_length($row['form_name'],65);
		$form_name = htmlspecialchars($row['form_name']);
		$payment_enable_merchant = (int) $row['payment_enable_merchant'];
		
		if($payment_enable_merchant < 1){
			$payment_enable_merchant = 0;
		}
		
		$payment_price_amount = (double) $row['payment_price_amount'];
		$payment_merchant_type = $row['payment_merchant_type'];
		$payment_price_type = $row['payment_price_type'];
		$form_payment_currency = strtoupper($row['payment_currency']);
		$payment_ask_billing = (int) $row['payment_ask_billing'];
		$payment_ask_shipping = (int) $row['payment_ask_shipping'];

		$payment_enable_tax = (int) $row['payment_enable_tax'];
		$payment_tax_rate 	= (float) $row['payment_tax_rate'];

		$payment_enable_discount = (int) $row['payment_enable_discount'];
		$payment_discount_type 	 = $row['payment_discount_type'];
		$payment_discount_amount = (float) $row['payment_discount_amount'];
		$payment_discount_element_id = (int) $row['payment_discount_element_id'];
	}else{
		die("Error. Unknown form ID.");
	}

	$is_discount_applicable = false;

	//get number of status indicators
	$cntStatusArr = array(0, 0, 0, 0);
	foreach ($formIdArr as $temp_form_id) {
		$query_count_status = '
								select
									e.*,
								  si.indicator indicator
								from
									'.LA_TABLE_PREFIX.'form_elements e
								LEFT JOIN '.LA_TABLE_PREFIX.'element_status_indicator si
								ON  e.element_id = si.element_id AND e.form_id = si.form_id AND si.company_id = '.$company_id.' AND si.entry_id = '.$entry_id.'
									where
											e.form_id=? and
											e.element_status = 1
									group by
									element_id
									order by
										indicator,
										e.element_position
								';
		$sth_count_status = la_do_query( $query_count_status, array($temp_form_id), $dbh );

		while($row = la_do_fetch_result($sth_count_status)) {
			if ( in_array( $row['element_type'],
			 	array( 'text',
			        'textarea',
			        'file',
			        'radio',
			        'checkbox',
			        'select',
			        'signature',
			        'matrix' )) && $row['element_status_indicator'] == 1) {

				if ( isset( $row['indicator'] ) && $row['indicator'] === '0' ) {
					$cntStatusArr[0]++;
				} elseif ( isset( $row['indicator'] ) && $row['indicator'] === '1' ) {
					$cntStatusArr[1]++;
				} elseif ( isset( $row['indicator'] ) && $row['indicator'] === '2' ) {
					$cntStatusArr[2]++;
				} elseif ( isset( $row['indicator'] ) && $row['indicator'] === '3' ) {
					$cntStatusArr[3]++;
				} else {
					$cntStatusArr[0]++;
				}
			}
		}
	}

	if(array_sum($cntStatusArr) == 0) {
		$status_percentage = 0;
	} else {
		$status_percentage = round($cntStatusArr[3] / array_sum($cntStatusArr) * 100);
	}

	//get entry details for particular entry_id
	$param['checkbox_image'] = 'images/icons/59_blue_16.png';
	$entry_details = la_get_entry_details($dbh, $form_id, $company_id, $entry_id ,$params);
	
	// begin getting highest score of particular form with form_id
	$max_score = 0;
	$in  = str_repeat('?,', count($formIdArr) - 1) . '?';

	$questions_query = "select
	                                fe.element_type, fe.element_choice_has_other, fe.element_choice_other_score, eo.option_value_max as option_value_max, eo.option_value_sum as option_value_sum 
	                            from
	                                 " . LA_TABLE_PREFIX . "form_elements fe
	                            left join 
	                            (
	                                SELECT max(option_value) as option_value_max, sum(option_value) as option_value_sum, element_id, form_id 
	                                FROM " . LA_TABLE_PREFIX . "element_options 
	                                GROUP BY element_id, form_id
	                            ) as eo ON eo.element_id = fe.element_id AND eo.form_id = fe.form_id
	                            where
	                                fe.element_type in ('radio','select','checkbox') and
	                                fe.element_status = 1 and
	                                fe.form_id in ($in)
	                                ";

	$questions_sth = la_do_query($questions_query, $formIdArr, $dbh);
	$questions_options = array();

	while ($questions_row = la_do_fetch_result($questions_sth)) {
	    if($questions_row['element_type']=="select"){
	        if ($questions_row["option_value_max"] > 0) {
	            $max_score += $questions_row["option_value_max"];
	        }
	    } else if($questions_row['element_type']=="radio"){
	        if ($questions_row["option_value_max"] > 0) {
	            if ($questions_row["element_choice_has_other"] && $questions_row["element_choice_has_other"] > 0 && $questions_row["element_choice_other_score"] > $questions_row["option_value_max"]) {
	                $max_score += $questions_row["element_choice_other_score"];
	            } else {
	                $max_score += $questions_row["option_value_max"];
	            }
	        } else if ($questions_row["element_choice_has_other"] && $questions_row["element_choice_has_other"] > 0) {
	            {
	                $max_score += $questions_row["element_choice_other_score"];
	            }
	        }
	    } else {
	        if ($questions_row["option_value_sum"] > 0) {
	            $max_score += $questions_row["option_value_sum"];
	        }
	        if ($questions_row["element_choice_has_other"] && $questions_row["element_choice_has_other"] > 0) {
	            $max_score += $questions_row["element_choice_other_score"];
	        }
	    }
	}

	$questions_query = "select
	                                    fe.element_matrix_allow_multiselect as element_matrix_allow_multiselect, fe.element_constraint as element_constraint, eo.option_value_max as option_value_max, eo.option_value_sum as option_value_sum
	                                from
	                                     " . LA_TABLE_PREFIX . "form_elements fe
	                                left join 
	                                (
	                                    SELECT max(option_value) as option_value_max, sum(option_value) as option_value_sum , element_id, form_id 
	                                    FROM " . LA_TABLE_PREFIX . "element_options 
	                                    GROUP BY element_id, form_id
	                                ) as eo ON eo.element_id = fe.element_id AND eo.form_id = fe.form_id
	                                where
	                                    fe.element_type = 'matrix' and
	                                    fe.element_status = 1 and
	                                    fe.element_constraint != '' and
	                                    fe.form_id IN ($in)
	                                    ";
	$questions_sth = la_do_query($questions_query, $formIdArr, $dbh);
	$questions_options = array();

	while ($questions_row = la_do_fetch_result($questions_sth)) {
	    $matrix_rows = (strlen($questions_row["element_constraint"]) + 1) / 2 + 1;
	    $element_matrix_allow_multiselect = $questions_row["element_matrix_allow_multiselect"];

	    if ($element_matrix_allow_multiselect == '1') {
	        if ($questions_row["option_value_sum"] > 0) {
	            $max_score += $questions_row["option_value_sum"] * $matrix_rows;
	        }
	    } else {
	        if ($questions_row["option_value_max"] > 0) {
	            $max_score += $questions_row["option_value_max"] * $matrix_rows;
	        }
	    }
	}

	if($max_score > 0){
		if($totalScore < 0) {
			$score_percentage = 100;
		} else {
			$score_percentage = round($totalScore / $max_score * 100);
		}
		
		for($i = 0; $i<count($scoreArr); $i++){
			if($scoreArr[$i] < 0) {
				$scoreArr[$i] =  100;
			} else {
				$scoreArr[$i] =  round($scoreArr[$i] / $max_score * 100);
			}
		}
	} else if($max_score == 0){
		$score_percentage = 0;
		$scoreArr[0] =  0;
	} else {
		$score_percentage = 100;
		$scoreArr[0] =  100;
	}	
	// end getting highest score of particular form with form_id

	//check for any 'signature' field, if there is any, we need to include the javascript library to display the signature
	$query = "select 
					count(form_id) total_signature_field 
				from 
					".LA_TABLE_PREFIX."form_elements 
			   where 
			   		element_type = 'signature' and 
			   		element_status=1 and 
			   		form_id=?";
	$params = array($form_id);

	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);


	//get the current sign information from DB
	$query = "SELECT * FROM ".LA_TABLE_PREFIX."signed_forms WHERE client_id=? and form_id=? order by created_at desc";
	$sth = la_do_query($query, array($company_id, $form_id), $dbh);
	$res = la_do_fetch_result($sth);

	if (isset($res)) {
		$signature_id = $res["signature_id"];
		$signer_id = $res["signer_id"];
		$sign_date = $res["created_at"];

		$query = "SELECT * FROM ".LA_TABLE_PREFIX."ask_client_users WHERE `client_user_id`=?";
		$sth = la_do_query($query, array($signer_id), $dbh);
		$row = la_do_fetch_result($sth);

		$signer_full_name = "";
		if (isset($row)) {
			$signer_full_name = $row["full_name"];
		}
	}

	$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="css/entry_print.css" media="print">
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery-migrate.min.js"></script>
EOT;

	$current_nav_tab = 'manage_forms';
	require('includes/header.php'); 
	
?>
<style>
	.score-span {
		float: right;
		font-weight: bold;
		padding-left: 30px;
	}

	.status-icon{
		vertical-align: middle;
		width:10px;
	}
	.deny-entry {
		width: 48px;
	    text-align: center;
	    margin-top: 5px;
	}
	#ve_table_info {
		color: #000000;
	}
	.highcharts-container {
		margin: 0 auto;
	}
	.highcharts-credits {
		display: none!important;
	}
	.ve_detail_table {
		color: #000;
	}
	.view_entry {
		min-height: 500px;
	}
	.breadcrumb {
		border: none!important;
	}
	.small_loader_box {
		padding-top: 7px!important;
	}
</style>
<script type="text/javascript">
	<?php	
	$base_url = str_replace("http:", "https:", $la_settings['base_url']);
	?>
	app_base_url = '<?=$base_url;?>';
</script>
<div id="content" class="full">
  <div class="post view_entry">
    <div class="content_header">
      <div class="content_header_title">
        <div style="float: left; width:60%;">
          <h2><?php echo "<a class=\"breadcrumb\" href='manage_forms.php?id={$form_id}'>".$form_name.'</a>'; ?> <img src="images/icons/resultset_next.gif" /> <?php echo "<a id=\"ve_a_entries\" class=\"breadcrumb\" href='manage_entries.php?id={$form_id}'>Entries</a>"; ?> <img id="ve_a_next" src="images/icons/resultset_next.gif" /> <?php echo $company_name; ?><img id="ve_a_next" src="images/icons/resultset_next.gif" /> #<?php echo $entry_id; ?></h2>
        </div>
        <?php if(empty($_SESSION['is_examiner'])){ ?>
	        <div style="float:right; width:40%;">
			  <div style="float: right;">
			  	<select id="portal-company">
				    <?php echo $select_ent; ?>
				  </select>
				  <span style="margin-left:10px;">
				    <input type="button" id="move-to-company" value="Move data to Portal Entity" />
				  </span>
				</div>
			</div>
		<?php } ?>
		<div style="clear: both; height: 1px"></div>
      </div>
    </div>
    <?php la_show_message(); ?>
    <div class="content_body">
      <div id="ve_details" data-form_id="<?php echo $form_id; ?>" data-company_id="<?php echo $company_id; ?>" data-entry_id="<?php echo $entry_id; ?>" data-incomplete="<?php if($is_incomplete_entry){ echo '1';}else{ echo '0';} ?>">
  		<?php
      		if( isset($_GET['entry_status_only']) ) {
      			echo '<style type="text/css">#ve_entry_actions li.entry_sidebar_option, #view_entry_content_only, .entry_sidebar_option{display:none;}#ve_entry_actions li.status_sidebar_option{display:block;}</style>';
      			include('view_entry_status_only.php');
      		} else {
      			echo '<style type="text/css">#ve_entry_actions li.status_sidebar_option{display:none;}#ve_entry_actions li.entry_sidebar_option{display:block;}</style>';

      			if ( isset($_GET['readonly']) ) {
	      			$user_id = (int) trim($_REQUEST['user_id']);
					$entity_id = (int) trim($_REQUEST['entity_id']);

					//get lock information
					if( $_REQUEST['isAdmin'] == 1 ) {
						$user_type = 'Admin';
						$query = "SELECT `locked_id`, `lockDateTime`, (SELECT `user_fullname` FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = `entity_user_id`) `user_fullname` FROM `".LA_TABLE_PREFIX."form_editing_locked` WHERE `form_id` = ? AND `entity_user_id` = ? AND `isFormLocked` = '1' order by locked_id DESC limit 1";
						$sth = la_do_query($query,array($form_id, $user_id),$dbh);
						$row = la_do_fetch_result($sth);	

						$lock_fullname = $row['user_fullname'];
					} else {
						$user_type = 'Portal User';
						$query = "SELECT `locked_id`, `lockDateTime`, (SELECT `full_name` FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = `entity_user_id`) `user_fullname` FROM `".LA_TABLE_PREFIX."form_editing_locked` WHERE `form_id` = ? AND `entity_user_id` = ? AND `isFormLocked` = '1' order by locked_id DESC limit 1";
						$sth = la_do_query($query,array($form_id, $user_id),$dbh);
						$row = la_do_fetch_result($sth);	

						$lock_fullname = $row['user_fullname'];
					}

					if( !empty($row) ){
					
						$locked_id     = (int)$row['locked_id'];
						$lock_date     = la_short_relative_date(date("Y-m-d H:i:s", $row['lockDateTime']));
					}
				?>
					<div id="form_locked_body">
						<div class="bootstrap-alert bootstrap-alert-warning" role="alert">
						<?php if( !empty($row) ){ ?>
						
						
					  		<h3 class="text-center">Read Only View</h3>
							<p>This module is currently locked by <strong>[<?=$user_type?>] <?php echo htmlspecialchars($lock_fullname).' on '.$lock_date; ?>.</strong></p>
							<p>If you are certain <?php echo htmlspecialchars($lock_fullname); ?> is no longer working in this module, you may unlock it to continue: <a href="entry_locked.php?id=<?= $form_id ?>&entry_id=<?=$entry_id?>&unlock=<?= $user_id ?>&entity_id=<?=$entity_id?>" id="unlock_form" style="padding: 4px 12px;" class="bb_button bb_small bb_green">Unlock Form</a></p>
							<p>Important: Clicking unlock will <strong>discard any unsaved changes</strong> being made by <strong><?php echo htmlspecialchars($lock_fullname); ?></strong>.</p>
						

							<?php } else { ?>
							<p>Link Expired - Please go to <a href="/auditprotocol/edit_entry.php?form_id=<?php echo $form_id ?>&entry_id=<?=$entry_id?>" style="color: #000;">this page</a> to check latest data.</p>
						<?php } ?>
						</div>
					</div>
			<?php 	}//if readonly isset
				}//if entry_status_only isset
			?>
      	
      	<div id="view_entry_content_only">
      		<table id="ve_chart_table" width="100%" border="0" cellspacing="0" cellpadding="0">
      			<tbody>
      				<tr>
      					<td style="height: 300px; width: 33.33%; position: relative;">
				      		<div id="status_chart" style="height: 100%; width: 100%;" data-gray="<?php echo $cntStatusArr[0]; ?>" data-red="<?php echo $cntStatusArr[1]; ?>" data-yellow="<?php echo $cntStatusArr[2]; ?>" data-green="<?php echo $cntStatusArr[3]; ?>"></div>
				      		<strong id="status_total" style="position:absolute;left:0px;top:0px;height:100%;width:100%;line-height:340px;text-align:center;font-size:18px;font-family:inherit;font-weight:bold;color: #000;"><?php echo $status_percentage; ?>%</strong>
						</td>
						<td style="height: 300px; width: 33.33%; position: relative;">
				      		<div id="score_chart" style="height: 100%; width: 100%;"></div>
				      		<strong id="score_percentage" percentage = "<?php echo $score_percentage; ?>" style="position:absolute;left:0px;top:0px;height:100%;width:100%;line-height:340px;text-align:center;font-size:18px;font-family:inherit;font-weight:bold;color: #000;"><?php echo $score_percentage; ?>%</strong>
						</td>
						<td style="height: 300px; width: 33.33%; position: relative;">
				      		<div id="maturity_chart" style="height: 100%; width: 100%;"></div>
				      		<strong id="maturity_percentage" style="position:absolute;left:0px;top:0px;height:100%;width:100%;line-height:340px;text-align:center;font-size:18px;font-family:inherit;font-weight:bold;color: #000;"></strong>
						</td>
      				</tr>
      			</tbody>
      		</table>
	        <table id="ve_detail_table" width="100%" border="0" cellspacing="0" cellpadding="0">
	          <tbody>
	            <?php 
					$toggle = false;
					$row_markup_complete = '';

					foreach ($entry_details as $data){ 
						if($data['label'] == 'la_page_break' && $data['value'] == 'la_page_break'){
							continue;
						}
						$edit_entry_url = $la_settings['base_url']."edit_entry.php?form_id={$form_id}&company_id={$company_id}&entry_id={$entry_id}";

						if( isset($data['element_page_number']) && $data['element_page_number'] > 1 ) {
							$edit_entry_url.= "&la_page=".$data['element_page_number'];
						}

						if( !empty($data['element_id_auto']) ) {
							$edit_entry_url.= "&element_id_auto=".$data['element_id_auto'];
						}

						if($toggle){
							$toggle = false;
							$row_style = 'class="alt"';
						}else{
							$toggle = true;
							$row_style = '';
						}

						$row_markup = '';
						$row_markup_doc = '';
						$element_id = $data['element_id'];
						
						$status_indicator = "";
						$indicator_count = 0;
						
						if(in_array($data['element_type'], array('text', 'textarea', 'file', 'radio', 'checkbox', 'select', 'signature', 'matrix')) && $data['element_status_indicator'] == 1){
							if(isset($statusElementArr[$data['element_id']])){
								$indicator_count = $statusElementArr[$data['element_id']];
							}
							
							if(isset($statusElementArr[$data['element_id']]) && $statusElementArr[$data['element_id']] == 0){
								$status_indicator_image = 'Circle_Gray.png';
							}elseif(isset($statusElementArr[$data['element_id']]) && $statusElementArr[$data['element_id']] == 1){
								$status_indicator_image = 'Circle_Red.png';
							}elseif(isset($statusElementArr[$data['element_id']]) && $statusElementArr[$data['element_id']] == 2){
								$status_indicator_image = 'Circle_Yellow.png';
							}elseif(isset($statusElementArr[$data['element_id']]) && $statusElementArr[$data['element_id']] == 3){
								$status_indicator_image = 'Circle_Green.png';
							}else{
								$status_indicator_image = 'Circle_Gray.png';
							}	

							$status_indicator = '<img class="status-icon status-icon-action-view" data-form_id="'.$form_id.'" data-element_id="'.$data['element_id'].'" data-company_id="'.$company_id.'" data-entry_id="'.$entry_id.'" data-indicator="'.$indicator_count.'" src="images/'.$status_indicator_image.'" style="margin-left:8px; cursor:pointer;" />';
						}

						if($data['element_type'] == 'section' || $data['element_type'] == 'textarea') {
							if($data['element_type'] == 'textarea'){
								$data['value'] = html_entity_decode($data['value']);
							}
							
							if(!empty($data['label']) && !empty($data['value']) && ($data['value'] != '&nbsp;')){
								$section_separator = '<br/>';
							}else{
								$section_separator = '';
							} 
						if ($data["value"] != strip_tags($data["value"])) {  $contains_html = true; } else { $contains_html = false; }
						$contains_html = false;
						if ($contains_html) {
							?>
						<script>
						  function resizeIframe_<?php echo $entry_id; ?>(obj) {   obj.style.height = (obj.contentWindow.document.body.scrollHeight + 20) + 'px';	}
						</script>				
						<?php														 
							$display_data = "<iframe srcdoc='" . $data['value'] . "' style='width:100%; border:0px;' scrolling='no' onload='resizeIframe_" . $entry_id . "(this)'></iframe>";
			 				//$display_data = $data["value"];
							
		 				}
						
						else {
							
							$display_data = nl2br($data['value']);
							
							
						}

		 

							$section_break_content = '<span class="la_section_title"><strong>'.nl2br($data['label']).'</strong>'.$status_indicator.'</span>'.$section_separator.'<span class="la_section_content">'.$display_data.'</span>';

							$row_markup .= "<tr {$row_style}>\n";
							$row_markup .= "<td width=\"80%\" colspan=\"2\">{$section_break_content}</td>\n";
							$row_markup .= "<td width=\"20%\"><a href=\"{$edit_entry_url}\">Edit field</a></td>\n";
							$row_markup .= "</tr>\n";
						}
                        else if($data['element_type'] == 'signature') {
                            $row_digital_signature = "";
                            if (isset($signature_id)) {
                                $row_digital_signature = <<<EOT
							<tr>
								<td><span><strong>Digital Signature</strong></span>{$status_indicator}</td>
								<td>
									<div style="margin: 15px; padding: 20px; height: 150px; width: 250px; border: 1px dashed #8EACCF;">
										<img id="digital_signature_img" width="200" height="100" src="https://{$_SERVER['SERVER_NAME']}/auditprotocol/digital_signature_img.php?q={$signature_id}"/>
										<p style="text-align:right">Signed by {$signer_full_name}</p>
										<p style="text-align:right">Signed at {$sign_date}</p>
									</div>
								</td>
								<td></td>
							</tr>
							<script>
								var url = "https://{$_SERVER['SERVER_NAME']}/auditprotocol/digital_signature_img.php?q={$signature_id}"
								function getBase64FromImageUrl(url) {
									var img = new Image();				
									img.setAttribute('crossOrigin', 'anonymous');
									img.onload = function () {
										var canvas = document.createElement("canvas");
										canvas.width =this.width;
										canvas.height =this.height;

										var ctx = canvas.getContext("2d");
										ctx.drawImage(this, 0, 0);
							
										var dataURL = canvas.toDataURL("image/png");
										$("img#digital_signature_img").attr("src", dataURL);
									};
									img.src = url;
								}
								getBase64FromImageUrl(url);
							</script>
						EOT;
                            } else {
                                $row_digital_signature = <<<EOT
						<tr>
							<td><span><strong>Digital Signature</strong></span>{$status_indicator}</td>
							<td>
								<div style="margin-top: 30px; padding: 20px; height: 120px; width: 250px; border: 1px dashed #8EACCF;">
									<p>Please click here to sign this form</p>
								</div>
							</td>
							<td></td>
						</tr>
						EOT;
                            }
                            $row_markup .= $row_digital_signature;
                        }
		                else if($data['element_type'] == 'casecade_form') {
		                    $query_cascade_form_title = "SELECT form_name FROM `".LA_TABLE_PREFIX."forms` where `form_id` = ? LIMIT 1";
		                    $sth_cascade_form_title = la_do_query($query_cascade_form_title,array($data['value']),$dbh);
		                    $row_cascade_form_title = la_do_fetch_result($sth_cascade_form_title);

		                    $row_markup .= "<tr {$row_style}>\n";
		                    $row_markup .= "<td colspan=\"4\" width=\"100%\"><span><div id='cascade-form-{$data['value']}'><a href='#' class='cascade-form-expand' style='width:100%' id='cascade-form-expand-link-{$data['value']}' data-cascade_parent_page_number='{$data['element_page_number']}' data-form_id='{$data['value']}'><strong>Click to expand {$row_cascade_form_title["form_name"]}</strong></a></div></span></td>\n";
		                    $row_markup .= "</tr>\n";
						}else{
							$row_markup .= "<tr {$row_style}>\n";
							$row_markup .= "<td width=\"30%\"><span><strong>{$data['label']}</strong>".$status_indicator."</span></td>\n";
							$row_markup .= "<td width=\"50%\">".nl2br($data['value'])."</td>\n";
							//generate link to edit_entry.php
							// $edit_entry_url = $la_settings['base_url']."edit_entry.php?form_id=444107&la_page=2&casecade_form_page_number=4&casecade_element_position=8&entry_id=2";

							$row_markup .= "<td width=\"20%\"><a href=\"{$edit_entry_url}\">Edit field</a></td>\n";
							$row_markup .= "</tr>\n";
						}

						$row_markup_complete .= $row_markup;
					}

					echo $row_markup_doc;


					//check if the document is added to ap_background_document_proccesses table list and if has not created yet
					$query_document_process = "SELECT * FROM `".LA_TABLE_PREFIX."background_document_proccesses` WHERE `form_id` = ? AND `company_user_id` = ? AND `entry_id` = ? AND status != 1 ORDER BY id DESC LIMIT 1";
					$sth_document_process = la_do_query($query_document_process, array($form_id, $company_id, $entry_id), $dbh);
					$row_document_process = la_do_fetch_result($sth_document_process);
					if( $row_document_process['id'] ) {
						//check if the form has any template files attached
						$query_template_count = "SELECT COUNT(*) AS counter FROM `".LA_TABLE_PREFIX."form_template` WHERE `form_id` = ?";
						$param_template_count = array($form_id);
						$result_template_count = la_do_query($query_template_count, array($form_id), $dbh);
						$template_rows = $result_template_count->fetchColumn();
						if( $template_rows > 0 ) {
							//latest document has not been created yet
							echo "<tr><td><strong>Document information </strong></td>";				
							if( $row_document_process['status'] == 0 ) {
								echo "<td>
										Document is scheduled to be created. Click <a title=\"Generate Document\" href=\"javascript:void(0)\" onclick=\"generate_entry_document({$form_id}, {$_SESSION['la_user_id']}, {$company_id});\">Generate Document</a> button to generate the document.
									</td>";
							} else if( $row_document_process['status'] == 2 ) {
								echo "<td>Document is generating now. Sometimes it could take up to an hour.</td>";
							}
							echo "<td></td></tr>";
						}
					} else {

			            // fetch doc details if available
						$query11 = "SELECT * FROM `".LA_TABLE_PREFIX."template_document_creation` WHERE `form_id` = ? AND `company_id` = ? AND `entry_id` = ? AND `isZip` = 1 AND `isPOAM` = 0 ORDER BY `docx_create_date` DESC LIMIT 1";
						$sth11 = la_do_query($query11, array($form_id, $company_id, $entry_id), $dbh);

			            if($toggle){
			              $toggle = false;
			              $row_style = 'class="alt"';
			            }else{
			              $toggle = true;
			              $row_style = '';
			            }

			        	$document_list = [];
			        	$document_count = 0;
			        	while($document_data = la_do_fetch_result($sth11)){
			        		
						?>
							<tr class="<?php echo $row_style; ?>">
								<td><strong>Download Report </strong></td>
								<td>
									<a target="_blank" href="javascript:void(0)" class="action-download-document-zip" data-documentdownloadlink="<?=$la_settings['base_url']?>download_document_zip.php?id=<?=$document_data['docxname']?>&form_id=<?=$_GET['form_id']?>&entry_id=<?=$entry_id?>&company_id=<?=$company_id?>" ><?php echo $document_data['docxname']; ?></a>
								</td>
								<td></td>
							</tr>
						<?php
							if( !empty($document_data['added_files']) ) {
								$added_files = explode(',', $document_data['added_files']);

								foreach ($added_files as $docxname) {
									$target_file 	= $_SERVER['DOCUMENT_ROOT']."/portal/template_output/{$docxname}";
									$filename_ext   = end(explode(".", $docxname));
									$q_string = "file_path={$target_file}&file_name={$docxname}&form_id={$form_id}&document_preview=1";
									
									$document_list[$document_count]['ext'] = $filename_ext;
									$document_list[$document_count]['q_string'] = $q_string;
									$document_list[$document_count]['file_name'] = $docxname;
									$document_count++;
								}
							}
						}

						if( count($document_list) > 0 ) {
							echo '<tr><td><strong>Preview Report(s)</strong></td><td>';
								
							foreach ($document_list as $document_view_data) {
								echo '<img src="'.$options['itauditmachine_path'].'images/icons/185.png" align="absmiddle" style="vertical-align: middle" />&nbsp;<a class="entry_link entry-link-preview" href="#" data-identifier="other" data-ext="'.$document_view_data['ext'].'" data-src="'.base64_encode($document_view_data['q_string']).'">'.$document_view_data['file_name'].'</a><br/>';
							}
							echo '</td><td></td></tr>';
						}

						//get POAM reports
						$query11 = "SELECT * FROM `".LA_TABLE_PREFIX."template_document_creation` WHERE `form_id` = ? AND `company_id` = ? AND `entry_id` = ? AND `isZip` = 1 AND `isPOAM` = 1 ORDER BY `docx_create_date` DESC LIMIT 1";
						$sth11 = la_do_query($query11, array($form_id, $company_id, $entry_id), $dbh);

			            if($toggle){
			              $toggle = false;
			              $row_style = 'class="alt"';
			            }else{
			              $toggle = true;
			              $row_style = '';
			            }

			        	$document_list = [];
			        	$document_count = 0;
			        	while($document_data = la_do_fetch_result($sth11)){
			        		
						?>
							<tr class="<?php echo $row_style; ?>">
								<td><strong>Download POAM Report </strong></td>
								<td>
									<a target="_blank" href="javascript:void(0)" class="action-download-document-zip" data-documentdownloadlink="<?=$la_settings['base_url']?>download_document_zip.php?id=<?=$document_data['docxname']?>&form_id=<?=$_GET['form_id']?>&entry_id=<?=$entry_id?>&company_id=<?=$company_id?>" ><?php echo $document_data['docxname']; ?></a>
								</td>
								<td></td>
							</tr>
						<?php
							if( !empty($document_data['added_files']) ) {
								$added_files = explode(',', $document_data['added_files']);

								foreach ($added_files as $docxname) {
									$target_file 	= $_SERVER['DOCUMENT_ROOT']."/portal/template_output/{$docxname}";
									$filename_ext   = end(explode(".", $docxname));
									$q_string = "file_path={$target_file}&file_name={$docxname}&form_id={$form_id}&document_preview=1";
									
									$document_list[$document_count]['ext'] = $filename_ext;
									$document_list[$document_count]['q_string'] = $q_string;
									$document_list[$document_count]['file_name'] = $docxname;
									$document_count++;
								}
							}
						}

						if( count($document_list) > 0 ) {
							echo '<tr><td><strong>Preview POAM Report(s)</strong></td><td>';
								
							foreach ($document_list as $document_view_data) {
								echo '<img src="'.$options['itauditmachine_path'].'images/icons/185.png" align="absmiddle" style="vertical-align: middle" />&nbsp;<a class="entry_link entry-link-preview" href="#" data-identifier="other" data-ext="'.$document_view_data['ext'].'" data-src="'.base64_encode($document_view_data['q_string']).'">'.$document_view_data['file_name'].'</a><br/>';
							}
							echo '</td><td></td></tr>';
						}
					}
					echo $row_markup_complete;
				?>
	          </tbody>
	        </table>
	        <?php if(!empty($payment_enable_merchant)){ ?>
	        <table width="100%" cellspacing="0" cellpadding="0" border="0" id="ve_payment_info">
	          <tbody>
	            <tr>
	              <td class="payment_details_header"><span class="icon-info"></span>Payment Details</td>
	              <td>&nbsp;</td>
	            </tr>
	            <tr class="alt">
	              <td width="40%" class="payment_label"><strong>Amount</strong></td>
	              <td width="60%"><?php echo $currency_symbol.$payment_amount.' '.$payment_currency; ?></td>
	            </tr>
	            <?php if($payment_has_record){ ?>
	            <tr class="alt">
	              <td class="payment_label"><strong>Payment ID</strong></td>
	              <td><?php echo $payment_id; ?></td>
	            </tr>
	            <tr>
	              <td class="payment_label"><strong>Payment Date</strong></td>
	              <td><?php echo $payment_date; ?></td>
	            </tr>
	            <tr class="alt">
	              <td>&nbsp;</td>
	              <td>&nbsp;</td>
	            </tr>
	            <tr>
	              <td class="payment_label"><strong>Full Name</strong></td>
	              <td><?php echo htmlspecialchars($payment_fullname,ENT_QUOTES); ?></td>
	            </tr>
	            <?php if(!empty($payment_ask_billing) && !empty($billing_address)){ ?>
	            <tr class="alt">
	              <td class="payment_label"><strong>Billing Address</strong></td>
	              <td><?php echo $billing_address; ?></td>
	            </tr>
	            <?php } ?>
	            <?php if(!empty($payment_ask_shipping) && !empty($shipping_address)){ ?>
	            <tr>
	              <td class="payment_label"><strong>Shipping Address</strong></td>
	              <td><?php echo $shipping_address; ?></td>
	            </tr>
	            <?php } ?>
	            <?php } ?>
	          </tbody>
	        </table>
	        <?php } ?>
	        <table width="100%" cellspacing="0" cellpadding="0" border="0" id="ve_table_info">
	          <tbody>
	            <tr id="entry_info_header_tr">
	              <td class="entry_info_header"><span class="icon-info"></span>Entry Info</td>
	              <td>&nbsp;</td>
	            </tr>
	            <tr class="alt">
	              <td width="40%"><strong>Date Created</strong></td>
	              <td width="60%"><?php echo $date_created; ?></td>
	            </tr>
	            <tr>
	              <td><strong>Date Updated</strong></td>
	              <td><?php echo $date_updated; ?></td>
	            </tr>
	            <tr>
	              <td><strong>Total Score</strong></td>
	              <td id="total-score"><?php echo $totalScore; ?></td>
	            </tr>
				<tr>
	              <td><strong>Status Indicator</strong></td>
	              <td id="total-status"></td>
	            </tr>
	            <tr class="alt">
	              <td><strong>IP Address</strong></td>
	              <td><?php echo $ip_address; ?></td>
	            </tr>
	          </tbody>
	        </table>
	        <table id="ve_progress_timeline" width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 25px;">
	        	<tr>
	        		<td>
	        			<div id="progress_timeline" style="height:400px; width: 100%;"></div>
	        		</td>
	        	</tr>
	        </table>
	        
	        <?php if($is_incomplete_entry){ ?>
	        <table width="100%" cellspacing="0" cellpadding="0" border="0" id="ve_table_info">
	          <tbody>
	            <tr>
	              <td style="font-size: 85%;color: #444; font-weight: bold"><img src="images/icons/227_blue.png" align="absmiddle" style="vertical-align: middle;margin-right: 5px">Resume URL</td>
	              <td>&nbsp;</td>
	            </tr>
	            <tr class="alt">
	              <td colspan="2"><a class="ve_resume_link" href="<?php echo $form_resume_url; ?>"><?php echo $form_resume_url; ?></a></td>
	            </tr>
	          </tbody>
	        </table>
	        <?php } ?>
	        <?php if(!empty($payment_resume_url)){ ?>
	        <table width="100%" cellspacing="0" cellpadding="0" border="0" id="ve_table_info">
	          <tbody>
	            <tr>
	              <td style="font-size: 85%;color: #444; font-weight: bold"><img src="images/icons/227_blue.png" align="absmiddle" style="vertical-align: middle;margin-right: 5px">Payment URL</td>
	              <td>&nbsp;</td>
	            </tr>
	            <tr class="alt">
	              <td colspan="2"><a class="ve_resume_link" href="<?php echo $payment_resume_url; ?>">Open Payment Page</a></td>
	            </tr>
	          </tbody>
	        </table>
	        <?php } ?>

	        <?php
	        	//check if approval/deny logic enabled for entry and show info to admin only
	        	if(!empty($_SESSION['la_user_privileges']['priv_administer'])){
	        		if( $show_approval_deny_table ) {
	        			$approval_status = $entry_data['approval_status'];
	        			$approval_status_text = '';
	        			


	        			//get get entry saved json
	        			$query_ad  = "SELECT `data` FROM `".LA_TABLE_PREFIX."form_approval_logic_entry_data` where `form_id` = {$form_id} and company_id={$company_id}";
						$result_ad = la_do_query($query_ad,array(),$dbh);
						$form_logic_data    = la_do_fetch_result($result_ad);

						$logic_approver_enable = '';
						if($form_logic_data){
							$form_logic_data_arr = json_decode($form_logic_data['data']);
							$logic_approver_enable = $form_logic_data_arr->logic_approver_enable;
							
							$logic_approver_enable_1_a = 0;
			 				if( $logic_approver_enable == 1 ) {
								$logic_approver_enable_1_a = $form_logic_data_arr->selected_admin_check_1_a;
			 				}
						}

						if ( $approval_status == 1) { // form is approved
							$approval_status_text = 'Approved';
						} elseif ( $approval_status == 2) { // form is dis-approved
							$approval_status_text = 'Denied';
						} else {
							$approval_status_text = 'Pending';
						}


						if( $logic_approver_enable > 0 ) {

							//logic type
							if( $logic_approver_enable == 1 ) {
								$logic_type_text = 'Single-Step Approval - ';
								if( $logic_approver_enable_1_a == 1 ) {
									$logic_type_text .= 'Any user can approve';
								} else if( $logic_approver_enable_1_a == 2 ) {
									$logic_type_text .= 'Selected users only';
								} else if( $logic_approver_enable_1_a == 3 ) {
									$logic_type_text .= 'Require unanimous approval from all selected users';
								}
							} else if ($logic_approver_enable == 2) {
								$logic_type_text = 'Multi-Step Approval';
							}




							//get all user info from ap_form_approvals
							$query_form_approvals = "SELECT 
											A.user_order,
											A.is_replied,
											A.message,
											B.user_email,
											B.user_fullname 
										FROM 
											".LA_TABLE_PREFIX."form_approvals A LEFT JOIN ".LA_TABLE_PREFIX."users B
										  ON 
										  	A.user_id = B.user_id 
									   	WHERE
											A.company_id = ? AND A.form_id = ?
									ORDER BY 
											A.user_order asc";
							$params = array($company_id, $form_id);
							$sth_form_approvals = la_do_query($query_form_approvals,$params,$dbh);
							
							$row_data_arr = [];
							// echo $company_id;
							$row_count = 0;
							while($row = la_do_fetch_result($sth_form_approvals)){
								$row_data_arr[$row_count]['user_order'] = $row['user_order'];
								$user_reply = $row['is_replied'];

								if( $user_reply == 1 ) {
									$user_reply_text = 'Approved';
								} else if( $user_reply == 2 ) {
									$user_reply_text = 'Denied';
								} else {
									$user_reply_text = '-';
								}


								$row_data_arr[$row_count]['is_replied'] = $user_reply_text;
								$row_data_arr[$row_count]['message'] = $row['message'];
								$row_data_arr[$row_count]['user_email'] = $row['user_email'];
								$row_data_arr[$row_count]['user_fullname'] = $row['user_fullname'];
								$row_count++;
							}

	       	?>
	       	<table width="100%" cellspacing="0" cellpadding="0" border="0" id="ad_table_info" class="striped_table">
	          	<tbody>
	            	<tr id="entry_info_header_tr">
		              	<td class="entry_info_header"><span class="icon-info"></span>Approval/Deny Info</td>
		              	<td>&nbsp;</td>
	            	</tr>
	            	<tr class="alt">
	              		<td width="40%"><strong>Entry Status</strong></td>
	              		<td width="60%"><?=$approval_status_text?></td>
	            	</tr>
	            	<tr class="alt">
	              		<td width="40%"><strong>Logic type</strong></td>
	              		<td width="60%"><?=$logic_type_text?></td>
	            	</tr>
	            </tbody>
	        </table>

	        <?php if( !empty($row_data_arr) ) { ?>
			<table width="100%" cellspacing="0" cellpadding="0" border="0" id="ad_table_rows" class="striped_table">
				<thead>
					<th>Name</th>
					<th>Email</th>
					<th>Status</th>
					<th>Order</th>
					<th>Message</th>
				</thead>
	          	<tbody>
	          		<?php foreach ($row_data_arr as $user_row) {
	          			echo '<tr>';
	          			echo "<td>{$user_row['user_fullname']}</td>";
	          			echo "<td>{$user_row['user_email']}</td>";
	          			echo "<td>{$user_row['is_replied']}</td>";
	          			echo "<td>{$user_row['user_order']}</td>";
	          			echo "<td>{$user_row['message']}</td>";
	          			echo '</tr>';

	          		} ?>
	            	
	            </tbody>
	        </table>
	    	<?php } ?>
	    	
	    	<style type="text/css">
	        	#ad_table_info {
	        		margin-top: 25px;
	        	}
	        	.striped_table td {
	        		border-bottom: 1px dotted #8EACCF;
	    			padding: 5px;
	        	}
				.striped_table .entry_info_header span {
				    color: #3661A1;
				    font-size: 110%;
				    margin-right: 5px;
				}
				.striped_table thead th {
				    color: #fff;
				    font-family: Arial, helvetica, 'Helvetica Neue', Arial, 'Trebuchet MS', 'Lucida Grande';
				    font-size: 13px;
				    font-weight: 500;
				    padding-bottom: 5px;
				    padding-top: 5px;
				    padding-left: 5px;
				    background-color: #3B699F;
				    text-shadow: 0 1px 1px #000000;
				    text-align: left;
				    border-right: 1px dotted #ffffff;
				}
	        </style>
	        <?php } } } ?>
    	</div>
      </div>
	    <?php # ACTIONS MENU ?>
      <div id="ve_actions">      	
		<div id="ve_entry_navigation"> 
			<?php
				if(!is_null($previous_entry_id)) {
					?>
					<a href="<?php echo 'view_entry.php?form_id='.$form_id.'&company_id='.$company_id.'&entry_id='.$previous_entry_id; ?>" title="Previous Entry">
						<img src="images/navigation/005499/24x24/Back.png">
					</a>
					<?php
				} else {
					?>
					<a href="<?php echo 'view_entry.php?form_id='.$form_id.'&company_id='.$company_id.'&entry_id='.$entry_id; ?>" title="Previous Entry" style="visibility: hidden;">
						<img src="images/navigation/005499/24x24/Back.png">
					</a>
					<?php
				}

				if(!is_null($next_entry_id)) {
					?>
					<a href="<?php echo 'view_entry.php?form_id='.$form_id.'&company_id='.$company_id.'&entry_id='.$next_entry_id; ?>" title="Next Entry" style="margin-left: 5px">
						<img src="images/navigation/005499/24x24/Forward.png">
					</a>
					<?php
				} else {
					?>
					<a href="<?php echo 'view_entry.php?form_id='.$form_id.'&company_id='.$company_id.'&entry_id='.$entry_id; ?>" title="Next Entry" style="visibility: hidden;">
						<img src="images/navigation/005499/24x24/Forward.png">
					</a>
					<?php
				}
			?>
		</div>
		<div id="ve_entry_actions">
			<ul style="width: 100%;">
				<li style="border-bottom: 1px dashed #8EACCF">
					<?php
						$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
						if( isset($_GET['entry_status_only']) ) {
							$status_url = str_replace('&entry_status_only=1', '', $current_url);
							$status_text = 'Back to Details';
						} else {
							$status_url = $current_url.'&entry_status_only=1';
							$status_text = 'View Status';
						}
					?>
					<a id="ve_action_status" href="<?=$status_url?>"><img src="images/navigation/005499/16x16/View.png"><?=$status_text?></a>
				</li>
				<li style="border-bottom: 1px dashed #8EACCF">
					<a id="ve_action_status_change" href="javascript:void(0)"><img src="images/navigation/005499/16x16/Import.png">Set Status Indicators</a>
				</li>
				<?php if ( !empty( $_SESSION['la_user_privileges']['priv_administer'] ) || !empty( $user_perms['edit_entries'] ) ) { ?>
					<li style="border-bottom: 1px dashed #8EACCF">
						<a id="ve_action_edit" href="<?php echo "edit_entry.php?form_id={$form_id}&company_id={$company_id}&entry_id={$entry_id}"; ?>"><img src="images/navigation/005499/16x16/Edit.png">Edit Entry Data</a>
					</li>
				<?php } ?>
				<?php if(empty($_SESSION['is_examiner'])){ ?>
					<li style="border-bottom: 1px dashed #8EACCF">
						<a id="ve_action_email" href="javascript:void(0)"><img src="images/navigation/005499/16x16/Email.png">Email Entry Data</a>
					</li>
				<?php } ?>				
				<?php
					$show_generate_entry_document = false;
					if(!empty($_SESSION['la_user_privileges']['priv_administer']) || !empty($user_perms['edit_entries'])){
						if( $form_properties['form_enable_template_wysiwyg'] == 1 && !empty( $form_properties['form_template_wysiwyg_id'] ) ) {
							$show_generate_entry_document = true;
						} else {
							$query_template_count = "SELECT COUNT(*) AS counter FROM `".LA_TABLE_PREFIX."form_template` WHERE `form_id` = ?";
							$param_template_count = array($form_id);
							$result_template_count = la_do_query($query_template_count, array($form_id), $dbh);
							$num_rows = $result_template_count->fetchColumn();
							if( $num_rows > 0 ) {
								$show_generate_entry_document = true;
							}
						}
						if( $show_generate_entry_document ) { ?>
							<li style="border-bottom: 1px dashed #8EACCF">
								<a href="javascript:void(0)" onclick="generate_entry_document(<?=$form_id?>, <?=$_SESSION['la_user_id']?>, <?=$company_id?>, <?=$entry_id?>);"><img src="images/navigation/005499/16x16/List.png">Generate Document</a>
							</li>
					<?php 
						}
					} ?>
				<?php if(!empty($_SESSION['la_user_privileges']['priv_administer']) || !empty($user_perms['edit_entries'])){ ?>
					<li style="border-bottom: 1px dashed #8EACCF">
						<a id="ve_action_delete" href="javascript:void(0)"><img src="images/navigation/005499/16x16/Delete.png ">Delete Entry Data</a>
					</li>
				<?php } ?>
				<li style="border-bottom: 1px dashed #8EACCF">
					<a id="ve_action_pdf" href="javascript:void(0)"><img src="images/navigation/005499/16x16/PDF.png">Export to PDF</a>
				</li>
				<li class="status_sidebar_option" style="border-bottom: 1px dashed #8EACCF">
					<a id="dialog-status-csv" href="javascript:void(0)"><img src="images/navigation/005499/16x16/List.png">Export Status To CSV</a>
				</li>
				<li>
					<a id="ve_action_print" href="javascript:window.print()"><img src="images/navigation/005499/16x16/Print.png">Print Entry Data</a>
				</li>
			</ul>
		</div>
      </div><?php # ACTIONS MENU (#ve_actions) ?>
    </div>
    <!-- /end of content_body -->    
  </div>
  <!-- /.post --> 
</div>
<!-- /#content -->

<div id="dialog-reset-status-indicators" title="Are you sure you want to reset all the status indicators of this entry data?" class="buttons" style="display: none;text-align: center;">
	<img src="images/navigation/ED1C2A/50x50/Warning.png">
	<p> This action cannot be undone.<br/>
		<strong>All the status indicators of this entry data will be changed to:</strong><br/>
	</p>
	<div style="padding: 10px 100px;">
		<input type="radio" value="0" name="change-status-to" checked>
		<label for="export-radio">Gray</label>
		<input type="radio" value="1" name="change-status-to">
		<label for="export-radio">Red</label>
		<input type="radio" value="2" name="change-status-to">
		<label for="export-radio">Yellow</label>
		<input type="radio" value="3" name="change-status-to">
		<label for="export-radio">Green</label>
	</div>
</div>
<div id="dialog-error" title="Error" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/ED1C2A/50x50/Warning.png" />
	<p id="dialog-error-msg"> Error </p>
</div>
<div id="dialog-confirm-entry-delete" title="Are you sure you want to delete this entry?" class="buttons" style="display: none">
	<img src="images/navigation/ED1C2A/50x50/Warning.png">
  <p id="dialog-confirm-entry-delete-msg"> This action cannot be undone.<br/>
    <strong id="dialog-confirm-entry-delete-info">Data and files associated with this entry will be deleted.</strong><br/>
    <br/>
  </p>
</div>
<div id="dialog-download-document-zip" title="Download Document" class="buttons" style="display: none">
	<p style="text-align: center"><?php echo htmlspecialchars($la_settings['disclaimer_message'],ENT_QUOTES); ?></p>
</div>
<div id="dialog-email-entry" title="Email entry #<?php echo $entry_id; ?> to:" class="buttons" style="display: none">
  <form id="dialog-email-entry-form" class="dialog-form" style="padding-left: 10px;padding-bottom: 10px">
    <div style="display:none;">
      <input id="post_csrf_token" type="hidden" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
    </div>
    <ul>
      <li>
        <div>
          <input type="text" value="" class="text" name="dialog-email-entry-input" id="dialog-email-entry-input" />
        </div>
        <div class="infomessage" style="padding-top: 5px;padding-bottom: 0px">Use commas to separate email addresses.</div>
      </li>
    </ul>
  </form>
</div>
<div id="dialog-entry-sent" title="Success!" class="buttons" style="display: none"> <img src="images/navigation/005499/50x50/Success.png" title="Success" />
  <p id="dialog-entry-sent-msg"> The entry has been sent. </p>
</div>
<div id="processing-pdf-dialog" style="visibility: hidden; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); opacity: 100;">
	<div style="font-weight: bold; font-size: 150%; text-align: center; vertical-align: middle; position: absolute; top: 35%; left: 40%; color: black; background-color: white; padding: 1rem 0rem; width: 24rem; border-radius: 0.5rem;">
				Generating PDF...<br>
	<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
	</div>
</div>
<div id="processing-dialog-view" style="display: none;text-align: center;font-size: 150%;">
	Processing Request...<br>
	<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
</div>

<div id="processing-dialog-document" style="display: none;text-align: center;font-size: 150%;">
	Document is generating now. Sometimes it could take up to an hour.<br>
	<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
</div>
<div id="processing-dialog-file" style="display: none;text-align: center;font-size: 150%;">
	Processing Request...<br>
	<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
</div>
<div id="document-preview" style="display: none;text-align: center;font-size: 150%;" title="Document Preview">
		<?php if( isset($la_settings['file_viewer_download_option']) && ($la_settings['file_viewer_download_option'] == 1) ) { ?>
			<div style="text-align: right;margin-bottom: 10px;">
				<a href="#" id="file_viewer_download_button" class="bb_button bb_small bb_green" download> 
		          <img src="images/navigation/FFFFFF/24x24/Save.png"> Download
		      	</a>
		    </div>
		<?php } ?>
	
    <div id="document-preview-content" style="height: 440px;">
		<img src="images/loading-gears.gif" style="transform: translateY(65%);"/>
	</div>
</div>

<div id="opening-entry-dialog" style="visibility: hidden; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); opacity: 100;">
	<div style="font-weight: bold; font-size: 150%; text-align: center; vertical-align: middle; position: absolute; top: 35%; left: 40%; color: black; background-color: white; padding: 1rem 0rem; width: 24rem; border-radius: 0.5rem;">
				Opening the entry in edit mode...<br>
	<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
	</div>
</div>
<?php
	
	$cb = function ($fn) {
	    return $fn;
	};
	$post_csrf_token = $_SESSION['csrf_token'];
	$footer_data =<<<EOT
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/signaturepad/jquery.signaturepad.min.js"></script>
<script type="text/javascript" src="js/signaturepad/json2.min.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script type="text/javascript" src="js/view_entry.js"></script>

<script>
$(document).ready(function(e) {
	//convert RGB color to Hex color
	function RGBToHex(r,g,b) {
	  r = r.toString(16);
	  g = g.toString(16);
	  b = b.toString(16);

	  if (r.length == 1)
	    r = "0" + r;
	  if (g.length == 1)
	    g = "0" + g;
	  if (b.length == 1)
	    b = "0" + b;

	  return "#" + r + g + b;
	}

	//generate status doughnut chart
	function generate_status_chart(status_indicators){
		var total_status = status_indicators[0] + status_indicators[1] + status_indicators[2] + status_indicators[3];
		var status_percentage = 0;
		var data_points = [];
		if(total_status == 0){
			data_points.push({name:'', y: 1, color:"#505356"});
		} else {
			if(status_indicators[0] != 0) {
				data_points.push({name:status_indicators[0], y: status_indicators[0], color:"#505356"});
			}
			if(status_indicators[1] != 0) {
				data_points.push({name:status_indicators[1], y: status_indicators[1], color:"#F95360"});
			}
			if(status_indicators[2] != 0) {
				data_points.push({name:status_indicators[2], y: status_indicators[2], color:"#F2B604"});
			}
			if(status_indicators[3] != 0) {
				data_points.push({name:status_indicators[3], y: status_indicators[3], color:"#33BF8C"});
			}
			status_percentage = Math.round(status_indicators[3]/total_status*100);
		}
		$("#status_chart").highcharts({
			chart: {
				plotBackgroundColor: null,
				plotBorderWidth: 0,
				plotShadow: false
			},
			title: {			  	
				text: 'Status Score',
				align: 'center',
				verticalAlign: 'top',
				y: 3,
				style: {
					fontFamily: 'glober_regularregular',
					fontWeight: 'bold',
					color: '#000'
				}
			},
			series: [{
				type: 'pie',
				innerSize: '60%',
				startAngle: 90,
				dataLabels: {
				    enabled: true,
				    distance: -20,
				    style: {
				    	fontFamily: 'glober_regularregular',
						fontWeight: 'bold',
						fontSize: '15px',
						color: '#000',
						textOutline: false
				    }
				},
				data: data_points
			}]
		});
		$('#status_total').html(status_percentage + "%");
	}
	
	//generate risk score doughnut chart
	function generate_score_chart(){
		var score_percentage = Math.round($("#score_percentage").attr("percentage"));
		var color_combination = RGBToHex(Math.round(255/100*score_percentage), Math.round(255 - 255/100*score_percentage), 0);
		var data_points = [{ name:'', y: 1, color: color_combination}];
		$("#score_chart").highcharts({
			chart: {
				plotBackgroundColor: null,
				plotBorderWidth: 0,
				plotShadow: false
			},
			title: {			  	
				text: 'Risk Score',
				align: 'center',
				verticalAlign: 'top',
				y: 3,
				style: {
					fontFamily: 'glober_regularregular',
					fontWeight: 'bold',
					color: '#000'
				}
			},
			series: [{
				type: 'pie',
				innerSize: '60%',
				startAngle: 90,
				dataLabels: {
				    enabled: false
				},
				data: data_points
			}]
		});
	}

	//generate maturity doughnut chart
	function generate_maturity_chart(total_population, green_population){
		var score_percentage = Math.round($("#score_percentage").attr("percentage"));
		var maturity_percentage = 0;
		if(total_population == 0){
			maturity_percentage = Math.round((100 - score_percentage)/2);
		} else {
			maturity_percentage = Math.round((Math.round(green_population/total_population*100) + 100 - score_percentage)/2);
		}
		var color_combination = RGBToHex(Math.round(255/100*(100 - maturity_percentage)), Math.round(255 - 255/100*(100 - maturity_percentage)), 0);
		var data_points = [{name:'', y: 1, color: color_combination}];
		$("#maturity_chart").highcharts({
			chart: {
				plotBackgroundColor: null,
				plotBorderWidth: 0,
				plotShadow: false
			},
			title: {			  	
				text: 'Maturity Score',
				align: 'center',
				verticalAlign: 'top',
				y: 3,
				style: {
					fontFamily: 'glober_regularregular',
					fontWeight: 'bold',
					color: '#000'
				}
			},
			series: [{
				type: 'pie',
				innerSize: '60%',
				startAngle: 90,
				dataLabels: {
				    enabled: false
				},
				data: data_points
			}]
		});
		$('#maturity_percentage').html(maturity_percentage + "%");
	}

	//generate data series line chart
	function generate_progress_timeline(total_population, green_population){
		var scoreDataPoints = [];
		var maturityDataPoints = [];
		var scoreArr = {$cb(json_encode($scoreArr))};
		var datesArr = {$cb(json_encode($datesArr))};
		for(var i=0; i<datesArr.length; i++){			
			scoreDataPoints.push({x: new Date(datesArr[i]), y:scoreArr[i]});
			if(total_population == 0){				
				maturityDataPoints.push({x: new Date(datesArr[i]), y:Math.round((100 - scoreArr[i])/2)});
			} else {				
				maturityDataPoints.push({x: new Date(datesArr[i]), y:Math.round((Math.round(green_population/total_population*100) + 100 - scoreArr[i])/2)});
			}
		}
		Highcharts.chart('progress_timeline', {
			chart: {
		        type: 'line'
		    },
		    title: {
		        text: 'Status Timeline',
		        style: {
		        	fontFamily: 'glober_regularregular',
					fontWeight: 'bold',
					color: '#000'
		        }
		    },
		    xAxis: {
		        type: 'datetime',
		        dateTimeLabelFormats: {
		            second: '%Y-%m-%d<br/>%H:%M:%S',
		            minute: '%Y-%m-%d<br/>%H:%M',
		            hour: '%Y-%m-%d<br/>%H:%M',
		            day: '%Y<br/>%m-%d',
		            week: '%Y<br/>%m-%d',
		            month: '%Y-%m',
		            year: '%Y'
		        }
		    },
		    yAxis: {
		    	title: 'Percent',
		        min: 0
		    },
		    tooltip: {
		        headerFormat: '<b>{series.name}: {point.y}%</b><br>',
		        pointFormat: '{point.x:%e %b, %Y %H:%M}'
		    },
		    plotOptions: {
		        spline: {
		            marker: {
		                enabled: true
		            }
		        }
		    },
		    colors: ['#F95360', '#33BF8C'],
		    series: [
		    	{
			        name: "Score",
			        data: scoreDataPoints
			    },
			    {
			        name: "Maturity",
			        data: maturityDataPoints
			    }
			]
		});
	}

	var statusIndicatorArr = [parseInt($('#status_chart').attr('data-gray')), parseInt($('#status_chart').attr('data-red')), parseInt($('#status_chart').attr('data-yellow')), parseInt($('#status_chart').attr('data-green'))];

	$('#total-status').html('<span><img class="status-icon" src="images/Circle_Gray.png">&nbsp;'+(statusIndicatorArr[0])+'</span><span style="margin-left:20px;"><img class="status-icon" src="images/Circle_Red.png">&nbsp;'+statusIndicatorArr[1]+'</span><span style="margin-left:20px;"><img class="status-icon" src="images/Circle_Yellow.png">&nbsp;'+statusIndicatorArr[2]+'</span><span style="margin-left:20px;"><img class="status-icon" src="images/Circle_Green.png">&nbsp;'+statusIndicatorArr[3]+'</span>');
	
	generate_status_chart([statusIndicatorArr[0], statusIndicatorArr[1], statusIndicatorArr[2], statusIndicatorArr[3]]);
	generate_score_chart();
	generate_maturity_chart(statusIndicatorArr[0]+statusIndicatorArr[1]+statusIndicatorArr[2]+statusIndicatorArr[3], statusIndicatorArr[3]);
	generate_progress_timeline(statusIndicatorArr[0]+statusIndicatorArr[1]+statusIndicatorArr[2]+statusIndicatorArr[3], statusIndicatorArr[3]);

	$(document).on('click', 'img.status-icon-action-view', function(){
		var _selector = $(this);
		var form_id = _selector.attr('data-form_id');
		var element_id = _selector.attr('data-element_id');
		var company_id = _selector.attr('data-company_id');
		var entry_id = _selector.attr('data-entry_id');
		var indicator = _selector.attr('data-indicator');
		$.ajax({
			url:"processupload.php",
			type:"POST",
			data:{mode:"updateindicator", form_id:form_id, element_id:element_id, company_id:company_id, entry_id:entry_id, indicator:indicator},
			beforeSend: function(){},
			success: function(response){
				response = JSON.parse(response);
				_selector.attr('src', 'images/Circle_'+response.status_icon+'.png');
				_selector.attr('data-indicator', response.indicator);
				if(response.indicator == 3) {
					var status_green = parseInt($('#status_chart').attr('data-green'));
					$('#status_chart').attr('data-green', status_green + 1);
					var status_yellow = parseInt($('#status_chart').attr('data-yellow'));
					$('#status_chart').attr('data-yellow', status_yellow - 1);
				} else if(response.indicator == 2) {
					var status_yellow = parseInt($('#status_chart').attr('data-yellow'));
					$('#status_chart').attr('data-yellow', status_yellow + 1);
					var status_red = parseInt($('#status_chart').attr('data-red'));
					$('#status_chart').attr('data-red', status_red - 1);
				} else if(response.indicator == 1) {
					var status_red = parseInt($('#status_chart').attr('data-red'));
					$('#status_chart').attr('data-red', status_red + 1);
					var status_gray = parseInt($('#status_chart').attr('data-gray'));
					$('#status_chart').attr('data-gray', status_gray - 1);
				} else if(response.indicator == 0) {
					var status_gray = parseInt($('#status_chart').attr('data-gray'));
					$('#status_chart').attr('data-gray', status_gray + 1);
					var status_green = parseInt($('#status_chart').attr('data-green'));
					$('#status_chart').attr('data-green', status_green - 1);
				}
				
				var statusIndicatorArr = [parseInt($('#status_chart').attr('data-gray')), parseInt($('#status_chart').attr('data-red')), parseInt($('#status_chart').attr('data-yellow')), parseInt($('#status_chart').attr('data-green'))];

				$('#total-status').html('<span><img class="status-icon" src="images/Circle_Gray.png">&nbsp;'+(statusIndicatorArr[0]-(statusIndicatorArr[1]+statusIndicatorArr[2]+statusIndicatorArr[3]))+'</span><span style="margin-left:20px;"><img class="status-icon" src="images/Circle_Red.png">&nbsp;'+statusIndicatorArr[1]+'</span><span style="margin-left:20px;"><img class="status-icon" src="images/Circle_Yellow.png">&nbsp;'+statusIndicatorArr[2]+'</span><span style="margin-left:20px;"><img class="status-icon" src="images/Circle_Green.png">&nbsp;'+statusIndicatorArr[3]+'</span>');
				
				generate_status_chart([statusIndicatorArr[0], statusIndicatorArr[1], statusIndicatorArr[2], statusIndicatorArr[3]]);
				generate_maturity_chart(statusIndicatorArr[0]+statusIndicatorArr[1]+statusIndicatorArr[2]+statusIndicatorArr[3], statusIndicatorArr[3]);
				generate_progress_timeline(statusIndicatorArr[0]+statusIndicatorArr[1]+statusIndicatorArr[2]+statusIndicatorArr[3], statusIndicatorArr[3]);
			},
			complete:function(){
				
			}
		});
	});
});
</script>
EOT;

	require('includes/footer.php');
?>

<script>
	var autoSave = localStorage.getItem("auto-save-then-logout");
	if(autoSave && autoSave == "true") {
		localStorage.removeItem("auto-save-then-logout");
		document.location.href = "/auditprotocol/logout.php";
	}
</script>