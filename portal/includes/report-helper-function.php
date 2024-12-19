<?php
function matrixNewType($dbh, $form_id, $element_id){
	$element_query_matrix = "select element_id, element_matrix_parent_id, element_matrix_allow_multiselect from `".LA_TABLE_PREFIX."form_elements` where `form_id` = :form_id and `element_id` = :element_id";
	$element_result_matrix = la_do_query($element_query_matrix,array(':form_id' => $form_id, ':element_id' => $element_id),$dbh);
	$element = la_do_fetch_result($element_result_matrix);
	
	if($element['element_matrix_allow_multiselect'] == 1 && $element['element_matrix_parent_id'] == 0){
		$element['element_type'] = 'checkbox';
	}else{
		$query_matrix = "select element_matrix_allow_multiselect from `".LA_TABLE_PREFIX."form_elements` where `form_id` = :form_id and `element_id` = :element_id";
		$result_element_matrix = la_do_query($query_matrix,array(':form_id' => $form_id, ':element_id' => $element['element_matrix_parent_id']),$dbh);
		$row_element_matrix = la_do_fetch_result($result_element_matrix);

		if($row_element_matrix['element_matrix_allow_multiselect'] == 1){
			$element['element_type'] = 'checkbox';
		}else{
			$element['element_type'] = 'radio';
		}
	}
	
	return $element['element_type'];
}

function storeScore($dbh, $form_id, $company_id){
	$query = "select id_of_form_entry from ".LA_TABLE_PREFIX."form_submission_details where `company_id` = :company_id and `form_id` = :form_id";	
	$params = array();
	$params[':company_id'] = $company_id;
	$params[':form_id'] = $form_id;
	$resultset = la_do_query($query,$params,$dbh);
	$rowdata = la_do_fetch_result($resultset);
	
	if(!empty($rowdata['id_of_form_entry'])){
		
		$id = $rowdata['id_of_form_entry'];
		$query_forms = "SELECT * FROM `".LA_TABLE_PREFIX."form_".$form_id."` WHERE `id` = :id";
		$param_forms = array();
		$param_forms[':id'] = $id;
		$result_forms = la_do_query($query_forms,$param_forms,$dbh);
		$row_forms = la_do_fetch_result($result_forms);
		
		$element_array = array();
		$query_form_element = "SELECT `element_id`, `element_type` FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = :form_id AND `element_status` = '1' AND `element_type` <> 'section' AND `element_type` <> 'page_break' AND (`element_type` = 'checkbox' OR `element_type` = 'radio' OR `element_type` = 'select' OR `element_type` = 'address') AND element_status = '1'";
		$param_form_element = array();
		$param_form_element[':form_id'] = $form_id;
		$result_form_element = la_do_query($query_form_element,$param_form_element,$dbh);
		
		while($row_form_template = la_do_fetch_result($result_form_element)){
			$element_array[$row_form_template['element_id']] = array();
			$element_array[$row_form_template['element_id']]['element_type'] = $row_form_template['element_type'];
		}
		
		if(count($element_array) > 0){
			$genrate_query = "INSERT INTO `".LA_TABLE_PREFIX."form_report_score_{$form_id}` (";
			$column = array();
			$column_values = array();
			foreach($element_array as $key => $value){
				if($value['element_type'] == 'checkbox'){
					$query_element_option = "SELECT `option_id`, `option_value` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id AND live = '1' ORDER BY `option_id`";
					$param_element_option = array();
					$param_element_option[':form_id'] = $form_id;
					$param_element_option[':element_id'] = $key;
					$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
					while($row_element_option = la_do_fetch_result($result_element_option)){
						if($row_forms['element_'.$key.'_'.$row_element_option['option_id']] != 0){
							$column[] = (string)"element_{$key}_".$row_element_option['option_id'];
							$column_values[] = "'{$row_element_option['option_value']}'";	
						}else{
							$column[] = (string)"element_{$key}_".$row_element_option['option_id'];
							$column_values[] = "'0'";	
						}
					}
				}else if($value['element_type'] == 'address'){
					$column[] = "element_{$key}_1";
					$column[] = "element_{$key}_2";
					$column[] = "element_{$key}_3";
					$column[] = "element_{$key}_4";
					$column[] = "element_{$key}_5";
					$column[] = "element_{$key}_6";
					$column_values[] = "'".$row_forms['element_'.$key.'_1']."'";
					$column_values[] = "'".$row_forms['element_'.$key.'_2']."'";
					$column_values[] = "'".$row_forms['element_'.$key.'_3']."'";
					$column_values[] = "'".$row_forms['element_'.$key.'_4']."'";
					$column_values[] = "'".$row_forms['element_'.$key.'_5']."'";
					$column_values[] = "'".$row_forms['element_'.$key.'_6']."'";
				}else{
					$column[] = "element_{$key}";
					$query_element_option = "SELECT `option_value` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id AND `option_id` = :option_id AND live = '1'";
					$param_element_option = array();
					$param_element_option[':form_id'] = $form_id;
					$param_element_option[':element_id'] = $key;
					$param_element_option[':option_id'] = (int) $row_forms['element_'.$key];
					$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
					$row_element_option = la_do_fetch_result($result_element_option);
					$column_values[] = "'{$row_element_option['option_value']}'";
				}
			}
			$column[] = "company_id";
			$column_values[] = $company_id;
			$genrate_query .= implode(",", $column);
			$genrate_query .= ") VALUES (";
			$genrate_query .= implode(",", $column_values);
			$genrate_query .= ");";
			la_do_query($genrate_query,array(),$dbh);
		}
	}
}

