var videos = {};
var get_existing_files_flag = false;

function renderRss(param){
    var _rssFeedXml = '';
    var _response = new Array();
    var data = JSON.parse(param.feedInfo);
    if(data.feed_url != '') {
        $('#loader_' + param.li).show();

        App.ajax('GET', 'rss-feed.php', {feed_url: data.feed_url}, function(res) {
            var contentLength = (data.scroll_direction == 'horizontal' ? 130 : 160);
            var feedTemplate = '<div class="feed-item">';
            res.forEach(function(feed) {
                feedTemplate += '<div class="item">';
                    feedTemplate += '<p class="feed-title">';
                        feedTemplate += '<a target="_blank" href="' + feed.link + '">'  + feed.title + '</a>';
                    feedTemplate += '</p>';
                    feedTemplate += '<p class="feed-description">'  + feed.description.substr(0, contentLength) + ' ...</p>';
                    feedTemplate += '<p class="feed-datetime">'  + feed.pubDate + '</p>';
                feedTemplate += '</div>';
            });
            feedTemplate += '</div>';

            $('#loader_' + param.li).hide();
            $('#li_' + param.li + ' .feed-container').html(feedTemplate);

            applyRssChanges(param.li, data);
        });
    }
}

function applyRssChanges(index, props) {
    if(props.width) {
        $('#li_' + index).css('width', props.width);
    }

    if(props.height) {
        $('#li_' + index + ' .feed-container').css('height', props.height);
    }

    if(props.datetime) {
        $('#li_' + index + ' .feed-datetime').css('visibility', 'visible');
    }

    if(props.scroll_bar) {
        $('#li_' + index + ' .feed-container').addClass('scroll-bar');
    } else {
        var marqueeOptions = {
            itemSelecter: 'div',
            delay: props.scroll_speed * 1000,
            direction: props.scroll_direction
        };

        if(props.scroll_direction == 'horizontal') {
            marqueeOptions['delay'] = 0;
            marqueeOptions['timing'] = props.scroll_speed * 10;

            $('#li_' + index + ' .feed-container').addClass('horizontal');
        }

        $('#li_' + index + ' .feed-item').marquee(marqueeOptions);
    }
}

function renderVideos(_video) {
    if( _video ) {
        var template;
        if(_video.element_video_url != '') {

            if (_video.element_media_type == null ||  
                _video.element_media_type == undefined || 
                _video.element_media_type == 0 ||
                _video.element_media_type == 'video'

                ) {

                var options = {
                    loop: _video['element_video_loop'] == 1 ? true : false,
                    autoplay: _video['element_video_auto_play'] == 1 ? true : false,
                };

                var type = detectVideoUrlType(_video.element_video_url);
                var videoType = 'video/' + type;
                if (type && (type == 'youtube' || type == 'vimeo')) {
                    options['techOrder'] = [type];
                }
                
                template = '<video id="video_' + _video.element_id + '" class="video-js" controls muted="muted">';
                    template += '<source src="' + _video.element_video_url + '" type="' + videoType + '">';
                template += '</video>';
                template += '<span id="video_data_' + _video.element_id + '" style="display: none">' + JSON.stringify(_video) + '</span>';
                $('#li_' + _video.element_id + ' .video-player-container').html(template);
                videos[_video.element_id] = videojs('video_' + _video.element_id, options);

            } else if (  _video.element_media_type == 'image' ) {
                template = '<img src="'+_video.element_video_url+'" style="max-width: 100%;">';
                $('#li_' + _video.element_id + ' .video-player-container').html(template);
            }
            
        }
    }
}

function detectVideoUrlType(url) {
    var type = 'custom';
    if( url ) {
        var urlParts = url.split('.');

        if(url.indexOf('youtube') != -1 || url.indexOf('youtu.be') != -1) {
            return 'youtube';
        }

        if(url.indexOf('vimeo') != -1) {
            return 'vimeo';
        }

        if(type == 'custom') {
            return urlParts[urlParts.length - 1];
        }
    }

    return null;
}

