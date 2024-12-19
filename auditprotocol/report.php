<?php
/********************************************************************************
 IT Audit Machine
  
 Patent Pending, Copyright 2000-2018 Continuum GRC Software. This code cannot be redistributed without
 permission from http://lazarusalliance.com/
 
 More info at: http://lazarusalliance.com/
 ********************************************************************************/
	require('includes/init.php');

	require('config.php');
	require('includes/db-core.php');
	require('includes/helper-functions.php');
	require('includes/filter-functions.php');
	require('includes/entry-functions.php');
	require('includes/report-functions.php');

	$report_access_key = la_sanitize($_GET['key']);

	if(empty($report_access_key)){
		die("This report is not available (missing access key).");
	}

	$dbh = la_connect_db();
	$ssl_suffix = la_get_ssl_suffix();
	
	//check the validity of the access key
	$query = "SELECT 
					A.form_id,
					(select form_name from ap_forms where form_id = A.form_id) form_title
			    FROM
			    	".LA_TABLE_PREFIX."reports A
			   WHERE
			   		A.report_access_key = ?";
	$params = array($report_access_key);
		
	$sth = la_do_query($query,$params,$dbh);
	$row = la_do_fetch_result($sth);

	if(!empty($row['form_id'])){
		$form_id  	  = (int) $row['form_id'];
		$report_title = noHTML($row['form_title']);
	}else{
		die("This report is no longer available.");
	}

	//get the list of widgets, put them into  array
	$query = "SELECT 
					access_key,
					chart_id,
					chart_title,
					chart_height,
					chart_type 
				FROM 
					".LA_TABLE_PREFIX."report_elements
				WHERE 
					chart_status = 1 and 
					access_key <> '' and 
					form_id = ?
			ORDER BY 
					chart_position,chart_id desc";
	
	$params = array($form_id);
	$sth = la_do_query($query,$params,$dbh);
	
	$report_widgets_array = array();
	$i=0;
	while($row = la_do_fetch_result($sth)){
		$report_widgets_array[$i]['chart_id'] 	 = $row['chart_id'];
		$report_widgets_array[$i]['access_key']  = $row['access_key'];

		$chart_type = $row['chart_type'];
		$chart_title = $row['chart_title'];

		$report_widgets_array[$i]['chart_height'] = (int) $row['chart_height'];
		if($chart_type == 'grid' && !empty($chart_title)){
			$report_widgets_array[$i]['chart_height'] += 30; //if the grid is having title, add 30px to the height
		}

		$report_widgets_array[$i]['chart_title'] = htmlspecialchars($row['chart_title']);
		if(empty($report_widgets_array[$i]['chart_title'])){
			$report_widgets_array[$i]['chart_title'] = '-Untitled Widget-';
		}

		$i++;
	}


?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo $report_title; ?></title>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge" >
<link href="css/reset.css" rel="stylesheet">
</head>
<body style="margin: 20px">
<ul id="report_list">
  <?php
			//display the widgets
		foreach ($report_widgets_array as $value) {
			$widget_url = 'http'.$ssl_suffix.'://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/widget.php?key='.$value['access_key'];
	?>
  <li id="li_<?php echo noHTML($value['chart_id']); ?>">
    <iframe 
							height="<?php echo noHTML($value['chart_height']); ?>" 
							allowTransparency="true" 
							frameborder="0" 
							scrolling="no" 
							style="width:100%;border:none" 
							src="<?php echo noHTML($widget_url); ?>" 
							title="Report Form"> <a href="<?php echo noHTML($widget_url); ?>" title="<?php echo noHTML($value['chart_title']); ?>"><?php echo noHTML($value['chart_title']); ?></a> </iframe>
  </li>
  <?php } ?>
</ul>
</body>
</html>