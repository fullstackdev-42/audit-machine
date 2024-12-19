<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://www.continuumgrc.com/

 More info at: http://www.continuumgrc.com/
 ********************************************************************************/
	require('includes/init.php');

	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/check-session.php');
	require('includes/filter-functions.php');
	require('includes/language.php');
	require('includes/view-functions.php');
	require('includes/users-functions.php');

	$dbh = la_connect_db();
	$la_settings = la_get_settings($dbh);

	$form_id = (int) la_sanitize($_REQUEST['id']);
	$unlock_hash = la_sanitize($_REQUEST['unlock']);

	$is_new_form = false;
    $upload_max_size = get_maximum_file_upload_size() / 1024 / 1024;

	//check the form_id
	//if blank or zero, create a new form first, otherwise load the form

	/***************************************/
	/*			fetch all company		   */
	/***************************************/
	$query_com = "select `client_id`, `company_name` from ".LA_TABLE_PREFIX."ask_clients ORDER BY `company_name`";
	$sth_com = la_do_query($query_com,array(),$dbh);
	$select_com = '<option value=""></option>';
	$select_ent = '<option value="0">ALL</option>';
	while($row = la_do_fetch_result($sth_com)){
		$select_com .= '<option value="'.$row['client_id'].'">'.$row['company_name'].'</option>';
		$select_ent .= '<option value="'.$row['client_id'].'">'.$row['company_name'].'</option>';
	}

	if(empty($form_id)){
		$is_new_form = true;
		//insert into ap_forms table and set the status to draft
		//set the status within 'form_active' field
		//form_active: 0 - Inactive / Disabled temporarily
		//form_active: 1 - Active
		//form_active: 2 - Draft
		//form_active: 9 - Deleted

		//check user privileges, is this user has privilege to create new form?
		if(empty($_SESSION['la_user_privileges']['priv_new_forms'])){
			$_SESSION['LA_DENIED'] = "You don't have permission to create new forms.";

			$ssl_suffix = la_get_ssl_suffix();
			header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].la_get_dirname($_SERVER['PHP_SELF'])."/restricted.php");
			exit;
		}


		//generate random form_id number, based on existing value
		$query = "select max(form_id) max_form_id from ".LA_TABLE_PREFIX."forms";
		$params = array();

		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);

		if(empty($row['max_form_id'])){
			$last_form_id = 10000;
		}else{
			$last_form_id = $row['max_form_id'];
		}

		$form_id = $last_form_id + rand(100,1000);

		//insert into ap_permissions table, so that this user can add fields
		$query = "insert into ".LA_TABLE_PREFIX."permissions(form_id,user_id,edit_form,edit_entries,view_entries) values(?,?,1,1,1)";
		$params = array($form_id,$_SESSION['la_user_id']);
		la_do_query($query,$params,$dbh);


		$query = "INSERT INTO `".LA_TABLE_PREFIX."forms` (
							form_id,
							form_name,
							form_description,
							form_redirect,
							form_redirect_enable,
							form_active,
							form_success_message,
							form_password,
							form_frame_height,
							form_unique_ip,
							form_captcha,
							form_captcha_type,
							form_review,
							form_label_alignment,
							form_resume_enable,
							form_limit_enable,
							form_limit,
							form_language,
							form_schedule_enable,
							form_schedule_start_date,
							form_schedule_end_date,
							form_schedule_start_hour,
							form_schedule_end_hour,
							form_lastpage_title,
							form_submit_primary_text,
							form_submit_secondary_text,
							form_submit_primary_img,
							form_submit_secondary_img,
							form_submit_use_image,
							form_page_total,
							form_pagination_type,
							form_review_primary_text,
							form_review_secondary_text,
							form_review_primary_img,
							form_review_secondary_img,
							form_review_use_image,
							form_review_title,
							form_review_description,
							form_upload_template,
							form_enable_template,
							form_custom_script_enable,
							form_custom_script_url,
							form_enable_auto_mapping,
							form_enable_template_wysiwyg
							)
					VALUES (?,
							'Untitled Form',
							'This is your form description. Click here to edit.',
							'',
							0,
							2,
							'Success! Your submission has been saved! Thank you for using the Continuum GRC ITAM SaaS application.',
							'',
							0,
							0,
							0,
							'r',
							0,
							'top_label',
							0,
							0,
							0,
							'english',
							0,
							'',
							'',
							'',
							'',
							'Untitled Page',
							'Submit',
							'Previous',
							'',
							'',
							0,
							1,
							'steps',
							'Submit',
							'Previous',
							'',
							'',
							0,
							'Review Your Entry',
							'Please review your entry below. Click Submit button to finish.',
							'',
							0,
							0,
							'',
							0,
							0
						   );";
		la_do_query($query,array($form_id),$dbh);
	}else{
		//check permission, is the user allowed to access this page?
		if(empty($_SESSION['la_user_privileges']['priv_administer'])){
			$user_perms = la_get_user_permissions($dbh,$form_id,$_SESSION['la_user_id']);

			//this page need edit_form permission
			if(empty($user_perms['edit_form'])){
				$_SESSION['LA_DENIED'] = "You don't have permission to edit this form.";

				$ssl_suffix = la_get_ssl_suffix();
				header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].la_get_dirname($_SERVER['PHP_SELF'])."/restricted.php");
				exit;
			}
		}

		$is_form_locked = false;

		//get lock status for this form
		$query = "select lock_date from ".LA_TABLE_PREFIX."form_locks where form_id = ? and user_id <> ?";
		$params = array($form_id,$_SESSION['la_user_id']);

		$sth = la_do_query($query,$params,$dbh);
		$row = la_do_fetch_result($sth);

		if(!empty($row['lock_date'])){
			$lock_date = strtotime($row['lock_date']);
			$current_date = date(time());

			$seconds_diff = $current_date - $lock_date;
			$lock_expiry_time = 60 * 60; //1 hour expiry

			//if there is a lock and the lock hasn't expired yet
			if($seconds_diff < $lock_expiry_time){
				$is_form_locked = true;
			}
		}

		//if the form is locked and no unlock key, redirect to warning page
		if($is_form_locked === true && empty($unlock_hash)){
			$ssl_suffix = la_get_ssl_suffix();

			header("Location: http{$ssl_suffix}://".$_SERVER['HTTP_HOST'].la_get_dirname($_SERVER['PHP_SELF'])."/form_locked.php?id=".$form_id);
			exit;
		}

		//if this is an existing form, delete the previous unsaved form fields
		$query = "DELETE FROM `".LA_TABLE_PREFIX."form_elements` where form_id = ? AND element_status='2'";
		$params = array($form_id);
		la_do_query($query,$params,$dbh);

		//the ap_element_options table has "live" column, which has 3 possible values:
		// 0 - the option is being deleted
		// 1 - the option is active
		// 2 - the option is currently being drafted, not being saved yet and will be deleted by edit_form.php if the form is being edited the next time
		$query = "DELETE FROM `".LA_TABLE_PREFIX."element_options` where form_id = ? AND live='2'";
		$params = array($form_id);
		la_do_query($query,$params,$dbh);

		$query = "select (select `user_fullname` from `".LA_TABLE_PREFIX."users` where `user_id` = `".LA_TABLE_PREFIX."form_locks`.`user_id`) `user_name`, `lock_date`, (select `form_name` from `".LA_TABLE_PREFIX."forms` where `form_id` = `".LA_TABLE_PREFIX."form_locks`.`form_id`) `form_name` from `".LA_TABLE_PREFIX."form_locks` WHERE `form_id` = ? ORDER BY `lock_date` DESC LIMIT 1";
		$sth = la_do_query($query,array($form_id),$dbh);
		$row = la_do_fetch_result($sth);

		$query = "select `user_fullname` from `".LA_TABLE_PREFIX."users` where `user_id` = ?";
		$sth1 = la_do_query($query,array($_SESSION['la_user_id']),$dbh);
		$row1 = la_do_fetch_result($sth1);


		//lock this form, to prevent other user editing the same form at the same time
		$query = "delete from ".LA_TABLE_PREFIX."form_locks where form_id=?";
		$params = array($form_id);
		la_do_query($query,$params,$dbh);

		$new_lock_date = date("Y-m-d H:i:s");
		$query = "insert into ".LA_TABLE_PREFIX."form_locks(form_id,user_id,lock_date) values(?,?,?)";
		$params = array($form_id,$_SESSION['la_user_id'],$new_lock_date);
		la_do_query($query,$params,$dbh);

		if (isset($_REQUEST['unlock'])){
			// add user activity to log: activity - 7 (UNLOCK FORM)
			$actionTxt = "unlocked Form {$row['form_name']} (earlier locked by {$row['user_name']})";
			addUserActivity($dbh, $_SESSION['la_user_id'], $form_id, 7, $actionTxt, time(), "");
		}

	}

	//get the HTML markup of the form
	$markup = la_display_raw_form($dbh,$form_id);

	//get the properties for each form field
	//get form data
	$query 	= "select
					form_name,
					form_active,
					form_description,
					form_redirect,
					form_redirect_enable,
					form_success_message,
					form_password,
					form_unique_ip,
					form_captcha,
					form_captcha_type,
					form_review,
					form_resume_enable,
					form_limit_enable,
					form_limit,
					form_language,
					form_frame_height,
					form_label_alignment,
					form_lastpage_title,
					form_schedule_enable,
					form_schedule_start_date,
					form_schedule_end_date,
					form_schedule_start_hour,
					form_schedule_end_hour,
					form_submit_primary_text,
					form_submit_secondary_text,
					form_submit_primary_img,
					form_submit_secondary_img,
					form_submit_use_image,
					form_last_page_break_bg_color,
					form_page_total,
					form_pagination_type,
					form_review_primary_text,
					form_review_secondary_text,
					form_review_primary_img,
					form_review_secondary_img,
					form_review_use_image,
					form_review_title,
					form_review_description,
					form_upload_template,
					form_enable_template,
					form_custom_script_enable,
					form_custom_script_url,
                    form_enable_auto_mapping,
					form_for_selected_company,
					folder_id,
          			form_enable_template_wysiwyg,
          			form_template_wysiwyg_id,
                form_framework_type,
                form_framework_or_group_id
					
			    from ".LA_TABLE_PREFIX."forms
			    where
			    	 form_id = ?";
	$params = array($form_id);

	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);

	$formInfo = new stdClass();
	$formInfo->isFieldDelete = "0";

	$form = new stdClass();
	if(!empty($row)){
		$form->id 				= $form_id;
		$form->name 			= $row['form_name'];
		$form->active 			= (int) $row['form_active'];
		$form->description 		= $row['form_description'];
		$form->redirect 		= $row['form_redirect'];
		$form->redirect_enable 	= (int) $row['form_redirect_enable'];
		$form->success_message  = $row['form_success_message'];
		$form->password 		= $row['form_password'];
		$form->frame_height 	= $row['form_frame_height'];
		$form->unique_ip 		= (int) $row['form_unique_ip'];
		$form->captcha 			= (int) $row['form_captcha'];
		$form->captcha_type 	= $row['form_captcha_type'];
		$form->review 			= (int) $row['form_review'];
		$form->resume_enable 	= (int) $row['form_resume_enable'];
		$form->limit_enable 	= (int) $row['form_limit_enable'];
		$form->limit 			= (int) $row['form_limit'];
		$form->label_alignment	= $row['form_label_alignment'];
		$form->schedule_enable 	= (int) $row['form_schedule_enable'];

		if(empty($row['form_language'])){
			$form->language		= 'english';
		}else{
			$form->language		= $row['form_language'];
		}

		$form->schedule_start_date  = $row['form_schedule_start_date'];
		if(!empty($row['form_schedule_start_hour'])){
			$form->schedule_start_hour  = date('h:i:a',strtotime($row['form_schedule_start_hour']));
		}else{
			$form->schedule_start_hour  = '';
		}
		$form->schedule_end_date  	= $row['form_schedule_end_date'];
		if(!empty($row['form_schedule_end_hour'])){
			$form->schedule_end_hour  	= date('h:i:a',strtotime($row['form_schedule_end_hour']));
		}else{
			$form->schedule_end_hour	= '';
		}
		$form_lastpage_title		= $row['form_lastpage_title'];
		$form_submit_primary_text 	= $row['form_submit_primary_text'];
		$form_submit_secondary_text = $row['form_submit_secondary_text'];
		$form_last_page_break_bg_color = $row['form_last_page_break_bg_color'];
		$form_submit_primary_img 	= $row['form_submit_primary_img'];
		$form_submit_secondary_img  = $row['form_submit_secondary_img'];
		$form_submit_use_image  	= (int) $row['form_submit_use_image'];
		$form->page_total			= (int) $row['form_page_total'];
		$form->pagination_type		= $row['form_pagination_type'];

		$form->review_primary_text 	 = $row['form_review_primary_text'];
		$form->review_secondary_text = $row['form_review_secondary_text'];
		$form->review_primary_img 	 = $row['form_review_primary_img'];
		$form->review_secondary_img  = $row['form_review_secondary_img'];
		$form->review_use_image  	 = (int) $row['form_review_use_image'];
		$form->review_title			 = $row['form_review_title'];
		$form->review_description	 = $row['form_review_description'];
		$form->upload_template  = $row['form_upload_template'];
		$form->enable_template  = (int) $row['form_enable_template'];

		$form->custom_script_enable  = (int) $row['form_custom_script_enable'];
		$form->custom_script_url  	 = $row['form_custom_script_url'];
		$form->enable_auto_mapping  = (int) $row['form_enable_auto_mapping'];
		$form->framework_type  = $row['form_framework_type'];
		$form->framework_or_group_id  = $row['form_framework_or_group_id'];
		$form->enable_template_wysiwyg  = (int) $row['form_enable_template_wysiwyg'];
		$form->template_wysiwyg_id  = (int) $row['form_template_wysiwyg_id'];

		// now multiple entity can be associated to a form
		$query_entity_relation = "SELECT `entity_id` FROM `".LA_TABLE_PREFIX."entity_form_relation` WHERE `form_id` = ?";
		$sth_entity_relation = la_do_query($query_entity_relation, array($form_id), $dbh);

		$form->for_selected_company  = (int) $row['form_for_selected_company'];

		/* newly added */

		$form->for_selected_entity = array();

		$form->folder_id  = (int) $row['folder_id'];

		while($row = la_do_fetch_result($sth_entity_relation)){
			array_push($form->for_selected_entity, $row['entity_id']);
		}
	}

	//get element options first and store it into array
	$query = "select
					`element_id`,
					`option_id`,
					`position`,
					`option`,
					`option_value`,
					`option_is_default`,
					`option_icon_src`
			    from
			    	".LA_TABLE_PREFIX."element_options
			   where
			   		form_id = ? and live=1
			order by
					element_id asc,`position` asc";
	$params = array($form_id);
	$sth 	= la_do_query($query,$params,$dbh);


	while($row = la_do_fetch_result($sth)){
		$element_id = $row['element_id'];
		$option_id  = $row['option_id'];
		$options_lookup[$element_id][$option_id]['position'] 		  = $row['position'];
		$options_lookup[$element_id][$option_id]['option'] 			  = $row['option'];
		$options_lookup[$element_id][$option_id]['option_value'] 	  = $row['option_value'];
		$options_lookup[$element_id][$option_id]['option_is_default'] = $row['option_is_default'];
		$options_lookup[$element_id][$option_id]['option_icon_src'] = $row['option_icon_src'];
	}

	//get the last option id for each options and store it into array
	//we need it when the user adding a new option, so that we could assign the last option id + 1
	$query = "select
					element_id,
					max(option_id) as last_option_id
			    from
			    	".LA_TABLE_PREFIX."element_options
			   where
			   		form_id = ?
			group by
					element_id";
	$params = array($form_id);
	$sth 	= la_do_query($query,$params,$dbh);

	while($row = la_do_fetch_result($sth)){
		$element_id = $row['element_id'];
		$last_option_id_lookup[$element_id] = $row['last_option_id'];
	}


	//get elements data
	$element = array();
	$query = "select
					id,
					element_id,
					element_title,
					element_guidelines,
					element_size,
					element_is_required,
					element_is_unique,
					element_is_private,
					element_type,
					element_position,
					element_default_value,
					element_constraint,
					element_range_min,
					element_range_max,
					element_range_limit_by,
					element_choice_columns,
					element_choice_has_other,
					element_choice_other_label,
					element_choice_other_score,
					element_choice_other_icon_src,
					element_time_showsecond,
					element_time_24hour,
					element_address_hideline2,
					element_address_us_only,
					element_date_enable_range,
					element_date_range_min,
					element_date_range_max,
					element_date_enable_selection_limit,
					element_date_selection_max,
					element_date_disable_past_future,
					element_date_past_future,
					element_date_disable_weekend,
					element_date_disable_specific,
					element_date_disabled_list,
					element_file_enable_type_limit,
					element_file_block_or_allow,
					element_file_type_list,
					element_file_as_attachment,
					element_file_enable_advance,
					element_file_auto_upload,
					element_file_enable_multi_upload,
					element_file_max_selection,
					element_file_enable_size_limit,
					element_file_size_max,
					element_file_select_existing_files,
					element_submit_use_image,
					element_submit_primary_text,
					element_submit_secondary_text,
					element_submit_primary_img,
					element_submit_secondary_img,
					element_page_title,
					element_matrix_allow_multiselect,
					element_matrix_parent_id,
					element_section_display_in_email,
					element_section_enable_scroll,
					element_number_enable_quantity,
					element_number_quantity_link,
					element_policymachine_code,
					element_machine_code,
					element_note,
					element_status_indicator,
					element_rich_text,
					element_page_number,
					element_video_source,
					element_video_loop,
					element_video_url,
					element_video_auto_play,
					element_media_type,
					element_label_background_color,
					element_label_color,
					element_page_break_bg_color,
					element_cascade_form_invisible,
					element_enhanced_checkbox,
					element_enhanced_multiple_choice,
					element_file_upload_synced 
					from
					".LA_TABLE_PREFIX."form_elements
			   where
			   		form_id = ? and element_status='1'
			order by
					element_position asc";
	$params = array($form_id);
	$sth 	= la_do_query($query,$params,$dbh);

	$j=0;
	while($row = la_do_fetch_result($sth)){
		$element_id = $row['element_id'];

		//lookup element options first
		$option_id_array = array();
		$element_options = array();

		if(!empty($options_lookup[$element_id])){

			$i=1;
			foreach ($options_lookup[$element_id] as $option_id => $data){
				$element_options[$option_id] = new stdClass();
				$element_options[$option_id]->position 	 = $i;
				$element_options[$option_id]->option 	 = $data['option'];
				$element_options[$option_id]->option_value = $data['option_value'];
				$element_options[$option_id]->is_default = $data['option_is_default'];
				$element_options[$option_id]->option_icon_src = $data['option_icon_src'];
				$element_options[$option_id]->is_db_live = 1;
				$option_id_array[$element_id][$i] = $option_id;
				$i++;
			}
		}


		//populate elements
		$element[$j] = new stdClass();
		$element[$j]->form_id 						= $form_id;
		$element[$j]->title 						= $row['element_title'];
		$element[$j]->guidelines 					= $row['element_guidelines'];
		$element[$j]->size 							= $row['element_size'];
		$element[$j]->is_required 					= $row['element_is_required'];
		$element[$j]->is_unique 					= $row['element_is_unique'];
		$element[$j]->is_private 					= $row['element_is_private'];
		$element[$j]->type 							= $row['element_type'];
		$element[$j]->position 						= $row['element_position'];
		$element[$j]->id 							= $row['element_id'];
		$element[$j]->is_db_live 					= 1;
		$element[$j]->default_value 				= $row['element_default_value'];
		$element[$j]->constraint 					= $row['element_constraint'];
		$element[$j]->range_min 					= (int) $row['element_range_min'];
		$element[$j]->range_max 					= (int) $row['element_range_max'];
		$element[$j]->range_limit_by	 			= $row['element_range_limit_by'];
		$element[$j]->choice_columns	 			= (int) $row['element_choice_columns'];
		$element[$j]->choice_has_other	 			= (int) $row['element_choice_has_other'];
		$element[$j]->choice_other_label 			= $row['element_choice_other_label'];
		$element[$j]->choice_other_score 			= $row['element_choice_other_score'];
		$element[$j]->choice_other_icon_src 		= $row['element_choice_other_icon_src'];
		$element[$j]->time_showsecond	 			= (int) $row['element_time_showsecond'];
		$element[$j]->time_24hour	 	 			= (int) $row['element_time_24hour'];
		$element[$j]->address_hideline2	 			= (int) $row['element_address_hideline2'];
		$element[$j]->address_us_only	 			= (int) $row['element_address_us_only'];
		$element[$j]->date_enable_range	 			= (int) $row['element_date_enable_range'];
		$element[$j]->date_range_min	 			= $row['element_date_range_min'];
		$element[$j]->date_range_max	 			= $row['element_date_range_max'];
		$element[$j]->date_enable_selection_limit	= (int) $row['element_date_enable_selection_limit'];
		$element[$j]->date_selection_max	 		= (int) $row['element_date_selection_max'];
		$element[$j]->date_disable_past_future	 	= (int) $row['element_date_disable_past_future'];
		$element[$j]->date_past_future	 			= $row['element_date_past_future'];
		$element[$j]->date_disable_weekend	 		= (int) $row['element_date_disable_weekend'];
		$element[$j]->date_disable_specific	 		= (int) $row['element_date_disable_specific'];
		$element[$j]->date_disabled_list	 		= $row['element_date_disabled_list'];
		$element[$j]->file_enable_type_limit	 	= (int) $row['element_file_enable_type_limit'];
		$element[$j]->file_block_or_allow	 		= $row['element_file_block_or_allow'];
		$element[$j]->file_type_list	 			= $row['element_file_type_list'];
		$element[$j]->file_as_attachment	 		= (int) $row['element_file_as_attachment'];
		$element[$j]->file_enable_advance	 		= (int) $row['element_file_enable_advance'];
		$element[$j]->file_auto_upload	 			= (int) $row['element_file_auto_upload'];
		$element[$j]->file_enable_multi_upload	 	= (int) $row['element_file_enable_multi_upload'];
		$element[$j]->file_max_selection	 		= (int) $row['element_file_max_selection'];
		$element[$j]->file_enable_size_limit	 	= (int) $row['element_file_enable_size_limit'];
		$element[$j]->file_size_max	 				= (int) $row['element_file_size_max'];
		$element[$j]->file_select_existing_files 	= (int) $row['element_file_select_existing_files'];
		$element[$j]->submit_use_image	 			= (int) $row['element_submit_use_image'];
		$element[$j]->submit_primary_text	 		= $row['element_submit_primary_text'];
		$element[$j]->submit_secondary_text	 		= $row['element_submit_secondary_text'];
		$element[$j]->submit_primary_img	 		= $row['element_submit_primary_img'];
		$element[$j]->submit_secondary_img	 		= $row['element_submit_secondary_img'];
		$element[$j]->page_title	 				= $row['element_page_title'];
		$element[$j]->matrix_allow_multiselect	 	= (int) $row['element_matrix_allow_multiselect'];
		$element[$j]->matrix_parent_id	 			= (int) $row['element_matrix_parent_id'];
		$element[$j]->section_display_in_email	 	= (int) $row['element_section_display_in_email'];
		$element[$j]->section_enable_scroll	 		= (int) $row['element_section_enable_scroll'];
		$element[$j]->number_enable_quantity	 	= (int) $row['element_number_enable_quantity'];
		$element[$j]->number_quantity_link	 		= $row['element_number_quantity_link'];
		$element[$j]->policymachine_code 			= $row['element_policymachine_code'];
		$element[$j]->machine_code 			        = $row['element_machine_code'];
		$element[$j]->note	        				= $row['element_note'];
		$element[$j]->status_indicator              = $row['element_status_indicator'];
		$element[$j]->rich_text              		= $row['element_rich_text'];
		$element[$j]->element_page_number           = $row['element_page_number'];
		$element[$j]->video_source              	= $row['element_video_source'];
		$element[$j]->video_loop              		= $row['element_video_loop'];
		$element[$j]->video_url              		= $row['element_video_url'];
		$element[$j]->media_type             		= $row['element_media_type'];
		$element[$j]->video_auto_play              	= $row['element_video_auto_play'];
		$element[$j]->cascade_form_invisible      	= (int) $row['element_cascade_form_invisible'];
		$element[$j]->label_background_color        = $row['element_label_background_color'];
		$element[$j]->label_color              		= $row['element_label_color'];
		$element[$j]->page_break_bg_color           = $row['element_page_break_bg_color'];
		$element[$j]->enhanced_checkbox      	    = (int) $row['element_enhanced_checkbox'];
		$element[$j]->enhanced_multiple_choice      = (int) $row['element_enhanced_multiple_choice'];
		$element[$j]->file_upload_synced            = $row['element_file_upload_synced'];

		if(!empty($element_options)){
			$element[$j]->options 	= $element_options;
			$element[$j]->last_option_id = $last_option_id_lookup[$element_id];
		}else{
			$element[$j]->options 	= '';
		}

		//if the element is a matrix field and not the parent, store the data into a lookup array for later use when rendering the markup
		if($row['element_type'] == 'matrix' && !empty($row['element_matrix_parent_id'])){

				$parent_id 	  = $row['element_matrix_parent_id'];
				$row_position = is_null($matrix_elements[$parent_id]) ? 2 : count($matrix_elements[$parent_id]) + 2;
				$element_id   = $row['element_id'];

				$matrix_elements[$parent_id][$element_id] = new stdClass();
				$matrix_elements[$parent_id][$element_id]->is_db_live = 1;
				$matrix_elements[$parent_id][$element_id]->position   = $row_position;
				$matrix_elements[$parent_id][$element_id]->row_title  = is_null($row['element_title']) ? "" : $row['element_title'];
				$matrix_elements[$parent_id][$element_id]->machine_code  = is_null($row['element_machine_code']) ? "" : $row['element_machine_code'];

				$column_data = array();
				$col_position = 1;
				foreach ($element_options as $option_id=>$value){
					$column_data[$option_id] = new stdClass();
					$column_data[$option_id]->is_db_live = 1;
					$column_data[$option_id]->position 	 = $col_position;
					$column_data[$option_id]->column_title 	= $value->option;
					$column_data[$option_id]->column_score 	= $value->option_value;
					$col_position++;
				}

				$matrix_elements[$parent_id][$element_id]->column_data = $column_data;

				//remove it from the main element array
				$element[$j] = array();
				unset($element[$j]);
				$j--;
		}

		$j++;
	}

	//if this is multipage form, add the lastpage submit property into the element list
	if($form->page_total > 1){
		$element[$j] = new stdClass();
		$element[$j]->id 		 = 'lastpage';
		$element[$j]->type 		 = 'page_break';
		$element[$j]->page_title = $form_lastpage_title;
		$element[$j]->submit_primary_text	 		= $form_submit_primary_text;
		$element[$j]->submit_secondary_text	 		= $form_submit_secondary_text;
		$element[$j]->page_break_bg_color	 		= $form_last_page_break_bg_color;
		$element[$j]->submit_primary_img	 		= $form_submit_primary_img;
		$element[$j]->submit_secondary_img	 		= $form_submit_secondary_img;
		$element[$j]->submit_use_image	 			= $form_submit_use_image;
	}


	$jquery_data_code = '';

	//build the json code for form fields
	$all_element = array('elements' => $element);
	foreach ($element as $data){
		//if this is matrix element, attach the children data into options property and merge with current (matrix parent) options
		if($data->type == 'matrix'){
			$matrix_elements[$data->id][$data->id] = new stdClass();
			$matrix_elements[$data->id][$data->id]->is_db_live = 1;
			$matrix_elements[$data->id][$data->id]->position   = 1;
			$matrix_elements[$data->id][$data->id]->row_title  = $data->title;
			$matrix_elements[$data->id][$data->id]->machine_code  = $data->machine_code;

			$column_data = array();
			$col_position = 1;
			foreach ($data->options as $option_id=>$value){
				$column_data[$option_id] = new stdClass();
				$column_data[$option_id]->is_db_live = 1;
				$column_data[$option_id]->position 	 = $col_position;
				$column_data[$option_id]->column_title 	= $value->option;
				$column_data[$option_id]->column_score  = $value->option_value;
				$col_position++;
			}

			$matrix_elements[$data->id][$data->id]->column_data = $column_data;

			$temp_array = array();
			$temp_array = $matrix_elements[$data->id];

			asort($temp_array);

			$matrix_elements[$data->id] = array();
			$matrix_elements[$data->id] = $temp_array;

			$data->options = array();
			$data->options = $matrix_elements[$data->id];

		}
		$field_settings = json_encode($data);
		$jquery_data_code .= "\$('#li_{$data->id}').data('field_properties',{$field_settings});\n";
	}

	$form_theme_array = array();
	$form_theme_array[0]['theme_id']   = 0;
	$form_theme_array[0]['theme_name'] = "Select Theme";

	$query = "SELECT `theme_id`, `theme_name` FROM `".LA_TABLE_PREFIX."form_themes` ORDER BY `theme_name`";
	$sth = la_do_query($query,array(),$dbh);

	$i=1;

	while($row = la_do_fetch_result($sth)){
		$form_theme_array[$i]['theme_id'] = $row['theme_id'];
		$form_theme_array[$i]['theme_name'] = $row['theme_name'];
		$i++;
	}


	//get the list of the form, put them into array
	$query = "SELECT
					form_name,
					form_id
				FROM
					".LA_TABLE_PREFIX."forms
				WHERE
					form_active=0 or form_active=1 and form_id <> {$form_id}
			 ORDER BY
					form_name ASC";

	$params = array();
	$sth = la_do_query($query,$params,$dbh);

	$form_list_array = array();


	$form_list_array[0]['form_id']   	  = 0;
	$form_list_array[0]['form_name']     = "Select Form";

	$i=1;

	while($row = la_do_fetch_result($sth)){
		$form_list_array[$i]['form_id']   	  = $row['form_id'];

		if(!empty($row['form_name'])){
			$form_list_array[$i]['form_name'] = htmlentities($row['form_name'],ENT_QUOTES)." (#{$row['form_id']})";
		}else{
			$form_list_array[$i]['form_name'] = '-Untitled Form- (#'.$row['form_id'].')';
		}
		$i++;
	}

	//build the json code for form settings
	$json_form = json_encode($form);
	$json_form_info = json_encode($formInfo);
	$jquery_data_code .= "\$('#form_header').data('form_properties',{$json_form});\n";
	$jquery_data_code .= "\$('#form_header').data('form_info',{$json_form_info});\n";

    header("Content-Security-Policy: media-src * data:;");

	$header_data =<<<EOT
