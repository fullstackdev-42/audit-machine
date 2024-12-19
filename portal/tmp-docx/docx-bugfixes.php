<?php
require_once '../../policymachine/classes/CreateDocx.inc';
require_once '../../policymachine/classes/CreateDocxFromTemplate.inc';

/*$docx = new CreateDocx();
$docx->addText('Hello world!');
$docx->createDocx('hello_world');*/
//
$html =  '<div>';
$html .=  '<a href="http://www.google.com">google.com</a>';
$html .=  '<br>';
$html .=  '<a href="http://www.yahoo.com">yahoo.com</a>';
$html .= '</div>';

$text =  '<div>';
$text .=  '<img width="200px" src="https://upload.wikimedia.org/wikipedia/en/thumb/b/b4/Donald_Duck.svg/618px-Donald_Duck.svg.png" />';
$text .=  '<br>';
$text .=  '<img width="200px" src="http://img.dunyanews.tv/news/2017/November/11-29-17/news_big_images/416704_17676629.jpg" />';
$text .= '</div>';

try{
	$docx = new CreateDocxFromTemplate('test.docx');
	$docx->replaceVariableByHTML('HTML', 'inline', $html, array('isFile' => false, 'parseDivsAsPs' => true, 'downloadImages' => false));
	$docx->replaceVariableByHTML('TEXT', 'inline', $text, array('isFile' => false, 'parseDivsAsPs' => true, 'downloadImages' => true));
	$docx->createDocx('test_template2');
} catch(Exception $e){
	echo var_dump($e);
	echo "<br>";
	echo $e->getMessage();
}
?>