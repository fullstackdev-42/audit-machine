<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
	function getFileType(&$value,$key) {
		$value = strtolower(trim($value));
	}

	//validation for required field
	function la_validate_required($value){
		global $la_lang;

		$value = $value[0]; 
		if(empty($value) && (($value != 0) || ($value != '0'))){ //0  and '0' should not considered as empty
			return $la_lang['val_required'];
		}else{
			return true;
		}
	}	
	
	//validation for unique checking on db table
	function la_validate_unique($value){
		global $la_lang;

		$input_value  = $value[0]; 
	
		$exploded = explode('#',$value[1]);
		$form_id  = (int) $exploded[0];
		$element_name = $exploded[1];
		$company_id = $_SESSION["la_client_entity_id"];
		$dbh = $value[2]['dbh'];
		
		$query = "SELECT COUNT(`field_name`) total FROM `".LA_TABLE_PREFIX."form_{$form_id}` WHERE `field_name`=? AND `company_id` != ? AND `data_value` = ?";
		
		$sth = la_do_query($query, array($element_name, $company_id, $input_value), $dbh);
		$row = la_do_fetch_result($sth);
		
		if(!empty($row['total'])){ 
			return $la_lang['val_unique'];
		}else{
			return true;
		}
	}	
	
	//validation for coupon code checking on db table
	function la_validate_coupon($value){
		global $la_lang;

		$input_coupon_code  = strtolower(trim($value[0])); 
		$form_id  			= (int) $value[1];

		//we don't need to validate empty coupon code
		if(empty($input_coupon_code)){
			return true;
		}
		
		$dbh = $value[2]['dbh'];
		
		//get the coupon code setting first
		$query = "select payment_discount_code,payment_discount_element_id,payment_discount_max_usage,payment_discount_expiry_date from ".LA_TABLE_PREFIX."forms where form_id = ?";
		$params = array($form_id);
			
		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);

		$discount_code = strtolower($row['payment_discount_code']);
		$discount_element_id = (int) $row['payment_discount_element_id'];
		$discount_max_usage = (int) $row['payment_discount_max_usage'];
		$discount_expiry_date = $row['payment_discount_expiry_date'];


		//validate the coupon code
		//make sure entered coupon code is valid
		$discount_code_array = explode(',', $discount_code);
		array_walk($discount_code_array, 'la_trim_value');

		if(!in_array($input_coupon_code, $discount_code_array)){
			return $la_lang['coupon_not_exist'];
		}
		
		//make sure entered coupon code is within the max usage
		if(!empty($discount_max_usage)){
			$query = "select count(*) coupon_usage from ".LA_TABLE_PREFIX."form_{$form_id} where element_{$discount_element_id} = ? and `status` = 1";

			$params = array($input_coupon_code);
			
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);
			$current_coupon_usage = (int) $row['coupon_usage'];
			
			if($current_coupon_usage >= $discount_max_usage){
				return $la_lang['coupon_max_usage'];
			}
		}

		//make sure entered coupon code is not expired yet
		if(!empty($discount_expiry_date) && $discount_expiry_date != '0000-00-00'){
			$current_date = strtotime(date("Y-m-d"));
			$expiry_date  = strtotime($discount_expiry_date);

			if($current_date >= $expiry_date){
				return $la_lang['coupon_expired'];
			}
		}		
		
		return true;
	}
		
	//validation for integer
	function la_validate_integer($value){
		global $la_lang;

		$error_message = $la_lang['val_integer'];
		
		$value = $value[0];
		if(is_int($value)){
			return true; //it's integer
		}else if(is_float($value)){
			return $error_message; //it's float
		}else if(is_numeric($value)){
			$result = strpos($value,'.');
			if($result !== false){
				return $error_message; //it's float
			}else{
				return true; //it's integer
			}
		}else{
			return $error_message; //it's not even a number!
		}
	}
	
	//validation for float aka double
	function la_validate_float($value){
		global $la_lang;

		$error_message = $la_lang['val_float'];
		
		$value = $value[0];
		if(is_int($value)){
			return $error_message; //it's integer
		}else if(is_float($value)){
			return true; //it's float
		}else if(is_numeric($value)){
			$result = strpos($value,'.');
			if($result !== false){
				return true; //it's float
			}else{
				return $error_message; //it's integer
			}
		}else{
			return $error_message; //it's not even a number!
		}
	}
	
	//validation for numeric
	function la_validate_numeric($value){
		global $la_lang;

		$error_message = $la_lang['val_numeric'];
				
		$value = $value[0];
		if(is_numeric($value)){
			return true;
		}else{
			return $error_message;
		}
		
	}
	
	//validation for phone (###) ### ####
	function la_validate_phone($value){
		global $la_lang;

		$error_message = $la_lang['val_phone'];
		
		if(!empty($value[0])){
			$regex  = '/^[1-9][0-9]{9}$/';
			$result = preg_match($regex, $value[0]);
			
			if(empty($result)){
				return $error_message;
			}else{
				return true;
			}
		}else{
			return true;
		}
		
	}
	
	//validation for simple phone, international phone
	function la_validate_simple_phone($value){
		global $la_lang;

		$error_message = $la_lang['val_inter_phone'];
		if(!empty($value[0])){
			
			$regex  = '/^[\(]?[\+]?[1-9]{0,1}[0-9]{0,2}[\)]?[\-\s]?[\(]?[0-9]{3,4}[\)]?[\-\s]?[0-9]{3}[\-\s]?[0-9]{4}$/';
			
			if(!(preg_match($regex, $value[0]))){
				return $error_message;
			}else{
				return true;
			}
		}else{
			return true;
		}
	}
	
	//validation for minimum length
	function la_validate_min_length($value){
		global $la_lang;

		$target_value = $value[0];
		$exploded 	  = explode('#',$value[1]);
		
		$range_limit_by = $exploded[0];
		$range_min		= (int) $exploded[1];
		
		if($range_limit_by == 'c' || $range_limit_by == 'd'){
			$target_length = strlen($target_value);
		}elseif ($range_limit_by == 'w'){
			$target_length = count(preg_split("/[\s\.]+/", $target_value, NULL, PREG_SPLIT_NO_EMPTY));
		}
		
		if($target_length < $range_min){
			return 'error_no_display';
		}else{
			return true;
		}
	}
	
	//validation for number minimum value
	function la_validate_min_value($value){
		global $la_lang;

		$target_value = (float) $value[0];
		$range_min	  = (float) $value[1];
		
		if($target_value < $range_min){
			return 'error_no_display';
		}else{
			return true;
		}
	}
	
	//validation for maximum length
	function la_validate_max_length($value){
		global $la_lang;

		$target_value = $value[0];
		$exploded 	  = explode('#',$value[1]);
		
		$range_limit_by = $exploded[0];
		$range_max		= (int) $exploded[1];
		
		if($range_limit_by == 'c' || $range_limit_by == 'd'){
			$target_length = strlen($target_value);
		}elseif ($range_limit_by == 'w'){
			$target_length = count(preg_split("/[\s\.]+/", $target_value, NULL, PREG_SPLIT_NO_EMPTY));
		}
		
		if($target_length > $range_max){
			return 'error_no_display';
		}else{
			return true;
		}
	}
	
	//validation for number minimum value
	function la_validate_max_value($value){
		global $la_lang;

		$target_value = (float) $value[0];
		$range_max	  = (float) $value[1];
		
		if($target_value > $range_max){
			return 'error_no_display';
		}else{
			return true;
		}
	}
	
	//validation for range length
	function la_validate_range_length($value){
		global $la_lang;

		$target_value = $value[0];
		$exploded 	  = explode('#',$value[1]);
		
		$range_limit_by = $exploded[0];
		$range_min		= (int) $exploded[1];
		$range_max		= (int) $exploded[2];
		
		if($range_limit_by == 'c' || $range_limit_by == 'd'){
			$target_length = strlen($target_value);
		}elseif ($range_limit_by == 'w'){
			$target_length = count(preg_split("/[\s\.]+/", $target_value, NULL, PREG_SPLIT_NO_EMPTY));
		}
		
		if(!($range_min <= $target_length && $target_length <= $range_max)){
			return 'error_no_display';
		}else{
			return true;
		}
	}
	
	//validation for number range value
	function la_validate_range_value($value){
		global $la_lang;
		
		$target_value = (float) $value[0];
		$exploded 	  = explode('#',$value[1]);
		
		$range_min		= (float) $exploded[0];
		$range_max		= (float) $exploded[1];
		
		if(!($range_min <= $target_value && $target_value <= $range_max)){
			return 'error_no_display';
		}else{
			return true;
		}
	}
	
	//validation to check email address format
	function la_validate_email($value) {
		global $la_lang;

		$error_message = $la_lang['val_email'];
		
		$value[0] = trim($value[0]);

		if(!empty($value[0])){
			$regex  = '/^[A-z0-9][\w.\'-]*@[A-z0-9][\w\-\.]*\.[A-z0-9]{2,}$/';
			$result = preg_match($regex, $value[0]);
			
			if(empty($result)){
				return sprintf($error_message,'%s',$value[0]);
			}else{
				return true;
			}
		}else{
			return true;
		}
	}
	
	//validation to check URL format
	function la_validate_website($value) {
		global $la_lang;

		$error_message = $la_lang['val_website'];
		$value[0] = trim($value[0],'/').'/';
		
		if(!empty($value[0]) && $value[0] != '/'){
			$regex  = '/^https?:\/\/([a-z0-9]([-a-z0-9]*[a-z0-9])?\.)+([A-z0-9]{2,})(\/)(.*)$/i';
			$result = preg_match($regex, $value[0]);
			
			if(empty($result)){
				return sprintf($error_message,'%s',$value[0]);
			}else{
				return true;
			}
		}else{
			return true;
		}
	}
	
	//validation to allow only a-z 0-9 and underscores 
	function la_validate_username($value){
		global $la_lang;

		$error_message = $la_lang['val_username'];
		
		if(!preg_match("/^[a-z0-9][\w]*$/i",$value[0])){
			return sprintf($error_message,'%s',$value[0]);
		}else{
			return true;
		}
	}
	
	
	
	//validation to check two variable equality. usefull for checking password 
	function la_validate_equal($value){
		global $la_lang;

		$error_message = $la_lang['val_equal'];
		
		if($value[0] != $value[2][$value[1]]){
			return $error_message;
		}else{
			return true;
		}
	}
	
	//validate date format
	//currently only support this format: mm/dd/yyyy or mm-dd-yyyy, yyyy/mm/dd or yyyy-mm-dd
	function la_validate_date($value) {
		global $la_lang;

		$error_message = $la_lang['val_date'];
		
		if(!empty($value[0])){
			if($value[1] == 'yyyy/mm/dd'){
				$regex = "/^([1-9][0-9])\d\d[-\/](0?[1-9]|1[012])[-\/](0?[1-9]|[12][0-9]|3[01])$/";
			}elseif($value[1] == 'mm/dd/yyyy'){
				$regex = "/^(0[1-9]|1[012])[-\/](0[1-9]|[12][0-9]|3[01])[-\/](19|20)\d\d$/";
			}
			
			$result = preg_match($regex, $value[0]);
		}
		
		
		if(empty($result)){
			return sprintf($error_message,'%s',$value[1]);
		}else{
			return true;
		}
	}
	
	//validate date range
	function la_validate_date_range($value){
		global $la_lang;

		$error_message = $la_lang['val_date_range'];
		
		$target_value = strtotime($value[0]);
		$exploded 	  = explode('#',$value[1]);
		
		if($exploded[0] == '0000-00-00'){
			$range_min = '';
		}else{
			$range_min		= strtotime($exploded[0]);
			$range_min_formatted = date('M j, Y',$range_min);
		}

		if($exploded[1] == '0000-00-00'){
			$range_max = '';
		}else{
			$range_max		= strtotime($exploded[1]);
			$range_max_formatted = date('M j, Y',$range_max);
		}
		
		
		if(!empty($range_min) && !empty($range_max)){
			if(!($range_min <= $target_value && $target_value <= $range_max)){
				$error_message = $la_lang['val_date_range'];
				return sprintf($error_message,$range_min_formatted,$range_max_formatted);
			}
		}else if(!empty($range_min)){
			if($target_value < $range_min){
				$error_message = $la_lang['val_date_min'];
				return sprintf($error_message,$range_min_formatted);
			}
		}else if (!empty($range_max)){
			if($target_value > $range_max){
				$error_message = $la_lang['val_date_max'];
				return sprintf($error_message,$range_max_formatted);
			}
		}
		
		return true;
	}
	
	function la_validate_disabled_dates($value){
		global $la_lang;

		$error_message = $la_lang['val_date_na'];
		
		$target_value   = $value[0];
		$disabled_dates = $value[1];

		$target_value = date('Y-n-j',strtotime(trim($target_value)));

		if(in_array($target_value,$disabled_dates)){
			return $error_message;
		}else{
			return true;
		}
	}
	
	//check if a date is a weekend date or not
	function la_validate_date_weekend($value){
		global $la_lang;

		$error_message = $la_lang['val_date_na'];
		
		$target_value   = $value[0];
		
		if(date('N', strtotime($target_value)) >= 6){
			return $error_message;
		}else{
			return true;
		}
	}
	
	//validation to check valid time format 
	function la_validate_time($value){
		global $la_lang;

		$error_message = $la_lang['val_time'];
		
		$timestamp = strtotime($value[0]);
		
		if($timestamp == -1 || $timestamp === false){
			return $error_message;
		}else{
			return true;
		}
	}
	
	
	//validation for required file
	function la_validate_required_file($value){
		global $la_lang;

		$error_message = $la_lang['val_required_file'];
		$element_file = $value[0];
		
		if($_FILES[$element_file]['size'] > 0){
			return true;
		}else{
			return $error_message;
		}
	}
	
	//validation for file upload filetype
	function la_validate_filetype($value){
		global $la_lang;

		$file_rules = $value[2];
		
		$error_message = $la_lang['val_filetype'];
		$value = $value[0];
		
		$ext = pathinfo(strtolower($_FILES[$value]['name']), PATHINFO_EXTENSION);
		
		if(!empty($file_rules['file_type_list'])){
			
			$file_type_array = explode(',',$file_rules['file_type_list']);
			array_walk($file_type_array, 'getFileType');
			
			if($file_rules['file_block_or_allow'] == 'b'){
				if(in_array($ext,$file_type_array)){
					return $error_message;
				}	
			}else if($file_rules['file_block_or_allow'] == 'a'){
				if(!in_array($ext,$file_type_array)){
					return $error_message;
				}
			}
		}
		
		
		return true;
	}
	
	/*********************************************************
	* This is main validation function
	* This function will call sub function, called validate_xx
	* Each sub function is specific for one rule
	*
	* Syntax: $rules[field_name][validation_type] = value
	* validation_type: required,integer,float,min,max,range,email,username,equal,date
	* Example rules:
	*
	* $rules['author_id']['required'] = true; //author_id is required
	* $rules['author_id']['integer']  = true; //author_id must be an integer
	* $rules['author_id']['range']    = '2-10'; //author_id length must be between 2 - 10 characters
	*
	**********************************************************/
	function la_validate_rules($input,$rules){
		global $la_lang;

		//traverse for each input, check for rules to be applied
		foreach ($input as $key=>$value){
			$current_rules = @$rules[$key];
			$error_message = array();
			
			if(!empty($current_rules)){
				//an input can be validated by many rules, check that here
				foreach ($current_rules as $key2=>$value2){
					$argument_array = array($value,$value2,$input);
					$result = call_user_func('la_validate_'.$key2,$argument_array);
					
					if($result !== true){ //if we got error message, break the loop
						$error_message = $result;
						break;
					}
				}
			}
			if(count($error_message) > 0){
				$total_error_message[$key] = $error_message;
			}
		}
		
		if(@is_array($total_error_message)){
			return $total_error_message;
		}else{
			return true;
		}
	}
	
	//similar as function above, but this is specific for validating form inputs, with only one error message per input
	function la_validate_element($input,$rules){
		global $la_lang;
		$error_message = '';
		//traverse for each input, check for rules to be applied
		foreach ($input as $key=>$value){
			$current_rules = @$rules[$key];
			if(!empty($current_rules)){
				//an input can be validated by many rules, check that here
				foreach ($current_rules as $key2=>$value2){
					$argument_array = array($value,$value2,$input);
					$result = call_user_func('la_validate_'.$key2,$argument_array);
					
					if($result !== true){ //if we got error message, break the loop
						$error_message = $result;
						break;
					}
				}
			}
			if($error_message !== ''){
				$last_error_message = $error_message;
				break;
			}
		}
		
		if(!empty($last_error_message)){
			return $last_error_message;
		}else{
			return true;
		}
	}
?>