<link type="text/css" href="js/jquery-ui/jquery-ui.min.css" rel="stylesheet" />
<link type="text/css" href="css/edit_form.css" rel="stylesheet" />
<link type="text/css" href="js/datepick/smoothness.datepick.css" rel="stylesheet" />
<link type="text/css" href="js/video-js/video-js.css" rel="stylesheet" />
<link type="text/css" href="js/colorpicker/jquery.colorpicker.css" rel="stylesheet" />
<script type="text/javascript" src="js/video-js/video.js"></script>
<script type="text/javascript" src="js/video-js/youtube.min.js"></script>
<script type="text/javascript" src="js/video-js/vimeo.js"></script>
<style type="text/css">
.ck-editor__editable {
 	height: 200px!important;
    min-height: initial!important;
}
#progressbox {
	border: 1px solid #0099CC;
	padding: 1px;
	position: relative;
	width: 200px;
	border-radius: 3px;
	margin: 10px;
	display: none;
	text-align: left;
}
#progressbar {
	height: 20px;
	border-radius: 3px;
	background-color: #003333;
	width: 1%;
}
#statustxt {
	top: 3px;
	left: 50%;
	position: absolute;
	display: inline-block;
	color: #000000;
}
#prop_matrix_row fieldset, #prop_matrix_column fieldset {
	position: relative;
}
div.cus_legend {
	background: #3d6c10 none repeat scroll 0 0;
	border: 1px solid #3d6c10;
	border-radius: 7px;
	color: #ffffff;
	float: left;
	font-family: "glober_regularregular", "Trebuchet MS", "Lucida Grande", Tahoma, Arial, sans-serif;
	font-size: 100%;
	left: 75px;
	margin-left: 10px;
	padding-left: 5px;
	padding-right: 5px;
	position: absolute;
	top: -20px;
}

/*** Microsoft Edge ***/
@supports (-ms-accelerator:true) {
 div.cus_legend {
 top: -1px;
}
}

/*** Apple Safari ***/
@media screen and (-webkit-min-device-pixel-ratio:0) {
/* Safari and Chrome, if Chrome rule needed */
div.cus_legend {
	top: -1px;
}
    /* Safari 5+ ONLY */
    ::i-block-chrome, div.cus_legend {
 top: -1px;
}
}
</style>
EOT;

	$current_nav_tab = 'manage_forms';

	require('includes/header.php');
