<?php
	$action = "";
	if(!empty($_GET['action']))
	{
		$action = $_GET['action']; //phpcs:ignore
	}
?>
<div class="wrap arm_page arm_manage_members_main_wrapper arm_addon_grid_wrapper">
	
	<div class="content_wrapper" id="content_wrapper">
		<div class="page_title">
			<?php
			if(empty($action))
			{
				esc_html_e('Manage Group Membership', 'ARMGroupMembership');
			}
			else
			{
				esc_html_e('Add Group Membership', 'ARMGroupMembership');
			}
			?>
		</div>
		<div class="armclear"></div>
		<?php
			if(!empty($action) && $action == "add_group_membership")
			{
				require(ARM_GROUP_MEMBERSHIP_VIEWS_DIR.'/admin/arm_gm_members_add.php');
			}
			else
			{
		?>
		<div class="arm_solid_divider"></div>
		<div class="arm_gm_members_grid_container" id="arm_gm_members_grid_container">
			<input type="hidden" id="arm_gm_child_user_confirmation_txt" value="<?php esc_html_e('Okay', 'ARMGroupMembership'); ?>">
			<?php
				require(ARM_GROUP_MEMBERSHIP_VIEWS_DIR.'/admin/arm_gm_members_listing_records.php');
			?>
		</div>
		<?php
			}
		?>
	</div>
