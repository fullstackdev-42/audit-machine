<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/

 More info at: http://lazarusalliance.com/
 ********************************************************************************/
	require('includes/init.php');

	header("p3p: CP=\"IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT\"");

	require('config.php');
	require('includes/language.php');
	require('includes/db-core.php');
	require('includes/common-validator.php');
	require('includes/docxhelper-functions.php');
	require('includes/post-functions.php');
	require('includes/filter-functions.php');
	require('includes/entry-functions.php');
	require('includes/helper-functions.php');
	require('includes/view-functions.php');
	require('includes/theme-functions.php');
	require('lib/swift-mailer/swift_required.php');
	require('lib/HttpClient.class.php');
	require('lib/recaptchalib.php');
	require('lib/php-captcha/php-captcha.inc.php');
	require('lib/text-captcha.php');
	require('hooks/custom_hooks.php');
	require_once("../policymachine/classes/CreateDocx.php");

	//get data from database
	$dbh = la_connect_db();
	$ssl_suffix = la_get_ssl_suffix();

	$_SESSION['admin'] = base64_decode($_REQUEST['userid']);
	$company_id = $_SESSION['admin'];
	$form_id 	= (int)trim($_GET['id']);

	$tmp_form_data = NULL;
	$submit_result = array();

	$_SESSION['tmp_company_user_id'] = time();

	if(la_is_form_submitted()){ //if form submitted
		$input_array   = la_sanitize($_POST);
		$tmp_form_data = getFormData(array(
			'dbh' => $dbh,
			'form_id' => $form_id,
			'column' => 'form_page_total'
		));
		//echo '<pre>';print_r($input_array);echo '</pre>';die;
		$submit_result = la_process_form($dbh,$input_array);
		if(!isset($input_array['password'])){ //if normal form submitted

			if($submit_result['status'] === true){

				if(!empty($submit_result['form_resume_url'])){ //the user saving a form, display success page with the resume URL
					$_SESSION['la_form_resume_url'][$input_array['form_id']] = $submit_result['form_resume_url'];
					header("Location: /auditprotocol/embed.php?id={$input_array['form_id']}&done=1&resume_form=1");
					exit;
				}else if($submit_result['logic_page_enable'] === true){ //the page has skip logic enable and a custom destination page has been set
					$target_page_id = $submit_result['target_page_id'];
					if(is_numeric($target_page_id)){
						header("Location: /auditprotocol/embed.php?id={$input_array['form_id']}&la_page={$target_page_id}");
						exit;
					}else if($target_page_id == 'payment'){
						//redirect to payment page, based on selected merchant
						$form_properties = la_get_form_properties($dbh,$input_array['form_id'],array('payment_merchant_type'));
						if(in_array($form_properties['payment_merchant_type'], array('stripe','authorizenet','paypal_rest','braintree'))){
							//allow access to payment page
							$_SESSION['la_form_payment_access'][$input_array['form_id']] = true;
							$_SESSION['la_payment_record_id'][$input_array['form_id']] = $submit_result['entry_id'];

							// edited on 05-11-2014
							header("Location: /auditprotocol/embed.php?id={$input_array['form_id']}&done=1");
							exit;
						}else if($form_properties['payment_merchant_type'] == 'paypal_standard'){
							header("Location: /auditprotocol/embed.php?id={$input_array['form_id']}&done=1");
							exit;
						}
					}else if($target_page_id == 'review'){
						if(!empty($submit_result['origin_page_number'])){
							$page_num_params = '&la_page_from='.$submit_result['origin_page_number'];
						}
						$_SESSION['review_id'] = $submit_result['review_id'];
						header("Location: confirm_embed.php?id={$input_array['form_id']}{$page_num_params}");
						exit;
					}else if($target_page_id == 'success'){
						//redirect to success page
						if(empty($submit_result['form_redirect'])){
							header("Location: /auditprotocol/embed.php?id={$input_array['form_id']}&done=1");
							exit;
						}else{
							header("Location: /auditprotocol/embed.php?id={$input_array['form_id']}&done=1");
							exit;
						}
					}
				}else{
					if(isset($input_array['go_to_ask_page']) && !empty($input_array['go_to_ask_page'])){
                        $_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;
						header("Location: /auditprotocol/embed.php?id={$input_array['form_id']}&la_page={$input_array['go_to_ask_page']}");
						exit;
                    }
					if(isset($input_array['casecade_form_page_number']) && $input_array['casecade_form_page_number'] && $input_array['page_number'] == $tmp_form_data[0]['form_page_total']){
						header("Location: /auditprotocol/embed.php?id={$input_array['form_id']}&done=1");
						exit;
					}
					/*if(isset($_REQUEST['parent_nxt_element']) && $_REQUEST['parent_nxt_element']){
						$_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;
						$_REQUEST['casecade_form_page_number'] = 2;
						header("Location: /auditprotocol/embed.php?id={$input_array['form_id']}&la_page={$submit_result['next_page_number']}&casecade_form_page_number={$_REQUEST['casecade_form_page_number']}&casecade_element_position={$_REQUEST['casecade_element_position']}&parent_nxt_element={$_REQUEST['parent_nxt_element']}");
						exit;
					}*/
					if(isset($_REQUEST['casecade_form_page_number']) && $_REQUEST['casecade_form_page_number'] ){
						$_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;

						if($_REQUEST['casecade_form_page_number'] != 'NO_ELEMENTS') {
							$_REQUEST['casecade_form_page_number']++;
						}

						header("Location: /auditprotocol/embed.php?id={$input_array['form_id']}&la_page={$submit_result['next_page_number']}&casecade_form_page_number={$_REQUEST['casecade_form_page_number']}&casecade_element_position={$_REQUEST['casecade_element_position']}");
						exit;
					}
					if(!empty($submit_result['next_page_number'])){ //redirect to the next page number
						$_SESSION['la_form_access'][$input_array['form_id']][$submit_result['next_page_number']] = true;
						header("Location: /auditprotocol/embed.php?id={$input_array['form_id']}&la_page={$submit_result['next_page_number']}");
						exit;
					} else if(!empty($submit_result['review_flag']) && $submit_result['review_flag'] == 1){ //redirect to review page
						//echo 'Test'; exit;
						if(!empty($submit_result['origin_page_number'])){
							$page_num_params = '&la_page_from='.$submit_result['origin_page_number'];
						}

						$_SESSION['review_id'] = $submit_result['review_id'];
						header("Location: confirm_embed.php?id={$input_array['form_id']}{$page_num_params}");
						exit;
					} else{ //otherwise display success message or redirect to the custom redirect URL or payment page


						if(la_is_payment_has_value($dbh,$input_array['form_id'],$submit_result['entry_id'])){
							//redirect to credit card payment page, if the merchant is being enabled and the amount is not zero
							//allow access to payment page
							$_SESSION['la_form_payment_access'][$input_array['form_id']] = true;
							$_SESSION['la_payment_record_id'][$input_array['form_id']] = $submit_result['entry_id'];
							// edited on 08-11-2015
							header("Location: /auditprotocol/embed.php?id={$input_array['form_id']}&done=1");
							exit;
						}else{
							//redirect to success page
							if(empty($submit_result['form_redirect'])){
								header("Location: /auditprotocol/embed.php?id={$input_array['form_id']}&done=1");
								exit;
							}else{
								header("Location: /auditprotocol/embed.php?id={$input_array['form_id']}&done=1");
								exit;
							}
						}
					}
				}

			}else if($submit_result['status'] === false){ //there are errors, display the form again with the errors
				$old_values 	= $submit_result['old_values'];
				$custom_error 	= @$submit_result['custom_error'];
				$error_elements = $submit_result['error_elements'];

				$form_params = array();
				$form_params['integration_method'] = 'iframe';
				$form_params['page_number'] = $input_array['page_number'];
				$form_params['populated_values'] = $old_values;
				$form_params['error_elements'] = $error_elements;
				$form_params['custom_error'] = $custom_error;

				$markup = la_display_form($dbh,$input_array['form_id'],$form_params);
			}
		}else{ //if password form submitted

			if($submit_result['status'] === true){ //on success, display the form
				$markup = la_display_form($dbh,$input_array['form_id'],true);
			}else{
				$custom_error = $submit_result['custom_error']; //error, display the pasword form again

				$form_params = array();
				$form_params['integration_method'] = 'iframe';
				$form_params['custom_error'] = $custom_error;
 				$markup = la_display_form($dbh,$input_array['form_id'],$form_params,true);
			}
		}
	}else{
		$page_number	= (int) trim($_GET['la_page']);
		//commented this out , now user can jump to any page
		// $page_number 	= la_verify_page_access($form_id,$page_number);
		$resume_key		= trim($_GET['la_resume']);
		if( empty($page_number) )
			$page_number = 1;

		if(!empty($resume_key)){
			$_SESSION['la_form_resume_key'][$form_id] = $resume_key;
		}

		if(isset($_GET['done']) && !empty($_GET['done'])){
			/*********************************************************************************/

			$query_template_count = "SELECT COUNT(*) AS counter FROM `".LA_TABLE_PREFIX."form_template` WHERE `form_id` = ".$form_id;
			$param_template_count = array();
			$param_template_count[':form_id'] = $form_id;
			$result_template_count = la_do_query($query_template_count,$param_template_count,$dbh);
			$num_rows = $result_template_count->fetchColumn();


			$query_forms = "SELECT * FROM `".LA_TABLE_PREFIX."form_".$form_id."` WHERE `field_name` LIKE 'element_%' AND company_id=".$_SESSION['company_user_id']."";
			$param_forms = array();
			$result_forms = la_do_query($query_forms,$param_forms,$dbh);

			while($row = la_do_fetch_result($result_forms)){
				$row_forms[$row['field_name']] = $row['data_value'];
			}

			//echo '<pre>'.print_r($row_forms,1).'</pre>';

			$element_array = array();
			$replace_data_array = array();
			$file_variable_list = array();
			$address_counter = 0;

			$query_form_element = "SELECT `element_id`, `element_type`, `element_machine_code`, `element_matrix_allow_multiselect`, `element_matrix_parent_id`, `element_default_value` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = :form_id AND `element_type` != 'section' AND `element_type` != 'page_break' AND `element_type` != 'syndication' order by element_id asc";

			$param_form_element = array();
			$param_form_element[':form_id'] = $form_id;
			$result_form_element = la_do_query($query_form_element,$param_form_element,$dbh);

			while($row_form_template = la_do_fetch_result($result_form_element)){
				$element_array[$row_form_template['element_id']] 						 			 = array();
				$element_array[$row_form_template['element_id']]['element_type'] 		 			 = $row_form_template['element_type'];
				$element_array[$row_form_template['element_id']]['element_machine_code'] 			 = $row_form_template['element_machine_code'];
				$element_array[$row_form_template['element_id']]['element_matrix_allow_multiselect'] = $row_form_template['element_matrix_allow_multiselect'];
				$element_array[$row_form_template['element_id']]['element_matrix_parent_id'] 		 = $row_form_template['element_matrix_parent_id'];
				$element_array[$row_form_template['element_id']]['element_id'] 						 = $row_form_template['element_id'];
				$element_array[$row_form_template['element_id']]['element_default_value'] 			 = $row_form_template['element_default_value'];
			}

			//echo '<pre>'.print_r($element_array,1).'</pre>';

			if(count($element_array) > 0){
				foreach($element_array as $element_id => $element){
					$replace_string = array();
					if($element['element_type'] == 'simple_name'){

						if(trim($element['element_machine_code']) != '' && trim($element['element_machine_code']) != 'Null'){

							$replace_data_array[$element['element_machine_code']] = $row_forms['element_'.$element_id.'_1']." ".$row_forms['element_'.$element_id.'_2'];

						}

					}
					elseif($element['element_type'] == 'address'){

						if(trim($element['element_machine_code']) !='' && trim($element['element_machine_code']) != 'Null'){

							$replace_data_array[$element['element_machine_code']] = $row_forms['element_'.$element_id.'_1']." ".$row_forms['element_'.$element_id.'_2']." ".$row_forms['element_'.$element_id.'_3']." ".$row_forms['element_'.$element_id.'_4']." ".$row_forms['element_'.$element_id.'_5']." ".$row_forms['element_'.$element_id.'_6'];

						}

					    // update form address field
					    updateScoreField($dbh, $form_id, $_SESSION['company_user_id'], array('element_'.$element_id.'_4'));

					}
					elseif($element['element_type'] == 'radio' || $element['element_type'] == 'checkbox' || $element['element_type'] == 'matrix' || $element['element_type'] == 'select'){

						if($element['element_type'] == 'matrix'){
							$element['element_type'] = getMatrixNewType($dbh, $form_id, $element);
						}

						if($element['element_type'] == 'checkbox'){

							$query_element_option = "SELECT `option_id`, `option`, `option_value` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id ORDER BY `option_id` DESC";

							$param_element_option = array();
							$param_element_option[':form_id'] = $form_id;
							$param_element_option[':element_id'] = $element_id;
							$i= 0;
							$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);

							while($row_element_option = la_do_fetch_result($result_element_option)){
								$query20 = "SELECT field_score FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE field_name='element_".$element_id.'_'.$row_element_option['option_id']."' AND company_id='".$_SESSION['company_user_id']."'";

								$result20 = la_do_query($query20,$params_table_data,$dbh);
								$row20 = la_do_fetch_result($result20);

								if(!empty($row_forms['element_'.$element_id.'_'.$row_element_option['option_id']]) && $row_forms['element_'.$element_id.'_'.$row_element_option['option_id']] >= 1){
									$replace_string[] =  $row_element_option['option'];
									$score += $row_element_option['option_value'];


									if(empty($row20['field_score'])){
										$filed_score = $row_element_option['option_value'];
									} else {
										$filed_score = $row20['field_score'].','.$row_element_option['option_value'];
									}

									$query17 = "UPDATE `".LA_TABLE_PREFIX."form_{$form_id}` SET field_score= '".$filed_score."' WHERE field_name='element_".$element_id.'_'.$row_element_option['option_id']."' AND company_id='".$_SESSION['company_user_id']."'";
									la_do_query($query17,$params_table_data,$dbh);

								} else {

									if($row20['field_score'] == ''){

										$filed_score = '0';

									} else {
										$filed_score = $row20['field_score'].',0';

									}

									$query17 = "UPDATE `".LA_TABLE_PREFIX."form_{$form_id}` SET field_score= '".$filed_score."' WHERE field_name='element_".$element_id.'_'.$row_element_option['option_id']."'  AND company_id='".$_SESSION['company_user_id']."'";
									la_do_query($query17,$params_table_data,$dbh);
								}
							}


							if(isset($row_forms['element_'.$element_id.'_other']) && !empty($row_forms['element_'.$element_id.'_other'])){
								$replace_string[] =  $row_forms['element_'.$element_id.'_other'];
							}

							$_string = implode("\r\n", $replace_string);

							if(trim($element['element_machine_code']) !='' && trim($element['element_machine_code']) != 'Null'){

								$replace_data_array[$element['element_machine_code']] = $_string;
							}

						}else{

							$query_element_option = "SELECT `option`, `option_id`, `option_value` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id AND `option_id` = :option_id";
							$param_element_option = array();
							$param_element_option[':form_id'] = $form_id;
							$param_element_option[':element_id'] = $element_id;
							$param_element_option[':option_id'] = (int) $row_forms['element_'.$element_id];
							$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
							$row_element_option = la_do_fetch_result($result_element_option);

							if(isset($row_forms['element_'.$element_id.'_other']) && !empty($row_forms['element_'.$element_id.'_other'])){
								$replace_string[] =  $row_forms['element_'.$element_id.'_other'];
							}
							if(!empty($row_element_option['option'])){
								$replace_string[] =  $row_element_option['option'];
							}
							$_string = implode("\r\n", $replace_string);

							if(trim($element['element_machine_code']) !='' && trim($element['element_machine_code']) != 'Null'){

								$replace_data_array[$element['element_machine_code']] = $_string;
							}

							$score += $row_element_option['option_value'];

							$query20 = "SELECT field_score FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE field_name='element_".$element_id."' AND company_id='".$_SESSION['company_user_id']."'";

							$result20 = la_do_query($query20,$params_table_data,$dbh);
							$row20 = la_do_fetch_result($result20);

							if($row20['field_score'] == ''){
								$filed_score = $row_element_option['option_value'];
							} else {
								$filed_score = $row20['field_score'].','.$row_element_option['option_value'];
							}

							$query17 = "UPDATE `".LA_TABLE_PREFIX."form_{$form_id}` SET field_score= '".$filed_score."' WHERE field_name='element_".$element_id."' AND data_value != '' AND company_id='".$_SESSION['company_user_id']."'";
							la_do_query($query17,$params_table_data,$dbh);
						}

					}
					elseif($element['element_type'] == 'phone'){
						$phone_val = substr($row_forms['element_'.$element_id],0,3).'-'.substr($row_forms['element_'.$element_id],3,3).'-'.substr($row_forms['element_'.$element_id],6,4);
						if(trim($element['element_machine_code']) !='' && trim($element['element_machine_code']) != 'Null'){
							$replace_data_array[$element['element_machine_code']] = $phone_val;
						}
					}
					elseif($element['element_type'] == 'casecade_form'){
						$case_cade_replace_data_array = calculateScoreAndGenerateDoc(array('dbh' => $dbh, 'la_settings' => $la_settings, 'form_id' => $element['element_default_value'], 'company_id' => $_SESSION['company_user_id'], 'user_id' => $_SESSION['la_user_id']));

						// merge casecade policy machine code to the main form replace variable so that if any casecade policy machine code found on doc can be replace.
						$replace_data_array = array_merge($replace_data_array, $case_cade_replace_data_array);
					}
					elseif($element['element_type'] == 'file'){
						$replace_data_array[$element['element_machine_code']] = $row_forms['element_'.$element_id];
						array_push($file_variable_list, $element['element_machine_code']);
					}
					else{
						if(trim($element['element_machine_code']) !='' && trim($element['element_machine_code']) != 'Null'){
							$replace_data_array[$element['element_machine_code']] = $row_forms['element_'.$element_id];
						}

					}
				}

				// echo '<pre>';print_r($replace_data_array);echo '</pre>';exit();
			    // update form address field and date field
			    updateScoreField($dbh, $form_id, $_SESSION['company_user_id'], array('date_created'));

			  	$timestamp = time();

				if($num_rows > 0){
					$query_template = "SELECT `template` FROM `".LA_TABLE_PREFIX."form_template` WHERE `form_id` = :form_id";
					$param_template = array();
					$param_template[':form_id'] = $form_id;
					$result_template = la_do_query($query_template,$param_template,$dbh);

					$iLoop = 1;
					if(extension_loaded('zip')){
						/***************F O R M  N A M E ****************/
						$query_form  = "SELECT `form_name` FROM `ap_forms` WHERE `form_id` = :form_id";
						$result_form = la_do_query($query_form, array(':form_id' => $form_id), $dbh);
						$row_form    = la_do_fetch_result($result_form);
						$form_name   = trim($row_form['form_name']);
						$form_name   = str_replace(" ", "_", $form_name);
						$form_name	 = preg_replace('/[^A-Za-z0-9\_]/', '_', $form_name);
						$form_name	 = substr($form_name, 0, 24);
						/***************F O R M  N A M E ****************/

						$zip = new ZipArchive();
						$zip_name = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$form_name."_".$timestamp.".zip";

						if($num_rows > 0){
							if($zip->open($zip_name, ZIPARCHIVE::CREATE)!==TRUE){

							}
						}

						while($row_template = la_do_fetch_result($result_template)){

							$template_document = trim($row_template['template']);

							if(file_exists($template_document) == true){

								$fileExt = end(explode(".", $template_document));

								if($fileExt == 'docx'){
									$template_arr = explode("/", $template_document);
									$template_raw_name = end($template_arr);
									$template_name_split = explode('.', $template_raw_name);
									$template_name = $template_name_split[0]."_".$timestamp;

									if(count($replace_data_array) > 0){
										$docx = new CreateDocxFromTemplate($template_document);

										foreach($replace_data_array as $key => $value){
											if(in_array($key, $file_variable_list)){
												if(strpos($value, "|") !== false){
													$value = end(explode("|", $value));
												}

												$value = $_SERVER["DOCUMENT_ROOT"].'/auditprotocol/data/form_'.$form_id.'/files/'.$value;
												//$docx->replacePlaceholderImage($key, $value, array('height' => 3, 'width' => 3, 'target' => 'document'));
                                                //$docx->replaceVariableByText(array($key => $value), array('document' => true));

                                              	$image = new WordFragment($docx, 'document');
											  	$image->addImage(array('src' => $value , 'scaling' => 30, 'float' => 'left', 'textWrap' => 1));
                                                $docx->replaceVariableByWordFragment(array($key => $image), array('type' => 'block'));
											}else{
												$docx->replaceVariableByText(array($key => $value), array('target' => 'header'));
												$docx->replaceVariableByText(array($key => $value), array('document' => true));
												$docx->replaceVariableByText(array($key => $value), array('target' => 'footer'));
											}
										}

										$docx->createDocx($_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$template_name);
									}

									$query_docx_insert = "INSERT INTO `".LA_TABLE_PREFIX."template_document_creation` (`docx_id`, `client_id`, `company_id`, `form_id`, `docx_create_date`, `docxname`) VALUES (null, :client_id, :company_id, :form_id, :docx_create_date, :docxname)";
									$params_docx_insert = array();
									$params_docx_insert[':client_id'] = (int) $_SESSION['la_user_id'];
									$params_docx_insert[':company_id'] = (int) $_SESSION['company_user_id'];
									$params_docx_insert[':form_id'] = $form_id;
									$params_docx_insert[':docx_create_date'] = $timestamp;
									$params_docx_insert[':docxname'] = (string) $template_name.'.docx';
									la_do_query($query_docx_insert,$params_docx_insert,$dbh);
									$iLoop++;

									$zip->addFile($_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$template_name.'.docx', $template_name.'.docx');
								}else{
									// Create new PHPExcel object
									$objPHPExcel = PHPExcel_IOFactory::createReader('Excel2007');
									$objPHPExcel = $objPHPExcel->load($template_document);

									// Create a first sheet, representing sales data
									$sheetCount = $objPHPExcel->getSheetCount();
									$qa = array();

									for($o=0; $o<$sheetCount; $o++){
										$sheet = $objPHPExcel->getSheet($o);
										//echo '<pre>';print_r($replace_data_array);echo '</pre>';
										/*foreach($replace_data_array as $k => $v){
											$foundInCells = array();
											$searchValue = '$'.$k.'$';*/

											/*foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
												$ws = $worksheet->getTitle();*/
												foreach ($sheet->getRowIterator() as $row) {
													$cellIterator = $row->getCellIterator();
													$cellIterator->setIterateOnlyExistingCells(true);

													foreach($cellIterator as $cell){
														if (isset($replace_data_array[str_replace("$", "", trim($cell->getValue()))])) {
															$sheet->setCellValue($cell->getCoordinate(), $replace_data_array[str_replace("$", "", trim($cell->getValue()))]);
														}
													}
												}
											//}
										//}
										//echo '<pre>';print_r($qa);echo '</pre>';
									}

									$filename  = $_SERVER["DOCUMENT_ROOT"].'/portal/template_output/'.$timestamp.'.xlsx';
									$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
									$objWriter->save($filename);

									sleep(1);

									$zip->addFile($filename, $timestamp.'.xlsx');
								}
							}

						}

						if($num_rows > 0){
							$zip->close();
							$query_docx_insert = "INSERT INTO `".LA_TABLE_PREFIX."template_document_creation` (`docx_id`, `client_id`, `company_id`, `form_id`, `docx_create_date`, `docxname`, `isZip`) VALUES (null, :client_id, :company_id, :form_id, :docx_create_date, :docxname, '1')";
							$params_docx_insert = array();
							$params_docx_insert[':client_id'] = (int) $_SESSION['la_user_id'];
							$params_docx_insert[':company_id'] = (int) $_SESSION['company_user_id'];
							$params_docx_insert[':form_id'] = $form_id;
							$params_docx_insert[':docx_create_date'] = $timestamp;
							$params_docx_insert[':docxname'] = (string) $form_name."_".$timestamp.".zip";

							la_do_query($query_docx_insert,$params_docx_insert,$dbh);
						}
					}
				}
			}

			unset($_SESSION['company_user_id']);

			if(isset($_SESSION['tmp_company_user_id']))
				unset($_SESSION['tmp_company_user_id']);

			$query_form_data_sql = "SELECT `form_id`, `form_redirect`, `form_redirect_enable` FROM `ap_forms` WHERE `form_id` = :form_id";
			$query_form_data_result = la_do_query($query_form_data_sql,array(':form_id' => $form_id),$dbh);
			$query_form_data_row = la_do_fetch_result($query_form_data_result);

			/*if(!empty($query_form_data_row['form_redirect_enable']) && $query_form_data_row['form_redirect_enable'] == 1){

				header("location:{$query_form_data_row['form_redirect']}");
				exit();

			}else{*/
				$markup = la_display_success($dbh,$form_id,array(),0);
			/*}*/
		}else{
			/**********************************************/
			/*   Fetching Company data from clent table   */
			/**********************************************/
			$showSubmit = true;
			/**********************************************************************/
			{
				$form_params = array();
				$form_params['integration_method'] = 'iframe';
				$form_params['page_number'] = $page_number;
				$markup = la_display_form($dbh,$form_id,$form_params,$showSubmit);
			}
			/***************************************************************/
		}
	}

	if(!isset($_SESSION['userid'])){
		$_SESSION['userid'] = base64_decode($_REQUEST['userid']);
	}

	if(isset($_SESSION['userid'])){
		header("Content-Type: text/html; charset=UTF-8");
		echo $markup;
	} else {
		echo '<center><h1>This form is not viewable. Please log into your portal and subscribe to the form.</h1></center>';
	}