?>
<div id="editor_loading"> Loading... Please wait... </div>
<div id="content" class="medium">
  <div class="form_editor"> <span id="selected_field_image" class="arrow-field-prop" ><img src="images/navigation/FFFFFF/24x24/Forward.png"></span>

    <?php
	echo $markup;
    ?>
    <div id="bottom_bar" style="display: none">
      <!--<div class="bottom_bar_side"> <img style="float: left" src="images/bullet_green.png" /> <img style="float: right" src="images/bullet_green.png"/> </div> -->
      <div id="bottom_bar_content" class="buttons_bar">
        <?php
        $save_button_text = 'Save Form';
        $pull_from_form = '';
        if( $form->enable_auto_mapping ) {
          $save_button_text = 'Sync and Save';
          $pull_from_form = 1;
        }

      if( $pull_from_form ) {
      ?>
      <p style="margin-bottom:10px;display: none;">
        Pull From form (Optional) 
        <select class="element select" id="pull_from_form" name="pull_from_form">
            <?php
              if(!empty($form_list_array)){
                foreach ($form_list_array as $value) {
                  echo "<option value=\"{$value['form_id']}\">{$value['form_name']}</option>";
                }
              }
            ?>
						
          </select>
      </p>
      <?php
      }

	if($is_new_form == false){
		$check_form_data = "select count(id) as counter from ".LA_TABLE_PREFIX."form_{$form_id}";
		$check_form_result = la_do_query($check_form_data,array(),$dbh);
		$check_form_row = la_do_fetch_result($check_form_result);
		if($check_form_row['counter'] > 0){
    ?>
		<a id="bottom_bar_save_form" href="#" class="bb_button bb_green" alt="Save Form" title="Save Form" data-counter="1" data-allow_syncsave="0"><img src="images/navigation/FFFFFF/24x24/Create_new_form.png"> <span id="bottom_bar_save_form_text"><?=$save_button_text?></span> </a>
        <?php
		}else{
	?>
        <a id="bottom_bar_save_form" href="#" class="bb_button bb_green" alt="Save Form" title="Save Form" data-counter="0" data-allow_syncsave="0"><img src="images/navigation/FFFFFF/24x24/Create_new_form.png"> <span id="bottom_bar_save_form_text"><?=$save_button_text?></span> </a>
        <?php
		}
	}else{
	?>
        <a id="bottom_bar_save_form" href="#" class="bb_button bb_green"  alt="Save Form" title="Save Form" data-allow_syncsave="0"><img src="images/navigation/FFFFFF/24x24/Create_new_form.png" data-counter="0"> <span id="bottom_bar_save_form_text"><?=$save_button_text?></span> </a>
        <?php
	}
	?>
        <a id="bottom_bar_add_field" class="bb_button bb_grey" href="#" alt="Add a New Field" title="Add a New Field"><img src="images/navigation/FFFFFF/24x24/Add.png"> Add Field </a>
        <div id="bottom_bar_field_action">
         <a id="bottom_bar_duplicate_field" href="#" class="bb_button bb_grey" alt="Duplicate Selected Field" title="Duplicate Selected Field"><img src="images/navigation/FFFFFF/24x24/Duplicate.png"> Duplicate </a> <a id="bottom_bar_delete_field" href="#" class="bb_button bb_red" alt="Delete Selected Field" title="Delete Selected Field"> <!--<span class="icon-remove"></span>--><img src="images/navigation/FFFFFF/24x24/Delete.png"> Delete </a> </div>
      </div>
      <div id="bottom_bar_loader"> <span> <img src="images/loader.gif" width="32" height="32"/> <span id="bottom_bar_msg">Please wait... Synching...</span> </span> </div>
      <!-- <div class="bottom_bar_side"> <img style="float: left" src="images/bullet_green.png" /> <img style="float: right" src="images/bullet_green.png"/> </div> -->
    </div>
    <div id="bottom_bar_limit"></div>
    <?php if($is_new_form){ ?>
    <div id="no_fields_notice">
    	<img src="images/navigation/FFFFFF/50x50/Forward.png" style="margin-bottom: 20px;background-color: #33BF8C;font-size: 50px;">
		<h3>Your form has no fields yet!</h3>
		<p><span style="color: #33BF8C; font-weight: bold;">Click the buttons</span> on the right sidebar or <span style="color: #33BF8C; font-weight: bold;">Drag it here</span> to add new field.</p>
    </div>
    <?php } ?>
  </div>
</div>
<!-- /#content -->

<div id="sidebar">
  <div id="builder_tabs">
    <ul id="builder_tabs_btn" style="display: none">
      <li id="btn_add_field"><a href="#tab_add_field">Add a Field</a></li>
      <li id="btn_field_properties"><a href="#tab_field_properties">Field Properties</a></li>
      <li id="btn_form_properties"><a href="#tab_form_properties">Form Properties</a></li>
    </ul>
    <div id="tab_add_field">
      <div id="social" class="box">
        <ul>
<?php
  
  $buttons = array (
    "Single Line Text",
    "Paragraph Text",
    "Name",
    "Address",
    "Phone",
    "Email",
    "Time",
    "Date",
    "Number",
    "Price",
    "Website",
    "Syndication",
    "Multiple Choice",
    "Checkboxes",
    "Matrix Choice",
    "Drop Down",
    "File Upload",
    "Signature",
    "Section Break",
    "Page Break",
    "Casecade Form",
    "Media Player"
  );
  foreach ($buttons as $button) {
    $str = strtolower($button);
	$id = str_replace(' ', '_', $str);
    $fname = str_replace(' ', '%20', ucfirst($str));
    $title = ucwords($str);

    if( $button == 'Media Player' )
    	$id = 'video_player';

    if( $button == 'Website' )
    	$title = 'Web Site';

    if( $button == 'Casecade Form')
    	$title = 'Cascade Form';

    echo "<li id='btn_{$id}' class='box'> <a id='a_{$id}' href='#' title='{$title}'> <span><img style='margin:6px 5px 0 0;' src='images/form_edit/{$fname}.png'></span><span class='blabel'>{$title}</span> </a> </li>\n";
  }

