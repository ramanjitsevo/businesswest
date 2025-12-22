<?php
global $wpdb, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings,$arm_members_badges,$arm_email_settings,$arm_manage_coupons;
$active = 'arm_general_settings_tab_active';
$b_action = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : "manage_badges";
?>
<?php

global $arm_members_activity;
$setact = 0;
global $check_sorting;
$setact = $arm_members_activity->$check_sorting();
?>
<div class="wrap arm_page arm_general_settings_main_wrapper">
	<?php
    if ($setact != 1) {
		if($ARMember->arm_licence_notice_status()){
			$admin_css_url = admin_url('admin.php?page=arm_manage_license');
			$nonce = wp_create_nonce('arm_wp_nonce');
			?>
			<div class="armember_notice_warning armember_licence_notice_warning">ARMember License is not activated. Please activate license from <a href="<?php echo esc_url($admin_css_url); ?>">here</a><span class="armember_close_licence_notice_icon" id="armember_close_licence_notice_icon" data-nonce="<?php echo $nonce;?>" data-type="armember" title="<?php esc_html_e('Dismiss for 7 days', 'ARMember'); ?>"></span></div>
    <?php }
	} ?>
	<div class="content_wraper arm_badges_settings_content" id="content_wraper">
		<div class="page_title"><?php esc_html_e('Badges & Achievements','ARMember'); ?>
				<?php 
					if($b_action == 'manage_badges') { 
				?>
					 	<div class="arm_add_new_item_box arm_margin_bottom_20" >			
			            <a class="greensavebtn arm_add_new_badges_btn" href="javascript:void(0);" ><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL //phpcs:ignore?>/add_new_icon.svg"><span><?php esc_html_e('Add New Badge', 'ARMember') ?></span></a>
			         </div>
	      	<?php 
	      		} elseif ($b_action == 'manage_achievements') { 
	      	?>
		      		<div class="arm_add_new_item_box arm_margin_bottom_20" >            
	            		<a class="greensavebtn arm_add_achievements_btn" href="javascript:void(0);" ><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL //phpcs:ignore?>/add_new_icon.svg"><span><?php esc_html_e('Add New Achievement', 'ARMember') ?></span></a>
	        			</div>
	      	<?php 
	      		} elseif ($b_action == 'manage_user_achievements') { 
	      	?>
	      		  <div class="arm_add_new_item_box arm_margin_bottom_20" >			
            			<a class="greensavebtn arm_add_user_badges_btn" href="javascript:void(0);" ><img align="absmiddle" src="<?php echo MEMBERSHIPLITE_IMAGES_URL //phpcs:ignore?>/add_new_icon.svg"><span><?php esc_html_e('Add User Badges', 'ARMember');?></span></a>
        				</div>
        		<?php 
        			} 
        		?>
			</div>
		<div class="armclear"></div>
		<div class="arm_general_settings_wrapper">			
			<div class="arm_general_settings_tab_wrapper arm_padding_left_64 arm_width_auto">
				<a class="arm_general_settings_tab arm_badges_tab <?php echo(in_array($b_action, array('manage_badges'))) ? esc_attr($active) : ""; ?>" href="<?php echo esc_url( admin_url('admin.php?page=' . $arm_slugs->badges_achievements) ); ?>">&nbsp;<?php esc_html_e('Badges', 'ARMember'); ?>&nbsp;&nbsp;</a>
                <a class="arm_general_settings_tab arm_achievements_tab arm_margin_left_32 <?php echo (in_array($b_action, array('manage_achievements'))) ? esc_attr($active) : "";?>" href="<?php echo esc_url( admin_url('admin.php?page=' . $arm_slugs->badges_achievements . '&action=manage_achievements') ); ?>">&nbsp;&nbsp;<?php esc_html_e('Achievements', 'ARMember'); ?>&nbsp;&nbsp;</a>
				<a class="arm_general_settings_tab arm_user_badges_tab arm_margin_left_32 <?php echo (in_array($b_action, array('manage_user_achievements'))) ? esc_attr($active) : "";?>" href="<?php echo esc_url( admin_url('admin.php?page=' . $arm_slugs->badges_achievements . '&action=manage_user_achievements') ); ?>">&nbsp;&nbsp;<?php esc_html_e('User Badges', 'ARMember'); ?>&nbsp;&nbsp;</a>
				<div class="armclear"></div>
            </div>			
			<div class="arm_settings_container">
				<?php 
				$file_path = MEMBERSHIP_VIEWS_DIR . '/arm_manage_badges.php';
				switch ($b_action)
				{
					case 'manage_badges':
						$file_path = MEMBERSHIP_VIEWS_DIR . '/arm_manage_badges.php';
						break;
					case 'manage_achievements':
						$file_path = MEMBERSHIP_VIEWS_DIR . '/arm_manage_achievements.php';
						break;					
					case 'manage_user_achievements':
						$file_path = MEMBERSHIP_VIEWS_DIR . '/arm_manage_user_achievements.php';
						break;
					default:
						$file_path = MEMBERSHIP_VIEWS_DIR . '/arm_manage_badges.php';
						break;
				}
				if (file_exists($file_path)) {
					include($file_path);
				}
                ?>
			</div>
		</div>
		<div class="armclear"></div>
	</div>
</div>
<?php
	echo $ARMember->arm_get_need_help_html_content('manage-user-badges-achievements'); //phpcs:ignore
?>