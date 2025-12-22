<?php
global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_global_settings, $arm_manage_coupons, $arm_payment_gateways, $arm_subscription_plans, $arm_transaction,$arm_common_lite;

$payment_gateways = $arm_payment_gateways->arm_get_all_payment_gateways();
$global_currency = $arm_payment_gateways->arm_get_global_currency();
$nowDate = current_time('mysql');
$filter_gateway = (!empty($_POST['gateway'])) ? sanitize_text_field($_POST['gateway']) : '0';//phpcs:ignore
$filter_ptype = (!empty($_POST['ptype'])) ? sanitize_text_field($_POST['ptype']) : '0';//phpcs:ignore
$filter_pmode = (!empty($_POST['pmode'])) ? sanitize_text_field($_POST['pmode']) : '0';//phpcs:ignore
$filter_pstatus = (!empty($_POST['pstatus'])) ? sanitize_text_field($_POST['pstatus']) : '0';//phpcs:ignore
$filter_search = (!empty($_POST['search'])) ? sanitize_text_field($_POST['search']) : '';//phpcs:ignore
$default_hide = array(
    'arm_transaction_id' => 'Transaction ID',
    'arm_invoice_id' => 'Invoice ID',
    'arm_user_fname' => 'First Name',
    'arm_user_lname' => 'Last Name',
    'arm_user_id' => 'User',
    'arm_user_email' => 'User Email',
    'arm_plan_id' => 'Membership',
    'arm_payment_gateway' => 'Gateway',
    'arm_payment_type' => 'Payment Type',
    'arm_payer_email' => 'Payer Email',
    'arm_transaction_status' => 'Transaction Status',
    'arm_created_date' => 'Payment Date',
    'arm_amount' => 'Amount',
    'arm_cc_number' => 'Credit Card Number',
);
$user_id = get_current_user_id();
$transaction_show_hide_column = maybe_unserialize(get_user_meta($user_id, 'arm_transaction_paid_post_hide_show_columns', true));

$arm_common_date_format = $arm_global_settings->arm_check_common_date_format(get_option('date_format'));

$i = 1;
$column_hide = "";
if(!empty($transaction_show_hide_column)) {
    foreach ($transaction_show_hide_column as $value) {
        if ($value != 1) {
            $column_hide = $column_hide . $i . ',';
        }
        $i++;
    }
} else {
    $column_hide = '3,4';
}


