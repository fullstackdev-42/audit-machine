$(document).ready(function() {
	$('.accordion').click(function(){
		_selector = $(this);
		_selector.toggleClass('active');
		_selector.next('div.panel-accordion').slideToggle();
	});
	var state_code = get_state_abbrvs();

	// convert RGB color to HEX color
	function RGBToHex(r,g,b) {
	  	r = r.toString(16);
		g = g.toString(16);
		b = b.toString(16);

		if (r.length == 1)
			r = "0" + r;
		if (g.length == 1)
			g = "0" + g;
		if (b.length == 1)
			b = "0" + b;

		return "#" + r + g + b;
	}

	function getGreenToRedGradientByValue(params) {
		var r = Math.round((255*Math.round(params.current_value))/Math.round(params.max_value)).toString(16);
		var g = Math.round(255*(Math.round(params.max_value)-(Math.round(params.current_value)))/Math.round(params.max_value)).toString(16);
		var b = '00';
		
		r = r.length == 1 ? '0'+r : r;
		g = g.length == 1 ? '0'+g : g;
		b = b.length == 1 ? '0'+b : b;
		
		return ('#'+r+g+b).toUpperCase();
	};
	
	function inArray(needle, haystack) {
		var length = haystack.length;
		for(var i = 0; i < length; i++) {
			if(haystack[i] == needle) return true;
		}
		return false;
	}

	function compare( a, b ) {
	if ( a.name < b.name ){
		return -1;
	}
	if ( a.name > b.name ){
		return 1;
	}
		return 0;
	}

	var statusColor = new Array("#505356", "#F95360", "#F2B604", "#33BF8C");
	var statusName = new Array("Pending", "In Remediation", "In Progress", "Compliant");
	
	var colors = new Array();
	
	for(i=1;i<=100;i++){
		colors.push(getGreenToRedGradientByValue({current_value:i,max_value:100}));
	}
	
	Highcharts.setOptions({
		colors:colors
	});

	$("#processing-dialog-file").dialog({
		modal: true,
		autoOpen: false,
		closeOnEscape: false,
		width: 400,
		draggable: false,
		resizable: false
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

	$.fn.dataTable.ext.classes.sPageButton = 'data-table-custom-pagination';
	
	var field_note_table = $("#field_note_report").DataTable({
		dom: 'Bfrtip',
		pageLength: 20,
		sPaginationType: "numbers",
		buttons: [
			{
				extend: 'csvHtml5',
				text: 'Save as CSV',
				filename: 'Field note report',
				title: 'Field note report',
				className: 'bb_button bb_small bb_green',
				exportOptions: {
					columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
					orthogonal: {
						display: ':null'
					},
					modifier: {
						order: 'current',
						page: 'all',
						selected: null,
					},
					format: {
						body: function ( data, row, column, node ) {
							var val = "";
							if(column == 8) {
								val = window.location.origin + $(node).find("a").attr("href");
							} else {
								val = $(node).text();
							}
							return val;
						}
					}
				}
			},
			{
				extend: 'excelHtml5',
				text: 'Save as Excel',
				filename: 'Field note report',
				title: 'Field note report',
				className: 'bb_button bb_small bb_green',
				exportOptions: {
					columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
					orthogonal: {
						display: ':null'
					},
					modifier: {
						order: 'current',
						page: 'all',
						selected: null,
					},
					format: {
						body: function ( data, row, column, node ) {
							var val = "";
							if(column == 8) {
								val = window.location.origin + $(node).find("a").attr("href");
							} else {
								val = $(node).text();
							}
							return val;
						}
					}
				}
			},
			{
				extend: 'pdfHtml5',
				text: 'Save as PDF',
				filename: 'Field note report',
				title: 'Field note report',
				orientation: 'landscape',
				pageSize: 'TABLOID',
				className: 'bb_button bb_small bb_green',
				exportOptions: {
					columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
					orthogonal: {
						display: ':null'
					},
					modifier: {
						order: 'current',
						page: 'all',
						selected: null,
					},
					format: {
						body: function ( data, row, column, node ) {
							var val = "";
							if(column == 8) {
								val = window.location.origin + $(node).find("a").attr("href");
							} else {
								val = data.replace(/<[^>]*>/g, "\n");
							}
							return val;
						}
					}
				},
				customize: function ( doc ) {
					var pdf_header_img = $("#pdf_header_img").val();
					doc.defaultStyle.alignment = 'center';
					doc.content.splice( 0, 0, {
						margin: [ 0, 0, 20, 20 ],
						alignment: 'left',
						image: pdf_header_img
					});
					doc.content[2].layout = "Borders";
				}
			}
		],
		order: [[0, 'asc']]
	});

	var artifact_management_table = $("#artifact-management-table").DataTable({
		dom: 'Bfrtip',
		pageLength: 20,
		sPaginationType: "numbers",
		buttons: [
			{
				extend: 'csvHtml5',
				text: 'Save as CSV',
				filename: "Artifact management report",
				title: "Artifact management report",
				className: 'bb_button bb_small bb_green',
				exportOptions: {
					orthogonal: {
						display: ':null'
					},
					modifier: {
						order: 'current',
						page: 'all',
						selected: null,
					},
					format: {
						body: function ( data, row, column, node ) {
							var val = "";
							if(column == 6) {
								val = window.location.origin + "/" + $(node).find("a").attr("href");
							} else {
								val = $(node).text();
							}
							return val;
						}
					}
				}
			},
			{
				extend: 'excelHtml5',
				text: 'Save as Excel',
				filename: "Artifact management report",
				title: "Artifact management report",
				className: 'bb_button bb_small bb_green',
				exportOptions: {
					orthogonal: {
						display: ':null'
					},
					modifier: {
						order: 'current',
						page: 'all',
						selected: null,
					},
					format: {
						body: function ( data, row, column, node ) {
							var val = "";
							if(column == 6) {
								val = window.location.origin + "/" + $(node).find("a").attr("href");
							} else {
								val = $(node).text();
							}
							return val;
						}
					}
				}
			},
			{
				extend: 'pdfHtml5',
				text: 'Save as PDF',
				filename: "Artifact management report",
				title: "Artifact management report",
				orientation: 'landscape',
				pageSize: 'TABLOID',
				className: 'bb_button bb_small bb_green',
				exportOptions: {
					orthogonal: {
						display: ':null'
					},
					modifier: {
						order: 'current',
						page: 'all',
						selected: null,
					},
					format: {
						body: function ( data, row, column, node ) {
							var val = "";
							if(column == 6) {
								val = window.location.origin + "/" + $(node).find("a").attr("href");
							} else {
								val = $(node).text();
							}
							return val;
						}
					}
				},
				customize: function ( doc ) {
					var pdf_header_img = $("#pdf_header_img").val();
					doc.defaultStyle.alignment = 'center';
					doc.content.splice( 0, 0, {
						margin: [ 0, 0, 20, 20 ],
						alignment: 'left',
						image: pdf_header_img
					});
					doc.content[2].layout = "Borders";
				}
			}
		],
		order: [[0, 'asc']]
	});

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
			$('#processing-dialog-file').dialog('open');

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
					$('#processing-dialog-file').dialog('close');
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
						$('#processing-dialog-file').dialog('close');
						$('#document-preview').dialog('open');
						$('#file_viewer_download_button').attr('href', response.download_src);

					} else {
						$('#processing-dialog-file').dialog('close');
						$('#document-preview-content').html('Error Occurred while requesting. Please try again later.');
						$('#document-preview').dialog('open');
					}
				}
			});
		}
	});

	$(".display-chart").each(function(index, element) {
		
		var _selector = $(element);
		var _subtitle = _selector.attr("report-name");
		var report_type = _selector.attr("report-type");
		var chart_type = "";
		var _xAxisArr = new Array();
		var _xAxisAreaArr = new Array();
		var _series = new Array();
		var _seriesCombination = new Array();
		var _lineseries = new Array();
		var _pieseries = new Array();
		var _areaseries = new Array();
		var _3dseries = new Array();
		var _scatterseries = new Array();
		var _combinationseries = new Array();
		var _tmplineData = new Array();
		var _tmppieData = new Array();
		var _tmpareaData = new Array();
		var _tmp3dData = new Array();
		var _tmpscatterDataValue = new Array();
		var _bubbleseries = new Array();
		var _tmpbubbleseries = new Array();
		var _tmpbubbleDataValue = new Array();
		var _tmpsunburstData = new Array();
		var _tmpsunburstInteractData = new Array();
		var _tmppolarData = new Array();
		var _statedataArr = new Array();
		var _stateName = "";
		var is_status_report = false;

		if(report_type == "status-indicator") {
			chart_type = _selector.attr("chart-type");
			if(chart_type == "sunburst_chart") {
				var form_id = _selector.attr("form-id");
				var _tmpsunburstData = [
					{
						id: "status" + form_id,
						parent: "",
						name: "Total"
					},
					{
						id: "status" + form_id + "gray",
						parent: "status" + form_id,
						name: statusName["0"],
						color: statusColor["0"]
					},
					{
						id: "status" + form_id + "red",
						parent: "status" + form_id,
						name: statusName["1"],
						color: statusColor["1"]
					},
					{
						id: "status" + form_id + "yellow",
						parent: "status" + form_id,
						name: statusName["2"],
						color: statusColor["2"]
					},
					{
						id: "status" + form_id + "green",
						parent: "status" + form_id,
						name: statusName["3"],
						color: statusColor["3"]
					}
				];
				//display the interact chart with green indicators by default
				var _tmpsunburstInteractData = [
					{
						id: "status" + form_id + "green",
						parent: "",
						name: statusName["3"],
						color: statusColor["3"]
					}
				];
				var status_data = JSON.parse(_selector.attr("status-data"));
				
				$.each(status_data, function(ind, val){
					if(val["status"] == 0) {
						_tmpsunburstData.push({
							id: ind,
							parent: "status" + form_id + "gray",
							name: val["label"],
							field_link: val["field_link"],
							value: 1,
							color: "#505356"
						});
					} else if(val["status"] == 1) {
						_tmpsunburstData.push({
							id: ind,
							parent: "status" + form_id + "red",
							name: val["label"],
							field_link: val["field_link"],
							value: 1,
							color: "#F95360"
						});
					} else if(val["status"] == 2) {
						_tmpsunburstData.push({
							id: ind,
							parent: "status" + form_id + "yellow",
							name: val["label"],
							field_link: val["field_link"],
							value: 1,
							color: "#F2B604"
						});
					} else if(val["status"] == 3) {
						_tmpsunburstData.push({
							id: ind,
							parent: "status" + form_id + "green",
							name: val["label"],
							field_link: val["field_link"],
							value: 1,
							color: "#33BF8C"
						});
						_tmpsunburstInteractData.push({
							id: ind,
							parent: "status" + form_id + "green",
							name: val["label"],
							field_link: val["field_link"],
							value: 1,
							color: "#33BF8C"
						});
					}						
				})
				// Splice in transparent for the center circle
				Highcharts.getOptions().colors.splice(0, 0, 'transparent');
				_selector.highcharts({
				    chart: {
				        height: '500px'
				    },
				    title: {
						text: _subtitle
					},
				    series: [{
				        type: "sunburst",
				        data: _tmpsunburstData,
				        allowDrillToNode: false,
				        cursor: 'pointer',
				        events: {
					    	click: function(event) {
					    		if(event.point.node.level == 3) {
					    			window.location.href = event.point.field_link;
					    		} else if(event.point.node.level == 2) {
					    			var interact_table_title = "";
					    			switch(event.point.node.name) {
					    				case "Compliant":
					    					var _tmpsunburstInteractData = [
												{
													id: "status" + form_id + "green",
													parent: "",
													name: statusName["3"],
													color: statusColor["3"]
												}
											];
											$.each(status_data, function(ind, val){
												if(val["status"] == 3) {
													_tmpsunburstInteractData.push({
														id: ind,
														parent: "status" + form_id + "green",
														name: val["label"],
														field_link: val["field_link"],
														value: 1,
														color: "#33BF8C"
													});
												}
											});
											interact_table_title = "Compliant " + _subtitle;
											break;
										case "Pending":
					    					var _tmpsunburstInteractData = [
												{
													id: "status" + form_id + "gray",
													parent: "",
													name: statusName["0"],
													color: statusColor["0"]
												}
											];
											$.each(status_data, function(ind, val){
												if(val["status"] == 0) {
													_tmpsunburstInteractData.push({
														id: ind,
														parent: "status" + form_id + "gray",
														name: val["label"],
														field_link: val["field_link"],
														value: 1,
														color: "#505356"
													});
												}
											});
											interact_table_title = "Pending " + _subtitle;
											break;
										case "In Progress":
											var _tmpsunburstInteractData = [
												{
													id: "status" + form_id + "yellow",
													parent: "",
													name: statusName["2"],
													color: statusColor["2"]
												}
											];
											$.each(status_data, function(ind, val){
												if(val["status"] == 2) {
													_tmpsunburstInteractData.push({
														id: ind,
														parent: "status" + form_id + "yellow",
														name: val["label"],
														field_link: val["field_link"],
														value: 1,
														color: "#F2B604"
													});
												}
											});
											interact_table_title = "In Progress " + _subtitle;
											break;
										case "In Remediation":
											var _tmpsunburstInteractData = [
												{
													id: "status" + form_id + "red",
													parent: "",
													name: statusName["1"],
													color: statusColor["1"]
												}
											];
											$.each(status_data, function(ind, val){
												if(val["status"] == 1) {
													_tmpsunburstInteractData.push({
														id: ind,
														parent: "status" + form_id + "red",
														name: val["label"],
														field_link: val["field_link"],
														value: 1,
														color: "#F95360"
													});
												}
											});
											interact_table_title = "In Remediation " + _subtitle;
											break;											
					    			}
					    			$("#chart_interact_"+form_id).highcharts({
									    chart: {
									        height: '500px'
									    },
									    title: {
											text: interact_table_title
										},
									    series: [{
									        type: "sunburst",
									        data: _tmpsunburstInteractData,
									        allowDrillToNode: false,
									        cursor: 'pointer',
									        events: {
										    	click: function(event) {
										    		if(event.point.node.level == 2) {
										    			window.location.href = event.point.field_link;
										    		}
										    	}
										    },
									        dataLabels: {
									            format: '{point.name}',
									            rotationMode: 'circular'
									        },
									        levels: [{
									            level: 1,
									            levelIsConstant: false,
									        }, {
									            level: 2,
									            colorByPoint: true
									        }]
									    }],
									    tooltip: {
									        formatter: function () {
										        if (this.point.node.level != 2) {
										            return 'Number of <b>' + this.point.name + '</b> Status Indicators is <b>' + this.point.value + '</b>';
										        } else {
										        	return 'Field Label: <b>' + this.point.name + '</b>';
										        }
										    }
									    }
									});
					    		}
					    	}
					    },
				        dataLabels: {
				            format: '{point.name}',
				            rotationMode: 'circular'
				        },
				        levels: [{
				            level: 1,
				            levelIsConstant: false,
				        }, {
				            level: 2,
				            colorByPoint: true
				        },
				        {
				            level: 3,
				            colorByPoint: true
				        }]
				    }],
				    tooltip: {
				        formatter: function () {
					        if (this.point.node.level != 3) {
					            return 'Number of <b>' + this.point.name + '</b> Status Indicators is <b>' + this.point.value + '</b>';
					        } else {
					        	return 'Field Label: <b>' + this.point.name + '</b>';
					        }
					    }
				    }
				});
				$("#chart_interact_"+form_id).highcharts({
				    chart: {
				        height: '500px'
				    },
				    title: {
						text: "Compliant " + _subtitle
					},
				    series: [{
				        type: "sunburst",
				        data: _tmpsunburstInteractData,
				        allowDrillToNode: false,
				        cursor: 'pointer',
				        events: {
					    	click: function(event) {
					    		if(event.point.node.level == 2) {
					    			window.location.href = event.point.field_link;
					    		}
					    	}
					    },
				        dataLabels: {
				            format: '{point.name}',
				            rotationMode: 'circular'
				        },
				        levels: [{
				            level: 1,
				            levelIsConstant: false,
				        }, {
				            level: 2,
				            colorByPoint: true
				        }]
				    }],
				    tooltip: {
				        formatter: function () {
					        if (this.point.node.level != 2) {
					            return 'Number of <b>' + this.point.name + '</b> Status Indicators is <b>' + this.point.value + '</b>';
					        } else {
					        	return 'Field Label: <b>' + this.point.name + '</b>';
					        }
					    }
				    }
				});
			} else if(chart_type == "polar_chart"){
				var form_id = _selector.attr("form-id");
				_tmpwindroseData = new Array();
				_tmppolarData = [[], [], [], []];
				var status_data = JSON.parse(_selector.attr("status-data"));
				$.each(status_data, function(ind, val){
					var _tmpName = "";
					var _tmpColor = "";
					if(val["status"] == "0") {
						_tmpName = statusName["0"];
						_tmpColor = statusColor["0"];
					} else if(val["status"] == "1") {
						_tmpName = statusName["1"];
						_tmpColor = statusColor["1"];
					} else if(val["status"] == "2") {
						_tmpName = statusName["2"];
						_tmpColor = statusColor["2"];
					} else if(val["status"] == "3") {
						_tmpName = statusName["3"];
						_tmpColor = statusColor["3"];
					}
					_tmpwindroseData.push({
						y: 1,
						name: _tmpName,
						field_link: val["field_link"],
						color: _tmpColor,
						field_name: val["label"]
					});
					_tmppolarData[val["status"]].push({
						y: Math.floor(Math.random() * 10) + 1,
						name: val["label"],
						field_link: val["field_link"]
					});
				});
				_tmpwindroseData.sort(compare);
				//display the windrose chart on the left side
				_selector.highcharts({
					chart: {
				        polar: true,
				        type: 'column',
				        height: '500px'
				    },
				    accessibility: {
						announceNewData: {
							enabled: true
						}
					},
				    title: {
				        text: _subtitle
				    },
				    pane: {
				        size: '85%'
				    },
				    legend: {
				        enabled: false,
				    },
				    xAxis: {
				        tickmarkPlacement: 'on',
				        type: 'category',
				        labels: {
				        	formatter: function() {
				        		return this.value;
				        	}
				        }
				    },
				    yAxis: {
				        min: 0,
				        endOnTick: false,
				        showLastLabel: true,
				        title: {
				            text: ''
				        },
				        labels: {
				            formatter: function () {
				                return this.value;
				            }
				        },
				        reversedStacks: false
				    },
				    tooltip: {
				    	formatter: function() {
				    		return 'Status: <b>' + this.key + '</b><br>Field Label: <b>' + this.point.options.field_name;
				    	}
				    },
				    plotOptions: {
				        series: {
				            shadow: false,
				            groupPadding: 0,
				            pointPlacement: 'on',
				            events: {
						    	click: function(event) {
						    		window.location.href = event.point.field_link;
						    	}
						    }
				        },
				        column: {
							stacking: 'normal',
							dataLabels: {
								enabled: false
							}
						}
				    },
				    series: [{
				    	name: "Status Indicators",
						colorByPoint: true,
						data: _tmpwindroseData
				    }]
				});
				//display the polar chart on the right side
				$("#chart_interact_"+form_id).highcharts({
				    chart: {
				        polar: true,
				        height: '500px'
				    },
				    title: {
						text: _subtitle
					},
				    pane: {
				        startAngle: 0,
				        endAngle: 360
				    },
				    xAxis: {
				        tickInterval: 90,
				        min: 0,
				        max: 360,
				        labels: {
				            enabled: false
				        }
				    },
				    yAxis: {
				        min: 0,
				        labels: {
				        	enabled: false
				        }
				    },
				    plotOptions: {
				        series: {
				            pointStart: 0
				        },
				        column: {
				            pointPadding: 0,
				            groupPadding: 0
				        }
				    },
				    series: [{
				        type: 'column',
				        name: statusName["0"]+': '+_tmppolarData[0].length,
				        color: statusColor["0"],
				        data: _tmppolarData[0],
				        pointStart: 0,
				        pointInterval: 90/(_tmppolarData[0].length),
				        tooltip:{
				        	headerFormat: "",
				        	pointFormatter: function () {				        		
				          		return 'Field Label: <b>' + this.name + '</b>';
				          	}
				        },
				        events:{
				        	click: function(){
				        		window.location.href = event.point.field_link;
				        	}
				        }
				    }, {
				        type: 'column',
				        name: statusName["1"]+': '+_tmppolarData[1].length,
				        color: statusColor["1"],
				        data: _tmppolarData[1],
				        pointStart: 90,
				        pointInterval: 90/(_tmppolarData[1].length),
				        tooltip:{
				        	headerFormat: "",
				        	pointFormatter: function () {
					          	return 'Field Label: <b>' + this.name + '</b>';
					        }
				        },
				        events:{
				        	click: function(){
				        		window.location.href = event.point.field_link;
				        	}
				        }
				    }, {
				        type: 'column',
				        name: statusName["2"]+': '+_tmppolarData[2].length,
				        color: statusColor["2"],
				        data: _tmppolarData[2],
				        pointStart: 180,
				        pointInterval: 90/(_tmppolarData[2].length),
				        tooltip:{
				        	headerFormat: "",
				        	pointFormatter: function () {
				          		return 'Field Label: <b>' + this.name + '</b>';
				          	}
				        },
				        events:{
				        	click: function(){
				        		window.location.href = event.point.field_link;
				        	}
				        }
				    }, {
				        type: 'column',
				        name: statusName["3"]+': '+_tmppolarData[3].length,
				        color: statusColor["3"],
				        data: _tmppolarData[3],
				        pointStart: 270,
				        pointInterval: 90/(_tmppolarData[3].length),
				        tooltip:{
				        	headerFormat: "",
				        	pointFormatter: function () {
				          		return 'Field Label: <b>' + this.name + '</b>';
				          	}
				        },
				        events:{
				        	click: function(){
				        		window.location.href = event.point.field_link;
				        	}
				        }
				    }]
				});
			} else {
				var data = _selector.attr("data").split(",");
				data.forEach(function(value, ind){
					var _tmpData = new Array();
					_tmpData = {
						name: statusName[ind],
						y: Math.round(value),
						color: statusColor[ind]
					};
					_series.push({
						type: 'column',
						name: statusName[ind],
						data: [_tmpData],
						color: statusColor[ind]
					});
					_combinationseries.push({
						name: statusName[ind],
						y: Math.round(value),
						color: statusColor[ind]
					});
					_xAxisArr.push(statusName[ind]);
					_tmplineData.push({name:statusName[ind],color:statusColor[ind],y:Math.round(value)});
					_tmppieData.push({name:statusName[ind],color:statusColor[ind],y:Math.round(value)});

					_areaseries.push({name:statusName[ind],color:statusColor[ind],y:Math.round(value)});
					_xAxisAreaArr.push(statusName[ind]);				
					_tmp3dData.push({name:statusName[ind],color:statusColor[ind],y:Math.round(value)});				
					_tmpbubbleDataValue.push({name:statusName[ind],color:statusColor[ind],y:Math.round(value)});
				});
				/* column and bar chart */
				if(chart_type == 'column_and_bar_chart'){
		        	_selector.highcharts({
						chart: {
							type: 'column',
							plotBackgroundColor: '#fff', //null
							plotBorderWidth: 1,
							plotShadow: false
						},
						title: {
							text: _subtitle
						},
						exporting:{
							'enabled':false
						},
						xAxis: {
							type: 'category',
							categories: _xAxisArr
						},
						yAxis: {
							min: 0,
							title: {
								text: ''
							},
						},
						tooltip: {
							formatter: function(){
								return _subtitle+'<br>'+'<span><span style="fill:'+this.point.color+';" x="8" dy="16">●</span> '+this.x+' - '+this.y+'</span>';
							},
						},
						plotOptions: {
							bar: {
								dataLabels: {
									enabled: true
								}
							},
							column: {
								pointWidth: 60
							}
						},
						legend: {
							enabled:false
						},				
						series: _series
					});
				}
				/* column and bar chart */

				/* line chart */
				if(chart_type == 'line_chart'){
					_lineseries.push({
						name: 'score',
						data: _tmplineData
					});
		        	_selector.highcharts({
						title: {
							text: _subtitle
						},
						exporting:{
							'enabled':false
						},
						xAxis: {
							categories: _xAxisArr
						},
						yAxis: {
							title: {
								text: ''
							},
							plotLines: [{
								value: 0,
								width: 1,
								color: '#808080'
							}]
						},
						tooltip: {
							formatter: function(){
								return _subtitle+'<br>'+'<span><span style="fill:'+this.point.color+';" x="8" dy="16">●</span> '+this.x+' - '+this.y+'</span>';
							},
						},
						plotOptions: {
							column: {
								pointPadding: 0.2,
								borderWidth: 0
							}
						},
						legend: {
							enabled:false
						},				
						series: _lineseries
					});
				}
				/* line chart */

				/* pie chart */
				if(chart_type == 'pie_chart'){
					_pieseries.push({
						type: 'pie',
						name: _subtitle,
						data: _tmppieData
					});
		        	_selector.highcharts({
						chart: {
							plotBackgroundColor: null,
							plotBorderWidth: 1,//null,
							plotShadow: false
						},
						title: {
							text: _subtitle
						},
						exporting:{
							'enabled':false
						},
						tooltip: {
							formatter: function() {
								return _subtitle+'<br>'+'<span><span style="fill:'+this.point.color+';" x="8" dy="16">●</span> '+this.point.name+' - '+this.point.y+'</span>';
							}
						},
						plotOptions: {
							pie: {
								allowPointSelect: true,
								cursor: 'pointer',
								dataLabels: {
									enabled: true,
									format: '<b>{point.name}: <b>{point.y}</b>',
									style: {
										color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
									}
								}
							}
						},
						series: _pieseries
					});
				}
				/* pie chart */

				/* area chart */
				if(chart_type == 'area_chart'){
		        	_selector.highcharts({
						chart: {
							type: 'area'
						},				
						title: {
							text: _subtitle
						},
						xAxis: {
							categories: _xAxisArr
						},
						exporting:{
							'enabled':false
						},
						tooltip: {
							formatter: function() {
								return _subtitle+'<br>'+'<span><span style="fill:'+this.point.color+';" x="8" dy="16">●</span> '+this.point.name+' - '+this.point.y+'</span>';
							}
						},
						plotOptions: {
							area: {
								marker: {
									enabled: false,
									symbol: 'circle',
									radius: 2,
									states: {
										hover: {
											enabled: true
										}
									}
								}
							}
						},
						legend: {
							enabled:false
						},				
						series: [{data: _areaseries}]
					});
				}
				/* area chart */

				/* 3d chart */
				if(chart_type == '3d_chart'){
					_3dseries.push({
						data: _tmp3dData
					});
		        	_selector.highcharts({
						chart: {
							renderTo: 'container',
							type: 'column',
							margin: 75,
							options3d: {
								enabled: true,
								alpha: 25,
								beta: 5,
								depth: 50,
								viewDistance: 25
							}
						},				
						title: {
							text: _subtitle
						},
						exporting:{
							enabled: false
						},
						plotOptions: {
							column: {
								depth: 25
							}
						},
						xAxis: {
						    categories: _xAxisArr,
						},
						yAxis: {
							title: {
								text: ''
							}
						},
						legend: {
							enabled:false
						},
						tooltip: {
							formatter: function() {
								return _subtitle+'<br>'+'<span><span style="fill:'+this.point.color+';" x="8" dy="16">●</span> '+this.point.name+' - '+this.point.y+'</span>';
							}
						},
						series: _3dseries
					});
				}
				/* 3d chart */

				/* bubble chart */
				if(chart_type == 'bubble_chart'){
					_bubbleseries.push({
						data: _tmpbubbleDataValue
					});
					_selector.highcharts({
						chart: {
							type: 'bubble',
							zoomType: 'xy'
						},				
						title: {
							text: _subtitle
						},
						xAxis: {
						    categories: _xAxisArr
						},
						yAxis: {
							title: {
								text: ''
							}
						},
						tooltip: {
							formatter: function(){
								return _subtitle+'<br>'+'<span><span style="fill:'+this.point.color+';" x="8" dy="16">●</span> '+this.point.name+' - '+this.point.y+'</span>';
							},
						},
						exporting:{
							'enabled':false
						},
						legend: {
							enabled:false
						},
						series: _bubbleseries
					});
				}
				/* bubble chart */

				/* combinations chart */
				if(chart_type == 'combinations'){
					var tmpSeries = new Array();
					tmpSeries.push({
						type: 'spline',
						name: _subtitle,
						data: _tmplineData,
						marker: {
							lineWidth: 2,
							radius:10
						}
					});
					tmpSeries.push({
						type: 'pie',
						name: _subtitle,
						data: _combinationseries,
						center: [100, 80],
						size: 100,
						showInLegend: false,
						dataLabels: {
							enabled: false
						}
					});
		        	_selector.highcharts({
						title: {
							text: _subtitle
						},
						xAxis: {
							categories: _xAxisArr
						},
						yAxis: {
							min: 0,
							title: {
								text: ''
							}
						},
						exporting:{
							'enabled':false
						},
						legend: {
							enabled:false
						},
						tooltip: {
							formatter: function(){
								if(this.series.options.type == "spline") {
									return _subtitle+'<br>'+'<span><span style="fill:'+this.point.color+';" x="8" dy="16">●</span> '+this.x+' - '+this.y+'</span>';
								} else if(this.series.options.type == "pie") {
									return _subtitle+'<br>'+'<span><span style="fill:'+this.point.color+';" x="8" dy="16">●</span> '+this.point.name+' - '+this.point.y+'</span>';
								}
							}
						},
						labels: {
							items: [{
								html: '',
								style: {
									left: '50px',
									top: '18px',
									color: 'black'
								}
							}]
						},
						series: tmpSeries
					});
				}
				/* combinations chart */
			}				
		} else if(report_type == "risk") {
			chart_type = _selector.attr("chart-type");
			if(chart_type == "sunburst_chart") {
				var form_id = _selector.attr("form-id");
				var max_score = _selector.attr("max-score");
				var risk_score = _selector.attr("risk-score")/100;
				var _tmpsunburstData = [
					{
						id: "status" + form_id,
						parent: "",
						name: "Total",
						color: RGBToHex(Math.round(255*risk_score), Math.round(255 - 255*risk_score), 0)
					},
					{
						id: "status" + form_id + "gray",
						parent: "status" + form_id,
						name: statusName["0"],
						color: statusColor["0"]
					},
					{
						id: "status" + form_id + "red",
						parent: "status" + form_id,
						name: statusName["1"],
						color: statusColor["1"]
					},
					{
						id: "status" + form_id + "yellow",
						parent: "status" + form_id,
						name: statusName["2"],
						color: statusColor["2"]
					},
					{
						id: "status" + form_id + "green",
						parent: "status" + form_id,
						name: statusName["3"],
						color: statusColor["3"]
					}
				];
				//display the interact chart with green indicators by default
				var _tmpsunburstInteractData = [
					{
						id: "status" + form_id + "green",
						parent: "",
						name: statusName["3"],
						color: statusColor["3"]
					}
				];
				var risk_data = JSON.parse(_selector.attr("risk-data"));
				$.each(risk_data, function(ind, val){
					if(typeof val["score"] != "undefined") {
						var virtual_score = (risk_score * 3 + val["score"]/max_score) / 4;
						var color_combination = RGBToHex(Math.round(255*virtual_score), Math.round(255 - 255*virtual_score), 0);
						if(val["status"] == 0) {
							_tmpsunburstData.push({
								id: ind,
								parent: "status" + form_id + "gray",
								name: val["label"],
								field_link: val["field_link"],
								value: val["score"],
								color: color_combination
							});
						} else if(val["status"] == 1) {
							_tmpsunburstData.push({
								id: ind,
								parent: "status" + form_id + "red",
								name: val["label"],
								field_link: val["field_link"],
								value: val["score"],
								color: color_combination
							});
						} else if(val["status"] == 2) {
							_tmpsunburstData.push({
								id: ind,
								parent: "status" + form_id + "yellow",
								name: val["label"],
								field_link: val["field_link"],
								value: val["score"],
								color: color_combination
							});
						} else if(val["status"] == 3) {
							_tmpsunburstData.push({
								id: ind,
								parent: "status" + form_id + "green",
								name: val["label"],
								field_link: val["field_link"],
								value: val["score"],
								color: color_combination
							});
							_tmpsunburstInteractData.push({
								id: ind,
								parent: "status" + form_id + "green",
								name: val["label"],
								field_link: val["field_link"],
								value: val["score"],
								color: color_combination
							});
						}
					}
				})
				// Splice in transparent for the center circle
				Highcharts.getOptions().colors.splice(0, 0, 'transparent');
				_selector.highcharts({
				    chart: {
				        height: '500px'
				    },
				    title: {
						text: _subtitle
					},
				    series: [{
				        type: "sunburst",
				        data: _tmpsunburstData,
				        allowDrillToNode: false,
				        cursor: 'pointer',
				        events: {
					    	click: function(event) {
					    		if(event.point.node.level == 3) {
					    			window.location.href = event.point.field_link;
					    		} else if(event.point.node.level == 2) {
					    			var interact_table_title = "";
					    			switch(event.point.node.name) {
					    				case "Compliant":
					    					var _tmpsunburstInteractData = [
												{
													id: "status" + form_id + "green",
													parent: "",
													name: statusName["3"],
													color: statusColor["3"]
												}
											];
											$.each(risk_data, function(ind, val){
												if(typeof val["score"] != "undefined") {
													var virtual_score = (risk_score * 3 + val["score"]/max_score) / 4;
													var color_combination = RGBToHex(Math.round(255*virtual_score), Math.round(255 - 255*virtual_score), 0);
													if(val["status"] == 3) {
														_tmpsunburstInteractData.push({
															id: ind,
															parent: "status" + form_id + "green",
															name: val["label"],
															field_link: val["field_link"],
															value: val["score"],
															color: color_combination
														});
													}
												}
											});
											interact_table_title = "Compliant " + _subtitle;
											break;
										case "Pending":
					    					var _tmpsunburstInteractData = [
												{
													id: "status" + form_id + "gray",
													parent: "",
													name: statusName["0"],
													color: statusColor["0"]
												}
											];
											$.each(risk_data, function(ind, val){
												if(typeof val["score"] != "undefined") {
													var virtual_score = (risk_score * 3 + val["score"]/max_score) / 4;
													var color_combination = RGBToHex(Math.round(255*virtual_score), Math.round(255 - 255*virtual_score), 0);
													if(val["status"] == 0) {
														_tmpsunburstInteractData.push({
															id: ind,
															parent: "status" + form_id + "gray",
															name: val["label"],
															field_link: val["field_link"],
															value: val["score"],
															color: color_combination
														});
													}
												}
											});
											interact_table_title = "Pending " + _subtitle;
											break;
										case "In Progress":
											var _tmpsunburstInteractData = [
												{
													id: "status" + form_id + "yellow",
													parent: "",
													name: statusName["2"],
													color: statusColor["2"]
												}
											];
											$.each(risk_data, function(ind, val){
												if(typeof val["score"] != "undefined") {
													var virtual_score = (risk_score * 3 + val["score"]/max_score) / 4;
													var color_combination = RGBToHex(Math.round(255*virtual_score), Math.round(255 - 255*virtual_score), 0);
													if(val["status"] == 2) {
														_tmpsunburstInteractData.push({
															id: ind,
															parent: "status" + form_id + "yellow",
															name: val["label"],
															field_link: val["field_link"],
															value: val["score"],
															color: color_combination
														});
													}
												}
											});
											interact_table_title = "In Progress " + _subtitle;
											break;
										case "In Remediation":
											var _tmpsunburstInteractData = [
												{
													id: "status" + form_id + "red",
													parent: "",
													name: statusName["1"],
													color: statusColor["1"]
												}
											];
											$.each(risk_data, function(ind, val){
												if(typeof val["score"] != "undefined") {
													var virtual_score = (risk_score * 3 + val["score"]/max_score) / 4;
													var color_combination = RGBToHex(Math.round(255*virtual_score), Math.round(255 - 255*virtual_score), 0);
													if(val["status"] == 1) {
														_tmpsunburstInteractData.push({
															id: ind,
															parent: "status" + form_id + "red",
															name: val["label"],
															field_link: val["field_link"],
															value: val["score"],
															color: color_combination
														});
													}
												}
											});
											interact_table_title = "In Remediation " + _subtitle;
											break;											
					    			}
					    			$("#chart_interact_"+form_id).highcharts({
									    chart: {
									        height: '500px'
									    },
									    title: {
											text: interact_table_title
										},
									    series: [{
									        type: "sunburst",
									        data: _tmpsunburstInteractData,
									        allowDrillToNode: false,
									        cursor: 'pointer',
									        events: {
										    	click: function(event) {
										    		if(event.point.node.level == 2) {
										    			window.location.href = event.point.field_link;
										    		}
										    	}
										    },
									        dataLabels: {
									            format: '{point.name}',
									            rotationMode: 'circular'
									        },
									        levels: [{
									            level: 1,
									            levelIsConstant: false,
									        }, {
									            level: 2,
									            colorByPoint: true
									        }]
									    }],
									    tooltip: {
									        formatter: function () {
										        if (this.point.node.level == 1) {
										            return 'Total risk score of <b>' + this.point.name + '</b> Status Indicators is <b>' + this.point.value + '</b>';
										        } else {
										        	return 'Field Label: <b>' + this.point.name + '</b><br>Risk Score: <b>' + this.point.value + '</b>';
										        }
										    }
									    }
									});
					    		}
					    	}
					    },
				        dataLabels: {
				            format: '{point.name}',
				            rotationMode: 'circular'
				        },
				        levels: [{
				            level: 1,
				            levelIsConstant: false,
				        }, {
				            level: 2,
				            colorByPoint: true
				        },
				        {
				            level: 3,
				            colorByPoint: true
				        }]
				    }],
				    tooltip: {
				        formatter: function () {
					        if (this.point.node.level == 1) {
					            return 'Total risk score is <b>' + this.point.value + '</b>';
					        } else if(this.point.node.level == 2) {
					        	return 'Total risk score of <b>' + this.point.name + '</b> Status Indicators is <b>' + this.point.value + '</b>';
					        } else {
					        	return 'Field Label: <b>' + this.point.name + '</b><br>Risk Score: <b>' + this.point.value + '</b>';
					        }
					    }
				    }
				});
				$("#chart_interact_"+form_id).highcharts({
				    chart: {
				        height: '500px'
				    },
				    title: {
						text: "Compliant " + _subtitle
					},
				    series: [{
				        type: "sunburst",
				        data: _tmpsunburstInteractData,
				        allowDrillToNode: false,
				        cursor: 'pointer',
				        events: {
					    	click: function(event) {
					    		if(event.point.node.level == 2) {
					    			window.location.href = event.point.field_link;
					    		}
					    	}
					    },
				        dataLabels: {
				            format: '{point.name}',
				            rotationMode: 'circular'
				        },
				        levels: [{
				            level: 1,
				            levelIsConstant: false,
				        }, {
				            level: 2,
				            colorByPoint: true
				        }]
				    }],
				    tooltip: {
				        formatter: function () {
					        if (this.point.node.level == 1) {
					            return 'Total risk score of <b>' + this.point.name + '</b> Status Indicators is <b>' + this.point.value + '</b>';
					        } else {
					        	return 'Field Label: <b>' + this.point.name + '</b><br>Risk Score: <b>' + this.point.value + '</b>';
					        }
					    }
				    }
				});
			} else if(chart_type == "line_chart") {
				var max_score = _selector.attr("max-score");
				var risk_score = _selector.attr("risk-score")/100;
				var risk_data = JSON.parse(_selector.attr("risk-data"));
				$.each(risk_data, function(ind, val){
					if(typeof val["score"] != "undefined") {
						var virtual_score = (risk_score * 3 + val["score"]/max_score) / 4;
						var color_combination = RGBToHex(Math.round(255*virtual_score), Math.round(255 - 255*virtual_score), 0);
						_xAxisArr.push(val["label"]);
						_tmplineData.push({name:val["label"],color:color_combination,y:val["score"],field_link:val["field_link"]});
					}
				});
				
				_lineseries.push({
					name: 'score',
					data: _tmplineData
				});
	        	_selector.highcharts({
					title: {
						text: _subtitle
					},
					exporting:{
						'enabled':false
					},
					xAxis: {
						categories: _xAxisArr
					},
					yAxis: {
						title: {
							text: ''
						},
						plotLines: [{
							value: 0,
							width: 1,
							color: '#808080'
						}]
					},
					tooltip: {
						formatter: function(){
							return 'Field Label: <b>' + this.x + '</b><br>Risk Score: <b>' + this.y + '</b>';
						},
					},						
					plotOptions: {
						column: {
							pointPadding: 0.2,
							borderWidth: 0
						},
						series: {
							events: {
						    	click: function(event) {
						    		window.location.href = event.point.field_link;
						    	}
						    }
						}
					},
					legend: {
						enabled:false
					},				
					series: _lineseries
				});
			} else if(chart_type == "pie_chart") {
				var max_score = _selector.attr("max-score");
				var risk_score = _selector.attr("risk-score")/100;
				var risk_data = JSON.parse(_selector.attr("risk-data"));
				$.each(risk_data, function(ind, val){
					if(typeof val["score"] != "undefined") {
						var virtual_score = (risk_score * 3 + val["score"]/max_score) / 4;
						var color_combination = RGBToHex(Math.round(255*virtual_score), Math.round(255 - 255*virtual_score), 0);
						_tmppieData.push({name:val["label"],color:color_combination,y:val["score"],field_link:val["field_link"]});
					}
				});
				_pieseries.push({
					type: 'pie',
					name: _subtitle,
					data: _tmppieData
				});
	        	_selector.highcharts({
					chart: {
						plotBackgroundColor: null,
						plotBorderWidth: 1,//null,
						plotShadow: false
					},
					title: {
						text: _subtitle
					},
					exporting:{
						'enabled':false
					},
					tooltip: {
						formatter: function(){
							return 'Field Label: <b>' + this.point.name + '</b><br>Risk Score: <b>' + this.point.y + '</b>';
						},
					},	
					plotOptions: {
						pie: {
							allowPointSelect: true,
							cursor: 'pointer',
							dataLabels: {
								enabled: true,
								format: '<b>{point.name}</b>',
								style: {
									color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
								}
							}
						},
						series: {
							events: {
						    	click: function(event) {
						    		window.location.href = event.point.field_link;
						    	}
						    }
						}
					},
					series: _pieseries
				});
			} else if(chart_type == "column_and_bar_chart") {
				var max_score = _selector.attr("max-score");
				var risk_score = _selector.attr("risk-score")/100;
				var risk_data = JSON.parse(_selector.attr("risk-data"));
				$.each(risk_data, function(ind, val){
					if(typeof val["score"] != "undefined") {
						var virtual_score = (risk_score * 3 + val["score"]/max_score) / 4;
						var color_combination = RGBToHex(Math.round(255*virtual_score), Math.round(255 - 255*virtual_score), 0);
						_xAxisArr.push(val["label"]);
						_series.push({
							type: 'column',
							name: val["label"],
							data: [{name:val["label"],color:color_combination,y:val["score"],field_link:val["field_link"]}]
						});
					}
				});
				_selector.highcharts({
					chart: {
						type: 'column',
						plotBackgroundColor: '#fff', //null
						plotBorderWidth: 1,
						plotShadow: false
					},
					title: {
						text: _subtitle
					},
					exporting:{
						'enabled':false
					},
					xAxis: {
						type: 'category',
						categories: _xAxisArr
					},
					yAxis: {
						min: 0,
						title: {
							text: ''
						},
					},
					tooltip: {
						formatter: function(){
							return 'Field Label: <b>' + this.x + '</b><br>Risk Score: <b>' + this.y + '</b>';
						},
					},
					plotOptions: {
						bar: {
							dataLabels: {
								enabled: true
							}
						},
						column: {
							pointWidth: 60
						},
						series: {
							events: {
						    	click: function(event) {
						    		window.location.href = event.point.field_link;
						    	}
						    }
						}
					},
					legend: {
						enabled:false
					},				
					series: _series
				});
			} else if(chart_type == "bubble_chart") {
				var max_score = _selector.attr("max-score");
				var risk_score = _selector.attr("risk-score")/100;
				var risk_data = JSON.parse(_selector.attr("risk-data"));
				$.each(risk_data, function(ind, val){
					if(typeof val["score"] != "undefined") {
						var virtual_score = (risk_score * 3 + val["score"]/max_score) / 4;
						var color_combination = RGBToHex(Math.round(255*virtual_score), Math.round(255 - 255*virtual_score), 0);
						_xAxisArr.push(val["label"]);
						_tmpbubbleDataValue.push({name:val["label"],color:color_combination,y:val["score"],field_link:val["field_link"]});
					}
				});
				_bubbleseries.push({
					data: _tmpbubbleDataValue
				});
				_selector.highcharts({
					chart: {
						type: 'bubble',
						zoomType: 'xy'
					},				
					title: {
						text: _subtitle
					},
					xAxis: {
					    categories: _xAxisArr
					},
					yAxis: {
						title: {
							text: ''
						}
					},
					tooltip: {
						formatter: function(){
							return 'Field Label: <b>' + this.x + '</b><br>Risk Score: <b>' + this.y + '</b>';
						},
					},
					plotOptions: {
						series: {
							events: {
						    	click: function(event) {
						    		window.location.href = event.point.field_link;
						    	}
						    }
						}
					},
					exporting:{
						'enabled':false
					},
					legend: {
						enabled:false
					},
					series: _bubbleseries
				});
			} else if(chart_type == "combinations") {
				var max_score = _selector.attr("max-score");
				var risk_score = _selector.attr("risk-score")/100;
				var risk_data = JSON.parse(_selector.attr("risk-data"));
				$.each(risk_data, function(ind, val){
					if(typeof val["score"] != "undefined") {
						var virtual_score = (risk_score * 3 + val["score"]/max_score) / 4;
						var color_combination = RGBToHex(Math.round(255*virtual_score), Math.round(255 - 255*virtual_score), 0);
						_xAxisArr.push(val["label"]);
						_tmplineData.push({name:val["label"],color:color_combination,y:val["score"],field_link:val["field_link"]});
					}
				});
				var data = _selector.attr("data").split(",");
				data.forEach(function(value, ind){
					_combinationseries.push({
						name: statusName[ind],
						y: Math.round(value),
						color: statusColor[ind]
					});
				});
				_series.push({
					type: 'spline',
					name: _subtitle,
					data: _tmplineData,
					marker: {
						lineWidth: 2,
						radius:10
					}
				});
				_series.push({
					type: 'pie',
					name: _subtitle,
					data: _combinationseries,
					center: [100, 80],
					size: 200,
					showInLegend: false,
					dataLabels: {
						enabled: false
					}
				});
				_selector.highcharts({
					title: {
						text: _subtitle
					},
					xAxis: {
						categories: _xAxisArr
					},
					yAxis: {
						min: 0,
						title: {
							text: ''
						}
					},
					labels: {
				        items: [{
				            html: 'Status Indicators',
				            style: {
				                left: '50px',
				                top: '18px',
				            }
				        }]
				    },
					exporting:{
						'enabled':false
					},
					legend: {
						enabled:false
					},
					tooltip: {
						formatter: function(){
							if(this.series.options.type == "spline") {
								return 'Field Label: <b>' + this.x + '</b><br>Risk Score: <b>' + this.y + '</b>';
							} else if(this.series.options.type == "pie") {
								return this.point.name + ': <b>' + this.point.y + '</b>';
							}
						}
					},
					plotOptions: {
						series: {
							events: {
						    	click: function(event) {
						    		if(event.point.series.options.type == "spline") {
						    			window.location.href = event.point.field_link;
						    		}							    		
						    	}
						    }
						}
					},
					series: _series
				});
			} else if(chart_type == "polar_chart") {
				var form_id = _selector.attr("form-id");
				_tmppolarData = new Array();
				_tmpwindroseData = new Array();
				var max_score = _selector.attr("max-score");
				var risk_score = _selector.attr("risk-score")/100;
				var risk_data = JSON.parse(_selector.attr("risk-data"));
				var backgroundColor = RGBToHex(Math.round(255*risk_score), Math.round(255 - 255*risk_score), 0);
				$.each(risk_data, function(ind, val){
					if(typeof val["score"] != "undefined") {
						var virtual_score = (risk_score * 3 + val["score"]/max_score) / 4;
						var color_combination = RGBToHex(Math.round(255*val["score"]/max_score), Math.round(255 - 255*val["score"]/max_score), 0);
						_tmppolarData.push({
							name: statusName[val["status"]],
							y: val["score"],
							field_name: val["label"],
							field_link: val["field_link"],
							color: color_combination
						});
						_tmpwindroseData.push({
							type: 'column',
							y: val["score"],
							name: val["label"],
							field_link: val["field_link"],
							color: color_combination
						});
					}						
				});
				_tmppolarData.sort(compare);
				_selector.highcharts({
				    chart: {
				        polar: true,
				        type: 'column',
				        height: '500px'
				    },
				    accessibility: {
						announceNewData: {
							enabled: true
						}
					},
				    title: {
						text: _subtitle
					},
				    pane: {
				        size: '85%',
				        background : {
			                backgroundColor:backgroundColor
			            }
				    },
				    legend: {
				        enabled: false,
				    },
				    xAxis: {
				        tickmarkPlacement: 'on',
				        type: 'category',
				        labels: {
				        	formatter: function() {
				        		return this.value;
				        	}
				        }
				    },
				    yAxis: {
				        min: 0,
				        endOnTick: false,
				        showLastLabel: true,
				        title: {
				            text: ''
				        },
				        labels: {
				            formatter: function () {
				                return this.value;
				            }
				        },
				        reversedStacks: false
				    },
				    plotOptions: {
				        series: {
				            shadow: false,
				            groupPadding: 0,
				            pointPlacement: 'on',
				            events: {
						    	click: function(event) {
						    		window.location.href = event.point.field_link;
						    	}
						    }
				        },
				        column: {
							stacking: 'normal',
							dataLabels: {
								enabled: false
							}
						}
				    },
				    series: [{
				    	name: "Field Scores",
						colorByPoint: true,
						data: _tmppolarData
				    }],
				    tooltip: {
				    	formatter: function() {
				    		return 'Field Label: <b>' + this.point.options.field_name + '</b><br>Risk Score: <b>' + this.y + '</b>';
				    	}
				    }
				});
				$("#chart_interact_"+form_id).highcharts({
				    chart: {
				        polar: true,
				        height: '500px'
				    },
				    title: {
						text: _subtitle
					},
				    pane: {
				        startAngle: 0,
				        endAngle: 360,
				        background : {
			                backgroundColor:backgroundColor
			            }
				    },
				    xAxis: {
				        tickInterval: 360,
				        min: 0,
				        max: 360,
				        labels: {
				            enabled: false
				        }
				    },
				    yAxis: {
				        min: 0,
				        labels: {
				        	enabled: true
				        }
				    },
				    plotOptions: {
				        series: {
				            pointStart: 0
				        },
				        column: {
				            pointPadding: 0,
				            groupPadding: 0
				        }
				    },
				    legend: {
				    	enabled: false
				    },
				    series: [{					        
				        data: _tmpwindroseData,
				        type: 'column',
				        startAngle: 0,
				        pointInterval: Math.round(360/_tmpwindroseData.length),
				        tooltip:{
				        	headerFormat: "",
				        	pointFormatter: function () {
				          		return 'Field Label: <b>' + this.name + '</b><br>Risk Score: <b>' + this.y + '</b>';
				          	}
				        },
				        events:{
				        	click: function(){
				        		window.location.href = event.point.field_link;
				        	}
				        }
				    }]
				});
			}
		} else if(report_type == "maturity"){
			chart_type = _selector.attr("chart-type");
			var highChartColor = Highcharts.getOptions().colors;

			var data_status_points = [];
			var donut_plot_option = {
				pie: {
						allowPointSelect: true,
						cursor: 'pointer',
						dataLabels: {
							enabled: true,
							style: {
								color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
							}
						},
						states: {
							hover: {
								enabled: false
							}
						}
					}
			}

			if(chart_type == "status"){
				var data = _selector.attr("data").split(",");
				var total_status = Number(data[0]) + Number(data[1]) + Number(data[2]) + Number(data[3]);
				
				if(total_status == 0){
					data_status_points.push({y:1, color:"#505356", name:""});
				} else {
					if(data[0] != 0) {
						data_status_points.push({name:data[0], y: Number(data[0]), color:"#505356"});
					}
					if(data[1] != 0) {
						data_status_points.push({name:data[1], y: Number(data[1]), color:"#F95360"});
					}
					if(data[2] != 0) {
						data_status_points.push({name:data[2], y: Number(data[2]), color:"#F2B604"});
					}
					if(data[3] != 0) {
						data_status_points.push({name:data[3], y: Number(data[3]), color:"#33BF8C"});
					}					
				}
				_selector.find("section").highcharts({
					chart: {
						plotBackgroundColor: null,
						plotBorderWidth: 0,
						plotShadow: false,
						type: 'pie'
					},
					credits: { enabled: false },
					title: {
						text: 'Status Score', 
						y: 3,
						style: {
							fontFamily: 'glober_regularregular',
							fontWeight: 'bold',
							color: '#000'
						}
					},
					tooltip: {
						enabled: false
					},
					exporting: {
						enabled: false
					},
					plotOptions: donut_plot_option,
					series: [{
						name: 'Status Indicator',			
						innerSize: '60%',
						startAngle: 90,
						dataLabels: {
							enabled: true,
							distance: -20,
							style: {
								fontFamily: 'glober_regularregular',
								fontWeight: 'bold',
								fontSize: '15px',
								color: '#000',
								textOutline: false
							}
						},
						data: data_status_points
					}]
				});
				_selector.find("strong").css("display", "block");
			} else if (chart_type == "score"){
				var data = Math.round(_selector.attr("data"));
				var color_combination = RGBToHex(Math.round(255/100*data), Math.round(255 - 255/100*data), 0);

				var data_score_points = [
					{ name:'Score', y: Math.round(data) == 0 ? 1 : data, color: color_combination}			
				];
				_selector.find("section").highcharts({
					chart: {
						plotBackgroundColor: null,
						plotBorderWidth: 0,
						plotShadow: false,
						type: 'pie'
					},
					credits: { enabled: false },
					title: {
						text: 'Risk Score',
						y: 3,
						style: {
							fontFamily: 'glober_regularregular',
							fontWeight: 'bold',
							color: '#000'
						}
					},
					plotOptions: donut_plot_option,
					tooltip: {
						enabled: false
					},
					exporting: {
						enabled: false
					},
					series: [
						{		
							name: 'Score(percentage)',			
							innerSize: '60%',						
							dataLabels: {
								enabled: false							
							},
							data: data_score_points
						}
					]
				});
				_selector.find("strong").css("display", "block");
			} else {
				var data = Math.round(_selector.attr("data"));
				var color_combination = RGBToHex(Math.round(255/100*(100 - data)), Math.round(255 - 255/100*(100 - data)), 0);

				var data_maturity_points = [
					{ name:'Maturity', y: Math.round(data) == 0 ? 1 : data, color: color_combination}			
				];
				_selector.find("section").highcharts({
					chart: {
						plotBackgroundColor: null,
						plotBorderWidth: 0,
						plotShadow: false,
						type: 'pie'
					},
					credits: { enabled: false },
					title: {
						text: 'Maturity Score',
						y: 3,
						style: {
							fontFamily: 'glober_regularregular',
							fontWeight: 'bold',
							color: '#000'
						}
					},
					plotOptions: donut_plot_option,
					tooltip: {
						enabled: false
					},
					exporting: {
						enabled: false
					},
					series: [
						{		
							name: 'MyName',			
							innerSize: '60%',						
							dataLabels: {
								enabled: false							
							},
							data: data_maturity_points
						}
					]
				});
				_selector.find("strong").css("display", "block");
			}
		} else if(report_type == "compliance-dashboard") {
			var data_score = _selector.attr("data-score").split(",");
			var data_maturity = _selector.attr("data-maturity").split(",");
			var data_date = _selector.attr("data-date").split(",");
			
			var scoreDataPoints = [];
			var maturityDataPoints = [];

			for(var i=0; i<data_date.length; i++){			
				scoreDataPoints.push({x: new Date(data_date[i]), y:Math.round(Math.round(data_score[i]))});
				maturityDataPoints.push({x: new Date(data_date[i]), y:Math.round(data_maturity[i])});
			}
			_selector.find("div").highcharts({
				chart: {
			        type: 'line'
			    },
			    title: {
			        text: '',
			        style: {
			        	fontFamily: 'glober_regularregular',
						fontWeight: 'bold',
						color: '#000'
			        }
			    },
			    xAxis: {
			        type: 'datetime',
			        dateTimeLabelFormats: {
			            second: '%Y-%m-%d<br/>%H:%M:%S',
			            minute: '%Y-%m-%d<br/>%H:%M',
			            hour: '%Y-%m-%d<br/>%H:%M',
			            day: '%Y<br/>%m-%d',
			            week: '%Y<br/>%m-%d',
			            month: '%Y-%m',
			            year: '%Y'
			        }
			    },
			    yAxis: {
			    	title: 'Percent',
			        min: 0
			    },
			    tooltip: {
			        headerFormat: '<b>{series.name}: {point.y}%</b><br>',
			        pointFormat: '{point.x:%e %b, %Y %H:%M}'
			    },
			    plotOptions: {
			        spline: {
			            marker: {
			                enabled: true
			            }
			        }
			    },
			    colors: ['#F95360', '#33BF8C'],
			    series: [
			    	{
				        name: "Score",
				        data: scoreDataPoints
				    },
				    {
				        name: "Maturity",
				        data: maturityDataPoints
				    }
				]
			});
		} else if(report_type == "field-data"){
			chart_type = _selector.attr("chart-type");
			var _tmpData;
			var data_score = _selector.attr("data-score").split(",");
			var data_date = _selector.attr("data-date").split(",");
			for(var i=0; i<data_date.length; i++){			
				var colorSeed;
				if (Math.round(data_score[i]) < 10) {
					colorSeed = Math.floor(Math.random() * 100) + 1; 
				}
				else {
					colorSeed = Math.round(data_score[i]);
				}
				_tmpData = {
					name: data_date[i],
					y: Math.round(data_score[i]),
					color: getGreenToRedGradientByValue({current_value:(colorSeed),max_value:100})
				};
				_series.push({
					type: 'column',
					name: data_date[i],
					data: [_tmpData],
					color: getGreenToRedGradientByValue({current_value:(data_score[i]/10),max_value:100})
				});
				_combinationseries.push({
					name: data_date[i],
					y: Math.round(data_score[i]),
					color: getGreenToRedGradientByValue({current_value:(data_score[i]/10),max_value:100})
				});
				/* column and bar chart */
				_xAxisArr.push(data_date[i]);
				_tmplineData.push({name:data_date[i],color:getGreenToRedGradientByValue({current_value:(colorSeed+1),max_value:100}),y:Math.round(data_score[i])});
				_tmppieData.push({name:data_date[i],color:getGreenToRedGradientByValue({current_value:(colorSeed+1),max_value:100}),y:Math.round(data_score[i])});
				_areaseries.push(Math.round(data_score[i]));
				_xAxisAreaArr.push(data_date[i]);
				_tmp3dData.push({name:data_date[i],color:getGreenToRedGradientByValue({current_value:(colorSeed),max_value:100}),y:Math.round(data_score[i]/10)});
				_tmpbubbleDataValue.push(Math.round(data_score[i]));
			}
			/* column and bar chart */
			if(chart_type == 'column_and_bar_chart'){
	        	_selector.highcharts({
					chart: {
						type: 'column',
						plotBackgroundColor: '#fff', //null
						plotBorderWidth: 1,
						plotShadow: false
					},
					title: {
						text: _subtitle
					},
					exporting:{
						'enabled':false
					},
					xAxis: {
						type: 'category',
						categories: _xAxisArr
					},
					yAxis: {
						min: 0,
						title: {
							text: ''
						},
					},
					tooltip: {
						formatter: function(){
							return '<span><span style="fill:'+this.point.color+';" x="8" dy="16">●</span> '+_subtitle+'<br>'+this.x+' - '+this.y+'</span>';
						},
					},
					plotOptions: {
						bar: {
							dataLabels: {
								enabled: true
							}
						},
						column: {
							pointWidth: 60
						}
					},
					legend: {
						enabled:false
					},				
					series: _series
				});
			}
			/* column and bar chart */

			/* line chart */
			if(chart_type == 'line_chart'){
				_lineseries.push({
					name: 'score',
					data: _tmplineData
				});
	        	_selector.highcharts({
					title: {
						text: _subtitle
					},
					exporting:{
						'enabled':false
					},
					xAxis: {
						categories: _xAxisArr
					},
					yAxis: {
						title: {
							text: ''
						},
						plotLines: [{
							value: 0,
							width: 1,
							color: '#808080'
						}]
					},
					tooltip: {
						formatter: function(){
							return '<span><span style="fill:'+this.point.color+';" x="8" dy="16">●</span> '+_subtitle+'<br>'+this.x+' - '+this.y+'</span>';
						},
					},
					plotOptions: {
						column: {
							pointPadding: 0.2,
							borderWidth: 0
						}
					},
					legend: {
						enabled:false
					},				
					series: _lineseries
				});
			}
			/* line chart */

			/* pie chart */
			if(chart_type == 'pie_chart'){
				_pieseries.push({
					type: 'pie',
					name: _subtitle,
					data: _tmppieData
				});
	        	_selector.highcharts({
					chart: {
						plotBackgroundColor: null,
						plotBorderWidth: 1,//null,
						plotShadow: false
					},
					title: {
						text: _subtitle
					},
					exporting:{
						'enabled':false
					},
					tooltip: {
						pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
					},
					plotOptions: {
						pie: {
							allowPointSelect: true,
							cursor: 'pointer',
							dataLabels: {
								enabled: true,
								format: '<b>{point.name}</b>: {point.percentage:.1f} %',
								style: {
									color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
								}
							}
						}
					},
					series: _pieseries
				});
			}
			/* pie chart */

			/* area chart */
			if(chart_type == 'area_chart'){
	        	_selector.highcharts({
					chart: {
						type: 'area'
					},				
					title: {
						text: _subtitle
					},
					xAxis: {
						categories: _xAxisArr
					},
					exporting:{
						'enabled':false
					},
					tooltip: {
						pointFormat: '{series.name}: {point.y}'
					},
					plotOptions: {
						area: {
							marker: {
								enabled: false,
								symbol: 'circle',
								radius: 2,
								states: {
									hover: {
										enabled: true
									}
								}
							}
						}
					},
					legend: {
						enabled:false
					},				
					series: [{name: "Score", data: _areaseries}]
				});
			}
			/* area chart */

			/* 3d chart */
			if(chart_type == '3d_chart'){
				_3dseries.push({
					data: _tmp3dData
				});
	        	_selector.highcharts({
					chart: {
						renderTo: 'container',
						type: 'column',
						margin: 75,
						options3d: {
							enabled: true,
							alpha: 25,
							beta: 5,
							depth: 50,
							viewDistance: 25
						}
					},				
					title: {
						text: _subtitle
					},
					exporting:{
						'enabled':false
					},
					plotOptions: {
						column: {
							depth: 25
						}
					},
					series: _3dseries
				});
			}
			/* 3d chart */

			/* bubble chart */
			if(chart_type == 'bubble_chart'){
				_bubbleseries.push({
					data: _tmpbubbleDataValue
				});
				_selector.highcharts({
					chart: {
						type: 'bubble',
						zoomType: 'xy'
					},				
					title: {
						text: _subtitle
					},
					xAxis: {
					    categories: _xAxisArr
					},
					yAxis: {
						title: {
							text: ''
						}
					},
					exporting:{
						'enabled':false
					},
					series: _bubbleseries
				});
			}
			/* bubble chart */

			/* combinations chart */
			if(chart_type == 'combinations'){
				
				_series.push({
					type: 'spline',
					name: _subtitle,
					data: _tmplineData,
					marker: {
						lineWidth: 2,
						lineColor: 'black',
						fillColor: 'white'
					}
				});
				_series.push({
					type: 'pie',
					name: _subtitle,
					data: _combinationseries,
					center: [100, 80],
					size: 100,
					showInLegend: false,
					dataLabels: {
						enabled: false
					}
				});
	        	_selector.highcharts({
					title: {
						text: _subtitle
					},
					xAxis: {
						categories: _xAxisArr
					},
					yAxis: {
						min: 0,
						title: {
							text: ''
						}
					},
					exporting:{
						'enabled':false
					},
					labels: {
						items: [{
							html: '',
							style: {
								left: '50px',
								top: '18px',
								color: 'black'
							}
						}]
					},
					series: _series
				});
			}
			/* combinations chart */
		} else {
			//
		}
	});

	$(".report-generation-msg").hide();
	$(".report-details").show();
});