?>
        </ul>
        <div class="clear"></div>
      </div>
      <!-- /#social -->
    </div>
    <div id="tab_field_properties" style="display: none">
      <div id="field_properties_pane" class="box"> <!-- Start field properties pane -->
        <form style="display: block;" id="element_properties" action="" onsubmit="return false;">
          <div id="element_inactive_msg">
            <!-- <div class="bullet_bar_top"> <img class="left" src="images/bullet_green.png" /> <img class="right" src="images/bullet_green.png"/> -->
            <img src="images/navigation/FFFFFF/50x50/Back.png" style="margin-top: 80px;background-color: #33BF8C;font-size: 50px;">
            <h3>Please select a field</h3>
            <p id="eim_p">Click on a field on the left to change its properties.</p>
            <!-- <div class="bullet_bar_bottom"> <img class="left" src="images/bullet_green.png" /> <img class="right" src="images/bullet_green.png"/> </div>-->
          </div>
          <div id="element_properties_form">
            <!-- <div class="bullet_bar_top"> <img class="left" src="images/bullet_green.png" /> <img class="right" src="images/bullet_green.png"/> </div>-->
            <div class="num" id="element_position">12</div>
            <ul id="all_properties">
              <li id="prop_element_label">
                <label class="desc" for="element_label">Field Label <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Field Label is one or two words placed directly above the field."/> </label>
                <textarea id="element_label" name="element_label" class="textarea"></textarea>
              </li>
              <li class="feed-element" id="prop_element_feed_url" style="display:list-item;">
                <label class="desc" for="element_feed_url">Field URL<img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Feed Url"/> </label>
                <input type="text" id="element_feed_url" name="element_feed_url" class="text large" placeholder="http://" />
              </li>
              <li class="leftCol feed-element" id="prop_feed_box_width_px" style="display:list-item;">
                <label class="desc" for="feed_box_width_px"> Width <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Feed box width"/> </label>
                <input type="text" id="feed_box_width_px" name="feed_box_width_px" class="text medium" placeholder="px" />
              </li>
              <li class="rightCol feed-element" id="prop_feed_box_height_px" style="display:list-item;">
                <label class="desc" for="feed_box_height_px"> Height <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Feed box height"/> </label>
                <input type="text" id="feed_box_height_px" name="feed_box_height_px" class="text medium" placeholder="px" value="300" />
              </li>
              <li class="leftCol feed-element" id="prop_feed_scroll_bar" style="display:list-item;">
                <label class="desc" for="feed_scroll_bar"> Scroll Bar <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: middle" title="Feed box scroll bar"> </label>
                <input type="radio" id="feed_scroll_bar" name="feed_scroll" value="scroll-bar" />
              </li>
              <li class="rightCol feed-element" id="prop_feed_auto_scroll" style="display:list-item;">
                <label class="desc" for="feed_auto_scroll"> Auto Scroll <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: middle" title="Feed box auto scroll"> </label>
                <input type="radio" id="feed_auto_scroll" name="feed_scroll" value="auto-scroll" checked />
              </li>
              <li class="leftCol feed-element" id="prop_feed_box_speed">
                <label class="desc" for="feed_box_speed"> Scroll Speed <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Feed box scroll speed"> </label>
                <input type="text" id="feed_box_speed" name="feed_box_speed" class="text medium" placeholder="sec/step" value="3" />
              </li>
              <li class="rightCol feed-element" id="prop_feed_box_direction" style="display:list-item;">
                <label class="desc" for="feed_box_direction"> Scroll Direction <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: middle" title="Feed box scroll direction"> </label>
                <select class="select full" id="feed_box_direction" name="feed_box_direction" autocomplete="off">
                  <option value="vertical" selected>Vertical</option>
                  <option value="horizontal">Horizontal</option>
                </select>
              </li>
              <li class="leftCol feed-element" id="prop_feed_box_no_of_msg">
                <label class="desc" for="feed_box_no_of_msg"> No of RSS msg <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Feed box number of RSS messages"> </label>
                <input type="text" id="feed_box_no_of_msg" name="feed_box_no_of_msg" class="text medium" placeholder="Number of RSS messages" value="3" />
              </li>
              <li class="rightCol feed-element" id="prop_feed_box_datetime" style="display:list-item;">
                <label class="desc" for="feed_box_datetime"> Datetime <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: middle" title="Feed box datetime include|exclude option"> </label>
                <input type="checkbox" id="feed_box_datetime" name="feed_box_datetime" />
              </li>
            <!--start::media player options-->
              	<li class="video-player-element" id="prop_media_type" style="display:none;">
                  	<label class="desc" for="media_type"> Media Type <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: middle" title="Select Video or Image"> </label>
                  	<select class="select full" id="media_type" name="media_type" autocomplete="off">
                      	<option value="video" selected>Video</option>
                      	<option value="image">Image</option>
                  	</select>
                </li>

                <li class="video-player-element" id="prop_video_source" style="display:none;">
                  	<label class="desc" for="video_source"> Media Source <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: middle" title="Select local or remote file"> </label>
                  	<select class="select full" id="video_source" name="video_source" autocomplete="off">
                      	<option value="local" selected>Local</option>
                      	<option value="remote">Remote</option>
                  	</select>
                </li>
                <li class="video-player-element" id="prop_video_file" style="display:none;">
                    <label class="desc"> Media File <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: middle" title="Maximum file size <?php echo $upload_max_size ?>MB"> </label>
                    <input type="file" id="video_file" name="video_file" />
                    <div class="relative"><div class="file-size-error error-msg"></div></div>
                </li>
                <li class="video-player-element" id="prop_video_url" style="display:none;">
                    <label class="desc" for="video_url"> Media Url <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: middle" title="Youtube, Vimeo or path to video source"> </label>
                    <input type="text" id="video_url" name="video_url" class="text full" placeholder="http://" />
                </li>
                <li class="leftCol video-player-element" id="prop_video_loop" style="display:none;">
                    <input type="checkbox" id="video_loop" name="video_loop" />
                    <label class="choice" for="video_loop"> Loop</label>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: middle" title="On or off video loop">
                </li>
                <li class="rightCol video-player-element" id="prop_video_auto_play" style="display:none;">
                    <input type="checkbox" id="video_auto_play" name="video_auto_play" />
                    <label class="choice" for="video_auto_play"> Auto Play</label>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: middle" title="On or off video auto play">
                </li>
            <!--end::media player options-->

              <li class="clear" id="rss-clear-li" style="display:none;"></li>
              <!-- <li id="prop_element_share_link">
              		<label class="desc"> Field Share link <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Share this link with any user to redirect them to particular form field."/></label>
              		<label class="share_link_copied"></label>
              		<p></p>
              </li> -->
              <li class="leftCol" id="prop_element_label_background_color">
                <label class="desc" for="element_label_background_color"> Label Background <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Field Type determines the background color of label."/> </label>
                <input id="element_label_background_color" class="text" style="width: 95%" type="text" />
              </li>
              <li class="rightCol" id="prop_element_label_color">
                <label class="desc" for="element_label_color"> Label Color <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Field Type determines the font color of label."/> </label>
                <input id="element_label_color" class="text" style="width: 95%" type="text" />
              </li>
              <li class="leftCol" id="prop_element_type">
                <label class="desc" for="element_type"> Field Type <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Field Type determines what kind of data can be collected by your field. After you save the form, the field type cannot be changed."/> </label>
                <select class="select full" id="element_type" name="element_type" autocomplete="off" tabindex="12">
                  <option value="text">Single Line Text</option>
                  <option value="textarea">Paragraph Text</option>
                  <option value="radio">Multiple Choice</option>
                  <option value="checkbox">Checkboxes</option>
                  <option value="select">Drop Down</option>
                  <option value="number">Number</option>
                  <option value="simple_name">Name</option>
                  <option value="policymachine_code">Template Code</option>
                  <option value="date">Date</option>
                  <option value="time">Time</option>
                  <option value="phone">Phone</option>
                  <option value="money">Price</option>
                  <option value="url">Web Site</option>
                  <option value="email">Email</option>
                  <option value="address">Address</option>
                  <option value="file">File Upload</option>
                  <option value="section">Section Break</option>
                  <option value="matrix">Matrix Choice</option>
                  <option value="casecade_form">Cascade Form</option>
                </select>
              </li>
              <li class="rightCol" id="prop_element_size">
                <label class="desc" for="element_size"> Field Size <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This property set the visual appearance of the field in your form. It does not limit nor increase the amount of data that can be collected by the field."/> </label>
                <select class="select full" id="element_size" autocomplete="off" tabindex="13">
                  <option value="small">Small</option>
                  <option value="medium">Medium</option>
                  <option value="large">Large</option>
                </select>
              </li>
              <li class="rightCol" id="prop_choice_columns">
                <label class="desc" for="element_choice_columns"> Choice Columns <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Set the number of columns being used to display the choices. Inline columns means the choices are sitting next to each other."/> </label>
                <select class="select full" id="element_choice_columns" autocomplete="off">
                  <option value="1">One Column</option>
                  <option value="2">Two Columns</option>
                  <option value="3">Three Columns</option>
                  <option value="9">Inline</option>
                </select>
              </li>
              <li class="rightCol" id="prop_date_format">
                <label class="desc" for="field_size"> Date Format <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can choose between American and European Date Formats"/> </label>
                <select class="select full" id="date_type" autocomplete="off">
                  <option id="element_date" value="date">MM / DD / YYYY</option>
                  <option id="element_europe_date" value="europe_date">DD / MM / YYYY</option>
                </select>
              </li>
              <li class="rightCol" id="prop_name_format">
                <label class="desc" for="name_format"> Name Format <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Two format available. A normal name field, or an extended name field with title and suffix."/> </label>
                <select class="select full" id="name_format" autocomplete="off">
                  <option id="element_simple_name" value="simple_name" selected="selected">Normal</option>
                  <option id="element_name" value="name" selected="selected">Normal + Title</option>
                  <option id="element_simple_name_wmiddle" value="simple_name_wmiddle" selected="selected">Full</option>
                  <option id="element_name_wmiddle" value="name_wmiddle">Full + Title</option>
                </select>
              </li>
              <li class="rightCol" id="prop_phone_format">
                <label class="desc" for="field_size"> Phone Format <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can choose between American and International Phone Formats"/> </label>
                <select class="select full" id="phone_format" name="phone_format" autocomplete="off">
                  <option id="element_phone" value="phone">### - ### - ####</option>
                  <option id="element_simple_phone" value="simple_phone">International</option>
                </select>
              </li>
              <li class="rightCol" id="prop_currency_format">
                <label class="desc" for="field_size"> Currency Format </label>
                <select class="select full" id="money_format" name="money_format" autocomplete="off">
                  <option id="element_money_usd" value="dollar">&#36; - Dollars</option>
                  <option id="element_money_euro" value="euro">&#8364; - Euros</option>
                  <option id="element_money_pound" value="pound">&#163; - Pounds Sterling</option>
                  <option id="element_money_yen" value="yen">&#165; - Yen</option>
                  <option id="element_money_baht" value="baht">&#3647; - Baht</option>
                  <option id="element_money_forint" value="forint">&#70;&#116; - Forint</option>
                  <option id="element_money_franc" value="franc">CHF - Francs</option>
                  <option id="element_money_koruna" value="koruna">&#75;&#269; - Koruna</option>
                  <option id="element_money_krona" value="krona">kr - Krona</option>
                  <option id="element_money_pesos" value="pesos">&#36; - Pesos</option>
                  <option id="element_money_rand" value="rand">R - Rand</option>
                  <option id="element_money_ringgit" value="ringgit">RM - Ringgit</option>
                  <option id="element_money_rupees" value="rupees">Rs - Rupees</option>
                  <option id="element_money_zloty" value="zloty">&#122;&#322; - ZÅoty</option>
                  <option id="element_money_riyals" value="riyals">&#65020; - Riyals</option>
                </select>
              </li>
              <li class="clear" id="prop_choices">
                <fieldset class="choices">
                  <legend> Choices and Scores <img class="helpmsg" src="images/navigation/FFFFFF/16x16/Help.png" style="vertical-align: top; " title="Use the plus and minus buttons to add and delete choices. Click on the choice to make it the default selection. Please enter a numeric value in the field adjacent to the response that may be used for risk weighting or other analytical calculations of your choice."/> </legend>
                  <ul id="element_choices">
                    <li>
                      <input type="radio" title="Select this choice as the default." class="choices_default" name="choices_default" />
                      <input type="text" value="First option" autocomplete="off" class="text" id="choice_1" />
                      <img title="Add" alt="Add" src="images/icons/add.png" style="vertical-align: middle" >
                      <img title="Delete" alt="Delete" src="images/icons/delete.png" style="vertical-align: middle" >
                      <input type="button" id="choice_upload_1" class="upload-icon" value="Upload Icon" />
                  	</li>
                  </ul>
                  <div style="text-align: center;padding-top: 5px;padding-bottom: 10px"> <img src="images/icons/page_go.png" style="vertical-align: top"/> <a href="#" id="bulk_import_choices">bulk insert choices</a> </div>
                </fieldset>
              </li>
              <li class="clear" id="prop_choices_other">
                <fieldset class="choices">
                  <legend> Choices Options </legend>
                  <span>
                  <input id="prop_choices_other_checkbox" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_choices_other_checkbox">Allow Client to Add Other Choice</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enable this option if you would like to allow your client to write his own answer if none of the other choices are applicable. A text field will be added to the last choice. Enter the label below this checkbox."/>
                  <div style="margin-bottom: 5px;margin-top: 3px;padding-left: 20px"> <img src="images/navigation/005499/16x16/Tag.png" style="vertical-align: middle">
                    <input id="prop_other_choices_label" style="width: 170px" class="text" value="" size="25" type="text">
                    <input type="text" autocomplete="off" class="choice-value" id="prop_other_choices_score" style="width:35px; background-color: #FFF;" maxlength="5" placeholder="Score">
                    <input type="button" id="prop_other_choices_icon" value="Upload Icon" />
                    <input type="hidden" id="prop_other_choices_icon_src" value="" />
                  </div>
                  <span id="prop_choices_randomize_span" style="display: none">
                  <input id="prop_choices_randomize" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_choices_randomize">Randomize Choices</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enable this option if you would like the choices to be shuffled around each time the form being displayed."/> </span> </span>
                </fieldset>
              </li>
              <li class="clear" id="prop_matrix_row">
                <fieldset class="choices">
                  <legend> Rows <img class="helpmsg" src="images/navigation/FFFFFF/16x16/Help.png" style="vertical-align: top; " title="Enter rows labels here. Use the plus and minus buttons to add and delete matrix row. "/> </legend>

                  <!-- new custome legend is added -->
                  <div class="cus_legend"> Template Code <img class="helpmsg" src="images/navigation/FFFFFF/16x16/Help.png" style="vertical-align: top; " title="Enter Template code. "/> </div>
                  <!-- new custome legend is added -->

                  <ul id="element_matrix_row">
                    <li>
                      <input type="text" value="First Question" autocomplete="off" class="text" id="matrixrow_1" />
                      <img title="Add" alt="Add" src="images/icons/add.png" style="vertical-align: middle" > <img title="Delete" alt="Delete" src="images/icons/delete.png" style="vertical-align: middle" > </li>
                  </ul>
                  <div style="text-align: center;padding-top: 5px;padding-bottom: 10px"> <img src="images/icons/page_go.png" style="vertical-align: top"/> <a href="#" id="bulk_import_matrix_row">bulk insert rows</a> </div>
                </fieldset>
              </li>
              <li class="clear" id="prop_matrix_column">
                <fieldset class="choices">
                  <legend> Columns <img class="helpmsg" src="images/navigation/FFFFFF/16x16/Help.png" style="vertical-align: top; " title="Enter column labels here. Use the plus and minus buttons to add and delete matrix column. "/> </legend>

                  <!-- new custome legend is added -->
                  <div class="cus_legend" style="left:90px;"> Score <img class="helpmsg" src="images/navigation/FFFFFF/16x16/Help.png" style="vertical-align: top; " title="Enter score. "/> </div>
                  <!-- new custome legend is added -->

                  <ul id="element_matrix_column">
                    <li>
                      <input type="text" value="First Question" autocomplete="off" class="text" id="matrixcolumn_1" />
                      <img title="Add" alt="Add" src="images/icons/add.png" style="vertical-align: middle" > <img title="Delete" alt="Delete" src="images/icons/delete.png" style="vertical-align: middle" > </li>
                  </ul>
                  <div style="text-align: center;padding-top: 5px;padding-bottom: 10px"> <img src="images/icons/page_go.png" style="vertical-align: top"/> <a href="#" id="bulk_import_matrix_column">bulk insert columns</a> </div>
                </fieldset>
              </li>
              <li id="prop_breaker"></li>
              <li class="leftCol" id="prop_options">
                <fieldset class="fieldset">
                  <legend>Rules</legend>
                  <input id="element_required" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="element_required">Required</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Checking this rule will make sure that a user fills out a particular field. A message will be displayed to the user if they have not filled out the field."/> <br>
                  <span id="element_unique_span">
                  <input id="element_unique" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="element_unique">No Duplicates</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Checking this rule will verify that the data entered into this field is unique and has not been submitted previously."/> </span><br>
                </fieldset>
              </li>
              <li class="rightCol" id="prop_access_control">
                <fieldset class="fieldset">
                  <legend>Field Visible to</legend>
                  <input id="element_public" name="element_visibility" class="radio" value="" checked="checked" type="radio">
                  <label class="choice" for="element_public">Everyone</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is the default option. The field will be accessible by anyone when the form is made public."/> <br>
                  <span id="admin_only_span">
                  <input id="element_private" name="element_visibility" class="radio" value="" type="radio">
                  <label class="choice" for="element_private">Admin Only</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Fields that are set to 'Admin Only' will not be shown to users when the form is made public."/></span><br>
                </fieldset>
              </li>
              <li class="clear" id="prop_time_options">
                <fieldset class="choices">
                  <legend> Time Options </legend>
                  <span>
                  <input id="prop_time_showsecond" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_time_showsecond">Show Seconds Field</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Checking this will enable Seconds field on your time field."/> <br/>
                  <input id="prop_time_24hour" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_time_24hour">Use 24 Hour Format</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This will enable 24-hour notation in the form hh:mm (for example 14:23) or hh:mm:ss (for example, 14:23:45)"/> </span>
                </fieldset>
              </li>
              <li class="clear" id="prop_text_options">
                <fieldset class="choices">
                  <legend> Text Option </legend>
                  <span>
                  <input id="prop_text_as_password" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_text_as_password">Display as Password Field</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Checking this will display the field as a password field and masked all the characters (shown as asterisks or circles). <br/><br/>Please be aware that there is <u>no encryption</u> being made for the password field. You will be able to see it from the admin panel/email as a plain text."/> </span>
                </fieldset>
              </li>
              <li class="clear" id="prop_matrix_options">
                <fieldset class="choices">
                  <legend> Matrix Option </legend>
                  <span>
                  <input id="prop_matrix_allow_multiselect" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_matrix_allow_multiselect">Allow Multiple Answers Per Row</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Checking this option will allow your client to select multiple answers for each row. This option can only be set once, when you initially added the matrix field. Once you have saved the form, this option can't be changed."/> </span>
                </fieldset>
              </li>
              <li class="clear" id="prop_address_options">
                <fieldset class="choices">
                  <legend> Address Options </legend>
                  <span>
                  <input id="prop_address_hideline2" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_address_hideline2">Hide Address Line 2</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Hide the 'Address Line 2' field from the address field."/> <br/>
                  <input id="prop_address_us_only" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_address_us_only">Restrict to U.S. State Selection</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Checking this will limit the country selection to United States only and the state field will be populated with U.S. state list"/> </span>
                </fieldset>
              </li>
              <li class="clear" id="prop_date_options">
                <fieldset class="choices">
                  <legend> Date Options </legend>
                  <span>
                  <input id="prop_date_range" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_date_range">Enable Minimum and/or Maximum Dates</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can set minimum and/or maximum dates within which a date may be chosen."/>
                  <div id="prop_date_range_details" style="display: none;">
                    <div id="form_date_range_minimum">
                      <label class="desc">Minimum Date:</label>
                      <span>
                      <input type="text" value="" maxlength="2" size="2" style="width: 2em;" class="text" name="date_range_min_mm" id="date_range_min_mm">
                      <label for="date_range_min_mm">MM</label>
                      </span> <span>
                      <input type="text" value="" maxlength="2" size="2" style="width: 2em;" class="text" name="date_range_min_dd" id="date_range_min_dd">
                      <label for="date_range_min_dd">DD</label>
                      </span> <span>
                      <input type="text" value="" maxlength="4" size="4" style="width: 3em;" class="text" name="date_range_min_yyyy" id="date_range_min_yyyy">
                      <label for="date_range_min_yyyy">YYYY</label>
                      </span> <span style="height: 30px;padding-right: 10px;">
                      <input type="hidden" value="" maxlength="4" size="4" style="width: 3em;" class="text" name="linked_picker_range_min" id="linked_picker_range_min">
                      <div style="display: none"><img id="date_range_min_pick_img" alt="Pick date." src="images/icons/calendar.png" class="trigger" style="margin-top: 3px; cursor: pointer" /></div>
                      </span> </div>
                    <div id="form_date_range_maximum">
                      <label class="desc">Maximum Date:</label>
                      <span>
                      <input type="text" value="" maxlength="2" size="2" style="width: 2em;" class="text" name="date_range_max_mm" id="date_range_max_mm">
                      <label for="date_range_max_mm">MM</label>
                      </span> <span>
                      <input type="text" value="" maxlength="2" size="2" style="width: 2em;" class="text" name="date_range_max_dd" id="date_range_max_dd">
                      <label for="date_range_max_dd">DD</label>
                      </span> <span>
                      <input type="text" value="" maxlength="4" size="4" style="width: 3em;" class="text" name="date_range_max_yyyy" id="date_range_max_yyyy">
                      <label for="date_range_max_yyyy">YYYY</label>
                      </span> <span>
                      <input type="hidden" value="" maxlength="4" size="4" style="width: 3em;" class="text" name="linked_picker_range_max" id="linked_picker_range_max">
                      <div style="display: none"><img id="date_range_max_pick_img" alt="Pick date." src="images/icons/calendar.png" class="trigger" style="margin-top: 3px; cursor: pointer" /></div>
                      </span> </div>
                    <div style="clear: both"></div>
                  </div>
                  <div style="clear: both"></div>
                  <input id="prop_date_selection_limit" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_date_selection_limit">Enable Date Selection Limit</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is useful for reservation or booking form, so that you could allocate each day for a maximum number of customers. For example, setting the value to 5 will ensure that the same date can't be booked/selected by more than 5 customers."/>
                  <div id="form_date_selection_limit" style="display: none"> Only allow each date to be selected
                    <input id="date_selection_max" style="width: 20px" class="text" value="" maxlength="255" type="text">
                    times </div>
                  <div style="clear: both"></div>
                  <input id="prop_date_past_future_selection" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_date_past_future_selection">Disable</label>
                  <select class="select medium" id="prop_date_past_future" name="prop_date_past_future" autocomplete="off" disabled="disabled">
                    <option id="element_date_past" value="p">All Past Dates</option>
                    <option id="element_date_future" value="f">All Future Dates</option>
                  </select>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Checking this option will disable either past or future dates selection."/>
                  <div style="clear: both"></div>
                  <input id="prop_date_disable_weekend" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_date_disable_weekend">Disable Weekend Dates</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Checking this option will disable all weekend dates."/>
                  <div style="clear: both"></div>
                  <input id="prop_date_disable_specific" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_date_disable_specific">Disable Specific Dates</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can disable any specific dates to prevent them from being selected by your clients. Use the datepicker to disable multiple dates."/>
                  <div id="form_date_disable_specific" style="display: none">
                    <textarea class="textarea" rows="10" cols="100" style="width: 175px;height: 45px" id="date_disabled_list"></textarea>
                    <div style="display: none"><img id="date_disable_specific_pick_img" alt="Pick date." src="images/icons/calendar.png" class="trigger" style="vertical-align: top; cursor: pointer" /></div>
                  </div>
                  </span>
                </fieldset>
              </li>
              <li class="clear" id="prop_section_options">
                <fieldset class="choices">
                  <legend> Section Break Options </legend>
                  <span>
                  <input id="prop_section_email_display" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_section_email_display">Display Section Break in Email</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enable this option if you need to display the content of the section break within the notification email, review page and entries page."/>
                  <div style="clear: both"></div>
                  <input id="prop_section_enable_scroll" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_section_enable_scroll">Enable Scrollbar</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="The section break will be set to a fixed height and a vertical scrollbar will be displayed as needed. This is useful to display large amount of text, such as terms and conditions, or contract agreement."/>
                  <div id="div_section_size" style="display: none"> Section Break Size:
                    <select class="select" id="prop_section_size" autocomplete="off" tabindex="13" style="width: 100px">
                      <option value="small">Small</option>
                      <option value="medium">Medium</option>
                      <option value="large">Large</option>
                    </select>
                  </div>
                  <div style="clear: both"></div>
                  </span>
                </fieldset>
              </li>
              <li class="clear" id="prop_file_options">
                <fieldset class="choices">
                  <legend> Upload Options </legend>
                  <span>
                  <input id="prop_file_enable_type_limit" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_file_enable_type_limit">Limit File Upload Type</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can block or only allow certain file types to be uploaded. Enter the file types extension into the textbox, separate them with commas. (example: jpg,gif,png,bmp)"/>
                  <div id="form_file_limit_type" style="display: none">
                    <select class="select" id="prop_file_block_or_allow" name="prop_file_block_allow" autocomplete="off" style="width: 90px">
                      <option id="element_file_allow" value="a">Only Allow</option>
                      <option id="element_file_block" value="b">Block</option>
                    </select>
                    <label class="choice" for="file_type_list">file types listed below:</label>
                    <textarea class="textarea" rows="10" cols="100" style="width: 230px; height: 30px;margin-top: 5px" id="file_type_list"></textarea>
                  </div>
                  <div style="clear: both"></div>
                  <input id="prop_file_as_attachment" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_file_as_attachment">Send File as Email Attachment</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="By default, all file uploads will be sent to your email as a download link. Checking this option will send the file as email attachment instead. WARNING: Don't enable this option if you expect to receive large files from your clients. If the files attached are larger than the allowed memory limit on your server, the email won't be sent."/>
                  <div style="clear: both"></div>
                  <input id="prop_file_enable_advance" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_file_enable_advance">Enable Advanced Uploader</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Checking this option will enable advanced functionality, such as Upload Progress Bar, Multiple File Uploads, AJAX uploads, File Size Limit, etc. This option is recommended to be enabled."/> </span>
                  <div style="clear: both"></div>
                  <input id="prop_file_upload_synced" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_file_upload_synced">Enable Synced Upload</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Checking this option will enable Synced File Upload."/> </span>
                </fieldset>
              </li>
              <li class="clear" id="prop_file_advance_options">
                <fieldset class="choices">
                  <legend> Advanced Uploader Options </legend>
                  <span>
                  <input id="prop_file_auto_upload" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_file_auto_upload">Automatically Upload Files</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="By default, the upload button or the form submit button need to be clicked to start uploading the file. By checking this option, the file will be automatically being uploaded as soon as the file being selected."/>
                  <div style="clear: both"></div>
                  <input id="prop_file_multi_upload" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_file_multi_upload">Allow Multiple File Upload</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Checking this option will allow multiple files to be uploaded. You can also limit the maximum number of files to be uploaded."/>
                  <div id="form_file_max_selection"> Limit selection to a maximum
                    <input id="file_max_selection" style="width: 20px" class="text" value="" maxlength="255" type="text">
                    files </div>
                  <div style="clear: both"></div>
                  <input id="prop_file_limit_size" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_file_limit_size">Limit File Size</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can set the maximum size of a file allowed to be uploaded here."/>
                  <div id="form_file_limit_size"> Limit each file to a maximum
                    <input id="file_size_max" style="width: 20px" class="text" value="" maxlength="255" type="text">
                    MB </div>
                  <div style="clear: both"></div>
                  <input id="prop_file_select_existing_files" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_file_select_existing_files">Allow Selecting Files Previously Uploaded</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Checking this option will allow entity users to select existing files previously uploaded into all forms owned by that entity."/>
                  </span>
                </fieldset>
              </li>
              <li class="clear" id="prop_range">
                <fieldset class="range">
                  <legend> Range </legend>
                  <div style="padding-left: 2px"> <span>
                    <label for="element_range_min" class="desc">Min</label>
                    <input type="text" value="" class="text" name="element_range_min" id="element_range_min">
                    </span> <span>
                    <label for="element_range_max" class="desc">Max</label>
                    <input type="text" value="" class="text" name="element_range_max" id="element_range_max">
                    </span> <span>
                    <label for="element_range_limit_by" class="desc">Limit By <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can limit the amount of characters typed to be between certain characters or words, or between certain values in the case of number field. Leave the value blank or 0 if you don't want to set any limit."/></label>
                    <select class="select" name="element_range_limit_by" id="element_range_limit_by">
                      <option value="c">Characters</option>
                      <option value="w">Words</option>
                    </select>
                    <select class="select" name="element_range_number_limit_by" id="element_range_number_limit_by">
                      <option value="v">Value</option>
                      <option value="d">Digits</option>
                    </select>
                    </span> </div>
                </fieldset>
              </li>
              <li class="clear" id="prop_number_advance_options">
                <fieldset class="choices">
                  <legend> Advanced Options </legend>
                  <span>
                  <input id="prop_number_enable_quantity" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="prop_number_enable_quantity">Enable as Quantity field</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enable this option if your form has payment enabled and need to use quantity field to calculate the total price. Select the target field for the calculation from the dropdown list. Target field type must be one of the following: Multiple Choice, Drop Down, Checkboxes, Price."/>
                  <div id="prop_number_quantity_link_div" style="display: none"> Calculate with this field: <br />
                    <select class="select large" id="prop_number_quantity_link" name="prop_number_quantity_link" style="width: 95%" autocomplete="off">
                      <option value=""> -- No Supported Fields Available --</option>
                    </select>
                  </div>
                  </span>
                </fieldset>
              </li>
              <li class="clear" id="prop_default_value">
                <label class="desc" for="element_default"> Default Value <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="By setting this value, the field will be pre-populated with the text you enter."/> </label>
                <input id="element_default_value" class="text large" name="element_default_value" value="" type="text">
              </li>
              <li class="clear" id="prop_default_phone">
                <label class="desc" for="element_default_phone"> Default Value <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="By setting this value, the field will be pre-populated with the text you enter."/> </label>
                <input id="element_default_phone1" class="text" size="3" maxlength="3" name="element_default_phone1" value="" type="text">
                -
                <input id="element_default_phone2" class="text" size="3" maxlength="3" name="element_default_phone2" value="" type="text">
                -
                <input id="element_default_phone3" class="text" size="4" maxlength="4" name="element_default_phone3" value="" type="text">
              </li>
              <li class="clear" id="prop_default_date">
                <label class="desc" for="element_default_date"> Default Date <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="By setting this value, the date will be pre-populated with the date you enter. Use the format ##/##/#### or any English date words, such as 'today', 'tomorrow', 'last friday', '+1 week', 'last day of next month', '3 days ago', 'monday next week'"/> </label>
                <input id="element_default_date" class="text large" name="element_default_date" value="" type="text">
              </li>
              <li class="clear" id="prop_default_value_textarea">
                <label class="desc" for="element_default_textarea"> Default Value <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="By setting this value, the field will be pre-populated with the text you enter."/> </label>
                <textarea class="textarea" rows="10" cols="50" id="element_default_value_textarea" name="element_default_value_textarea"></textarea>
              </li>
              <li class="clear" id="prop_default_country">
                <label class="desc" for="fieldaddress_default"> Default Country <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="By setting this value, the country field will be pre-populated with the selection you make."/> </label>
                <select class="select" id="element_countries" name="element_countries">
                  <option value=""></option>
                  <optgroup label="North America">
                  <option value="Antigua and Barbuda">Antigua and Barbuda</option>
                  <option value="Bahamas">Bahamas</option>
                  <option value="Barbados">Barbados</option>
                  <option value="Belize">Belize</option>
                  <option value="Canada">Canada</option>
                  <option value="Costa Rica">Costa Rica</option>
                  <option value="Cuba">Cuba</option>
                  <option value="Dominica">Dominica</option>
                  <option value="Dominican Republic">Dominican Republic</option>
                  <option value="El Salvador">El Salvador</option>
                  <option value="Grenada">Grenada</option>
                  <option value="Guatemala">Guatemala</option>
                  <option value="Haiti">Haiti</option>
                  <option value="Honduras">Honduras</option>
                  <option value="Jamaica">Jamaica</option>
                  <option value="Mexico">Mexico</option>
                  <option value="Nicaragua">Nicaragua</option>
                  <option value="Panama">Panama</option>
                  <option value="Puerto Rico">Puerto Rico</option>
                  <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
                  <option value="Saint Lucia">Saint Lucia</option>
                  <option value="Saint Vincent and the Grenadines">Saint Vincent and the Grenadines</option>
                  <option value="Trinidad and Tobago">Trinidad and Tobago</option>
                  <option value="United States">United States</option>
                  </optgroup>
                  <optgroup label="South America">
                  <option value="Argentina">Argentina</option>
                  <option value="Bolivia">Bolivia</option>
                  <option value="Brazil">Brazil</option>
                  <option value="Chile">Chile</option>
                  <option value="Columbia">Columbia</option>
                  <option value="Ecuador">Ecuador</option>
                  <option value="Guyana">Guyana</option>
                  <option value="Paraguay">Paraguay</option>
                  <option value="Peru">Peru</option>
                  <option value="Suriname">Suriname</option>
                  <option value="Uruguay">Uruguay</option>
                  <option value="Venezuela">Venezuela</option>
                  </optgroup>
                  <optgroup label="Europe">
                  <option value="Albania">Albania</option>
                  <option value="Andorra">Andorra</option>
                  <option value="Armenia">Armenia</option>
                  <option value="Austria">Austria</option>
                  <option value="Azerbaijan">Azerbaijan</option>
                  <option value="Belarus">Belarus</option>
                  <option value="Belgium">Belgium</option>
                  <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
                  <option value="Bulgaria">Bulgaria</option>
                  <option value="Croatia">Croatia</option>
                  <option value="Cyprus">Cyprus</option>
                  <option value="Czech Republic">Czech Republic</option>
                  <option value="Denmark">Denmark</option>
                  <option value="Estonia">Estonia</option>
                  <option value="Finland">Finland</option>
                  <option value="France">France</option>
                  <option value="Georgia">Georgia</option>
                  <option value="Germany">Germany</option>
                  <option value="Greece">Greece</option>
                  <option value="Guernsey">Guernsey</option>
                  <option value="Hungary">Hungary</option>
                  <option value="Iceland">Iceland</option>
                  <option value="Ireland">Ireland</option>
                  <option value="Italy">Italy</option>
                  <option value="Latvia">Latvia</option>
                  <option value="Liechtenstein">Liechtenstein</option>
                  <option value="Lithuania">Lithuania</option>
                  <option value="Luxembourg">Luxembourg</option>
                  <option value="Macedonia">Macedonia</option>
                  <option value="Malta">Malta</option>
                  <option value="Moldova">Moldova</option>
                  <option value="Monaco">Monaco</option>
                  <option value="Montenegro">Montenegro</option>
                  <option value="Netherlands">Netherlands</option>
                  <option value="Norway">Norway</option>
                  <option value="Poland">Poland</option>
                  <option value="Portugal">Portugal</option>
                  <option value="Romania">Romania</option>
                  <option value="San Marino">San Marino</option>
                  <option value="Serbia">Serbia</option>
                  <option value="Slovakia">Slovakia</option>
                  <option value="Slovenia">Slovenia</option>
                  <option value="Spain">Spain</option>
                  <option value="Sweden">Sweden</option>
                  <option value="Switzerland">Switzerland</option>
                  <option value="Ukraine">Ukraine</option>
                  <option value="United Kingdom">United Kingdom</option>
                  <option value="Vatican City">Vatican City</option>
                  </optgroup>
                  <optgroup label="Asia">
                  <option value="Afghanistan">Afghanistan</option>
                  <option value="Bahrain">Bahrain</option>
                  <option value="Bangladesh">Bangladesh</option>
                  <option value="Bhutan">Bhutan</option>
                  <option value="Brunei Darussalam">Brunei Darussalam</option>
                  <option value="Myanmar">Myanmar</option>
                  <option value="Cambodia">Cambodia</option>
                  <option value="China">China</option>
                  <option value="East Timor">East Timor</option>
                  <option value="Hong Kong">Hong Kong</option>
                  <option value="India">India</option>
                  <option value="Indonesia">Indonesia</option>
                  <option value="Iran">Iran</option>
                  <option value="Iraq">Iraq</option>
                  <option value="Israel">Israel</option>
                  <option value="Japan">Japan</option>
                  <option value="Jordan">Jordan</option>
                  <option value="Kazakhstan">Kazakhstan</option>
                  <option value="North Korea">North Korea</option>
                  <option value="South Korea">South Korea</option>
                  <option value="Kuwait">Kuwait</option>
                  <option value="Kyrgyzstan">Kyrgyzstan</option>
                  <option value="Laos">Laos</option>
                  <option value="Lebanon">Lebanon</option>
                  <option value="Malaysia">Malaysia</option>
                  <option value="Maldives">Maldives</option>
                  <option value="Mongolia">Mongolia</option>
                  <option value="Nepal">Nepal</option>
                  <option value="Oman">Oman</option>
                  <option value="Pakistan">Pakistan</option>
                  <option value="Palestine">Palestine</option>
                  <option value="Philippines">Philippines</option>
                  <option value="Qatar">Qatar</option>
                  <option value="Russia">Russia</option>
                  <option value="Saudi Arabia">Saudi Arabia</option>
                  <option value="Singapore">Singapore</option>
                  <option value="Sri Lanka">Sri Lanka</option>
                  <option value="Syria">Syria</option>
                  <option value="Taiwan">Taiwan</option>
                  <option value="Tajikistan">Tajikistan</option>
                  <option value="Thailand">Thailand</option>
                  <option value="Turkey">Turkey</option>
                  <option value="Turkmenistan">Turkmenistan</option>
                  <option value="United Arab Emirates">United Arab Emirates</option>
                  <option value="Uzbekistan">Uzbekistan</option>
                  <option value="Vietnam">Vietnam</option>
                  <option value="Yemen">Yemen</option>
                  </optgroup>
                  <optgroup label="Oceania">
                  <option value="Australia">Australia</option>
                  <option value="Fiji">Fiji</option>
                  <option value="Kiribati">Kiribati</option>
                  <option value="Marshall Islands">Marshall Islands</option>
                  <option value="Micronesia">Micronesia</option>
                  <option value="Nauru">Nauru</option>
                  <option value="New Zealand">New Zealand</option>
                  <option value="Palau">Palau</option>
                  <option value="Papua New Guinea">Papua New Guinea</option>
                  <option value="Samoa">Samoa</option>
                  <option value="Solomon Islands">Solomon Islands</option>
                  <option value="Tonga">Tonga</option>
                  <option value="Tuvalu">Tuvalu</option>
                  <option value="Vanuatu">Vanuatu</option>
                  </optgroup>
                  <optgroup label="Africa">
                  <option value="Algeria">Algeria</option>
                  <option value="Angola">Angola</option>
                  <option value="Benin">Benin</option>
                  <option value="Botswana">Botswana</option>
                  <option value="Burkina Faso">Burkina Faso</option>
                  <option value="Burundi">Burundi</option>
                  <option value="Cameroon">Cameroon</option>
                  <option value="Cape Verde">Cape Verde</option>
                  <option value="Central African Republic">Central African Republic</option>
                  <option value="Chad">Chad</option>
                  <option value="Comoros">Comoros</option>
                  <option value="Congo">Congo</option>
                  <option value="Djibouti">Djibouti</option>
                  <option value="Egypt">Egypt</option>
                  <option value="Equatorial Guinea">Equatorial Guinea</option>
                  <option value="Eritrea">Eritrea</option>
                  <option value="Ethiopia">Ethiopia</option>
                  <option value="Gabon">Gabon</option>
                  <option value="Gambia">Gambia</option>
                  <option value="Ghana">Ghana</option>
                  <option value="Guinea">Guinea</option>
                  <option value="Guinea-Bissau">Guinea-Bissau</option>
                  <option value="Côte d'Ivoire">Côte d'Ivoire</option>
                  <option value="Kenya">Kenya</option>
                  <option value="Lesotho">Lesotho</option>
                  <option value="Liberia">Liberia</option>
                  <option value="Libya">Libya</option>
                  <option value="Madagascar">Madagascar</option>
                  <option value="Malawi">Malawi</option>
                  <option value="Mali">Mali</option>
                  <option value="Mauritania">Mauritania</option>
                  <option value="Mauritius">Mauritius</option>
                  <option value="Morocco">Morocco</option>
                  <option value="Mozambique">Mozambique</option>
                  <option value="Namibia">Namibia</option>
                  <option value="Niger">Niger</option>
                  <option value="Nigeria">Nigeria</option>
                  <option value="Rwanda">Rwanda</option>
                  <option value="Sao Tome and Principe">Sao Tome and Principe</option>
                  <option value="Senegal">Senegal</option>
                  <option value="Seychelles">Seychelles</option>
                  <option value="Sierra Leone">Sierra Leone</option>
                  <option value="Somalia">Somalia</option>
                  <option value="South Africa">South Africa</option>
                  <option value="Sudan">Sudan</option>
                  <option value="Swaziland">Swaziland</option>
                  <option value="United Republic of Tanzania">Tanzania</option>
                  <option value="Togo">Togo</option>
                  <option value="Tunisia">Tunisia</option>
                  <option value="Uganda">Uganda</option>
                  <option value="Zambia">Zambia</option>
                  <option value="Zimbabwe">Zimbabwe</option>
                  </optgroup>
                </select>
              </li>
              <li class="clear" id="prop_phone_default">
                <label class="desc" for="element_phone_default1"> Default Value <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="By setting this value, the field will be pre-populated with the text you enter."/> </label>
                (
                <input id="element_phone_default1" class="text" size="3" name="text" value="" tabindex="11" maxlength="3" onkeyup="set_properties(JJ('#element_phone_default1').val().toString()+JJ('#element_phone_default2').val().toString()+JJ('#element_phone_default3').val().toString(), 'default_value')" onblur="set_properties(JJ('#element_phone_default1').val().toString()+JJ('#element_phone_default2').val().toString()+JJ('#element_phone_default3').val().toString(), 'default_value')" type="text">
                )
                <input id="element_phone_default2" class="text" size="3" name="text" value="" tabindex="11" maxlength="3" onkeyup="set_properties(JJ('#element_phone_default1').val().toString()+JJ('#element_phone_default2').val().toString()+JJ('#element_phone_default3').val().toString(), 'default_value')" onblur="set_properties(JJ('#element_phone_default1').val().toString()+JJ('#element_phone_default2').val().toString()+JJ('#element_phone_default3').val().toString(), 'default_value')" type="text">
                -
                <input id="element_phone_default3" class="text" size="4" name="text" value="" tabindex="11" maxlength="4" onkeyup="set_properties(JJ('#element_phone_default1').val().toString()+JJ('#element_phone_default2').val().toString()+JJ('#element_phone_default3').val().toString(), 'default_value')" onblur="set_properties(JJ('#element_phone_default1').val().toString()+JJ('#element_phone_default2').val().toString()+JJ('#element_phone_default3').val().toString(), 'default_value')" type="text">
              </li>
              <li class="clear" id="prop_default_casecade_form" style="display:none;">
                <label>Choose Form to Cascade</label>
                <select class="element select" id="element_default_casecade_form" name="element_default_casecade_form" style="width: 300px;margin-right: 10px">
                  <?php
						if(!empty($form_list_array)){
							foreach ($form_list_array as $value) {
								echo "<option value=\"{$value['form_id']}\">{$value['form_name']}</option>";
							}
						}
					?>
                </select>
              </li>
              <li class="clear" id="prop_cascade_form_invisible" style="display: none;">
              	<input type="checkbox" id="element_cascade_form_invisible" />
                <label class="choice" for="element_cascade_form_invisible"> Make this form invisible in the user portal <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This form will be invisible in the user portal but still able to use."/> </label>
              </li>
              <li class="clear" id="prop_instructions">
                <label class="desc" for="element_instructions"> Guidelines for User <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This text will be displayed to your users while they're filling out particular field."/> </label>
                <textarea class="textarea" rows="10" cols="50" id="element_instructions"></textarea>
              </li>
              <li class="clear" id="prop_field_notes">
                <input type="checkbox" id="element_field_notes" />
                <label class="choice" for="element_field_notes"> Enable Field Notes <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enable Field Notes"/> </label>
              </li>
			  <li class="clear" id="prop_status_indicator">
                <input type="checkbox" id="element_status_indicator" />
                <label class="choice" for="element_status_indicator"> Enable Status Indicator <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enable Status Indicator"/> </label>
              </li>
              <li class="clear" id="prop_enhanced_checkbox">
                <input type="checkbox" id="element_enhanced_checkbox" />
                <label class="choice" for="element_enhanced_checkbox"> Enable Enhanced Checkbox <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enable Enhanced Checkbox"/> </label>
              </li>
              <li class="clear" id="prop_enhanced_multiple_choice">
                <input type="checkbox" id="element_enhanced_multiple_choice" />
                <label class="choice" for="element_enhanced_multiple_choice"> Enable Enhanced Multiple Choice <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enable Enhanced Multiple Choice"/> </label>
              </li>
			  <li class="clear" id="prop_rich_text">
                <input type="checkbox" id="element_rich_text" />
                <label class="choice" for="element_rich_text"> Enable Rich Text <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enable Rich Text"/> </label>
              </li>
              <li class="clear" id="prop_policymachine_code">
                <label class="desc" for="element_policymachine_code"> Custom Template Code <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is advanced Policy Mahine option. You can add a custom Template variable to the parent element of the field. This is useful if you would like to generate Template reports using IT Audit Machine form data. These custom Template codes will not appear live in the form builder, only on the live form."/> </label>
                <input id="element_policymachine_code" class="text" name="element_policymachine_code" value=""  maxlength="50" type="text">
              </li>
              <li id="prop_machine_code">
                <label class="desc" for="element_machine_code"> Template Code <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is advanced Template option. You can add a custom Template variable to the parent element of the field. This is useful if you would like to generate Template reports using IT Audit Machine form data. These custom Template codes will not appear live in the form builder, only on the live form."/> </label>
                <input id="element_machine_code" class="text large" name="element_machine_code" value="" maxlength="255" type="text">
              </li>
              <li id="embed_machine_code">
              	<fieldset class="">
              		<legend> Field Embed Options </legend>
	              	<label class="desc">User Type </label>
	              	<select class="element select full" id="ec_code_type_field_user_properties" name="ec_code_type_field_user_properties">
						<option value="">Select an Option</option>
						<option value="admin">Admin</option>
						<option value="portal">Portal</option>
					</select>

	                <label class="desc">Code Type </label>
	                <select class="element select full" id="ec_code_type_field_properties" name="ec_code_type_field_properties">
						<option value="">Select an Option</option>
						<!-- <option value="javascript">Javascript Code (Recommended)</option> -->
						<option value="iframe">Iframe Code</option>
						<option value="simple_link">Simple Link</option>
						<option value="popup_link">Popup Link</option>
					</select>
					<label style="display: none;" class="desc" id="embed_field_main_title"></label>
					<textarea id="embed_field_textarea_content" class="textarea" style="display: none;" rows="10"></textarea>
				</fieldset>
              </li>
              <!--options for page break-->
              <li class="clear" id="prop_page_break_background" style="display: list-item;margin-top: 15px;">
                <fieldset class="">
                  <legend> Background Options </legend>
                  <label class="desc" for="element_page_break_bg_color">Background Color<img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Field Type determines the background color of page."/> </label>
                	<input id="element_page_break_bg_color" class="text" style="width: 95%" type="text" />
                </fieldset>
              </li>
              <li class="clear" id="prop_page_break_button" style="margin-top: 15px;margin-bottom: 10px">
                <fieldset style="padding-top: 15px">
                  <legend>Page Submit Buttons</legend>
                  <div class="left" style="padding-bottom: 5px">
                    <input id="prop_submit_use_text" name="submit_use_image" class="radio" value="0" type="radio">
                    <label class="choice" for="prop_submit_use_text">Use Text Button</label>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is the default and recommended option. All buttons will use simple text. You can change the text being used on each page submit/back button."/> </div>
                  <div class="left" style="padding-left: 5px;padding-bottom: 5px">
                    <input id="prop_submit_use_image" name="submit_use_image" class="radio" value="1" type="radio">
                    <label class="choice" for="prop_submit_use_image">Use Image Button</label>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Select this option if you prefer to use your own submit/back image buttons. Make sure to enter the full URL address to your images."/> </div>
                  <div id="div_submit_use_text" class="left" style="padding-left: 8px;padding-bottom: 10px;width: 92%">
                    <label class="desc" for="submit_primary_text">Submit Button</label>
                    <input id="submit_primary_text" class="text large" name="submit_primary_text" value="" type="text">
                    <label id="lbl_submit_secondary_text" class="desc" for="submit_secondary_text" style="margin-top: 10px">Back Button</label>
                    <input id="submit_secondary_text" class="text large" name="submit_secondary_text" value="" type="text">
                  </div>
                  <div id="div_submit_use_image" class="left" style="padding-left: 8px;padding-bottom: 10px;width: 92%; display: none">
                    <label class="desc" for="submit_primary_img">Submit Button. Image URL:</label>
                    <input id="submit_primary_img" class="text large" name="submit_primary_img" value="http://" type="text">
                    <label id="lbl_submit_secondary_img" class="desc" for="submit_secondary_img" style="margin-top: 10px">Back Button. Image URL:</label>
                    <input id="submit_secondary_img" class="text large" name="submit_secondary_img" value="http://" type="text">
                  </div>
                </fieldset>
              </li>
            </ul>
            <div class="bullet_bar_bottom"> <img style="float: left" src="images/bullet_green.png" /> <img style="float: right" src="images/bullet_green.png"/> </div>
          </div>
        </form>
      </div>
      <!-- end field properties pane -->
    </div>
    <div id="tab_form_properties" style="display: none">
      <div id="form_properties_pane" class="box">
        <div id="form_properties_holder">
          <!-- <div class="bullet_bar_top"> <img style="float: left" src="images/bullet_green.png" /> <img style="float: right" src="images/bullet_green.png"/> </div> -->

          <!--  start form properties pane -->
          <form id="form_properties" action="" onsubmit="return false;">
            <ul id="all_form_properties">
              <li class="form_prop">
                <label class="desc" for="form_title">Form Title <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="The title of your form displayed to the user when they see your form."/> </label>
                <input id="form_title" name="form_title" class="text large" value="" tabindex="1"  type="text">
              </li>
              <li class="form_prop">
                <label class="desc" for="form_description">Description <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This will appear directly below the form name. Useful for displaying a short description or any instructions, notes, guidelines."/> </label>
                <textarea class="textarea small" rows="10" cols="50" id="form_description" tabindex="2"></textarea>
              </li>
              <li id="form_prop_confirmation" class="form_prop">
                <fieldset>
                  <legend>Submission Confirmation</legend>
                  <div class="left" style="padding-bottom: 5px">
                    <input id="form_success_message_option" name="confirmation" class="radio" value="" checked="checked" type="radio">
                    <label class="choice" for="form_success_message_option">Show Text</label>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This message will be displayed after your users have successfully submitted an entry. <br/><br/>You can enter any HTML codes, Javascript codes or Template Variables as well."/> </div>
                  <div class="left" style="padding-left: 15px;padding-bottom: 5px">
                    <input id="form_redirect_option" name="confirmation" class="radio" value="" type="radio">
                    <label class="choice" for="form_redirect_option">Redirect to Web Site</label>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="After your users have successfully submitted an entry, you can redirect them to another
								website/URL of your choice.<br/><br/>You can insert Template Variables into the URL to pass form data."/> </div>
                  <textarea class="textarea" rows="10" cols="50" id="form_success_message" tabindex="9"></textarea>
                  <input id="form_redirect_url" class="text hide" name="form_redirect_url" value="http://" type="text">
                </fieldset>
              </li>
			  <!--<li id="form_prop_confirmation" class="form_prop">
                <fieldset>
                  <legend>Status Indicators</legend>
				  <div class="left" style="width:25%;">
                    <img src="images/Circle_Gray.png" title="Gray" style="vertical-align: middle; width: 15px;">
                    <input type="text" value="0" autocomplete="off" class="text" id="circle_gray" style="width: 30px; float: right; vertical-align: middle;margin-right: 8px;"></div>
                  <div class="left" style="width:25%;">
                    <img src="images/Circle_Red.png" style="vertical-align: middle; width: 15px;">
                    <input type="text" value="0" autocomplete="off" class="text" id="circle_red" style="width: 30px; float: right; vertical-align: middle;margin-right: 8px;"></div>
				  <div class="left" style="width:25%;">
                    <img src="images/Circle_Yellow.png" style="vertical-align: middle; width: 15px;">
                    <input type="text" value="0" autocomplete="off" class="text" id="circle_yellow" style="width: 30px; float: right; vertical-align: middle;margin-right: 8px;"></div>
				  <div class="left" style="width:25%;">
                    <img src="images/Circle_Green.png" style="vertical-align: middle; width: 15px;">
                    <input type="text" value="0" autocomplete="off" class="text" id="circle_green" style="width: 30px; float: right; vertical-align: middle;margin-right: 8px;"></div>
                </fieldset>
              </li>-->
              <li id="form_prop_toggle" class="form_prop">
                <div style="text-align: right"> <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="All settings below this point are optional. You can leave it as it is if you don't need it."/> <a href=""  id="form_prop_toggle_a">show more options</a> <img style="vertical-align: top;cursor: pointer" src="images/icons/resultset_next.gif" id="form_prop_toggle_img"/> </div>
              </li>
              <li id="form_prop_language" class="leftCol advanced_prop form_prop">
                <label class="desc"> Language <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can choose the language being used to display your form messages."/> </label>
                <div>
                  <select autocomplete="off" id="form_language" class="select large">
                    <option value="bulgarian">Bulgarian</option>
                    <option value="chinese">Chinese (Traditional)</option>
                    <option value="chinese_simplified">Chinese (Simplified)</option>
                    <option value="danish">Danish</option>
                    <option value="dutch">Dutch</option>
                    <option value="english">English</option>
                    <option value="estonian">Estonian</option>
                    <option value="finnish">Finnish</option>
                    <option value="french">French</option>
                    <option value="german">German</option>
                    <option value="greek">Greek</option>
                    <option value="hungarian">Hungarian</option>
                    <option value="indonesia">Indonesia</option>
                    <option value="italian">Italian</option>
                    <option value="japanese">Japanese</option>
                    <option value="norwegian">Norwegian</option>
                    <option value="polish">Polish</option>
                    <option value="portuguese">Portuguese</option>
                    <option value="romanian">Romanian</option>
                    <option value="russian">Russian</option>
                    <option value="slovak">Slovak</option>
                    <option value="spanish">Spanish</option>
                    <option value="swedish">Swedish</option>
                  </select>
                </div>
              </li>
              <li id="form_prop_label_alignment" class="rightCol advanced_prop form_prop">
                <label for="form_label_alignment" class="desc">Label Alignment <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Set the field label placement"/> </label>
                <div>
                  <select autocomplete="off" id="form_label_alignment" class="select large">
                    <option value="top_label">Top Aligned</option>
                    <option value="left_label">Left Aligned</option>
                    <option value="right_label">Right Aligned</option>
                  </select>
                </div>
              </li>
              <li id="form_prop_processing" class="clear advanced_prop form_prop">
                <fieldset>
                  <legend>Processing Options</legend>
                  <span>
                  <input id="form_resume" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="form_resume">Allow Clients to Save and Resume Later</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Checking this will display additional link at the bottom of your form which would allow your clients to save their progress and resume later. This option only available if your form has at least two pages (has one or more Page Break field)."/> </span><br>
                  <span>
                  <input id="form_review" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="form_review">Show Review Page Before Submitting</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If enabled, your clients will be prompted to a preview page that lets them double check their entries before submitting the form."/> </span><br>
                </fieldset>
              </li>
              <li class="clear advanced_prop form_prop" id="form_prop_review" style="display: none;zoom: 1">
                <fieldset style="padding-top: 15px">
                  <legend>Review Page Options</legend>
                  <label class="desc" for="form_review_title"> Review Page Title <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enter the title to be displayed on the review page."/> </label>
                  <input id="form_review_title" class="text large" name="form_review_title" value="" maxlength="255" type="text">
                  <label class="desc" for="form_review_description"> Review Page Description <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Enter some brief description to be displayed on the review page."/> </label>
                  <textarea class="textarea" rows="10" cols="50" id="form_review_description" style="height: 2.5em"></textarea>
                  <div style="border-bottom: 1px dashed green; height: 15px;margin-right: 10px"></div>
                  <div class="left" style="padding-bottom: 5px;margin-top: 12px">
                    <input id="form_review_use_text" name="form_review_option" class="radio" value="0" type="radio">
                    <label class="choice" for="form_review_use_text">Use Text Button</label>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="This is the default and recommended option. All buttons on review page will use simple text."/> </div>
                  <div class="left" style="padding-left: 5px;padding-bottom: 5px;margin-top: 12px">
                    <input id="form_review_use_image" name="form_review_option" class="radio" value="1" type="radio">
                    <label class="choice" for="form_review_use_image">Use Image Button</label>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Select this option if you prefer to use your own submit/back image buttons. Make sure to enter the full URL address to your images."/> </div>
                  <div id="div_review_use_text" class="left" style="padding-left: 8px;padding-bottom: 10px;width: 92%">
                    <label class="desc" for="review_primary_text">Submit Button</label>
                    <input id="review_primary_text" class="text large" name="review_primary_text" value="" type="text">
                    <label id="lbl_review_secondary_text" class="desc" for="review_secondary_text" style="margin-top: 3px">Back Button</label>
                    <input id="review_secondary_text" class="text large" name="review_secondary_text" value="" type="text">
                  </div>
                  <div id="div_review_use_image" class="left" style="padding-left: 8px;padding-bottom: 10px;width: 92%;display: none">
                    <label class="desc" for="review_primary_img">Submit Button. Image URL:</label>
                    <input id="review_primary_img" class="text large" name="review_primary_img" value="http://" type="text">
                    <label id="lbl_review_secondary_img" class="desc" for="review_secondary_img" style="margin-top: 3px">Back Button. Image URL:</label>
                    <input id="review_secondary_img" class="text large" name="review_secondary_img" value="http://" type="text">
                  </div>
                </fieldset>
              </li>
              <li id="form_prop_protection" class="advanced_prop form_prop">
                <fieldset>
                  <legend>Protection &amp; Limit</legend>
                  <span>
                  <input id="form_password_option" class="checkbox" value=""  type="checkbox">
                  <label class="choice" for="form_password_option">Turn On Password Protection</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If enabled, all users accessing the public form will then be required to type in the password to access the form. Your form is password protected."/>
                  <div id="form_password" style="display: none"> <img src="images/navigation/005499/16x16/My_account.png" alt="Password : " style="vertical-align: middle">
                    <input id="form_password_data" style="width: 50%" class="text" value="" size="25"  type="password" autocomplete="off">
                  </div>
                  </span> <span style="clear: both;display: block">
                  <input id="form_captcha" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="form_captcha">Turn On Spam Protection (CAPTCHA)</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If enabled, an image with random words will be generated and users will be required to enter the correct words to be able submitting your form. This is useful to prevent abuse from bots or automated programs usually written to generate spam."/>
                  <div id="form_captcha_type_option" style="display: block">
                    <label class="choice" for="form_captcha_type">Type: </label>
                    <select class="select" id="form_captcha_type" name="form_captcha_type" autocomplete="off">
                      <?php
                      if (!empty($la_settings['recaptcha_public']) && !empty($la_settings['recaptcha_secret'])) { ?>
                        <option value="r">reCAPTCHA (Hardest)</option>
                        <?php } ?>
                      <option value="i">Simple Image (Medium)</option>
                      <option value="t">Simple Text (Easiest)</option>
                    </select>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can select the difficulty level of the spam protection.
											 <br/>
											 <br/>
											reCAPTCHA : Display an image with distorted words. An audio also included. This is the most secure but also the hardest to read. Some people might find this annoying.
											 <br/>
											 <br/>
											Simple Image : Display an image with a clear and sharp words. Most people will find this easy to read.
											 <br/>
											 <br/>
											Simple Text : Display a text (not an image) which contain simple question to solve."/> </div>
                  </span> <span style="clear: both;display: block">
                  <input id="form_unique_ip" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="form_unique_ip">Limit One Entry Per IP</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Use this to prevent clients from filling out your form more than once. This is done by comparing client's IP Address."/> </span> <span style="clear: both;display: block">
                  <input id="form_limit_option" class="checkbox" value="" type="checkbox">
                  <label class="choice" for="form_limit_option">Limit Submission</label>
                  <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="The form will be turned off after reaching the number of entries defined here."/>
                  <div id="form_limit_div" style="display: none"> <img src="images/icons/flag_red.png" alt="Maximum accepted entries : " style="vertical-align: middle"> Maximum accepted entries:
                    <input id="form_limit" style="width: 20%" class="text" value="" maxlength="255" type="text">
                  </div>
                  </span>
                </fieldset>
              </li>
              <li id="form_prop_scheduling" class="clear advanced_prop form_prop">
                <fieldset>
                  <legend>Form Availability</legend>
                  <div style="padding-bottom: 10px">
                    <input id="form_schedule_enable" class="checkbox" value="" style="float: left"  type="checkbox">
                    <label class="choice" style="float: left;margin-left: 5px;margin-right:3px;line-height: 1.7" for="form_schedule_enable">Enable Form Availability</label>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If you would like to schedule your form to become active at certain period of time only, enable this option."/>
                  </div>
                  <div id="form_prop_scheduling_start" style="display: none">
                    <label class="desc">Only Accept Submission From Date: </label>
                    <span>
                    <input type="text" value="" maxlength="2" size="2" style="width: 1.5em;" class="text" name="scheduling_start_mm" id="scheduling_start_mm">
                    <label for="scheduling_start_mm">MM</label>
                    </span> <span>
                    <input type="text" value="" maxlength="2" size="2" style="width: 1.5em;" class="text" name="scheduling_start_dd" id="scheduling_start_dd">
                    <label for="scheduling_start_dd">DD</label>
                    </span> <span>
                    <input type="text" value="" maxlength="4" size="4" style="width: 3em;" class="text" name="scheduling_start_yyyy" id="scheduling_start_yyyy">
                    <label for="scheduling_start_yyyy">YYYY</label>
                    </span> <span id="scheduling_cal_start">
                    <input type="hidden" value="" maxlength="4" size="4" style="width: 3em;" class="text" name="linked_picker_scheduling_start" id="linked_picker_scheduling_start">
                    <div style="display: none"><img id="scheduling_start_pick_img" alt="Pick date." src="images/icons/calendar.png" class="trigger" style="margin-top: 3px; cursor: pointer" /></div>
                    </span> <span>
                    <select name="scheduling_start_hour" id="scheduling_start_hour" class="select">
                      <option value="01">1</option>
                      <option value="02">2</option>
                      <option value="03">3</option>
                      <option value="04">4</option>
                      <option value="05">5</option>
                      <option value="06">6</option>
                      <option value="07">7</option>
                      <option value="08">8</option>
                      <option value="09">9</option>
                      <option value="10">10</option>
                      <option value="11">11</option>
                      <option value="12">12</option>
                    </select>
                    <label for="scheduling_start_hour">HH</label>
                    </span> <span>
                    <select name="scheduling_start_minute" id="scheduling_start_minute" class="select">
                      <option value="00">00</option>
                      <option value="15">15</option>
                      <option value="30">30</option>
                      <option value="45">45</option>
                    </select>
                    <label for="scheduling_start_minute">MM</label>
                    </span> <span>
                    <select name="scheduling_start_ampm" id="scheduling_start_ampm" class="select">
                      <option value="am">AM</option>
                      <option value="pm">PM</option>
                    </select>
                    <label for="scheduling_start_ampm">AM/PM</label>
                    </span> </div>
                  <div id="form_prop_scheduling_end" style="display: none">
                    <label class="desc">Until Date:</label>
                    <span>
                    <input type="text" value="" maxlength="2" size="2" style="width: 1.5em;" class="text" name="scheduling_end_mm" id="scheduling_end_mm">
                    <label for="scheduling_end_mm">MM</label>
                    </span> <span>
                    <input type="text" value="" maxlength="2" size="2" style="width: 1.5em;" class="text" name="scheduling_end_dd" id="scheduling_end_dd">
                    <label for="scheduling_end_dd">DD</label>
                    </span> <span>
                    <input type="text" value="" maxlength="4" size="4" style="width: 3em;" class="text" name="scheduling_end_yyyy" id="scheduling_end_yyyy">
                    <label for="scheduling_end_yyyy">YYYY</label>
                    </span> <span id="scheduling_cal_end">
                    <input type="hidden" value="" maxlength="4" size="4" style="width: 3em;" class="text" name="linked_picker_scheduling_end" id="linked_picker_scheduling_end">
                    <div style="display: none"><img id="scheduling_end_pick_img" alt="Pick date." src="images/icons/calendar.png" class="trigger" style="margin-top: 3px; cursor: pointer" /></div>
                    </span> <span>
                    <select name="scheduling_end_hour" id="scheduling_end_hour" class="select">
                      <option value="01">1</option>
                      <option value="02">2</option>
                      <option value="03">3</option>
                      <option value="04">4</option>
                      <option value="05">5</option>
                      <option value="06">6</option>
                      <option value="07">7</option>
                      <option value="08">8</option>
                      <option value="09">9</option>
                      <option value="10">10</option>
                      <option value="11">11</option>
                      <option value="12">12</option>
                    </select>
                    <label for="scheduling_end_hour">HH</label>
                    </span> <span>
                    <select name="scheduling_end_minute" id="scheduling_end_minute" class="select">
                      <option value="00">00</option>
                      <option value="15">15</option>
                      <option value="30">30</option>
                      <option value="45">45</option>
                    </select>
                    <label for="scheduling_end_minute">MM</label>
                    </span> <span>
                    <select name="scheduling_end_ampm" id="scheduling_end_ampm" class="select">
                      <option value="am">AM</option>
                      <option value="pm">PM</option>
                    </select>
                    <label for="scheduling_end_ampm">AM/PM</label>
                    </span> </div>
                </fieldset>
              </li>
              <?php if( $la_settings['enable_autocomplete'] == 1 ) { ?>
              <li class="clear advanced_prop form_prop">
                <fieldset>
                  <legend>Framework</legend>
                  <div style="padding-bottom: 5px">
                    <input id="framework_type" name="framework_type" class="radio" value="0" type="radio">
                    <label class="choice">Framework</label>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top;margin-right:20px" title="Info for section."/>

                    <input id="framework_type_group" name="framework_type" class="radio" value="1" type="radio">
                    <label class="choice">Framework Group</label>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Info for section."/>
                  </div>
                  <div style="padding-bottom: 5px" id="show_framework">
                    <label class="choice" style="float: left;margin-left: 5px;margin-right:3px;line-height: 1.7" for="form_framework">Select Framework</label>
                    <select name="form_framework" id="form_framework" class="select full">
                      <option value="0">Select Framework</option>
                    </select>
                  </div>
                  <div style="padding-bottom: 10px" id="show_framework_group">
                    <label class="choice" style="float: left;margin-left: 5px;margin-right:3px;line-height: 1.7" for="form_framework_group">Select Framework Group</label>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Info for section."/>
                    <select name="form_framework_group" id="form_framework_group" class="select full">
                      <option value="0">Select Framework</option>
                    </select>
                  </div>
                </fieldset>
              </li>
              <?php } ?>
              <li class="clear advanced_prop form_prop">
                <fieldset>
                  <legend>Template Options</legend>
                  <div style="padding-bottom: 10px">
                    <div style="width:100%;">
                      <input id="form_enable_template" class="checkbox" value="" style="float: left"  type="checkbox">
                      <label class="choice" style="float: left;margin-left: 5px;margin-right:3px;line-height: 1.7" for="form_enable_template" id="form_enable_template_label">Enable Uploading Templates</label>
                      <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="When enabled, you can upload template documents."/> 
                    </div>
                    <div style="padding-left: 20px;" id="form_template_upload_parent">
						<div style="margin:5px 0;">
						<label class="desc" style="float:left; margin-right:10px;">Upload Templates</label>
						<input type="button" id="upload-files" name="fileupload" value="Upload Files" style="background-color:#ccc;border-radius:0;padding:2px 20px;border:0;color:#666; cursor:pointer;" />
						<input id="form_upload_template" class="text large" value="" type="hidden" />
						</div>
						<div id="progressbox">
						<div id="progressbar"></div>
						<div id="statustxt">0%</div>
						</div>
						<div class="template-list-div"></div>
						<div id="optimizebox" style="display: none; color: #33BF8C;">
							<span>Optimizing the uploaded template document...</span>
						</div>
						<div style="color:red; display:none;" id="doc_loc_error_msg"></div>
                    </div>
                    <div style="width:100%; float:left;margin-top:10px;">
                      <input id="form_enable_template_wysiwyg" class="checkbox" value="" style="float: left"  type="checkbox">
                      <label class="choice" style="float: left;margin-left: 5px;margin-right:3px;line-height: 1.7" for="form_enable_template_wysiwyg" id="form_enable_template_wysiwyg_label">Enable Template Manager</label>
                      <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="When enabled, you can use one of the templates created in Template Management Settings."/>
                    </div>
                    <div style="padding-left: 20px;" id="form_template_wysiwyg_parent">
                      <label class="description" for="form_template_wysiwyg_id" style="margin-top: 2px"> Select a Template </label>
                      <select class="select" id="form_template_wysiwyg_id" name="form_template_wysiwyg_id" autocomplete="off" style="width: 243px;">
                          <option value=""></option>
                          <?php
                          $query = "SELECT * FROM " . LA_TABLE_PREFIX . "form_templates";
                          $sth = la_do_query($query, array(), $dbh);
                          while ($row = la_do_fetch_result($sth)) {
                              $selected = '';
                              if( $row['id'] == $template_id ) {
                                  $selected = 'selected';
                              }
                              echo "<option value=\"{$row['id']}\" {$selected}>{$row['name']}</option>";
                          }
                          ?>
                      </select>
                      <label id="template_preview"></label>
                    </div>
                  </div>
                </fieldset>
              </li>
              <li id="form_prop_advanced_option" class="clear advanced_prop form_prop">
                <fieldset>
                  <legend>Advanced Options</legend>
                  <div style="padding-bottom: 10px">
                    <input id="form_custom_script_enable" class="checkbox" value="1" style="float: left"  type="checkbox">
                    <label class="choice" style="float: left;margin-left: 5px;margin-right:3px;line-height: 1.7" for="form_custom_script_enable">Load Custom Javascript File</label>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="You can register your own custom javascript file to run inline with the form. Your script will be loaded each time the form is being displayed."/> </div>
                  <div id="form_custom_script_div" style="display: none; margin-left: 25px;margin-bottom: 10px">
                    <label class="desc" for="form_custom_script_url">Script URL:</label>
                    <input id="form_custom_script_url" name="form_custom_script_url" style="width: 90%" class="text" value=""  type="text">
                  </div>
                  <div style="padding-bottom: 10px">
                    <input id="form_enable_auto_mapping" class="checkbox" value="1" style="float: left"  type="checkbox">
                    <label class="choice" style="float: left;margin-left: 5px;margin-right:3px;line-height: 1.7" for="form_enable_auto_mapping">Enable Auto-Mapping</label>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="If enabled, field entries will be overwritten in other forms where the entity and element machine code are the same."/> </div>
                  <div style="padding-bottom: 10px">
                    <input id="form_private_form_check" class="checkbox" value="1" style="float: left"  type="checkbox">
                    <label class="choice" style="float: left;margin-left: 5px;margin-right:3px;line-height: 1.7" for="form_private_form_check">Private Form</label>
                    <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Select this option to prevent subscribers from viewing and subscribing to administrative forms."/> </div>
                  <!-- newly added -->
                  <label class="desc" for="form_for_selected_company">Sync On Entity: <img style="vertical-align: top" src="images/navigation/005499/16x16/Help.png" class="helpmsg" title="Set this entity to the entity you want to sync on when using auto mapping." /> </label>
                  <div>
                    <select class="select full" id="form_for_selected_company" name="form_for_selected_company" autocomplete="off">
                      <?php echo $select_com; ?>
                    </select>
                  </div>
                  <!-- *********** -->
                  <label class="desc" for="form_for_selected_entity">Entity Owners: <img style="vertical-align: top" src="images/navigation/005499/16x16/Help.png" class="helpmsg" title="Select All to make this form available to every user. Select one entity or control-click several to provision access to only those designated entities. Data ownership resides with the primary entity." /> </label>
                  <div>
                    <select multiple class="select full" id="form_for_selected_entity" name="form_for_selected_entity[]" autocomplete="off">
                      <?php echo $select_ent; ?>
                    </select>
                  </div>
                  <!-- *********** -->
                </fieldset>
              </li>
              <li id="form_prop_breaker" class="clear advanced_prop form_prop"></li>
              <li id="prop_pagination_style" class="clear">
                <fieldset class="choices">
                  <legend> Pagination Header Style <img class="helpmsg" src="images/navigation/FFFFFF/16x16/Help.png" style="vertical-align: top; " title="When a form has multiple pages, the pagination header will be displayed on top of your form to let your clients know their progress. This is useful to help your clients understand how much of the form has been completed and how much left to be filled out."/> </legend>
                  <ul>
                    <li>
                      <input type="radio" id="pagination_style_steps" name="pagination_style" class="choices_default" title="Complete Steps">
                      <label for="pagination_style_steps" class="choice">Complete Steps</label>
                      <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="A complete series of all page titles will be displayed, along with the page numbers. The respective page title will be highlighted as the client continue to the next pages. Use this style if your form only has small number of pages."/> </li>
                    <li>
                      <input type="radio" id="pagination_style_percentage" name="pagination_style" class="choices_default" title="Progress Bar">
                      <label for="pagination_style_percentage" class="choice">Progress Bar</label>
                      <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="A progress bar with a percentage number and the current active page title will be displayed. Use this style if your form has many pages or you need to put longer page title for each page."/> </li>
                    <li>
                      <input type="radio" id="pagination_style_disabled" name="pagination_style" class="choices_default" title="Disable Pagination Header">
                      <label for="pagination_style_disabled" class="choice">Disable</label>
                      <img class="helpmsg" src="images/navigation/005499/16x16/Help.png" style="vertical-align: top" title="Select this option if you prefer to disable the pagination header completely."/> </li>
                  </ul>
                </fieldset>
              </li>
              <li id="prop_pagination_titles" class="clear">
                <fieldset class="choices">
                  <legend> Page Titles <img class="helpmsg" src="images/navigation/FFFFFF/16x16/Help.png" style="vertical-align: top; " title="Each page on your form will have its own title which you can specify here. This is useful to organize the form into meaningful content groups. Ensure that the titles of your form pages match your clients' expectations and succintly explain what each page is for. "/> </legend>
                  <ul id="pagination_title_list">
                    <li>
                      <label for="pagetitleinput_1">1.</label>
                      <input type="text" value="" autocomplete="off" class="text" id="pagetitle_1" />
                    </li>
                  </ul>
                </fieldset>
              </li>
              <li id="embed_share_options" class="advanced_prop">
              	<fieldset class="choices">
              		<legend> Form Embed Options </legend>
					<label for="ec_code_type_user" class="description">User Type</label>
					<select class="element select full" id="ec_code_type_user_form_properties" name="ec_code_type_user">
						<option value="">Select an Option</option>
						<option value="admin">Admin</option>
						<option value="portal">Portal</option>
					</select>

					<label for="ec_code_type" class="description">Code Type</label>
					<select class="element select full" id="ec_code_type_form_properties" name="ec_code_type">
						<option value="">Select an Option</option>
						<!-- <option value="javascript">Javascript Code (Recommended)</option> -->
						<option value="iframe">Iframe Code</option>
						<option value="simple_link">Simple Link</option>
						<option value="popup_link">Popup Link</option>
                    </select>

					<label style="display: none;" class="desc" id="embed_main_title"></label>
					<textarea id="embed_textarea_content" style="display: none;" rows="10"></textarea>
				</fieldset>
              </li>

            </ul>
          </form>
          <!--  end form properties pane -->
          <form action="processupload.php?form_id=<?php echo noHTML($form_id); ?>" method="post" enctype="multipart/form-data" id="upload-form">
            <div style="display:none;">
              <input type="hidden" id="post-csrf-token" name="post_csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
            </div>
            <input name="ImageFile[]" id="image-file" type="file" style="display:none;" multiple />
            <input name="ImageFolderFormId" id="image-folder-form-id" type="hidden" value="<?php echo noHTML($form_id); ?>" />
          </form>
          <form action="processupload.php?form_id=<?php echo noHTML($form_id); ?>" method="post" enctype="multipart/form-data" id="upload-icon-form">
            <input name="ImageFile[]" id="upload-icon-button" type="file" accept="image/*" style="display:none;" />
            <input name="ImageFolderFormId" type="hidden" value="<?php echo noHTML($form_id); ?>" />
            <input type="hidden" name="mode" value="upload_icon" />
            <input type="hidden" name="option_id" id="upload-icon-option-id" />
          </form>
          <!-- <div class="bullet_bar_bottom"> <img style="float: left" src="images/bullet_green.png" /> <img style="float: right" src="images/bullet_green.png"/> </div> -->
        </div>
      </div>
    </div>
  </div>