if(isset($_POST["arm_export_pphistory"]) && $_POST["arm_export_pphistory"] == 1) {//phpcs:ignore

    $filter_gateway = isset($_REQUEST['arm_filter_pp_gateway']) ? sanitize_text_field($_REQUEST['arm_filter_pp_gateway']) : '';
    $filter_ptype = isset($_REQUEST['arm_filter_pptype']) ? sanitize_text_field($_REQUEST['arm_filter_pptype']) : '';
    $filter_pmode = isset($_REQUEST['arm_filter_ppmode']) ? sanitize_text_field($_REQUEST['arm_filter_ppmode']) : '';
    $filter_pstatus = isset($_REQUEST['arm_filter_ppstatus']) ? sanitize_text_field($_REQUEST['arm_filter_ppstatus']) : '';
    $payment_start_date = isset($_REQUEST['arm_filter_ppstart_date']) ? sanitize_text_field($_REQUEST['arm_filter_ppstart_date']) : '';
    $payment_end_date = isset($_REQUEST['arm_filter_ppend_date']) ? sanitize_text_field($_REQUEST['arm_filter_ppend_date']) : '';
    $sSearch = isset($_REQUEST['armmanagesearch_pp_new']) ? sanitize_text_field($_REQUEST['armmanagesearch_pp_new']) : '';

    $arm_is_post_payment = isset($_REQUEST['arm_is_post_payment']) ? sanitize_text_field($_REQUEST['arm_is_post_payment']) : 0;

    $date_time_format = $arm_global_settings->arm_get_wp_date_time_format();

    $where_plog = $wpdb->prepare("WHERE 1=1 AND arm_display_log = %d ",1);

    if (!empty($filter_gateway) && $filter_gateway != '0') {
        $where_plog .= $wpdb->prepare(" AND `arm_payment_gateway` = %s",$filter_gateway);
    }
    if (!empty($filter_ptype) && $filter_ptype != '0') {
        $where_plog .= $wpdb->prepare(" AND `arm_payment_type` = %s",$filter_ptype);
    }
    if (!empty($filter_pmode) && $filter_pmode != '0') {
        $where_plog .= $wpdb->prepare(" AND `arm_payment_mode` = %s",$filter_pmode);
    }

    if(!empty($arm_is_post_payment) && $arm_is_post_payment != '0'){

        $where_plog .= $wpdb->prepare(" AND `arm_is_post_payment`=%s",$arm_is_post_payment);
    }else{
        $where_plog .= $wpdb->prepare(" AND `arm_is_post_payment`=%s",$arm_is_post_payment);
    }
    
    if (!empty($filter_pstatus) && $filter_pstatus != '0') {
        $filter_pstatus = strtolower($filter_pstatus);
        $status_query = $wpdb->prepare(" AND ( LOWER(`arm_transaction_status`)=%s",$filter_pstatus);
        if(!in_array($filter_pstatus,array('success','pending','canceled'))) {
            $status_query .= ")";
        }
        switch ($filter_pstatus) {
            case 'success':
                $status_query .= $wpdb->prepare(" OR `arm_transaction_status`=%s)",'1');
                break;
            case 'pending':
                $status_query .= $wpdb->prepare(" OR `arm_transaction_status`=%s)",'0');
                break;
            case 'canceled':
                $status_query .= $wpdb->prepare(" OR `arm_transaction_status`=%s)",'2');
                break;
        }
        $where_plog .= $status_query;
    }

    $pt_where = $bt_where = "";
    if(!empty($payment_start_date)) {
        $payment_start_date = date("Y-m-d", strtotime($payment_start_date));
        $pt_where .= $wpdb->prepare(" WHERE `pt`.`arm_created_date` >= %s ",$payment_start_date);
        $bt_where .= $wpdb->prepare(" WHERE `bt`.`arm_created_date` >= %s ",$payment_start_date);
    }

    if(!empty($payment_end_date)) {
        $payment_end_date = date("Y-m-d", strtotime("+1 day", strtotime($payment_end_date)));
        if($pt_where != "") $pt_where .= " AND "; else $pt_where = " WHERE ";
        $pt_where .= $wpdb->prepare(" `pt`.`arm_created_date` < %s ",$payment_end_date);

        if($bt_where != "") $bt_where .= " AND "; else $bt_where = " WHERE ";
        $bt_where .= $wpdb->prepare(" `bt`.`arm_created_date` < %s ",$payment_end_date);
    }

    $search_ = "";
    if ($sSearch != '') {
        $search_ =  $wpdb->prepare(" AND (`arm_payment_history_log`.`arm_transaction_id` LIKE %s OR `arm_payment_history_log`.`arm_payer_email` LIKE %s OR `arm_payment_history_log`.`arm_created_date` LIKE %s OR `arm_user_email` LIKE %s ) ",'%'.$sSearch.'%','%'.$sSearch.'%','%'.$sSearch.'%','%'.$sSearch.'%');
    }

    $orderby = "ORDER BY `arm_payment_history_log`.`arm_invoice_id` DESC";
    $ctquery = "SELECT pt.arm_log_id,pt.arm_invoice_id,pt.arm_user_id,pt.arm_first_name,pt.arm_last_name,pt.arm_plan_id,pt.arm_payer_email,pt.arm_transaction_id,pt.arm_amount,pt.arm_currency,pt.arm_is_trial,pt.arm_payment_gateway,pt.arm_payment_mode,pt.arm_transaction_status,pt.arm_created_date,pt.arm_payment_type,pt.arm_extra_vars,sp.arm_subscription_plan_name,wpu.user_login as arm_user_login, wpu.user_email as arm_user_email, pt.arm_display_log as arm_display_log,pt.arm_is_post_payment as arm_is_post_payment FROM `" . $ARMember->tbl_arm_payment_log . "` pt LEFT JOIN `" . $ARMember->tbl_arm_subscription_plans . "` sp ON pt.arm_plan_id = sp.arm_subscription_plan_id LEFT JOIN `" . $wpdb->users . "` wpu ON pt.arm_user_id = wpu.ID " . $pt_where;
    $ptquery = "{$ctquery}";
        
    $payment_grid_query = "SELECT * FROM (" . $ptquery . ") AS arm_payment_history_log {$where_plog} {$search_} {$orderby}";

    $payment_log = $wpdb->get_results($payment_grid_query, ARRAY_A);//phpcs:ignore --Reason payment_grid_query is a query name

        $final_log = array();
        $tmp = array (
            "Transaction_Id" => '',
            "Invoice_Id" => '',
            "First_Name" => '',
            "Last_Name" => '',
            "User" => '',
            "User Email" => '',
            "Membership" => '',
            "Gateway" => '',
            "Payment_Type" => '',
            "Payer_Email" => '',
            "Transaction_Status" => '',
            "Payment_Date" => '',
            "Amount" => '',
            "Credit_Card_Number" => ''
        );
        foreach ($payment_log as $row) {
            $ccn = maybe_unserialize($row["arm_extra_vars"]);
            $arm_transaction_status = $row["arm_transaction_status"];
            switch ($arm_transaction_status) {
                case '0':
                    $arm_transaction_status = 'pending';
                    break;
                case '1':
                    $arm_transaction_status = 'success';
                    break;
                case '2':
                    $arm_transaction_status = 'canceled';
                    break;
                default:
                    $arm_transaction_status = $row["arm_transaction_status"];
                    break;
            }
            $tmp["Transaction_Id"] = $row["arm_transaction_id"];
            if($tmp["Transaction_Id"] == "-") {
                $tmp["Transaction_Id"] = "";
            }
            $tmp["Invoice_Id"] = $row["arm_invoice_id"];
            $tmp["First_Name"] = $row["arm_first_name"];
            $tmp["Last_Name"] = $row["arm_last_name"];
            $tmp["User"] = $row["arm_user_login"];
            $tmp["User Email"] = $row["arm_user_email"];
            $tmp["Membership"] = $row["arm_subscription_plan_name"];
            $tmp["Gateway"] = $row["arm_payment_gateway"] == "" ? esc_html__('Manual', 'ARMember') : $arm_payment_gateways->arm_gateway_name_by_key($row["arm_payment_gateway"]);
            $tmp["Payment_Type"] = "";
            $tmp["Payer_Email"] = $row["arm_payer_email"];
            $tmp["Transaction_Status"] = $arm_transaction_status;
            $tmp["Payment_Date"] = date_i18n($date_time_format, strtotime($row["arm_created_date"]));
            $tmp["Amount"] = $row["arm_amount"] . " " . $row["arm_currency"];
            $tmp["Credit_Card_Number"] = isset($ccn["card_number"]) ? $ccn["card_number"] : '';
            if($tmp["Credit_Card_Number"] == "-") {
                $tmp["Credit_Card_Number"] = "";
            }

            $log_payment_mode = $row["arm_payment_type"];
            $plan_id = $row["arm_plan_id"];
            $plan_info = new ARM_Plan($plan_id);
            $payment_type_text = $user_payment_mode = "";

            if($plan_info->is_recurring()) {
                if($log_payment_mode != '') {
                    if($log_payment_mode == 'manual_subscription') {
                        $user_payment_mode .= "";
                    }
                    else {
                        $user_payment_mode .= "(" . esc_html__('Automatic','ARMember') . ")";
                    }
                }
                $payment_type = 'subscription';
            }

            if($payment_type =='one_time') {
                $payment_type_text = esc_html__('One Time', 'ARMember');
            }
            else if($payment_type == 'subscription') {
                $payment_type_text = esc_html__('Subscription', 'ARMember');
            }

            if($row["arm_is_trial"] == 1) {
                $arm_trial = "(" . esc_html__('Trial Transaction','ARMember') . ")";
            }
            else {
                $arm_trial = '';
            }

            $tmp["Payment_Type"] = $payment_type_text . " " . $user_payment_mode . " " . $arm_trial;
            array_push($final_log, $tmp);
        }

        ob_clean();
        ob_start();
        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename=ARMember-export-paid-post-payment-history.csv");
        header("Content-Transfer-Encoding: binary");
        $df = fopen("php://output", 'w');
        fputcsv($df, array_keys($tmp));
        if(!empty($final_log)) {
            foreach ($final_log as $row) {
                fputcsv($df, $row);
            }
        }
        fclose($df);
        exit;
}
?>
<style type="text/css">
    #armmanagesearch_pp_new{ width:150px; }
    @media all and (min-width:1400px) {
        #armmanagesearch_pp_new{
            width:200px;
        }
    }
    @media all and (min-width:1600px) {
        #armmanagesearch_pp_new{
            width:250px;
        }
    }
