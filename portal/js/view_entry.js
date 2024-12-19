$(function(){
	//start::show disclaimer before zip download
	//dialog box to download document disclaimer
	$("#dialog-download-document-zip").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 550,
		draggable: false,
		resizable: false,
		buttons: [{
				text: 'I accept',
				id: 'btn-download-document-zip',
				'class': 'bb_button bb_small bb_green',
				click: function() {
					var documentdownloadlink = $("#btn-download-document-zip").data('documentdownloadlink');
					window.location.href = documentdownloadlink;
					$(this).dialog('close');
				}
			},
			{
				text: 'Cancel',
				id: 'btn-download-document-zip-cancel',
				'class': 'btn_secondary_action',
				click: function() {
					$(this).dialog('close');
				}
			}]

	});
	
	//open the deletion dialog when the download document link clicked
	$(".action-download-document-zip").click(function(){
		$("#btn-download-document-zip").data('documentdownloadlink', $(this).attr('data-documentdownloadlink'));
		$("#dialog-download-document-zip").dialog('open');
		return false;
	});
	//end::show disclaimer before zip download
	
	$("#processing-dialog, #processing-dialog-edit-entry, #processing-dialog-document").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false
	});

	$("#processing-dialog-document").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false,
		buttons: [{
			text: 'Close',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});

	$("#document-preview").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 930,
		draggable: false,
		resizable: false,
		open: function(){
			//$(this).next().find('button').blur()
		},
		buttons: [{
			text: 'Close',
			'class': 'bb_button bb_small bb_green',
			click: function() {
				$(this).dialog('close');
			}
		}]
	});
	// $('#document-preview').dialog('open');
	$(document).on('click', '.entry-link-preview', function(e){
		e.preventDefault();
		$('#document-preview-content').html("");
		$('#file_viewer_download_button').attr('href', "");
    	var identifier = $(this).data('identifier');
    	var ext = $(this).data('ext');
    	var src = $(this).data('src');

    	$('#document-preview-content').html("");
		if( identifier == 'image_format' ) {
			//means this document is an image and has format one of these ('png', 'jpg', 'jpeg')
			//so we can show directly it in popup
			$('#document-preview-content').html('<img src="'+src+'" style="max-width: 100%;max-height: 100%;margin: auto;display: block;" />');
			$('#file_viewer_download_button').attr('href', src);
			$('#document-preview').dialog('open');
		} else if( identifier == 'other' ) {
			$('#processing-dialog').dialog('open');

			//do the ajax call to get pdf link
			$.ajax({
				type: "GET",
				async: true,
				url: "/auditprotocol/download.php?q="+src,
				cache: false,
				global: false,
				dataType: "json",
				error: function(xhr,text_status,e){
					//show error message to user
					$('#processing-dialog').dialog('close');
					$('#document-preview-content').html('Error Occurred while requesting. Please try again later.');
					$('#document-preview').dialog('open');
				},
				success: function(response){
					if( response.status == 'success' ) {
						if( response.only_download ) {
							$('#document-preview-content').html('Preview is not available for this file extension.');
						} else {
							$('#document-preview-content').html('<embed src="'+response.file_src+'#toolbar=0" type="application/pdf" width="100%" height="100%">');
						}
						$('#processing-dialog').dialog('close');
						$('#document-preview').dialog('open');
						$('#file_viewer_download_button').attr('href', response.download_src);

					} else {
						$('#processing-dialog').dialog('close');
						$('#document-preview-content').html('Error Occurred while requesting. Please try again later.');
						$('#document-preview').dialog('open');
					}
				}
			});
		}
    });
});