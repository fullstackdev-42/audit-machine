<?php

function chatbot($dbh, $form_id) {
	$chat_html = '';
	
	$query  = "select 
                    form_name,
                    chat_bot_enable,
                    chat_bot_type
                 from 
                     ".LA_TABLE_PREFIX."forms 
                where 
                     form_id = ?";
    $params = array($form_id);
    
    $sth = la_do_query($query,$params,$dbh);
    $row = la_do_fetch_result($sth);
    
    if(!empty($row)){
		$form_name          = noHTML($row['form_name']);
        $chat_bot_enable = (int) $row['chat_bot_enable'];
        $chat_bot_type = $row['chat_bot_type'];


        if( $chat_bot_enable ) {
            $query  = "select 
                    field_name,
                    field_value
                 from 
                     ".LA_TABLE_PREFIX."form_integration_fields
                where 
                     form_id = ?";
            $params = array($form_id);
            
            $sth = la_do_query($query,$params,$dbh);
            
            while($row = la_do_fetch_result($sth)){
                ${$row['field_name']} = $row['field_value'];
            }
        }

        
        if( $chat_bot_type == 'chatstack' ) {
        	$chat_html .= <<<EOT
        	<!-- START Chatstack Live Chat HTML Code - chatstack.com --> 
<script type="text/javascript"> 
 var Chatstack = { server: '{$chatstack_domain}', embedded: true }; 
 (function(d, undefined)

{    // JavaScript    Chatstack.e = []; Chatstack.ready = function (c) 

{ Chatstack.e.push(c); }
 
  var b = d.createElement('script'); b.type = 'text/javascript'; b.async = true; 
  b.src = ('https:' == d.location.protocol ? 'https://' : 'http://') + Chatstack.server + '/livehelp/scripts/js.min.js'; 
  var s = d.getElementsByTagName('script')[0]; 
  s.parentNode.insertBefore(b, s); 
 })(document); 
</script> 
<!-- END Chatstack Live Chat HTML Code - chatstack.com --> 

<!-- BEGIN chatstack.com Live Chat HTML Code --> 
<a href="#" class="LiveHelpButton default"><img src="https://continuumgrc.com/livehelp/status.php" id="LiveHelpStatusDefault" name="LiveHelpStatusDefault" border="0" alt="Live Help" class="LiveHelpStatus"/></a> 
<!-- END chatstack.com Live Chat HTML Code -->
<style type="text/css">
.LiveHelpButton{
    position: fixed;
    right: 14px;
    bottom: 24px;
}
</style>
EOT;

        }


    }

    return $chat_html;
}