function getScore($dbh, $report_id, $form_id, $company_id, $entry_id){
	
	$column = array();
	$column[$report_id] = array();
	
	$report_elements_query = "SELECT * FROM `".LA_TABLE_PREFIX."form_report_elements` WHERE `report_id` = :report_id AND `form_id` = :form_id";
	$report_elements_param = array();
	$report_elements_param[':report_id'] = $report_id;
	$report_elements_param[':form_id'] = $form_id;
	$report_elements_result = la_do_query($report_elements_query, $report_elements_param, $dbh);
	
	while($report_elements_row = la_do_fetch_result($report_elements_result)){
		$element_id = (int)$report_elements_row['element_id'];
		$element_type = $report_elements_row['element_type'];
        
        if($report_elements_row['element_type'] == 'matrix'){
			$element_type = matrixNewType($dbh, $form_id, $element_id);
		}
       
		if($element_type == 'checkbox'){
			$query_element_option = "SELECT `option_id`, `option_value` FROM `".LA_TABLE_PREFIX."element_options` WHERE `form_id` = :form_id AND `element_id` = :element_id ORDER BY `option_id`";
			$param_element_option = array();
			$param_element_option[':form_id'] = $form_id;
			$param_element_option[':element_id'] = $element_id;
			$result_element_option = la_do_query($query_element_option,$param_element_option,$dbh);
			while($row_element_option = la_do_fetch_result($result_element_option)){
				$column[$report_id][] = (string)"element_{$element_id}_".$row_element_option['option_id'];
			}
			$column[$report_id][] = (string)"element_{$element_id}_"."other";
		}
		else if($element_type == 'select'){
			$column[$report_id][] = (string)"element_{$element_id}";
		}
		else if($element_type == 'radio'){
			$column[$report_id][] = (string)"element_{$element_id}";
			$column[$report_id][] = (string)"element_{$element_id}_"."other";
		}
		else if($element_type == 'address'){
			$column[$report_id][] = (string)"element_{$element_id}_4";
		}
	}
	
	$column[$report_id][] = "date_created";

	$table_exists = "SELECT count(*) AS counter FROM information_schema.tables WHERE table_schema = '".LA_DB_NAME."' AND table_name = '".LA_TABLE_PREFIX."form_{$form_id}'";
	$result_table_exists = la_do_query($table_exists,array(),$dbh);
	$row_table_exists = la_do_fetch_result($result_table_exists);
	
	if($row_table_exists['counter'] == 1){
		$query_to_fetch_score = "SELECT `field_name`, `data_value`, `field_score` FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `field_name` IN ('".implode("','", $column[$report_id])."') AND `company_id` = ? AND `entry_id` = ?";
		$report_elements_score_result = la_do_query($query_to_fetch_score, array($company_id, $entry_id), $dbh);
		
		$column[$report_id][$form_id] = array();
		
		$tmpArray = array();
		
		while($report_elements_score_row = la_do_fetch_result($report_elements_score_result)){
			if(!empty($report_elements_score_row['field_score'])) {
				if(strpos($report_elements_score_row["field_name"], "other") !== false) {
					$query_default_value = "SELECT * FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE company_id= ? AND entry_id = ? AND field_name= ?";
					$sth_default_value = la_do_query($query_default_value, array($company_id, $entry_id, str_replace("_other", "", $report_elements_score_row["field_name"])), $dbh);
					$res_default_value = la_do_fetch_result($sth_default_value);
					
					if(!($res_default_value)){
						$reverseScoreArr = explode(",", $report_elements_score_row['field_score']);
						$scoreKeys = array_keys($reverseScoreArr);
						$scoreValues = array_values($reverseScoreArr);
						$reversed = array_reverse($scoreValues);
						$tmpArray[$report_elements_score_row['field_name']] = array_combine($scoreKeys, $reversed);
					} else {
						if(isset($res_default_value["data_value"]) && $res_default_value["data_value"] == "0"){
							$reverseScoreArr = explode(",", $report_elements_score_row['field_score']);
							$scoreKeys = array_keys($reverseScoreArr);
							$scoreValues = array_values($reverseScoreArr);
							$reversed = array_reverse($scoreValues);
							$tmpArray[$report_elements_score_row['field_name']] = array_combine($scoreKeys, $reversed);
						}
					}
				} else {
					if(isset($report_elements_score_row['data_value']) && $report_elements_score_row['data_value'] !="" && $report_elements_score_row['data_value'] !="0"){
						$reverseScoreArr = explode(",", $report_elements_score_row['field_score']);
						$scoreKeys = array_keys($reverseScoreArr);
						$scoreValues = array_values($reverseScoreArr);
						$reversed = array_reverse($scoreValues);
						$tmpArray[$report_elements_score_row['field_name']] = array_combine($scoreKeys, $reversed);
					}
				}							
			}
		}

		$fieldKeys = array_keys($tmpArray);
		$fieldValues = array_values($tmpArray);

		$no_of_data = count($fieldValues[0]);
	
		if($no_of_data > 0) {
			for($i=0; $i<$no_of_data; $i++) {
				$column[$report_id][$form_id][$i] = array();
				foreach($fieldKeys as $keys => $values){
					$column[$report_id][$form_id][$i][$values] = $fieldValues[$keys][$i];
				}
				$column[$report_id][$form_id][$i]['id'] = rand(100, 10000);
			}
		}		
		return array_reverse($column[$report_id][$form_id]);
	}
}