</div>
<!-- /#sidebar -->

<div id="dialog-message" title="Error. Unable to complete the task." class="buttons" style="display: none"> <img src="images/navigation/ED1C2A/50x50/Warning.png" title="Warning" />
  <p> There was a problem connecting with your website server.<br/>
    Please try again within few minutes.<br/>
    <br/>
    If the problem persist, please contact us and we'll get back to you immediately! </p>
</div>
<div id="dialog-warning" title="Error Title" class="buttons" style="display: none"> <img src="images/navigation/ED1C2A/50x50/Warning.png" title="Warning" />
  <p id="dialog-warning-msg"> Error </p>
</div>
<div id="dialog-syncsave" title="Are you sure ?" class="buttons" style="display: none;text-align: center;"> <img src="images/navigation/ED1C2A/50x50/Warning.png" title="Warning" />
  <p id="dialog"> Are you sure you want to sync and save? All of your data will be removed. </p>
</div>
<div id="dialog-confirm-field-delete" title="Are you sure you want to delete this field?" class="buttons" style="display: none">
  <img src="images/navigation/ED1C2A/50x50/Warning.png">
  <p> This action cannot be undone.<br/>
    <strong>All data collected by the field will be deleted as well.</strong><br/>
    <br/>
  </p>
</div>
<div id="dialog-form-saved" title="Success! Your module has been saved." class="buttons" style="display: none; text-align: center;"> <img src="images/navigation/005499/50x50/Success.png">
  <p> <strong>Do you want to continue editing this form?</strong><br/>
    <br/>
  </p>
