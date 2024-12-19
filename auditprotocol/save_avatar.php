<?php

require('includes/init.php');
require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('includes/users-functions.php');


$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);
//user info
$is_admin = $_POST['is_admin'];
$user_id = $_POST['user_id'];
$mode = $_POST['mode'];

$imgUrl = $_POST['imgUrl'];
// original sizes
$imgInitW = $_POST['imgInitW'];
$imgInitH = $_POST['imgInitH'];
// resized sizes
$imgW = $_POST['imgW'];
$imgH = $_POST['imgH'];
// offsets
$imgY1 = $_POST['imgY1'];
$imgX1 = $_POST['imgX1'];
// crop box
$cropW = $_POST['cropW'];
$cropH = $_POST['cropH'];
// rotation angle
$angle = $_POST['rotation'];

$jpeg_quality = 100;

$output_filename = "";
if($is_admin == 1) {
	$output_filename = "avatars/admin_".$user_id."_".time();
} else {
	$output_filename = "avatars/user_".$user_id."_".time();
}

$what = getimagesize($imgUrl);

switch(strtolower($what['mime']))
{
    case 'image/png':
        $img_r = imagecreatefrompng($imgUrl);
		$source_image = imagecreatefrompng($imgUrl);
		$type = '.png';
        break;
    case 'image/jpeg':
        $img_r = imagecreatefromjpeg($imgUrl);
		$source_image = imagecreatefromjpeg($imgUrl);
		error_log("jpg");
		$type = '.jpeg';
        break;
    case 'image/gif':
        $img_r = imagecreatefromgif($imgUrl);
		$source_image = imagecreatefromgif($imgUrl);
		$type = '.gif';
        break;
    default: die('image type not supported');
}


//Check write Access to Directory

if(!is_writable(dirname($output_filename))){
	$response = Array(
	    "status" => 'error',
	    "message" => 'Can`t write cropped File'
    );	
}else{

    // resize the original image to size of editor
    $resizedImage = imagecreatetruecolor($imgW, $imgH);
	imagecopyresampled($resizedImage, $source_image, 0, 0, 0, 0, $imgW, $imgH, $imgInitW, $imgInitH);
    // rotate the rezized image
    $rotated_image = imagerotate($resizedImage, -$angle, 0);
    // find new width & height of rotated image
    $rotated_width = imagesx($rotated_image);
    $rotated_height = imagesy($rotated_image);
    // diff between rotated & original sizes
    $dx = $rotated_width - $imgW;
    $dy = $rotated_height - $imgH;
    // crop rotated image to fit into original rezized rectangle
	$cropped_rotated_image = imagecreatetruecolor($imgW, $imgH);
	imagecolortransparent($cropped_rotated_image, imagecolorallocate($cropped_rotated_image, 0, 0, 0));
	imagecopyresampled($cropped_rotated_image, $rotated_image, 0, 0, $dx / 2, $dy / 2, $imgW, $imgH, $imgW, $imgH);
	// crop image into selected area
	$final_image = imagecreatetruecolor($cropW, $cropH);
	imagecolortransparent($final_image, imagecolorallocate($final_image, 0, 0, 0));
	imagecopyresampled($final_image, $cropped_rotated_image, 0, 0, $imgX1, $imgY1, $cropW, $cropH, $cropW, $cropH);
	// finally output png image
	//imagepng($final_image, $output_filename.$type, $png_quality);
	imagejpeg($final_image, $output_filename.$type, $jpeg_quality);

	//save in DB
	if($is_admin == 1) {
		$query_update = "UPDATE `".LA_TABLE_PREFIX."users` SET `avatar_url` = ? WHERE `user_id`= ?";
		la_do_query($query_update, array($output_filename.$type, $user_id), $dbh);
		$files = scandir("avatars/");
		foreach ($files as $key => $file) {
			if((strpos($file, "admin_".$user_id."_") !== false) && ("avatars/".$file != $output_filename.$type)){
				unlink($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/avatars/".$file);
			}
		}
	} else {
		$query_update = "UPDATE `".LA_TABLE_PREFIX."ask_client_users` SET `avatar_url` = ? WHERE `client_user_id`= ?";
		la_do_query($query_update, array($output_filename.$type, $user_id), $dbh);
		$files = scandir("avatars/");
		foreach ($files as $key => $file) {
			if((strpos($file, "user_".$user_id."_") !== false) && ("avatars/".$file != $output_filename.$type)){
				unlink($_SERVER["DOCUMENT_ROOT"]."/auditprotocol/avatars/".$file);
			}
		}
	}
	if($mode == "my_profile") {
		$_SESSION["LA_SUCCESS"] = "Your profile photo has been updated successfully.";
	} elseif ($mode == "edit_user") {
		$_SESSION["LA_SUCCESS"] = "User profile photo has been updated successfully.";
	}
	
	$response = Array(
	    "status" => 'success',
	    "url" => $la_settings["base_url"].$output_filename.$type
    );
}
print json_encode($response);