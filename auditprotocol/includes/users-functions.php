<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/

	//get an array containing id number of all filtered users id within ap_users table, based on $filter_data
	function la_get_filtered_users_ids($dbh,$filter_data,$exclude_admin=true){

		//set column properties for basic fields
		$column_name_lookup['user_fullname']	= 'Name';
		$column_name_lookup['user_email']		= 'Email';
		$column_name_lookup['priv_administer']	= 'Privileges';
		$column_name_lookup['status']			= 'Status';
		
		$column_type_lookup['user_fullname']	= 'text';
		$column_type_lookup['user_email']		= 'text';
		$column_type_lookup['priv_administer'] 	= 'admin';
		$column_type_lookup['status']			= 'status';
		
		
		$column_prefs = array('user_fullname','user_email','priv_administer','status');
		
		
		//determine column labels
		//the first 2 columns are always id and row_num
		$column_labels = array();

		$column_labels[] = 'la_id';
		$column_labels[] = 'la_row_num';
		
		foreach($column_prefs as $column_name){
			$column_labels[] = $column_name_lookup[$column_name];
		}

		//get the entries from ap_form_x table and store it into array
		$column_prefs_joined = '`'.implode("`,`",$column_prefs).'`';
		
		//check for filter data and build the filter query
		if(!empty($filter_data)){

			if($filter_type == 'all'){
				$condition_type = ' AND ';
			}else{
				$condition_type = ' OR ';
			}

			$where_clause_array = array();

			foreach ($filter_data as $value) {
				$element_name 	  = $value['element_name'];
				$filter_condition = $value['filter_condition'];
				$filter_keyword   = addslashes($value['filter_keyword']);

				$filter_element_type = $column_type_lookup[$element_name];

				$temp = explode('_', $element_name);
				$element_id = $temp[1];
				
				
				if($filter_condition == 'is'){
						$where_operand = '=';
						$where_keyword = "'{$filter_keyword}'";
				}else if($filter_condition == 'is_not'){
						$where_operand = '<>';
						$where_keyword = "'{$filter_keyword}'";
				}else if($filter_condition == 'begins_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'{$filter_keyword}%'";
				}else if($filter_condition == 'ends_with'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}'";
				}else if($filter_condition == 'contains'){
						$where_operand = 'LIKE';
						$where_keyword = "'%{$filter_keyword}%'";
				}else if($filter_condition == 'not_contain'){
						$where_operand = 'NOT LIKE';
						$where_keyword = "'%{$filter_keyword}%'";
				}else if($filter_condition == 'less_than' || $filter_condition == 'is_before'){
						$where_operand = '<';
						$where_keyword = "'{$filter_keyword}'";
				}else if($filter_condition == 'greater_than' || $filter_condition == 'is_after'){
						$where_operand = '>';
						$where_keyword = "'{$filter_keyword}'";
				}else if($filter_condition == 'is_admin'){
						$where_operand = '=';
						$where_keyword = "'1'";
				}else if($filter_condition == 'is_not_admin'){
						$where_operand = '=';
						$where_keyword = "'0'";
				}else if($filter_condition == 'is_active'){
						$where_operand = '=';
						$where_keyword = "'1'";
				}else if($filter_condition == 'is_suspended'){
						$where_operand = '=';
						$where_keyword = "'2'";
				}
		 			
				$where_clause_array[] = "{$element_name} {$where_operand} {$where_keyword}"; 
				
			}
			
			$where_clause = implode($condition_type, $where_clause_array);
			
			if(empty($where_clause)){
				$where_clause = "WHERE `status` > 0";
			}else{
				$where_clause = "WHERE ({$where_clause}) AND `status` > 0";
			}
			
						
		}else{
			$where_clause = "WHERE `status` > 0";
		}


		$query = "select 
						`user_id`,
						`user_id` as `row_num`,
						`user_fullname`,
						`user_email`,
						if(`priv_administer`=1,'Administrator','') `priv_administer`,
						if(`status`=1,'Active','Suspended') `status`
				    from 
				    	".LA_TABLE_PREFIX."users A 
				    	{$where_clause} ";
		
		$params = array();
		$sth = la_do_query($query,$params,$dbh);
		
		$filtered_user_id_array = array();
		while($row = la_do_fetch_result($sth)){
			if($exclude_admin){
				if($row['user_id'] != 1){ 
					$filtered_user_id_array[] = $row['user_id'];
				}
			}else{
				$filtered_user_id_array[] = $row['user_id'];
			}
		}

		return $filtered_user_id_array;

	}

	//get an array containing user permission to one particular form
	function la_get_user_permissions($dbh,$form_id,$user_id){
		if($_SESSION['is_examiner'] == 0) {
			$query = "SELECT `edit_form`,`edit_entries`,`view_entries` FROM `".LA_TABLE_PREFIX."permissions` WHERE `user_id` = ? and `form_id` = ?";
			$params = array($user_id,$form_id);
			$sth = la_do_query($query,$params,$dbh);
			$row = la_do_fetch_result($sth);

			$perms['edit_form'] 	= false;
			$perms['edit_entries'] 	= false;
			$perms['view_entries'] 	= false;

			if(!empty($row['edit_form'])){
				$perms['edit_form'] = true;
			}
			if(!empty($row['edit_entries'])){
				$perms['edit_entries'] = true;
			}
			if(!empty($row['view_entries'])){
				$perms['view_entries'] = true;
			}
		} else {
			$entity_array = array("0");
			$query_entity = "SELECT `entity_id` FROM `".LA_TABLE_PREFIX."entity_examiner_relation` WHERE `user_id` = ?";
			$sth_entity = la_do_query($query_entity, array($user_id), $dbh);
			while($row_entity = la_do_fetch_result($sth_entity)) {
				array_push($entity_array, $row_entity['entity_id']);
			}
			$string_entity_ids = implode(',', $entity_array);
			$query_form = "SELECT COUNT(*) total_row FROM `".LA_TABLE_PREFIX."entity_form_relation` WHERE `entity_id` IN ($string_entity_ids) AND `form_id` = ?";
			$sth_form = la_do_query($query_form, array($form_id), $dbh);
			$row_form = la_do_fetch_result($sth_form);
			if (!empty($row_form['total_row'])) {
				$perms['edit_form'] = false;
				$perms['edit_entries'] = true;
				$perms['view_entries'] = true;
			} else {
				$perms['edit_form'] = false;
				$perms['edit_entries'] = false;
				$perms['view_entries'] = false;
			}
		}
		return $perms;
	}

	//get an array containing user permission to all forms
	function la_get_user_permissions_all($dbh,$user_id){
		if($_SESSION['is_examiner'] == 0) {
			$query = "SELECT `edit_form`,`edit_entries`,`view_entries`,`form_id` FROM `".LA_TABLE_PREFIX."permissions` WHERE `user_id` = ?";
			$params = array($user_id);
			$sth = la_do_query($query,$params,$dbh);
			while($row = la_do_fetch_result($sth)){
				$form_id = $row['form_id'];

				$edit_form    = false;
				$edit_entries = false;
				$view_entries = false;

				if(!empty($row['edit_form'])){
					$edit_form = true;
				}
				if(!empty($row['edit_entries'])){
					$edit_entries = true;
				}
				if(!empty($row['view_entries'])){
					$view_entries = true;
				}

				$perms[$form_id]['edit_form']    = $edit_form;
				$perms[$form_id]['edit_entries'] = $edit_entries;
				$perms[$form_id]['view_entries'] = $view_entries;
			}
		} else {
			$entity_array = array("0");
			$query_entity = "SELECT `entity_id` FROM `".LA_TABLE_PREFIX."entity_examiner_relation` WHERE `user_id` = ?";
			$sth_entity = la_do_query($query_entity, array($user_id), $dbh);
			while($row_entity = la_do_fetch_result($sth_entity)) {
				array_push($entity_array, $row_entity['entity_id']);
			}
			$string_entity_ids = implode(',', $entity_array);
			$query_form = "SELECT DISTINCT `form_id` FROM `".LA_TABLE_PREFIX."entity_form_relation` WHERE `entity_id` IN ($string_entity_ids)";
			$sth_form = la_do_query($query_form, array(), $dbh);
			while($row_form = la_do_fetch_result($sth_form)){
				$form_id = $row_form['form_id'];
				$perms[$form_id]['edit_form']    = false;
				$perms[$form_id]['edit_entries'] = true;
				$perms[$form_id]['view_entries'] = true;
			}
		}
		return $perms;
	}

	function get_user_details($dbh, $user_id) {
		$query = "select * from `".LA_TABLE_PREFIX."users` where `user_id` = ?";
		$sth = la_do_query($query,array($user_id),$dbh);
		$row = la_do_fetch_result($sth);
		if( $row ) {
			return $row;
		} else {
			return false;
		}
	}

	function la_get_all_user_ids($dbh){
		$ids = [];
		$query = "select user_id from `".LA_TABLE_PREFIX."users` where `user_id` <> 1";
		$sth = la_do_query($query,[],$dbh);
		while($row = la_do_fetch_result($sth)){
			$ids[] = $row['user_id'];
		}
		return $ids;
	}
	
?>
