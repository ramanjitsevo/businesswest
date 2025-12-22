<div id="child_user_restrict_sec"  class="arm_settings_section" style="display:none;">
<div class="page_sub_title">
	<?php esc_html_e('Set Child User Restrict Page', 'ARMGroupMembership'); ?>
</div>
<div class="arm_setting_main_content arm_padding_0 arm_margin_top_32 arm_margin_bottom_32">
	<div class="arm_row_wrapper arm_row_wrapper_padding_before">
		<div class="left_content">
			<div class="arm_form_header_label arm-setting-hadding-label"><?php esc_html_e('Restrict Page','ARMGroupMembership');?></div>
		</div>
	</div>
	<div class="arm_content_border"></div>
	<div class="arm_row_wrapper arm_row_wrapper_padding_after arm_padding_top_24 arm_display_block">
		<div class="arm_email_setting_flex_group arm_payment_getway_page arm_google_recaptcha">
			<div class="arm_form_field_block">
				<label class="arm-form-table-label"><?php esc_html_e('Select Child User Restrict Page','ARMGroupMembership');?></label>
				<div class="arm-form-table-content">
					<?php 
						$arm_global_settings->arm_wp_dropdown_pages(
							array(
								'selected' => $arm_gm_selected_page,
								'name' => 'arm_general_settings[arm_gm_child_user_restrict_page]',
								'id' => 'arm_gm_child_user_restrict_page',
								'show_option_none' => __('Select Page', 'ARMGroupMembership'),
								'option_none_value' => '0',
								'class' => 'arm_gm_child_user_restrict_page',
							)
						);
					?>
				</div>
			</div>
		</div>
	</div>
</div>

</div>