$(document).ready(function(){
    $.fn.dataTable.ext.classes.sPageButton = 'data-table-custom-pagination';
    var existing_files_table = $("#table-uploaded-files").DataTable({
        dom: 'rftip',
        columnDefs: [
            {
                orderable: false,
                searchable: false,
                className: 'select-checkbox',
                targets:   0
            }
        ],
        select: {
            style:    'multi',
            selector: 'td:first-child'
        },
        order: [[1, 'asc']],
        sPaginationType: "numbers",
        pageLength: 15
    });

    var generated_reports_table = $("#table-generated-reports").DataTable({
        dom: 'rftip',
        columnDefs: [
            {
                orderable: false,
                searchable: false,
                className: 'select-checkbox',
                targets:   0
            }
        ],
        select: {
            style:    'multi',
            selector: 'td:first-child'
        },
        order: [[1, 'asc']],
        sPaginationType: "numbers",
        pageLength: 15
    });

    //toggle file management tabs
    $("#file_management").on("click", ".btn-activity", function(e){
        $(".tab-panel-header .actions button.btn-activity").removeClass("active");
        $(this).addClass("active");
        var active_div = $(this).attr("toggle");
        $("#file_management .activity-div").css("display", "none");
        $("#" + active_div).css("display", "block");
    });

    $(document).on('click', '.remove-selected-file', function(event){
        event.preventDefault();
        var file_target_id = $(this).attr("file_target_id");
        var new_file = $(this).attr("new_file");
        var selected_files = $(file_target_id).val().split("|");
        const index = selected_files.indexOf(new_file);
        if (index > -1) {
            selected_files.splice(index, 1);
        }
        $(file_target_id).val(selected_files.filter(function(a){return a !== ""}).join("|"));
        $(this).parent().remove();
    });

	$(document).on('click', 'img.element-note', function(event){
    	var _selector = $(this);
        var _note_id = 0;
    	var _form_id = _selector.attr('data-form-id');
    	var _element_id = _selector.attr('data-element-id');
    	var _company_id = _selector.attr('data-company-id');
        $('#assignees :selected').each(function(i, selected){
            $(selected).prop("selected", false);
        });
        $("#element-note").val("");

    	$.ajax({
    		url:"processupload.php",
    		type:"POST",
    		data:{mode:"getnote", form_id:_form_id, element_id:_element_id, company_id: _company_id},
    		beforeSend: function(){},
    		success: function(response){
    			if(response != "No element note found") {
                    
                    var assignees = JSON.parse(response).assignees;
                    var note = JSON.parse(response).note;
                    _note_id = JSON.parse(response).note_id;
                    var admin = assignees.split(";")[0].split(",");
                    var entity = assignees.split(";")[1].split(",");
                    var user = assignees.split(";")[2].split(",");
                    admin.forEach(function(i){
                        $("#assignees option").each(function(index, selected){
                            if($(selected).attr("role") == "admin" && selected.value == i){
                                $(selected).prop("selected", true);
                            }
                        })
                    })
                    entity.forEach(function(i){
                        $("#assignees option").each(function(index, selected){
                            if($(selected).attr("role") == "entity" && selected.value == i){
                                $(selected).prop("selected", true);
                            }
                        })
                    })
                    user.forEach(function(i){
                        $("#assignees option").each(function(index, selected){
                            if($(selected).attr("role") == "user" && selected.value == i){
                                $(selected).prop("selected", true);
                            }
                        })
                    })
                    $("#element-note").val(note);                    
                }
                $("#dialog-element-note").attr("note_id", _note_id);
                $("#dialog-element-note").attr("form_id", _form_id);
                $("#dialog-element-note").attr("element_id", _element_id);
                $("#dialog-element-note").attr("company_id", _company_id);
                $("#dialog-element-note").dialog('open');
    		},
    		complete:function(){
    			
    		}
    	});		
    });

	$(document).on('click', 'img.assigned-note', function(event){
		var _selector = $(this);
    	var _form_id = _selector.attr('form-id');
    	var _element_id = _selector.attr('element-id');
    	var _assignee_id = _selector.attr('assignee-id');
    	var _role = _selector.attr('role');
    	$("#tbody-assigned-note").empty();
    	$.ajax({
    		url:"processupload.php",
    		type:"POST",
    		data:{mode:"getassignednotes", form_id:_form_id, element_id:_element_id, assignee_id: _assignee_id, role: _role},
    		beforeSend: function(){},
    		success: function(response){
    			var notes_html = "";
    			if(response != "No element note found") {                    
                    var notes = JSON.parse(response).notes;                    
                    if(notes.length > 0) {
                    	for(var i = 0; i<notes.length; i++) {
                            var avatar_ele = "";
                            if(notes[i]["avatar_url"] != "") {
                                avatar_ele = "<img style='width:50px;border-radius:50%;' src='"+notes[i]["avatar_url"]+"'>";
                            }
                    		if(i % 2 == 0){
                    			if(notes[i]["status"] == 0) {
	                    			notes_html += "<tr><td>"+notes[i]["note_id"]+"</td><td>"+notes[i]["note"]+"</td><td>"+avatar_ele+"<div>"+notes[i]["assigner"]+"</div></td><td><img src='images/downarrow-grayx16.png'></td><td><img class='img-delete-note' note-id='"+notes[i]["note_id"]+"' src='images/navigation/ED1C2A/16x16/Delete.png' style='cursor:pointer;'></td></tr>";
	                    		} else if(notes[i]["status"] > 2) {
	                    			notes_html += "<tr><td>"+notes[i]["note_id"]+"</td><td>"+notes[i]["note"]+"</td><td>"+avatar_ele+"<div>"+notes[i]["assigner"]+"</div></td><td><img src='images/downarrow-redx16.png'></td><td><img class='img-delete-note' note-id='"+notes[i]["note_id"]+"' src='images/navigation/ED1C2A/16x16/Delete.png' style='cursor:pointer;'></td></tr>";
	                    		} else {
	                    			notes_html += "<tr><td>"+notes[i]["note_id"]+"</td><td>"+notes[i]["note"]+"</td><td>"+avatar_ele+"<div>"+notes[i]["assigner"]+"</div></td><td><img src='images/downarrow-greenx16.png'></td><td><img class='img-delete-note' note-id='"+notes[i]["note_id"]+"' src='images/navigation/ED1C2A/16x16/Delete.png' style='cursor:pointer;'></td></tr>";
	                    		}
                    		} else {
                    			if(notes[i]["status"] == 0) {
	                    			notes_html += "<tr class='alt'><td>"+notes[i]["note_id"]+"</td><td>"+notes[i]["note"]+"</td><td>"+avatar_ele+"<div>"+notes[i]["assigner"]+"</div></td><td><img src='images/downarrow-grayx16.png'></td><td><img class='img-delete-note' note-id='"+notes[i]["note_id"]+"' src='images/navigation/ED1C2A/16x16/Delete.png' style='cursor:pointer;'></td></tr>";
	                    		} else if(notes[i]["status"] > 2) {
	                    			notes_html += "<tr class='alt'><td>"+notes[i]["note_id"]+"</td><td>"+notes[i]["note"]+"</td><td>"+avatar_ele+"<div>"+notes[i]["assigner"]+"</div></td><td><img src='images/downarrow-redx16.png'></td><td><img class='img-delete-note' note-id='"+notes[i]["note_id"]+"' src='images/navigation/ED1C2A/16x16/Delete.png' style='cursor:pointer;'></td></tr>";
	                    		} else {
	                    			notes_html += "<tr class='alt'><td>"+notes[i]["note_id"]+"</td><td>"+notes[i]["note"]+"</td><td>"+avatar_ele+"<div>"+notes[i]["assigner"]+"</div></td><td><img src='images/downarrow-greenx16.png'></td><td><img class='img-delete-note' note-id='"+notes[i]["note_id"]+"' src='images/navigation/ED1C2A/16x16/Delete.png' style='cursor:pointer;'></td></tr>";
	                    		}
                    		}                    		                   		
                    	}
                    } else {
                    	notes_html +="<tr><td colspan='5'>You have no field notes assgined to you.</td></tr>";
                    }                                      
                } else {
                	notes_html +="<tr><td colspan='5'>You have no field notes assgined to you.</td></tr>";
                }
                $("#tbody-assigned-note").append(notes_html);
                $("#dialog-assigned-note").dialog('open');
    		},
    		complete:function(){
    			
    		}
    	});	
		$("#dialog-assigned-note").dialog('open');
	});

	$(document).on('click', 'img.img-delete-note', function(event){
		var _selector = $(this);
		var note_id = $(this).attr("note-id");
		if(confirm("Are you sure you want to delete this note?")) {
			$.ajax({
	            url:"processupload.php",
	            type:"POST",
	            data:{
	                mode:"clearnote",
	                note_id: note_id
	            },
	            beforeSend: function(){},
	            success: function(response){
	                if(response == "deleted") {
	                    _selector.parent().parent().remove();
	                    if($("#tbody-assigned-note tr").length == 0) {
	                    	$("#tbody-assigned-note").append("<tr><td colspan='5'>You have no field notes assgined to you.</td></tr>");
	                    }
	                }
	            },
	            complete:function(){
	                
	            }
	        });
		}
	});

    $("#dialog-select-error-message").dialog({
        modal: true,
        autoOpen: false,
        closeOnEscape: false,
        width: 550,
        draggable: false,
        resizable: false,
        buttons: [
            {
                text: 'Ok',
                id: 'btn-error-message-ok',
                'class': 'bb_button bb_small bb_green',
                click: function() {
                    $(this).dialog('close');
                }
            }
        ]
    });

    $("#dialog-element-note").dialog({
        modal: true,
        autoOpen: false,
        closeOnEscape: false,
        width: 700,
        draggable: false,
        resizable: false,
        open: function(){
            $("#btn-element-note-save").blur();
        },
        buttons: [{
                text: 'Save Note',
                id: 'btn-element-note-save',
                'class': 'bb_button bb_small bb_green',
                click: function() {
                    var save_flag = true;
                    var assignees = "";
                    var admin = [];
                    var entity = [];
                    var user = [];
                    var select = $("#assignees");
                    var options = select[0].options;
                    var dialog = $(this);
                    if(options.selectedIndex == -1){
                        alert("Please select assignees.");
                        save_flag = false;
                    } else {
                        for (var i=0, iLen=options.length; i<iLen; i++) {
                            opt = options[i];
                            if (opt.selected) {
                                if($(opt).attr("role") == "admin"){
                                    admin.push(opt.value);
                                } else if($(opt).attr("role") == "entity") {
                                    entity.push(opt.value);
                                } else if($(opt).attr("role") == "user") {
                                    user.push(opt.value);
                                }
                            }
                        }
                    }

                    if($('#element-note').val() == "") {
                        alert("Please type a note.");
                        save_flag = false;
                    }
                    if(save_flag){
                        var form_id = $("#dialog-element-note").attr("form_id");
                        var element_id = $("#dialog-element-note").attr("element_id");
                        var company_id = $("#dialog-element-note").attr("company_id");
                        var note = $("#element-note").val();
                        var assignees = admin.toString()+";"+entity.toString()+";"+user.toString();

                        $.ajax({
                            url:"processupload.php",
                            type:"POST",
                            data:{
                                mode:"updatenote",
                                form_id: form_id,
                                element_id: element_id,
                                company_id: company_id,
                                note: note,
                                assignees: assignees
                            },
                            beforeSend: function(){},
                            success: function(response){
                                if(response == "green") {
                                    $("#img-"+form_id+"-"+element_id).attr("src", "images/downarrow-greenx16.png");
                                }
                                dialog.dialog('close');
                            },
                            complete:function(){
                                
                            }
                        });
                    }
                }
            },
            {
                text: 'Clear Note',
                id: 'btn-element-note-delete',
                'class': 'bb_button bb_small bb_green',
                click: function() {
                    var note_id = $("#dialog-element-note").attr("note_id");
                    var dialog = $(this);
                    if(note_id == 0) {
                        alert("No note exists.")
                    } else {
                        if(confirm("Are you sure you want to delete this note?")) {
                            $.ajax({
                                url:"processupload.php",
                                type:"POST",
                                data:{
                                    mode:"clearnote",
                                    note_id: note_id
                                },
                                beforeSend: function(){},
                                success: function(response){
                                    if(response == "deleted") {
                                        var form_id = $("#dialog-element-note").attr("form_id");
                                        var element_id = $("#dialog-element-note").attr("element_id");
                                        $("#img-"+form_id+"-"+element_id).attr("src", "images/downarrow-grayx16.png");
                                    }
                                    dialog.dialog('close');
                                },
                                complete:function(){
                                    
                                }
                            });
                        }                        
                    }
                }
            },
            {
                text: 'Close',
                id: 'btn-element-note-cancel',
                'class': 'btn_secondary_action',
                click: function() {
                    $(this).dialog('close');
                }
            }]
    });
	
	$("#dialog-assigned-note").dialog({
        modal: true,
        autoOpen: false,
        closeOnEscape: false,
        width: 750,
        draggable: false,
        resizable: false,
        buttons: [            
            {
                text: 'Close',
                'class': 'bb_button bb_small bb_green',
                click: function() {
                    $(this).dialog('close');
                }
            }]
    });

    $("#dialog-file-management").dialog({
        modal: true,
        autoOpen: false,
        closeOnEscape: false,
        width: 1200,
        draggable: false,
        resizable: false,
        buttons: [{
            text: 'Save',
            id: 'btn-selected-files-save',
            'class': 'bb_button bb_small bb_green',
            click: function() {
                var selected_rows = existing_files_table.rows({ selected: true })[0];
                if(selected_rows.length != 0) {
                    $.each(selected_rows, function(i, e) {
                        var row = existing_files_table.rows(e).nodes()[0];
                        var new_file = $(row).attr("file_path") + "*" + $(row).attr("file_name");
                        var file_target_id = "#" + $("#file_target_id").val();
                        var selected_files = $(file_target_id).val().split("|");
                        //check if a file has already been selected
                        if($.inArray(new_file, selected_files) == -1) {
                            selected_files.push(new_file);
                            $(file_target_id).val(selected_files.filter(function(a){return a !== ""}).join("|"));
                            //add file to file_queue
                            var queue_target_id = file_target_id.replace("selected_existing_files", "queue");
                            var new_queue_html = "<div class=\"uploadifive-queue-item complete\"><a class=\"close remove-selected-file\" file_target_id='"+file_target_id+"' new_file='"+new_file+"'><img border=\"0\" src=\"images/icons/delete.png\"></a><div><span class=\"filename\"><img align=\"absmiddle\" class=\"file_attached\" src=\"images/icons/attach.gif\">"+$(row).attr("file_name")+"</span></div>";
                            $(queue_target_id).append(new_queue_html);
                        }
                    })
                }
                var selected_rows = generated_reports_table.rows({ selected: true })[0];
                if(selected_rows.length != 0) {
                    $.each(selected_rows, function(i, e) {
                        var row = generated_reports_table.rows(e).nodes()[0];
                        var new_file = $(row).attr("file_path") + "*" + $(row).attr("file_name");
                        var file_target_id = "#" + $("#file_target_id").val();
                        var selected_files = $(file_target_id).val().split("|");
                        //check if a file has already been selected
                        if($.inArray(new_file, selected_files) == -1) {
                            selected_files.push(new_file);
                            $(file_target_id).val(selected_files.filter(function(a){return a !== ""}).join("|"));
                            //add file to file_queue
                            var queue_target_id = file_target_id.replace("selected_existing_files", "queue");
                            var new_queue_html = "<div class=\"uploadifive-queue-item complete\"><a class=\"close remove-selected-file\" file_target_id='"+file_target_id+"' new_file='"+new_file+"'><img border=\"0\" src=\"images/icons/delete.png\"></a><div><span class=\"filename\"><img align=\"absmiddle\" class=\"file_attached\" src=\"images/icons/attach.gif\">"+$(row).attr("file_name")+"</span></div>";
                            $(queue_target_id).append(new_queue_html);
                        }
                    })
                }
                $(this).dialog('close');
            }
        },
        {
            text: 'Close',
            'class': 'bb_button bb_small bb_green',
            id: 'btn-selected-files-cancel',
            click: function() {
                $(this).dialog('close');
            }
        }]
    });

    $(document).on('click', '.btn-select-file-management', function(e) {
        e.preventDefault();
        that = $(this);
        if(get_existing_files_flag) {
            existing_files_table.rows().deselect();
            generated_reports_table.rows().deselect();
            $("#file_target_id").val($(that).attr("target_id"));
            $("#dialog-file-management").dialog('open');
        } else {
            $('#processing-dialog-file').dialog('open');
            $.ajax({
                url:"ajax-requests.php",
                type:"POST",
                data:{action:"get_uploaded_files"},
                cache: false,
                dataType: "json",
                error: function(xhr,text_status,e) {
                    $('#processing-dialog-file').dialog('close');
                    $("#error-message").html("Something went wrong. Please try again later.");
                    $("#dialog-select-error-message").dialog("open");
                },
                success: function(response_data){
                    if(response_data.status == "ok") {
                        get_existing_files_flag = true;
                        var uploaded_files = response_data.uploaded_files;
                        if(uploaded_files.length > 0) {
                            existing_files_table.clear();
                            var table_rows = "";
                            var i = 0;
                            uploaded_files.forEach(function(file){
                                i++;
                                table_rows += '<tr file_path="' + file['file_path'] + '" file_name="' + file["display_filename"] + '">';
                                table_rows += '<td></td>';
                                table_rows += '<td>'+ i +'</td>';
                                table_rows += '<td>' + file["form_id"] + '</td>';
                                table_rows += '<td>' + file["form_name"] + '</td>';
                                table_rows += '<td>' + file["element_title"] + '</td>';
                                table_rows += '<td><a class="entry_link entry-link-preview" href="#" data-identifier="' + file["data_identifier"] + '" data-ext="' + file["file_ext"] + '" data-src="' + file["q_string"] + '">' + file["display_filename"] + '</a></td>';
                                table_rows += '<td><a target="_blank" href="' + file["field_link"] + '">Go To Field</a></td>';
                                table_rows += '</tr>';
                            })
                            existing_files_table.rows.add($(table_rows)).draw();
                        }

                        var generated_documents = response_data.generated_documents;
                        if(generated_documents.length > 0) {
                            generated_reports_table.clear();
                            var table_rows = "";
                            var i = 0;
                            generated_documents.forEach(function(file){
                                i++;
                                table_rows += '<tr file_path="' + file['file_path'] + '" file_name="' + file["display_filename"] + '">';
                                table_rows += '<td></td>';
                                table_rows += '<td>'+ i +'</td>';
                                table_rows += '<td>' + file["form_id"] + '</td>';
                                table_rows += '<td>' + file["form_name"] + '</td>';
                                table_rows += '<td>' + file["report_for"] + '</td>';
                                table_rows += '<td><a class="entry_link entry-link-preview" href="#" data-identifier="' + file["data_identifier"] + '" data-ext="' + file["file_ext"] + '" data-src="' + file["q_string"] + '">' + file["display_filename"] + '</a></td>';
                                table_rows += '</tr>';
                            })
                            generated_reports_table.rows.add($(table_rows)).draw();
                        }

                        $('#processing-dialog-file').dialog('close');
                        $("#file_target_id").val($(that).attr("target_id"));
                        $(".tab-panel-header .actions").find("button.btn-activity").removeClass("active");
                        $(".tab-panel-header .actions").find("button.btn-activity").first().addClass("active");
                        $("#div_uploaded_files").css("display", "block");
                        $("#dialog-file-management").dialog('open');
                    } else {
                        $('#processing-dialog-file').dialog('close');
                        $("#error-message").html("Something went wrong. Please try again later.");
                        $("#dialog-select-error-message").dialog("open");
                    }
                }
            });
        }
    });

	$(document).on('click', 'img.status-icon-action', function(){
		var _selector = $(this);
		var form_id = _selector.attr('data-form_id');
		var element_id = _selector.attr('data-element_id');
		var company_id = _selector.attr('data-company_id');
        var entry_id = _selector.attr('data-entry_id');
		var indicator = _selector.attr('data-indicator');
		
		$.ajax({
			url:"processupload.php",
			type:"POST",
			data:{mode:"updateindicator", form_id:form_id, element_id:element_id, company_id:company_id, entry_id:entry_id, indicator:indicator},
			beforeSend: function(){},
			success: function(response){
				response = JSON.parse(response);
				_selector.attr('src', 'images/Circle_'+response.status_icon+'.png');
				_selector.attr('data-indicator', response.indicator);
                if(response.indicator == 3) {
                    $("#"+form_id+"_"+element_id+"_status_preserve_green").val("3");
                }
			}
		});
	});
});