</div>
<div id="dialog-insert-choices" title="Bulk insert choices" class="buttons" style="display: none">
  <form class="dialog-form">
    <ul>
      <li>
        <label for="bulk_insert_choices" class="description">You can insert a list of choices here. Separate the choices with new line. </label>
        <div>
          <textarea cols="90" rows="8" class="element textarea medium" name="bulk_insert_choices" id="bulk_insert_choices"></textarea>
        </div>
      </li>
    </ul>
  </form>
</div>
<div id="dialog-insert-matrix-rows" title="Bulk insert rows" class="buttons" style="display: none">
  <form class="dialog-form">
    <ul>
      <li>
        <label for="bulk_insert_rows" class="description">You can insert a list of rows here. Separate the rows with new line. </label>
        <div>
          <textarea cols="90" rows="8" class="element textarea medium" name="bulk_insert_rows" id="bulk_insert_rows"></textarea>
        </div>
      </li>
    </ul>
  </form>
</div>
<div id="dialog-insert-matrix-columns" title="Bulk insert columns" class="buttons" style="display: none">
  <form class="dialog-form">
    <ul>
      <li>
        <label for="bulk_insert_columns" class="description">You can insert a list of columns here. Separate the labels with new line. </label>
        <div>
          <textarea cols="90" rows="8" class="element textarea medium" name="bulk_insert_columns" id="bulk_insert_columns"></textarea>
        </div>
      </li>
    </ul>
  </form>
