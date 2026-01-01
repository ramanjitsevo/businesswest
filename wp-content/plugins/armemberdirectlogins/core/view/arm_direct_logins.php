<?php global $wpdb, $arm_member_direct_logins, $arm_members_class, $arm_global_settings; 
$user_roles = $arm_member_direct_logins->arm_direct_logins_get_all_roles();
$all_members = $arm_member_direct_logins->arm_direct_logins_get_all_members(0,0);
?>

<div class="wrap arm_page arm_manage_members_main_wrapper">
    <div class="content_wrapper" id="content_wrapper">
        <div class="page_title">
            <?php esc_html_e('Manage Direct Logins', 'ARM_DIRECT_LOGINS');?>
            <div class="arm_add_new_item_box">
                <a class="greensavebtn arm_add_direct_login" href="javascript:void(0);">
                    <img align="absmiddle" src="<?php echo esc_url(MEMBERSHIPLITE_IMAGES_URL); ?>/add_new_icon.png" />
                    <span><?php esc_html_e('Add Direct Login', 'ARM_DIRECT_LOGINS') ?></span>
                </a>
            </div>
            <div class="armclear"></div>
        </div>
        <div class="arm_solid_divider"></div>
        <div class="arm_members_grid_container" id="arm_members_grid_container">
            <?php 
            if (file_exists(ARM_DIRECT_LOGINS_VIEW_DIR . '/arm_direct_logins_list_records.php')) {
                    include( ARM_DIRECT_LOGINS_VIEW_DIR.'/arm_direct_logins_list_records.php');
            }
            ?>
        </div>
    </div>
</div>
<?php $arm_member_direct_logins->arm_direct_logins_footer(); ?>

<?php
    global $arm_version;
    if(version_compare($arm_version, '4.0.1', '<'))
    {
?>
        <style type="text/css" title="currentStyle">
            @import "<?php echo esc_url(ARM_DIRECT_LOGINS_ARMEMBER_URL); ?>/datatables/media/css/demo_page.css";
            @import "<?php echo esc_url(ARM_DIRECT_LOGINS_ARMEMBER_URL); ?>/datatables/media/css/demo_table_jui.css";
            @import "<?php echo esc_url(ARM_DIRECT_LOGINS_ARMEMBER_URL); ?>/datatables/media/css/jquery-ui-1.8.4.custom.css";
        </style>
<?php        
    }
    else
    {
?>
        <style type="text/css">
            { padding-top: 1rem !important; }
        </style>
<?php        
    }
?>

