<?php

if (!class_exists('ARM_subsctriptions')) {

    class ARM_subsctriptions{
        
        function __construct(){
            global $wpdb, $ARMember, $arm_slugs;

            add_action('wp_ajax_get_activity_data',array($this, 'arm_fetch_activity_data'));
            add_action('wp_ajax_get_subscription_data',array($this, 'arm_fetch_subscription_data'));
            add_action('wp_ajax_get_upcoming_subscription_data',array($this, 'arm_fetch_upcoming_subscription_data'));
            add_action('wp_ajax_transaction_activity_ajax_action',array($this, 'arm_delete_transaction_data'));
            add_action('wp_ajax_arm_change_bank_transfer_status', array($this, 'arm_change_bank_transfer_status'));
            add_action('wp_ajax_arm_cancel_subscription_ajax_action',array($this, 'arm_cancel_subscription_data'));
            add_action('wp_ajax_arm_refund_subscription_ajax_action',array($this, 'arm_refund_subscription_action')); 
            add_action('wp_ajax_arm_add_new_subscriptions',array($this,'arm_add_new_subscriptions'));
            add_action('wp_ajax_get_user_all_transaction_details_for_grid',array($this,'get_user_all_transaction_details_for_grid'));      
            add_action('wp_ajax_arm_activation_subscription_plan',array($this,'arm_activation_subscription_plan'),10,2);     
        }
        function arm_activation_subscription_plan()
        {
            global $wp,$wpdb,$ARMember,$arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $global_currency_sym ,$arm_capabilities_global,$arm_members_class;

            $response = array('type'=>'error','msg'=>esc_html__('Something went wrong','ARMember'));
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_subscriptions'], '1',1); //phpcs:ignore --Reason:Verifying nonce

            $activity_id = intval( $_POST['arm_activity'] ); //phpcs:ignore
            $sql_act = $wpdb->prepare('SELECT `arm_user_id`,`arm_item_id` FROM '.$ARMember->tbl_arm_activity.' WHERE arm_activity_id=%d',$activity_id); //phpcs:ignore --Reason $ARMember->tbl_arm_activity is a table name
            
            $get_result_sql = $wpdb->get_row($sql_act,ARRAY_A); //phpcs:ignore --Reason $sql_act is a query name
            $user_id = $get_result_sql['arm_user_id'];
            $plan_id = $get_result_sql['arm_item_id'];

            $post_data=array('arm_action'=>'status','user_id'=>$user_id,'plan_id'=>$plan_id);

            $is_activated = $arm_members_class->arm_user_plan_action($post_data,true);

            if($is_activated['type'] == 'success')
            {
                $response = array('type'=>'success','msg'=>$is_activated['msg']);
            }
            else
            {
                $response = array('type'=>'error','msg'=>$is_activated['msg']);
            }
            echo json_encode($response);
            die;
        }
        function get_user_all_transaction_details_for_grid(){
            global $wp,$wpdb,$ARMember,$arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $arm_transaction,$global_currency_sym ,$arm_capabilities_global;

            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_subscriptions'], '1',1); //phpcs:ignore --Reason:Verifying nonce

            $general_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : array();
            $arm_currency_decimal = isset($general_settings['arm_currency_decimal_digit']) ? $general_settings['arm_currency_decimal_digit'] : 2;

            $arm_invoice_tax_feature = get_option('arm_is_invoice_tax_feature', 0);

            $arm_activity_id = intval( $_POST['activity_id'] );//phpcs:ignore

            $get_result_sql = $wpdb->prepare('SELECT act.*,am.arm_user_login FROM '.$ARMember->tbl_arm_activity.' act LEFT JOIN '.$ARMember->tbl_arm_members.' am ON act.arm_user_id = am.arm_user_id WHERE act.arm_activity_id =%d',$arm_activity_id); //phpcs:ignore --Reason $ARMember->tbl_arm_activity is a table name
            $response_result = $wpdb->get_row($get_result_sql); //phpcs:ignore --Reason $get_result_sql is a sql query
            
            $return='';
            
            if(!empty($response_result))
            {
                $rc = (object) $response_result;
               
                // $get_activity_data = maybe_unserialize($rc->arm_content);
                $grace_period_data = $plan_detail = $membership_start = '';
                $user_id = $rc->arm_user_id;
                $date_format = $arm_global_settings->arm_get_wp_date_format();
                $user_plan_detail = get_user_meta($user_id, 'arm_user_plan_'.$rc->arm_item_id, true);
                $start_plan_date = !empty($rc->arm_activity_plan_start_date) ? $rc->arm_activity_plan_start_date : current_time( 'mysql' );
                $plan_status = $this->get_return_status_data($user_id,$rc->arm_item_id,$user_plan_detail,strtotime($start_plan_date));
                $canceled_date = !empty($plan_status['canceled_date']) ? $plan_status['canceled_date'] : '';
                $transaction_started_date = date('Y-m-d 00:00:00', strtotime($start_plan_date));
                
                if($rc->arm_activity_payment_gateway !='manual')
                {
                    $transaction_started_date =date('Y-m-d H:i:s', ( strtotime($start_plan_date) - 120));
                }
                if(!empty($canceled_date))
                {
                    $get_last_transaction_sql = $wpdb->prepare("SELECT * FROM ".$ARMember->tbl_arm_payment_log." WHERE arm_user_id=%d AND arm_plan_id=%d AND arm_created_date BETWEEN %s AND %s  ORDER BY arm_log_id DESC",$user_id,$rc->arm_item_id,$transaction_started_date,$canceled_date); //phpcs:ignore --Reason: $ARMember->tbl_arm_payment_log is a table name
                }
                else
                {
                    if(!empty($user_plan_detail['arm_trial_start']))
                    {
                        $transaction_started_date = date('Y-m-d H:i:s', ( $user_plan_detail['arm_trial_start'] - 120));
                    }
                    $get_last_transaction_sql = $wpdb->prepare("SELECT * FROM ".$ARMember->tbl_arm_payment_log." WHERE arm_user_id=%d AND arm_plan_id=%d AND arm_created_date >= %s ORDER BY arm_log_id DESC",$user_id,$rc->arm_item_id,$transaction_started_date); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name
                }
                $return .= '<div class="arm_child_row_div"><table class="arm_user_child__transaction_table " cellspacing="1" >';
                $return .= '<tr class="arm_child_transaction_row">';
                $return .= '<th>' . esc_html__('Invoice ID', 'ARMember') . '</th>';
                $return .= '<th>' . esc_html__('Transaction ID', 'ARMember') . '</th>';
                $return .= '<th>' . esc_html__('Subscription ID', 'ARMember') . '</th>';
                $return .= '<th>' . esc_html__('Payment Gateway', 'ARMember') . '</th>';
                $return .= '<th class="dt-right">' . esc_html__('Amount', 'ARMember') . '</th>';
                $return .= '<th class="center">' . esc_html__('Status', 'ARMember') . '</th>';
                $return .= '<th>' . esc_html__('Transaction Date', 'ARMember') . '</th>';
                $return .= '</tr>';
                $response_transaction_result = $wpdb->get_results($get_last_transaction_sql); //phpcs:ignore --Reason $get_last_transaction_sql is a predefined query
                foreach($response_transaction_result as $transactions)
                {
                    $arm_invoice_id = $arm_global_settings->arm_manipulate_invoice_id($transactions->arm_invoice_id);
                    $transactionID = !empty($transactions->arm_transaction_id) ? $transactions->arm_transaction_id : 'manual';
                    $subscription_id = !empty($transactions->arm_token) ? $transactions->arm_token : '-';
                    $arm_transaction_status = $transactions->arm_transaction_status;
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
                            $arm_transaction_status = !empty($rc->arm_transaction_status) ? $rc->arm_transaction_status : 'success';
                            break;
                    }
                    $return .= '<tr class="arm_child_transaction_row">';
                    if($arm_invoice_tax_feature == 1)
                    {
                        $log_type = ($transactions->arm_payment_gateway == 'bank_transfer') ? 'bt_log' : 'other';
                        $return .= '<td><a class="armhelptip arm_invoice_detail tipso_style" href="javacript:void(0)" data-log_id='. esc_attr($transactions->arm_log_id).' data-log_type='. esc_attr($log_type).'>' . esc_html($arm_invoice_id). '</a></td>';
                    }
                    else
                    {
                        $return .= '<td>' . $arm_invoice_id. '</td>';
                    }
                    $return .= '<td class="arm_min_width_200">' . $transactionID . '</td>';
                    $return .= '<td class="arm_min_width_250">' . $subscription_id . '</td>';
                    $return .= '<td>' . $arm_payment_gateways->arm_gateway_name_by_key($transactions->arm_payment_gateway) . '</td>';
                    $return .= '<td class="dt-right arm_min_width_150">' . number_format($transactions->arm_amount,$arm_currency_decimal,'.',',') .' '. $transactions->arm_currency . '</td>';
                    $return .= '<td class="center">' . $arm_transaction->arm_get_transaction_status_text($arm_transaction_status) . '</td>';
                    $return .= '<td>' . date_i18n($date_format, strtotime($transactions->arm_payment_date)) . '</td>';
                    $return .= '</tr>';
                }
                $return .= '</table></div>';
            }
            echo $return; //phpcs:ignore
            die;
        }
        function arm_add_new_subscriptions()
        {
            global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $global_currency_sym,$arm_capabilities_global,$arm_members_class;

            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_subscriptions'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $date_format = $arm_global_settings->arm_get_wp_date_format();
            $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
            $post_data = array();
            $posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data_extend'), $_POST ); //phpcs:ignore
            if(!empty($posted_data))
            {
                $post_data['arm_action'] = 'add';
                $post_data['user_id'] = isset($posted_data['arm_user_id_hidden']) ? $posted_data['arm_user_id_hidden'] : 0;
                $post_data['arm_user_plan'] = isset($posted_data['membership_plan']) ? $posted_data['membership_plan'] : 0;
                $post_data['arm_selected_payment_cycle'] = isset($posted_data['arm_selected_payment_cycle']) ? $posted_data['arm_selected_payment_cycle'] : 0;
                $membership_type = isset($posted_data['plan_type']) ? $posted_data['plan_type'] : 0;
                if($membership_type == '1')
                {
                    $post_data['arm_paid_post_request'] = 1;
                    $post_data['arm_user_plan'] = isset($posted_data['arm_paid_post_id']) ? $posted_data['arm_paid_post_id'] : 0;
                }
                else if($membership_type == '2')
                {
                    $post_data['arm_gift_plan_request'] = 1;
                    $post_data['arm_user_plan'] = isset($posted_data['arm_gift_plan_id']) ? $posted_data['arm_gift_plan_id'] : 0;
                }
                $post_data['arm_subscription_start_date'] = date_i18n($date_format, strtotime(current_time('mysql')));
                $post_data['user_id'] = isset($posted_data['arm_user_id_hidden']) ? intval($posted_data['arm_user_id_hidden']) : 0;
                $old_plan_ids = get_user_meta($posted_data['arm_user_id_hidden'], 'arm_user_plan_ids', true);
                $old_plan_ids = !empty($old_plan_ids) ? $old_plan_ids : array();
                if(!in_array($post_data['arm_user_plan'],$old_plan_ids))
                {
                    $response = $this->add_plan_action($post_data);
                }
                else
                {
                    $response = array('type' => 'error', 'msg' => esc_html__("Membership plan is already exist for selected member.", 'ARMember'));
                }
                echo arm_pattern_json_encode($response);
                die;
            }
            
        }
        function add_plan_action($post_data=array()) {
            global $wpdb, $ARMember, $arm_member_forms, $arm_manage_communication, $is_multiple_membership_feature, $arm_subscription_plans, $arm_members_class, $arm_global_settings, $arm_capabilities_global, $arm_pay_per_post_feature, $arm_subscription_cancel_msg;
            
            $response = array('type' => 'error', 'msg' => esc_html__("Sorry, Something went wrong. Please try again.", 'ARMember'));

            $date_format = $arm_global_settings->arm_get_wp_date_format();
            $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
            if ($post_data['arm_action'] == 'add') {
                $user_ID = !empty($post_data['user_id']) ? intval($post_data['user_id']) : 0;

                do_action('arm_modify_content_on_plan_change', $post_data, $user_ID);

                if (!empty($user_ID)) {
                    if (!isset($post_data['arm_user_plan'])) {
                        $post_data['arm_user_plan'] = 0;
                    } else {
                        if (is_array($post_data['arm_user_plan'])) {
                            foreach ($post_data['arm_user_plan'] as $key => $mpid) {
                                if (empty($mpid)) {
                                    unset($post_data['arm_user_plan'][$key]);
                                } else {
                                    $post_data['arm_subscription_start_' . $mpid] = isset($post_data['arm_subscription_start_date'][$key]) ? $post_data['arm_subscription_start_date'][$key] : '';
                                }
                            }
                            unset($post_data['arm_subscription_start_date']);
                            $post_data['arm_user_plan'] = array_values($post_data['arm_user_plan']);
                        }
                    }
                    unset($post_data['arm_action']);
                    $post_data['action'] = 'update_member';

                    $old_plan_ids = get_user_meta($user_ID, 'arm_user_plan_ids', true);
                    $old_plan_ids = !empty($old_plan_ids) ? $old_plan_ids : array();
                    $old_plan_id = isset($old_plan_ids[0]) ? $old_plan_ids[0] : 0;
                    if (!empty($old_plan_ids)) {
                        foreach ($old_plan_ids as $plan_id) {
                            $field_name = "arm_subscription_expiry_date_" . $plan_id . "_" . $user_ID;
                            if (isset($post_data[$field_name])) {
                                unset($post_data[$field_name]);
                            }
                        }
                    }
                    unset($post_data['user_id']);

                    $arm_old_suscribed_plans = "";


                    $is_paid_post = (!empty($post_data['arm_paid_post_request']) && ($post_data['arm_paid_post_request'] == 1)) ? 1 : 0;
                    $is_gift_plan = (!empty($post_data['arm_gift_plan_request']) && ($post_data['arm_gift_plan_request'] == 1)) ? 1 : 0;

                    if($is_gift_plan || $is_paid_post || $is_multiple_membership_feature->isMultipleMembershipFeature)
                    {
                        if(array_key_exists('arm_user_plan', $post_data))
                        {
                            //Get Old Data Of Subscribed Plans
                            $arm_old_suscribed_plans = get_user_meta($user_ID, 'arm_user_plan_ids', true);

                            $arm_new_subscribed_data = array();
                            array_push($arm_new_subscribed_data, $post_data['arm_user_plan']);

                            if(!empty($arm_old_suscribed_plans))
                            {

                                foreach($arm_old_suscribed_plans as $value)
                                {
                                    if(!in_array($value, $arm_new_subscribed_data))
                                    {
                                        array_push($arm_new_subscribed_data, $value);
                                    }
                                }
                            }


                            $post_data['arm_user_plan'] = $arm_new_subscribed_data;
                        }
                    }
                    $admin_save_flag = 1;
                    do_action('arm_member_update_meta', $user_ID, $post_data, $admin_save_flag);

                    if (isset($post_data['arm_user_plan']) && !empty($post_data['arm_user_plan'])) {
                        if ((is_array($post_data['arm_user_plan']) && $is_multiple_membership_feature->isMultipleMembershipFeature) || ($is_paid_post) || ($is_gift_plan)) {
                            $old_plan_ids = array_intersect($post_data['arm_user_plan'], $old_plan_ids);
                            foreach ($post_data['arm_user_plan'] as $plan_id) {
                                if (!in_array($plan_id, $old_plan_ids)) {
                                    $arm_manage_communication->membership_communication_mail('on_new_subscription', $user_ID, $plan_id);
                                    do_action('arm_after_user_plan_change_by_admin', $user_ID, $plan_id);
                                }
                            }
                        } else {
                            if ($old_plan_id != 0 && $old_plan_id != '') {
                                if ($old_plan_id != $post_data['arm_user_plan']) {
                                    $arm_manage_communication->membership_communication_mail('on_change_subscription_by_admin', $user_ID, $post_data['arm_user_plan']);
                                }
                            } else {
                                $arm_manage_communication->membership_communication_mail('on_new_subscription', $user_ID, $post_data['arm_user_plan']);
                            }
                            do_action('arm_after_user_plan_change_by_admin', $user_ID, $post_data['arm_user_plan']);
                        }
                    }
                    
                    $popup_plan_content = "";
                    if($is_paid_post)
                    {
                        $response = array('type' => 'success', 'msg' => esc_html__("Paid Post added successfully.", 'ARMember'), 'content' => $popup_plan_content);
                    }
                    else
                    {
                        $response = array('type' => 'success', 'msg' => esc_html__("Plan added successfully.", 'ARMember'), 'content' => $popup_plan_content);
                    }

                    $response = apply_filters('arm_modify_admin_plan_add_response', $response, $user_ID, $popup_plan_content, $post_data);
                }
            }

            if (isset($response['type']) && $response['type'] == 'success' && $user_ID > 0) 
            {
                $userPlanIDs = get_user_meta($user_ID, 'arm_user_plan_ids', true);

        		if(!empty($userPlanIDs))
        		{
        			$userPostIDs = get_user_meta($user_ID, 'arm_user_post_ids', true);
                    foreach($userPlanIDs as $arm_plan_key => $arm_plan_val)
                    {
                        if(isset($userPostIDs[$arm_plan_val]) && in_array($userPostIDs[$arm_plan_val], $userPostIDs))
                        {
                            unset($userPlanIDs[$arm_plan_key]);
                        }
                    }
                    $userPlanIDs = apply_filters('arm_modify_plan_ids_externally',$userPlanIDs,$user_ID);
        		}
                $arm_all_user_plans = $userPlanIDs;
                $arm_future_user_plans = get_user_meta($user_ID, 'arm_user_future_plan_ids', true);
                
                if (!empty($arm_future_user_plans)) {
                    $arm_all_user_plans = array_merge($userPlanIDs, $arm_future_user_plans);
                }
                $arm_user_plans = '';
                $plan_names = array();
                $subscription_effective_from = array();
                if (!empty($arm_all_user_plans) && is_array($arm_all_user_plans)) {
                    foreach ($arm_all_user_plans as $userPlanID) {
                        $plan_data = get_user_meta($user_ID, 'arm_user_plan_' . $userPlanID, true);

                        $userPlanDatameta = !empty($plan_data) ? $plan_data : array();
                        $plan_data = shortcode_atts($defaultPlanData, $userPlanDatameta);
                        $subscription_effective_from_date = $plan_data['arm_subscr_effective'];
                        $change_plan_to = $plan_data['arm_change_plan_to'];

                        $plan_names[$userPlanID] = $arm_subscription_plans->arm_get_plan_name_by_id($userPlanID);
                        $subscription_effective_from[] = array('arm_subscr_effective' => $subscription_effective_from_date, 'arm_change_plan_to' => $change_plan_to);
                    }
                }

                if ($is_multiple_membership_feature->isMultipleMembershipFeature) {
                    $response['multiple_membership'] = '1';

                    $arm_user_plans = '<div class="arm_min_width_120">';
                    $arm_user_plans .= "<a href='javascript:void(0)'  id='arm_show_user_more_plans_" . esc_attr($user_ID) . "' class='arm_show_user_more_plans' data-id='" . esc_attr($user_ID) . "'>";

                    if (!empty($arm_all_user_plans) && is_array($arm_all_user_plans)) {
                        foreach ($arm_all_user_plans as $plan_id) {
                            $plan_color_id = ($plan_id > 10) ? intval($plan_id / 10) : $plan_id;
                            $plan_color_id = ($plan_color_id > 10) ? intval($plan_color_id / 10) : $plan_color_id;
                            $arm_user_plans .= "<span class='armhelptip arm_user_plan_circle arm_user_plan_" . $plan_color_id . "' title='" . stripslashes_deep($plan_names[$plan_id]) . "' >";
                            $plan_name = str_replace('-', '', stripslashes_deep($plan_names[$plan_id]));
                            $words = explode(" ", $plan_name);
                            $plan_name = '';
                            foreach ($words as $w) {
                                $w = preg_replace('/[^A-Za-z0-9\-]/', '', $w);
                                $plan_name .= mb_substr($w, 0, 1, 'utf-8');
                            }
                            $plan_name = strtoupper($plan_name);
                            $arm_user_plans .= substr($plan_name, 0, 2);
                            $arm_user_plans .= "</span>";
                        }
                    }
                    $arm_user_plans .= "</a></div>";
                    $response['membership_plan'] = $arm_user_plans;
                } else {
                    $response['multiple_membership'] = '0';
                    $auser = new WP_User($user_ID);
                    $u_role = array_shift($auser->roles);
                    $user_roles = get_editable_roles();
                    if (!empty($user_roles[$u_role]['name'])) {
                        $arm_user_role = $user_roles[$u_role]['name'];
                    } else {
                        $arm_user_role = '-';
                    }
                    $response['user_role'] = $arm_user_role;

                    $memberTypeText = $arm_members_class->arm_get_member_type_text($user_ID);
                    $response['membership_type'] = $memberTypeText;

                    $plan_name = (!empty($plan_names)) ? implode(',', $plan_names) : '-';
                    $response['membership_plan'] = '<span class="arm_user_plan_' . esc_attr( $user_ID ) . '">' . esc_html( stripslashes_deep($plan_name) ) . '</span>';

                    if (!empty($subscription_effective_from)) {
                        foreach ($subscription_effective_from as $subscription_effective) {
                            $subscr_effective = $subscription_effective['arm_subscr_effective'];
                            $change_plan = $subscription_effective['arm_change_plan_to'];
                            $change_plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($change_plan);
                            if (!empty($change_plan) && $subscr_effective > strtotime($nowDate)) {
                                $response['membership_plan'] .= '<div>' . esc_html($change_plan_name) . '<br/> (' . esc_html__('Effective from', 'ARMember') . ' ' . esc_html(date_i18n($date_format, $subscr_effective)) . ')</div>';
                            }
                        }
                    }
                }
            }
            return $response;
            exit;
        }
        function arm_change_bank_transfer_status()
		{
			global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_global_settings, $arm_subscription_plans,$arm_manage_coupons, $arm_debug_payment_log_id, $arm_capabilities_global,$arm_pay_per_post_feature;
				
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_subscriptions'], '1',1); //phpcs:ignore --Reason:Verifying nonce
			
            $log_id = intval($_POST['log_id']);//phpcs:ignore
            $logid_exit_flag = '';
            $new_status = sanitize_text_field($_POST['log_status']);//phpcs:ignore

			$response = array('status' => 'error', 'message' => esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember'));
			if (!empty($log_id) && $log_id != 0) {
				$log_data = $wpdb->get_row( $wpdb->prepare("SELECT `arm_log_id`, `arm_user_id`, `arm_plan_id`, `arm_payment_cycle` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_log_id`=%d" , $log_id) ); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name

                

				do_action('arm_payment_log_entry', 'bank_transfer', 'Change status log data', 'armember', $log_data, $arm_debug_payment_log_id);

				if(!empty($log_data))
				{
					$user_id = $log_data->arm_user_id;
					$plan_id = $log_data->arm_plan_id;
                    $payment_cycle = $log_data->arm_payment_cycle;

                    if ($new_status == '1') {

                    	$plan_payment_mode = 'manual_subscription';
                    	$is_recurring_payment = $arm_subscription_plans->arm_is_recurring_payment_of_user($user_id, $plan_id, $plan_payment_mode);
					
						$nowDate = current_time('mysql');
                        $arm_last_payment_status = $wpdb->get_var( $wpdb->prepare("SELECT `arm_transaction_status` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_user_id`=%d AND `arm_plan_id`=%d AND `arm_created_date`<=%s ORDER BY `arm_log_id` DESC LIMIT 0,1",$user_id,$plan_id,$nowDate) ); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name
					 	$arm_subscription_plans->arm_update_user_subscription_for_bank_transfer($user_id, $plan_id, 'bank_transfer', $payment_cycle, $arm_last_payment_status);
						$wpdb->update($ARMember->tbl_arm_payment_log, array('arm_transaction_status' => 1), array('arm_log_id' => $log_id));
						
						$userPlanData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
						$arm_manage_coupons->arm_coupon_apply_to_subscription($user_id, $log_data, 'bank_transfer', $userPlanData);
						
						if($is_recurring_payment)
						{
							do_action('arm_after_recurring_payment_success_outside', $user_id, $plan_id, 'bank_transfer', $plan_payment_mode);
						}

                        
                        if( $arm_pay_per_post_feature->isPayPerPostFeature)
                        {
                            $arm_pay_per_post_feature->arm_assign_paid_post_to_user($user_id,$plan_id);
                        }
						
                        do_action('arm_after_accept_bank_transfer_payment', $user_id, $plan_id, $log_id);
						$response = array('status' => 'success', 'message' => esc_html__('Bank transfer request has been approved.', 'ARMember'));
					} else {
						delete_user_meta($user_id, 'arm_change_plan_to');
						$wpdb->update($ARMember->tbl_arm_payment_log, array('arm_transaction_status' => 2), array('arm_log_id' => $log_id));
                                                do_action('arm_after_decline_bank_transfer_payment',$user_id,$plan_id);
						$response = array('status' => 'success', 'message' => esc_html__('Bank transfer request has been cancelled.', 'ARMember'));
					}
				}
			}

			do_action('arm_payment_log_entry', 'bank_transfer', 'Change bank transfer response', 'armember', $response, $arm_debug_payment_log_id);

			if(empty($logid_exit_flag))
			{
				echo arm_pattern_json_encode($response);
				exit;
			}
		}
        function arm_delete_transaction_data(){
			global $wpdb, $ARMember, $arm_members_class, $arm_global_settings, $arm_capabilities_global;
			$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_subscriptions'], '1',1); //phpcs:ignore --Reason:Verifying nonce
			if (!isset($_POST))//phpcs:ignore
			{
				return;
			}
			
			$action = sanitize_text_field($_POST['act']);//phpcs:ignore
			$id = intval($_POST['id']);//phpcs:ignore
			if ($action == 'delete')
			{
				if (empty($id))
				{
					$errors[] = esc_html__('Invalid action.', 'ARMember');
				}
				else
				{
					if (!current_user_can('arm_manage_subscriptions'))
					{
						$errors[] = esc_html__('Sorry, You do not have permission to perform this action.', 'ARMember');
					}
					else {
                        $res_var = $wpdb->delete($ARMember->tbl_arm_payment_log, array('arm_log_id' => $id));

						if ($res_var)
						{
							$message = esc_html__('Record deleted successfully.', 'ARMember');
						}
						else
						{
							$errors[] = esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember');
						}
					}
				}
			}
			$return_array = $arm_global_settings->handle_return_messages(@$errors, @$message);
			echo arm_pattern_json_encode($return_array);
			exit;
		}
        function arm_fetch_activity_data() {
            global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $is_multiple_membership_feature, $arm_capabilities_global, $arm_pay_per_post_feature,$arm_transaction,$arm_invoice_tax_feature,$arm_gift;
            
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_subscriptions'], '1',1);
            $date_format = $arm_global_settings->arm_get_wp_date_format();
            $user_roles = get_editable_roles();
            $nowDate = current_time('mysql');
            $arm_invoice_tax_feature = get_option('arm_is_invoice_tax_feature', 0);

            $global_currency = $arm_payment_gateways->arm_get_global_currency();
			$general_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : array();
			$arm_currency_decimal = isset($general_settings['arm_currency_decimal_digit']) ? $general_settings['arm_currency_decimal_digit'] : 2;

            $response_data = array();
            $posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_REQUEST );//phpcs:ignore
            $filter_ptype = isset($posted_data['payment_type']) ? $posted_data['payment_type'] : '';
            $filter_search = isset($posted_data['sSearch']) ? $posted_data['sSearch'] : '';
            $filter_status = isset($posted_data['plan_status']) ? $posted_data['plan_status'] : '';

            $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans();
            if(!empty($posted_data['data']))
            {
                $posted_data = json_decode(stripslashes_deep($posted_data['data']),true);
            }
            $sql = '';
            $filter = '';
            $total_results = 0;
            $response_result = array();
            

            
            $grid_columns = array(
                'username' => esc_html__('Username', 'ARMember'),
                'name' => esc_html__('Name', 'ARMember'),
                'date' => esc_html__('Start Date', 'ARMember'),
                'arm_payment_cycle' => esc_html__('Expire/Next Renewal', 'ARMember'),
                'amount' => esc_html__('Amount', 'ARMember'),
                'arm_payment_type' => esc_html__('Payment Type', 'ARMember'),
                'transaction' => esc_html__('Transaction', 'ARMember'),
                'status' => esc_html__('Status', 'ARMember'),
            );

            $displayed_grid_columns = $grid_columns;
            $filter_plans = (!empty($posted_data['arm_subs_plan_filter']) && $posted_data['arm_subs_plan_filter'] != '') ? $posted_data['arm_subs_plan_filter'] : '';
            $filter_status_id = (!empty($posted_data['filter_status_id']) && $posted_data['filter_status_id'] != 0) ? $posted_data['filter_status_id'] : '';
            $filter_gateway = (!empty($posted_data['payment_gateway']) && $posted_data['payment_gateway'] != '0') ? $posted_data['payment_gateway'] : '';
            $filter_plan_type = (!empty($posted_data['filter_plan_type']) && $posted_data['filter_plan_type'] != '') ? $posted_data['filter_plan_type'] : '';
            $filter_tab = (!empty($posted_data['selected_tab']) && $posted_data['selected_tab'] != '') ? $posted_data['selected_tab'] : 'activity';
            
            $grid_columns['action_btn'] = '';            
            $sorting_ord = isset($_REQUEST['sSortDir_0']) ? sanitize_text_field($_REQUEST['sSortDir_0']) : 'desc';
            $sorting_ord = strtolower($sorting_ord);
            $sorting_col = (isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] > 0) ? intval($_REQUEST['iSortCol_0']) : 0;
            if ( empty($sorting_col) && ( 'asc'!=$sorting_ord && 'desc'!=$sorting_ord ) ) {
                $sorting_ord = 'desc';
            }
            $offset = isset($posted_data['iDisplayStart']) ? $posted_data['iDisplayStart'] : 0;
            $limit = isset($posted_data['iDisplayLength']) ? $posted_data['iDisplayLength'] : 10;
            $phlimit = " LIMIT {$offset},{$limit}";
            
            $response_data = array();
            $grid_columns = array(
                'arm_invoice_id' => esc_html__('Order/Invoice ID', 'ARMember'),
                'arm_plan_id' => esc_html__('Membership', 'ARMember'),
                'arm_payment_date' => esc_html__('Payment Date', 'ARMember'),
                'arm_amount' => esc_html__('Amount', 'ARMember'),
                'arm_payment_type' => esc_html__('Payment type', 'ARMember'),
                'arm_transaction_status' => esc_html__('Payment type', 'ARMember'),
            );
            $data_columns = array();
            $n = 0;
            foreach ($grid_columns as $key => $value) {
                $data_columns[$n]['data'] = $key;
                $n++;
            }
            unset($n);

            $sOrder = "";
            $orderby = !empty($data_columns[(intval($sorting_col))]['data']) ? $data_columns[(intval($sorting_col))]['data'] : 'pl.arm_log_id';
            if($orderby == 'arm_invoice_id')
            {
                $orderby = 'pl.'.$orderby;
            }

            $order_by_qry = "ORDER BY " . $orderby . " " . $sorting_ord ;
            
            $sql = $wpdb->prepare( "SELECT pl.arm_log_id,pl.arm_invoice_id,am.arm_user_id,am.arm_user_login,pl.arm_plan_id,pl.arm_payment_gateway,pl.arm_payment_type,pl.arm_transaction_status,pl.arm_payment_date,pl.arm_is_post_payment,pl.arm_paid_post_id,pl.arm_is_gift_payment,pl.arm_payment_mode,pl.arm_amount,pl.arm_currency FROM ".$ARMember->tbl_arm_payment_log." pl LEFT JOIN ".$ARMember->tbl_arm_members." am ON pl.arm_user_id = am.arm_user_id WHERE 1=1 AND pl.arm_user_id !=%d ",0); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log and $ARMember->tbl_arm_members are a table names
            $filter ='';
            if (!empty($filter_gateway) && $filter_gateway != '0') {
                $filter .= $wpdb->prepare(" AND pl.arm_payment_gateway=%s",$filter_gateway);
            }
            if (!empty($filter_ptype) && $filter_ptype != '') {
                $filter .= $wpdb->prepare(" AND pl.arm_payment_type=%s",$filter_ptype);
            }
            if (!empty($filter_plans) && $filter_plans != '0') {
				$filter_act_plans = explode(',',$filter_plans);
                $page_placeholders = 'AND pl.arm_plan_id IN (';
                $page_placeholders .= rtrim( str_repeat( '%s,', count( $filter_act_plans ) ), ',' );
                $page_placeholders .= ')';
                array_unshift( $filter_act_plans, $page_placeholders );
                $filter .= call_user_func_array(array( $wpdb, 'prepare' ), $filter_act_plans );
                // $filter .= " AND pl.arm_plan_id IN ($filter_plans)";
            }
            if (!empty($filter_search) && $filter_search != '') {
                $filter .= $wpdb->prepare(" AND (pl.arm_invoice_id LIKE %s OR pl.arm_plan_id LIKE %s OR pl.arm_payment_gateway LIKE %s OR pl.arm_payment_type LIKE %s OR pl.arm_transaction_status LIKE %s OR am.arm_user_login LIKE %s)",'%'.$filter_search.'%','%'.$filter_search.'%','%'.$filter_search.'%','%'.$filter_search.'%','%'.$filter_search.'%','%'.$filter_search.'%');
            }
            if (!empty($filter_status) && $filter_status != '') {
                $filter_pstatus = strtolower($filter_status);
                $status_query = $wpdb->prepare(" AND (pl.arm_transaction_status=%s",$filter_pstatus);
                if( !in_array($filter_pstatus,array('success','pending','canceled')) ){
                    $status_query .= ")";
                }
                switch ($filter_pstatus) {
                    case 'success':
                        $status_query .= $wpdb->prepare(" OR pl.arm_transaction_status=%s)",1);                        break;
                    case 'pending':
                        $status_query .= $wpdb->prepare(" OR pl.arm_transaction_status=%s)",0);
                        break;
                    case 'canceled':
                        $status_query .= $wpdb->prepare(" OR pl.arm_transaction_status=%s)",2);
                        break;
                }
                $filter .= $status_query;
            }
            $get_result_sql = $sql .' '. $filter . ' ' . $order_by_qry . ' '. $phlimit; //phpcs:ignore
            $response_result = $wpdb->get_results($get_result_sql,ARRAY_A); //phpcs:ignore --Reason $get_result_sql is a predefined query
            
            $before_filter_sql = $wpdb->get_results($sql);//phpcs:ignore --Reason $sql is a predefined query

            $before_filter = count($before_filter_sql);

            $total_results = $wpdb->get_results($sql .' '. $filter . ' ' . $order_by_qry);//phpcs:ignore --Reason $sql is a predefined query

            $after_filter = count($total_results);
            if(!empty($response_result))
            {
                $ai = 0;
                foreach($response_result as $rc)
                {
                    
                    $plan_detail = '';
                    $rc = (Object) $rc;
                    $arm_is_post_payment = isset($rc->arm_is_post_payment) ? $rc->arm_is_post_payment : 0;
                    $arm_is_gift_payment = isset($rc->arm_is_gift_payment) ? $rc->arm_is_gift_payment : 0;
                    $user_first_name = get_user_meta( $rc->arm_user_id,'first_name',true);
                    $user_last_name = get_user_meta( $rc->arm_user_id,'last_name',true);
                    $log_type = ($rc->arm_payment_gateway == 'bank_transfer') ? 'bt_log' : 'other';
                    $arm_invoice_id = $arm_global_settings->arm_manipulate_invoice_id($rc->arm_invoice_id);
                    if($arm_invoice_tax_feature == 1)
                    {
                        $response_data[$ai][0] = "<a class='armhelptip arm_invoice_detail tipso_style' href='javacript:void(0)' data-log_id='" . esc_attr($rc->arm_log_id) . "' data-log_type='" . esc_attr($log_type) . "' title='" . esc_attr__('View Invoice', 'ARMember') . "'>".$arm_invoice_id."</a>";
                    }
                    else
                    {
                        $response_data[$ai][0] = $arm_invoice_id;
                    }
                    if( $arm_pay_per_post_feature->isPayPerPostFeature && $arm_is_post_payment){
                        $membership_data = "<span class='arm_item_status_plan paid_post'>".esc_html__('Paid Post','ARMember')."</span>";
                    }
                    if( $arm_is_gift_payment){
                        $membership_data = "<span class='arm_item_status_plan gift'>".esc_html__('Gift','ARMember')."</span>";
                    }
                    if($arm_is_gift_payment)
                    {
                        $gift_id = $rc->arm_plan_id;
                        $gift_sql = $wpdb->prepare("SELECT arm_subscription_plan_name FROM {$ARMember->tbl_arm_subscription_plans} WHERE arm_subscription_plan_id = %d",$gift_id); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name
                        $get_gift_data = $wpdb->get_row($gift_sql); //phpcs:ignore --Reason $gift_sql is a query
                        $response_data[$ai][1] = (!empty($get_gift_data->arm_subscription_plan_name)) ? $get_gift_data->arm_subscription_plan_name : '-';
                        if(!empty($get_gift_data->arm_subscription_plan_name))
                        {
                            $response_data[$ai][1].='<br>'.$membership_data;
                        }
                    }
                    else if($arm_is_post_payment)
                    {
                        $post_id = $rc->arm_paid_post_id;
                        $post_sql = $wpdb->prepare("SELECT arm_subscription_plan_name FROM {$ARMember->tbl_arm_subscription_plans} WHERE arm_subscription_plan_post_id  = %d",$post_id); //phpcs:ignore --Reason $ARMember->tbl_arm_subscription_plans is a table name
                        $get_post_data = $wpdb->get_row($post_sql); //phpcs:ignore --Reason $post_sql is a query
                        $response_data[$ai][1] = (!empty($get_post_data->arm_subscription_plan_name)) ? $get_post_data->arm_subscription_plan_name : '-';
                        if(!empty($membership_data))
                        {
                            $response_data[$ai][1].='&nbsp;'.$membership_data;
                        }
                    }
                    else
                    {
                        $plan_ID = $rc->arm_plan_id;
                        $plan_detail = '';
                        if(!empty($all_plans)){
                            foreach($all_plans as $planData)
                            {
                                $planObj = new ARM_Plan();
                                $planObj->init((object) $planData);
                                $planID = $planData['arm_subscription_plan_id'];
                                if($plan_ID == $planID)
                                {
                                    $plan_detail = $planObj->name;
                                    break;
                                }
                            }
                        }
                        $response_data[$ai][1] = (!empty($plan_detail)) ? $plan_detail : '-';
                        if($arm_pay_per_post_feature->isPayPerPostFeature || is_plugin_active('armembergift/armembergift.php'))
                        {
                            $response_data[$ai][1] .= "&nbsp;<span class='arm_item_status_plan membership'>".esc_html__('Plan','ARMember')."</span>";
                        }
                    }
                    /* if(!empty($plan_data))
                    {
                        $plan_name = esc_html(stripslashes($plan_data['arm_subscription_plan_name']));
                    } */
                    
                    
                    $response_data[$ai][2] = '<a class="arm_openpreview_popup" data-arm_hide_edit="1" href="javascript:void(0)" data-id="'.esc_attr( $rc->arm_user_id ).'">'.esc_html($rc->arm_user_login).'</a>';

                    $response_data[$ai][3] = trim($user_first_name.' '.$user_last_name);
                    
                    $log_type = ($rc->arm_payment_gateway == 'bank_transfer') ? 'bt_log' : 'other';
                    $response_data[$ai][4] = date_i18n($date_format, strtotime($rc->arm_payment_date));
                    $currency_sym = (!empty($rc->arm_currency)) ? strtoupper($rc->arm_currency) : strtoupper($global_currency);
                    $response_data[$ai][5] = number_format(floatval($rc->arm_amount),$arm_currency_decimal,'.',',') .' '. $currency_sym;
                    $payment_mode = (!empty($rc->arm_payment_mode)) ? $rc->arm_payment_mode : esc_html__('Semi Automatic','ARMember');
                    if($payment_mode == 'auto_debit_subscription')
                    {
                        $payment_mode = '<span>'.esc_html__('Auto Debit','ARMember').'</span>';
                    }
                    else
                    {
                        $payment_mode = '<span>'.esc_html__('Semi Automatic','ARMember') .'</span>';
                    }
                    $payment_gateway = $arm_payment_gateways->arm_gateway_name_by_key($rc->arm_payment_gateway);
                    $payment_type = !empty($rc->arm_payment_mode) ? $rc->arm_payment_mode : 'manual';
                    if(!empty($payment_gateway) && $payment_gateway != 'manual')
                    {
                        $payment_types = ($payment_type != 'auto_debit_subscription') ? esc_html__('Semi Automatic','ARMember') : esc_html__('Auto Debit','ARMember')  ;
                        $class = ($payment_type != 'auto_debit_subscription') ? 'arm_semi_auto' : 'arm_auto';
                        $response_data[$ai][6] = $payment_gateway." <br/><span class='arm_payment_types ".esc_attr($class)."'>".esc_html($payment_types)."</span>";
                    }
                    else
                    {
                        
                        $response_data[$ai][6] = !empty($payment_gateway) ? $payment_gateway : esc_html__('Manual','ARMember');    
                    }
                    $arm_transaction_status = $rc->arm_transaction_status;
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
                            $arm_transaction_status = $rc->arm_transaction_status;
                            break;
                    }
                    $response_data[$ai][7] =  $arm_transaction->arm_get_transaction_status_text($arm_transaction_status);
                    $transactionID = $rc->arm_log_id;   
                    $gridAction = "<div class='arm_grid_action_btn_container'>";
                    if ($rc->arm_payment_gateway == 'bank_transfer' && $arm_transaction_status == 'pending') {
                    	$changeStatusFun = 'ChangeStatus(' . $transactionID .',1);';
                    	$chagneStatusFun2 = 'ChangeStatus(' . $transactionID . ',2);';
                    	$armbPopupArg = 'change_transaction_status_message';
                    	if( $arm_is_post_payment ){                           
                    		$changeStatusFun = 'ArmPPChangeStatus(' . $transactionID .',1);';
                    		$chagneStatusFun2 = 'ArmPPChangeStatus(' . $transactionID . ',2);';
                    	}
                    	else if( $arm_is_gift_payment ){
                    		$changeStatusFun = 'ArmGPChangeStatus(' . $transactionID .',1);';
                    		$chagneStatusFun2 = 'ArmGPChangeStatus(' . $transactionID . ',2);';
                    		$armbPopupArg = 'change_gp_transaction_status_message';
                    	}

                        $gridAction .= "<a class='armhelptip arm_change_btlog_status' href='javascript:void(0)' onclick=\"{$changeStatusFun}armBpopup('{$armbPopupArg}');\" data-status='1' data-log_id='" . esc_attr($transactionID) . "' title='" . esc_attr__('Approve', 'ARMember') . "'><img src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_approved.png' onmouseover=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_approved_hover.png';\" onmouseout=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_approved.png';\" /></a>";
                        $gridAction .= "<a class='armhelptip arm_change_btlog_status' href='javascript:void(0)' onclick=\"{$chagneStatusFun2}armBpopup('{$armbPopupArg}');\" data-status='2' data-log_id='" . esc_attr($transactionID) . "' title='" . esc_attr__('Reject', 'ARMember') . "'><img src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_denied.svg' onmouseover=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_denied_hover.svg';\" onmouseout=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_denied.svg';\" /></a>";
                    } 
                    if( $arm_transaction_status == 'success' && $arm_invoice_tax_feature == 1 ) {
                    	$gridAction .= "<a class='armhelptip arm_invoice_detail' href='javascript:void(0)' data-log_type='" . esc_attr($log_type) . "' data-log_id='" . esc_attr($transactionID) . "' title='" . esc_attr__('View Invoice', 'ARMember') . "'><img src='" . MEMBERSHIP_IMAGES_URL . "/invoice_icon.png' onmouseover=\"this.src='" . MEMBERSHIP_IMAGES_URL . "/invoice_icon_hover.png';\" onmouseout=\"this.src='" . MEMBERSHIP_IMAGES_URL . "/invoice_icon.png';\" /></a>";//phpcs:ignore
                    }
                    $gridAction .= "<a class='armhelptip arm_preview_log_detail' href='javascript:void(0)' data-log_type='" . esc_attr($log_type) . "' data-log_id='" . esc_attr($transactionID) . "' data-trxn_status='".esc_attr($arm_transaction_status)."' title='" . esc_attr__('View Detail', 'ARMember') . "'><img src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_preview.svg' onmouseover=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_preview_hover.svg';\" onmouseout=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_preview.svg';\" /></a>";
                    $gridAction .= "<a href='javascript:void(0)' data-log_type='" . esc_attr($log_type) . "' data-delete_log_id='" . esc_attr($transactionID) . "' data-trxn_status='".esc_attr($arm_transaction_status)."' onclick='showConfirmBoxCallback(".esc_attr($transactionID).");'><img src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_delete.svg' class='armhelptip' title='" . esc_attr__('Delete', 'ARMember') . "' onmouseover=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_delete_hover.svg';\" onmouseout=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_delete.svg';\" /></a>";//phpcs:ignore
                    $arm_transaction_del_cls = 'arm_transaction_delete_btn';
                    if( $arm_is_post_payment ){
                    	$arm_transaction_del_cls .= ' arm_pp_transaction_delete_btn';
                    }
                    else if( $arm_is_gift_payment ){
                    	$arm_transaction_del_cls .= ' arm_gp_transaction_delete_btn';
                    }
                    $gridAction .= $arm_global_settings->arm_get_confirm_box($transactionID, esc_html__("Are you sure you want to delete this transaction?", 'ARMember'), $arm_transaction_del_cls, $log_type,esc_html__("Delete", 'ARMember'),esc_html__("Cancel", 'ARMember'),esc_html__("Delete", 'ARMember'));
                    $gridAction .= "</div>";
                    $response_data[$ai][8] = $gridAction;
                    $ai++;
                }
            }
            $sEcho = isset($_REQUEST['sEcho']) ? intval($_REQUEST['sEcho']) : intval(10);
            $response = array(
                'sColumns' => implode(',', $grid_columns),
                'sEcho' => $sEcho,
                'iTotalRecords' => $before_filter, // Before Filtered Records
                'iTotalDisplayRecords' => $after_filter, // After Filter Records
                'aaData' => $response_data,
            );
            echo json_encode($response);
            die();
        }
        function arm_fetch_subscription_data() {
            global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $is_multiple_membership_feature, $arm_capabilities_global, $arm_pay_per_post_feature,$arm_transaction;

            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_subscriptions'], '1',1);//phpcs:ignore --Reason:Verifying nonce
            $date_format = $arm_global_settings->arm_get_wp_date_format();
            $user_roles = get_editable_roles();
            $nowDate = current_time('mysql');
            $is_gift_enabled = is_plugin_active('armembergift/armembergift.php');
            $is_paid_post_enabled = $arm_pay_per_post_feature->isPayPerPostFeature;

            $global_currency = $arm_payment_gateways->arm_get_global_currency();
			$general_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : array();
			$arm_currency_decimal = isset($general_settings['arm_currency_decimal_digit']) ? $general_settings['arm_currency_decimal_digit'] : 2;

            $response_data = array();
            $posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_REQUEST );//phpcs:ignore
            $filter_ptype = isset($posted_data['payment_type']) ? sanitize_text_field( $posted_data['payment_type'] ) : '';
            $filter_search = isset($posted_data['sSearch']) ? sanitize_text_field( $posted_data['sSearch'] ) : '';

            $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans();
            if(!empty($posted_data['data']))
            {
                $posted_data = json_decode(stripslashes_deep($posted_data['data']),true);
            }
            $sql = '';
            $filter = '';
            $total_results = 0;
            $response_result = array();
            
            $filter_plans = (!empty($posted_data['arm_subs_filter']) && $posted_data['arm_subs_filter'] != '') ? $posted_data['arm_subs_filter'] : '';
            $filter_status_id = (!empty($posted_data['plan_status']) && $posted_data['plan_status'] != 0) ? intval( $posted_data['plan_status'] ) : '';
            $filter_gateway = (!empty($posted_data['payment_gateway']) && $posted_data['payment_gateway'] != '0') ? sanitize_text_field( $posted_data['payment_gateway'] ) : '';
            $filter_plan_type = (!empty($posted_data['filter_plan_type']) && $posted_data['filter_plan_type'] != '') ? sanitize_text_field( $posted_data['filter_plan_type'] ) : '';
            $filter_tab = (!empty($posted_data['selected_tab']) && $posted_data['selected_tab'] != '') ? esc_attr( $posted_data['selected_tab'] ) : 'activity';
            
            $sorting_ord = !empty($posted_data['sSortDir_0']) ? strtoupper($posted_data['sSortDir_0']) : 'DESC';
            $sorting_col = (isset($posted_data['iSortCol_0']) && $posted_data['iSortCol_0'] > 0) ? intval( $posted_data['iSortCol_0'] ) : 1;

            $offset = isset($posted_data['iDisplayStart']) ? intval( $posted_data['iDisplayStart'] ) : 0;
            $limit = isset($posted_data['iDisplayLength']) ? intval( $posted_data['iDisplayLength'] ) : 10;
            $phlimit = " LIMIT {$offset},{$limit}";
            
            $response_data = array();
            $grid_columns = array(
                'arm_activity_id' => esc_html__('ID', 'ARMember'),
                'arm_item_id' => esc_html__('Membership', 'ARMember'),
                'arm_user_login' => esc_html__('Username', 'ARMember'),
                'name' => esc_html__('Name', 'ARMember'),
                'arm_date_recorded' => esc_html__('Start Date', 'ARMember'),
                'arm_next_cycle_date' => esc_html__('Expire/Next Renewal', 'ARMember'),
                'arm_amount' => esc_html__('Amount Type', 'ARMember'),
                'arm_payment_type' => esc_html__('Payment Type', 'ARMember'),
                'arm_transactions' => esc_html__('Transaction', 'ARMember'),
                'arm_plan_status' => esc_html__('Status', 'ARMember'),
            );
            $grid_columns['action_btn'] = '';    
            $data_columns = array();
            $n = 1;
            foreach ($grid_columns as $key => $value) {
                $data_columns[$n]['data'] = $key;
                $n++;
            }
            unset($n);
            $sql = $wpdb->prepare('SELECT act.*,am.arm_user_login FROM '.$ARMember->tbl_arm_activity.' act LEFT JOIN '.$ARMember->tbl_arm_members.' am ON act.arm_user_id = am.arm_user_id WHERE act.arm_user_id !=%d AND act.arm_action = %s',0,"new_subscription"); //phpcs:ignore --Reason $ARMember->tbl_arm_members is a table name
            
            $orderby = $data_columns[(intval($sorting_col))]['data'];

            $order_by_qry = "ORDER BY " . $orderby . " " . $sorting_ord ;
            if(!empty($filter_gateway))
            {
                $filter_gateway = '"'.$filter_gateway.'"';
                $filter .= $wpdb->prepare("AND act.arm_content LIKE %s ",'%'.$filter_gateway.'%');
            }
            if(!empty($filter_ptype))
            {
                $filter_data = '%s:17:"plan_payment_type";s:8:"'.$filter_ptype.'"%';
                if($filter_ptype == 'subscription')
                {
                    $filter_data = '%s:17:"plan_payment_type";s:12:"'.$filter_ptype.'"%';
                }
                $filter .= $wpdb->prepare("AND act.arm_content LIKE %s",$filter_data);
            }
            if(!empty($filter_search))
            {
                $filter .= $wpdb->prepare('AND (am.arm_user_login LIKE %s) ','%'.$filter_search.'%');
            }
            if (!empty($filter_plans) && $filter_plans != '0') {
                $filter_sub_plans = explode(',', $filter_plans);
                $admin_placeholders = ' AND act.arm_item_id IN (';
				$admin_placeholders .= rtrim( str_repeat( '%d,', count( $filter_sub_plans ) ), ',' );
				$admin_placeholders .= ')';
				array_unshift( $filter_sub_plans, $admin_placeholders );
				
				$filter .= call_user_func_array(array( $wpdb, 'prepare' ), $filter_sub_plans );               
            }
            if(!empty($filter_status_id))
            {
                $user_ids = array();
                $plan_ids = array();
                $filter_sql = $sql;
                $filter_response_result = $wpdb->get_results($filter_sql); //phpcs:ignore --Reason $filter_sql is a query
                if(!empty($filter_response_result))
                {
                    foreach($filter_response_result as $rc)
                    {
                        $rc = (object) $rc;
                        $user_plan_detail = get_user_meta($rc->arm_user_id, 'arm_user_plan_'.$rc->arm_item_id, true);
                        $get_activity_data = maybe_unserialize($rc->arm_content);
                        $start_plan_date = !empty($rc->arm_activity_plan_start_date) ? $rc->arm_activity_plan_start_date : '';
                        $plan_status = $this->get_return_status_data($rc->arm_user_id,$rc->arm_item_id,$user_plan_detail,strtotime($start_plan_date));
                        $suspended_plan_detail = get_user_meta($rc->arm_user_id, 'arm_user_suspended_plan_ids', true);
                        
                        if(!empty($plan_status['status']) && $plan_status['status'] == 'suspended' && $filter_status_id == '3')
                        {
                            array_push($user_ids,$rc->arm_activity_id);
                        }
                        else if( !empty($plan_status['status']) && $plan_status['status'] == 'canceled' && $filter_status_id == '4')
                        {
                            array_push($user_ids,$rc->arm_activity_id);
                        }
                        else if( !empty($plan_status['status']) && $plan_status['status'] == 'expired' && $filter_status_id == '2')
                        {
                            array_push($user_ids,$rc->arm_activity_id);
                        }
                        else if(!empty($plan_status['status']) && $plan_status['status'] == 'active' &&  $filter_status_id == '1' && (empty($suspended_plan_detail) || !in_array($rc->arm_item_id,$suspended_plan_detail)))
                        {
                            array_push($user_ids,$rc->arm_activity_id);
                        }
                    }
                }
                if(!empty($user_ids))
                {
                    $admin_placeholders = ' AND act.arm_activity_id IN (';
                    $admin_placeholders .= rtrim( str_repeat( '%s,', count( $user_ids ) ), ',' );
                    $admin_placeholders .= ')';
                    array_unshift( $user_ids, $admin_placeholders );
                    
                    $filter .= call_user_func_array(array( $wpdb, 'prepare' ), $user_ids );   
                }
            }
            
            $before_filter_total_results = $wpdb->get_results($sql); //phpcs:ignore --Reason $sql is a Predefined query
            
            $before_filter = count($before_filter_total_results);

            $get_result_sql = $sql .' '. $filter . ' '.$order_by_qry.' '. $phlimit;

            $response_result = $wpdb->get_results($get_result_sql); //phpcs:ignore --Reason $get_result_sql is a predefined query

            $total_results = $wpdb->get_results($sql .' '. $filter . ' '.$order_by_qry);//phpcs:ignore --Reason $sql is a predefined query

           
            $after_filter = count($total_results);
            
            if(!empty($response_result))
            {
                $ai = 0;
                foreach($response_result as $rc)
                {
                    $rc = (object) $rc;
                    $activity_id = $rc->arm_activity_id;
                    $user_id = $rc->arm_user_id;
                    $plan_id = $rc->arm_item_id;
                    $user_first_name = get_user_meta( $user_id,'first_name',true);
                    $user_last_name = get_user_meta( $user_id,'last_name',true);
                    $plan_name = '';
                    $is_paid_post = (!empty($rc->arm_paid_post_id)) ? 1 : 0;
                    $is_gift_plan = (!empty($rc->arm_gift_plan_id)) ? 1 : 0;
                    $response_data[$ai][1] = $rc->arm_activity_id;
                    $get_activity_data = maybe_unserialize($rc->arm_content);
                    $arm_currency = !empty($get_activity_data['arm_currency']) ? $get_activity_data['arm_currency'] : $global_currency;
                    $start_plan_date = !empty($rc->arm_activity_plan_start_date) ? strtotime($rc->arm_activity_plan_start_date) : '';
                    $user_future_plan_ids = get_user_meta($user_id, 'arm_user_future_plan_ids', true);
                    $membership_data = "<span class='arm_item_status_plan paid_post'>".esc_html__('Plan','ARMember')."</span>";
                    if( $arm_pay_per_post_feature->isPayPerPostFeature && $is_paid_post){
                        $membership_data = "<span class='arm_item_status_plan paid_post'>".esc_html__('Paid Post','ARMember')."</span>";
                    }
                    if( $is_gift_plan){
                        $membership_data = "<span class='arm_item_status_plan gift'>".esc_html__('Gift','ARMember')."</span>";
                    }
                    if(!empty($get_activity_data))
                    {
                        $grace_period_data = $plan_detail = $membership_start = '';
                        $plan_text = !empty($get_activity_data['plan_text']) ? htmlentities($get_activity_data['plan_text']) : '';
                        $plan_details = explode('&lt;br/&gt;',$plan_text);
                        
                        $plan_detail = (!empty($plan_details[1])) ? strip_tags(html_entity_decode($plan_details[1])) : '';
                        $user_plan_detail = get_user_meta($user_id, 'arm_user_plan_'.$rc->arm_item_id, true);
                        $membership_start = (!empty($user_plan_detail['arm_start_plan'])) ? $user_plan_detail['arm_start_plan'] : 0;
                        if(!empty($user_plan_detail['arm_is_user_in_grace']) && $user_plan_detail['arm_is_user_in_grace'] == 1)
                        {
                            $grace_period_data = "<span class='arm_item_status_plan grace'>".esc_html__('Grace Expiration','ARMember').": ". esc_html(date_i18n($date_format, $user_plan_detail['arm_grace_period_end']))."</span>";
                        }
                        if(!empty($user_future_plan_ids) && in_array($plan_id,$user_future_plan_ids)){
                            $grace_period_data .= " <span class='arm_item_status_plan plan_future'>".esc_html__('Future Membership','ARMember')."</span>";
                        }
                        if(!empty($user_plan_detail['arm_current_plan_detail']) && !empty($user_plan_detail['arm_current_plan_detail']['arm_subscription_plan_type']) && $user_plan_detail['arm_current_plan_detail']['arm_subscription_plan_type'] == 'recurring')
                        {
                            $arm_subscription_plans_expire = date_i18n($date_format, $user_plan_detail['arm_next_due_payment']);
                        }
                        else
                        {
                            $arm_subscription_plans_expire = !empty($user_plan_detail['arm_expire_plan']) ? date_i18n($date_format, $user_plan_detail['arm_expire_plan']) : '-';
                        }
                        $suspended_plan_detail = get_user_meta($user_id, 'arm_user_suspended_plan_ids', true);
                        $plan_status = $this->get_return_status_data($user_id,$rc->arm_item_id,$user_plan_detail,$start_plan_date);
                        $status = !empty($plan_status['status']) ? $plan_status['status'] : '';
                        $canceled_date = !empty($plan_status['canceled_date']) ? $plan_status['canceled_date'] : '';
                        if(!empty($plan_status['status']) && $plan_status['status'] == 'suspended')
                        {
                            $status = 'suspended';
                            $response_data[$ai][10] = '<span class="arm_item_status_plan cancelled">'.esc_html__('Suspended','ARMember').'</span>';
                        }
                        else if(!empty($plan_status['status']) &&  $plan_status['status'] == 'canceled')
                        {
                            $status = 'canceled';
                            $arm_subscription_plans_expire = '-';
                            $response_data[$ai][10] = '<span class="arm_item_status_plan cancelled">'.esc_html__('Canceled','ARMember').'</span>';
                        }
                        else if( !empty($plan_status['status']) && $plan_status['status'] == 'expired')
                        {
                            $status = 'expired';
                            $arm_subscription_plans_expire = '-';
                            $response_data[$ai][10] = '<span class="arm_item_status_plan expired">'.esc_html__('Expired','ARMember').'</span>';
                        }
                        else if( !empty($plan_status['status']) && $plan_status['status'] == 'active')
                        {
                            $status = 'active';
                            $response_data[$ai][10] ='<span class="arm_item_status_plan active">'.esc_html__('Active','ARMember').'</span>';
                        }
                        else{
                            $arm_subscription_plans_expire = '-';
                            $status ='';
                            $response_data[$ai][10] = '<span class="arm_item_status_plan cancelled">'.esc_html__('Canceled','ARMember').'</span>';
                        }
                        $plan_name = $rc->arm_activity_plan_name;
                        if($is_gift_enabled || $is_paid_post_enabled)
                        {
                            $response_data[$ai][2] = $rc->arm_activity_plan_name." &nbsp; " .$membership_data. "<br/><span class='arm_plan_style' style='margin-right:10px'>".$plan_detail."</span><br/>".$grace_period_data;
                        }
                        else
                        {
                            $response_data[$ai][2] = $rc->arm_activity_plan_name . "<br/><span class='arm_plan_style'>".$plan_detail."</span><br/>".$grace_period_data;
                        }
                        $response_data[$ai][6] = $arm_subscription_plans_expire;
                        
                        $response_data[$ai][7] = number_format(floatval($rc->arm_activity_plan_amount),$arm_currency_decimal,'.',',') . ' '. $arm_currency;

                        $payment_type = !empty($user_plan_detail['arm_payment_mode']) ? $user_plan_detail['arm_payment_mode'] : 'manual';
                        
                    }

                    $response_data[$ai][3] = '<a class="arm_openpreview_popup"  data-arm_hide_edit="1" href="javascript:void(0)" data-id="'.esc_attr($user_id).'">'.$rc->arm_user_login.'</a>';
                    $response_data[$ai][4] = $user_first_name . ' ' .$user_last_name;
                    $start_plan_date = strtotime($rc->arm_activity_plan_start_date);
                    $response_data[$ai][5] = date_i18n($date_format, $start_plan_date);
                    $transaction_started_date = date('Y-m-d H:i:s', ($start_plan_date - 120));
                    $payment_gateway = $rc->arm_activity_payment_gateway;
                    if($payment_gateway == 'manual')
                    {
                        $transaction_started_date = date('Y-m-d 00:00:00', $start_plan_date);
                    }
                    
                    if(!empty($canceled_date))
                    {
                        $get_last_transaction_sql = $wpdb->prepare("SELECT * FROM ".$ARMember->tbl_arm_payment_log." WHERE arm_user_id=%d AND arm_plan_id=%d AND arm_created_date BETWEEN %s AND %s ORDER BY arm_log_id DESC",$user_id,$rc->arm_item_id,$transaction_started_date,$canceled_date); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name
                    }
                    else
                    {
                        if(!empty($user_plan_detail['arm_trial_start']))
                        {
                            $transaction_started_date = date('Y-m-d H:i:s', ($user_plan_detail['arm_trial_start'] - 120));
                        }
                        $get_last_transaction_sql = $wpdb->prepare("SELECT * FROM ".$ARMember->tbl_arm_payment_log." WHERE arm_user_id=%d AND arm_plan_id=%d AND arm_created_date >= %s ORDER BY arm_log_id DESC",$user_id,$rc->arm_item_id,$transaction_started_date); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name
                    }
                    
                    $get_transaction_sql = $wpdb->get_results($get_last_transaction_sql,ARRAY_A); //phpcs:ignore --Reason get_last_transaction_sql is a query
                    $transaction_count = 0;
                    $payment_row = $payment_gateway_text = $arm_payment_gateways->arm_gateway_name_by_key($payment_gateway);
                    $payment_types = '';
                    $class = '';                   
                    if($payment_gateway != 'manual')
                    {
                        $payment_types = ($payment_type != 'auto_debit_subscription') ? esc_html__('Semi Automatic','ARMember') : esc_html__('Auto Debit','ARMember')  ;
                        $class = ($payment_type != 'auto_debit_subscription') ? 'arm_semi_auto' : 'arm_auto';
                        $payment_row = $payment_gateway_text." <br/><span class='arm_payment_types ".esc_attr($class)."'>".$payment_types."</span>";
                    }
                    if(!empty($get_transaction_sql))
                    {
                        $total_trans = count($get_transaction_sql);
                        
                        if($payment_gateway != 'manual')
                        {
                            $response_data[$ai][8] = $payment_row;                    
                        }
                        else
                        {
                            $response_data[$ai][8] = $payment_gateway_text;  
                        }
                        $response_data[$ai][9] = $total_trans;
                        $transaction_count = $total_trans;
                    }
                    else
                    {
                        $response_data[$ai][8] = esc_html__('Manual','ARMember');
                        $response_data[$ai][9] ='0';
                        $transaction_count = 0;
                    }
                    $activityID = $rc->arm_activity_id;   
                    if($transaction_count > 0)
                    {
                        $response_data[$ai][0] = "<div class='arm_show_user_more_transactions' id='arm_show_user_more_transaction_" . esc_attr($activityID) . "' data-id='" . esc_attr($activityID) . "'></div>";
                    }
                    else
                    {
                        $response_data[$ai][0] = "";
                    }
                    $gridAction ='';
                    $gridAction .= "<div class='arm_grid_action_btn_container'>";
                    if($status == 'active')
                    {
                        $allow_refund = apply_filters('arm_display_refund_button_from_outside', false, $payment_gateway);
                        if(!in_array($payment_gateway,array('manual','bank_transfer')) && $allow_refund)
                        {
                            $gridAction .= "<a href='javascript:void(0)' data-refund_activity_id='" . esc_attr($activityID) . "' onclick='showConfirmBoxCallback_refund(".esc_attr($activityID).");'><img src='" . MEMBERSHIP_IMAGES_URL . "/arm-refund-icon.png' class='armhelptip arm_refund_icon_btn' title='" . esc_attr__('Refund', 'ARMember') . "' onmouseover=\"this.src='" . MEMBERSHIP_IMAGES_URL . "/arm-refund-icon-hover.png';\" onmouseout=\"this.src='" . MEMBERSHIP_IMAGES_URL . "/arm-refund-icon.png';\" style='vertical-align:middle' /></a>"; //phpcs:ignore
    
                            $confirmBox = "<div class='arm_confirm_box arm_confirm_box_refund arm_confirm_box_".esc_attr($activityID)."' id='arm_confirm_box_refund_".esc_attr($activityID)."'>";
                                $confirmBox .='<form id="#arm_refund_form_'.esc_attr($activityID).'" class="arm_refund_form" >';
                                $confirmBox .= "<input type='hidden' name='arm_refund_data[activity_id]' id='arm_activity_id' value='".esc_attr($activity_id)."'><input type='hidden' id='arm_default_refund_amount' value='".esc_attr($rc->arm_activity_plan_amount)."' name='arm_refund_data[arm_default_refund_amount]'>";
                                $confirmBox .= "<div class='arm_confirm_box_body' >";
                                $confirmBox .= "<div class='arm_confirm_box_arrow'></div>";
                                $confirmBox .= "<div class='arm_confirm_box_text_title'><b>" . esc_html__("Refund Payment", 'ARMember') . "</b></div>";
                                $confirmBox .= "<div class='arm_confirm_box_btn_container arm_padding_top_0' style='display:none'>";
                                $confirmBox .=" <div class='arm_confirm_box_label'>".esc_html__('Refund Type','ARMember')." </div>";
                                $confirmBox .='<div class="arm_refund_mode_container arm_padding_top_12" id="arm_refund_mode_container">
                                <input id="arm_refund_mode_full_'.esc_attr($activityID).'" class="arm_refund_mode_radio arm_iradio" type="radio" value="full" name="arm_refund_data[arm_refund_mode]" checked="checked">
                                <label class="arm_confirm_box_label arm_padding_left_5" for="arm_refund_mode_full_'.esc_attr($activityID).'" style="font-size: 16px;font-weight: normal;">'. esc_html__('Full', 'ARMember').'</label>
                                <input id="arm_refund_mode_partial_'.esc_attr($activityID).'" class="arm_refund_mode_radio arm_iradio" type="radio" value="partial" name="arm_refund_data[arm_refund_mode]">
                                <label class="arm_confirm_box_label arm_padding_left_5" for="arm_refund_mode_partial_'.esc_attr($activityID).'" style="font-size: 16px;font-weight: normal;">'. esc_html__('partial', 'ARMember').'</label>
                                </div>';
                                $global_currency = $arm_payment_gateways->arm_get_global_currency();
                                $all_currencies = $arm_payment_gateways->arm_get_all_currencies();
                                $currency_sym = (isset($all_currencies) && !empty($all_currencies[strtoupper($global_currency)]))  ? $all_currencies[strtoupper($global_currency)] : strtoupper($global_currency);
                                $confirmBox .='<div class="arm_refund_amount_container arm_margin_top_24 hidden_section" id="arm_refund_amount_container">';
                                $confirmBox .=" <div class='arm_confirm_box_label'>".esc_html__('Refund amount','ARMember')."(".$currency_sym.")";
                                $confirmBox .='<input type="text" class="arm_margin_top_12" id="arm_refund_amount" value="" placeholder="'.esc_attr__('Refund amount','ARMember').'" name="arm_refund_data[arm_refund_amount]" style="width:100%;"></div>';
                                $confirmBox .='</div>';
                                $confirmBox .=" <div class='arm_confirm_box_label arm_margin_top_24'>".esc_html__('Refund Reason','ARMember')." </div>";
                                $confirmBox .='<div class="arm_refund_reason_container arm_padding_top_12 arm_margin_bottom_24" id="arm_refund_reason_container" style="height:80px;">
                                <textarea class="arm_codemirror_field" name="arm_refund_data[arm_refund_reason]" pleaceholder="'.esc_attr__('Refund Reason','ARMember').'" id ="arm_refund_reason_'.esc_attr($activityID).'" rows="3" style="height: 100%;min-height: 70px;resize:none !important;width:100%;"></textarea>
                                </div>';
    
                                $arm_member_refund_btn_class = 'arm_member_plan_refund_btn' ;

                                $confirmBox .= "<button type='button' class='arm_confirm_box_btn armcancel' onclick='hideConfirmBoxCallback();'>" . esc_html__('Cancel', 'ARMember') . "</button>";
    
                                $confirmBox .= "<button type='button' class='arm_confirm_box_btn armok ".esc_attr($arm_member_refund_btn_class)."' data-item_id='".esc_attr($activityID)."'>" . esc_html__('Refund', 'ARMember') . "</button>";
                                
                                $confirmBox .= "</div>";
                                $confirmBox .= "</form>";
                                $confirmBox .= "</div>";
                                $confirmBox .= "</div>";
    
                            $gridAction .= $confirmBox;
                        }

                        $gridAction .= "<a href='javascript:void(0)' data-cancel_activity_type='" . esc_attr($status) . "'  data-cancel_activity_id='" . esc_attr($activityID) . "' onclick='showConfirmBoxCallback(".esc_attr($activityID).");'><img src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_denied.svg' class='armhelptip' title='" . esc_attr__('Cancel', 'ARMember') . "' onmouseover=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_denied_hover.svg';\" onmouseout=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_denied.svg';\" /></a>";//phpcs:ignore
                        $arm_transaction_del_cls = 'arm_activity_delete_btn';
                        $gridAction .= $arm_global_settings->arm_get_confirm_box($activityID, esc_html__("Are you sure you want to cancel this subscription  ?", 'ARMember'), $arm_transaction_del_cls,'',esc_html__("Cancel", 'ARMember'),esc_html__("Close", 'ARMember'),esc_html__("Cancel Subscription", 'ARMember'));

                    }
                    if($status == 'suspended')
                    {
                        $gridAction .= "<a href='javascript:void(0)' data-activation_id='" . esc_attr($activityID) . "' data-plan_id='" . esc_attr($plan_id) . "' onclick='showConfirmBoxCallback_activation(".esc_attr($activityID).");'><img src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_edit.svg' class='armhelptip' title='" . esc_attr__('Activate Plan', 'ARMember') . "' onmouseover=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_edit_hover.svg';\" onmouseout=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_edit.svg';\" style='vertical-align:middle' /></a>"; //phpcs:ignore

                        $arm_plan_is_suspended = "<div class='arm_confirm_box arm_confirm_box_activate_".esc_attr($activityID)."' id='arm_confirm_box_activate_".esc_attr($activityID)."' style='right: -5px;'>";
                        $arm_plan_is_suspended .= "<div class='arm_confirm_box_body'>";
                        $arm_plan_is_suspended .= "<div class='arm_confirm_box_arrow'></div>";
                        $arm_plan_is_suspended .= "<div class='arm_confirm_box_text_title'>". esc_html__("Activate Plan", 'ARMember')."</div>";
                        $arm_plan_is_suspended .= "<div class='arm_confirm_box_text'>" . esc_html__("Are you sure you want to activate", 'ARMember') . " " . esc_html($plan_name) . esc_html__(" plan for this user?", 'ARMember') . "</div>";
                        $arm_plan_is_suspended .= "<div class='arm_confirm_box_btn_container'>";//phpcs:ignore
                        $arm_plan_is_suspended .= "<button type='button' class='arm_confirm_box_btn armcancel' onclick='hideConfirmBoxCallback();'>" . esc_html__('Cancel', 'ARMember') . "</button>";
                        $arm_plan_is_suspended .= "<button type='button' class='arm_confirm_box_btn armok arm_plan_activation_change arm_margin_right_0' data-item_id='".esc_attr($activityID)."'>" . esc_html__('Activate', 'ARMember') . "</button>";
                        $arm_plan_is_suspended .= "</div>";
                        $arm_plan_is_suspended .= "</div>";
                        $arm_plan_is_suspended .= "</div></div>";

                        $gridAction .= $arm_plan_is_suspended;
                    }
                    $gridAction .= "</div>";
                    $response_data[$ai][11] = $gridAction;
                    $ai++;
                }
                // exit;
            }
            $sEcho = isset($_REQUEST['sEcho']) ? intval($_REQUEST['sEcho']) : intval(10);
            $response = array(
                'sColumns' => implode(',', $grid_columns),
                'sEcho' => $sEcho,
                'iTotalRecords' => $before_filter, // Before Filtered Records
                'iTotalDisplayRecords' => $after_filter, // After Filter Records
                'aaData' => $response_data,
            );
            echo json_encode($response);
            die();
        
        }

        function arm_fetch_upcoming_subscription_data() {
            global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $is_multiple_membership_feature, $arm_capabilities_global, $arm_pay_per_post_feature,$arm_transaction;
        
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_subscriptions'], '1',1);//phpcs:ignore --Reason:Verifying nonce
            $date_format = $arm_global_settings->arm_get_wp_date_format();
            $user_roles = get_editable_roles();
            $nowDate = current_time('mysql');
            $is_gift_enabled = is_plugin_active('armembergift/armembergift.php');
            $is_paid_post_enabled = $arm_pay_per_post_feature->isPayPerPostFeature;
        
            $global_currency = $arm_payment_gateways->arm_get_global_currency();
            $general_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : array();
            $arm_currency_decimal = isset($general_settings['arm_currency_decimal_digit']) ? $general_settings['arm_currency_decimal_digit'] : 2;
        
            $response_data = array();
            $posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_REQUEST );//phpcs:ignore
            $filter_ptype = isset($posted_data['payment_type']) ? sanitize_text_field( $posted_data['payment_type'] ) : '';
            $filter_search = isset($posted_data['sSearch']) ? sanitize_text_field( $posted_data['sSearch'] ) : '';
        
            $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans();
            if(!empty($posted_data['data']))
            {
                $posted_data = json_decode(stripslashes_deep($posted_data['data']),true);
            }
            $sql = '';
            $filter = '';
            $total_results = 0;
            $response_result = array();
            
            $filter_plans = (!empty($posted_data['arm_subs_filter']) && $posted_data['arm_subs_filter'] != '') ? $posted_data['arm_subs_filter'] : '';
            $filter_status_id = (!empty($posted_data['plan_status']) && $posted_data['plan_status'] != 0) ? intval( $posted_data['plan_status'] ) : '';
            $filter_gateway = (!empty($posted_data['payment_gateway']) && $posted_data['payment_gateway'] != '0') ? sanitize_text_field( $posted_data['payment_gateway'] ) : '';
            $filter_plan_type = (!empty($posted_data['filter_plan_type']) && $posted_data['filter_plan_type'] != '') ? sanitize_text_field( $posted_data['filter_plan_type'] ) : '';
            $filter_tab = (!empty($posted_data['selected_tab']) && $posted_data['selected_tab'] != '') ? esc_attr( $posted_data['selected_tab'] ) : 'activity';
            
            $sorting_ord = !empty($posted_data['sSortDir_0']) ? strtoupper($posted_data['sSortDir_0']) : 'ASC';
            $sorting_col = (isset($posted_data['iSortCol_0']) && $posted_data['iSortCol_0'] > 0) ? intval( $posted_data['iSortCol_0'] ) : 1;
        
            $offset = isset($posted_data['iDisplayStart']) ? intval( $posted_data['iDisplayStart'] ) : 0;
            $limit = isset($posted_data['iDisplayLength']) ? intval( $posted_data['iDisplayLength'] ) : 10;
            $phlimit = " LIMIT {$offset},{$limit}";
            
            $response_data = array();
            $grid_columns = array(
                'arm_activity_id' => esc_html__('ID', 'ARMember'),
                'arm_item_id' => esc_html__('Membership', 'ARMember'),
                'arm_user_login' => esc_html__('Username', 'ARMember'),
                'name' => esc_html__('Name', 'ARMember'),
                'arm_date_recorded' => esc_html__('Start Date', 'ARMember'),
                'arm_next_cycle_date' => esc_html__('Expire/Next Renewal', 'ARMember'),
                'arm_amount' => esc_html__('Amount Type', 'ARMember'),
                'arm_payment_type' => esc_html__('Payment Type', 'ARMember'),
            );
            $grid_columns['action_btn'] = '';    
            $data_columns = array();
            $n = 0;
            foreach ($grid_columns as $key => $value) {
                $data_columns[$n]['data'] = $key;
                $n++;
            }
            unset($n);
            $sql = $wpdb->prepare('SELECT act.*,am.arm_user_login,
                CASE WHEN act.arm_activity_plan_end_date != "0000-00-00 00:00:00" THEN act.arm_activity_plan_end_date WHEN act.arm_activity_plan_next_cycle_date != "0000-00-00 00:00:00" THEN act.arm_activity_plan_next_cycle_date
                WHEN act.arm_activity_plan_end_date != "0000-00-00 00:00:00" && act.arm_activity_plan_next_cycle_date != "0000-00-00 00:00:00" THEN act.arm_activity_plan_next_cycle_date
                END AS arm_activity_due_date
             FROM '.$ARMember->tbl_arm_activity.' act LEFT JOIN '.$ARMember->tbl_arm_members.' am ON act.arm_user_id = am.arm_user_id WHERE act.arm_user_id !=%d AND act.arm_action != "eot"',0); //phpcs:ignore --Reason $ARMember->tbl_arm_members is a table name
            
            $orderby = 'arm_activity_due_date';
        
            $order_by_qry = "ORDER BY " . $orderby . " " . $sorting_ord ;
            if(!empty($filter_gateway))
            {
                $filter_gateway = '"'.$filter_gateway.'"';
                $filter .= $wpdb->prepare("AND act.arm_content LIKE %s ",'%'.$filter_gateway.'%');
            }
            if(!empty($filter_ptype))
            {
                $filter_data = '%s:17:"plan_payment_type";s:8:"'.$filter_ptype.'"%';
                if($filter_ptype == 'subscription')
                {
                    $filter_data = '%s:17:"plan_payment_type";s:12:"'.$filter_ptype.'"%';
                }
                $filter .= $wpdb->prepare("AND act.arm_content LIKE %s",$filter_data);
            }
            if(!empty($filter_search))
            {
                $filter .= $wpdb->prepare('AND (am.arm_user_login LIKE %s) ','%'.$filter_search.'%');
            }
            if (!empty($filter_plans) && $filter_plans != '0') {
                $filter_sub_plans = explode(',', $filter_plans);
                $admin_placeholders = ' AND act.arm_item_id IN (';
                $admin_placeholders .= rtrim( str_repeat( '%d,', count( $filter_sub_plans ) ), ',' );
                $admin_placeholders .= ')';
                array_unshift( $filter_sub_plans, $admin_placeholders );
                
                $filter .= call_user_func_array(array( $wpdb, 'prepare' ), $filter_sub_plans );               
            }
            
            //fetch all active plan only
            $user_ids = array();
            $plan_ids = array();
            $filter_sql = $sql;
            $filter_response_result = $wpdb->get_results($filter_sql); //phpcs:ignore --Reason $filter_sql is a query
            
            if(!empty($filter_response_result))
            {
                foreach($filter_response_result as $rc)
                {
                    $rc = (object) $rc;
                    $user_plan_detail = get_user_meta($rc->arm_user_id, 'arm_user_plan_'.$rc->arm_item_id, true);
                    $suspended_plan_detail = get_user_meta($rc->arm_user_id, 'arm_user_suspended_plan_ids', true);
                    $suspended_plan_ids = maybe_unserialize( $suspended_plan_detail );
                    if(empty($suspended_plan_ids) || (!empty($suspended_plan_ids) && !in_array($rc->arm_item_id,$suspended_plan_ids)))
                    {
                        $get_activity_data = maybe_unserialize($rc->arm_content);
                        $start_plan_type = !empty($rc->arm_activity_plan_type) ? $rc->arm_activity_plan_type : 'recurring';
                        $arm_subscription_plans_expire = '-';
                        if(!empty($rc->arm_activity_due_date))
                        {
                            $arm_subscription_plans_expire = $rc->arm_activity_due_date;
                        }

                        if(in_array($start_plan_type,array('recurring','paid_finite')) && !empty($arm_subscription_plans_expire) && strtotime($arm_subscription_plans_expire) >= current_time('timestamp'))
                        {
                            array_push($user_ids,$rc->arm_activity_id);
                        }
                    }
                }
            }
            $before_filter = 1;
            $after_filter = 1;
            if(!empty($user_ids))
            {
                $admin_placeholders = ' AND act.arm_activity_id IN (';
                $admin_placeholders .= rtrim( str_repeat( '%s,', count( $user_ids ) ), ',' );
                $admin_placeholders .= ')';
                array_unshift( $user_ids, $admin_placeholders );
                
                $sql .= call_user_func_array(array( $wpdb, 'prepare' ), $user_ids );   

                $fetch_all_activities = $wpdb->get_results($sql); //phpcs:ignore --Reason $sql is a Predefined query

                if(!empty($fetch_all_activities))
                {
                    $arm_eot_activity = array();
                    foreach($fetch_all_activities as $rct)
                    {
                        $rct = (object) $rct;
                        $activity_id = $rct->arm_activity_id;
                        $user_id = $rct->arm_user_id;
                        $plan_id = $rct->arm_item_id;
                        $suspended_plan_detail = get_user_meta( $user_id, 'arm_user_suspended_plan_ids', true );
                        $check_if_plan_is_canceled = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $ARMember->tbl_arm_activity WHERE arm_user_id = %d AND arm_item_id =%d AND arm_activity_id > %d AND (arm_action = %s OR arm_action = %s)",$user_id,$plan_id,$activity_id,'cancel_subscription','eot'),ARRAY_A); //phpcs:ignore
        
                        if(!empty($check_if_plan_is_canceled) && count($check_if_plan_is_canceled) > 0)
                        {
                            array_push($arm_eot_activity,$activity_id);
                        }

                        if(!empty($suspended_plan_detail) && in_array($plan_id,$suspended_plan_detail))
                        {
                            if(!in_array($activity_id,$arm_eot_activity))
                            {
                                array_push($arm_eot_activity,$activity_id);
                            }
                        }
                    }
                    if(!empty($arm_eot_activity)){
                        $sql = $sql.' AND act.arm_activity_id NOT IN ('.implode(',',$arm_eot_activity).')';
                    }
                }

                $before_filter_total_results = $wpdb->get_results($sql); //phpcs:ignore --Reason $sql is a Predefined query
                
                $before_filter = count($before_filter_total_results);
            
                $get_result_sql = $sql .' '. $filter . ' '.$order_by_qry.' '. $phlimit;
            
                $response_result = $wpdb->get_results($get_result_sql); //phpcs:ignore --Reason $get_result_sql is a predefined query
            
                $total_results = $wpdb->get_results($sql .' '. $filter . ' '.$order_by_qry);//phpcs:ignore --Reason $sql is a predefined query

            
                $after_filter = count($total_results);
                
                if(!empty($response_result))
                {
                    $ai = 0;
                    foreach($response_result as $rc)
                    {
                        $rc = (object) $rc;
                        $activity_id =$activityID = $rc->arm_activity_id;
                        $user_id = $rc->arm_user_id;
                        $plan_id = $rc->arm_item_id;
                        $user_first_name = get_user_meta( $user_id,'first_name',true);
                        $user_last_name = get_user_meta( $user_id,'last_name',true);
                        $plan_name = '';
                        $is_paid_post = (!empty($rc->arm_paid_post_id)) ? 1 : 0;
                        $is_gift_plan = (!empty($rc->arm_gift_plan_id)) ? 1 : 0;
                        $response_data[$ai][0] = $rc->arm_activity_id;
                        $get_activity_data = maybe_unserialize($rc->arm_content);
                        $arm_currency = !empty($get_activity_data['arm_currency']) ? $get_activity_data['arm_currency'] : $global_currency;
                        $start_plan_date = !empty($rc->arm_activity_plan_start_date) ? strtotime($rc->arm_activity_plan_start_date) : '';
                        $user_future_plan_ids = get_user_meta($user_id, 'arm_user_future_plan_ids', true);
                        $membership_data = "<span class='arm_item_status_plan paid_post'>".esc_html__('Plan','ARMember')."</span>";
                        if( $arm_pay_per_post_feature->isPayPerPostFeature && $is_paid_post){
                            $membership_data = "<span class='arm_item_status_plan paid_post'>".esc_html__('Paid Post','ARMember')."</span>";
                        }
                        if( $is_gift_plan){
                            $membership_data = "<span class='arm_item_status_plan gift'>".esc_html__('Gift','ARMember')."</span>";
                        }
                        if(!empty($get_activity_data))
                        {
                            $grace_period_data = $plan_detail = $membership_start = '';
                            $plan_text = htmlentities($get_activity_data['plan_text']);
                            $plan_details = explode('&lt;br/&gt;',$plan_text);
                            
                            $plan_detail = (!empty($plan_details[1])) ? strip_tags(html_entity_decode($plan_details[1])) : '';
                            $user_plan_detail = get_user_meta($user_id, 'arm_user_plan_'.$rc->arm_item_id, true);
                            $membership_start = (!empty($user_plan_detail['arm_start_plan'])) ? $user_plan_detail['arm_start_plan'] : 0;
                            if(!empty($user_plan_detail['arm_is_user_in_grace']) && $user_plan_detail['arm_is_user_in_grace'] == 1)
                            {
                                $grace_period_data = "<span class='arm_item_status_plan grace'>".esc_html__('Grace Expiration','ARMember').": ". esc_html(date_i18n($date_format, $user_plan_detail['arm_grace_period_end']))."</span>";
                            }
                            if(!empty($user_future_plan_ids) && in_array($plan_id,$user_future_plan_ids)){
                                $grace_period_data .= " <span class='arm_item_status_plan plan_future'>".esc_html__('Future Membership','ARMember')."</span>";
                            }
                            $arm_subscription_plans_expire = '';
                            if(!empty($rc->arm_activity_due_date) && $rc->arm_activity_due_date != '0000-00-00')
                            {
                                $arm_subscription_plans_expire = $rc->arm_activity_due_date;
                            }
                            
                            $arm_subscription_plans_expire = !empty($arm_subscription_plans_expire) ? date_i18n($date_format, strtotime($arm_subscription_plans_expire)) : '-';
                            $suspended_plan_detail = get_user_meta($user_id, 'arm_user_suspended_plan_ids', true);
                            $plan_name = $rc->arm_activity_plan_name;
                            if($is_gift_enabled || $is_paid_post_enabled)
                            {
                                $response_data[$ai][1] = $plan_name." &nbsp; " .$membership_data. "<br/><span class='arm_plan_style' style='margin-right:10px'>".$plan_detail."</span><br/>".$grace_period_data;
                            }
                            else
                            {
                                $response_data[$ai][1] = $plan_name . "<br/><span class='arm_plan_style'>".$plan_detail."</span><br/>".$grace_period_data;
                            }
                            $response_data[$ai][5] = $arm_subscription_plans_expire;
                            
                            $response_data[$ai][6] = number_format(floatval($rc->arm_activity_plan_amount),$arm_currency_decimal,'.',',') . ' '. $arm_currency;
            
                            $payment_type = !empty($user_plan_detail['arm_payment_mode']) ? $user_plan_detail['arm_payment_mode'] : 'manual';
                            
                        }
            
                        $response_data[$ai][2] = '<a class="arm_openpreview_popup" data-arm_hide_edit="1" href="javascript:void(0)" data-id="'.esc_attr($user_id).'">'.$rc->arm_user_login.'</a>';
                        $response_data[$ai][3] = $user_first_name . ' ' .$user_last_name;
                        // $start_plan_date = !empty($get_activity_data['start']) ? $get_activity_data['start'] : '';
                        $response_data[$ai][4] = !empty($start_plan_date) ? date_i18n($date_format, $start_plan_date) : '-';
                        $transaction_started_date = !empty($start_plan_date) ? date('Y-m-d H:i:s', ($start_plan_date - 120)) : '-';
                        $payment_gateway = !empty($rc->arm_activity_payment_gateway) ? $rc->arm_activity_payment_gateway : 'manual';
                        if($payment_gateway == 'manual')
                        {
                            $transaction_started_date = !empty($start_plan_date) ? date('Y-m-d 00:00:00', $start_plan_date): '-';
                        }
                        
                        if(!empty($canceled_date))
                        {
                            $get_last_transaction_sql = $wpdb->prepare("SELECT * FROM ".$ARMember->tbl_arm_payment_log." WHERE arm_user_id=%d AND arm_plan_id=%d AND arm_created_date BETWEEN %s AND %s ORDER BY arm_log_id DESC",$user_id,$rc->arm_item_id,$transaction_started_date,$canceled_date); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name
                        }
                        else
                        {
                            if(!empty($user_plan_detail['arm_trial_start']))
                            {
                                $transaction_started_date = date('Y-m-d H:i:s', ($user_plan_detail['arm_trial_start'] - 120));
                            }
                            $get_last_transaction_sql = $wpdb->prepare("SELECT * FROM ".$ARMember->tbl_arm_payment_log." WHERE arm_user_id=%d AND arm_plan_id=%d AND arm_created_date >= %s ORDER BY arm_log_id DESC",$user_id,$rc->arm_item_id,$transaction_started_date); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name
                        }
                        
                        $get_transaction_sql = $wpdb->get_results($get_last_transaction_sql,ARRAY_A); //phpcs:ignore --Reason get_last_transaction_sql is a query
                        $transaction_count = 0;
                        $payment_row = $payment_gateway_text = $arm_payment_gateways->arm_gateway_name_by_key($payment_gateway);
                        $payment_types = '';
                        $class = '';                   
                        if($payment_gateway != 'manual')
                        {
                            $payment_types = ($payment_type != 'auto_debit_subscription') ? esc_html__('Semi Automatic','ARMember') : esc_html__('Auto Debit','ARMember')  ;
                            $class = ($payment_type != 'auto_debit_subscription') ? 'arm_semi_auto' : 'arm_auto';
                            $payment_row = $payment_gateway_text." <br/><span class='arm_payment_types ".esc_attr($class)."'>".$payment_types."</span>";
                        }
                        if(!empty($get_transaction_sql))
                        {
                            $total_trans = count($get_transaction_sql);
                            
                            if($payment_gateway != 'manual')
                            {
                                $response_data[$ai][7] = $payment_row;                    
                            }
                            else
                            {
                                $response_data[$ai][7] = $payment_gateway_text;  
                            }
                        
                        }
                        else
                        {
                            $response_data[$ai][7] = esc_html__('Manual','ARMember');
                        }
                        $gridAction ='';
                        $gridAction .= "<div class='arm_grid_action_btn_container'>";
                        
                            $allow_refund = apply_filters('arm_display_refund_button_from_outside', false, $payment_gateway);
                            if(!in_array($payment_gateway,array('manual','bank_transfer')) && $allow_refund)
                            {
                                $gridAction .= "<a href='javascript:void(0)' data-refund_activity_id='" . esc_attr($activityID) . "' onclick='showConfirmBoxCallback_refund(".esc_attr($activityID).");'><img src='" . MEMBERSHIP_IMAGES_URL . "/arm-refund-icon.png' class='armhelptip' title='" . esc_attr__('Refund', 'ARMember') . "' onmouseover=\"this.src='" . MEMBERSHIP_IMAGES_URL . "/arm-refund-icon-hover.png';\" onmouseout=\"this.src='" . MEMBERSHIP_IMAGES_URL . "/arm-refund-icon.png';\" style='vertical-align:middle' /></a>"; //phpcs:ignore
            
                                $confirmBox = "<div class='arm_confirm_box arm_confirm_box_refund arm_confirm_box_".esc_attr($activityID)."' id='arm_confirm_box_refund_".esc_attr($activityID)."'>";
                                $confirmBox .='<form id="#arm_refund_form_'.esc_attr($activityID).'" class="arm_refund_form" >';
                                $confirmBox .= "<input type='hidden' name='arm_refund_data[activity_id]' id='arm_activity_id' value='".esc_attr($activity_id)."'><input type='hidden' id='arm_default_refund_amount' value='".esc_attr($get_activity_data['plan_amount'])."' name='arm_refund_data[arm_default_refund_amount]'>";
                                $confirmBox .= "<div class='arm_confirm_box_body' >";
                                $confirmBox .= "<div class='arm_confirm_box_arrow'></div>";
                                $confirmBox .= "<div class='arm_confirm_box_text_title'><b>" . esc_html__("Refund Payment", 'ARMember') . "</b></div>";
                                $confirmBox .= "<div class='arm_confirm_box_btn_container arm_padding_top_0' style='display:none'>";
                                $confirmBox .=" <div class='arm_confirm_box_label'>".esc_html__('Refund Type','ARMember')." </div>";
                                $confirmBox .='<div class="arm_refund_mode_container arm_padding_top_12" id="arm_refund_mode_container">
                                <input id="arm_refund_mode_full_'.esc_attr($activityID).'" class="arm_refund_mode_radio arm_iradio" type="radio" value="full" name="arm_refund_data[arm_refund_mode]" checked="checked">
                                <label class="arm_confirm_box_label arm_padding_left_5" for="arm_refund_mode_full_'.esc_attr($activityID).'" style="font-size: 16px;font-weight: normal;">'. esc_html__('Full', 'ARMember').'</label>
                                <input id="arm_refund_mode_partial_'.esc_attr($activityID).'" class="arm_refund_mode_radio arm_iradio" type="radio" value="partial" name="arm_refund_data[arm_refund_mode]">
                                <label class="arm_confirm_box_label arm_padding_left_5" for="arm_refund_mode_partial_'.esc_attr($activityID).'" style="font-size: 16px;font-weight: normal;">'. esc_html__('partial', 'ARMember').'</label>
                                </div>';
                                $global_currency = $arm_payment_gateways->arm_get_global_currency();
                                $all_currencies = $arm_payment_gateways->arm_get_all_currencies();
                                $currency_sym = (isset($all_currencies) && !empty($all_currencies[strtoupper($global_currency)]))  ? $all_currencies[strtoupper($global_currency)] : strtoupper($global_currency);
                                $confirmBox .='<div class="arm_refund_amount_container arm_margin_top_24 hidden_section" id="arm_refund_amount_container">';
                                $confirmBox .=" <div class='arm_confirm_box_label'>".esc_html__('Refund amount','ARMember')."(".$currency_sym.")";
                                $confirmBox .='<input type="text" class="arm_margin_top_12" id="arm_refund_amount" value="" placeholder="'.esc_attr__('Refund amount','ARMember').'" name="arm_refund_data[arm_refund_amount]" style="width:100%;"></div>';
                                $confirmBox .='</div>';
                                $confirmBox .=" <div class='arm_confirm_box_label arm_margin_top_24'>".esc_html__('Refund Reason','ARMember')." </div>";
                                $confirmBox .='<div class="arm_refund_reason_container" id="arm_refund_reason_container" style="height:80px;">
                                <textarea class="arm_codemirror_field arm_padding_top_12 arm_margin_bottom_24" name="arm_refund_data[arm_refund_reason]" pleaceholder="'.esc_attr__('Refund Reason','ARMember').'" id ="arm_refund_reason_'.esc_attr($activityID).'" rows="3" style="height: 100%;min-height: 70px;resize:none !important;width:100%;"></textarea>
                                </div>';
        
                                $arm_member_refund_btn_class = 'arm_member_plan_refund_btn' ;
                                
                                $confirmBox .= "<button type='button' class='arm_confirm_box_btn armcancel' onclick='hideConfirmBoxCallback();'>" . esc_html__('Cancel', 'ARMember') . "</button>";

                                $confirmBox .= "<button type='button' class='arm_confirm_box_btn armok ".esc_attr($arm_member_refund_btn_class)."' data-item_id='".esc_attr($activityID)."'>" . esc_html__('Refund', 'ARMember') . "</button>";
                                
                                $confirmBox .= "</div>";
                                $confirmBox .= "</form>";
                                $confirmBox .= "</div>";
                                $confirmBox .= "</div>";
            
                                $gridAction .= $confirmBox;
                            }
            
                            $gridAction .= "<a href='javascript:void(0)' data-cancel_activity_type='active'  data-cancel_activity_id='" . esc_attr($activityID) . "' onclick='showConfirmBoxCallbackupcomming(".esc_attr($activityID).");'><img src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_denied.svg' class='armhelptip' title='" . esc_attr__('Cancel', 'ARMember') . "' onmouseover=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_denied_hover.svg';\" onmouseout=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_denied.svg';\" /></a>";//phpcs:ignore
                            $arm_transaction_del_cls = 'arm_activity_delete_btn';

                            $confirmBox  = "<div class='arm_confirm_box arm_confirm_box_".esc_attr($activityID)."' id='arm_upcoming_activity_".esc_attr($activityID)."'>";
                                $confirmBox .= "<div class='arm_confirm_box_body'>";
                                    $confirmBox .= "<div class='arm_confirm_box_arrow'></div>";
                                        $confirmBox .= "<div class='arm_confirm_box_text'>".esc_html__("Are you sure you want to cancel this subscription?", 'ARMember')."</div>";
                                            $confirmBox .= "<div class='arm_confirm_box_btn_container'>";
                                                $confirmBox .= "<button type='button' class='arm_confirm_box_btn armok ".esc_attr($arm_transaction_del_cls).
                                                "' data-item_id='".esc_attr($activityID)."' data-type=''>" . esc_html('Delete','ARMember') . '</button>';
                                                $confirmBox .= "<button type='button' class='arm_confirm_box_btn armcancel' onclick='hideConfirmBoxCallback();'>" . esc_html__('Cancel','ARMember') . '</button>';
                                        $confirmBox .= '</div>';
                                    $confirmBox .= '</div>';
                                $confirmBox .= '</div>';
                            $confirmBox .= '</div>';

                            $gridAction .= $confirmBox;

                        $gridAction .= "</div>";
                        $response_data[$ai][8] = $gridAction;
                        $ai++;
                    }
                    // exit;
                }
            }
            
            //Get upcoming subscriptions
            
            $sEcho = isset($_REQUEST['sEcho']) ? intval($_REQUEST['sEcho']) : intval(10);
            $response = array(
                'sColumns' => implode(',', $grid_columns),
                'sEcho' => $sEcho,
                'iTotalRecords' => $before_filter, // Before Filtered Records
                'iTotalDisplayRecords' => $after_filter, // After Filter Records
                'aaData' => $response_data,
            );
            echo json_encode($response);
            die();
        
        }
        function get_return_status_data($user_id,$plan_id,$user_plan_detail,$start_plan_date)
        {
            global $wp,$wpdb,$ARMember;
            $end_date = '';
            
            $suspended_plan_detail = get_user_meta($user_id, 'arm_user_suspended_plan_ids', true);
            $active_plan_detail = get_user_meta($user_id, 'arm_user_plan_ids', true);
            $future_plan_detail = get_user_meta($user_id, 'arm_user_future_plan_ids', true);
            if(!empty($user_plan_detail['arm_next_due_payment']))
            {
                $end_date = $user_plan_detail['arm_next_due_payment'];
            }
            else
            {
                $end_date = !empty($user_plan_detail['arm_expire_plan']) ? $user_plan_detail['arm_expire_plan'] : '';
            }
            $sql_act = $wpdb->prepare('SELECT * FROM '.$ARMember->tbl_arm_activity.' WHERE arm_user_id=%d AND arm_item_id = %d AND (arm_action=%s OR arm_action=%s)',$user_id,$plan_id,"cancel_subscription","eot"); //phpcs:ignore --Reason $ARMember->tbl_arm_activity is a table name
            $get_activity_status = $wpdb->get_results($sql_act); //phpcs:ignore --Reason $sql_act is a query 
            $retun_data = array();
            if(!empty($get_activity_status))
            {
                
                foreach($get_activity_status as $ract)
                {
                    $plan_started_date = !empty($ract->arm_activity_plan_start_date) ? strtotime($ract->arm_activity_plan_start_date) : '';
                    
                    if(!empty($start_plan_date))
                    {
                        if($start_plan_date == $plan_started_date)
                        {
                            if($ract->arm_action == 'cancel_subscription')
                            {
                                
                                $retun_data = array('status'=>'canceled','canceled_date'=>$ract->arm_date_recorded);
                                break;
                            }
                            else if($ract->arm_action == 'eot')
                            {
                                $retun_data = array('status'=>'expired','canceled_date'=>$ract->arm_date_recorded);
                                break;
                            }
                        }
                        else
                        {
                            if( (!empty( $active_plan_detail ) && in_array( $plan_id,$active_plan_detail ) ) || ( !empty( $future_plan_detail ) && in_array( $plan_id,$future_plan_detail ) ) )
                            {
                                $retun_data = array('status'=>'active','canceled_date'=>'');
                                break;
                            }
                            else {
                                $retun_data = array('status'=>'','canceled_date'=>'');
                            }
                        }
                    }
                    
                }
            }
            else
            {
                if(!empty($suspended_plan_detail) && in_array($plan_id,$suspended_plan_detail))
                {
                    $retun_data = array('status'=>'suspended','canceled_date'=>'');
                }
                else
                {
                    if( (!empty( $active_plan_detail ) && in_array( $plan_id,$active_plan_detail ) ) || ( !empty( $future_plan_detail ) && in_array( $plan_id,$future_plan_detail ) ) )
                    {
                        $retun_data = array('status'=>'active','canceled_date'=>'');
                    }
                    else {
                        $retun_data = array('status'=>'','canceled_date'=>'');
                    }

                }

            }
            return $retun_data;
        }
        function arm_cancel_subscription_data()
        {
            global $wp,$wpdb,$ARMember,$arm_subscription_plans,$arm_capabilities_global;
            $activity_id = intval( $_POST['activity_id'] );//phpcs:ignore

            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_subscriptions'], '1',1); //phpcs:ignore --Reason:Verifying nonce

            $sql_act = $wpdb->prepare('SELECT * FROM '.$ARMember->tbl_arm_activity.' WHERE arm_activity_id=%d',$activity_id); //phpcs:ignore --Reason $ARMember->tbl_arm_activity is a table name
            $get_activity_status = $wpdb->get_row($sql_act,ARRAY_A); //phpcs:ignore --Reason $sql_act is a query

            
            $response ='';
            if($get_activity_status['arm_action'] == 'new_subscription')
            {
                //check membership plan has selected "DO NOT CANCEL UNTIL PLAN EXPIRES" option
                unset($get_activity_status['arm_activity_id']);
                $get_activity_status['arm_action'] = 'cancel_subscription';
                $get_activity_status['arm_date_recorded'] = current_time('mysql');
                $user_id = $get_activity_status['arm_user_id'];

                $plan_id = $get_activity_status['arm_item_id'];

                $update = $arm_subscription_plans->arm_ajax_stop_user_subscription($user_id,$plan_id);
                if($update['type']=='success')
                {
                    $response = array('type' => 'success', 'message' => esc_html__('Subscription plan has been canceled successfully', 'ARMember'));
                }
                else
                {
                    $response = array('type' => 'error', 'message' => esc_html__('Something went wrong please try again', 'ARMember'));
                }
            }
            echo arm_pattern_json_encode($response);
            die;
        }

        function arm_refund_subscription_action()
        {
            global $wp,$wpdb,$ARMember,$arm_capabilities_global,$arm_subscription_plans,$arm_members_class;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_subscriptions'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST ); //phpcs:ignore
            $arm_refund_data = $posted_data['arm_refund_data'];
            if(!empty($arm_refund_data))
            {
                $activity_id = $arm_refund_data['activity_id'];
                $refund_reason = !empty($arm_refund_data['arm_refund_reason']) ? $arm_refund_data['arm_refund_reason'] :'';
                $sql_act = $wpdb->prepare('SELECT `arm_user_id`,`arm_item_id` FROM '.$ARMember->tbl_arm_activity.' WHERE arm_activity_id=%d',$activity_id);//phpcs:ignore --Reason $ARMember->tbl_arm_activity is a table name
                $get_result_sql = $wpdb->get_row($sql_act,ARRAY_A);//phpcs:ignore --Reason $sql_act is a query name
                $user_id = $get_result_sql['arm_user_id'];
                $plan_id = $get_result_sql['arm_item_id'];
                $get_last_transaction_sql = $wpdb->prepare("SELECT arm_transaction_id,arm_payment_gateway FROM ".$ARMember->tbl_arm_payment_log." WHERE arm_user_id=%d AND arm_plan_id=%d ORDER BY arm_log_id DESC LIMIT 1",$user_id,$plan_id); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name
                $get_transaction_detail_sql = $wpdb->get_row($get_last_transaction_sql,ARRAY_A); //phpcs:ignore --Reason $get_last_transaction_sql is a query name
                $arm_payment_gateway = $get_transaction_detail_sql['arm_payment_gateway'];
                $response = array();
                if(!in_array($arm_payment_gateway,array('bank_transfer','manual')))
                {
                    $arm_transaction_id = $get_transaction_detail_sql['arm_transaction_id'];
                    $arm_refund_data['arm_transaction_id'] = $arm_transaction_id;
                    $response = apply_filters('arm_refund_payment_subscription_'.$arm_payment_gateway,$arm_refund_data,$arm_payment_gateway);
                    if(!empty($response) && $response['type'] == 'success' && !empty($arm_refund_data))
                    {
                        $post_data=array('arm_action'=>'delete','user_id'=>$user_id,'plan_id'=>$plan_id);
			
                        do_action('arm_refund_subscription_gateway_action',$user_id, $plan_id, $arm_refund_data);
			
                        $is_removed = $arm_members_class->arm_user_plan_action($post_data,true);
                        $refunded_amount = (!empty($arm_refund_data['arm_refund_mode']) && $arm_refund_data['arm_refund_mode'] == 'full') ? $arm_refund_data['arm_default_refund_amount'] : $arm_refund_data['arm_refund_amount'] ;
                        $refunded_data=array(
                            'refund_activity_id'=>$activity_id,
                            'refund_type'=>$arm_refund_data['arm_refund_mode'],
                            'refund_amount'=>$refunded_amount,
                            'refund_reason'=>$arm_refund_data['arm_refund_reason']
                        );
                        $get_last_canceled_id = $wpdb->prepare('SELECT arm_log_id,arm_extra_vars FROM '.$ARMember->tbl_arm_payment_log.' WHERE arm_user_id=%d AND arm_plan_id=%d AND arm_transaction_status=%s LIMIT 0,1',$user_id,$plan_id,"canceled"); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name
                        $get_last_cenceled_sql = $wpdb->get_row($get_last_canceled_id,ARRAY_A);//phpcs:ignore --Reason $get_last_canceled_id is a query
                        if(!empty($get_last_cenceled_sql['arm_extra_vars']))
                        {
                            $get_extra_vars = maybe_unserialize($get_last_cenceled_sql['arm_extra_vars']);
                            $refunded_data = array_push($get_extra_vars,$refunded_data);
                        }
                        $get_log_id = !empty($get_last_cenceled_sql['arm_log_id']) ? $get_last_cenceled_sql['arm_log_id'] : '';
                        $wpdb->update($ARMember->tbl_arm_payment_log, array('arm_extra_vars' => maybe_serialize($refunded_data)), array('arm_log_id' => $get_log_id));
                    }
                    else
                    {
                        $response = array('type'=>'error','msg'=>$response['msg']);    
                    }
                }else
                {
                    $response = array('type'=>'error','msg'=>esc_html__('Bank Transfer and Manual does not support refund','ARMember'));    
                }
            }
            else
            {
                $response = array('type'=>'error','msg'=>esc_html__('Something went wrong please try again later','ARMember'));    
            }
            echo arm_pattern_json_encode($response);
            die;
        }
    }
}
global $arm_subscription_class;
$arm_subscription_class = new ARM_subsctriptions();