</div>
<div id="dialog-confirm-edit" title="Caution! Are you sure you want to change this form?" class="buttons" style="display: none; text-align:center;">
	<img src="images/navigation/005499/50x50/Notice.png">
  <p id="dialog-confirm-edit-msg">Saving your changes to this form will delete all previous existing user data entries, but you will retain that entry data via a downloaded backup file. Are you sure you wish to proceed? Cancel if you are uncertain.<br/>
    <br/>
  </p>
</div>

<div id="processing-embed-dialog" style="display: none;text-align: center;font-size: 150%;">
	Processing Request...<br>
	 <img src="images/loading-gears.gif" style="height: 20%; width: 20%"/>
</div>
<div id="downloading-entry-dialog" style="display: none;text-align: center;font-size: 150%;">
	Downloading the entry data backup file...<br>
	<img src="images/loading-gears.gif" style="height: 100px; width: 100px"/>
</div>
<div id="entry-download-result-dialog" title="Error Title" style="display: none;text-align: center;font-size: 150%;">
	<img src="images/navigation/005499/50x50/Notice.png">
	<p id="entry-download-result-msg"> Error </p>
</div>
<?php
	$footer_data =<<<EOT
<script type="text/javascript" src="js/ajaxupload/jquery.form.js"></script>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>

