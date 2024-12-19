<section style="background:white">
<h4 class="hidden_in_compliance_dashboard">Status Indicator</h4>
<p class="hidden_in_compliance_dashboard">To view fields, click the status indicators below</p>

<!--start::status indicator accordion-->
<div class="status_accordion_parent" style="overflow:visible; height:auto !important">
  	<style type="text/css">
      #entries_table tbody tr.indicator-detail-wrapper:hover td {
  			background-color: white !important;
  		} 
      #entries_table tbody tr.indicator-detail-wrapper table tr:hover td {
  			background-color: #7aa7d6 !important;
  		} 
  		.status_accordion {
        height: auto !important;
  			margin-bottom: 30px;
  			margin-top: 10px;
        white-space: normal!important;
  		}
      .panel-accordion {
        white-space: normal!important;
      }
      .status_accordion div {
        height: auto !important;
      }
    	.status_accordion ul li:nth-child(even) {
        background-color: #eee;
    	}
  		.status_accordion h3.gray {
  			background: #505356;
  		}
  		.status_accordion h3.green {
  			background: #33BF8C;
  		}
  		.status_accordion h3.yellow {
  			background: #F2B604;
  		}
  		.status_accordion h3.red {
  			background: #F95360;
  		}
  		.status_accordion h3 > .ball {
  	    color: #000;
  	    position: relative;
  	    border-radius: 50%;
  	    background-color: #fff;
  	    align-items: center;
  	    padding: 3px 10px;
        font-size: 14px;
  	    justify-content: center;
  		}
  		.accordion {			
  			color: #000;
  			cursor: pointer;
  			padding: 10px 20px;			
  			font-size: 15px;
  			position: relative;
  			transition: 0.4s;
  			margin: 2px 0 0 0;
  		}
  		.accordion:after {
  			content: '+';
  			color: #000;
  			font-weight: bold;
  			float: right;
  			font-size: 25px;
  			margin-left: 5px;
        line-height: 25px;
  		}
  		.accordion.active:after {
  		  content: "-";
        line-height: 21px;
  		}
  		.panel-accordion {
  	  	padding: 1em 1.3em;
  	    background-color: white !important;
  	    display: none;
  	    border-top: 0;
  	    margin-bottom: 2px;
  		}
      .panel-accordion table:hover {
        background: white !important;
      }
      .panel-accordion table tr {
        background: white !important;
      }
  		.all_statuses {
  			display: inline-block;
  			margin-left: 25px;
  			margin-top: 5px;
  			margin-bottom: 5px			
  		}
  		.all_statuses_child {
  	    margin-left: 10px;
    		cursor: pointer;
    	}
  		.status_parent .status-icon{
  			vertical-align: unset;
  		}
  		.status_accordion td {
  			vertical-align: top;
  		}
  	</style>
	
  	<div class="status_accordion">
  	<?php
  		$accordion_Arr = [[],[],[],[]];
  		$accordion_head_count_Arr = [0,0,0,0];
      $all_heading_colors = ['gray','red','yellow','green'];

  		foreach ($entry_details as $data){
        if($data['label'] == 'la_page_break' && $data['value'] == 'la_page_break'){
          continue;
        }
        $element_id = $data['element_id'];
        
        $status_indicator = "";
        $indicator_count = 0;
        $is_cascade = false;

        if(in_array( $data['element_type'],
              array( 'text',
                  'textarea',
                  'file',
                  'radio',
                  'checkbox',
                  'select',
                  'signature',
                  'matrix')) && $data['element_status_indicator'] == 1) {
          $statusElementArrId = $statusElementArr[$data['element_id']];
          if(isset($statusElementArrId)){
            $indicator_count = $statusElementArrId;

            if( $statusElementArrId == 0){
              $status_indicator_image = 'Circle_Gray.png';
              $accordion_head_count_Arr[0] = $accordion_head_count_Arr[0]+1;
            }elseif( $statusElementArrId == 1){
              $status_indicator_image = 'Circle_Red.png';
              $accordion_head_count_Arr[1] =$accordion_head_count_Arr[1]+1; 
            }elseif( $statusElementArrId == 2){
              $status_indicator_image = 'Circle_Yellow.png';
              $accordion_head_count_Arr[2] =$accordion_head_count_Arr[2]+1;
            }elseif( $statusElementArrId == 3){
              $status_indicator_image = 'Circle_Green.png';
              $accordion_head_count_Arr[3] = $accordion_head_count_Arr[3]+1;
            } 
          } else{
            $statusElementArrId = 0;
            $status_indicator_image = 'Circle_Gray.png';
            $accordion_head_count_Arr[0] = $accordion_head_count_Arr[0]+1;
          }
          if( $data['element_type'] == 'textarea' ) {
            $field_value = html_entity_decode($data['value']);
          } else if($data['element_type'] == 'signature') {
            if($data['element_size'] == 'small'){
              $canvas_height = 70;
              $line_margin_top = 50;
            }else if($data['element_size'] == 'medium'){
              $canvas_height = 130;
              $line_margin_top = 95;
            }else{
              $canvas_height = 260;
              $line_margin_top = 200;
            }

            $signature_markup = <<<EOT
            <div id="la_sigpad_{$element_id}" class="la_sig_wrapper {$data['element_size']}">
              <canvas class="la_canvas_pad" width="309" height="{$canvas_height}"></canvas>
            </div>
            <script type="text/javascript">
            $(function(){
              var sigpad_options_{$element_id} = {
                 drawOnly : true,
                 displayOnly: true,
                 bgColour: '#fff',
                 penColour: '#000',
                 output: '#element_{$element_id}',
                 lineTop: {$line_margin_top},
                 lineMargin: 10,
                 validateFields: false
              };
              var sigpad_data_{$element_id} = {$data['value']};
              $('#la_sigpad_{$element_id}').signaturePad(sigpad_options_{$element_id}).regenerate(sigpad_data_{$element_id});
            });
            </script>
EOT;
            $field_value = $signature_markup;
          } else {
            $field_value = $data['value'];
          }
          $element_id_auto = $data["element_id_auto"];
          $la_page = $data["element_page_number"];
            $status_indicator = '<td width="30%" class="status_parent"><strong>'.$data['label'].'</strong><img class="status-icon status-icon-action-status" data-form_id="'.$form_id.'" data-element_id="'.$data['element_id'].'" data-company_id="'.$company_id.'" data-entry_id="'.$entry_id.'" data-indicator="'.$indicator_count.'" src="images/'.$status_indicator_image.'" style="margin-left:8px; cursor:pointer;"><div class="all_statuses"></div></td><td width="50%">'.$field_value.'</td><td width="20%"><a target="_blank" style="float: right;" href="edit_entry.php?form_id='.$form_id.'&company_id='.$company_id.'&entry_id='.$entry_id.'&la_page='.$la_page.'&element_id_auto='.$element_id_auto.'">Go To Field</a></td>';

          if(isset($statusElementArrId)) {
            $accordion_Arr[$statusElementArrId][] = $status_indicator;
          } else {
            $accordion_Arr[0][] = $status_indicator;
          }
        } else if($data['element_type'] == 'casecade_form') {
            $cascade_data = display_casecade_form_fields_status_only(array('dbh' => $dbh, 'form_id' => $data['value'], 'parent_form_id' => $form_id, 'entry_id' => $entry_id, 'company_id' => $company_id, 'cascade_parent_page_number' => $data['element_page_number']));
            if( !empty($cascade_data['row_markup']) ) {
              foreach ($cascade_data['row_markup'] as $cascade_key => $cascade_value) {
                foreach ($cascade_value as $cascade_key_inner => $cascade_value_inner) {
                  $accordion_Arr[$cascade_key][] = $cascade_value_inner;
                }
              }
            }

            if( !empty($cascade_data['accordion_head_count_Arr']) ) {
              foreach ($cascade_data['accordion_head_count_Arr'] as $key => $value) {
                $accordion_head_count_Arr[$key] = $accordion_head_count_Arr[$key] + $value;
              }
            }
            //$is_cascade = true;
        }
    }

      $acc_list_content ='';
      for ($i=0; $i < 4; $i++) { 
        $acc_list_content .= "<h3 class=\"{$all_heading_colors[$i]} accordion\"><label class=\"ball\"> {$accordion_head_count_Arr[$i]} </label></h3>";
        $acc_list_content .="<div class=\"panel-accordion\">".'<table class="ve_detail_table" width="100%" border="0" cellspacing="0" cellpadding="0">  <tbody>';
        if( $accordion_head_count_Arr[$i] > 0 ) {
          
          foreach ($accordion_Arr[$i] as $value) {
            $acc_list_content .= "<tr>{$value}</tr>";
          }
          
        } else {
          $acc_list_content .= "<tr class=\"no-results\"><td><strong>No fields available for this status</strong></td></tr>";
        }
        $acc_list_content .="</tbody></table></div>";
      }
      echo $acc_list_content;
  	?>
  	</div><!--parent ends here-->
  	
  	
	<script type="text/javascript">
		$(document).ready(function (e) {
      
			$("#processing-status-dialog").dialog({
				modal: true,
				autoOpen: false,
				closeOnEscape: false,
				width: 400,
				draggable: false,
				resizable: false
			});
		}); // document ready

	</script>
</div>
<!--end::status indicator accordion-->
</section>