</div>
<style type="text/css" title="currentStyle">
    .paginate_page a{display:none;}
	#poststuff #post-body {margin-top: 32px;}
	.DTFC_ScrollWrapper{background-color: #EEF1F2;}
</style>
<?php // Child Users Modal ?>
<?php // --------------------------------------- ?>
<div id="arm_gm_child_users_data" class="arm_members_list_detail_popup popup_wrapper arm_members_list_detail_popup_wrapper" style="width:900px;min-height: 250px;">
	<div class="popup_wrapper_inner">
		<div class="popup_header arm_member_popup_header_wrapper">
			<div class="page_title">
				<span><?php esc_html_e('Child Users List', 'ARMGroupMembership'); ?></span>
				<div class="popup_close_btn arm_popup_close_btn arm_gm_child_user_cls_btn"></div>
			</div>
		</div>

		<div class="popup_content_text arm_gm_child_user_content_data arm_members_list_detail_popup_text arm_visibility_hidden">

		</div>

		<div class="armclear"></div>
	</div>
</div>
<?php // Members Listing Details Modal ?>
<?php require_once(MEMBERSHIPLITE_VIEWS_DIR.'/arm_view_member_details.php')?>

<?php // Member Edit Modal ?>
<div id="arm_gm_edit_users_data" class="arm_member_edit_detail_popup popup_wrapper arm_member_edit_detail_popup_wrapper" style="width:810px;min-height: 250px;">
	<div class="popup_wrapper_inner" style="overflow: hidden; border: none;">
		<div class="popup_header">
			<span class="popup_close_btn arm_popup_close_btn arm_member_edit_gm_cancel_btn"></span>
			<span class="popup_header_text"><?php esc_html_e('Edit Group Membership', 'ARMGroupMembership'); ?></span>
		</div>

		<div class="popup_content_text arm_gm_edit_user_content_data arm_member_edit_detail_popup_text arm_padding_bottom_24 arm_padding_right_30">
		</div>
		<div class="armclear"></div>
	</div>
</div>
<?php // Sub User Add Modal ?>
<div id="arm_gm_add_sub_users_data" class="arm_member_edit_detail_popup popup_wrapper arm_member_edit_detail_popup_wrapper" style="width:810px;min-height: 250px;">
	<div class="popup_wrapper_inner" style="overflow: hidden; border: none;">
		<div class="popup_header">
			<span class="popup_close_btn arm_popup_close_btn arm_member_add_sub_user_gm_cancel_btn"></span>
			<span class="popup_header_text"><?php esc_html_e('Add Child User', 'ARMGroupMembership'); ?></span>
		</div>

		<div class="popup_content_text arm_gm_add_sub_user_content_data arm_member_add_sub_user_detail_popup_text">
			<form method="POST" id="arm_gm_add_sub_user_form" class="arm_admin_form arm_addon_sml_form">
				<input type="hidden" name="arm_gm_parent_user_id" id="arm_gm_parent_user_id" value="">
				<div class="popup_content_text arm_gm_sub_user_add_div arm_text_align_center arm_padding_0">
					<div class="arm_gm_sub_user_add_wrapper arm_position_relative">
						<span class="arm_edit_plan_lbl arm_margin_bottom_12"><?php esc_html_e('Enter Child Username', 'ARMGroupMembership'); ?> <span class="required_icon">*</span></span>
						<div class="arm_edit_field arm_max_width_100_pct">
							<input type='text' name='arm_gm_sub_user_username' class='arm_form_input_box arm_gm_sub_user_username' required="">
							<div class="arm_gm_username_required_error" style="color: #f00;display: none;"><?php esc_html_e('Please enter child user username', 'ARMGroupMembership'); ?></div>
							<div class="arm_gm_username_error" style="color: #f00;display: none;"><?php esc_html_e('Child User Username already exists...', 'ARMGroupMembership'); ?></div>
						</div>
					</div>
					<div class="arm_gm_sub_user_add_wrapper arm_margin_top_28 arm_position_relative">
						<span class="arm_edit_plan_lbl arm_margin_bottom_12"> <?php esc_html_e('Enter Child Email', 'ARMGroupMembership'); ?> <span class="required_icon">*</span> </span>
						<div class="arm_edit_field arm_max_width_100_pct">
							<input type='email' name='arm_gm_sub_user_email' class='arm_form_input_box arm_gm_sub_user_email' required="">
							<div class="arm_gm_valid_email_required_error" style="color: #f00;display: none;"><?php esc_html_e('Please enter valid child user email', 'ARMGroupMembership'); ?></div>
							<div class="arm_gm_email_required_error" style="color: #f00;display: none;"><?php esc_html_e('Please enter child user email', 'ARMGroupMembership'); ?></div>
							<div class="arm_gm_email_error" style="color: #f00;display: none;"><?php esc_html_e('Child User Email already exists', 'ARMGroupMembership'); ?></div>
						</div>
					</div>
					<div class="arm_gm_sub_user_add_wrapper arm_margin_top_28 arm_position_relative">
						<span class="arm_edit_plan_lbl arm_margin_bottom_12"><?php esc_html_e('Enter Child Password', 'ARMGroupMembership'); ?> <span class="required_icon">*</span> </span>
						<div class="arm_edit_field arm_max_width_100_pct">
							<input type='password' id="arm_gm_user_pass" name='arm_gm_sub_user_password' class='arm_form_input_box arm_gm_sub_user_password' required="">
							<span class="arm_visible_password_admin arm_editor_suffix" id="" style="" onclick="show_hide_pass()"><i class="armfa armfa-eye"></i></span>
							<div class="arm_gm_pass_required_error" style="color: #f00;display: none;"><?php esc_html_e('Please enter child user password', 'ARMGroupMembership'); ?></div>
						</div>
					</div>
					<div class="arm_position_relative arm_margin_top_28 arm_display_block arm_float_right">
					<div class="arm_edit_field arm_display_flex">
						<button class="arm_member_add_sub_user_gm_cancel_btn arm_cancel_btn" type="button"><?php esc_html_e('Close', 'ARMGroupMembership'); ?></button>					
						<button class="arm_member_add_sub_user_gm_save_btn arm_save_btn" type="button"><?php esc_html_e('Save', 'ARMGroupMembership'); ?></button>
						<img src="<?php echo MEMBERSHIPLITE_IMAGES_URL;?>/arm_loader.gif" class="arm_loader_img_add_sub_user_gm arm_submit_btn_loader" style="position:absolute;top:8px;display:none;left:-30px;" width="24" height="24">
					</div>
				</div>
				<?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
				<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
			</form>
			<div class="armclear"></div>
		</div>
	</div>
</div>