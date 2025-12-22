<?php
if (is_ssl()) {
    define('ARM_GROUP_MEMBERSHIP_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_GROUP_MEMBERSHIP_DIR_NAME));
} else {
    define('ARM_GROUP_MEMBERSHIP_URL', WP_PLUGIN_URL . '/' . ARM_GROUP_MEMBERSHIP_DIR_NAME);
}

define('ARM_GROUP_MEMBERSHIP_CORE_DIR', ARM_GROUP_MEMBERSHIP_DIR . '/core');
define('ARM_GROUP_MEMBERSHIP_CLASSES_DIR', ARM_GROUP_MEMBERSHIP_CORE_DIR . '/classes');
define('ARM_GROUP_MEMBERSHIP_VIEWS_DIR', ARM_GROUP_MEMBERSHIP_CORE_DIR . '/views');
define('ARM_GROUP_MEMBERSHIP_IMAGES_URL', ARM_GROUP_MEMBERSHIP_URL . '/images');
define('ARM_GROUP_MEMBERSHIP_CSS_URL', ARM_GROUP_MEMBERSHIP_URL . '/css');
define('ARM_GROUP_MEMBERSHIP_JS_URL', ARM_GROUP_MEMBERSHIP_URL . '/js');
define('ARM_GROUP_MEMBERSHIP_TEXTDOMAIN', 'ARMGroupMembership');
if(!defined('ARMADDON_STORE_URL')){
    define('ARMADDON_STORE_URL', 'https://www.armemberplugin.com/' );
}    

global $arm_gm_version, $arm_gm_class;
$arm_gm_version = '2.1';

global $wp_version;

global $arm_gm_newdbversion;

if (!class_exists('ARM_GroupMembership')) {

    class ARM_GroupMembership {

        public $isActiveGroupMembership;
        var $tbl_arm_gm_child_users_status;

        function __construct($arm_gm_active_value) {
            global $wpdb;
            $this->tbl_arm_gm_child_users_status = $wpdb->prefix . 'arm_gm_child_users_status';

            $this->isActiveGroupMembership = $arm_gm_active_value;

            register_activation_hook(ARM_GROUP_MEMBERSHIP_DIR.'/armembergroupmembership.php', array('ARM_GroupMembership', 'arm_gm_install'));

            register_activation_hook(ARM_GROUP_MEMBERSHIP_DIR.'/armembergroupmembership.php', array('ARM_GroupMembership', 'arm_gm_check_network_activation'));
    
            register_uninstall_hook(ARM_GROUP_MEMBERSHIP_DIR.'/armembergroupmembership.php', array( 'ARM_GroupMembership', 'arm_gm_uninstall'));

            add_action('init', array($this, 'arm_gm_db_check'), 100);

            add_action('init', array($this, 'arm_gm_load_textdomain'));

            add_filter('arm_admin_notice', array($this, 'arm_gmtive_campaign_admin_notices'), 10, 1);

            add_action('admin_notices', array(&$this, 'arm_version_check_admin_notices_func'));

            register_deactivation_hook( __FILE__, array($this, 'arm_gm_plugin_deactivate'));            
            if(!empty($this->armgm_license_status())){
                add_action('arm_deactivate_feature_settings', array($this, 'arm_gm_deactivate_plugin'), 10, 1);
                add_action('admin_init', array($this, 'upgrade_data_groupmembership'));
            }
            
            if ($this->arm_gm_is_plugin_version_comatible() && $this->armgm_license_status())
            {
                add_action('admin_enqueue_scripts', array( $this, 'arm_gm_scripts' ), 20 );
                add_action('wp_enqueue_scripts', array( $this, 'arm_gm_front_scripts' ) );

                add_action( 'admin_menu', array( $this, 'arm_gm_membership_menu' ), 30 );
                add_filter('arm_default_global_settings', array($this, 'arm_gm_add_default_global_settings'), 10);
               //add_action('arm_update_feature_settings', array($this, 'arm_gm_check_addon_enable_or_not'));

               add_action('user_register', array($this, 'arm_add_capabilities_to_new_user'));
            }
            add_action('admin_enqueue_scripts', array( $this, 'armgm_license_scripts' ), 20 );
            
            add_filter('arm_page_slugs_modify_external',array($this,'arm_gm_page_slugs_modify_external_func'),10,1);

            add_action( 'wp_ajax_armgm_dismiss_licence_notice',array($this,'armgm_dismiss_licence_notice_func'));

            add_action('activated_plugin',array($this,'arm_gm_addon_activated'),11,2);
        }

        function arm_gm_page_slugs_modify_external_func($arm_slugs)
        {
            $arm_slugs->arm_gm_membership = 'arm_gm_membership';
            $arm_slugs->arm_gm_add_group_membership = 'arm_gm_add_group_membership';
            return $arm_slugs;
        }


        /*
        function arm_gm_check_addon_enable_or_not()
        {
            $arm_gm_action = $_POST['action']; //phpcs:ignore
            $arm_gm_arm_features_options = $_POST['arm_features_options']; //phpcs:ignore
            $arm_gm_features_status = $_POST['arm_features_status']; //phpcs:ignore

            if($arm_gm_arm_features_options == "arm_is_multiple_membership_feature")
            {
                $response = array('type' => 'wocommerce_error', 'msg' => __("Group Membership Activated. So Multiple Membership Can't be Activated.", 'ARMGroupMembership'));
                echo json_encode($response);
                die();
            }
        }
        */


        function arm_gm_plugin_deactivate()
        {
            global $wpdb, $ARMember;
            $arm_gm_coupon_exist = $wpdb->get_row("SELECT COUNT(arm_coupon_id) as total FROM `" . $ARMember->tbl_arm_coupons . "` WHERE arm_group_parent_user_id IS NOT NULL", ARRAY_A); //phpcs:ignore

            $arm_coupon_exist_count = $arm_gm_coupon_exist['total'];
            if($arm_coupon_exist_count > 0)
            {
                if(!empty($_POST['action'])) //phpcs:ignore
                {
                    $response = array('type' => 'wocommerce_error', 'msg' => __("One or more users have group membership enabled. So plugin cannot deactivate.", 'ARMGroupMembership'));
                    echo json_encode($response);
                    die();
                }
                else
                {
                    wp_die('One or more users have group membership enabled. So plugin cannot deactivate', 'Plugin Deactivation Error', array('response' => 500,'back_link' => true));
                }
            }
        }

        function arm_gm_membership_menu()
        {
            global $arm_slugs, $current_user;
            $arm_gm_name    = __( 'Group Membership', 'ARMGroupMembership' );
            $arm_gm_title   = __( 'Group Membership', 'ARMGroupMembership' );
            $arm_gm_cap     = 'arm_gm_membership';
            $arm_gm_slug    = 'arm_gm_membership';

            add_submenu_page( $arm_slugs->main, $arm_gm_name, $arm_gm_title, $arm_gm_cap, $arm_gm_slug, array( $this, 'arm_gm_members_route' ) );
        }

        function upgrade_data_groupmembership() {
            global $arm_gm_newdbversion;

            if (!isset($arm_gm_newdbversion) || $arm_gm_newdbversion == "")
                $arm_gm_newdbversion = get_option('arm_gm_version');

            if (version_compare($arm_gm_newdbversion, '2.1', '<')) {
                $path = ARM_GROUP_MEMBERSHIP_VIEWS_DIR . '/upgrade_latest_data_groupmembership.php';
                include($path);
            }
        }

        function arm_gm_members_route()
        {
            global $ARMember;
            $pageWrapperClass = '';
            $content = '';
            $request = $_REQUEST;

            if(!empty($request) && ($request['page'] == "arm_gm_membership"))
            {
                if (is_rtl()) 
                {
                    $pageWrapperClass = 'arm_page_rtl';
                }
                echo '<div class="arm_page_wrapper '.esc_attr($pageWrapperClass).'" id="arm_page_wrapper">';

                $arm_admin_notice = '';
                echo apply_filters('arm_admin_notice',$arm_admin_notice);  //phpcs:ignore

                $ARMember->arm_admin_messages_init();

                switch($request['page']) 
                {
                    case 'arm_gm_membership':
                        if( file_exists( ARM_GROUP_MEMBERSHIP_VIEWS_DIR . '/admin/arm_gm_members_listing.php' ) ) 
                        {
                            if(!empty($request['action']) && $request['action'] == "new")
                            {
                                ob_start();
                                require(ARM_GROUP_MEMBERSHIP_VIEWS_DIR.'/admin/arm_gm_add_group_membership.php');
                                $content = ob_get_clean();
                            }
                            else
                            {
                                ob_start();
                                require(ARM_GROUP_MEMBERSHIP_VIEWS_DIR . '/admin/arm_gm_members_listing.php');
                                $content = ob_get_clean();
                            }
                        }
                        break;
                }

                echo $content.'</div>'; //phpcs:ignore
            }
        }
        function armgm_license_scripts(){
            global $arm_gm_version, $arm_version;
            wp_register_script('arm-gm-license', ARM_GROUP_MEMBERSHIP_URL . '/js/armgm_license.js', array(), $arm_gm_version);
            if( isset($_REQUEST['page']) && $_REQUEST['page'] == 'arm_manage_license')
            {
                wp_enqueue_script( 'arm-gm-license');
                global $wp_styles;
                $srcs = array_map('basename', (array) wp_list_pluck($wp_styles->registered, 'src') );
                if (!in_array('arm_gm_admin.css', $srcs)) {
                    wp_register_style( 'arm-gm-admin-css', ARM_GROUP_MEMBERSHIP_URL . '/css/arm_gm_admin.css', array(), $arm_gm_version );
                }
                wp_enqueue_style( 'arm-gm-admin-css' );
            }
        }
        function arm_gm_scripts()
        {
            global $arm_gm_version, $arm_global_settings, $arm_version;
            
            wp_register_style('arm-gm-admin-css', ARM_GROUP_MEMBERSHIP_CSS_URL . '/arm_gm_admin.css', array(), $arm_gm_version );
            wp_register_script('arm-gm-admin-js', ARM_GROUP_MEMBERSHIP_JS_URL . '/arm_gm_admin.js', array(), $arm_gm_version);
            


            $arm_version_compatible = (version_compare($arm_version, '4.1', '>=')) ? 1 : 0;

            if($arm_version_compatible)
            {
                wp_enqueue_style('datatables');
            }

            if(!empty($_REQUEST['page']) && ($_REQUEST['page'] == "arm_gm_membership" || $_REQUEST['page'] == "arm_gm_add_group_membership" ||  $_REQUEST['page'] == "arm_manage_plans"))
            {
                wp_enqueue_style('arm-gm-admin-css');
                wp_enqueue_script('arm-gm-admin-js');
                wp_enqueue_script( 'jquery' );
                wp_enqueue_style( 'armlite_admin_css' );
                wp_enqueue_style( 'arm_admin_css' );
                wp_enqueue_style( 'arm_lite_admin_model_css' );
                wp_enqueue_style( 'arm_admin_model_css' );
                wp_enqueue_style( 'arm-font-awesome-css' );
                wp_enqueue_style( 'arm_form_style_css' );
                wp_enqueue_style( 'arm_chosen_selectbox' );
                wp_enqueue_script( 'arm_chosen_jq_min' );
                wp_enqueue_script( 'arm_tipso' );
                wp_enqueue_script( 'arm_icheck-js' );
                wp_enqueue_script( 'arm_bpopup' );
                wp_enqueue_script( 'arm_validate' );
                

                wp_enqueue_script('arm_bootstrap_datepicker_with_locale');

                wp_register_style('arm_bootstrap_all_css', MEMBERSHIPLITE_URL . '/bootstrap/css/bootstrap_all.css', array(), MEMBERSHIP_VERSION);
                wp_enqueue_style('arm_bootstrap_all_css');

                if($arm_version_compatible)
                {
                    wp_enqueue_script('datatables');
                    wp_enqueue_script('buttons-colvis');
                    wp_enqueue_script('fixedcolumns');
                    wp_enqueue_script('fourbutton');
                }
                else
                {
                    wp_enqueue_script( 'jquery_dataTables', MEMBERSHIPLITE_URL . '/datatables/media/js/jquery.dataTables.js', array(), $arm_gm_version );
                    wp_enqueue_script( 'FourButton', MEMBERSHIPLITE_URL . '/datatables/media/js/four_button.js', array(), $arm_gm_version );
                    wp_enqueue_script( 'FixedColumns', MEMBERSHIPLITE_URL . '/datatables/media/js/FixedColumns.js', array(), $arm_gm_version );
                }

            }

            echo '<script type="text/javascript" data-cfasync="false">';
            echo 'var __Users_Del_Success_Msg = "'.esc_html__("Users Deleted Successfully", 'ARMGroupMembership').'";';
            echo 'var __Data_Update = "'.esc_html__("Data Updated Successfully", 'ARMGroupMembership').'";';
            echo 'var __Sub_User_Added = "'.esc_html__("Sub User Added Successfully", 'ARMGroupMembership').'";';
            echo '</script>';
        }

        function arm_gm_front_scripts()
        {
            global $arm_gm_version, $ARMember, $arm_global_settings,$ARMember;
            
			$isEnqueueAll = $arm_global_settings->arm_get_single_global_settings('enqueue_all_js_css', 0);
			if( $ARMember->is_arm_front_page() || $isEnqueueAll=='1' )
			{
				//if( wp_script_is('arm_angular_with_material') && wp_script_is('arm_form_angular') )
				//{
					wp_register_style('arm-gm-front-css', ARM_GROUP_MEMBERSHIP_CSS_URL . '/arm_gm_front.css', array(), $arm_gm_version );

					wp_enqueue_style( 'arm-gm-front-css' );
					wp_enqueue_style( 'arm_form_style_css' );
					wp_enqueue_style( 'arm-fontawesome-css' );
					
                    wp_enqueue_script('jquery');
                    wp_register_script('arm-gm-front-js', ARM_GROUP_MEMBERSHIP_JS_URL . '/arm_gm_front.js', array('jquery', 'arm_angular_with_material'), $arm_gm_version);
                    $ARMember->set_front_css(true);
                    $ARMember->set_front_js(true);
                    $ARMember->enqueue_angular_script();
                    $global_var = $ARMember->set_global_javascript_variables();
					wp_enqueue_script('arm-gm-front-js');

                    wp_add_inline_script( 'arm-gm-front-js', $global_var);

					$arm_gm_get_plugin_version = get_option('arm_gm_version');
					/*if (isset($arm_gm_get_plugin_version) || $arm_gm_get_plugin_version != '') 
					{*/
						echo '<script type="text/javascript" data-cfasync="false">';
						echo 'var __ARMGM = true;';
						echo 'var __CodeRefreshText = "'.esc_html__("Are you sure you want to re-generate invite code?", "ARMGroupMembership").'";';
						echo 'var __ChildDeleteText = "'.esc_html__("Are you sure you want to delete child user?", "ARMGroupMembership").'";';
						echo 'var __CodeResendText = "'.esc_html__("Are you sure you want to resend invite code?", "ARMGroupMembership").'";';
						echo '</script>';
					//}
				//}
			}
        }

        public static function arm_gm_install() {
            global $arm_gm_version, $wpdb, $ARMember, $is_multiple_membership_feature, $arm_gm_class;

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            if($is_multiple_membership_feature->isMultipleMembershipFeature)
            {
                if(is_plugin_active(ARM_GROUP_MEMBERSHIP_DIR_NAME.'/'.ARM_GROUP_MEMBERSHIP_DIR_NAME.'.php')){
                    deactivate_plugins(ARM_GROUP_MEMBERSHIP_DIR_NAME.'/'.ARM_GROUP_MEMBERSHIP_DIR_NAME.'.php');
                }
                $arm_gm_err_msg = esc_html__("ARMember Multiple Membership module is Activated. So Group Membership can't Activate.", 'ARMGroupMembership');
                if(!empty($_POST['action'])) //phpcs:ignore
                {
                    $response = array('type' => 'error', 'msg' => $arm_gm_err_msg);
                    echo json_encode($response);
                    die();
                }
                else
                {
                    wp_die($arm_gm_err_msg, "Plugin Activation Error", array("response" => 500,"back_link" => true));//phpcs:ignore
                }
            }

            $isColumnExist = false;
            $arm_gm_table_fields = $wpdb->get_results("DESCRIBE $ARMember->tbl_arm_coupons"); //phpcs:ignore
            foreach($arm_gm_table_fields as $arm_gm_tbl_keys => $arm_gm_tbl_val)
            {
                if(!empty($arm_gm_tbl_val->Field) && $arm_gm_tbl_val->Field == "arm_group_parent_user_id")
                {
                    $isColumnExist = true;
                }
            }

            if(!$isColumnExist)
            {
                $wpdb->query("ALTER TABLE $ARMember->tbl_arm_coupons ADD arm_group_parent_user_id INT(11)"); //phpcs:ignore
            }


            $charset_collate = '';
            if ($wpdb->has_cap('collation')) {
                if (!empty($wpdb->charset)):
                    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
                endif;

                if (!empty($wpdb->collate)):
                    $charset_collate .= " COLLATE $wpdb->collate";
                endif;
            }
            $tbl_arm_gm_child_users_status = $wpdb->prefix . 'arm_gm_child_users_status';
            $create_tbl_arm_gm_child_users_status = "CREATE TABLE IF NOT EXISTS `{$tbl_arm_gm_child_users_status}` (
                arm_gm_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                arm_gm_email_id varchar(255) NULL,
                arm_gm_user_id bigint(20) NULL,
                arm_gm_parent_user_id bigint(20) NULL,
                arm_gm_status int(1) NOT NULL DEFAULT 0,
                arm_gm_invite_code_id bigint(20) NULL,
                arm_gm_added_date datetime NULL,
                arm_gm_updated_date datetime NULL
            ) {$charset_collate};";
            dbDelta( $create_tbl_arm_gm_child_users_status );

            $arm_gm_get_plugin_version = get_option('arm_gm_version');
            if (!isset($arm_gm_get_plugin_version) || $arm_gm_get_plugin_version == '') {
                update_option('arm_gm_version', $arm_gm_version);
            }

            $arm_gm_class->arm_gm_capabilities_assign();
        }

        function arm_gm_addon_activated($plugin,$network_activation)
        {
            global $armember_check_plugin_copy, $arm_check_addon_copy_flag;
            $myaddon_name = "armembergroupmembership/armembergroupmembership.php";

            if($plugin == $myaddon_name && empty($arm_check_addon_copy_flag))
            {

                if(!(is_plugin_active('armember/armember.php')))
                {
                    deactivate_plugins($myaddon_name, FALSE);
                    $redirect_url = network_admin_url('plugins.php?deactivate=true&arm_license_deactivate=true&arm_deactivate_plugin='.$myaddon_name);
                    $armpa_dact_message = __('Please activate license of ARMember premium plugin to use ARMember - Group/Umbrella Membership Addon', 'ARMGroupMembership');
                    /* translators: 1. Redirect URL link starts 2.Redirect URL link ends */
                    $arm_link = sprintf( __('Please %s Click Here %s to Continue', 'ARMGroupMembership'), '<a href="javascript:void(0)" onclick="window.location.href=\'' . $redirect_url . '\'">', '</a>');
                    wp_die('<p>'.$armpa_dact_message.'<br/>'.$arm_link.'</p>'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - Text is escaped properly
                    die;
                }

                $license = trim( get_option( 'arm_pkg_key' ) );

                if($armember_check_plugin_copy == 1 && ('' === $license || false === $license )) 
                {
                    deactivate_plugins($myaddon_name, FALSE);
                    $redirect_url = network_admin_url('plugins.php?deactivate=true&arm_license_deactivate=true&arm_deactivate_plugin='.$myaddon_name);
                    $armpa_dact_message = __('Please activate license of ARMember premium plugin to use ARMember - Group/Umbrella Membership Addon', 'ARMGroupMembership');
                    /* translators: 1. Redirect URL link starts 2.Redirect URL link ends */
                    $arm_link = sprintf( __('Please %s Click Here %s to Continue', 'ARMGroupMembership'), '<a href="javascript:void(0)" onclick="window.location.href=\'' . $redirect_url . '\'">', '</a>');
                    wp_die('<p>'.$armpa_dact_message.'<br/>'.$arm_link.'</p>'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - Text is escaped properly
                    die;
                }
                else
                {
                    
                    if(empty($armember_check_plugin_copy)){
                        
                        global $wp_version, $arm_members_activity;
                        $lidata = array();
                        
                        $get_purchased_info = get_option('armSortInfo');
                        $cust_name = '';
                        $pcodecustemail = "";
                        $arm_pkg_key = '';
                        if(!empty($get_purchased_info)){
                            $sortorderval = base64_decode($get_purchased_info);

                            $ordering = explode("^", $sortorderval);

                            if (is_array($ordering)) {
                                if (isset($ordering[0]) && $ordering[0] != "") {
                                    $arm_pkg_key = $ordering[0];
                                } else {
                                    $arm_pkg_key = "";
                                }
                                if (isset($ordering[4]) && $ordering[4] != "") {
                                    $pcodecustemail = $ordering[4];
                                } else {
                                    $pcodecustemail = "";
                                }
                            }
                        }    

                        $lidata[] = !empty($cust_name) ? $cust_name : ' '; //phpcs:ignore
                        $lidata[] = $pcodecustemail; //phpcs:ignore
                        $lidata[] = $arm_pkg_key; //phpcs:ignore
                        $lidata[] = $_SERVER["SERVER_NAME"]; //phpcs:ignore

                        $pluginuniquecode = $arm_members_activity->generateplugincode();
                        $lidata[] = $pluginuniquecode;
                        $lidata[] = MEMBERSHIP_URL;//phpcs:ignore
                        $lidata[] = get_option("arm_version");
                        $lidata[] = 0; //phpcs:ignore

                        $valstring = implode("||", $lidata);
                        $encodedval = base64_encode($valstring);

                        $urltopost = "https://www.reputeinfosystems.com/tf/plugins/armember/verify/lic_act_arm.php";

                        $response = wp_remote_post($urltopost, array(
                            'method' => 'POST',
                            'timeout' => 45,
                            'redirection' => 5,
                            'httpversion' => '1.0',
                            'blocking' => true,
                            'headers' => array(),
                            'body' => array('verifypurchase' => $encodedval),
                            'user-agent' => 'ARM-WordPress/' . $wp_version . '; ' . ARM_HOME_URL,
                            'cookies' => array()
                                )
                        );
                        
                        if(is_wp_error($response)) 
                        {
                            $urltopost = "http://www.reputeinfosystems.com/tf/plugins/armember/verify/lic_act_arm.php";

                            $response = wp_remote_post($urltopost, array(
                                'method' => 'POST',
                                'timeout' => 45,
                                'redirection' => 5,
                                'httpversion' => '1.0',
                                'blocking' => true,
                                'headers' => array(),
                                'body' => array('verifypurchase' => $encodedval),
                                'user-agent' => 'ARM-WordPress/' . $wp_version . '; ' . ARM_HOME_URL,
                                'cookies' => array()
                                    )
                            );
                        }

                        if (array_key_exists('body', $response) && isset($response["body"]) && $response["body"] != "")
                            $responsemsg = $response["body"];
                        else
                            $responsemsg = "";

                        $checklic = 0;
                        if ($responsemsg != "") {
                            $responsemsg = explode("|^|", $responsemsg);
                            if (is_array($responsemsg) && count($responsemsg) > 0) {

                                if (isset($responsemsg[0]) && $responsemsg[0] != "") {
                                    $msg = $responsemsg[0];
                                } else {
                                    $msg = "";
                                }
                                if (isset($responsemsg[1]) && $responsemsg[1] != "") {
                                    $code = $responsemsg[1];
                                } else {
                                    $code = "";
                                }
                                if (isset($responsemsg[2]) && $responsemsg[2] != "") {
                                    $info = $responsemsg[2];
                                } else {
                                    $info = "";
                                }

                                if ($msg == "1") {
                                    $checklic = $arm_members_activity->checksoringcode($code, $info);                                    
                                }
                            } 
                        }
                        if ($checklic != "1") {
                            deactivate_plugins($myaddon_name, FALSE);
                            $redirect_url = network_admin_url('plugins.php?deactivate=true&arm_license_deactivate=true&arm_deactivate_plugin='.$myaddon_name);
                            $armpa_dact_message = __('Please activate license of ARMember premium plugin to use ARMember - Group/Umbrella Membership Addon', 'ARMGroupMembership');
                            /* translators: 1. Redirect URL link starts 2.Redirect URL link ends */
                            $arm_link = sprintf( __('Please %s Click Here %s to Continue', 'ARMGroupMembership'), '<a href="javascript:void(0)" onclick="window.location.href=\'' . $redirect_url . '\'">', '</a>');
                            wp_die('<p>'.$armpa_dact_message.'<br/>'.$arm_link.'</p>'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - Text is escaped properly
                            die;
                        }
                    }else{

                        $package = trim( get_option( 'arm_pkg' ) );
                        $store_url = ARMADDON_STORE_URL;
                        $api_params = array(
                            'edd_action' => 'check_license',
                            'license' => $license,
                            'item_id'  => $package,
                            //'item_name' => urlencode( $item_name ),
                            'url' => home_url()
                        );
                        $response = wp_remote_post( $store_url, array( 'body' => $api_params, 'timeout' => 15, 'sslverify' => false ) );
                        if ( is_wp_error( $response ) ) {
                            return false;
                        }
            
                        $license_data = json_decode( wp_remote_retrieve_body( $response ) );
                        $license_data_string =  wp_remote_retrieve_body( $response );
            
                        $message = '';

                        if ( true === $license_data->success ) 
                        {
                            if($license_data->license != "valid")
                            {
                                deactivate_plugins($myaddon_name, FALSE);
                                $redirect_url = network_admin_url('plugins.php?deactivate=true&arm_license_deactivate=true&arm_deactivate_plugin='.$myaddon_name);
                                $armpa_dact_message = __('Please activate license of ARMember premium plugin to use ARMember - Group/Umbrella Membership Addon', 'ARMGroupMembership');
                                /* translators: 1. Redirect URL link starts 2.Redirect URL link ends */
                                $arm_link = sprintf( __('Please %s Click Here %s to Continue', 'ARMGroupMembership'), '<a href="javascript:void(0)" onclick="window.location.href=\'' . $redirect_url . '\'">', '</a>');
                                wp_die('<p>'.$armpa_dact_message.'<br/>'.$arm_link.'</p>'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - Text is escaped properly
                                die;
                            }

                        }
                        else
                        {
                            deactivate_plugins($myaddon_name, FALSE);
                            $redirect_url = network_admin_url('plugins.php?deactivate=true&arm_license_deactivate=true&arm_deactivate_plugin='.$myaddon_name);
                            $armpa_dact_message = __('Please activate license of ARMember premium plugin to use ARMember - Group/Umbrella Membership Addon', 'ARMGroupMembership');
                            /* translators: 1. Redirect URL link starts 2.Redirect URL link ends */
                            $arm_link = sprintf( __('Please %s Click Here %s to Continue', 'ARMGroupMembership'), '<a href="javascript:void(0)" onclick="window.location.href=\'' . $redirect_url . '\'">', '</a>');
                            wp_die('<p>'.$armpa_dact_message.'<br/>'.$arm_link.'</p>'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - Text is escaped properly
                            die;
                        }
                    }    
                }
            }

        }

        function arm_gm_capabilities_assign()
        {
            $arm_group_membership_cap = array('arm_gm_membership');
            $args = array(
                'role' => 'administrator',
                'fields' => 'id'
            );
            $arm_gm_admin_users = get_users($args);
            if (count($arm_gm_admin_users) > 0) {
                foreach ($arm_gm_admin_users as $arm_gm_key => $arm_gm_user_id) {
                    $userObj = new WP_User($arm_gm_user_id);
                    foreach ($arm_group_membership_cap as $arm_gm_role) {
                        $userObj->add_cap($arm_gm_role);
                    }
                }
                unset($arm_gm_role);
                unset($arm_group_membership_cap);
            }
        }

        /*
         * Restrict Network Activation
         */
        public static function arm_gm_check_network_activation($network_wide) {
            if (!$network_wide)
                return;

            deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

            header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
            exit;
        }

        function arm_add_capabilities_to_new_user($user_id)
        {
            if ($user_id == '') {
                return;
            }
            if (user_can($user_id, 'administrator')) {
                $arm_community_capabilities = array('arm_gm_membership');
                $userObj = new WP_User($user_id);
                foreach ($arm_community_capabilities as $armcomrole) {
                    $userObj->add_cap($armcomrole);
                }
                unset($armcomrole);
                unset($arm_community_capabilities);
            }
        }


        public static function arm_gm_uninstall() {
            global $wpdb;
            $tbl_arm_gm_child_users_status = $wpdb->prefix . 'arm_gm_child_users_status';
            $wpdb->query("DROP TABLE IF EXISTS ".$tbl_arm_gm_child_users_status); //phpcs:ignore
            delete_option('arm_gm_version');
        }

        public static function arm_gm_db_check() {
            global $arm_gm_class;
            $arm_gm_get_plugin_version = get_option('arm_gm_version');

            if (!isset($arm_gm_get_plugin_version) || $arm_gm_get_plugin_version == '') {
                $arm_gm_class->arm_gm_install();
            }
        }

        public static function arm_gm_load_textdomain() {
            load_plugin_textdomain('ARMGroupMembership', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }
		
        static function arm_gm_get_armember_version() {
            $arm_gm_armemberplugin_version = get_option('arm_version');
            return isset($arm_gm_armemberplugin_version) ? $arm_gm_armemberplugin_version : 0;
        }

        static function arm_gm_is_armember_plugin_installed() {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
            return is_plugin_active('armember/armember.php');
        }

        function arm_gm_is_plugin_version_comatible() {
            global $arm_gm_class;
            if ($this->arm_gm_is_armember_plugin_installed() && version_compare($this->arm_gm_get_armember_version(), '4.3', '>=')) {
                return true;
            } else {
                return false;
            }
        }
        function arm_gm_is_compatible_with_5_2() {
            if (!version_compare($this->arm_gm_get_armember_version(), '5.2', '>=') || !$this->arm_gm_is_armember_plugin_installed()) :
                return false;
            else : 
                return true;
            endif;
        }
        function armgm_license_status(){
            $armgm_license_status=get_option('armgm_license_status');
            return ($armgm_license_status=='valid')?true:false;
        }
        function arm_gmtive_campaign_admin_notices($arm_license_notice = '') {
            global $pagenow, $arm_slugs, $arm_gm_class;    
            if((isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){

                $armgm_license_status=get_option('armgm_license_status');
                $armgm_license_key=get_option('armgm_license_key');
                $armgm_page_slug=(isset($_REQUEST['page']))?sanitize_text_field($_REQUEST['page']):'';
                if(($armgm_license_status!='valid' || empty($armgm_license_key)) && $armgm_page_slug!='arm_manage_license' && $arm_gm_class->arm_gm_is_armember_plugin_installed()){
                    if($arm_gm_class->armgm_licence_notice_status()){
                        
                        $nonce = wp_create_nonce('arm_wp_nonce');

                        $admin_doc_license_url = "https://www.armemberplugin.com/documents/activate-armember-addon-license/";
                        $arm_license_notice .= sprintf("<div class='armember_notice_warning armgm_licence_notice_warning'>" . esc_html__("Please activate the ARMember - Group/Umbrella Membership Addon License from ARMember ➝ Licensing page to get future updates. For more information, %1\$s click here %2\$s.", 'ARMGroupMembership') . "<span class='armember_close_licence_notice_icon' id='armember_close_licence_notice_icon' data-nonce='".$nonce."' data-type='armgm' title='" . esc_html__('Dismiss for 7 days', 'ARMGroupMembership') . "'></span></div>","<a href='".esc_url( $admin_doc_license_url )."' target='_blank'>",'</a>');
                    }    
                }else if (!$arm_gm_class->arm_gm_is_armember_plugin_installed()) {
                    $arm_license_notice .= "<div class='armember_notice_warning blue'>" . esc_html__('ARMember - Group Membership plugin requires ARMember Plugin installed and active', 'ARMGroupMembership') . "</div>";
                } else if (!$arm_gm_class->arm_gm_is_plugin_version_comatible()) {
                    $arm_license_notice .= "<div class='armember_notice_warning blue'>" . esc_html__('ARMember - Group Membership plugin requires ARMember plugin installed with version 4.3 or higher.', 'ARMGroupMembership') . "</div>";
                }
            }
            return $arm_license_notice;
        }

        function arm_version_check_admin_notices_func(){
            global $arm_version;
            if(!empty($arm_version) && version_compare($arm_version , "7.0","<"))
            {
                $arm_license_notice = '<div class="error arm_admin_notices_container"><p><strong>Warning:</strong>&nbsp;It seems that you MUST update to ARMember pro to the latest version 7.0 to get new UI changes. If you do not update, some design changes can break the admin panel form.</p></div>';
                echo $arm_license_notice; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - Text is escaped properly
            }
        }

        function arm_gm_messages() {
            $alertMessages = array(
                'delOptInsConfirm' => __("Are you sure to delete configuration?", 'ARMGroupMembership'),
            );
            return $alertMessages;
        }


        function arm_gm_deactivate_plugin($post_data)
        {
            if(!empty($post_data['arm_features_options']) && $post_data['arm_features_options'] == "arm_is_group_membership_feature")
            {
                $response = array('type' => 'wocommerce_error', 'msg' => __("One or more users have Group Membership, so addon can't be deactivated.", 'ARMGroupMembership'));
                echo json_encode($response);
                die();
            }
        }


        function armgm_get_remote_post_params($plugin_info = "") {
            global $wpdb;
    
            $action = "";
            $action = $plugin_info;
    
            if (!function_exists('get_plugins')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            $plugin_list = get_plugins();
            $site_url = home_url();
            $plugins = array();
    
            $active_plugins = get_option('active_plugins');
    
            foreach ($plugin_list as $key => $plugin) {
                $is_active = in_array($key, $active_plugins);
    
                //filter for only armember ones, may get some others if using our naming convention
                if (strpos(strtolower($plugin["Title"]), "armembergroupmembership") !== false) {
                    $name = substr($key, 0, strpos($key, "/"));
                    $plugins[] = array("name" => $name, "version" => $plugin["Version"], "is_active" => $is_active);
                }
            }
            $plugins = json_encode($plugins);
    
            //get theme info
            $theme = wp_get_theme();
            $theme_name = $theme->get("Name");
            $theme_uri = $theme->get("ThemeURI");
            $theme_version = $theme->get("Version");
            $theme_author = $theme->get("Author");
            $theme_author_uri = $theme->get("AuthorURI");
    
            $im = is_multisite();
            $sortorder = get_option("armSortOrder");
    
            $post = array("wp" => get_bloginfo("version"), "php" => phpversion(), "mysql" => $wpdb->db_version(), "plugins" => $plugins, "tn" => $theme_name, "tu" => $theme_uri, "tv" => $theme_version, "ta" => $theme_author, "tau" => $theme_author_uri, "im" => $im, "sortorder" => $sortorder);
    
            return $post;
        }

        function arm_gm_add_default_global_settings($default_global_settings)
        {
	    if(!empty($default_global_settings['page_settings']))
	    {
            	$default_global_settings['page_settings']['child_user_signup_page_id'] = 0;
	    }

            return $default_global_settings;
        }

        function armgm_dismiss_licence_notice_func(){

            if (  current_user_can( 'manage_options' )){
        
                $arm_wp_nonce = !empty( $_POST['_wpnonce'] ) ? sanitize_text_field( $_POST['_wpnonce'] ) : ''; //phpcs:ignore
                if(wp_verify_nonce( $arm_wp_nonce, 'arm_wp_nonce' )){
        
                    $armgm_licence_dismiss_date = strtotime('+7 days',current_time( 'timestamp'));			
                    update_option('armgm_licence_dismiss_notice',$armgm_licence_dismiss_date);
        
                    echo json_encode( array( 'status' => 'success' ) );
                }
                else{
                    echo json_encode( array( 'status' => 'error', 'message' => esc_html__('Security check failed, please try again.', 'ARMGroupMembership') ) );
                }
                
            }else{
                echo json_encode( array( 'status' => 'error', 'message' => esc_html__('You do not have sufficient permissions to access this page.', 'ARMGroupMembership') ) );
            }
            exit;
        }
        function armgm_licence_notice_status(){
            $armgm_licence_dismiss_notice = get_option('armgm_licence_dismiss_notice');
                
            $arm_licence_display_notice = 1;
            if(!empty($armgm_licence_dismiss_notice)) {
                $current_time = current_time('timestamp');
                if($current_time < $armgm_licence_dismiss_notice ) {
                    $arm_licence_display_notice = 0;
                }
            }
            return $arm_licence_display_notice;
        }
    }

}

if(!function_exists('is_plugin_active'))
{
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}
$arm_gm_active_value = is_plugin_active('armembergroupmembership/armembergroupmembership.php') ? true : false;
$arm_gm_class = new ARM_GroupMembership($arm_gm_active_value);

$armgm_license_status=$arm_gm_class->armgm_license_status();
if(!empty($armgm_license_status)){
    if (file_exists(ARM_GROUP_MEMBERSHIP_CLASSES_DIR . '/class.arm_group_membership.php') && $arm_gm_class->arm_gm_is_plugin_version_comatible()) {
        require_once(ARM_GROUP_MEMBERSHIP_CLASSES_DIR . '/class.arm_group_membership.php');
    }

    if (file_exists(ARM_GROUP_MEMBERSHIP_CLASSES_DIR . '/class.arm_group_membership_child_signup.php') && $arm_gm_class->arm_gm_is_plugin_version_comatible()) {
        require_once(ARM_GROUP_MEMBERSHIP_CLASSES_DIR . '/class.arm_group_membership_child_signup.php');
    }
}    

add_filter('arm_addon_license_content_external', 'armgm_addon_license_content_external', 10, 1);

function armgm_addon_license_content_external($arm_license_addon_content){
    $armgm_license_status=get_option('armgm_license_status');
    $armgm_license_key=get_option('armgm_license_key');
    $arm_license_deactive=0;
    $arm_license_active_style='display:flex';
    $arm_license_deactive_style='display:none;';
    if($armgm_license_status=='valid' && !empty($armgm_license_key)){
        $arm_license_deactive=1;
        $arm_license_active_style='display:none';
        $arm_license_deactive_style='display:flex';        
    }
    $arm_license_addon_content .='<form method="post" action="#" id="armgm_license_settings" class="arm_license_settings arm_admin_form" onsubmit="return false;">
        <div class="arm_feature_settings_container arm_feature_settings_wrapper">
            <div class="page_sub_title">'.__('Activate Group/Umbrella Membership Addon','ARMGroupMembership').'</div>
            <div class="form-field">
                <div class="arm-form-table-content">';

                    $arm_license_addon_content .='<div class="arm_addon_license_field_wrapper armgm_add_license_section" style="'.$arm_license_active_style.'"><input type="text" name="arm_license_key" id="arm_license_key" placeholder="Enter License Code" value="'.$armgm_license_key.'" autocomplete="off" /><input type="hidden" name="arm_license_deactive" id="arm_license_deactive" value="'.$arm_license_deactive.'" /><span id="license_btn_section"><span id="license_loader" style="display:none;"><img src="'.MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif" height="24" class="arm_license_activation_loader" />&nbsp;</span><span id="license_link" class="armgm_add_license_section" style="'.$arm_license_active_style.'"><button type="button" id="armgm-verify-purchase-code" name="armgm_activate_license" class="arm_activate_license_btn greensavebtn">'.__('Activate License', 'ARMGroupMembership').'</button></span></span></div>'; 
                                                                
                    $arm_license_addon_content .='<div class="arm_addon_license_remove_wrapper armgm_remove_license_section" style="'.$arm_license_deactive_style.'">';
                    $arm_license_addon_content .='<div class="arm_addon_license_success_message">';
                    $arm_license_addon_content .='<span class="arm_license_remove_message_text"><span class="arm_license_remove_message_icon"><img src="'.MEMBERSHIPLITE_IMAGES_URL.'/arm_activeted_license_icon.svg" class="arm_license_activation_loader" /></span>'.__('Your license is currently Active.', 'ARMGroupMembership').'</span>';
                    $arm_license_addon_content .='<span class="arm_license_purchase_code"><strong>'.__('Purchase Code:', 'ARMGroupMembership').'</strong>'.$armgm_license_key.'</span>';
                    $arm_license_addon_content .='</div>';
                    $arm_license_addon_content .='<span id="license_btn_section"><span id="license_loader" style="display:none;"><img src="'.MEMBERSHIPLITE_IMAGES_URL.'/arm_loader.gif" height="24" class="arm_license_remove_loader" />&nbsp;</span><span id="license_link" class="armgm_remove_license_section" style="'.$arm_license_deactive_style.'"><button type="button" id="armgm-remove-verify-purchase-code" name="armgm_remove_license" class="arm_remove_license_btn red_remove_license_btn">'.__('Remove License', 'ARMGroupMembership').'</button></span></span>';
                    $arm_license_addon_content .='</div>';

                    $arm_license_addon_content .='<div class="arperrmessage" id="arm_license_key_error" style="display:none;">'.__('Please enter your license code.', 'ARMGroupMembership').'</div>
                    <span id="license_error" style="display:none;">&nbsp;</span>
                    <span id="license_reset" style="display:none;">&nbsp;&nbsp;<a onclick="javascript:return false;" href="#">Click here to submit RESET request</a></span>
                    <span id="license_success" style="display:none;">'.__('License Activated Successfully.', 'ARMGroupMembership').'</span>';

                $arm_license_addon_content .='</div>
            </div>
        </div>
    </form>
    <div class="armclear"></div>';

    global $arm_global_settings;
    /* **********./Begin remove License Popup/.********** */
    $arm_remove_license_popup_content = '<span class="arm_confirm_text">'.__("Are you sure you want to Remove this License?",'ARMGroupMembership' ).'<br>'.__("This will disable all features of the add-on and stop access to related updates or support. You can reactivate anytime with your license key.",'ARMGroupMembership' ). '</span>';
    $arm_remove_license_popup_content .= '<input type="hidden" value="" id="armgm_remove_license_flag"/>';
    $arm_remove_license_popup_title = '<span class="arm_confirm_text">'.__('Remove License', 'ARMGroupMembership').'</span>';		
    
    $arm_remove_license_popup_arg = array(
        'id' => 'armgm_remove_license_form_message',
        'class' => 'arm_addons_remove_license_from_message armgm_remove_license_form_message',
        'title' => $arm_remove_license_popup_title,
        'content' => $arm_remove_license_popup_content,
        'button_id' => 'arm_addons_remove_license_ok_btn armgm_remove_license_ok_btn',
        'button_onclick' => "armgm_deactivate_license();",
    );
    $arm_license_addon_content .=$arm_global_settings->arm_get_bpopup_html($arm_remove_license_popup_arg);
    /* **********./End remove License Popup/.********** */
		
    return $arm_license_addon_content;
}

add_action('wp_ajax_armgmactivatelicense','armgm_license_update');

function armgm_license_update(){
    global $wp, $wpdb, $ARMember, $arm_capabilities_global,$arm_slugs;
    
    $response = array('type' => 'error', 'msg' => __('Sorry, Something went wrong. Please try again.', 'ARMGroupMembership'),'arm_license_status'=>0);
    
    /*check the license page capabilities to user */
    $arm_manage_license_key = !isset(($arm_capabilities_global['arm_manage_license']))?$arm_slugs->licensing:$arm_capabilities_global['arm_manage_license'];
    $ARMember->arm_check_user_cap($arm_manage_license_key, '1'. '1');

    if(isset($_POST['arm_license_key']) && !empty($_POST['arm_license_key'])){ //phpcs:ignore
        $arm_license_deactive=(isset($_POST['arm_license_deactive']))?$_POST['arm_license_deactive']:'0'; //phpcs:ignore
        // activate license for this addon
        $posted_license_key = trim($_POST['arm_license_key']); //phpcs:ignore
        $posted_license_package = '10097';
        $edd_action='activate_license';
        if($arm_license_deactive=='1'){
            $edd_action='deactivate_license';
            $posted_license_key=get_option('armgm_license_key');
        }

        $api_params = array(
            'edd_action' => $edd_action,
            'license'    => $posted_license_key,
            'item_id'  => $posted_license_package,
            // 'url'        => home_url()
        );

        if($edd_action!='deactivate_license'){
            $api_params['url'] = home_url();
        }else {
            $verify_package = 0;
            $verify_package = apply_filters( 'arm_verify_license_package', $verify_package, $posted_license_package );

            if( $verify_package ) {

                delete_option('armgm_license_key');
                delete_option('armgm_license_package');
                delete_option('armgm_license_status');
                delete_option('armgm_license_data_activate_response');
                delete_option('armgm_licence_dismiss_notice');
                
                $message = __('License Deactivated Successfully.', 'ARMGroupMembership');
                $response_return['type']='success';
                $response_return['msg']=$message;
                $response_return['arm_license_status']=0;
                
                echo json_encode( $response_return );
                die();
            }
        }

        // Call the custom API.
        $response = wp_remote_post( ARMADDON_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
        $response_return = array();
        $message = "";
        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            $message =  ( is_wp_error( $response ) && ! empty( $response->get_error_message() ) ) ? $response->get_error_message() : __( 'An error occurred, please try again.', 'ARMGroupMembership' );
            $response_return['msg']=$message;
            $response_return['arm_license_status']=0;
        } else {
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );
            
            $license_data_string = wp_remote_retrieve_body( $response );
            if ( false === $license_data->success && ! empty( $license_data->error ) ) {
                switch( $license_data->error ) {
                    case 'expired' :
                        $message = sprintf(
                            __( "Your license key expired on %1\$s.", 'ARMGroupMembership'),
                            date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
                        );
                        break;
                    case 'revoked' :
                        $message = __( 'Your license key has been disabled.', 'ARMGroupMembership');
                        break;
                    case 'missing' :
                        $message = __( 'Invalid license.', 'ARMGroupMembership');
                        break;
                    case 'invalid' :
                    case 'site_inactive' :
                        $message = __( 'Your license is not active for this URL.', 'ARMGroupMembership');
                        break;
                    case 'item_name_mismatch' :
                        $message = __('This appears to be an invalid license key for your selected package.', 'ARMGroupMembership');
                        break;
                    case 'invalid_item_id' :
                            $message = __('This appears to be an invalid license key for your selected package.', 'ARMGroupMembership');
                            break;
                    case 'no_activations_left':
                        $message = __( 'Your license key has reached its activation limit.', 'ARMGroupMembership');
                        break;
                    default :
                        $message = __( 'An error occurred, please try again.', 'ARMGroupMembership');
                        break;
                }
                $response_return['msg']=$message;
                $response_return['arm_license_status']=0;

            }else if($license_data->license === "valid"){
                update_option('armgm_license_key', $posted_license_key );
                update_option('armgm_license_package', $posted_license_package );
                update_option('armgm_license_status', $license_data->license );
                update_option('armgm_license_data_activate_response', $license_data_string );

                $message = esc_html__('License Activated Successfully.', 'ARMGroupMembership');
                $response_return['type']='success';
                $response_return['msg']=$message;
                $response_return['arm_license_status']=1;
                $response_return['license_purchase_code_lbl'] = esc_html__('Purchase Code:', 'ARMGroupMembership');
            } else if( ( $license_data->license === "deactivated" || $license_data->license === "failed" ) && $arm_license_deactive=='1' ) {
                delete_option('armgm_license_key');
                delete_option('armgm_license_package');
                delete_option('armgm_license_status');
                delete_option('armgm_license_data_activate_response');
                delete_option('armgm_licence_dismiss_notice');
                
                $message = __('License Deactivated Successfully.', 'ARMGroupMembership');
                $response_return['type']='success';
                $response_return['msg']=$message;
                $response_return['arm_license_status']=0;
            }    

        }
        
    }    
    echo json_encode($response_return);
    die();
}

if ( ! class_exists( 'armember_pro_updater' ) ) {
	require_once ARM_GROUP_MEMBERSHIP_CLASSES_DIR . '/class.armember_pro_plugin_updater.php';
}

function armember_gm_plugin_updater() {
    global $arm_gm_version;

	$plugin_slug_for_update = 'armembergroupmembership/armembergroupmembership.php';

	// To support auto-updates, this needs to run during the wp_version_check cron job for privileged users.
	$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;
	if ( ! current_user_can( 'manage_options' ) && ! $doing_cron ) {
		return;
	}

	// retrieve our license key from the DB
	$license_key = trim( get_option( 'armgm_license_key' ) );
	$package = trim( get_option( 'armgm_license_package' ) );

	// setup the updater
	$edd_updater = new armember_pro_updater(
		ARMADDON_STORE_URL,
		$plugin_slug_for_update,
		array(
			'version' => $arm_gm_version,  // current version number
			'license' => $license_key,             // license key (used get_option above to retrieve from DB)
			'item_id' => $package,       // ID of the product
			'author'  => 'Repute Infosystems', // author of this plugin
			'beta'    => false,
		)
	);

}
add_action( 'init', 'armember_gm_plugin_updater' );