var jMachform = jQuery.noConflict();
(function($) { 
  $(function() {
    var la_iframe_height;
      
    var la_iframe = $('<iframe onload="javascript:parent.scrollTo(0,0);" height="' + __itauditmachine_height + '" allowTransparency="true" frameborder="0" scrolling="no" style="width:100%;border:none" src="'+ __itauditmachine_url +'"><a href="'+ __itauditmachine_url +'">View Form</a></iframe>');
    $("#la_placeholder").after(la_iframe);
    $("#la_placeholder").remove();

    $.receiveMessage(function(e){      
      if(e.data.indexOf('run_safari_cookie_fix') != -1){
        //execute safari cookie fix
        var la_folder = __itauditmachine_url.substring(0,__itauditmachine_url.lastIndexOf('/'));
        
        window.location.href = la_folder + '/safari_init.php?ref=' + window.btoa(window.location.href);
        return;
      }else{
        //adjust the height of the iframe     
        var new_height = Number( e.data.replace( /.*la_iframe_height=(\d+)(?:&|$)/, '$1' ) );
        if (!isNaN(new_height) && new_height > 0 && new_height !== la_iframe_height) {
          la_iframe.height(la_iframe_height = new_height); //height has changed, update the iframe
        }
      }
      
    });
  });
})(jMachform);