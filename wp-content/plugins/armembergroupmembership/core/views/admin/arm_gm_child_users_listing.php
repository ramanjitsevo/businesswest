<?php
	$arm_gm_grid_columns = array(
		'arm_gm_user_id' => __('User ID', 'ARMGroupMembership'),
		'arm_gm_user_login' => __('Username', 'ARMGroupMembership'),
		'arm_gm_user_email' => __('Email', 'ARMGroupMembership'),
		'arm_gm_status' => __('Invite Status', 'ARMGroupMembership'),
		'arm_gm_coupon_code' => __('Invite Code', 'ARMGroupMembership'),
	);
?>

<style type="text/css">
	.arm_gm_child_user_content_data .ui-corner-tr{ display: none; }
	.buttons-colvis{ display: none !important;}
</style>

<table width="100%" cellspacing="0" class="display arm_on_display" id="armember_datatable_1">
	<thead>
        <tr>
        	<?php
        		foreach($arm_gm_grid_columns as $arm_gm_key => $arm_gm_column_val)
        		{
        	?>
	            	<th data-key="<?php echo esc_attr($arm_gm_key); ?>" class="arm_grid_th_<?php echo esc_attr($arm_gm_key); ?>" ><?php echo esc_attr($arm_gm_column_val); ?></th>
	        <?php
        		}
        	?>
        	<th data-key="armGridActionTD" class="armGridActionTD noVis" style="display: none;">
    	</tr>
    </thead>
</table>
<div class="armclear"></div>



<script type="text/javascript" charset="utf-8">
	function show_grid_loader() {
		jQuery('.arm_gm_child_user_content_data').css('visibility','hidden');
	    jQuery('.arm_loading_grid').show();
	}


	function arm_load_gm_sub_user_list_grid(is_filtered)
	{
		var __ARM_Showing = '<?php echo addslashes(esc_html__('Showing','ARMGroupMembership')); //phpcs:ignore?>';
		var __ARM_Showing_empty = '<?php echo addslashes(esc_html__('Showing 0 to 0 of 0 entries','ARMGroupMembership')); //phpcs:ignore?>';
		var __ARM_to = '<?php echo addslashes(esc_html__('to','ARMGroupMembership')); //phpcs:ignore?>';
	    var __ARM_of = '<?php echo addslashes(esc_html__('of','ARMGroupMembership')); //phpcs:ignore ?>';
	    var __ARM_Entries = ' <?php esc_html_e('entries','ARMGroupMembership'); ?>';
	    var __ARM_Show = '<?php echo addslashes(esc_html__('Members Per page','ARMGroupMembership')); //phpcs:ignore?> ';
	    var __ARM_NO_FOUND = '<?php echo addslashes(esc_html__('No child users found.','ARMGroupMembership')); //phpcs:ignore?>';
	    var __ARM_NO_MATCHING = '<?php echo addslashes(esc_html__('No matching records found.','ARMGroupMembership')); //phpcs:ignore?>';

	    var ajax_url = '<?php echo admin_url("admin-ajax.php"); //phpcs:ignore?>';
	    var table = jQuery('#armember_datatable_1').dataTable({
			"bProcessing": false,
			"oLanguage": {
				"sProcessing": show_grid_loader(),
	            "sInfo": __ARM_Showing + " _START_ " + __ARM_to + " _END_ " + __ARM_of + " _TOTAL_ " + __ARM_Entries,
	            "sInfoEmpty": __ARM_Showing_empty,
	           
	            "sLengthMenu": __ARM_Show + "_MENU_",
	            "sEmptyTable": __ARM_NO_FOUND,
	            "sZeroRecords": __ARM_NO_MATCHING,
	        },
	        "language": {
	        	"searchPlaceholder": "Search",
                "search":"",
	        },
			"bServerSide": true,
			"sAjaxSource": ajax_url,
			"sServerMethod": "POST",
			"fnServerParams": function (aoData) {
	            aoData.push({'name': 'action', 'value': 'get_sub_user_table_data'}),
				aoData.push({'name': 'arm_gm_parent_user_id', 'value': '<?php echo intval($arm_gm_parent_user_id); ?>'});
	        },
			"bRetrieve": false,
	    	"sDom": '<"H"Cfr>t<"footer"ipl>',
			"sPaginationType": "four_button",        
			"bJQueryUI": true,
			"bPaginate": true,
			"bAutoWidth" : false,
			"bScrollCollapse": true,
			"aaSorting": [],
			"ordering": false,
			"fixedColumns": false,
			"aoColumnDefs": [
				{ "bVisible": false, "aTargets": [] },
				{ "sClass": 'center', "aTargets": [3]},
				{ "bSortable": false, "aTargets": [2,3] },
				{ "sClass": 'arm_padding_left_24', "aTargets": [0] },
			],
			"bStateSave": true,
			"iCookieDuration": 60 * 60,
	        "sCookiePrefix": "arm_datatable_1_",
	        "aLengthMenu": [10, 25, 50, 100, 150, 200],
			"fnPreDrawCallback": function () {
				show_grid_loader();
	        },
	        "fnStateSave": function (oSettings, oData) {
	            oData.aaSorting = [];
	            oData.abVisCols = [];
	            oData.aoSearchCols = [];
	            this.oApi._fnCreateCookie(
	                oSettings.sCookiePrefix + oSettings.sInstance,
	                this.oApi._fnJsonString(oData),
	                oSettings.iCookieDuration,
	                oSettings.sCookiePrefix,
	                oSettings.fnCookieCallback
	            );
	        },
			"stateSaveParams":function(oSettings,oData){
				oData.start=0;
			},
			"fnStateLoadParams": function (oSettings, oData) {
	            oData.iLength = 10;
	            oData.iStart = 1;
	        },
	        "fnCreatedRow": function (nRow, aData, iDataIndex) {
	        	jQuery(nRow).find('.arm_grid_action_wrapper').each(function () {
	                jQuery(this).parent().addClass('armGridActionTD');
	                jQuery(this).parent().attr('data-key', 'armGridActionTD');
	            });
	        },
			"fnDrawCallback":function(){
				setTimeout(function(){
					jQuery('.arm_gm_child_user_content_data').css('visibility','visible');
					jQuery('.arm_loading_grid').hide();
					arm_show_data();
				},1000);
				if (jQuery.isFunction(jQuery().tipso)) {
	                jQuery('.armhelptip').each(function () {
	                    jQuery(this).tipso({
	                        position: 'top',
	                        size: 'small',
	                        background: '#939393',
	                        color: '#ffffff',
	                        width: false,
	                        maxWidth: 400,
	                        useTitle: true
	                    });
	                });
	            }
				table.dataTable().fnAdjustColumnSizing(false);
			}
		});
	}

	jQuery(document).ready( function () {
		jQuery('#armember_datatable_1').dataTable().fnDestroy();
	    arm_load_gm_sub_user_list_grid(false);
	});
</script>