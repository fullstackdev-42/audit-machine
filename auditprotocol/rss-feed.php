<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

libxml_use_internal_errors(true);
$feeds = file_get_contents(trim($_REQUEST['feed_url']));

$invalid_characters = '/[^\x9\xa\x20-\xD7FF\xE000-\xFFFD]/';
$feeds = preg_replace($invalid_characters, '', $feeds);

$feedArr = simplexml_load_string($feeds);

$feeds = array();


if(count($feedArr->channel->item)){
	foreach($feedArr->channel->item as $k => $v){
		array_push($feeds, array('title' => (string)$v->title, 'link' => (string)$v->link, 'description' => (string)$v->description, 'pubDate' => (string)$v->pubDate, 'guid' => (string)$v->guid));
	}
}

echo json_encode($feeds);