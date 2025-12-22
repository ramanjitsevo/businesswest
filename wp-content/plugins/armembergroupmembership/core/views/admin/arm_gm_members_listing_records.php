<?php
	global $arm_common_lite;
	$arm_gm_grid_columns = array(
		'arm_gm_user_id' => __('User ID', 'ARMGroupMembership'),
		'arm_gm_user_login' => __('Parent Username', 'ARMGroupMembership'),
		'arm_gm_user_email' => __('Parent Email', 'ARMGroupMembership'),
		'arm_gm_child_users' => __('Total Child Users', 'ARMGroupMembership'),
		'arm_gm_total_coupons' => __('Total Invite Codes', 'ARMGroupMembership'),
		'arm_gm_remain_coupons' => __('Remaining Invite Codes', 'ARMGroupMembership'),		
	);
	$arm_loader = $arm_common_lite->arm_loader_img_func();

	$filter_search = !empty($_REQUEST['sSearch']) ? sanitize_text_field($_REQUEST['sSearch']) : '';
?>

<style type="text/css">
	.paginate_page a{display:none;}
	#poststuff #post-body {margin-top: 32px;}
	.ColVis_Button{display:none !important;}
</style>

<div class="arm_members_list">
	<div class="arm_filter_wrapper" id="arm_filter_wrapper" style="display:none;">
		<div class="arm_datatable_filters_options arm_filters_searchbox">
			<div class="sltstandard">
				<div class="arm_dt_filter_block arm_datatable_searchbox">
					<div class="arm_datatable_filter_item">
						<label class="arm_padding_0"><input type="text" placeholder="<?php esc_attr_e( 'Search Plans', 'ARMGroupMembership' ); ?>" id="armmanagegm_search" value="<?php echo esc_attr($filter_search); ?>" tabindex="-1"></label>
					</div>				
				</div>
			</div>
		</div>
	</div>
	<div id="armmainformnewlist" class="arm_filter_grid_list_container">
    <div class="arm_loading_grid" style="display: none;"><?php echo $arm_loader; //phpcs:ignore ?></div>
    <div class="response_messages"></div>
    <div class="armclear"></div>
    <table cellpadding="0" cellspacing="0" border="0" class="display arm_on_display" id="armember_datatable" style="visibility: hidden;">
        <thead>
            <tr>
            	<?php
            		foreach($arm_gm_grid_columns as $arm_gm_key => $arm_gm_column_val)
            		{
            	?>
		            	<th data-key="<?php echo esc_attr($arm_gm_key); ?>" class="arm_grid_th_<?php echo esc_attr($arm_gm_key); ?>" >
		            		<?php echo esc_attr($arm_gm_column_val); ?>
		        		</th>
		        <?php
            		}
            	?>
            	<th class="armGridActionTD"></th>
        	</tr>
        </thead>
    </table>
    <div class="armclear"></div>
</div>

<?php
	wp_nonce_field( 'arm_wp_nonce' );
	echo $ARMember->arm_get_need_help_html_content('arm-manage-group-membership');
?>



<script type="text/javascript" charset="utf-8">

	jQuery(document).on('keyup','#armmanagegm_search',function(e){
		if (e.keyCode == 13 || 'Enter' == e.key) {
			var arm_search = jQuery(this).val();
			jQuery('#armmanagegm_search').val(arm_search);
			jQuery('#armember_datatable').dataTable().fnDestroy();
			arm_load_gm_list_grid();
		}
	});

	jQuery(document).ready( function () {
		jQuery('#armember_datatable').dataTable().fnDestroy();
	    arm_load_gm_list_grid(false);
	});
	function show_grid_loader() {
		jQuery('#armember_datatable').hide();
		jQuery('.footer').hide();
	    jQuery('.arm_loading_grid').show();
	}
	function arm_load_gm_list_grid(is_filtered)
	{
		var __ARM_Showing = '<?php echo addslashes(esc_html__('Showing','ARMGroupMembership')); //phpcs:ignore?>';
		var __ARM_Showing_empty = '<?php echo addslashes(esc_html__('Showing 0 to 0 of 0 entries','ARMGroupMembership')); //phpcs:ignore?>';
		var __ARM_to = '<?php echo addslashes(esc_html__('to','ARMGroupMembership')); //phpcs:ignore?>';
	    var __ARM_of = '<?php echo addslashes(esc_html__('of','ARMGroupMembership')); //phpcs:ignore?>';
	    var __ARM_Entries = ' <?php esc_html_e('Group Members','ARMGroupMembership'); ?>';
	    var __ARM_Show = '<?php echo addslashes(esc_html__('Group Members per page','ARMGroupMembership')); //phpcs:ignore?> ';
	    var __ARM_NO_FOUND = '<?php echo addslashes(esc_html__('No members have group membership.','ARMGroupMembership')); //phpcs:ignore?>';
	    var __ARM_NO_MATCHING = '<?php echo addslashes(esc_html__('No matching records found.','ARMGroupMembership')); //phpcs:ignore?>';
		var db_search_field =jQuery('#armmanagegm_search').val();

	    var ajax_url = '<?php echo admin_url("admin-ajax.php"); //phpcs:ignore?>';
	    var table = jQuery('#armember_datatable').dataTable({
	        "oLanguage": {
	            "sProcessing": show_grid_loader(),
	            "sInfo": __ARM_Showing + " _START_ " + __ARM_to + " _END_ " + __ARM_of + " _TOTAL_ " + __ARM_Entries,
	            "sInfoEmpty": __ARM_Showing_empty,
	           
	            "sLengthMenu": __ARM_Show + "_MENU_",
	            "sEmptyTable": __ARM_NO_FOUND,
	            "sZeroRecords": __ARM_NO_MATCHING,
	        },
			"bDestroy": true,
	        "language": {
	        	"searchPlaceholder": "Search",
                "search":"",
	        },
			"bProcessing": false,
			"bServerSide": true,
			"sAjaxSource": ajax_url,
			"sServerMethod": "POST",
			"fnServerParams": function (aoData) {
	            aoData.push({'name': 'action', 'value': 'get_arm_gm_admin_data'});
				aoData.push({'name': 'sSearch', 'value': db_search_field});
	        },
			"bRetrieve": false,
	    	"sDom": '<"H"fr>t<"footer"ipl>',
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
				{ "sClass": 'arm_padding_left_24', "aTargets": [0] },
				{ "sClass": 'arm_min_width_250', "aTargets": [2] },
				{ "bSortable": false, "aTargets": [] }
			],
			"bStateSave": true,
			"iCookieDuration": 60 * 60,
	        "sCookiePrefix": "arm_datatable_",
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
					jQuery('#armember_datatable').show();
					jQuery('.footer').show();
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
			}
		});
		var filter_box = jQuery('#arm_filter_wrapper').html();
		jQuery('div#armember_datatable_filter').parent().append(filter_box);
		jQuery('div#armember_datatable_filter').addClass('arm_datatable_searchbox');
		if(db_search_field != '')
		{
			jQuery('#armmanagegm_search:last-child').val(db_search_field);
		}
	}
</script>