<script type="text/javascript" src="js/colorpicker/jquery.colorpicker.js"></script>
<script type="text/javascript" src="js/jquery.tools.min.js"></script>
<script type="text/javascript" src="js/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="js/ckeditor5-classic/build/ckeditor.js"></script>
<script type="text/javascript" src="js/marquee/jquery.marquee.min.js"></script>
<script type="text/javascript" src="js/builder.js"></script>
<script type="text/javascript" src="js/form-builder/syndication.js"></script>
<script type="text/javascript" src="js/form-builder/video-player.js"></script>
<script type="text/javascript">
	$(function(){
		{$jquery_data_code}
    });

	$("#dialog-confirm-edit").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		open: function()
		{
			$("#btn-confirm-edit-ok").blur();
		},
		buttons: [{
			text: 'Yes. Proceed',
			id: 'btn-confirm-edit-ok',
			'class': 'bb_button bb_small bb_green',
			click: function(){
				$(this).dialog('close');
				$('#downloading-entry-dialog').dialog('open');
				// export current entries
				$.ajax({
					type: "POST",
					async: true,
					url: "export_entries.php",
					data: {
						form_id: {$form_id},
						export_all: 1,
						save_entries_to_server: 1
					},
					cache: false,
					global: false,
					dataType: "json",
					error: function(xhr,text_status,e){
						//error, display the generic error message
						console.log(xhr);
						$('#downloading-entry-dialog').dialog('close');
						$("#entry-download-result-dialog").dialog("option", "title", "Unable to export entry data");
						$("#entry-download-result-msg").html("Sorry, you are unable to export the entry data.<br> Saving your changes to this form will proceed in <strong id='countDown'>5</strong>s.");
						$("#entry-download-result-dialog").dialog('open');

						$('a#bottom_bar_save_form').attr('data-counter', '0');

						var count_down = 5;
						var x = setInterval(function() {
							$("#countDown").html(count_down);
							count_down--;
							if(count_down < 0) {
								clearInterval(x);
								$("#entry-download-result-dialog").dialog('close');
								$('a#bottom_bar_save_form').click();
							}
						}, 1000);
					},
					success: function(response_data){
						$('#downloading-entry-dialog').dialog('close');
						if(response_data.status == "success") {
							var file_path = response_data.export_link;
							var a = document.createElement('A');
							a.href = file_path;
							a.download = file_path.substr(file_path.lastIndexOf('/') + 1);
							document.body.appendChild(a);
							a.click();
							document.body.removeChild(a);
							$("#entry-download-result-dialog").dialog("option", "title", "The entry data has been downloaded successfully.");
							$("#entry-download-result-msg").html("You've successfully downloaded the entry data.<br> Saving your changes to this form will proceed in <strong id='countDown'>5</strong>s.");
							$("#entry-download-result-dialog").dialog('open');

							$('a#bottom_bar_save_form').attr('data-counter', '0');

							var count_down = 5;
							var x = setInterval(function() {
								$("#countDown").html(count_down);
								count_down--;
								if(count_down < 0) {
									clearInterval(x);
									$("#entry-download-result-dialog").dialog('close');
									$('a#bottom_bar_save_form').click();
								}
							}, 1000);
						} else {
							console.log(response_data);
							$("#entry-download-result-dialog").dialog("option", "title", "Unable to export entry data");
							$("#entry-download-result-msg").html("Sorry, you are unable to export the entry data.<br> Saving your changes to this form will proceed in <strong id='countDown'>5</strong>s.");
							$("#entry-download-result-dialog").dialog('open');

							$('a#bottom_bar_save_form').attr('data-counter', '0');

							var count_down = 5;
							var x = setInterval(function() {
								$("#countDown").html(count_down);
								count_down--;
								if(count_down < 0) {
									clearInterval(x);
									$("#entry-download-result-dialog").dialog('close');
									$('a#bottom_bar_save_form').click();
								}
							}, 1000);
						}
					}
				});
			}
		},
		{
			text: 'Cancel',
			id: 'btn-confirm-entry-delete-cancel',
			'class': 'btn_secondary_action',
			click: function()
			{
				$(this).dialog('close');
			}
		}]
	});

	$("#processing-embed-dialog").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false
	});

	$("#downloading-entry-dialog").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false
	});

	$("#entry-download-result-dialog").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false
	});
</script>
EOT;
	require('includes/footer.php');
?>
<script>
function IsNumeric(input){
    var RE = /^-{0,1}\d*\.{0,1}\d+$/;
    return (RE.test(input));
}

$(document).ready(function() {
    var MAX_UPLOAD_SIZE = <?php echo $upload_max_size ?>;

    Syndication.init();
    VideoPlayer.init(MAX_UPLOAD_SIZE);

	$('a#get-user-confirmation').click(function(){
		$("#dialog-confirm-edit").dialog('open');
		return false;
	});

	$(document).on('change', 'select#ec_code_type_user_form_properties', function(event){
		$('#ec_code_type_form_properties').val('');
		$('#embed_textarea_content, #embed_main_title').hide();
	});

	$(document).on('change', 'select#ec_code_type_form_properties', function(event){
		$('#embed_textarea_content, #embed_main_title').hide();
		event.preventDefault();
		var ec_code_type_user = $('#ec_code_type_user_form_properties').val();
		if( !ec_code_type_user ) {
			alert('Please select \'Form User Type\' first.');
			$(this).val('');
			return false;
		}
		var selected_value = $(this).val();
		if( selected_value ) {
			$("#processing-embed-dialog").dialog('open');
			$.ajax({
		    type: "POST",
		    async: true,
		    url: "embed_code_ajax.php",
		    data: {
		        form_id: <?=$form_id?>,
		        embed_selected: $(this).val(),
		        ec_code_type_user: ec_code_type_user
		    },
		    cache: false,
		    global: false,
		    dataType: "json",
		    error: function(h, f, g) {},
		    success: function(e) {
				if (e.success) {
					var embed_main_title = e.embed_main_title+' <img class="helpmsgnew helpmsg" src="images/navigation/005499/16x16/Help.png" title="'+e.embed_extra_info+'" id="embed_extra_info" style="vertical-align: top">';
					// var embed_main_title = e.embed_main_title+'<br>'+e.embed_extra_info;
					// if( typeof e.embed_text_only != undefined ) {
					if( e.hasOwnProperty('embed_text_only') ) {
						embed_main_title += '<br>'+e.embed_text_only;
					} else {
				  		$('#embed_textarea_content').text(e.embed_textarea_content).show();
					}
					$('#embed_main_title').html(embed_main_title).show();
					$(".helpmsgnew").tooltip({
						position: "bottom center",
						offset: [10, 20],
						effect: "fade",
						opacity: 0.8,
						events: {
							def: 'click,mouseout'
						}
					});
					adjust_main_height();
				} else if (e.error) {
					alert(e.error_message);
					if( typeof(e.redirect) !== "undefined" ) {
						window.location = e.redirect;
					}
				}
		    },
				complete: function (event, request, settings) {
					$("#processing-embed-dialog").dialog('close');
				}
	    });
	  }
	});

	$(document).on('change', 'select#ec_code_type_field_user_properties', function(event){
		$('#ec_code_type_field_properties').val('');
		$('#embed_field_main_title, #embed_field_textarea_content').hide();
	});

	//field embed code
	$(document).on('change', 'select#ec_code_type_field_properties', function(event){
		$('#embed_field_textarea_content, #embed_field_main_title').hide();
		event.preventDefault();
		var selected_value = $(this).val();
		var embed_code_type_user = $('#ec_code_type_field_user_properties').val();
		if( !embed_code_type_user ) {
			alert('Please select \'Embed User Type\' first.');
			$(this).val('');
			return false;
		}

		var element_id_auto = $(this).attr('data-element_id_auto');
		var element_page_number = $(this).attr('data-element_page_number');
		if( selected_value && element_id_auto && element_page_number ) {
			$("#processing-embed-dialog").dialog('open');
			$.ajax({
		    type: "POST",
		    async: true,
		    url: "ajax-requests.php",
		    data: {
		        action: 'field_embed_content',
		        form_id: <?=$form_id?>,
		        //get the value from data
		        field_selected: element_id_auto,
		        element_page_number: element_page_number,
		        embed_selected: $(this).val(),
		        embed_code_type_user: embed_code_type_user
		    },
		    cache: false,
		    global: false,
		    dataType: "json",
		    error: function(h, f, g) {},
		    success: function(e) {
				if (e.success) {
					var embed_main_title = e.embed_main_title+' <img class="helpmsgnewfield helpmsg" src="images/navigation/005499/16x16/Help.png" title="'+e.embed_extra_info+'" style="vertical-align: top">';

					if( e.hasOwnProperty('embed_text_only') ) {
						embed_main_title += '<br>'+e.embed_text_only;
					} else {
						$('#embed_field_textarea_content').text(e.embed_textarea_content).show();
					}
						$('#embed_field_main_title').html(embed_main_title).show();

					$(".helpmsgnewfield").tooltip({
						position: "bottom center",
						offset: [10, 20],
						effect: "fade",
						opacity: 0.8,
						events: {
							def: 'click,mouseout'
						}
					});
					adjust_main_height();
				}

		    },
			complete: function (event, request, settings) {
				$("#processing-embed-dialog").dialog('close');
			}
	    });
	  }
	});

	$('#element_label_background_color, #element_label_color, #element_page_break_bg_color').colorpicker({
        // showOn:         'both',
        // showNoneButton: true,
        showCloseButton: true,
        showCancelButton: true,
        colorFormat: ['#HEX'],
    //     position: {
		  //  my: 'center',
		  //  at: 'center',
		  //  of: window
		  // },
	   // modal: false
    });

	// determines whether or not entries should be deleted
	// (delete entries if field added, removed, or reordered)
	// checks for reordered insinde builder.js
	localStorage.setItem('addRemoveOrReorder', 'false'); // defaults to false

  // begin field removed
	$('#btn-field-delete-ok').click(function() {
		localStorage.setItem('addRemoveOrReorder', 'true');
	});

  // begin field added
	$(document).ajaxComplete(function(event, xhr, settings) {
		if (settings.url === "add_field.php") {
			localStorage.setItem('addRemoveOrReorder', 'true');
		}
	});

  // begin field reordered

  // check if element is in the same location
  function checkIfElementInSameLocation(clickedElementLocationBefore, clickedElementLocationAfter) {
    if(clickedElementLocationBefore !== clickedElementLocationAfter) {
      // element has been reordered
      localStorage.setItem('addRemoveOrReorder', 'true');
    }
  }

  // calculate location of element
  function getElementLocation(el) {
    var x = 0;
    var y = 0;
    while(el && !isNaN(el.offsetLeft) && !isNaN(el.offsetTop)) {
        x += el.offsetLeft - el.scrollLeft;
        y += el.offsetTop - el.scrollTop;
        el = el.offsetParent;
    }
    return { top: y, left: x };
  }

  var elementClicked;
  var clickedElementLocationBefore;
  var clickedElementLocationAfter;

  // get location of clicked element on mousedown
  document.addEventListener("mousedown", function(e) {
    elementClicked = e.target;
    clickedElementLocationBefore = getElementLocation(elementClicked).top;
  });  
  
  // get location of clicked element on mouseup
  document.addEventListener("mouseup", function(e) {
    if(e.path.toString().indexOf("bottom_bar_add_field") == 0) {
      function waitToGetLocationThenCompare(){
        clickedElementLocationAfter = getElementLocation(elementClicked).top;
        checkIfElementInSameLocation(clickedElementLocationBefore, clickedElementLocationAfter);
      }
      setTimeout(waitToGetLocationThenCompare, 1000);
    }
  });
});
</script>
<?php if($la_settings['enable_autocomplete'] == 1) { ?>
<script>
//autocomplete logic
$(document).ready(function() {
  var form_properties = $('#form_header').data('form_properties');
  var framework_type = form_properties.framework_type;
  var framework_or_group_id = form_properties.framework_or_group_id;

  console.log('framework_type', framework_type)
  console.log('framework_or_group_id', framework_or_group_id)

  $.ajax({
    type: "GET",
    async: true,
    url: "<?=DISCOVERY_APP_URL?>framework/public",
    cache: false,
    global: false,
    dataType: "json",
    error: function(h, f, g) {

    },
    success: function(response) {
      $.each(response, function(i, item) {
        $('#form_framework').append(`<option value="${item.id}">${item.name}</option>`);
      })
    }
  });

  $.ajax({
    type: "GET",
    async: true,
    url: "<?=DISCOVERY_APP_URL?>framework-group/public",
    cache: false,
    global: false,
    dataType: "json",
    error: function(h, f, g) {

    },
    success: function(response) {
      $.each(response, function(i, item) {
        $('#form_framework_group').append(`<option value="${item.id}">${item.name}</option>`);
      })
    }
  });

  if( framework_type == 0 ) {
    if( framework_or_group_id > 0 ) {
      $( "#element_machine_code" ).autocomplete({
          source: `<?=DISCOVERY_APP_URL?>discovery/framework/${framework_or_group_id}?type=code`,
          minLength: 2,
          select: function( event, ui ) {
              $('#element_label').val(ui.item.field_label);
          }
      });

      $( "#element_label" ).autocomplete({
          source: `<?=DISCOVERY_APP_URL?>discovery/framework/${framework_or_group_id}?type=label`,
          minLength: 2,
          select: function( event, ui ) {
              $('#element_machine_code').val(ui.item.cgrc_code);
          }
      });
    }
  } else {
    if( framework_or_group_id > 0 ) {
      $( "#element_machine_code" ).autocomplete({
          source: `<?=DISCOVERY_APP_URL?>discovery/framework-group/${framework_or_group_id}?type=code`,
          minLength: 2,
          select: function( event, ui ) {
              $('#element_label').val(ui.item.field_label);
          }
      });

      $( "#element_label" ).autocomplete({
          source: `<?=DISCOVERY_APP_URL?>discovery/framework-group/${framework_or_group_id}?type=label`,
          minLength: 2,
          select: function( event, ui ) {
              $('#element_machine_code').val(ui.item.cgrc_code);
          }
      });
    }
  }
});
</script>
<?php } ?>