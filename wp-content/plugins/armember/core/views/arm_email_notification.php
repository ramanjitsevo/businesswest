<?php
global $wpdb, $ARMemberLite, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_email_settings,  $arm_slugs,$arm_common_lite;
$active = 'arm_general_settings_tab_active';

$_r_action = isset( $_REQUEST['action'] ) ? sanitize_text_field($_REQUEST['action']) : 'email_notification'; //phpcs:ignore
?>
<div class="wrap arm_page arm_general_settings_main_wrapper">
<?php
	$arm_license_notice = '';
	echo apply_filters('arm_admin_license_notice_html',$arm_license_notice);  //phpcs:ignore
	?>
	<div class="content_wrapper arm_global_settings_content arm_email_notification_main_wrapper" id="content_wrapper">
		<div class="page_title arm_margin_0">
			<?php esc_html_e( 'Email Notifications', 'ARMember' ); ?>
			<a class="greensavebtn arm_add_new_message_btn arm_float_right" href="javascript:void(0);"><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL;?>/add_new_icon.svg"><span><?php esc_html_e('Add New Response','ARMember');?></span></a>
		</div>
		<div class="arm_email_notification_tabs">
            <input type="hidden" id="arm_selected_email_tab" value="standard"/>
            <a class="arm_all_standard_tab"  href="<?php echo admin_url( 'admin.php?page=' . $arm_slugs->email_notifications);?>">
                <?php esc_html_e('Standard','ARMember');?>
			</a>
            <a class="arm_all_advanced_tab arm_selected_email_tab" href="<?php echo admin_url( 'admin.php?page=' . $arm_slugs->email_notifications . '&action=advanced_email' );?>">
                <?php esc_html_e('Advanced','ARMember');?>
			</a>
            
        </div>
		<div class="arm_page_spacing_div"></div>
		<div class="armclear"></div>
		<div class="arm_general_settings_wrapper">
			<div class="arm_loading_grid" style="display: none;"><?php $arm_loader = $arm_common_lite->arm_loader_img_func();
				echo $arm_loader; //phpcs:ignore ?></div>
			<div class="arm_settings_container" style="border-top: 0px;">
				<?php
				if ( file_exists( MEMBERSHIP_VIEWS_DIR . '/arm_email_templates.php' ) ) {
					include MEMBERSHIP_VIEWS_DIR . '/arm_email_templates.php';
				}
				?>
			</div>
		</div>
		<div class="armclear"></div>
	</div>
</div>
<?php
    echo $ARMemberLite->arm_get_need_help_html_content('email-notification-list'); //phpcs:ignore
?>