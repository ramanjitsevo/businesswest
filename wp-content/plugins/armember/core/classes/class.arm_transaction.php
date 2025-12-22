<?php
if (!class_exists('ARM_transaction'))
{
	class ARM_transaction
	{
		function __construct()
		{
			global $wpdb, $ARMember, $arm_slugs;
			add_action('wp_ajax_arm_transaction_ajax_action', array($this, 'arm_transaction_ajax_action'));
			add_action('wp_ajax_arm_bulk_delete_transactions', array($this, 'arm_bulk_delete_transactions'));
			add_action('wp_ajax_arm_filter_transactions_list', array($this, 'arm_filter_transactions_list'));
			add_action('wp_ajax_arm_transaction_hide_show_columns', array($this, 'arm_transaction_hide_show_columns'));
			// add_action('wp_ajax_arm_change_bank_transfer_status', array($this, 'arm_change_bank_transfer_status'));
			add_action('wp_ajax_arm_preview_log_detail', array($this, 'arm_preview_log_detail'));
			add_action('wp_ajax_arm_invoice_detail', array($this, 'arm_invoice_detail'));
			add_action('wp_ajax_arm_save_manual_payment', array($this, 'arm_add_manual_payment'));
			add_action('wp_ajax_arm_load_transactions', array($this, 'arm_load_transaction_grid'));
			add_action('wp_ajax_arm_get_user_transactions_paging_action', array($this, 'arm_get_user_transactions_paging_action'));
			add_action('wp_ajax_arm_filter_pp_transactions_list', array( $this, 'arm_filter_pp_transactions_list'));

			add_filter('arm_payment_gateway_selection_options',array($this,'arm_payment_gateway_selection_options_func'),10,1);

			add_filter('arm_payment_gateway_plans_selection_options',array($this,'arm_payment_gateway_plans_selection_options_func'));

			add_filter('arm_admin_paid_post_transaction_html',array($this,'arm_admin_paid_post_transaction_html_func'),10,1);
			
			add_filter( 'arm_get_transaction_tab_actions',array($this,'arm_get_transaction_tab_actions_func'),10,1);
		}

		function arm_get_transaction_tab_actions_func($arm_tab_actions){
			global $arm_slugs,$arm_pay_per_post_feature;
			if($arm_pay_per_post_feature->isPayPerPostFeature){
				$arm_plan_tab_active = (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'paid_post') ? '' : 'arm_selected_transaction_tab';
				$arm_pp_tab_active = (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'paid_post') ? 'arm_selected_transaction_tab' : '';
				$arm_tab_actions = '<div class="arm_payment_transaction_tabs">
					<input type="hidden" id="arm_selected_transaction_tab" value="membership"/>
					<a class="arm_transaction_plan_tab arm_all_standard_tab '.$arm_plan_tab_active.'" href="'. admin_url( 'admin.php?page=' . $arm_slugs->transactions ).'">'. esc_html__('Membership Plans','ARMember').'
					</a>
					<a class="arm_transaction_paid_post_tab arm_all_standard_tab '.$arm_pp_tab_active.'" href="'. admin_url( 'admin.php?page=' . $arm_slugs->transactions . '&action=paid_post' ).'">'. esc_html__('Paid Post','ARMember').'
					</a>
				</div>';
			}
			return $arm_tab_actions;
		}

		function arm_admin_paid_post_transaction_html_func(){
			if (file_exists(MEMBERSHIP_VIEWS_DIR . '/arm_transactions.php')) {
				include( MEMBERSHIP_VIEWS_DIR . '/arm_transactions.php');
			}
		}

		function arm_payment_gateway_selection_options_func($arm_selection_options)
		{
			global $wpdb, $ARMember, $arm_slugs, $arm_global_settings, $arm_members_class, $arm_manage_coupons, $arm_payment_gateways, $arm_subscription_plans,$arm_pay_per_post_feature, $arm_gift_version, $arm_manage_gift;
			if($arm_pay_per_post_feature->isPayPerPostFeature || $arm_gift_version)
			{
				$arm_selection_options= '<tr class="form-field form-required arm_transaction_membership_plan_selection_wrapper arm_width_50_pct">
					<th>
						<label for="arm_plan_id">'. esc_html__('Select Plan Type','ARMember').'</label>
					</th>
					<td>
						<div class="arm_transaction_option_input arm_plan_type_enable_radios">
							<div class="arm_transaction_options_radio">
							<input type="radio" class="arm_iradio arm_plan_type_chk" name="plan_type" value="0"  id="arm_plan_type_plan" checked>
							<label for="arm_plan_type_plan" class="arm_margin_left_0 arm_padding_right_10">'. esc_html__('Membership Plan', 'ARMember').'</label></div>';
							
							if($arm_pay_per_post_feature->isPayPerPostFeature) {
								$arm_selection_options .= '<div class="arm_transaction_options_radio"><input type="radio" class="arm_iradio arm_plan_type_chk" name="plan_type" value="1"  id="arm_paid_post_plan_type">
								<label for="arm_paid_post_plan_type" class="arm_margin_left_0 arm_padding_right_10">'. esc_html__('Paid Post', 'ARMember').'</label></div>';
							}
							if($arm_gift_version) {
								$arm_selection_options .= '<div class="arm_transaction_options_radio"><input type="radio" class="arm_iradio arm_plan_type_chk" name="plan_type" value="2"  id="arm_gift_plan_type">
								<label for="arm_gift_plan_type" class="arm_margin_left_0 arm_padding_right_10">'. esc_html__('Gift', 'ARMember').'</label></div>';
							} 
						$arm_selection_options .= '</div>
					</td>
				</tr>';
			}
			else 
			{ 
				$arm_selection_options .= '<input type="hidden" name="plan_type" id="arm_plan_type_plan" value="0">';

			}
			return $arm_selection_options;
		}

		function arm_payment_gateway_plans_selection_options_func($arm_plans_selection_options){
			global $wpdb, $ARMember, $arm_slugs, $arm_global_settings, $arm_members_class, $arm_manage_coupons, $arm_payment_gateways, $arm_subscription_plans,$arm_pay_per_post_feature, $arm_gift_version, $arm_manage_gift;
			if($arm_pay_per_post_feature->isPayPerPostFeature) {
				$arm_plans_selection_options = '<tr class="form-field arm_auto_paid_post_field arm_transaction_paid_post_plan_wrapper hidden_section">
					<th>
						<label for="arm_paid_post_plan_auto_selection">'. esc_html__('Select Paid Post Plan','ARMember').'</label>
					</th>
					<td>
						<input id="arm_paid_post_plan_auto_selection" type="text" name="arm_paid_post_plan_ids" value="" placeholder="'. esc_html__('Search by paid post plan...', 'ARMember').'" data-msg-required="'. esc_html__('Please select paid post plan.', 'ARMember').'">
						<div class="arm_paid_post_plan_items arm_required_wrapper" id="arm_paid_post_plan_items" style="display: none;"></div>
					</td>
				</tr>';
			} 
			if($arm_gift_version) {
				$all_gifts = $arm_manage_gift->arm_get_all_subscription_gift_plans('arm_subscription_plan_id, arm_subscription_plan_name, arm_subscription_plan_status, arm_subscription_plan_type');
				$arm_plans_selection_options .= '<tr class="form-field form-required arm_transaction_membership_gift_wrapper hidden_section">
					<th>
						<label for="arm_gift_id">'. esc_html__('Select Gift','ARMember').'</label>
					</th>
					<td>
						<input type="hidden" id="arm_gift_id" name="manual_payment[gift_id]" value="" data-msg-required="'. esc_html__('Please select at least one membership', 'ARMember').'"/>
						<dl class="arm_selectbox column_level_dd">
							<dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
							<dd>
								<ul data-id="arm_gift_id">
									<li data-label="'. esc_html__('Select Gift', 'ARMember').'" data-value="">'. esc_html__('Select Gift', 'ARMember').'</li>';
									if (!empty($all_gifts)) {
										foreach ($all_gifts as $p) {
											$p_id = $p['arm_subscription_plan_id'];
											if ($p['arm_subscription_plan_status'] == '1' && $p['arm_subscription_plan_type'] != 'free') {
												$arm_plans_selection_options .= '<li data-label="'. stripslashes( esc_attr($p['arm_subscription_plan_name'])).'" data-value="'. esc_attr($p_id) .'">'. stripslashes( esc_html($p['arm_subscription_plan_name'])).'</li>';
											}
										}
									}									
								$arm_plans_selection_options .= '</ul>
							</dd>
						</dl>
					</td>
				</tr>';
			}
			return $arm_plans_selection_options;
		}

		function arm_load_init_data()
		{
			if( !empty($_REQUEST) )
			{
				global $ARMember;
				$posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_REQUEST ); //phpcs:ignore
				if(!empty($posted_data['log_id']) && !empty($posted_data['log_type']) && !empty($posted_data['is_display_invoice']) && $posted_data['is_display_invoice'])
				{
					require_once( MEMBERSHIP_VIEWS_DIR.'/arm_invoice_template.php');
					exit();
				}
				else if(!empty($posted_data['is_display_card_data']) && $posted_data['is_display_card_data'] && !empty($posted_data['arm_mcard_id']) && !empty($posted_data['plan_id']) && !empty($posted_data['iframe_id']))
				{
					require_once( MEMBERSHIP_VIEWS_DIR . '/arm_membership_card_template.php');
					exit();
				}
			}
		}

		function arm_transaction_hide_show_columns()
		{
			global $ARMember, $arm_capabilities_global;
			$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_transactions'], '1',1); //phpcs:ignore --Reason:Verifying nonce
			$posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST ); //phpcs:ignore
			$column_list = isset($posted_data['column_list']) ? $posted_data['column_list'] : '';
			if ($column_list != "")
			{
				$user_id = get_current_user_id();
				$column_list = explode(',', $column_list);
				$transaction_columns = maybe_serialize($column_list);
				$transaction_history_type = isset($posted_data['transaction_history_type']) ? $posted_data['transaction_history_type'] : '';
				if($transaction_history_type=='paid_post')
				{
					update_user_meta($user_id, 'arm_transaction_paid_post_hide_show_columns', $transaction_columns);
				}
				else if($transaction_history_type=='plan'){
					update_user_meta($user_id, 'arm_transaction_hide_show_columns', $transaction_columns);
				}
				else {
					do_action('arm_transaction_hide_show_column_action', $user_id, $transaction_history_type, $transaction_columns, $posted_data);
				}
			}
			die();
		}

		function arm_get_transaction($field = '', $value = '', $output_type = ARRAY_A)
		{
			global $wp, $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_global_settings, $arm_subscription_plans;
			$log_data = array();
			if (!empty($field) && !empty($value) && $value != 0)
			{
				$log_data = $wpdb->get_results( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `" . $field . "`=%s",$value), $output_type); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name
			}
			return $log_data;
		}

		function arm_get_single_transaction($log_id = 0, $output_type = ARRAY_A)
		{
			global $wp, $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_global_settings, $arm_subscription_plans;
			$log_data = array();
			if (!empty($log_id) && $log_id != 0) {
				$log_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_log_id`=%d",$log_id), $output_type); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name
			}
			return $log_data;
		}

		function arm_invoice_detail()
		{
			global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $global_currency_sym,$arm_ajax_pattern_start,$arm_ajax_pattern_end;
			
			do_action('arm_before_preview_invoice_detail');
			$log_id = intval($_POST['log_id']);//phpcs:ignore
			$is_admin_page = !empty($_POST['is_admin']) ? $_POST['is_admin'] : 0;//phpcs:ignore
			$log_type = sanitize_text_field($_POST['log_type']);//phpcs:ignore
			/* Get Edit Rule Form HTML */
			if (!empty($log_id) && $log_id != 0) {
				echo $arm_ajax_pattern_start;
		?>
				<script type="text/javascript">
					jQuery('#arm_invoice_iframe').on('load', function() {
						var iframeDoc = document.getElementById('arm_invoice_iframe');
					});
					function arm_print_invoice() {
						var iframeDoc = document.getElementById('arm_invoice_iframe');
						iframeDoc.contentWindow.arm_print_invoice_content();
					}
				</script>
				<?php if(empty( $is_admin_page ))
				{?>
				<div class="arm_invoice_detail_popup popup_wrapper arm_invoice_detail_popup_wrapper">
					<div class="popup_wrapper_inner" style="overflow: hidden;">
						<div class="popup_header arm_text_align_center" >
							<span class="popup_close_btn arm_popup_close_btn arm_invoice_detail_close_btn"></span>
							<span class="add_rule_content"><?php esc_html_e('Invoice Detail','ARMember' );?></span>
						</div>
						<div class="popup_content_text arm_invoice_detail_popup_text arm_padding_0" id="arm_invoice_detail_popup_text" >
				<?php }?>
						<?php 
							$invoice_view_url = ARM_HOME_URL;
							$invoice_view_url = add_query_arg( 'log_id', esc_attr($log_id), $invoice_view_url );
							$invoice_view_url = add_query_arg( 'log_type', esc_attr($log_type), $invoice_view_url );
							$invoice_view_url = add_query_arg( 'is_display_invoice','1', $invoice_view_url );
						?>

							<iframe src="<?php echo $invoice_view_url; ?>" id="arm_invoice_iframe" class="arm_width_100_pct" style="height:665px;"></iframe>
						</div>
							
						<div class="popup_footer arm_text_align_center" style=" padding: 0 0 35px;">
							<button type="button" name="print" onclick="arm_print_invoice();" value="Print" class="armemailaddbtn"><?php esc_html_e('Print', 'ARMember'); ?></button>
							<?php 
							$invoice_pdf_icon_html='';
							$invoice_pdf_icon_html=apply_filters('arm_membership_invoice_details_outside',$invoice_pdf_icon_html,$log_id);
							echo $invoice_pdf_icon_html; //phpcs:ignore
							?>
							<?php if(empty( $is_admin_page ))
					{?>
						</div>
					</div>
				</div>
		<?php
				}
				echo $arm_ajax_pattern_end;
			}
			exit;
		}
                
                
		function arm_preview_log_detail()
		{
			global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $arm_capabilities_global,$arm_ajax_pattern_start,$arm_ajax_pattern_end;
			$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_transactions'], '1',1); //phpcs:ignore --Reason:Verifying nonce
			$gateways = $arm_payment_gateways->arm_get_all_payment_gateways();
			$bank_transfer_gateways_opts = $gateways['bank_transfer'];
			$log_id = intval($_POST['log_id']); //phpcs:ignore
			$log_type = sanitize_text_field($_POST['log_type']); //phpcs:ignore
			$trxn_status = sanitize_text_field($_POST['trxn_status']); //phpcs:ignore
			$date_time_format =  $arm_global_settings->arm_get_wp_date_time_format();
			$general_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : array();
			$arm_currency_decimal = isset($general_settings['arm_currency_decimal_digit']) ? $general_settings['arm_currency_decimal_digit'] : 2;
			/* Get Edit Rule Form HTML */
			if (!empty($log_id) && $log_id != 0)
			{
				if($log_type == 'bt_log' && $trxn_status!='failed')
				{
					$log_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_log_id`=%d",$log_id)); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name
					if(empty($log_data))
					{
						$log_detail = $this->arm_get_single_transaction($log_id);
					}
					if(!empty($log_data))
					{
						$lStatus = 'pending';
						if ($log_data->arm_transaction_status == '1')
						{
							$lStatus = 'success';
						}

						if ($log_data->arm_transaction_status == '2')
						{
							$lStatus = 'canceled';
						}
						$arm_coupon_on_each_subscriptions = isset($log_data->arm_coupon_on_each_subscriptions) ? $log_data->arm_coupon_on_each_subscriptions : '0';
						$plan_id = $log_data->arm_plan_id;
						//$userPlanData = get_user_meta($log_data->arm_user_id, 'arm_user_plan_'.$plan_id, true);
						$defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
		                $userPlanDatameta = get_user_meta($log_data->arm_user_id, 'arm_user_plan_' . $plan_id, true);
		                $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
		                $planData = shortcode_atts($defaultPlanData, $userPlanDatameta);
		                $planDetail = $planData['arm_current_plan_detail'];
						
						if (!empty($planDetail)) {
		                    $arm_user_plan_details = new ARM_Plan(0);
		                    $arm_user_plan_details->init((object) $planDetail);
		                } else {
		                    $arm_user_plan_details = new ARM_Plan($plan_id);
		                }
		                $payment_type = !empty($arm_user_plan_details->options['payment_type']) ? $arm_user_plan_details->options['payment_type'] : '';
		                $log_detail = array (
							'arm_log_id' => $log_data->arm_log_id,
							'arm_invoice_id' => $log_data->arm_invoice_id,
							'arm_user_id' => $log_data->arm_user_id,
							'arm_plan_id' => $plan_id,
							'arm_payment_gateway' => 'bank_transfer',
							'arm_payment_type' => $payment_type,
							'arm_token' => '',
							'arm_payer_email' => $log_data->arm_payer_email,
							'arm_receiver_email' => '',
							'arm_transaction_id' => $log_data->arm_transaction_id,
							'arm_transaction_payment_type' => '-',
							'arm_transaction_status' => $lStatus,
							'arm_payment_date' => $log_data->arm_created_date,
							'arm_amount' => $log_data->arm_amount,
							'arm_currency' => $log_data->arm_currency,
							'arm_extra_vars' => $log_data->arm_extra_vars,
							'arm_coupon_code' => $log_data->arm_coupon_code,
							'arm_coupon_discount' => $log_data->arm_coupon_discount,
							'arm_coupon_discount_type' => $log_data->arm_coupon_discount_type,
							'arm_created_date' => $log_data->arm_created_date,
							'arm_coupon_on_each_subscriptions' => $arm_coupon_on_each_subscriptions,
							'arm_bank_name' => $log_data->arm_bank_name,
							'arm_account_name' => $log_data->arm_account_name,
							'arm_additional_info' => $log_data->arm_additional_info,
							'arm_payment_transfer_mode' => $log_data->arm_payment_transfer_mode,
						);
						if(!empty($arm_user_plan_details->isGiftPlan)) {
							 $log_detail['arm_is_gift_payment']	= 1;
						}
					}						
				}
				else
				{
					$log_detail = $this->arm_get_single_transaction($log_id);
				}
				if(!empty($log_detail))
				{
					echo $arm_ajax_pattern_start;
					$extra_vars = (isset($log_detail['arm_extra_vars'])) ? maybe_unserialize($log_detail['arm_extra_vars']) : array();
					$arm_is_post_payment = (!empty($log_detail['arm_is_post_payment'])) ? maybe_unserialize($log_detail['arm_is_post_payment']) : 0;
					$arm_is_gift_payment = (!empty($log_detail['arm_is_gift_payment'])) ? maybe_unserialize($log_detail['arm_is_gift_payment']) : 0;		

					$log_detail = apply_filters('arm_filter_preview_log_details', $log_detail, $log_id, $_POST);//phpcs:ignore

					$transaction_id = (!empty($log_detail['arm_transaction_id'])) ? $log_detail['arm_transaction_id'] : esc_html__('Manual', 'ARMember'); 

					$plan_id = (!empty($log_detail['arm_plan_id'])) ? $arm_subscription_plans->arm_get_plan_name_by_id($log_detail['arm_plan_id']): '--';

					$arm_token = (!empty($log_detail['arm_token'])) ? $log_detail['arm_token'] : '--';

					$payment_gateway = (!empty($log_detail['arm_payment_gateway'])) ? $arm_payment_gateways->arm_gateway_name_by_key($log_detail['arm_payment_gateway']) : '--';

					$transaction_status = ucfirst($log_detail['arm_transaction_status']);

					$payment_amount = $arm_payment_gateways->arm_amount_set_separator($log_detail['arm_currency'], $log_detail['arm_amount']) . ' ' . strtoupper($log_detail['arm_currency']);

					$trial_amount = !empty($extra_vars['trial']['amount']) ? number_format((float) $extra_vars['trial']['amount'], $arm_currency_decimal).' '.strtoupper($log_detail['arm_currency']) : '-';
					?>
						<table width="100%" cellspacing="0">
							<tr>
								<th><?php esc_html_e('User','ARMember' );?></th>
								<td><?php 
								if(!empty($log_detail['arm_user_id']))
								{
									$data = get_userdata($log_detail['arm_user_id']);
									echo (!empty($data->user_login)) ? esc_html($data->user_login) : '--';
								}
								else {
									echo '--';
								}
								?></td>
							</tr>
							<tr>
								<th>
									<?php 
										if(!empty($arm_is_post_payment)) 
										{ 
											esc_html_e('Post','ARMember' ); 
										} 
										else if(!empty($arm_is_gift_payment)) 
										{ 
											esc_html_e('Gift','ARMember' ); 
										} 
										else 
										{ 
											esc_html_e('Plan','ARMember' ); 
										} 
									?>
								</th>
								<td><?php echo esc_html($plan_id);?></td>
							</tr>
							<tr>
								<?php
									if ($log_detail['arm_payment_gateway'] == "bank_transfer")
									{
										$transaction_id_field_label = !empty($bank_transfer_gateways_opts['transaction_id_label']) ? stripslashes($bank_transfer_gateways_opts['transaction_id_label']) : esc_html__('Transaction ID', 'ARMember');
								?>
										<th><?php echo esc_html($transaction_id_field_label); ?></th>
								<?php
									}
									else
									{
								?>
										<th><?php esc_html_e('Transaction ID','ARMember' );?></th>
								<?php } ?>

								<td><?php echo esc_html(stripslashes($transaction_id)); ?></td>
							</tr>
							<?php if(!empty($log_detail['arm_token'])):?>
							<tr>
								<th><?php 
								if($log_detail['arm_payment_type'] == 'subscription')
								{
									esc_html_e('Subscription ID','ARMember' );
								}
								else {
									esc_html_e('Token','ARMember' );
								}
								?></th>
								<td><?php echo esc_html($arm_token);?></td>
							</tr>
							<?php endif;?>
							<tr>
								<th><?php esc_html_e('Payment Gateway','ARMember' );?></th>
								<td><?php echo esc_html($payment_gateway);?></td>
							</tr>
							<tr>
								<th><?php esc_html_e('Payment Type','ARMember' );?></th>
								<td><?php ($log_detail['arm_payment_type'] == 'subscription') ? esc_html_e('Subscription', 'ARMember') : esc_html_e('One Time', 'ARMember');?></td>
							</tr>
							<tr>
								<th><?php esc_html_e('Payer Email','ARMember' );?></th>
								<td><?php echo esc_html($log_detail['arm_payer_email']);?></td>
							</tr>
							<?php if(!empty($log_detail['arm_receiver_email'])): ?>
							<tr>
								<th><?php esc_html_e('Receiver Email','ARMember' );?></th>
								<td><?php echo esc_html($log_detail['arm_receiver_email']);?></td>
							</tr>
							<?php endif;?>
							<tr>
								<th><?php esc_html_e('Transaction Status','ARMember' );?></th>
								<td><?php echo esc_html($transaction_status);?></td>
							</tr>
							<tr>
								<th><?php esc_html_e('Payment Amount','ARMember' );?></th>
								<td><?php echo esc_html($payment_amount)?></td>
							</tr>
							<tr>
								<th><?php esc_html_e('Credit Card Number','ARMember' );?></th>
								<td><?php 
								$cc_num = (isset($extra_vars['card_number']) && !empty($extra_vars['card_number'])) ? $extra_vars['card_number'] : '-';
								echo $cc_num; //phpcs:ignore
								?></td>
							</tr>
							<?php if(isset($extra_vars['trial']) && !empty($extra_vars['trial'])): ?>
							<tr>
								<th><?php esc_html_e('Trial Amount','ARMember' );?></th>
								<td><?php echo esc_html($trial_amount)?></td>
							</tr>
							<tr>
								<th><?php esc_html_e('Trial Period','ARMember' );?></th>
								<td><?php 
								$trialInterval = $extra_vars['trial']['interval'];
								$trialData = $trialInterval.' ';
								if ($extra_vars['trial']['period'] == 'Y')
								{
									$trialData .= ($trialInterval > 1) ? esc_html__('Years', 'ARMember') : esc_html__('Year', 'ARMember');
								}
								elseif ($extra_vars['trial']['period'] == 'M')
								{
									$trialData .= ($trialInterval > 1) ? esc_html__('Months', 'ARMember') : esc_html__('Month', 'ARMember');
								}
								elseif ($extra_vars['trial']['period'] == 'W')
								{
									$trialData .= ($trialInterval > 1) ? esc_html__('Weeks', 'ARMember') : esc_html__('Week', 'ARMember');
								}
								else
								{
									$trialData .= ($trialInterval > 1) ? esc_html__('Days', 'ARMember') : esc_html__('Day', 'ARMember');
								}
								echo esc_html($trialData);
								?></td>
							</tr>
							<?php endif;?>
							<?php if(!empty($log_detail['arm_coupon_code'])): ?>
							<tr>
								<th><?php esc_html_e('Used Coupon Code','ARMember' );?></th>
								<td><?php echo esc_html($log_detail['arm_coupon_code']);?></td>
							</tr>
							<tr>
								<th><?php esc_html_e('Used Coupon Discount','ARMember' );?></th>
								<td><?php
									if(!empty($log_detail['arm_coupon_discount']) && $log_detail['arm_coupon_discount'] > 0)
									{ 
										$discount_type = ($log_detail['arm_coupon_discount_type'] != 'percentage') ? " " .$log_detail['arm_coupon_discount_type'] : "%";
										$discount_decimal = number_format((float) $log_detail['arm_coupon_discount'], $arm_currency_decimal);
										echo esc_html($discount_decimal);
										echo esc_html($discount_type);
									}
									else
									{
										echo 0;
									}
								?></td>
							</tr>
							
							<?php endif;?>
							<?php if ($log_detail['arm_payment_gateway'] == "bank_transfer"):
								$bank_name_field_label = !empty($bank_transfer_gateways_opts['bank_name_label']) ? stripslashes($bank_transfer_gateways_opts['bank_name_label']) : esc_html__('Bank Name', 'ARMember');
								$account_name_field_label = !empty($bank_transfer_gateways_opts['account_name_label']) ? stripslashes($bank_transfer_gateways_opts['account_name_label']) : esc_html__('Account Holder Name', 'ARMember');
								$additional_info_field_label = !empty($bank_transfer_gateways_opts['additional_info_label']) ? stripslashes($bank_transfer_gateways_opts['additional_info_label']) : esc_html__('Additional Note', 'ARMember');
								$transfer_mode_field_label = !empty($bank_transfer_gateways_opts['transfer_mode_label']) ? stripslashes($bank_transfer_gateways_opts['transfer_mode_label']) : esc_html__('Payment Mode', 'ARMember');
								?>
								<?php if (isset($log_detail['arm_bank_name']) && !empty($log_detail['arm_bank_name'])): ?>
									<tr>
										<th><?php echo esc_html($bank_name_field_label);?></th>
										<td><?php echo esc_html(stripslashes($log_detail['arm_bank_name']));?></td>
									</tr>
								<?php endif;?>
								<?php if (isset($log_detail['arm_account_name']) && !empty($log_detail['arm_account_name'])): ?>
									<tr>
										<th><?php echo esc_html($account_name_field_label);?></th>
										<td><?php echo esc_html(stripslashes($log_detail['arm_account_name']));?></td>
									</tr>
								<?php endif;?>
								<?php if (isset($log_detail['arm_additional_info']) && !empty($log_detail['arm_additional_info'])): ?>
									<tr>
										<th><?php echo esc_html($additional_info_field_label);?></th>
										<td><?php $additional_info = nl2br(stripslashes($log_detail['arm_additional_info'])); echo esc_html($additional_info);?></td>
									</tr>
								<?php endif;?>
								<?php if (isset($log_detail['arm_payment_transfer_mode']) && !empty($log_detail['arm_payment_transfer_mode'])): ?>
									<tr>
										<th><?php echo esc_html($transfer_mode_field_label);?></th>
										<td><?php $transfer_mode = nl2br($log_detail['arm_payment_transfer_mode']); echo esc_html($transfer_mode);?></td>
									</tr>
								<?php endif;?>
							<?php endif;?>
							<?php if ($log_detail['arm_payment_gateway'] == "manual" && !empty($extra_vars['note'])): ?>
							<tr>
								<th><?php esc_html_e('Note','ARMember' );?></th>
								<td><?php $transaction_note= nl2br(stripslashes($extra_vars['note'])); echo esc_html($transaction_note);?></td>
							</tr>
							<?php endif;

							$all_global_settings = $arm_global_settings->arm_get_all_global_settings();
							$general_settings = $all_global_settings['general_settings'];
							$enable_tax= isset($general_settings['enable_tax']) ? $general_settings['enable_tax'] : 0;
							if($enable_tax)
							{
							?>
								<tr>
									<th><?php esc_html_e('Tax Percentage','ARMember' );?></th>
									<td><?php
									$tax_percentage = '-';
									if(isset($extra_vars['tax_percentage']))
									{
										$tax_percentage = ($extra_vars['tax_percentage']!='') ? number_format((float)$extra_vars['tax_percentage'],$arm_currency_decimal).'%' : '-';
									}
									echo esc_html($tax_percentage);
									?></td>
								</tr>
								<tr>
									<th><?php esc_html_e('Tax Amount','ARMember' );?></th>
									<td><?php
									$tax_amount = '-';
									if(isset($extra_vars['tax_amount']))
									{
										$tax_amount = ($extra_vars['tax_amount']!='') ? $extra_vars['tax_amount'].' '.strtoupper($log_detail['arm_currency']): '-';
									}
									echo esc_html($tax_amount);	
									?></td>
								</tr>
							<?php 
								}
							?>
							<tr>
								<th><?php esc_html_e('Payment Date','ARMember' );?></th>
								<td><?php $payment_date = date_i18n($date_time_format, strtotime($log_detail['arm_created_date'])); echo esc_html($payment_date);?></td>
							</tr>
							<tr>
								<th><?php esc_html_e('Refund Amount','ARMember' );?></th>
								
								<td><?php $refund_amount = !empty($extra_vars['refund_amount']) ?$arm_payment_gateways->arm_amount_set_separator($log_detail['arm_currency'], $extra_vars['refund_amount']) . ' ' . strtoupper($log_detail['arm_currency']) :'-'; echo esc_html($refund_amount);?></td>
							</tr>
							<tr>
								<th><?php esc_html_e('Refund Reason','ARMember' );?></th>
								<td><?php $refund_reason = !empty($extra_vars['refund_reason']) ? $extra_vars['refund_reason'] : '-'; echo esc_html($refund_reason);?></td>
							</tr>
						</table>
					<?php
					echo $arm_ajax_pattern_end;
				}
			}
			exit;
		}
		function arm_transaction_ajax_action()
		{
			global $wpdb, $ARMember, $arm_members_class, $arm_global_settings, $arm_capabilities_global;
			$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_transactions'], '1',1); //phpcs:ignore --Reason:Verifying nonce
			$posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_POST );//phpcs:ignore
			if(!isset($posted_data))
			{
				return;
			}
			$action = sanitize_text_field($posted_data['act']);
			$id = intval($posted_data['id']);
			$type = sanitize_text_field($posted_data['type']);
			$trxn_status = sanitize_text_field($posted_data['trxn_status']);
			if ($action == 'delete')
			{
				if (empty($id))
				{
					$errors[] = esc_html__('Invalid action.', 'ARMember');
				}
				else
				{
					if (!current_user_can('arm_manage_transactions'))
					{
						$errors[] = esc_html__('Sorry, You do not have permission to perform this action.', 'ARMember');
					}
					else {
						if ($type == 'bt_log' && $trxn_status!='failed')
						{
							$res_var = $wpdb->delete($ARMember->tbl_arm_payment_log, array('arm_log_id' => $id));
						}
						else
						{
							$res_var = $wpdb->delete($ARMember->tbl_arm_payment_log, array('arm_log_id' => $id));
						}

						if ($res_var)
						{
							$message = esc_html__('Record is deleted successfully.', 'ARMember');
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

		function arm_bulk_delete_transactions()
		{
			global $wpdb, $ARMember, $arm_members_class, $arm_global_settings, $arm_capabilities_global;
			$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_transactions'], '1',1); //phpcs:ignore --Reason:Verifying nonce
			$posted_data = array_map( array( $ARMember, 'arm_recursive_sanitize_data' ), $_POST ); //phpcs:ignore
			if(!isset($posted_data))
			{
				return;
			}

			$bulkaction = $arm_global_settings->get_param('action1');
			if ($bulkaction == -1)
			{
				$bulkaction = $arm_global_settings->get_param('action2');
			}

			$btids = $arm_global_settings->get_param('bt-item-action', '');
			$ids = $arm_global_settings->get_param('item-action', '');
			$ppids = $arm_global_settings->get_param('pp-item-action', '');
			$gpids = $arm_global_settings->get_param('gp-item-action', '');
			
			if(empty($ids) && empty($btids) && empty($ppids) && empty($gpids))
			{
				$errors[] = esc_html__('Please select one or more records.', 'ARMember');
			}
			else
			{
				if(!current_user_can('arm_manage_transactions'))
				{
					$errors[] = esc_html__('Sorry, You do not have permission to perform this action.', 'ARMember');
				}
				else
				{
					if($bulkaction == 'delete_transaction')
					{
						$btids = (!is_array($btids)) ? explode(',', $btids) : $btids;
						$ids = (!is_array($ids)) ? explode(',', $ids) : $ids;
						$ppids = (!is_array($ids)) ? explode(',', $ppids) : $ppids;
						$gpids = (!is_array($ids)) ? explode(',', $gpids) : $gpids;

						if (is_array($btids))
						{
							foreach ($btids as $id)
							{
								$res_var = $wpdb->delete($ARMember->tbl_arm_payment_log, array('arm_log_id' => $id));
							}
						}
						if (is_array($ids))
						{
							foreach ($ids as $id)
							{
								$res_var = $wpdb->delete($ARMember->tbl_arm_payment_log, array('arm_log_id' => $id));
							}
						}
						if (is_array($ppids))
						{
							foreach ($ppids as $id)
							{
								$res_var = $wpdb->delete($ARMember->tbl_arm_payment_log, array('arm_log_id' => $id));
							}
						}
						if (is_array($gpids))
						{
							foreach ($gpids as $id)
							{
								$res_var = $wpdb->delete($ARMember->tbl_arm_payment_log, array('arm_log_id' => $id));
							}
						}
						$message = esc_html__('Transaction(s) has been deleted successfully.', 'ARMember');
					}
					else
					{
						$errors[] = esc_html__('Please select valid action.', 'ARMember');
					}
				}
			}

			$return_array = $arm_global_settings->handle_return_messages(@$errors, @$message);
			echo arm_pattern_json_encode($return_array);
			exit;
		}

		function arm_filter_transactions_list()
		{
			global $ARMember, $arm_capabilities_global,$arm_ajax_pattern_start,$arm_ajax_pattern_end;
			$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_transactions'], '1',1); //phpcs:ignore --Reason:Verifying nonce
			echo $arm_ajax_pattern_start;
			if(defined('MEMBERSHIPLITE_VIEWS_DIR') && file_exists(MEMBERSHIPLITE_VIEWS_DIR.'/arm_transactions_list_records.php'))
			{
				include(MEMBERSHIPLITE_VIEWS_DIR.'/arm_transactions_list_records.php');
			}
			echo $arm_ajax_pattern_end;
			die();
		}

		function arm_filter_pp_transactions_list(){
			
			global $ARMember, $arm_capabilities_global;
			$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_transactions'], '1',1);

			if( file_exists( MEMBERSHIP_VIEWS_DIR . '/arm_paid_post_transaction_list_records.php' ) ){
				include( MEMBERSHIP_VIEWS_DIR . '/arm_paid_post_transaction_list_records.php' );
			}
			die;
		}

		function arm_add_manual_payment()
		{
			global $wp, $wpdb, $ARMember, $arm_slugs, $arm_global_settings, $arm_subscription_plans,$arm_pay_per_post_feature, $arm_capabilities_global,$arm_ajax_pattern_start,$arm_ajax_pattern_end;

			$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_transactions'], '1',1);

			$data = array_map( array( $ARMember, 'arm_recursive_sanitize_data'), $_REQUEST ); //phpcs:ignore
			// $redirect_to = admin_url('admin.php?page=' . $arm_slugs->transactions);
			$response = array("type"=>"error","msg" => esc_html__('Something Went Wrong! Please contact to site administrator','ARMember'));
			if (!empty($data))
			{
				$manual_data = $data['manual_payment'];
				$user_id = intval($data['arm_user_id_hidden']);
				$is_post_payment = ($data['plan_type']==1) ? 1 : 0;
				$is_gift_payment = ($data['plan_type']==2) ? 1 : 0;
				
				$arm_paid_post_id = !empty($data['arm_paid_post_id'])?$data['arm_paid_post_id']:0;	
				if(empty($user_id)){
					$message = esc_html__('Sorry, User not found.', 'ARMember');
					$response = array("type"=>"error","msg" => $message);
					echo arm_pattern_json_encode($response);
					die();
				}
				$plan_id = intval($manual_data['plan_id']);
				if(!empty($is_gift_payment))
				{
					$plan_id = intval($manual_data['gift_id']);
				}
				$user_info = get_user_by('id', $user_id);
				$plan = new ARM_Plan($plan_id);
				/* Add transaction payment log */
				$manual_log = array (
					'arm_user_id' => $user_id,
					'arm_first_name'=>$user_info->first_name,
					'arm_last_name'=>$user_info->last_name,
					'arm_plan_id' => $plan_id,
					'arm_payment_gateway' => 'manual',
					'arm_payer_email' => $user_info->user_email,
					'arm_payment_type' => $plan->payment_type,
					'arm_transaction_payment_type' => 'manual',
					'arm_transaction_status' => sanitize_text_field($manual_data['transaction_status']),
					'arm_amount' => $manual_data['amount'],
					'arm_currency' => sanitize_text_field($manual_data['currency']),
					'arm_is_post_payment' => $is_post_payment,
					'arm_is_gift_payment' => $is_gift_payment,
					'arm_paid_post_id' => $arm_paid_post_id,
					'arm_extra_vars' => maybe_serialize(array('note' => $manual_data['note'])),

				);
				$manual_log = apply_filters('arm_modify_payment_data_before_add_manual_payment', $manual_log, $data);
				$log_id = $this->arm_add_transaction($manual_log);
				if($log_id)
				{
					/* Action After Adding Plan */
					do_action('arm_saved_manual_payment', $data);
					$message = esc_html__('Manual payment has been added successfully.', 'ARMember');
					$response = array("type"=>"success","msg" => $message);
					echo arm_pattern_json_encode($response);
					die();
				} else {
					$message = esc_html__('Sorry, Something went wrong. please try again.', 'ARMember');
					$response = array("type"=>"success","msg" => $message);
					echo arm_pattern_json_encode($response);
					die();
				}
			}
			return;
		}

		function arm_add_transaction($log_data = array())
		{
			global $wp, $wpdb, $ARMember, $arm_subscription_plans, $arm_manage_coupons, $arm_payment_gateways;
			$currency = $arm_payment_gateways->arm_get_global_currency();
			$default_log_data = array (
				'arm_invoice_id' => 0,
				'arm_user_id' => 0,
				'arm_first_name' => '',
				'arm_last_name' => '',
				'arm_plan_id' => 0,
				'arm_payment_gateway' => '',
				'arm_payment_type' => '',
				'arm_token' => '',
				'arm_payer_email' => '',
				'arm_receiver_email' => '',
				'arm_transaction_id' => '',
				'arm_transaction_payment_type' => '',
				'arm_transaction_status' => '',
				'arm_payment_mode' => '',
				'arm_payment_date' => current_time('mysql'),
				'arm_amount' => 0,
				'arm_currency' => $currency,
				'arm_extra_vars' => '',
				'arm_coupon_code' => '',
				'arm_coupon_discount' => 0,
				'arm_coupon_discount_type' => '',
                'arm_is_trial' => '0',
				'arm_created_date' => current_time('mysql'),
                'arm_display_log' => '1',
                'arm_coupon_on_each_subscriptions' => '0',
                'arm_is_post_payment' => '0',
                'arm_is_gift_payment' => '0',
                'arm_paid_post_id' => '0',

			);

			$default_log_data = apply_filters('arm_add_default_log_data_value', $default_log_data);

			$log_data = shortcode_atts($default_log_data, $log_data); /* Merge Default Values */

            switch (strtolower($log_data['arm_transaction_status'])) {
				case 'completed':
				case 'paid':
				case 'active':
				case 'trialing':
				case 'succeeded':
				case 'success':
					$log_data['arm_transaction_status'] = 'success';
					break;
				case 'pending':
				case 'past_due':
					$log_data['arm_transaction_status'] = 'pending';
					break;
				case 'canceled':
				case 'unpaid':
					$log_data['arm_transaction_status'] = 'canceled';
                    $log_data['arm_coupon_code'] = $_REQUEST['arm_coupon_code'] = '';
					break;
				case 'failed':
					$log_data['arm_transaction_status'] = 'failed';
                    $log_data['arm_coupon_code'] = $_REQUEST['arm_coupon_code'] = '';
					break;
				case 'expired':
					$log_data['arm_transaction_status'] = 'expired';
                    $log_data['arm_coupon_code'] = $_REQUEST['arm_coupon_code'] = '';
					break;
				default:
					break;
			}

			$coupon_code = !empty($log_data['arm_coupon_code']) ? $log_data['arm_coupon_code'] : '';

			if (!empty($coupon_code) && $arm_manage_coupons->isCouponFeature)
			{
				$log_data['arm_coupon_code'] = $coupon_code;
				$log_data['arm_coupon_discount'] = !empty($log_data['arm_coupon_discount']) ? $log_data['arm_coupon_discount'] : 0;
				$log_data['arm_coupon_discount_type'] = !empty($log_data['arm_coupon_discount_type']) ? $log_data['arm_coupon_discount_type'] : '';
				if($coupon_code != '') {
					$arm_manage_coupons->arm_update_coupon_used_count($coupon_code);
				}
			}
			else {
				$log_data['arm_coupon_code'] = '';
			}
			
			if(is_null($log_data['arm_amount']) || (empty($log_data['arm_amount']) && !empty($log_data['arm_is_trial'])))
			{
				$log_data['arm_amount'] = 0;
			}
			
			if(is_null($log_data['arm_is_trial']))
			{
				$log_data['arm_is_trial'] = 0;
			}

			/* Insert Payment Log Data. */


			$arm_last_invoice_id = get_option('arm_last_invoice_id', 0);

			$arm_transaction_status = $log_data['arm_transaction_status'];

			$arm_invoice_id_generate_flag = 1; // Flag to check if invoice id is generated.
			$arm_invoice_id_generate_flag = apply_filters('arm_is_allowed_generate_invoice_id', $arm_invoice_id_generate_flag, $arm_transaction_status, $log_data); //phpcs:ignore --Reason:Filter to check last invoice id.
			
			if( !empty($arm_invoice_id_generate_flag) )
			{
				$arm_last_invoice_id++;
				$log_data['arm_invoice_id'] = $arm_last_invoice_id;
			}
			else
			{
				$log_data['arm_invoice_id'] = 0;
			}

			do_action('arm_before_add_transaction', $log_data);
			$payment_log = $wpdb->insert($ARMember->tbl_arm_payment_log, $log_data);
			if(!$payment_log)
			{
				//try again for make an entry for payment history due to first entry is failed.
				$arm_insert_data_keys = "";
				$arm_insert_data_values = "";

				foreach($log_data as $arm_log_data_key => $arm_log_data_value)
				{
					$arm_insert_data_keys .= (!empty($arm_insert_data_keys)) ? ",".$arm_log_data_key : $arm_log_data_key;
					$arm_insert_data_values .= (!empty($arm_insert_data_values)) ? ",'".$arm_log_data_value."'" : "'".$arm_log_data_value."'";
				}
				$arm_payment_log = $wpdb->query("INSERT INTO ".$ARMember->tbl_arm_payment_log." (".$arm_insert_data_keys.") VALUES(".$arm_insert_data_values.")"); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name
				
			}
			$payment_log_id = $wpdb->insert_id;

            if (!empty($payment_log_id) && $payment_log_id != 0)
            {
            	$log_data['arm_log_id'] = $payment_log_id;
                update_option('arm_last_invoice_id', $arm_last_invoice_id);
                do_action('arm_after_add_transaction', $log_data);
                return $payment_log_id;
            }
            else
            {
                return false;
            }
        }
		function arm_mask_credit_card_number($cc_number = '')
		{
			$masked = 'xxxx-xxxx-xxxx-' . substr($cc_number, -4);
			return $masked;
		}

		function arm_get_total_transaction($user_id = 0,$is_paid_post = 0) {
			global $ARMember, $wp, $wpdb;
			$where_plog = $wpdb->prepare(" WHERE arm_display_log = %d ",1);
			if(isset($user_id) && $user_id != '' && $user_id != 0)
			{
			$where_plog.= $wpdb->prepare(" AND `arm_user_id`=%d ",$user_id);
			}

			if(!empty($is_paid_post) && $is_paid_post==1){

				$where_plog.= $wpdb->prepare(" AND `arm_paid_post_id`> %d",0);	
				$where_plog.= $wpdb->prepare(" AND `arm_is_post_payment`= %d",1);
			}
			else if(!empty($is_paid_post) && $is_paid_post==2){
				$where_plog.= $wpdb->prepare(" AND `arm_is_gift_payment`= %d",1);
			}else{
				$where_plog.= $wpdb->prepare(" AND `arm_paid_post_id`= %d",0);	
				$where_plog.= $wpdb->prepare(" AND `arm_is_post_payment`= %d",0);
			}
			$total_payment_log_rows = "SELECT COUNT(*) as count_plog FROM `".$ARMember->tbl_arm_payment_log."` {$where_plog}";
			$count_payment_rows = $wpdb->get_results($total_payment_log_rows); //phpcs:ignore --Reason $total_payment_log_rows is prepared query.
			
			$totalRecord = intval($count_payment_rows[0]->count_plog);
			return $totalRecord;
		}

		function arm_get_all_transaction($user_id = 0, $offset = 0, $perPage = 5,$is_paid_post= 0) {
			global $ARMember, $wp, $wpdb;
			$ctquery = "SELECT pt.arm_log_id,pt.arm_invoice_id,pt.arm_user_id,pt.arm_first_name,pt.arm_last_name,pt.arm_plan_id,pt.arm_transaction_id,pt.arm_amount,pt.arm_currency,pt.arm_payment_gateway,pt.arm_transaction_status,pt.arm_payment_type,pt.arm_extra_vars,wpu.user_login as arm_user_login,pt.arm_display_log as arm_display_log, pt.arm_payment_date, pt.arm_coupon_code, pt.arm_coupon_discount_type, pt.arm_coupon_discount, pt.arm_created_date,pt.arm_is_post_payment,pt.arm_paid_post_id,pt.arm_is_gift_payment FROM `" . $ARMember->tbl_arm_payment_log . "` pt LEFT JOIN `" . $ARMember->tbl_arm_subscription_plans . "` sp ON pt.arm_plan_id = sp.arm_subscription_plan_id LEFT JOIN `" . $wpdb->users . "` wpu ON pt.arm_user_id = wpu.ID ";

			$ptquery = "{$ctquery}";

			$where_plog = $wpdb->prepare(" WHERE arm_display_log = %d ",1);
			if(isset($user_id) && $user_id != '' && $user_id != 0)
			{
				$where_plog.= $wpdb->prepare(" AND `arm_user_id`=%d ",$user_id);
			}
			if(!empty($is_paid_post) && $is_paid_post==1){

				$where_plog.= $wpdb->prepare(" AND `arm_paid_post_id`> %d",0);	
				$where_plog.= $wpdb->prepare(" AND `arm_is_post_payment`= %d",1);
			}
			else if(!empty($is_paid_post) && $is_paid_post==2)
			{
				$where_plog.= $wpdb->prepare(" AND `arm_is_gift_payment`= %d",1);
			}
			else{
				$where_plog.= $wpdb->prepare(" AND `arm_paid_post_id`= %d",0);	
				$where_plog.= $wpdb->prepare(" AND `arm_is_post_payment`= %d",0);
				$where_plog.= $wpdb->prepare(" AND `arm_is_gift_payment`= %d",0);
			}
			$orderby = " order by arm_payment_date desc, arm_invoice_id desc ";
			$phlimit = " LIMIT {$offset},{$perPage}";

			$payment_grid_query = "SELECT * FROM (" . $ptquery . ") AS arm_payment_history_log {$where_plog} {$orderby} {$phlimit}";
			$user_plogs = $wpdb->get_results($payment_grid_query, ARRAY_A); //phpcs:ignore --Reason $payment_grid_query is a table name

			return $user_plogs;
		}

		function arm_get_user_transactions_with_pagging($user_id, $current_page = 1, $perPage = 2, $plan_id_name_array = array(),$is_paid_post=0)
		{
			global $wp, $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_global_settings, $arm_subscription_plans,$arm_payment_gateways, $global_currency_sym;
			$log_data = $temp_logs = array();
			$date_format = $arm_global_settings->arm_get_wp_date_time_format();
			$global_currency = $arm_payment_gateways->arm_get_global_currency();
			$general_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : array();
			$arm_currency_decimal = isset($general_settings['arm_currency_decimal_digit']) ? $general_settings['arm_currency_decimal_digit'] : 2;
			if (!empty($user_id) && $user_id != 0) {
                            
				$perPage = (!empty($perPage) && is_numeric($perPage)) ? $perPage : 5;
				$offset = 0;
				if (!empty($current_page) && $current_page > 1) {
					$offset = ($current_page - 1) * $perPage;
				}                                

				$totalRecord = $this->arm_get_total_transaction($user_id,$is_paid_post);
				$user_logs = $this->arm_get_all_transaction($user_id, $offset, $perPage,$is_paid_post);
				$all_global_settings = $arm_global_settings->arm_get_all_global_settings();
				$general_settings = $all_global_settings['general_settings'];
				$enable_tax= isset($general_settings['enable_tax']) ? $general_settings['enable_tax'] : 0;
					                
				$trans_records = '';
				$trans_records .= '<div class="arm_user_transaction_wrapper" data-user_id="' . $user_id . '" data-is_paid_post="'.$is_paid_post.'">';
				$trans_records .= '<table class="form-table arm_member_last_subscriptions_table" width="100%">';
				$trans_records .= '<tr>';
				$trans_records .= '<td>#</td>';
				$membership_data_label = esc_html__('Membership','ARMember');
				$member_payment_type = esc_html__('Payment Type','ARMember');
				if($is_paid_post!= 2){
					$trans_records .= '<td>'.esc_html__('Membership','ARMember').'</td>';
					$trans_records .= '<td>'.esc_html__('Payment Type','ARMember').'</td>';
				}
				else if($is_paid_post == 2){
					$membership_data_label = esc_html__('Gift','ARMember');
					$member_payment_type = esc_html__('Gift','ARMember');
					$trans_records .= '<td>'.esc_html__('Gift','ARMember').'</td>';
				}
				$trans_records .= '<td>'.esc_html__('Transaction Status','ARMember').'</td>';
				$trans_records .= '<td>'.esc_html__('Gateway','ARMember').'</td>';
				$trans_records .= '<td>'.esc_html__('Amount','ARMember').'</td>';
				if($enable_tax){ 
					$trans_records .= '<td>'.esc_html__('Tax Percentage','ARMember').'</td>';
					$trans_records .= '<td>'.esc_html__('Tax Amount','ARMember').'</td>';
				}
				if($is_paid_post != 2)
				{
					$trans_records .= '<td>'.esc_html__('Used Coupon Code','ARMember').'</td>';
					$trans_records .= '<td>'.esc_html__('Used Coupon Discount','ARMember').'</td>';
				}
				$trans_records .= '<td>'.esc_html__('Payment Date','ARMember').'</td>';
				$trans_records .= '</tr>';
                                
				$i = 1;
				$plan_ids_array = array();
				$plan_ids_name_array = array();

				foreach($user_logs as $user_log)
				{
					$rc = (object) $user_log;
					if(in_array($rc->arm_plan_id, $plan_ids_array)) {
						$subs_plan = stripslashes_deep($plan_ids_name_array[$rc->arm_plan_id]);
					}
					else {
						$subs_plan = stripslashes_deep($plan_id_name_array[$rc->arm_plan_id]);
					}
					$plan_ids_name_array[$rc->arm_plan_id] = $subs_plan;
					$plan_ids_array[] = $rc->arm_plan_id;
					$membership = (!empty($subs_plan)) ? $subs_plan : '-';
					$payment_type = ($rc->arm_payment_type == 'subscription') ? esc_html__('Subscription', 'ARMember') : esc_html__('One Time', 'ARMember');

					$extraVars = (!empty($rc->arm_extra_vars)) ? maybe_unserialize($rc->arm_extra_vars) : array();
					if(!empty($extraVars))
					{
						if(isset($extraVars['manual_by']))
						{
							$payment_type.= '<div class="arm_font_size_12"><em>(' . esc_html($extraVars['manual_by']) . ')</em></div>';
						}
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

					$arm_transaction_status = $this->arm_get_transaction_status_text($arm_transaction_status);
					$arm_gateway = ($rc->arm_payment_gateway != '') ? $arm_payment_gateways->arm_gateway_name_by_key($rc->arm_payment_gateway) : esc_html__('Manual', 'ARMember');

					$t_currency = (isset($rc->arm_currency) && !empty($rc->arm_currency)) ? strtoupper($rc->arm_currency) : strtoupper($global_currency);

					$currency = (isset($all_currencies[$t_currency])) ? $all_currencies[$t_currency] : $global_currency_sym;
					$transAmount = '';
					if (!empty($extraVars) && !empty($extraVars['plan_amount']) && $extraVars['plan_amount'] != 0 )
					{
						$arm_plan_amount = $arm_payment_gateways->arm_amount_set_separator($t_currency, $extraVars['plan_amount']);

						if($arm_plan_amount != $rc->arm_amount)
						{
							$transAmount .= '<span class="arm_transaction_list_plan_amount">'.$arm_payment_gateways->arm_prepare_amount($t_currency, $extraVars['plan_amount']).'</span>';
						}
					}

					$transAmount .= '<span class="arm_transaction_list_paid_amount">';
					if (!empty($rc->arm_amount) && $rc->arm_amount > 0 ) {
						$transAmount .= $arm_payment_gateways->arm_prepare_amount($t_currency, $rc->arm_amount);
						if ($global_currency_sym == $currency && strtoupper($global_currency) != $t_currency) 
						{
							$transAmount .= ' ('.$t_currency.')';
						}
					}
					else
					{
						$transAmount .= $arm_payment_gateways->arm_prepare_amount($t_currency, $rc->arm_amount);
					}
					$transAmount .= '</span>';
					if (!empty($extraVars) && isset($extraVars['trial']))
					{
						$trialInterval = $extraVars['trial']['interval'];
						$transAmount .= '<span class="arm_transaction_list_trial_text">';
						$transAmount .= esc_html__('Trial Period', 'ARMember').": {$trialInterval} ";
						if ($extraVars['trial']['period'] == 'Y')
						{
							$transAmount .= ($trialInterval > 1) ? esc_html__('Years', 'ARMember') : esc_html__('Year', 'ARMember');
						}
						elseif ($extraVars['trial']['period'] == 'M')
						{
							$transAmount .= ($trialInterval > 1) ? esc_html__('Months', 'ARMember') : esc_html__('Month', 'ARMember');
						}
						elseif ($extraVars['trial']['period'] == 'W')
						{
							$transAmount .= ($trialInterval > 1) ? esc_html__('Weeks', 'ARMember') : esc_html__('Week', 'ARMember');
						}
						elseif ($extraVars['trial']['period'] == 'D')
						{
							$transAmount .= ($trialInterval > 1) ? esc_html__('Days', 'ARMember') : esc_html__('Day', 'ARMember');
						}
						$transAmount .= '</span>';
					}

					$arm_used_coupon_discount = '';
					if(!empty($rc->arm_coupon_code))
					{
						if(!empty($rc->arm_coupon_discount) && $rc->arm_coupon_discount > 0)
						{
							if($rc->arm_coupon_discount_type == 'percentage' || $rc->arm_coupon_discount_type == '%')
							{
								$arm_used_coupon_discount = $rc->arm_coupon_discount.'%';
							}
							else
							{
								$arm_used_coupon_discount = $arm_payment_gateways->arm_prepare_amount($t_currency, $rc->arm_coupon_discount);
							}
						}
						else
						{
							$arm_used_coupon_discount = 0;
						}
					}
					else
					{
						$arm_used_coupon_discount = '-';	
					};

					$arm_used_coupon_code = (!empty($rc->arm_coupon_code)) ? $rc->arm_coupon_code : '-';
					$trans_records .= '<tr class="arm_member_last_subscriptions_data">';
					$trans_records .= '<td data-label="#">'.$i.'</td>';
					$trans_records .= '<td class="rec_center" data-label="'.$membership_data_label.'">'.$membership.'</td>';

					if($is_paid_post != 2){
						$trans_records .= '<td class="rec_center" data-label="'.$member_payment_type.'">'.$payment_type.'</td>';
					}
					$trans_records .= '<td data-label="'.esc_html__('Transaction Status','ARMember').'">'.$arm_transaction_status.'</td>';
					$trans_records .= '<td data-label="'.esc_html__('Gateway','ARMember').'">'.$arm_gateway.'</td>';
					$trans_records .= '<td data-label="'.esc_html__('Amount','ARMember').'">'.$transAmount.'</td>';
					if($enable_tax)
					{
						$trans_records .= '<td data-label="'.esc_html__('Tax Percentage','ARMember').'">';
						if (!empty($extraVars) && isset($extraVars['tax_percentage']))
						{
							$trans_records .= ($extraVars['tax_percentage']!='') ? number_format((float)$extraVars['tax_percentage'],$arm_currency_decimal).'%' : '-';
						}
						else
						{
							$trans_records .= '-';
						}

						$trans_records .= '</td>';
						$trans_records .= '<td data-label="'.esc_html__('Tax Amount','ARMember').'">';

						if (!empty($extraVars) && isset($extraVars['tax_amount']))
						{
							$trans_records .= ($extraVars['tax_amount']!='') ? $arm_payment_gateways->arm_prepare_amount($t_currency, $extraVars['tax_amount']) : '-';
						}
						else
						{
							$trans_records .= '-';
						}
						$trans_records .= '</td>';
					}
					if($is_paid_post != 2){
						$trans_records .= '<td data-label="'.esc_html__('Used Coupon Code','ARMember').'">'.$arm_used_coupon_code.'</td>';
						$trans_records .= '<td data-label="'.esc_html__('Used Coupon Discount','ARMember').'">'.$arm_used_coupon_discount.'</td>';
					}
					$trans_records .= '<td data-label="'.esc_html__('Payment Date','ARMember').'">'.date_i18n($date_format, strtotime($rc->arm_created_date)).'</td>';
					$trans_records .= '</tr>';
					$i++;
				}
				if($totalRecord <= 0)
				{
					if($enable_tax)
					{
						$total_column = 11;
					}
					else
					{
						$total_column = 9;
					}

					$trans_records .= '<tr>';
					$trans_records .= '<td colspan="'.esc_attr($total_column).'" class="arm_text_align_center">' . esc_html__('No Payment History Found.', 'ARMember') . '</td>';
					$trans_records .= '</tr>';
				}
                                
				$trans_records .= '</table>';
				$trans_records .= '<div class="arm_membership_history_pagination_block">';
				$transPaging = $arm_global_settings->arm_get_paging_links($current_page, $totalRecord, $perPage, '');
				$trans_records .= '<div class="arm_membership_history_paging_container">' . $transPaging . '</div>';
				$trans_records .= '</div>';
				$trans_records .= '</div>';
			}
			return $trans_records;
		}

		function arm_get_user_transactions_paging_action() {
			global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_payment_gateways, $arm_subscription_plans, $arm_capabilities_global;
			if (isset($_POST['action']) && $_POST['action'] == 'arm_get_user_transactions_paging_action') {//phpcs:ignore
				$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0; //phpcs:ignore
				$current_page = isset($_POST['page']) ? intval($_POST['page']) : 1; //phpcs:ignore
				$per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 5; //phpcs:ignore
				$is_paid_post = isset($_POST['is_paid_post']) ? intval($_POST['is_paid_post']) : 0; //phpcs:ignore
				$plan_id_name_array = $arm_subscription_plans->arm_get_plan_name_by_id_from_array();
				$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_members'], '1',1);
				echo $this->arm_get_user_transactions_with_pagging($user_id, $current_page, $per_page, $plan_id_name_array,$is_paid_post); //phpcs:ignore
			}
			exit;
		}
                
		function arm_get_bank_transfer_logs($filter_gateway = 0, $filter_ptype = 0, $filter_pstatus = 0, $limit = 0)
		{
			global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_global_settings, $arm_subscription_plans;
			$bt_logs = array();
			if (empty($filter_gateway) || $filter_gateway == 'bank_transfer' || $filter_gateway == '0') {
				if (empty($filter_ptype) || $filter_ptype == 'one_time' || $filter_ptype == '0') {
					$where_btlog = 'WHERE arm_payment_gateway="bank_transfer"';
					if (!empty($filter_pstatus) && $filter_pstatus != '0') {
						if ($filter_pstatus == 'success') {
							$where_btlog .= $wpdb->prepare(" AND `arm_transaction_status`=%s",'1');
						}
						if ($filter_pstatus == 'pending') {
							$where_btlog .= $wpdb->prepare(" AND `arm_transaction_status`=%s",'0');
						}
						if ($filter_pstatus == 'canceled') {
							$where_btlog .= $wpdb->prepare(" AND `arm_transaction_status`=%s",'2');
						}
					}
					$where_btlog .= $wpdb->prepare(" AND `arm_is_post_payment`=%d AND `arm_is_gift_payment`=%d AND `arm_paid_post_id`=%d",0,0,0);
                    $sqlLimit = '';
                    if (!empty($limit) && $limit != 0) {
                        $sqlLimit = "LIMIT {$limit}";
                    }
                    $logs = $wpdb->get_results("SELECT * FROM `".$ARMember->tbl_arm_payment_log."` $where_btlog ORDER BY `arm_log_id` DESC {$sqlLimit}"); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name
					if (!empty($logs)) {
						foreach ($logs as $l) {
							$bt_logs[] = $this->arm_convert_bt_to_main_log($l);
						}
					}
				}
			}
			return $bt_logs;
		}
		function arm_convert_bt_to_main_log($data)
		{
			$main_logs = array();
			if (!empty($data)) {
				$lStatus = 'pending';
				if ($data->arm_transaction_status == '1') {
					$lStatus = 'success';
				}
				if ($data->arm_transaction_status == '2') {
					$lStatus = 'canceled';
				}
				$arm_coupon_on_each_subscriptions = isset($data->arm_coupon_on_each_subscriptions) ? $data->arm_coupon_on_each_subscriptions : '0';
				$main_log = array(
					'arm_log_id' => $data->arm_log_id,
                    'arm_invoice_id' => $data->arm_invoice_id,
					'arm_user_id' => $data->arm_user_id,
					'arm_first_name' => $data->arm_first_name,
					'arm_last_name' => $data->arm_last_name,
					'arm_plan_id' => $data->arm_plan_id,
					'arm_payment_gateway' => 'bank_transfer',
					'arm_payment_type' => $data->arm_payment_type,
					'arm_token' => '',
					'arm_payer_email' => $data->arm_payer_email,
					'arm_receiver_email' => '',
					'arm_transaction_id' => $data->arm_transaction_id,
					'arm_transaction_payment_type' => $data->arm_transaction_payment_type,
					'arm_transaction_status' => $lStatus,
					'arm_payment_date' => $data->arm_created_date,
					'arm_amount' => $data->arm_amount,
					'arm_currency' => $data->arm_currency,
					'arm_extra_vars' => maybe_unserialize($data->arm_extra_vars),
					'arm_coupon_code' => $data->arm_coupon_code,
					'arm_coupon_discount' => $data->arm_coupon_discount,
					'arm_coupon_discount_type' => $data->arm_coupon_discount_type,
					'arm_created_date' => $data->arm_created_date,
					'arm_coupon_on_each_subscriptions' => $arm_coupon_on_each_subscriptions,
				);
			}
			return $main_log;
		}
		function arm_change_bank_transfer_status($log_id = 0, $new_status = 0, $check_permission = 1)
		{
			global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_global_settings, $arm_subscription_plans,$arm_manage_coupons, $arm_debug_payment_log_id, $arm_capabilities_global;
			if(!empty($check_permission))
			{
				$ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_transactions'], '1');
			}
			
			$logid_exit_flag = '1';
			if(empty($log_id))
			{
				$log_id = intval($_POST['log_id']);//phpcs:ignore
				$logid_exit_flag = '';
			}

			if(empty($new_status))
			{
				$new_status = sanitize_text_field($_POST['log_status']);//phpcs:ignore
			}
			$response = array('status' => 'error', 'message' => esc_html__('Sorry, Something went wrong. Please try again.', 'ARMember'));
			if (!empty($log_id) && $log_id != 0) {
				$log_data = $wpdb->get_row( $wpdb->prepare("SELECT `arm_log_id`, `arm_user_id`, `arm_plan_id`, `arm_payment_cycle` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_log_id`=%d",$log_id)); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name

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
						$arm_last_payment_status = $wpdb->get_var($wpdb->prepare("SELECT `arm_transaction_status` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_user_id`=%d AND `arm_plan_id`=%d AND `arm_created_date`<=%s ORDER BY `arm_log_id` DESC LIMIT 0,1", $user_id, $plan_id, $nowDate)); //phpcs:ignore  --Reason $ARMember->tbl_arm_payment_log is a table name
					 	$arm_subscription_plans->arm_update_user_subscription_for_bank_transfer($user_id, $plan_id, 'bank_transfer', $payment_cycle, $arm_last_payment_status);
						$wpdb->update($ARMember->tbl_arm_payment_log, array('arm_transaction_status' => 1), array('arm_log_id' => $log_id));

						
						$userPlanData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
						$arm_manage_coupons->arm_coupon_apply_to_subscription($user_id, $log_data, 'bank_transfer', $userPlanData);
						
						if($is_recurring_payment)
						{
							do_action('arm_after_recurring_payment_success_outside', $user_id, $plan_id, 'bank_transfer', $plan_payment_mode);
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
		function arm_get_transaction_status_text($statuses = '')
		{
			$statusClass = 'active';
			$lStatus = 'success';
			switch ($statuses) {
				case 'success':
					$statusClass = 'active';
					$lStatus = esc_html__('success', 'ARMember');
					break;
				case 'pending':
					$statusClass = 'pending';
					$lStatus = esc_html__('pending', 'ARMember');
					break;
				case 'canceled':
				case 'cancelled':
					$statusClass = 'canceled';
					$lStatus = esc_html__('cancelled', 'ARMember');
					break;
				case 'failed':
					$statusClass = 'failed';
					$lStatus = esc_html__('failed', 'ARMember');
					break;
				case 'expired':
					$statusClass = 'expired';
					$lStatus = esc_html__('expired', 'ARMember');
					break;
				default:
					break;
			}
			return '<span class="arm_item_status_text_transaction ' . $statusClass . '">' . ucfirst($lStatus) . '</span>';
		}

        function arm_load_transaction_grid() {
            global $wpdb, $ARMember, $arm_slugs, $arm_members_class, $arm_global_settings, $arm_manage_coupons, $arm_payment_gateways, $arm_subscription_plans, $arm_invoice_tax_feature, $arm_default_user_details_text, $arm_capabilities_global;
            $ARMember->arm_check_user_cap($arm_capabilities_global['arm_manage_transactions'], '1',1); //phpcs:ignore --Reason:Verifying nonce
            $arm_invoice_tax_feature = get_option('arm_is_invoice_tax_feature', 0);
            $date_format = $arm_global_settings->arm_get_wp_date_format();
            $date_time_format = $arm_global_settings->arm_get_wp_date_time_format();
            $global_currency = $arm_payment_gateways->arm_get_global_currency();
	    $general_settings = isset($arm_global_settings->global_settings) ? $arm_global_settings->global_settings : array();
	    $arm_currency_decimal = isset($general_settings['arm_currency_decimal_digit']) ? $general_settings['arm_currency_decimal_digit'] : 2;
            $filter_gateway = isset($_REQUEST['gateway']) ? sanitize_text_field( $_REQUEST['gateway'] ) : '';
            $filter_ptype = isset($_REQUEST['payment_type']) ? sanitize_text_field( $_REQUEST['payment_type'] ) : '';
            $filter_pmode = isset($_REQUEST['payment_mode']) ? sanitize_text_field( $_REQUEST['payment_mode'] ) : '';
            $filter_pstatus = isset($_REQUEST['payment_status']) ? sanitize_text_field( $_REQUEST['payment_status'] ) : '';
            $payment_start_date = isset($_REQUEST['payment_start_date']) ? sanitize_text_field( $_REQUEST['payment_start_date'] ) : '';
            $payment_end_date = isset($_REQUEST['payment_end_date']) ? sanitize_text_field( $_REQUEST['payment_end_date'] ) : '';
            $arm_is_post_payment = isset($_REQUEST['arm_is_post_payment']) ? intval( $_REQUEST['arm_is_post_payment'] ) : 0;
            $arm_is_gift_payment = isset($_REQUEST['arm_is_gift_payment']) ? intval( $_REQUEST['arm_is_gift_payment'] ) : 0;
            $response_data = array();
            $nowDate = current_time('mysql');
            $where_plog = "WHERE 1=1 AND arm_display_log = 1 ";
            if (!empty($filter_gateway) && $filter_gateway != '0') {
                $where_plog .= $wpdb->prepare(" AND `arm_payment_gateway`=%s",$filter_gateway);
            }
            if (!empty($filter_ptype) && $filter_ptype != '0') {
                $where_plog .= $wpdb->prepare(" AND `arm_payment_type`=%s",$filter_ptype);
            }
            if (!empty($filter_pmode) && $filter_pmode != '0') {
                $where_plog .= $wpdb->prepare(" AND `arm_payment_mode`=%s",$filter_pmode);
            }
	    	$where_plog .= $wpdb->prepare(" AND `arm_is_post_payment`= %s AND `arm_is_gift_payment`= %s",$arm_is_post_payment,$arm_is_gift_payment);
            
            if (!empty($filter_pstatus) && $filter_pstatus != '0') {
                $filter_pstatus = strtolower($filter_pstatus);
                $status_query = $wpdb->prepare(" AND ( LOWER(`arm_transaction_status`)=%s",$filter_pstatus);
                if( !in_array($filter_pstatus,array('success','pending','canceled')) ){
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
            /*
            $total_count = $wpdb->get_results('SELECT COUNT(*) as total_logs FROM `' . $ARMember->tbl_arm_payment_log . '` WHERE `arm_is_post_payment`='.$arm_is_post_payment);
            
            $total_fpaylog = $total_count[0]->total_logs;
            

            $total_counter = $total_fpaylog;
            */

            $sorting_ord = isset($_REQUEST['sSortDir_0']) ? strtoupper( sanitize_text_field( $_REQUEST['sSortDir_0'] ) ) : 'DESC';
            $sorting_col = (isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] > 0) ? intval( $_REQUEST['iSortCol_0'] ) : '';
            if( isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] == 0){
                $sorting_ord = 'DESC';
            }
            $offset = isset($_REQUEST['iDisplayStart']) ? intval( $_REQUEST['iDisplayStart'] ) : 0;
            $limit = isset($_REQUEST['iDisplayLength']) ? intval( $_REQUEST['iDisplayLength'] ) : 10;

            $phlimit = " LIMIT {$offset},{$limit}";

            switch ($sorting_col) {
                case 1:
                    $column_name = "`arm_transaction_id`";
                    break;
                case 2:
                    $column_name = "`arm_invoice_id`";
                    break;
                case 3:
                    $column_name = "`arm_first_name`";
                    break;
                case 4:
                    $column_name = "`arm_last_name`";
                    break;
                case 5:
                    $column_name = "`arm_user_login`";
                    break;
                case 6:
                    $column_name = "`arm_user_email`";
                    break;
                case 7:
                    $column_name = "`arm_subscription_plan_name`";
                    break;
                case 8:
                    $column_name = "`arm_payment_gateway`";
                    break;
                case 9:
                    $column_name = "`arm_payment_type`";
                    break;
                case 10:
                    $column_name = "`arm_payer_email`";
                    break;
                case 11:
                    $column_name = "`arm_transaction_status`";
                    break;
                case 13:
                    $column_name = "`arm_amount`";
                    break;
                default:
                    $column_name = "`arm_created_date`";
                    break;
            }
            $orderby = "ORDER BY `arm_payment_history_log`.{$column_name} {$sorting_ord}";
            
            $sSearch = isset($_REQUEST['sSearch']) ? sanitize_text_field( $_REQUEST['sSearch'] ) : '';
            $search_ = "";
            if ($sSearch != '') {
                $search_ = $wpdb->prepare(" AND (`arm_payment_history_log`.`arm_transaction_id` LIKE %s OR `arm_payment_history_log`.`arm_token` LIKE %s OR `arm_payment_history_log`.`arm_payer_email` LIKE %s OR `arm_payment_history_log`.`arm_created_date` LIKE %s OR `arm_payment_history_log`.`arm_first_name` LIKE %s OR `arm_payment_history_log`.`arm_last_name` LIKE %s OR `arm_user_login` LIKE %s OR `arm_user_email` LIKE %s ) ",'%'.$sSearch.'%','%'.$sSearch.'%','%'.$sSearch.'%','%'.$sSearch.'%','%'.$sSearch.'%','%'.$sSearch.'%','%'.$sSearch.'%','%'.$sSearch.'%');
            }
	    $pt_where = 'WHERE';
            $pt_where .= $wpdb->prepare(" `arm_is_post_payment`=%s AND `arm_is_gift_payment`=%d ",$arm_is_post_payment,$arm_is_gift_payment);
            if(!empty($payment_start_date)) {
            	$payment_start_date = date("Y-m-d", strtotime($payment_start_date));
            	$pt_where .= $wpdb->prepare(" AND `pt`.`arm_created_date` >= %s ",$payment_start_date);
            }

            if(!empty($payment_end_date)) {
            	$payment_end_date = date("Y-m-d", strtotime("+1 day", strtotime($payment_end_date)));
            	if($pt_where != "") $pt_where .= " AND "; else $pt_where = " WHERE ";
            	$pt_where .= $wpdb->prepare(" `pt`.`arm_created_date` < %s ",$payment_end_date);
            }
            
            $ctquery = "SELECT pt.arm_log_id,pt.arm_invoice_id,pt.arm_user_id,pt.arm_first_name,pt.arm_last_name,pt.arm_plan_id,pt.arm_payer_email,pt.arm_token,pt.arm_transaction_id,pt.arm_amount,pt.arm_currency,pt.arm_is_trial,pt.arm_payment_gateway,pt.arm_payment_mode,pt.arm_transaction_status,pt.arm_created_date,pt.arm_payment_type,pt.arm_extra_vars,sp.arm_subscription_plan_name,wpu.user_login as arm_user_login, wpu.user_email as arm_user_email,pt.arm_display_log as arm_display_log,pt.arm_is_post_payment as arm_is_post_payment,pt.arm_is_gift_payment as arm_is_gift_payment  FROM `" . $ARMember->tbl_arm_payment_log . "` pt LEFT JOIN `" . $ARMember->tbl_arm_subscription_plans . "` sp ON pt.arm_plan_id = sp.arm_subscription_plan_id LEFT JOIN `" . $wpdb->users . "` wpu ON pt.arm_user_id = wpu.ID " . $pt_where;
            $ptquery = "{$ctquery}";
            
            $total_payment_rows = $wpdb->prepare("SELECT (SELECT COUNT(*) FROM `".$ARMember->tbl_arm_payment_log."` WHERE `arm_display_log` = %d AND `arm_is_post_payment`=%d AND `arm_is_gift_payment`=%d) as total_payment_log",1,$arm_is_post_payment,$arm_is_gift_payment); //phpcs:ignore --Reason $ARMember->tbl_arm_payment_log is a table name
            
            $payment_rows = $wpdb->get_results($total_payment_rows); //phpcs:ignore --Reason $total_payment_rows is a prepared query
            $before_filter = intval($payment_rows[0]->total_payment_log);

            $payment_logs_before_limit = "SELECT COUNT(*) AS total_payments FROM (" . $ptquery . ") AS arm_payment_history_log {$where_plog} {$search_} {$orderby}";
            $ex_query = $wpdb->get_results($payment_logs_before_limit);//phpcs:ignore --Reason payment_logs_before_limit is a prepared query
            
            $after_filter = intval($ex_query[0]->total_payments);
            
            $payment_grid_query = "SELECT * FROM (" . $ptquery . ") AS arm_payment_history_log {$where_plog} {$search_} {$orderby} {$phlimit}";
            
            $phquery = $wpdb->get_results($payment_grid_query, ARRAY_A);//phpcs:ignore --Reason payment_grid_query is a prepared query

            $payment_log = $phquery;
            if (!empty($payment_log)) {
                $effectiveData = array();
                $ai = 0;
                $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                $arm_all_plan_arr = array();
                foreach ($payment_log as $rc) {
                    $rc = (object) $rc;
                    $transactionID = $rc->arm_log_id;
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
                    $log_type = ($rc->arm_payment_gateway == 'bank_transfer') ? 'bt_log' : 'other';
                    $extraVars = (isset($rc->arm_extra_vars)) ? maybe_unserialize($rc->arm_extra_vars) : array();
                    if( $arm_is_post_payment ){
                    	$bulkCheckId = 'pp-cb-item-action-' . $rc->arm_log_id;
                    	$bulkCheckName = 'pp-';
                    } else if( $arm_is_gift_payment ){
                    	$bulkCheckId = 'gp-cb-item-action-' . $rc->arm_log_id;
                    	$bulkCheckName = 'gp-';
                    } else {
                    	$bulkCheckId = 'cb-item-action-' . $rc->arm_log_id;
                    	$bulkCheckName = '';
                    }
                    if ($rc->arm_payment_gateway == 'bank_transfer'):
                        $response_data[$ai][0] = '<input id="' . esc_attr($bulkCheckId) . '" class="chkstanard arm_bt_transaction_bulk_check" type="checkbox" value="' . esc_attr($rc->arm_log_id) . '" name="'.esc_attr($bulkCheckName).'item-action[]">';
                    else:
                        $response_data[$ai][0] = '<input id="' . esc_attr($bulkCheckId) . '" class="chkstanard arm_transaction_bulk_check" type="checkbox" value="' . esc_attr($rc->arm_log_id) . '" name="'.esc_attr($bulkCheckName).'item-action[]">';
                    endif;
                    $response_data[$ai][1] = (!empty($rc->arm_transaction_id)) ? stripslashes($rc->arm_transaction_id) : esc_html__('Manual', 'ARMember');

                    $arm_invoice_id = $arm_global_settings->arm_manipulate_invoice_id($rc->arm_invoice_id);
                    
                    if($arm_transaction_status == 'success' && $arm_invoice_tax_feature == 1) {
                        $response_data[$ai][2] = "<a class='armhelptip arm_invoice_detail' href='javascript:void(0)' data-log_type='" . esc_attr($log_type) . "' data-log_id='" . esc_attr($transactionID) . "' title='" . esc_attr__('View Invoice', 'ARMember') . "'>".$arm_invoice_id."</a>";
                    }
                    else {
                        $response_data[$ai][2] = $arm_invoice_id;
                    }
                    $data = get_userdata($rc->arm_user_id);
                    if (!empty($data)) {
                        $response_data[$ai][3] = (!empty($rc->arm_first_name))? $rc->arm_first_name :'-';
                        $response_data[$ai][4] = (!empty($rc->arm_last_name))? $rc->arm_last_name:'-';
			
                        $response_data[$ai][5] = "<a class='arm_openpreview_popup' href='javascript:void(0)' data-id='".$rc->arm_user_id."' data-arm_hide_edit='1'>".$data->user_login."</a>";
			$response_data[$ai][6] = !empty($rc->arm_user_email) ? $rc->arm_user_email : '-';
                    }
                    else
                    {
                        $response_data[$ai][3] = $arm_default_user_details_text;
                        $response_data[$ai][4] = $arm_default_user_details_text;
                        $response_data[$ai][5] = $arm_default_user_details_text;
			$response_data[$ai][6] = $arm_default_user_details_text;
                    }
                    
                    $response_data[$ai][7] = $arm_subscription_plans->arm_get_plan_name_by_id($rc->arm_plan_id);
                    
                    $userPlanData = get_user_meta($rc->arm_user_id, 'arm_user_plan_'.$rc->arm_plan_id, true);
                    
                    $change_plan = $subscr_effective = '';
                    if(!empty($userPlanData)){
                        $change_plan = $userPlanData['arm_change_plan_to'];
                        $subscr_effective = $userPlanData['arm_subscr_effective'];
                    }
                    
                    if (!isset($effectiveData[$rc->arm_user_id]) && !empty($change_plan) && $change_plan == $rc->arm_plan_id && $subscr_effective > strtotime($nowDate)) {
                        $response_data[$ai][7] .= '<div>' . esc_html__('Effective from', 'ARMember') . ' ' . date_i18n($date_format, $subscr_effective) . '</div>';
                        $effectiveData[$rc->arm_user_id][] = $change_plan;
                    }
                    if($rc->arm_payment_gateway == '')
                    {
                        $payment_gateway = esc_html__('Manual', 'ARMember');
                    }
                    else {
                        $payment_gateway = $arm_payment_gateways->arm_gateway_name_by_key($rc->arm_payment_gateway);
                    } 
                    $response_data[$ai][8] = $payment_gateway;
                    $payment_type = $rc->arm_payment_type;
                    $payment_type_text = '';
                    
                    
                    $plan_id = $rc->arm_plan_id;
                    $userPlanDatameta = get_user_meta($rc->arm_user_id, 'arm_user_plan_' . $plan_id, true);
                    $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                    $oldPlanData = shortcode_atts($defaultPlanData, $userPlanDatameta);
                    $arm_old_plan_detail = $oldPlanData['arm_current_plan_detail'];
                    if (!empty($arm_old_plan_detail)) {
                    	$plan_info = new ARM_Plan($plan_id);
                    	$plan_info->init((object) $arm_old_plan_detail);
                	}
                	else
                	{
                		if(!empty($arm_all_plan_arr[$plan_id]))
		                {
		                    $plan_info = $arm_all_plan_arr[$plan_id];
		                }
		                else
		                {
		                    $plan_info = new ARM_Plan($plan_id);
		                    $arm_all_plan_arr[$plan_id] = $plan_info;
		                }
                	}

                    $user_payment_mode = "";
                    
                    $log_payment_mode = isset($rc->arm_payment_mode) ? $rc->arm_payment_mode : '';
                    
                    if($plan_info->is_recurring()) {

                        if($log_payment_mode != '') {

                            if($log_payment_mode == 'manual_subscription') {

                                $user_payment_mode .= "";
                            }
                            else {
                            	
                                $user_payment_mode .= "<span>(".esc_html__('Automatic','ARMember').")</span>";
                            }
                        }
                        
                        //$payment_type = 'subscription';
                        $payment_type = $plan_info->options['payment_type'];
                    }

                    if($payment_type =='one_time'){
                    		$payment_type_text = esc_html__('One Time', 'ARMember');
                    }
                    else if($payment_type == 'subscription'){

                    		$payment_type_text = esc_html__('Subscription', 'ARMember');
                    }
                    
                    $arm_trial_tran = ($rc->arm_is_trial == 1) ? ' (' . esc_html__('Trial Transaction','ARMember') . ')' : '';
                    
                    $response_data[$ai][9] = $payment_type_text.' '.$user_payment_mode.$arm_trial_tran;
                    $payer_email = '';
                    if($rc->arm_payer_email == '')
                    {
                        $extra = maybe_unserialize($rc->arm_extra_vars);
                        if($extra != '') {

                        	if(array_key_exists('manual_by',$extra)) {

                            	$payer_email = '<em>' . esc_html__($extra['manual_by'], 'ARMember') . '</em>';//phpcs:ignore
                        	}
                        }
                    }
                    else
                    {
                        $payer_email = $rc->arm_payer_email;
                    }

                    if($payer_email=='')
                    {
                    	$payer_email = $arm_default_user_details_text;
                    }

                    $response_data[$ai][10] = $payer_email;

                    $transStatus = $this->arm_get_transaction_status_text($arm_transaction_status);
                    $failed_reason = (isset($extraVars['error']) && !empty($extraVars['error'])) ? $extraVars['error'] : '';
                    if ($rc->arm_transaction_status == 'failed' && !empty($failed_reason)) {
                        $transStatus = '<span class="armhelptip" title="' . $failed_reason . '">' . $transStatus . '</span>';
                    }
                    $response_data[$ai][11] = $transStatus;
                    $response_data[$ai][12] = date_i18n($date_time_format, strtotime($rc->arm_created_date));
                    $rc->arm_currency = (isset($rc->arm_currency) && !empty($rc->arm_currency)) ? strtoupper($rc->arm_currency) : strtoupper($global_currency);
		    $arm_amount = number_format($rc->arm_amount,$arm_currency_decimal);
                    $response_data[$ai][13] = $arm_payment_gateways->arm_amount_set_separator($rc->arm_currency, $arm_amount) . ' ' . strtoupper($rc->arm_currency);

                    $response_data[$ai][14] = (isset($extraVars['card_number']) && !empty($extraVars['card_number'])) ? $extraVars['card_number'] : '-';
                    $gridAction = "<div class='arm_grid_action_btn_container'>";
                    if( $arm_transaction_status == 'success' && $arm_invoice_tax_feature == 1 ) {
                    	$gridAction .= "<a class='armhelptip arm_invoice_detail' href='javascript:void(0)' data-log_type='" . esc_attr($log_type) . "' data-log_id='" . esc_attr($transactionID) . "' title='" . esc_attr__('View Invoice', 'ARMember') . "'><img src='" . MEMBERSHIP_IMAGES_URL . "/invoice_icon.png' onmouseover=\"this.src='" . MEMBERSHIP_IMAGES_URL . "/invoice_icon_hover.png';\" onmouseout=\"this.src='" . MEMBERSHIP_IMAGES_URL . "/invoice_icon.png';\" /></a>";
                    }
                    $gridAction .= "<a class='armhelptip arm_preview_log_detail' href='javascript:void(0)' data-log_type='" . esc_attr($log_type) . "' data-log_id='" . esc_attr($transactionID) . "' data-trxn_status='". esc_attr($arm_transaction_status)."' title='" . esc_attr__('View Detail', 'ARMember') . "'><img src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_preview.svg' onmouseover=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_preview_hover.svg';\" onmouseout=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_preview.svg';\" /></a>";
                    $gridAction .= "<a href='javascript:void(0)' data-log_type='" . esc_attr($log_type) . "' data-delete_log_id='" . esc_attr($transactionID) . "' data-trxn_status='".esc_attr($arm_transaction_status)."' onclick='showConfirmBoxCallback(".esc_attr($transactionID).");'><img src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_delete.svg' class='armhelptip' title='" . esc_attr__('Delete', 'ARMember') . "' onmouseover=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_delete_hover.svg';\" onmouseout=\"this.src='" . MEMBERSHIPLITE_IMAGES_URL . "/grid_delete.svg';\" /></a>";
                    $arm_transaction_del_cls = 'arm_transaction_delete_btn';
                    if( $arm_is_post_payment ){
                    	$arm_transaction_del_cls .= ' arm_pp_transaction_delete_btn';
                    }
                    else if( $arm_is_gift_payment ){
                    	$arm_transaction_del_cls .= ' arm_gp_transaction_delete_btn';
                    }
                    $gridAction .= $arm_global_settings->arm_get_confirm_box($transactionID, esc_html__("Are you sure you want to delete this transaction?", 'ARMember'), $arm_transaction_del_cls, $log_type,esc_html__("Delete", 'ARMember'),esc_html__("Cancel", 'ARMember'),esc_html__("Delete", 'ARMember'));
                    $gridAction .= "</div>";
                    $response_data[$ai][15] = $gridAction;
                    $ai++;
                }
            }

            $columns = ',' . esc_html__('Transaction ID', 'ARMember') . ',' . esc_html__('Invoice ID', 'ARMember') . ',' . esc_html__('User', 'ARMember') . ',' . esc_html__('Membership', 'ARMember') . ',' . esc_html__('Gateway', 'ARMember') . ',' . esc_html__('Payment Type', 'ARMember') . ',' . esc_html__('Payer Email', 'ARMember') . ',' . esc_html__('Transaction Status', 'ARMember') . ',' . esc_html__('Payment Date', 'ARMember') . ',' . esc_html__('Amount', 'ARMember') . ',' . esc_html__('Credit Card Number', 'ARMember') . ',';
            $sEcho = isset($_REQUEST['sEcho']) ? intval($_REQUEST['sEcho']) : '';
            $output = array(
                'sColumn' => $columns,
                'sEcho' => $sEcho,
                'iTotalRecords' => $before_filter, // Before Filtered Records
                'iTotalDisplayRecords' => $after_filter, // After filter records,
                'aaData' => $response_data
            );
            echo json_encode($output);
            die();
        }
    }
}
global $arm_transaction;
$arm_transaction = new ARM_transaction();