</style>
<script type="text/javascript" charset="utf-8">
// <![CDATA[

    jQuery(document).ready(function () {
        jQuery('#paid_post_transactions_list_form .dataTables_scroll').hide();
        jQuery('#paid_post_transactions_list_form .footer').hide();
        arm_load_paid_post_transaction_list_grid(false);
        var count_checkbox = jQuery('.chkstanard:checked').length;
		if(count_checkbox > 0)
		{
			jQuery('.arm_pp_datatable_filters_options.arm_filters_searchbox').addClass('hidden_section');
			jQuery('.arm_bulk_action_section').removeClass('hidden_section');
		}
		else{
			jQuery('.arm_pp_datatable_filters_options.arm_filters_searchbox').removeClass('hidden_section');
			jQuery('.arm_bulk_action_section').addClass('hidden_section');
		}
    });

    jQuery(document).on('change','.chkstanard',function()
	{
		var count_checkbox = jQuery('.chkstanard:not(#pp-cb-select-all-1):checked').length;
		var total_checkbox = jQuery('.arm_transaction_bulk_check').length;
		if(count_checkbox > 0)
		{
			jQuery('.arm_selected_chkcount').html(count_checkbox);
			jQuery('.arm_selected_chkcount_total').html(total_checkbox);
			jQuery('.arm_pp_datatable_filters_options.arm_filters_searchbox').addClass('hidden_section');
			jQuery('.arm_bulk_action_section').removeClass('hidden_section').show();
		}
		else{
			jQuery('.arm_pp_datatable_filters_options.arm_filters_searchbox').removeClass('hidden_section');
			jQuery('.arm_bulk_action_section').addClass('hidden_section').hide();
		}
	});

    jQuery(document).on('click','.arm_reset_bulk_action',function(){
		jQuery('.chkstanard:checked').each(function(){
			jQuery(this).prop('checked',false).trigger('change');
		})
	});

    jQuery(document).on('click','#arm_pp_transaction_grid_filter_btn',function(){
		var is_filtered = 0;
        var is_before_filtered = 0;
        hideConfirmBoxCallback_close_filter('manage_pp_transaction_filter');
        if(!jQuery('.arm_pp_transaction_filter_options.arm_filter_data_options').hasClass('hidden_section'))
        {
            is_before_filtered = 1;
        }
        else{
            is_before_filtered = 0;
        }
		jQuery('.arm_filter_data_options').removeClass('hidden_section');
		
		//gateway fileter
		jQuery('.arm_gateway_filter_pp_value').html('');
		var gateway_id = jQuery('#arm_filter_pp_gateway').val();
		if(gateway_id != '0'){           
			jQuery('.arm_transaction_gateway_filters_pp').removeClass('hidden_section');
			var gateway_label = jQuery('ul[data-id="arm_filter_pp_gateway"]').find('li[data-value="'+gateway_id+'"]').attr('data-label');
			jQuery('.arm_gateway_filter_pp_value').html(gateway_label);
		}
		else{
			if(!jQuery('.arm_transaction_gateway_filters_pp').hasClass('hidden_section'))
			{
				jQuery('.arm_transaction_gateway_filters_pp').addClass('hidden_section');
			}
		}
		

		jQuery('.arm_payment_type_filter_pp_value').html('');
		var type_val = jQuery('#arm_filter_pptype').val();
		if(type_val != '0'){
			jQuery('.arm_transaction_type_gateway_filters_pp').removeClass('hidden_section');
			var payment_type_label = jQuery('ul[data-id="arm_filter_pptype"]').find('li[data-value="'+type_val+'"]').attr('data-label');
			jQuery('.arm_payment_type_filter_pp_value').html(payment_type_label);
		}
		else{
			if(!jQuery('.arm_transaction_type_gateway_filters_pp').hasClass('hidden_section'))
			{
				jQuery('.arm_transaction_type_gateway_filters_pp').addClass('hidden_section');
			}
		}

		jQuery('.arm_payment_mode_filter_pp_value').html('');
		var mode_val = jQuery('#arm_filter_ppmode').val();
		if(mode_val != '0'){
			jQuery('.arm_transaction_mode_gateway_filters_pp').removeClass('hidden_section');
			var payment_mode_label = jQuery('ul[data-id="arm_filter_ppmode"]').find('li[data-value="'+mode_val+'"]').attr('data-label');
			jQuery('.arm_payment_mode_filter_pp_value').html(payment_mode_label);
		}
		else{
			if(!jQuery('.arm_transaction_mode_gateway_filters_pp').hasClass('hidden_section'))
			{
				jQuery('.arm_transaction_mode_gateway_filters_pp').addClass('hidden_section');
			}
		}

		jQuery('.arm_payment_status_filter_pp_value').html('');
		var payment_status_val = jQuery('#arm_filter_ppstatus').val();
		if(payment_status_val != '0'){
			jQuery('.arm_transaction_statys_gateway_filters_pp').removeClass('hidden_section');
			var payment_status_label = jQuery('ul[data-id="arm_filter_ppstatus"]').find('li[data-value="'+payment_status_val+'"]').attr('data-label');		
			jQuery('.arm_payment_status_filter_pp_value').html(payment_status_label);
		}
		else{
			if(!jQuery('.arm_transaction_statys_gateway_filters_pp').hasClass('hidden_section'))
			{
				jQuery('.arm_transaction_statys_gateway_filters_pp').addClass('hidden_section')
			}
		}

		jQuery('.arm_payment_start_date_filter_pp_value').html('');
		var payment_start_dt_val = jQuery('#arm_filter_ppstart_date').val();
		if(payment_start_dt_val != ''){
			jQuery('.arm_transaction_start_date_gateway_filters_pp').removeClass('hidden_section');
			jQuery('.arm_payment_start_date_filter_pp_value').html(payment_start_dt_val);
		}
		else{
			if(!jQuery('.arm_transaction_start_date_gateway_filters_pp').hasClass('hidden_section')){
				jQuery('.arm_transaction_start_date_gateway_filters_pp').addClass('hidden_section');
			}
		}

		jQuery('.arm_payment_end_date_filter_pp_value').html('');
		var payment_end_dt_val = jQuery('#arm_filter_ppend_date').val();
		if(payment_end_dt_val != ''){
			jQuery('.arm_transaction_end_date_gateway_filters_pp').removeClass('hidden_section');
			jQuery('.arm_payment_end_date_filter_pp_value').html(payment_end_dt_val);
		}
		else{
			if(!jQuery('.arm_transaction_end_date_gateway_filters_pp').hasClass('hidden_section'))
			{
				jQuery('.arm_transaction_end_date_gateway_filters_pp').addClass('hidden_section');
			}
		}

        if(payment_end_dt_val != '' || payment_start_dt_val != '' || gateway_id != '0' || type_val != '0' || mode_val != '0' || payment_status_val != '0'){
            is_filtered = 1;
        }
        else{
            is_filtered = 0;
        }
        if(is_filtered == 1 || is_before_filtered == 1)
        {
            if(is_filtered == 1)
            {
                jQuery('.arm_pp_transaction_filter_options.arm_filter_data_options').removeClass('hidden_section');
                jQuery('.arm_transaction_filters_pp_items').removeClass('hidden_section');
            }
            else{
                if(!jQuery('.arm_pp_transaction_filter_options.arm_filter_data_options').hasClass('hidden_section'))
                {
                    jQuery('.arm_pp_transaction_filter_options.arm_filter_data_options').addClass('hidden_section');
                }
            }
            jQuery('.arm_reset_bulk_action').trigger('click');	
            
            jQuery('.arm_confirm_back_wrapper_filter').trigger('click');
            setTimeout(function () {
                arm_load_paid_post_trasaction_list_filtered_grid();
            },200);
        }
        else{
            if(!jQuery('.arm_pp_transaction_filter_options.arm_filter_data_options').hasClass('hidden_section'))
            {
                jQuery('.arm_pp_transaction_filter_options.arm_filter_data_options').addClass('hidden_section');
            }      
        }
		
	});

    function arm_load_paid_post_trasaction_list_filtered_grid() {
        jQuery('#arm_paid_post_payment_grid_filter_btn').attr('disabled', 'disabled');
        jQuery('#armember_datatable_1').dataTable().fnDestroy();
        arm_load_paid_post_transaction_list_grid(true);
    }
    function arm_reset_pp_transaction_grid_filters(){
        hideConfirmBoxCallback_filter('manage_pp_transaction_filter');
        jQuery('#arm_filter_ppstart_date').val('').trigger('change');
		jQuery('#arm_filter_ppend_date').val('').trigger('change');
		if (jQuery.isFunction( jQuery().datetimepicker )) {
			jQuery('#arm_filter_ppstart_date,#arm_filter_ppend_date').val('').datetimepicker()
		}
    }
	function arm_reset_pp_transaction_grid(){
        arm_reset_pp_transaction_grid_filters();
		if(!jQuery('.arm_filter_data_options').hasClass('hidden_section'))
		{
            jQuery('.arm_transaction_filters_pp_items').addClass('hidden_section');
            jQuery('.manage_pp_transaction_filter_btn').removeAttr('disabled', 'disabled');
            jQuery('.manage_pp_transaction_filter_btn').removeClass('armopen');
            jQuery('#armember_datatable_1').dataTable().fnDestroy();
            arm_load_paid_post_transaction_list_grid(false);
        }
	}
	jQuery(document).on('keyup','#armmanagesearch_pp_new', function (e) {
		// e.stopPropagation();
		if (e.keyCode == 13 || 'Enter' == e.key) {
			var arm_search_val = jQuery(this).val();
			jQuery('#armmanagesearch_pp_new').val(arm_search_val);				
			jQuery('#armember_datatable_1').dataTable().fnDestroy();
			arm_load_paid_post_transaction_list_grid(true);
			return false;
		}
	});

    function show_grid_loader(){
		jQuery('#paid_post_transactions_list_form .dataTables_scroll').hide();
        jQuery('#paid_post_transactions_list_form .footer').hide();
		jQuery('#paid_post_transactions_list_form .arm_loading_grid').show();
	}

    function arm_load_paid_post_transaction_list_grid(is_filtered) {
        var __ARM_Showing = '<?php echo addslashes(esc_html__('Showing','ARMember')); //phpcs:ignore?>';
        var __ARM_Showing_empty = '<?php echo addslashes(esc_html__('Showing 0 to 0 of 0 transactions','ARMember')); //phpcs:ignore?>';
        var __ARM_to = '<?php echo addslashes(esc_html__('to','ARMember')); //phpcs:ignore?>';
        var __ARM_of = '<?php echo addslashes(esc_html__('of','ARMember')); //phpcs:ignore?>';
        var __ARM_transactions = '<?php esc_html_e('entries','ARMember'); //phpcs:ignore?>';
        var __ARM_Show = '<?php echo addslashes(esc_html__('Transactions per page','ARMember')); //phpcs:ignore?>';
        var __ARM_NO_FOUNT = '<?php echo addslashes(esc_html__('No any transaction found yet.','ARMember')); //phpcs:ignore?>';
        var __ARM_NO_MATCHING = '<?php echo addslashes(esc_html__('No matching transactions found.','ARMember')); //phpcs:ignore?>';
        var __ARM_Paid_Post_Transaction_List_right = [13];
        var payment_gateway = jQuery("#arm_filter_pp_gateway").val();
        var payment_type = jQuery("#arm_filter_pptype").val();
        var payment_mode = jQuery("#arm_filter_ppmode").val();
        var payment_status = jQuery("#arm_filter_ppstatus").val();
        var search_term = jQuery("#armmanagesearch_pp_new").val();
        var payment_start_date = jQuery("#arm_filter_ppstart_date").val();
        var payment_end_date = jQuery("#arm_filter_ppend_date").val();
        var ajaxurl = "<?php echo admin_url('admin-ajax.php') //phpcs:ignore?>";
        var filtered_data = (typeof is_filtered !== 'undefined' && is_filtered !== false) ? true : false;
        var _wpnonce = jQuery('input[name="arm_wp_nonce"]').val();
        var oTables = jQuery('#armember_datatable_1').dataTable({
            "bProcessing": false,           
            "oLanguage": {
                    "sProcessing": show_grid_loader(),
                    "sInfo": __ARM_Showing + " _START_ " + __ARM_to + " _END_ " + __ARM_of + " _TOTAL_ " + __ARM_transactions,
                    "sInfoEmpty": __ARM_Showing_empty,
                   
                    "sLengthMenu": __ARM_Show + "_MENU_",
                    "sEmptyTable": __ARM_NO_FOUNT,
                    "sZeroRecords": __ARM_NO_MATCHING
                },
            "language":{
                "searchPlaceholder": "<?php esc_html_e( 'Search', 'ARMember' ); ?>",
                "search":"",
            },
            "buttons":[
				{	
					"className":"ColVis_Button TableTools_Button ui-button ui-state-default ColVis_MasterButton arm_margin_right_10 manage_pp_transaction_filter_btn",
					"text":"<span class=\"armshowhideicon\" style=\"background-image: url(<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore ?>/show_hide_icon.svg);background-repeat: no-repeat;background-position: 0 center;padding: 0 0 0 25px;margin-right\"><?php esc_html_e('Filters','ARMember');?></span>",
					"action": function (e, dt, node, config) {
						showConfirmBoxCallback_filter('manage_pp_transaction_filter');
                    }
				},
                {
                "extend":"colvis",
                "columns":":not(.noVis)",
                "className":"ColVis_Button TableTools_Button ui-button ui-state-default ColVis_MasterButton arm_show_hide_columns",
                "text":"<span class=\"armshowhideicon\" style=\"background-image: url(<?php echo MEMBERSHIPLITE_IMAGES_URL; //phpcs:ignore?>/two_column.svg);background-repeat: no-repeat;background-position: 8px center;padding: 0 10px 0 30px;\"><?php esc_html_e('Columns','ARMember');?></span>",
            }],      
            "bServerSide": true,
            "sAjaxSource": __ARMAJAXURL,
            "sServerMethod": "POST",
            "fnServerParams": function (aoData) {
                aoData.push({"name": "action", "value": "arm_load_transactions"});
                aoData.push({"name": "gateway", "value": payment_gateway});
                aoData.push({"name": "payment_type", "value": payment_type});
                aoData.push({"name": "payment_status", "value": payment_status});
                aoData.push({"name": "payment_mode", "value": payment_mode});
                aoData.push({"name": "payment_start_date", "value": payment_start_date});
                aoData.push({"name": "payment_end_date", "value": payment_end_date});
                aoData.push({"name": "sSearch", "value": search_term});
                aoData.push({"name": "sColumns", "value": null});
                aoData.push({"name": "_wpnonce", "value": _wpnonce});
                aoData.push({"name": "arm_is_post_payment", "value": '1'});
            },
            "bRetrieve": false,
            "sDom": '<"H"CBfr>t<"footer"ipl>',
            "sPaginationType": "four_button",
            "bJQueryUI": true,
            "bPaginate": true,
            "bAutoWidth": false,
            "sScrollX": "100%",
            "bScrollCollapse": true,
            "oColVis": {
                "aiExclude": [0, <?php echo count($default_hide) + 1; ?>]
            },
            "aoColumnDefs": [
                {"aTargets":[0],"sClass":"noVis"},
                {"sType": "html", "bVisible": false, "aTargets": [<?php echo $column_hide; //phpcs:ignore?>]},
                {"bSortable": false, "aTargets": [0]},
                {"sClass": "dt-right", "aTargets": __ARM_Paid_Post_Transaction_List_right},     
                {"sClass":"center","aTargets":[0]}
            ],
            "bStateSave": true,
            "iCookieDuration": 60 * 60,
            "sCookiePrefix": "arm_datatable_",
            "aLengthMenu": [10, 25, 50, 100, 150, 200],
            "fnPreDrawCallback": function () {
                show_grid_loader();
            },
            "fnCreatedRow": function( nRow, aData, iDataIndex ) {
                jQuery(nRow).find('.arm_grid_action_btn_container').each(function () {
                    jQuery(this).parent().addClass('armGridActionTD');
                    jQuery(this).parent().attr('data-key', 'armGridActionTD');
                });
            },
            "stateSaveParams":function(oSettings,oData){
                oData.start=0;
            },
            "fnDrawCallback": function () {
                arm_show_data();
                jQuery('#paid_post_transactions_list_form .arm_loading_grid').hide();
                jQuery('#paid_post_transactions_list_form .dataTables_scroll').show();
                jQuery('#paid_post_transactions_list_form .footer').show();
                jQuery(".cb-select-all-th").removeClass('sorting_asc');
                jQuery("#cb-select-all-1").prop("checked", false);
                arm_selectbox_init();
                if (filtered_data == true) {
                    var filter_box = jQuery('#arm_filter_wrapper_after_filter').html();
                    jQuery('div#armember_datatable_1_filter').parent().append(filter_box);
                    jQuery('div#armember_datatable_1_filter').hide();
                }
                filtered_data = false;
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
                oTables.dataTable().fnAdjustColumnSizing(false);
                jQuery('#arm_paid_post_payment_grid_filter_btn').removeAttr('disabled');
            },
            "fnStateSave": function (oSettings, oData) {
                oData.aaSorting = [];
                oData.abVisCols = [];
                oData.aoSearchCols = [];
                oData.iStart = 0;
                this.oApi._fnCreateCookie(
                    oSettings.sCookiePrefix + oSettings.sInstance,
                    this.oApi._fnJsonString(oData),
                    oSettings.iCookieDuration,
                    oSettings.sCookiePrefix,
                    oSettings.fnCookieCallback
                );
            },
            "fnStateLoadParams": function (oSettings, oData) {
                oData.iLength = 10;
               // oData.oSearch.sSearch = search_term;
            },
        });
        var filter_box = jQuery('#arm_filter_wrapper_1').html();
        jQuery('div#armember_datatable_1_filter').parent().append(filter_box);
        jQuery('div#armember_datatable_1_filter').hide();
        jQuery('#armmanagesearch_pp_new').on('keyup', function (e) {
            e.stopPropagation();
            if (e.keyCode == 13) {
                var gateway = jQuery('#arm_filter_pp_gateway').val();
                var ptype = jQuery('#arm_filter_pptype').val();
                var pstatus = jQuery('#arm_filter_ppstatus').val();
                var search = jQuery('#armmanagesearch_pp_new').val();
                arm_reload_log_list(gateway, ptype, pstatus, search);
                return false;
            }
        });
        if(search_term != ''){
			jQuery('.arm_datatable_searchbox').find('#armmanagesearch_pp_new').val(search_term);
		}
    }
    function ChangeID(id, type)
    {
        document.getElementById('pp_delete_id').value = id;
        document.getElementById('pp_delete_type').value = type;
    }
    function ArmPPChangeStatus(id, status)
    {
        document.getElementById('pp_log_id').value = id;
        document.getElementById('pp_log_status').value = status;
    }
// ]]>
</script>
<div class="arm_pp_transactions_list arm_main_wrapper_seperator">
    <div class="arm_filter_wrapper" id="arm_filter_wrapper_1" style="display:none;">
        <div class="arm_pp_datatable_filters_options arm_pp_transaction_filter_options arm_bulk_action_section hidden_section">
            <span class="arm_reset_bulk_action"></span><span class="arm_selected_chkcount"></span>&nbsp;&nbsp;<span><?php esc_html_e('of','ARMember');?></span>&nbsp;&nbsp;<span class="arm_selected_chkcount_total"></span>&nbsp;&nbsp;<span><?php esc_html_e('Selected','ARMember');?></span><div class="arm_margin_right_10"></div><div class="arm_margin_left_10"></div>
            <div class='sltstandard'>
                <input type='hidden' id='arm_transaction_bulk_action1' name="action1" value="-1" />
                <dl class="arm_selectbox column_level_dd arm_width_160">
                    <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete" /><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                    <dd>
                        <ul data-id="arm_transaction_bulk_action1">
                            <li data-label="<?php esc_html_e('Bulk Actions', 'ARMember'); ?>" data-value="-1"><?php esc_html_e('Bulk Actions', 'ARMember'); ?></li>
                            <li data-label="<?php esc_html_e('Delete', 'ARMember'); ?>" data-value="delete_transaction"><?php esc_html_e('Delete', 'ARMember'); ?></li>
                        </ul>
                    </dd>
                </dl>
            </div>
            <input type="submit" id="doaction1" class="armbulkbtn armemailaddbtn" value="<?php esc_html_e('Go', 'ARMember'); ?>"/>
        </div>
    	<div class="arm_pp_datatable_filters_options arm_pp_transaction_filter_options arm_filters_searchbox">
            <div class="sltstandard">
                <div class="arm_dt_filter_block arm_datatable_searchbox">
                    <div class="arm_datatable_filter_item">
                        <label><input type="text" placeholder="<?php esc_html_e('Search', 'ARMember'); ?>" id="armmanagesearch_pp_new" value="<?php echo esc_attr($filter_search); ?>" tabindex="-1" ></label>
                    </div>
                </div>
            </div>
        </div>
        <div class="arm_pp_datatable_filters_options arm_pp_transaction_filter_options arm_filter_data_options hidden_section">
            <div class="sltstandard">
                <div class="arm_dt_filter_block arm_datatable_searchbox">
                    <div class="arm_datatable_filter_item arm_transaction_filters_pp_items arm_membership_plan_filters_items">
                        <div class="arm_transaction_gateway_filters arm_transaction_gateway_filters_pp arm_filter_view_section hidden_section">
                            <?php esc_html_e('Gateway','ARMember')?>&nbsp;&nbsp;<span class="arm_gateway_filter_pp_value arm_plan_filter_value"></span>
                        </div>

                        <div class="arm_transaction_type_gateway_filters_pp arm_filter_view_section">
                            <?php esc_html_e('Type','ARMember')?>&nbsp;&nbsp;<span class="arm_payment_type_filter_pp_value arm_plan_filter_value"></span>
                        </div>

                        <div class="arm_transaction_mode_gateway_filters_pp arm_filter_view_section">
                            <?php esc_html_e('Mode','ARMember')?>&nbsp;&nbsp;<span class="arm_payment_mode_filter_pp_value arm_plan_filter_value"></span>
                        </div>

                        <div class="arm_transaction_statys_gateway_filters_pp arm_filter_view_section">
                            <?php esc_html_e('Status','ARMember')?>&nbsp;&nbsp;<span class="arm_payment_status_filter_pp_value arm_plan_filter_value"></span>
                        </div>

                        <div class="arm_transaction_start_date_gateway_filters_pp arm_filter_view_section">
                            <?php esc_html_e('Start Date','ARMember')?>&nbsp;&nbsp;<span class="arm_payment_start_date_filter_pp_value arm_plan_filter_value"></span>
                        </div>

                        <div class="arm_transaction_end_date_gateway_filters_pp arm_filter_view_section">
                            <?php esc_html_e('End Date','ARMember')?>&nbsp;&nbsp;<span class="arm_payment_end_date_filter_pp_value arm_plan_filter_value"></span>
                        </div>

                        <a href="javascript:void(0)" class="arm_pp_transaction_list_filters_items_reset" onclick="arm_reset_pp_transaction_grid();"><?php esc_html_e('Clear Filters','ARMember');?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="arm_confirm_box arm_filter_confirm_box" id="arm_confirm_box_manage_pp_transaction_filter">
        <div class="arm_confirm_box_body">
            <div style="margin-left: 24px">
                <span class="arm_font_size_14 arm_filter_confirm_header"><?php esc_html_e('Add Filters','ARMember');?></span>
            </div>
            <div class="arm_solid_divider"></div>
            <div class="arm_confirm_box_btn_container">
                <table>
                    <?php if ( ! empty( $payment_gateways ) ) : ?>
                    <tr class="arm_child_user_row">
                        <th>
                            <?php esc_html_e('Payment Gateway','ARMember');?>
                        </th>
                        <td>
                            <div class="arm_datatable_filter_item arm_filter_gateway_label">
                                <input type="hidden" id="arm_filter_pp_gateway" class="arm_filter_pp_gateway" value="<?php echo esc_attr($filter_gateway); ?>" />
                                <dl class="arm_selectbox arm_width_220">
                                    <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                    <dd>
                                        <ul data-id="arm_filter_pp_gateway">
                                            <li data-label="<?php esc_attr_e( 'Gateway', 'ARMember' ); ?>" data-value="0"><?php esc_html_e( 'Gateway', 'ARMember' ); ?></li>
                                            <li data-label="<?php esc_attr_e( 'Manual', 'ARMember' ); ?>" data-value="<?php esc_attr_e( 'manual', 'ARMember' ); ?>"><?php esc_html_e( 'Manual', 'ARMember' ); ?></li>
                                            <?php foreach ( $payment_gateways as $key => $pg ) : ?>
                                                <li data-label="<?php echo esc_attr($pg['gateway_name']); ?>" data-value="<?php echo esc_attr($key); ?>"><?php echo esc_attr($pg['gateway_name']); ?></li>                                    
                                            <?php endforeach; ?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr class="arm_child_user_row">
                        <th>
                            <?php esc_html_e('Payment Type','ARMember');?>
                        </th>
                        <td>
                            <div class="arm_datatable_filter_item arm_filter_pptype_label">
                                <input type="hidden" id="arm_filter_pptype" class="arm_filter_pptype" value="<?php echo esc_html($filter_ptype); ?>" />
                                <dl class="arm_selectbox arm_width_220 arm_min_width_60">
                                    <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                    <dd>
                                        <ul data-id="arm_filter_pptype">
                                            <li data-label="<?php esc_attr_e( 'Payment Type', 'ARMember' ); ?>" data-value="0"><?php esc_html_e( 'Payment Type', 'ARMember' ); ?></li>
                                            <li data-label="<?php esc_attr_e( 'One Time', 'ARMember' ); ?>" data-value="one_time"><?php esc_html_e( 'One Time', 'ARMember' ); ?></li>
                                            <li data-label="<?php esc_attr_e( 'Recurring', 'ARMember' ); ?>" data-value="subscription"><?php esc_html_e( 'Recurring', 'ARMember' ); ?></li>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </td>
                    </tr>
                    <tr class="arm_child_user_row">
                        <th>
                            <?php esc_html_e('Payment Mode','ARMember');?>
                        </th>
                        <td>
                            <div class="arm_datatable_filter_item arm_filter_ppmode_label">
                                <input type="hidden" id="arm_filter_ppmode" class="arm_filter_ppmode" value="<?php echo esc_html($filter_pmode); ?>" />
                                <dl class="arm_selectbox arm_width_220 arm_min_width_80">
                                    <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                    <dd>
                                        <ul data-id="arm_filter_ppmode">
                                            <li data-label="<?php esc_attr_e( 'Subscription', 'ARMember' ); ?>" data-value="0"><?php esc_html_e( 'Subscription', 'ARMember' ); ?></li>
                                            <li data-label="<?php esc_attr_e( 'Automatic Subscription', 'ARMember' ); ?>" data-value="auto_debit_subscription"><?php esc_html_e( 'Automatic Subscription', 'ARMember' ); ?></li>
                                            <li data-label="<?php esc_attr_e( 'Semi Automatic Subscription', 'ARMember' ); ?>" data-value="manual_subscription"><?php esc_html_e( 'Semi Automatic Subscription', 'ARMember' ); ?></li>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </td>
                    </tr>
                    <tr class="arm_child_user_row">
                        <th>
                            <?php esc_html_e('Payment Status','ARMember');?>
                        </th>
                        <td>
                            <div class="arm_datatable_filter_item arm_filter_ppstatus_label">
                                <input type="hidden" id="arm_filter_ppstatus" class="arm_filter_ppstatus" value="<?php echo esc_html($filter_pstatus); ?>" />
                                <dl class="arm_selectbox arm_min_width_60 arm_width_220">
                                    <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                    <dd>
                                        <ul data-id="arm_filter_ppstatus">
                                            <li data-label="<?php esc_attr_e( 'Status', 'ARMember' ); ?>" data-value="0"><?php esc_html_e( 'Status', 'ARMember' ); ?></li>
                                            <li data-label="<?php esc_attr_e( 'Success', 'ARMember' ); ?>" data-value="success"><?php esc_html_e( 'Success', 'ARMember' ); ?></li>
                                            <li data-label="<?php esc_attr_e( 'Pending', 'ARMember' ); ?>" data-value="pending"><?php esc_html_e( 'Pending', 'ARMember' ); ?></li>
                                            <li data-label="<?php esc_attr_e( 'Cancelled', 'ARMember' ); ?>" data-value="canceled"><?php esc_html_e( 'Cancelled', 'ARMember' ); ?></li>
                                            <li data-label="<?php esc_attr_e( 'Failed', 'ARMember' ); ?>" data-value="failed"><?php esc_html_e( 'Failed', 'ARMember' ); ?></li>
                                            <li data-label="<?php esc_attr_e( 'Expired', 'ARMember' ); ?>" data-value="expired"><?php esc_html_e( 'Expired', 'ARMember' ); ?></li>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </td>
                    </tr>
                    <tr class="arm_child_user_row">
                        <th>
                            <?php esc_html_e('Start date','ARMember')?>
                        </th>
                        <td>
                            <div class="arm_datatable_filter_item arm_filter_pstatus_label arm_margin_left_0" >
                                <input type="text" id="arm_filter_ppstart_date" class="arm_min_width_60 arm_width_220" placeholder="<?php esc_attr_e( 'Start Date', 'ARMember' ); ?>" data-date_format="m/d/Y"/>
                            </div>
                        </td>
                    </tr>
                    <tr class="arm_child_user_row">
                        <th>
                            <?php esc_html_e('End date','ARMember')?>
                        </th>
                        <td>
                            <div class="arm_datatable_filter_item arm_filter_pstatus_label">
                                <input type="text" id="arm_filter_ppend_date" class="arm_min_width_60 arm_width_220" placeholder="<?php esc_attr_e( 'End Date', 'ARMember' ); ?>" data-date_format="m/d/Y"/>
                            </div>
                        </td>
                    </tr>
                    <tr class="arm_child_user_row">
                        <th></th>
                        <td>
                            <input type="button" class="arm_cancel_btn arm_margin_0" id="arm_member_grid_filter_clr_btn" onclick="arm_reset_pp_transaction_grid_filters();" value="<?php esc_html_e('Clear','ARMember');?>">
                            <input type="button" class="armemailaddbtn" id="arm_pp_transaction_grid_filter_btn" value="<?php esc_html_e('Apply','ARMember');?>">
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <form method="GET" id="paid_post_transactions_list_form" class="data_grid_list" onsubmit="return arm_paid_post_transactions_list_form_bulk_action();">
        <input type="hidden" name="page" value="<?php echo esc_attr($arm_slugs->transactions); ?>" />
        <input type="button" id="arm_paid_post_payment_grid_export_btn" class="hidden_section"/>
        <input type="hidden" name="armaction" value="list" />
        <div id="armmainformnewlist" class="arm_filter_grid_list_container">
            <div class="arm_loading_grid" style="display: none;"><?php echo $arm_common_lite->arm_loader_img_func();?></div>
            <div class="response_messages"></div>
            <table cellpadding="0" cellspacing="0" border="0" class="display arm_hide_datatable" id="armember_datatable_1">
                <thead>
                    <tr>
                        <th class="center cb-select-all-th arm_max_width_60" ><input id="pp-cb-select-all-1" type="checkbox" class="chkstanard"></th>
                        <th><?php esc_html_e('Transaction ID', 'ARMember'); ?></th>
                        <th><?php esc_html_e('Invoice ID', 'ARMember'); ?></th>
                        <th><?php esc_html_e('First Name', 'ARMember'); ?></th>
                        <th><?php esc_html_e('Last Name','ARMember'); ?></th>
                        <th><?php esc_html_e('User', 'ARMember'); ?></th>
                        <th><?php esc_html_e('User Email', 'ARMember'); ?></th>
                        <th class="arm_min_width_150"><?php esc_html_e('Post Title', 'ARMember'); ?></th>
                        <th><?php esc_html_e('Payment Gateway', 'ARMember'); ?></th>
                        <th><?php esc_html_e('Payment Type', 'ARMember'); ?></th>
                        <th><?php esc_html_e('Payer Email', 'ARMember'); ?></th>
                        <th class="center"><?php esc_html_e('Transaction Status', 'ARMember'); ?></th>
                        <th class="center arm_min_width_150" ><?php esc_html_e('Payment Date', 'ARMember'); ?></th>
                        <th class="center"><?php esc_html_e('Amount', 'ARMember'); ?></th>
                        <th class="center arm_min_width_150" ><?php esc_html_e('Credit Card Number', 'ARMember'); ?></th>
                        <th data-key="armGridActionTD" class="armGridActionTD noVis" style="display: none;"></th>
                    </tr>
                </thead>
            </table>
            <div class="armclear"></div>
            <input type="hidden" name="show_hide_columns" id="paid_post_show_hide_columns" value="<?php esc_html_e('Columns', 'ARMember'); ?>"/>
            <input type="hidden" name="search_grid" id="paid_post_search_grid" value="<?php esc_html_e('Search', 'ARMember'); ?>"/>
            <input type="hidden" name="entries_grid" id="paid_post_entries_grid" value="<?php esc_html_e('transactions', 'ARMember'); ?>"/>
            <input type="hidden" name="show_grid" id="paid_post_show_grid" value="<?php esc_html_e('Show', 'ARMember'); ?>"/>
            <input type="hidden" name="showing_grid" id="paid_post_showing_grid" value="<?php esc_html_e('Showing', 'ARMember'); ?>"/>
            <input type="hidden" name="to_grid" id="paid_post_to_grid" value="<?php esc_html_e('to', 'ARMember'); ?>"/>
            <input type="hidden" name="of_grid" id="paid_post_of_grid" value="<?php esc_html_e('of', 'ARMember'); ?>"/>
            <input type="hidden" name="no_match_record_grid" id="paid_post_no_match_record_grid" value="<?php esc_html_e('No matching transactions found', 'ARMember'); ?>"/>
            <input type="hidden" name="no_record_grid" id="paid_post_no_record_grid" value="<?php esc_html_e('No any transaction found yet.', 'ARMember'); ?>"/>
            <input type="hidden" name="filter_grid" id="paid_post_filter_grid" value="<?php esc_html_e('filtered from', 'ARMember'); ?>"/>
            <input type="hidden" name="totalwd_grid" id="paid_post_totalwd_grid" value="<?php esc_html_e('total', 'ARMember'); ?>"/>
            <?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
            <input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
        </div>
        <div class="footer_grid"></div>
    </form>
</div>

<?php require_once(MEMBERSHIPLITE_VIEWS_DIR.'/arm_view_member_details.php')?>