<!--./******************** Add New Direct Logins Form ********************/.-->
<div class="arm_add_new_direct_logins popup_wrapper" style="width: 750px;margin-top: 40px;">
    <form method="post" action="#" id="arm_add_direct_logins_wrapper_frm" class="arm_admin_form arm_add_direct_logins_wrapper_frm arm_padding_0 arm_width_100_pct <?php echo is_rtl() ? 'arm_page_rtl' : ''; ?>">
        <div cellspacing="0">
            <div class="popup_wrapper_inner">	
                <div class="add_add_direct_login_close_btn arm_popup_close_btn"></div>
                <div class="popup_header"><?php _e('Add New Direct Login', 'ARM_DIRECT_LOGINS');?></div>
                <div class="wrap popup_content_text">
                    <div class="arm_table_label_direct_logins">	
                        <label class="arm-form-table-label"><?php _e('Select User Type', 'ARM_DIRECT_LOGINS');?></label>
                        <div class="arm_dl_select_user_type right_content arm_radio_btn_wrapper arm_margin_top_20"> 
                            <div>
                                <input id="arm_direct_logins_new_user" class="arm_general_input arm_iradio" type="radio" value="new_user" name="arm_direct_logins_user_type" <?php checked('new_user', 'new_user'); ?> />
                                <label for="arm_direct_logins_new_user" class="arm_padding_right_0"><?php _e('New User', 'ARM_DIRECT_LOGINS'); ?></label>
                                <i class="arm_helptip_icon armfa armfa-question-circle arm_padding_right_46"  title="<?php _e('By selecting this option, It will create new user in site with selected user role & also will generate direct login token.', 'ARM_DIRECT_LOGINS'); ?>"></i>
                            </div>
                            <div>
                                <input id="arm_direct_logins_exists_user" class="arm_general_input arm_iradio" type="radio" value="exists_user" name="arm_direct_logins_user_type"  />
                                <label for="arm_direct_logins_exists_user" class="arm_padding_right_0"><?php _e('Existing User', 'ARM_DIRECT_LOGINS'); ?></label>
                                <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('By selecting this option, It will generate direct login token for selected user.', 'ARM_DIRECT_LOGINS'); ?>"></i>
                            </div>
                        </div>
                        <div class="arm_display_grid arm_grid_col_2">
                            <div class="new_user_section arm_margin_top_28">
                                <label><?php _e('Email Address', 'ARM_DIRECT_LOGINS');?></label>
                                <div class="arm_required_wrapper arm_margin_top_12">
                                    <input type="text" id="arm_direct_logins_email" name="arm_direct_logins_email" data-msg-required="<?php _e('Email can not be left blank.', 'ARM_DIRECT_LOGINS');?>" value="" style="width:330px;" >
                                    <span id="arm_direct_logins_email_error" class="arm_error_msg arm_direct_logins_email_error" style="display:none;"><?php _e('Please enter an email address.', 'ARM_DIRECT_LOGINS');?></span> 
                                    <span id="arm_direct_logins_email_invalid_error" class="arm_error_msg arm_direct_logins_email_invalid_error" style="display:none;"><?php _e('Please enter valid email.', 'ARM_DIRECT_LOGINS');?></span> 
                                    <span id="arm_direct_logins_email_exists_error" class="arm_error_msg arm_direct_logins_email_exists_error" style="display:none;"><?php _e('Email address already exists.', 'ARM_DIRECT_LOGINS');?></span> 
                                </div>
                            </div>
                            
                            <div class="new_user_section arm_margin_top_28">
                                <label><?php _e('Select User Role', 'ARM_DIRECT_LOGINS');?></label>
                                <div class="arm_required_wrapper arm_margin_top_12">
                                    <input type='hidden' id="arm_direct_logins_role" class="arm_direct_logins_role_change_input" name="arm_direct_logins_role" value="administrator" />
                                    <dl class="arm_selectbox arm_direct_logins_form_role arm_width_100_pct" style="margin-right:0px;">
                                        <dt class="arm_width_100_pct">
                                            <span></span><input type="text" style="display:none;" value="" class="arm_autocomplete" />
                                            <i class="armfa armfa-caret-down armfa-lg"></i>
                                        </dt>
                                        <dd>
                                            <ul data-id="arm_direct_logins_role">
                                                <?php if (!empty($user_roles)): ?>
                                                    <?php foreach ($user_roles as $key => $val): ?>
                                                        <li data-label="<?php echo $val; ?>" data-value="<?php echo $key; ?>" data-type="<?php echo $val; ?>"><?php echo $val; ?></li>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </ul>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-field exists_user_section arm_auto_user_field arm_margin_top_28" id="select_user" style="display:none;">
                            <label class="arm-form-table-label"><?php _e('Enter Username/Email', 'ARM_DIRECT_LOGINS'); ?></label>
                            <div class="arm-form-table-content arm_margin_top_12 arm_display_grid arm_grid_col_2">
                                <input id="arm_direct_logins_user_id" type="text" name="arm_direct_logins_user_id" value="" placeholder="<?php _e('Search by username or email...', 'ARM_DIRECT_LOGINS');?>" required data-msg-required="<?php _e('Please select user.', 'ARM_DIRECT_LOGINS');?>" autocomplete="off">
                                <div class="arm_direct_logins_users_items arm_required_wrapper" id="arm_direct_logins_users_items" style="display: none;"></div>
                                <?php /*?><input type='hidden' id="arm_direct_logins_user_id" class="arm_direct_logins_user_id_change_input" name="arm_direct_logins_user_id" value="" />
                                <dl class="arm_selectbox arm_direct_logins_form_role" style="margin-right:0px;">
                                    <dt style="width: 210px;">
                                        <span></span><input type="text" style="display:none;" value="Select user" class="arm_autocomplete" />
                                        <i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_direct_logins_user_id">
                                            <li data-label="Select user" data-value="Select user" data-type="Select user">Select user</li>
                                            <?php if (!empty($all_members)): ?>
                                                <?php foreach ($all_members as $user): ?>
                                                    <li data-label="<?php echo $user->user_login;?>" data-value="<?php echo $user->ID;?>" data-type="<?php echo $user->user_login;?>"><?php echo $user->user_login;?></li>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </dd>
                                </dl><?php */?>
                                <span id="arm_user_ids_error" class="arm_error_msg arm_user_ids_error" style="display:none;"><?php _e('Please select user.', 'ARM_DIRECT_LOGINS');?></span>         
                            </div>
                        </div>
                        
                        <div class="arm_margin_top_28">
                            <label><?php _e('Direct Login Expiration', 'ARM_DIRECT_LOGINS');?></label>
                            <div class="arm_required_wrapper arm_margin_top_20">
                                <div class="arm_table_label_dl_expiration">
                                    <div class="arm_display_grid arm_grid_col_2">
                                        <div>
                                            <input id="arm_direct_logins_expire_type_hours1" class="arm_general_input arm_iradio" type="radio" value="hours" name="arm_direct_logins_expire_type" <?php checked('hours', 'hours'); ?> />
                                            <label for="arm_direct_logins_expire_type_hours1"><?php _e('After Selected Hours', 'ARM_DIRECT_LOGINS'); ?></label>
                                            <input type='hidden' id="arm_direct_logins_hours" class="arm_direct_logins_hours_change_input" name="arm_direct_logins_hours" value="1" />
                                            <div class="arm_margin_top_20">
                                                <dl class="arm_selectbox arm_direct_logins_form_hours arm_width_100_pct" style="margin-right:0px;">
                                                    <dt class="arm_width_100_pct">
                                                        <span></span><input type="text" style="display:none;" value="" class="arm_autocomplete" />
                                                        <i class="armfa armfa-caret-down armfa-lg"></i>
                                                    </dt>
                                                    <dd>
                                                        <ul data-id="arm_direct_logins_hours">
                                                            <?php for ($hours = 1; $hours < 25; $hours++): ?>
                                                                    <li data-label="<?php echo $hours; ?>" data-value="<?php echo $hours; ?>" data-type="<?php echo $hours; ?>"><?php echo $hours; ?></li>
                                                            <?php endfor; ?>
                                                        </ul>
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>
                                        <div class="arm_required_wrapper">
                                            <input id="arm_direct_logins_expire_type_days1" class="arm_general_input arm_iradio" type="radio" value="days" name="arm_direct_logins_expire_type" />
                                            <label for="arm_direct_logins_expire_type_days1"><?php _e('After Selected Days', 'ARM_DIRECT_LOGINS'); ?></label>
                                            <input type='hidden' id="arm_direct_logins_days" class="arm_direct_logins_days_change_input" name="arm_direct_logins_days" value="1" />
                                            <div class="arm_margin_top_20">
                                                <dl class="arm_selectbox arm_direct_logins_form_days arm_width_100_pct" style="margin-right:0px;">
                                                    <dt class="arm_width_100_pct">
                                                        <span></span><input type="text" style="display:none;" value="" class="arm_autocomplete" />
                                                        <i class="armfa armfa-caret-down armfa-lg"></i>
                                                    </dt>
                                                    <dd>
                                                        <ul data-id="arm_direct_logins_days">
                                                            <?php for ($days = 1; $days < 365; $days++): ?>
                                                                    <li data-label="<?php echo $days; ?>" data-value="<?php echo $days; ?>" data-type="<?php echo $days; ?>"><?php echo $days; ?></li>
                                                            <?php endfor; ?>
                                                        </ul>
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>                                 
                                    </div> 
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="armclear"></div>
                </div>
                
                <div class="popup_content_btn popup_footer arm_padding_top_0 arm_padding_bottom_32">
                    <div class="popup_content_btn_wrapper arm_subscription_btn_wrapper">
                        <img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif' ?>" id="arm_diract_login_save_loader_img" class="arm_loader_img" style="left:65%; top:12px; position: relative;display: none;float: <?php echo (is_rtl()) ? 'right' : 'left';?>; "  width="20" height="20" />
                        <button class="arm_cancel_btn add_add_direct_login_close_btn arm_margin_0 arm_margin_right_10" type="button"><?php _e('Cancel','ARM_DIRECT_LOGINS');?></button>
                        <button class="arm_save_btn arm_add_direct_logins_submit arm_margin_right_0" type="submit" data-type="add"><?php _e('Save', 'ARM_DIRECT_LOGINS') ?></button>
                        <?php wp_nonce_field( 'arm_wp_nonce' );
            
                        $arm_wp_nonce = wp_create_nonce( 'arm_wp_nonce' );?>
                        <input type="hidden" name="arm_wp_nonce" value="<?php echo $arm_wp_nonce;?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="armclear"></div>
    </form>