function getFormScores($dbh, $form_id, $company_id, $entry_id) {
	$query  = "SELECT * from ".LA_TABLE_PREFIX."form_{$form_id} WHERE `company_id` = ? AND `entry_id` = ?";
	$sth = la_do_query($query, array($company_id, $entry_id), $dbh);
	
	$totalScore = 0;
	$entry_data = array();
	$datesArr   = array();
	$reverseScoreArr = array();
	$scoreArr = array();
	$formIdArr = array($form_id);
	$return = array();
	$statusElementArr = array();
	// $latestElementScoreArr = array();
	while($row = la_do_fetch_result($sth)){
		$entry_data[$row['field_name']] = htmlspecialchars($row['data_value'],ENT_QUOTES);
		if($row['field_name'] == 'date_created'){
			if(!empty($row['field_score'])){
				$datesArr = explode(",", trim($row['field_score']));
			}
		}else{
			if($row['field_name'] != "ip_address") {
				$eleID = explode("code_", trim($row['field_code']))[1];

				if ($row['field_score'] != "") {
					if(strpos($row["field_name"], "other") !== false) {
						$query_default_value = "SELECT * FROM ".LA_TABLE_PREFIX."form_{$form_id} WHERE company_id=? AND entry_id = ? AND field_name=?";
						$sth_default_value = la_do_query($query_default_value, array($company_id, $entry_id, str_replace("_other", "", $row["field_name"])), $dbh);
						$res_default_value = la_do_fetch_result($sth_default_value);
						
						if(!($res_default_value)){
							$field_score = explode(",", $row['field_score']);
							if(isset($row["data_value"]) && $row["data_value"] !="") {
								for($i=count($field_score)-1; $i>-1; $i--){
									$reverseScoreArr[count($field_score)-1-$i] += (float) $field_score[$i];
								}
								$totalScore += (float) end(explode(",", trim($row['field_score'])));
								$statusElementArr[$form_id."_".$eleID]['score'] += (float) end(explode(",", trim($row['field_score'])));
							}
						} else {
							if(isset($res_default_value["data_value"]) && $res_default_value["data_value"] == "0"){
								$field_score = explode(",", $row['field_score']);
								if(isset($row["data_value"]) && $row["data_value"] !="") {
									for($i=count($field_score)-1; $i>-1; $i--){
										$reverseScoreArr[count($field_score)-1-$i] += (float) $field_score[$i];
									}
									$totalScore += (float) end(explode(",", trim($row['field_score'])));
									$statusElementArr[$form_id."_".$eleID]['score'] += (float) end(explode(",", trim($row['field_score'])));
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
							$statusElementArr[$form_id."_".$eleID]['score'] += (float) end(explode(",", trim($row['field_score'])));
						}
					}
				}
			}
		}
	}
	//get scores from cascaded sub forms
	$query_cascade_forms = "SELECT `element_default_value`, `element_position`, `element_page_number` FROM ".LA_TABLE_PREFIX."form_elements WHERE `form_id` = ? AND `element_type` = ?";
	$sth_cascade_forms = la_do_query($query_cascade_forms, array($form_id, "casecade_form"), $dbh);
	while($row_cascade_form = la_do_fetch_result($sth_cascade_forms)) {
		array_push($formIdArr, $row_cascade_form['element_default_value']);
		$query  = "SELECT * FROM ".LA_TABLE_PREFIX."form_{$row_cascade_form['element_default_value']} WHERE company_id=? AND entry_id = ?";
		$sth = la_do_query($query, array($company_id, $entry_id), $dbh);
		while($row = la_do_fetch_result($sth)){
			if($row['field_name'] != 'date_created' && $row['field_name'] != "ip_address"){
				$eleID = explode("code_", trim($row['field_code']))[1];
				
				if ($row['field_score'] != "") {
					if(strpos($row["field_name"], "other") !== false) {
						$query_default_value = "SELECT * FROM ".LA_TABLE_PREFIX."form_{$row_cascade_form['element_default_value']} WHERE company_id=? AND entry_id = ? AND field_name=?";
						$sth_default_value = la_do_query($query_default_value, array($company_id, $entry_id, str_replace("_other", "", $row["field_name"])), $dbh);
						$res_default_value = la_do_fetch_result($sth_default_value);
						
						if(!($res_default_value)){
							$field_score = explode(",", $row['field_score']);
							if(isset($row["data_value"]) && $row["data_value"] !="") {
								for($i=count($field_score)-1; $i>-1; $i--){
									$reverseScoreArr[count($field_score)-1-$i] += (float) $field_score[$i];
								}
								$totalScore += (float) end(explode(",", trim($row['field_score'])));
								$statusElementArr[$row_cascade_form['element_default_value']."_".$eleID]['score'] += (float) end(explode(",", trim($row['field_score'])));
							}
						} else {
							if(isset($res_default_value["data_value"]) && $res_default_value["data_value"] == "0"){
								$field_score = explode(",", $row['field_score']);
								if(isset($row["data_value"]) && $row["data_value"] !="") {
									for($i=count($field_score)-1; $i>-1; $i--){
										$reverseScoreArr[count($field_score)-1-$i] += (float) $field_score[$i];
									}
									$totalScore += (float) end(explode(",", trim($row['field_score'])));
									$statusElementArr[$row_cascade_form['element_default_value']."_".$eleID]['score'] += (float) end(explode(",", trim($row['field_score'])));
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
							$statusElementArr[$row_cascade_form['element_default_value']."_".$eleID]['score'] += (float) end(explode(",", trim($row['field_score'])));
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
	
	// $return['latest_element_score_array'] = $latestElementScoreArr;
	$return["total_score"] = $totalScore;

	$tArr = array();
	if(count($datesArr) > 1){
		foreach($datesArr as $key => $value){
			$tArr[] = strtotime($value);
		}
	}		
	$return["dates"] = $datesArr;

	$form_full_name = '';
	//get form name
	$query 	= "select 
					 form_name					 
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
		$return["form_name"] = $form_name;
	}else{
		die("Error. Unknown form ID.");
	}

	$cntStatusArr = array(0, 0, 0, 0);
	foreach ($formIdArr as $temp_form_id) {
		$query_count_status = '
								select
									e.*,
								  si.indicator indicator
								from
									'.LA_TABLE_PREFIX.'form_elements e
								LEFT JOIN '.LA_TABLE_PREFIX.'element_status_indicator si
								ON  e.element_id = si.element_id AND e.form_id = si.form_id AND si.company_id = ? AND si.entry_id = ?
									where
											e.form_id=? and
											e.element_status = 1
									group by
									element_id
									order by
										indicator,
										e.element_position
								';
		$sth_count_status = la_do_query( $query_count_status, array($company_id, $entry_id, $temp_form_id), $dbh );

		while($data = la_do_fetch_result($sth_count_status)) {
			# STATUS
			if ( in_array( $data['element_type'],
			 	array( 'text',
			        'textarea',
			        'file',
			        'radio',
			        'checkbox',
			        'select',
			        'signature',
			        'matrix')) && $data['element_status_indicator'] == 1) {

				if ( isset( $data['indicator'] ) && $data['indicator'] === '0' ) {
					$cntStatusArr[0]++;
					$statusElementArr[$temp_form_id."_".$data["element_id"]]['field_link'] = "view.php?id=".$temp_form_id."&entry_id=".$entry_id."&la_page=".$data["element_page_number"]."&element_id_auto=".$data["id"];
					$statusElementArr[$temp_form_id."_".$data["element_id"]]['status'] = "0";
					$statusElementArr[$temp_form_id."_".$data["element_id"]]['label'] = $data["element_title"];
				} elseif ( isset( $data['indicator'] ) && $data['indicator'] === '1' ) {
					$cntStatusArr[1]++;
					$statusElementArr[$temp_form_id."_".$data["element_id"]]['field_link'] = "view.php?id=".$temp_form_id."&entry_id=".$entry_id."&la_page=".$data["element_page_number"]."&element_id_auto=".$data["id"];
					$statusElementArr[$temp_form_id."_".$data["element_id"]]['status'] = "1";
					$statusElementArr[$temp_form_id."_".$data["element_id"]]['label'] = $data["element_title"];
				} elseif ( isset( $data['indicator'] ) && $data['indicator'] === '2' ) {
					$cntStatusArr[2]++;
					$statusElementArr[$temp_form_id."_".$data["element_id"]]['field_link'] = "view.php?id=".$temp_form_id."&entry_id=".$entry_id."&la_page=".$data["element_page_number"]."&element_id_auto=".$data["id"];
					$statusElementArr[$temp_form_id."_".$data["element_id"]]['status'] = "2";
					$statusElementArr[$temp_form_id."_".$data["element_id"]]['label'] = $data["element_title"];
				} elseif ( isset( $data['indicator'] ) && $data['indicator'] === '3' ) {
					$cntStatusArr[3]++;
					$statusElementArr[$temp_form_id."_".$data["element_id"]]['field_link'] = "view.php?id=".$temp_form_id."&entry_id=".$entry_id."&la_page=".$data["element_page_number"]."&element_id_auto=".$data["id"];
					$statusElementArr[$temp_form_id."_".$data["element_id"]]['status'] = "3";
					$statusElementArr[$temp_form_id."_".$data["element_id"]]['label'] = $data["element_title"];
				} else {
					$cntStatusArr[0]++;
					$statusElementArr[$temp_form_id."_".$data["element_id"]]['field_link'] = "view.php?id=".$temp_form_id."&entry_id=".$entry_id."&la_page=".$data["element_page_number"]."&element_id_auto=".$data["id"];
					$statusElementArr[$temp_form_id."_".$data["element_id"]]['status'] = "0";
					$statusElementArr[$temp_form_id."_".$data["element_id"]]['label'] = $data["element_title"];
				}
			}
		}
	}	
	
	$return["count_status_array"] = $cntStatusArr;
	

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

	$return["score_array"] = $scoreArr;
	$return["score_percentage"] = $score_percentage;
	
	ksort($statusElementArr);
	$return["status_element_array"] = array_filter($statusElementArr, function($v){
		return isset($v['status']);
	});
	$return["max_score"] = $max_score;
	$return['id'] = $form_id;
	return $return;
}

function getStatusIndicatorValues($dbh, $report_id, $form_id, $display_type, $company_id) {
	$column = array();
	$column[$report_id] = array();
	$column[$report_id][$form_id] = array();
	$column[$report_id][] = "Grey";
	$column[$report_id][] = "Red";
	$column[$report_id][] = "Yellow";
	$column[$report_id][] = "Green";
	$totalIndicators = GetTotalIndicatorsByForm($form_id, $dbh);
	$totalIndicatorsFound = 0;
	
	for ($indicator_value = 0; $indicator_value < 4; $indicator_value++) {
		$query = 'SELECT COUNT(a.indicator) AS TOTAL, id FROM ' . LA_TABLE_PREFIX . 'element_status_indicator a ' .
					'INNER JOIN ( ' .
					'SELECT indicator, form_id, company_id, element_id, MAX(id) rev ' .
						'FROM ' . LA_TABLE_PREFIX . 'element_status_indicator ' .
						'WHERE form_id = ' . $form_id . ' AND company_id = ' . $company_id . ' ' .
						'GROUP BY form_id, company_id, element_id) b ' .
					'ON a.id = b.rev ' .
					'WHERE a.form_id = ' . $form_id . ' AND a.company_id = ' . $company_id . ' AND a.indicator = ' . $indicator_value;
		$query_result = la_do_query($query, null, $dbh);
		$row = la_do_fetch_result($query_result);
		if ($indicator_value == 0) {
			$column[$report_id][$form_id]['Grey']['Grey'] = $row['TOTAL'];
			$column[$report_id][$form_id]['Grey']['id'] = '0';
		}
		else if ($indicator_value == 1) {
			$column[$report_id][$form_id]['Red']['Red'] = $row['TOTAL'];
			$column[$report_id][$form_id]['Red']['id'] = '1';
		}
		else if ($indicator_value == 2) {
			$column[$report_id][$form_id]['Yellow']['Yellow'] = $row['TOTAL'];
			$column[$report_id][$form_id]['Yellow']['id'] = '2';
		}
		else if ($indicator_value == 3) {
			$column[$report_id][$form_id]['Green']['Green'] = $row['TOTAL'];
			$column[$report_id][$form_id]['Green']['id'] = '3';
		}
		$totalIndicatorsFound += $row['TOTAL'];
	} 
	if ($totalIndicatorsFound < $totalIndicators) {
		$indicatorsNotFound = ($totalIndicators - $totalIndicatorsFound);
		$column[$report_id][$form_id]['Grey']['Grey'] += $indicatorsNotFound;
	}
	return $column[$report_id][$form_id];
}


function GetTotalIndicatorsByForm($form_id, $dbh) {
	$totalIndicatorsOnForm = 0;
	$query = 'SELECT COUNT(element_id) as TOTAL FROM ' . LA_TABLE_PREFIX . 'form_elements WHERE form_id = ' . $form_id .
			 ' AND element_status_indicator = 1 AND element_matrix_parent_id = 0';
	$result = la_do_query($query, null, $dbh);		 
	$row = la_do_fetch_result($result);
	$totalIndicatorsOnForm = $row['TOTAL'];
	return $totalIndicatorsOnForm;
}

function calculateMedian($aValues) {
    $aToCareAbout = array();
    foreach ($aValues as $mValue) {
        if ($mValue >= 0) {
            $aToCareAbout[] = $mValue;
        }
    }
    $iCount = count($aToCareAbout);
    sort($aToCareAbout, SORT_NUMERIC);
    if ($iCount > 2) {
        if ($iCount % 2 == 0) {
            return ($aToCareAbout[floor($iCount / 2) - 1] + $aToCareAbout[floor($iCount / 2)]) / 2;
        } else {
            return $aToCareAbout[$iCount / 2];
        }
    } elseif (isset($aToCareAbout[0])) {
        return $aToCareAbout[0];
    } else {
        return 0;
    }
}

function getFieldNotes($dbh, $la_settings, $form_ids) {
	$formIdArr = array();
	$field_notes = array();
	$res = array();
	$cascade_flag = false;
	$noteIdArr = array();
	foreach ($form_ids as $top_form_id) {
		//get field notes from the top form
		$form_id = $top_form_id;
		$query_form_name = "SELECT `form_name` FROM`".LA_TABLE_PREFIX."forms` WHERE `form_id` = ?";
		$sth_form_name = la_do_query($query_form_name, array($form_id), $dbh);
		$res_form_name = la_do_fetch_result($sth_form_name);
		$form_name = $res_form_name["form_name"];

		$query_note = "SELECT * FROM `".LA_TABLE_PREFIX."form_element_note` WHERE `form_id` = ?";
		$sth_note = la_do_query($query_note, array($form_id), $dbh);
		while ($row_note = la_do_fetch_result($sth_note)) {
			if(!in_array($row_note["form_element_note_id"], $noteIdArr)) {
				array_push($noteIdArr, $row_note["form_element_note_id"]);
				$assigner = "";
				$assignees = "";
				$element_title = "";

				$query_element = "SELECT * FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_id` = ?";
				$sth_element = la_do_query($query_element, array($form_id, $row_note["element_id"]), $dbh);
				$res_element = la_do_fetch_result($sth_element);
				if($res_element["element_type"] == "matrix") {
					$element_title = $res_element["element_guidelines"];
				} else {
					$element_title = $res_element["element_title"];
				}

				//get assigner
				if($row_note["admin_id"] == 0) {//if assigned by entity
					$query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
					$sth_entity = la_do_query($query_entity, array($row_note["company_id"]), $dbh);
					$row_entity = la_do_fetch_result($sth_entity);
					$query_user = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = ?";
					$sth_user = la_do_query($query_user, array($row_note["user_id"]), $dbh);
					$row_user = la_do_fetch_result($sth_user);
					if(isset($row_user) && !empty($row_user) && isset($row_entity) && !empty($row_entity)){
						$assigner = "<img src='".$la_settings["base_url"].$row_user["avatar_url"]."' style='width:50px;border-radius:50%;'>"."<div>[User] ".$row_user["full_name"]."</br>[Entity] ".$row_entity["company_name"]."</div>";
 					} else {
						if(isset($row_entity) && !empty($row_entity)) {
							$assigner = "<div>[Entity] ".$row_entity["company_name"]."</div>";
						} else {
							$assigner = "<div>-</div>";
						}
					}
				} else {//if assigned by admin
					$query_admin = "SELECT * FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = ?";
					$sth_admin = la_do_query($query_admin, array($row_note["admin_id"]), $dbh);
					$row_admin = la_do_fetch_result($sth_admin);
					if(isset($row_admin) && !empty($row_admin)) {
						$assigner = "<img src='".$la_settings["base_url"].$row_admin["avatar_url"]."' style='width:50px;border-radius:50%;'>"."<div>[Admin] ".$row_admin["user_fullname"]."</div>";
					} else {
						$assigner = "<div>-</div>";
					}
				}
				//get assignees
				$assginee_admins = explode(",", explode(";", $row_note["assignees"])[0]);
				if(count($assginee_admins) > 0) {
					foreach ($assginee_admins as $admin_id) {
						$query_admin = "SELECT * FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = ?";
						$sth_admin = la_do_query($query_admin, array($admin_id), $dbh);
						$row_admin = la_do_fetch_result($sth_admin);
						if(isset($row_admin) && !empty($row_admin)) {
							$assignees .= "<img src='".$la_settings["base_url"].$row_admin["avatar_url"]."' style='width:50px;border-radius:50%;'>"."<div>[Admin] ".$row_admin["user_fullname"]."</br></div>";
						}
					}
				}
				
				$assginee_entities = explode(",", explode(";", $row_note["assignees"])[1]);
				if(count($assginee_entities) > 0) {
					foreach ($assginee_entities as $entity_id) {
						$query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
						$sth_entity = la_do_query($query_entity, array($entity_id), $dbh);
						$row_entity = la_do_fetch_result($sth_entity);

						if(isset($row_entity) && !empty($row_entity)) {
							$assignees .= "<div>[Entity] ".$row_entity["company_name"]."</br><div>";
						}
					}
				}

				$assginee_users = explode(",", explode(";", $row_note["assignees"])[2]);
				if(count($assginee_users) > 0) {
					foreach ($assginee_users as $user) {
						$query_user = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = ?";
						$sth_user = la_do_query($query_user, array(explode("-", $user)[1]), $dbh);
						$row_user = la_do_fetch_result($sth_user);
						if(isset($row_user) && !empty($row_user)){
							$assignees .= "<img src='".$la_settings["base_url"].$row_user["avatar_url"]."' style='width:50px;border-radius:50%;'>"."<div>[User] ".$row_user["full_name"]."</br></div>";
						}
					}
				}
				if($assignees == "") {
					$assignees = "-";
				}
				array_push($field_notes, array("form_id" => $form_id, "form_name" => $form_name, "element_id" => $row_note["element_id"], "element_title" => $element_title, "note_id" => $row_note["form_element_note_id"], "note" => $row_note["note"], "assigner" => $assigner, "assignees" => $assignees, "field_link" => "/view.php?id=".$top_form_id."&la_page=".$res_element["element_page_number"]."&element_id_auto=".$res_element["id"], "status" => $row_note["status"], "date_created" => $row_note["create_date"]));
			}
		}

		//get field notes from cascaded sub forms
		$query_cascade_forms = "SELECT `element_default_value`, `element_position`, `element_page_number` FROM ".LA_TABLE_PREFIX."form_elements WHERE `form_id` = ? AND `element_type` = ?";
		$sth_cascade_forms = la_do_query($query_cascade_forms, array($top_form_id, "casecade_form"), $dbh);
		while($row_cascade_form = la_do_fetch_result($sth_cascade_forms)) {			
			$form_id = $row_cascade_form['element_default_value'];
			$query_form_name = "SELECT `form_name` FROM`".LA_TABLE_PREFIX."forms` WHERE `form_id` = ?";
			$sth_form_name = la_do_query($query_form_name, array($form_id), $dbh);
			$res_form_name = la_do_fetch_result($sth_form_name);
			$form_name = $res_form_name["form_name"];

			$query_note = "SELECT * FROM `".LA_TABLE_PREFIX."form_element_note` WHERE `form_id` = ?";
			$sth_note = la_do_query($query_note, array($form_id), $dbh);
			while ($row_note = la_do_fetch_result($sth_note)) {
				if(!in_array($row_note["form_element_note_id"], $noteIdArr)) {
					array_push($noteIdArr, $row_note["form_element_note_id"]);
					$assigner = "";
					$assignees = "";
					$element_title = "";

					$query_element = "SELECT * FROM `".LA_TABLE_PREFIX."form_elements` WHERE `form_id` = ? AND `element_id` = ?";
					$sth_element = la_do_query($query_element, array($form_id, $row_note["element_id"]), $dbh);
					$res_element = la_do_fetch_result($sth_element);
					if($res_element["element_type"] == "matrix") {
						$element_title = $res_element["element_guidelines"];
					} else {
						$element_title = $res_element["element_title"];
					}
					//get assigner
					if($row_note["admin_id"] == 0) {//if assigned by entity
						$query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
						$sth_entity = la_do_query($query_entity, array($row_note["company_id"]), $dbh);
						$row_entity = la_do_fetch_result($sth_entity);
						$query_user = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = ?";
						$sth_user = la_do_query($query_user, array($row_note["user_id"]), $dbh);
						$row_user = la_do_fetch_result($sth_user);
						if(isset($row_user) && !empty($row_user) && isset($row_entity) && !empty($row_entity)){
							$assigner = "<img src='".$la_settings["base_url"].$row_user["avatar_url"]."' style='width:50px;border-radius:50%;'>"."<div>[User] ".$row_user["full_name"]."</br>[Entity] ".$row_entity["company_name"]."</div>";
 						} else {
							if(isset($row_entity) && !empty($row_entity)) {
								$assigner = "<div>[Entity] ".$row_entity["company_name"]."</div>";
							} else {
								$assigner = "<div>-</div>";
							}
						}
					} else {//if assigned by admin
						$query_admin = "SELECT * FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = ?";
						$sth_admin = la_do_query($query_admin, array($row_note["admin_id"]), $dbh);
						$row_admin = la_do_fetch_result($sth_admin);
						if(isset($row_admin) && !empty($row_admin)) {
							$assigner = "<img src='".$la_settings["base_url"].$row_admin["avatar_url"]."' style='width:50px;border-radius:50%;'>"."<div>[Admin] ".$row_admin["user_fullname"]."</div>";
						} else {
							$assigner = "<div>-</div>";
						}
					}
					//get assignees
					$assginee_admins = explode(",", explode(";", $row_note["assignees"])[0]);
					if(count($assginee_admins) > 0) {
						foreach ($assginee_admins as $admin_id) {
							$query_admin = "SELECT * FROM `".LA_TABLE_PREFIX."users` WHERE `user_id` = ?";
							$sth_admin = la_do_query($query_admin, array($admin_id), $dbh);
							$row_admin = la_do_fetch_result($sth_admin);
							if(isset($row_admin) && !empty($row_admin)) {
								$assignees .= "<img src='".$la_settings["base_url"].$row_admin["avatar_url"]."' style='width:50px;border-radius:50%;'>"."<div>[Admin] ".$row_admin["user_fullname"]."</br></div>";
							}
						}
					}
					
					$assginee_entities = explode(",", explode(";", $row_note["assignees"])[1]);
					if(count($assginee_entities) > 0) {
						foreach ($assginee_entities as $entity_id) {
							$query_entity = "SELECT * FROM `".LA_TABLE_PREFIX."ask_clients` WHERE `client_id` = ?";
							$sth_entity = la_do_query($query_entity, array($entity_id), $dbh);
							$row_entity = la_do_fetch_result($sth_entity);

							if(isset($row_entity) && !empty($row_entity)) {
								$assignees .= "<div>[Entity] ".$row_entity["company_name"]."</br></div>";
							}
						}
					}

					$assginee_users = explode(",", explode(";", $row_note["assignees"])[2]);
					if(count($assginee_users) > 0) {
						foreach ($assginee_users as $user) {
							$query_user = "SELECT * FROM `".LA_TABLE_PREFIX."ask_client_users` WHERE `client_user_id` = ?";
							$sth_user = la_do_query($query_user, array(explode("-", $user)[1]), $dbh);
							$row_user = la_do_fetch_result($sth_user);
							if(isset($row_user) && !empty($row_user)){
								$assignees .= "<img src='".$la_settings["base_url"].$row_user["avatar_url"]."' style='width:50px;border-radius:50%;'>"."<div>[User] ".$row_user["full_name"]."</br></div>";
							}
						}
					}

					if($assignees == "") {
						$assignees = "<div>-</div>";
					}
					$cascade_flag = true;
					array_push($field_notes, array("form_id" => $form_id, "form_name" => $form_name, "element_id" => $row_note["element_id"], "element_title" => $element_title, "note_id" => $row_note["form_element_note_id"], "note" => $row_note["note"], "assigner" => $assigner, "assignees" => $assignees, "field_link" => "/view.php?id=".$top_form_id."&la_page=".$row_cascade_form["element_page_number"]."&casecade_element_position=".$row_cascade_form["element_position"]."&casecade_form_page_number=".$res_element["element_page_number"]."&element_id_auto=".$res_element["id"], "status" => $row_note["status"], "date_created" => $row_note["create_date"]));
				}
			}
		}
	}
	$res["cascade_flag"] = $cascade_flag;
	$res["field_notes"] = $field_notes;
	return $res;
}