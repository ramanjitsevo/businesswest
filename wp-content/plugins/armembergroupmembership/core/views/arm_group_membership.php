<div class="arm_solid_divider arm_margin_bottom_32"></div>
<div id="arm_group_membership_div" class="arm_plan_price_box">
	<div class="page_sub_content">
		<div class="page_sub_title"><?php esc_html_e('Group Membership Settings', 'ARMGroupMembership'); ?></div>
		<table class="form-table arm_addon_plan_settings arm_margin_bottom_5">
			<tbody>
				<tr class="form-field form-required">
					<td class="arm_display_flex arm_margin_0">
						<div class="armswitch arm_global_setting_switch">
							<input type="checkbox" id="arm_gm_group_membership_disable_referral" value="1" class="armswitch_input" name="arm_subscription_plan_options[arm_gm_group_membership_disable_referral]" <?php checked($arm_gm_enable_referral); ?>>
							<label for="arm_gm_group_membership_disable_referral" class="armswitch_label" style="min-width:40px;"></label>
						</div>
						<label  for="arm_gm_group_membership_disable_referral"><?php esc_html_e('Enable Group Membership', 'ARMGroupMembership'); ?></label>
					</td>
				</tr>
				<tr class="form-field form-required arm_gm_sub_opt arm_width_33_pct <?php echo esc_attr($arm_gm_hidden_class); ?>">
					<th>
						<label><?php esc_html_e('Minimum Child Members', 'ARMGroupMembership'); ?> (<?php esc_html_e('Seats', 'ARMGroupMembership'); ?>)</label>
					</th>
					<td class="arm_padding_bottom_12 arm_margin_0">
						<div class="armclear"></div>
						<input name="gm_min_members" id="gm_min_members" type="text" size="50" class="" title="<?php esc_html_e('Minimum Child Members', 'ARMGroupMembership'); ?>" aria-required="true" aria-invalid="false" value="<?php echo intval($arm_gm_min_members); ?>" onkeypress="javascript:return ArmNumberValidation(event, this)">
						<span class="arm_min_member_error"><?php esc_html_e("( Minimum Child Member value cannot be grater than Maximum Child Member. )", "ARMGroupMembership"); ?></span>
						<span class="arm_zero_min_member_error"><?php esc_html_e("( Minimum Child Member value must be greater than 0. )", "ARMGroupMembership"); ?></span>
					</td>
				</tr>
				<tr class="form-field form-required arm_gm_sub_opt arm_width_33_pct <?php echo esc_attr($arm_gm_hidden_class); ?>">
					<th>
						<label><?php esc_html_e('Maximum Child Members', 'ARMGroupMembership'); ?>(<?php esc_html_e('Seats', 'ARMGroupMembership'); ?>)</label>
					</th>
					<td class="arm_padding_bottom_12 arm_margin_0">
						<div class="armclear"></div>
						<input name="gm_max_members" id="gm_max_members" type="text" size="50" class="" title="<?php esc_html_e('Maximum Child Members', 'ARMGroupMembership'); ?>" aria-required="true" aria-invalid="false" value="<?php echo intval($arm_gm_max_members); ?>" onkeypress="javascript:return ArmNumberValidation(event, this)">
						<span class="arm_max_member_error"><?php esc_html_e("( Maximum Child Member value can't low then Minimum Child Member. )", "ARMGroupMembership"); ?></span>
						<span class="arm_zero_max_member_error"><?php esc_html_e("( Maximum Child Member value must be greater than 0. )", "ARMGroupMembership"); ?></span>
					</td>
				</tr>
				<tr class="form-field form-required arm_gm_sub_opt arm_width_33_pct <?php echo esc_attr($arm_gm_hidden_class); ?>">
					<th>
						<label><?php esc_html_e('Child Member Purchase Slab', 'ARMGroupMembership'); ?>(<?php esc_html_e('Seats', 'ARMGroupMembership'); ?>)</label>
					</th>
					<td>
						<div class="armclear"></div>
						<input name="gm_sub_user_seat_slot" id="gm_sub_user_seat_slot" type="text" size="50" class="" title="<?php esc_html_e('Child Member Purchase Slab', 'ARMGroupMembership'); ?>" aria-required="true" aria-invalid="false" value="<?php echo intval($arm_gm_sub_user_seat_slot); ?>" onkeypress="javascript:return ArmNumberValidation(event, this)">
						<span class="arm_sub_user_slot_error"><?php esc_html_e("Purchase Slab Value cannot be greater than Maximum Child Members.", "ARMGroupMembership"); ?></span><br />
					</td>
				</tr>
				<tr class="form-field form-required arm_gm_sub_opt arm_display_inline_block <?php echo esc_attr($arm_gm_hidden_class); ?>">
					<td class="arm_padding_top_0">
						<span class="arm_info_text arm_notice_text arm_width_100_pct arm_margin_top_5"><?php esc_html_e('For Example: If you set "Minimum Child Members" to "5", "Maximum Child Members" to "50" and "Child Member Purchase Slab" to "5" then child user purchase selection will be as 5,10,15,20,... until Maximum seat.', 'ARMGroupMembership'); ?></span>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
			$arm_group_membership_recurring_error_display = false;
			$arm_group_membership_recurring_css = 'color: #f00; margin-left: 3rem; display: none;';
			if(!empty($arm_gm_plan_type) && $arm_gm_plan_type == "subscription")
			{
				$arm_group_membership_recurring_css = 'color: #f00; margin-left: 3rem;';
			}
		?>
		<div class="arm_group_membership_recurring_error hidden_section">
			<span><?php esc_html_e('Note: For subscription plan type, group membership is not possible to purchase with Auto-Debit Recurring Payment Method.', 'ARMGroupMembership'); ?></span>
		</div>
	</div>
</div>