</div>
<!--./******************** Add New Direct Logins Form ********************/.-->

<!--./******************** Edit Direct Logins Form ********************/.-->
<div class="arm_edit_direct_logins popup_wrapper" style="width: 750px;margin-top: 40px;">
    <form method="post" action="#" id="arm_edit_direct_logins_wrapper_frm" class="arm_admin_form arm_edit_direct_logins_wrapper_frm arm_padding_0 arm_width_100_pct <?php echo is_rtl() ? 'arm_page_rtl' : ''; ?>">
        <div cellspacing="0">
            <div class="popup_wrapper_inner">	
                <div class="arm_edit_direct_login_close_btn arm_popup_close_btn"></div>
                <div class="popup_header"><?php esc_html_e('Edit Direct Login', 'ARM_DIRECT_LOGINS');?></div>   
                <div class="wrap popup_content_text">
                    <div class="arm_table_label_direct_logins arm_padding_0">
                        <input type="hidden" id="arm_direct_logins_edit_user_type" name="arm_direct_logins_user_type" value="exists_user" />
                        <input type="hidden" id="arm_direct_logins_edit_user_id" name="arm_direct_logins_user_id" value="" />
                        
                        <div class="form-field edit_exists_user_section " id="select_user">
                            <label class="arm-form-table-label"><?php esc_html_e('Username', 'ARM_DIRECT_LOGINS'); ?></label>
                            <div class="arm-form-table-content arm_margin_top_12">
                                <span class="arm_dl_set_username"></span>
                            </div>
                        </div>

                        <div class="arm_margin_top_28">
                            <label><?php esc_html_e('Direct Login Expiration', 'ARM_DIRECT_LOGINS');?></label>
                            <div class="arm_required_wrapper arm_margin_top_20">
                                <div class="arm_table_label_dl_expiration ">
                                    
                                    <div class="arm_margin_top_20 arm_display_grid arm_grid_col_2">
                                    <div>
                                        <input id="arm_direct_logins_expire_type_hours" class="arm_general_input arm_iradio" type="radio" value="hours" name="arm_direct_logins_expire_type" <?php checked('hours', 'hours'); ?> />
                                        <label for="arm_direct_logins_expire_type_hours"><?php esc_html_e('After Selected Hours', 'ARM_DIRECT_LOGINS'); ?></label>
                                        <input type='hidden' id="arm_direct_logins_hours" class="arm_direct_logins_hours_change_input" name="arm_direct_logins_hours" value="1" />
                                        <div class="arm_margin_top_20">
                                            <dl class="arm_selectbox arm_direct_logins_form_hours arm_width_100_pct" style="margin-right:0px;">
                                                <dt class="arm_width_100_pct">
                                                    <span></span><input type="text" style="display:none;" value="" class="arm_autocomplete" />
                                                    <i class="armfa armfa-caret-down armfa-lg"></i>
                                                </dt>
                                                <dd>
                                                    <ul data-id="arm_direct_logins_hours">
                                                        <?php for ($hours = 1; $hours < 25; $hours++): ?>
                                                                <li data-label="<?php echo $hours; ?>" data-value="<?php echo $hours; ?>" data-type="<?php echo $hours; ?>"><?php echo $hours; ?></li><?php //phpcs:ignore?>
                                                        <?php endfor; ?>
                                                    </ul>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                   
                                        <div class="arm_required_wrapper">
                                            <input id="arm_direct_logins_expire_type_days" class="arm_general_input arm_iradio" type="radio" value="days" name="arm_direct_logins_expire_type" />
                                            <label for="arm_direct_logins_expire_type_days"><?php esc_html_e('After Selected Days', 'ARM_DIRECT_LOGINS'); ?></label>
                                            <input type='hidden' id="arm_direct_logins_days" class="arm_direct_logins_days_change_input" name="arm_direct_logins_days" value="1" />
                                            <div class="arm_margin_top_20">
                                                <dl class="arm_selectbox arm_direct_logins_form_days arm_width_100_pct" style="margin-right:0px;">
                                                    <dt class="arm_width_100_pct">
                                                        <span></span><input type="text" style="display:none;" value="" class="arm_autocomplete" />
                                                        <i class="armfa armfa-caret-down armfa-lg"></i>
                                                    </dt>
                                                    <dd>
                                                        <ul data-id="arm_direct_logins_days">
                                                            <?php for ($days = 1; $days < 365; $days++): ?>
                                                                    <li data-label="<?php echo $days; ?>" data-value="<?php echo $days; ?>" data-type="<?php echo $days; ?>"><?php echo $days; ?></li><?php //phpcs:ignore?>
                                                            <?php endfor; ?>
                                                        </ul>
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>                                    
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="armclear"></div>
                </div>
                
                <div class="popup_content_btn popup_footer arm_padding_top_0 arm_padding_bottom_32">
                    <div class="popup_content_btn_wrapper arm_subscription_btn_wrapper">
                        <img src="<?php echo MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif' ?>" id="arm_loader_img" class="arm_loader_img" style="left:65%; top:12px; position: relative;display: none;float: <?php echo (is_rtl()) ? 'right' : 'left';?>;" width="20" height="20" />
                        <button class="arm_cancel_btn arm_edit_direct_login_close_btn arm_margin_0 arm_margin_right_10" type="button"><?php _e('Cancel','ARM_DIRECT_LOGINS');?></button>
                        <button class="arm_save_btn arm_edit_direct_logins_submit arm_margin_right_0" type="submit" data-type="add"><?php _e('Save', 'ARM_DIRECT_LOGINS') ?></button>
                        <?php wp_nonce_field( 'arm_wp_nonce' );
            
                            $arm_wp_nonce = wp_create_nonce( 'arm_wp_nonce' );?>
                            <input type="hidden" name="arm_wp_nonce" value="<?php echo $arm_wp_nonce;?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="armclear"></div>
    </form>
</div>
<!--./******************** Edit Direct Logins Form ********************/.-->


<div class="arm_badge_details_popup_container" style="display:none;"></div>