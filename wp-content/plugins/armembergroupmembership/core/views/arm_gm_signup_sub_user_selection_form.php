<?php
	foreach($arm_gm_plan_data as $arm_gm_plan_key => $arm_gm_plan_data_val)
	{
		if($arm_gm_plan_data_val['arm_gm_enable_referral'])
		{
			$arm_gm_max_members = $arm_gm_plan_data_val['arm_gm_max_members'];
			$arm_gm_min_members = $arm_gm_plan_data_val['arm_gm_min_members'];
			$arm_gm_sub_user_seat_slot = $arm_gm_plan_data_val['arm_gm_sub_user_seat_slot'];

			if(empty($arm_gm_sub_user_seat_slot))
			{
				$arm_gm_sub_user_seat_slot = 1;
			}
			$arm_already_purchased_members = 0;
			$arm_gm_user_id = get_current_user_id();
			if(!empty($arm_gm_user_id))
			{
				$arm_user_existing_plans = get_user_meta($arm_gm_user_id, 'arm_user_plan_ids', true);
				if(is_array($arm_user_existing_plans) && in_array($arm_gm_plan_key, $arm_user_existing_plans)){
					$arm_already_purchased_members = get_user_meta($arm_gm_user_id, 'gm_sub_user_select_'.$arm_gm_plan_key, true);
				}else{
					$arm_already_purchased_members = 0;
				}

				$arm_already_purchased_members = !empty($arm_already_purchased_members) ? (int)$arm_already_purchased_members : 0;
				if($arm_already_purchased_members <= $arm_gm_max_members)
				{
					$arm_gm_max_members = $arm_gm_max_members - $arm_already_purchased_members;
				}
			}
			$arm_gm_sub_user_selection_css = ".arm_gm_setup_sub_user_selection_main_wrapper .md-select-value span{ margin-left: 15px !important; }";
			$arm_gm_sub_user_selection_css .= ".arm_gm_sub_user_".$arm_gm_plan_key." { display: none; }";

			$arm_is_display = 1;
			$arm_total_display_options = ($arm_gm_max_members / $arm_gm_sub_user_seat_slot);
			if(($arm_gm_min_members == $arm_gm_max_members) || ($arm_total_display_options < 2)){
				$arm_is_display = 0;
			}

			$arm_gm_trial_enabled_or_not = $arm_gm_plan_data_val['arm_is_trial'];

?>
<style type="text/css">
	<?php echo $arm_gm_sub_user_selection_css; //phpcs:ignore?>
</style>
<?php
	$arm_gm_jq_version_flag=$arm_gm_class->arm_gm_is_compatible_with_5_2();
	$new_arm_selection_class = "";
    if($arm_already_purchased_members < $arm_gm_max_members){
		if($arm_gm_jq_version_flag)
		{
			$attr_child_dropdown ='';
			$new_arm_selection_class = "arm_sub_user_5_2";
		}
		else
		{
			$attr_child_dropdown = 'data-ng-controller="ARMCtrl2"';
		}
?>
			<input name="arm_gm_purchased_member_seats_<?php echo $arm_gm_plan_key?>" value="<?php echo $arm_already_purchased_members; ?>" class="arm_hidden" style="display:none"/>
			<div class="arm_gm_setup_sub_user_selection_main_wrapper arm_gm_sub_user_<?php echo $arm_gm_plan_key; //phpcs:ignore ?>" data-arm_gm_is_display="<?php echo intval($arm_is_display); ?>">
				<div class="arm_gm_setup_sub_user_selection_wrapper <?php echo esc_attr($new_arm_selection_class);?>">
					<div class="arm_gm_module_sub_user_container <?php echo esc_attr($arm_gm_div_class); ?>">
						<div class="arm_setup_sub_user_section_dropdown_area">
							<div class="arm_form_input_container arm_container payment_gateway_dropdown_skin1">
								<div class="payment_gateway_dropdown_skin" <?php echo esc_attr($attr_child_dropdown);?>>
									<input type="hidden" class="arm_gm_hidden_min_members_<?php echo $arm_gm_plan_key; //phpcs:ignore?>" value="<?php echo $arm_gm_min_members; //phpcs:ignore?>">
									<?php if($arm_gm_jq_version_flag==1){ 
										    $drpdown_cntr = 0;
											$default_option_label = "";
											$arm_select_field_options = "";
											for($arm_gm_i=$arm_gm_min_members; $arm_gm_i<=$arm_gm_max_members; $arm_gm_i=$arm_gm_i+$arm_gm_sub_user_seat_slot)
											{
												if($arm_gm_i == $arm_gm_min_members)
												{
													$default_option_label = $arm_gm_i;									
												}
												$arm_select_field_options .= '<li class="arm__dc--item" data-label="' . $arm_gm_i . '" data-value="' . $arm_gm_i . '">' . $arm_gm_i . '</li>';										
												
											}
										?>
										<div class="arm-control-group arm-df__form-group arm-df__form-group_select">
											<div class="arm-df__form-field">
												<div class="arm-df__form-field-wrap_select arm-df__form-field-wrap arm-controls">
													<input type="text" name="gm_sub_user_select_<?php echo $arm_gm_plan_key; ?>" id="gm_sub_user_select_<?php echo $arm_gm_plan_key; ?>" data-default-val="<?php echo $arm_gm_min_members; ?>" data-old_value="<?php echo $arm_gm_min_members; ?>" class="arm-selectpicker-input-control arm-df__form-control" value="<?php echo $default_option_label;?>"  onchange="arm_gm_change_plan_price(this,'<?php echo $armFormSetupRandomID; ?>', '<?php echo $arm_gm_plan_data_val['arm_gm_plan_amount']; ?>', '<?php echo $arm_gm_trial_enabled_or_not; ?>');" /><?php //phpcs:ignore?>
													<?php
													$arm_allow_notched_outline = 0;
													if($arm_gm_form_input_style == 'writer_border')
													{
														$arm_allow_notched_outline = 1;
													}
													if(!empty($arm_allow_notched_outline))
													{
														$arm_field_wrap_active_class = (!empty($field_val)) ? ' arm-df__form-material-field-wrap' : '';;
														$ffield_label_html = '<div class="arm-notched-outline">';
														$ffield_label_html .= '<div class="arm-notched-outline__leading"></div>';
															$ffield_label_html .= '<div class="arm-notched-outline__notch">';

														$ffield_label_html .= '<label class="arm-df__label-text" > ' . esc_html__('Select Child Users', 'ARMGroupMembership') . '</label>';
														$ffield_label_html .= '</div>';
														$ffield_label_html .= '<div class="arm-notched-outline__trailing"></div>';
														$ffield_label_html .= '</div>';

														$ffield_label = $ffield_label_html;
													}
													else
													{
														$class_label ='';
														if($arm_gm_form_input_style =="writer")
														{
															$class_label="arm-df__label-text";
														}
														else
														{
															$class_label="arm_form_field_label_text";
														}
														$ffield_label = '<label class="'.$class_label.'" > * ' . esc_html__('Select Child Users', 'ARMGroupMembership') . '</label>';
													}

													?>
													<?php echo $ffield_label; //phpcs:ignore?>
													<dl class="arm-df__dropdown-control column_level_dd arm_member_form_dropdown">
														<dt class="arm__dc--head">
															<span class="arm__dc--head__title"><?php echo $default_option_label; //phpcs:ignore?></span>
															<input type="text" style="display:none;" value="" class="arm-df__dc--head__autocomplete arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i>
														</dt>
														<dd class="arm__dc--items-wrap">
															<ul class="arm__dc--items" data-id="gm_sub_user_select_<?php echo $arm_gm_plan_key; //phpcs:ignore?>" style="display:none;">
															<?php echo $arm_select_field_options; //phpcs:ignore?>
															</ul>
														</dd>
													</dl>
												</div>
											</div>
										</div>			
										<?php
									}else{
									?>
									<md-select name="gm_sub_user_select_<?php echo $arm_gm_plan_key; ?>" class="arm_module_cycle_input select_skin" data-ng-model="gm_sub_user_select" aria-label="gateway" ng-change="arm_gm_change_plan_price(this, '<?php echo $armFormSetupRandomID; ?>', '<?php echo $arm_gm_plan_data_val['arm_gm_plan_amount']; ?>', '<?php echo $arm_gm_trial_enabled_or_not; ?>')"><?php //phpcs:ignore?>
										<?php
											for($arm_gm_i=$arm_gm_min_members; $arm_gm_i<=$arm_gm_max_members; $arm_gm_i=$arm_gm_i+$arm_gm_sub_user_seat_slot)
											{
												if($arm_gm_i == $arm_gm_min_members)
												{
										?>
													<md-option value="<?php echo $arm_gm_i; ?>" class="armMDOption armSelectOption" selected=""><?php echo $arm_gm_i; ?></md-option><?php //phpcs:ignore?>
										<?php
												}
												else
												{
										?>
													<md-option value="<?php echo $arm_gm_i; ?>" class="armMDOption armSelectOption"><?php echo $arm_gm_i; ?></md-option><?php //phpcs:ignore?>
										<?php
												}
											}
										?>
									</md-select>
									<?php }?>
								</div>	
							</div>
						</div>
					</div>
				</div>
			</div>
<?php
    }
		}
	}
?>