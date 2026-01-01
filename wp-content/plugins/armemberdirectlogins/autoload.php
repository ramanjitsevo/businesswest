<?php 
if (is_ssl()) {
    define('ARM_DIRECT_LOGINS_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_DIRECT_LOGINS_DIR_NAME));
} else {
    define('ARM_DIRECT_LOGINS_URL', WP_PLUGIN_URL . '/' . ARM_DIRECT_LOGINS_DIR_NAME);
}

define('ARM_DIRECT_LOGINS_TEXTDOMAIN','ARM_DIRECT_LOGINS');

define('ARM_DIRECT_LOGINS_CLASSES_DIR', ARM_DIRECT_LOGINS_DIR . '/core/classes/' );

define('ARM_DIRECT_LOGINS_VIEW_DIR', ARM_DIRECT_LOGINS_DIR . '/core/view/' );

define('ARM_DIRECT_LOGINS_IMAGES_URL', ARM_DIRECT_LOGINS_URL . '/images/' );

define('ARM_DIRECT_LOGINS_JS_URL', ARM_DIRECT_LOGINS_URL . '/js/');

define('ARM_DIRECT_LOGINS_CSS_URL', ARM_DIRECT_LOGINS_URL . '/css/');

if(!defined('ARMADDON_STORE_URL')){
    define( 'ARMADDON_STORE_URL', 'https://www.armemberplugin.com/');
}

global $arm_direct_logins_version;
$arm_direct_logins_version = '2.1';

global $armdirectlogin_api_url, $armdirectlogin_plugin_slug, $wp_version;

global $arm_direct_logins_newdbversion;

if (!class_exists('ARM_Direct_Logins'))
{
    class ARM_Direct_Logins
    {
        function __construct() {
            
            add_action( 'init', array( &$this, 'arm_direct_logins_db_check' ) );

            register_activation_hook(ARM_DIRECT_LOGINS_DIR.'/armemberdirectlogins.php', array('ARM_Direct_Logins', 'install'));

            register_activation_hook(ARM_DIRECT_LOGINS_DIR.'/armemberdirectlogins.php', array('ARM_Direct_Logins', 'arm_direct_logins_check_network_activation'));

            register_uninstall_hook(ARM_DIRECT_LOGINS_DIR.'/armemberdirectlogins.php', array('ARM_Direct_Logins', 'uninstall'));

            add_action('activated_plugin',array($this,'arm_is_direct_logins_addon_activated'),11,2);
            
            add_filter( 'arm_admin_notice', array( &$this, 'arm_admin_notices' ), 10, 1 );

            add_action('admin_notices', array(&$this, 'arm_version_check_admin_notices_func'));
            
            add_action( 'admin_init', array( &$this, 'arm_direct_logins_hide_update_notice' ), 1 );
            
            add_action( 'init', array( &$this, 'arm_direct_logins_load_textdomain' ) );
            
            if ($this->is_armember_compatible()){

                define( 'ARM_DIRECT_LOGINS_ARMEMBER_URL', MEMBERSHIPLITE_URL );
                
                add_action( 'admin_menu', array( &$this, 'arm_direct_logins_menu' ), 30 );
                
                add_action( 'admin_enqueue_scripts', array( &$this, 'arm_direct_logins_scripts' ), 20 );

                add_action( 'user_register', array( &$this, 'arm_direct_logins_add_capabilities_to_new_user' ));
            }
			
			add_action('admin_init', array(&$this, 'upgrade_data_directlogins'));

            add_filter('arm_page_slugs_modify_external',array($this,'arm_direct_login_page_slugs_modify_external_func'),10,1);
        }

        public function arm_direct_login_page_slugs_modify_external_func($arm_slugs){
            $arm_slugs->arm_direct_logins = 'arm_direct_logins';
            return $arm_slugs;
        }
        
        public static function arm_direct_logins_db_check() {
            global $arm_direct_logins; 
            $arm_direct_logins_version = get_option( 'arm_direct_logins_version' );

            if ( !isset( $arm_direct_logins_version ) || $arm_direct_logins_version == '' )
                $arm_direct_logins->install();
        }

        public static function install() {
            global $arm_direct_logins;
            $arm_direct_logins_version = get_option( 'arm_direct_logins_version' );

            if ( !isset( $arm_direct_logins_version ) || $arm_direct_logins_version == '' ) {

                global $wpdb, $arm_direct_logins_version;

                update_option( 'arm_direct_logins_version', $arm_direct_logins_version );
            }

            // give administrator users capabilities
            $args = array(
                'role' => 'administrator',
                'fields' => 'id'
            );
            $users = get_users($args);
            if (count($users) > 0) {
                foreach ($users as $key => $user_id) {
                    $armroles = array( 'arm_direct_logins' => __('Direct Login', 'ARM_DIRECT_LOGINS' ) );
                    $userObj = new WP_User($user_id);
                    foreach ($armroles as $armrole => $armroledescription) {
                        $userObj->add_cap($armrole);
                    }
                    unset($armrole);
                    unset($armroles);
                    unset($armroledescription);
                }
            }
        }

        function arm_is_direct_logins_addon_activated($plugin,$network_activation)
        {
            global $armember_check_plugin_copy, $arm_check_addon_copy_flag;
            $myaddon_name = "armemberdirectlogins/armemberdirectlogins.php";

            if($plugin == $myaddon_name && empty($arm_check_addon_copy_flag))
            {

                if(!(is_plugin_active('armember/armember.php')))
                {
                    deactivate_plugins($myaddon_name, FALSE);
                    $redirect_url = network_admin_url('plugins.php?deactivate=true&arm_license_deactivate=true&arm_deactivate_plugin='.$myaddon_name);
                    $armpa_dact_message = __('Please activate license of ARMember premium plugin to use ARMember - Direct Login Addon', 'ARM_DIRECT_LOGINS');
                    /* translators: 1. Redirect URL link starts 2.Redirect URL link ends */
                    $arm_link = sprintf( __('Please %s Click Here %s to Continue', 'ARM_DIRECT_LOGINS'), '<a href="javascript:void(0)" onclick="window.location.href=\'' . $redirect_url . '\'">', '</a>');
                    wp_die('<p>'.$armpa_dact_message.'<br/>'.$arm_link.'</p>'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - Text is escaped properly
                    die;
                }

                $license = trim( get_option( 'arm_pkg_key' ) );

                if($armember_check_plugin_copy == 1 && ('' === $license || false === $license )) 
                {
                    deactivate_plugins($myaddon_name, FALSE);
                    $redirect_url = network_admin_url('plugins.php?deactivate=true&arm_license_deactivate=true&arm_deactivate_plugin='.$myaddon_name);
                    $armpa_dact_message = __('Please activate license of ARMember premium plugin to use ARMember - Direct Login Addon', 'ARM_DIRECT_LOGINS');
                    /* translators: 1. Redirect URL link starts 2.Redirect URL link ends */
                    $arm_link = sprintf( __('Please %s Click Here %s to Continue', 'ARM_DIRECT_LOGINS'), '<a href="javascript:void(0)" onclick="window.location.href=\'' . $redirect_url . '\'">', '</a>');
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
                            $armpa_dact_message = __('Please activate license of ARMember premium plugin to use ARMember - Direct Login Addon', 'ARM_DIRECT_LOGINS');
                            /* translators: 1. Redirect URL link starts 2.Redirect URL link ends */
                            $arm_link = sprintf( __('Please %s Click Here %s to Continue', 'ARM_DIRECT_LOGINS'), '<a href="javascript:void(0)" onclick="window.location.href=\'' . $redirect_url . '\'">', '</a>');
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
                                $armpa_dact_message = __('Please activate license of ARMember premium plugin to use ARMember - Direct Login Addon', 'ARM_DIRECT_LOGINS');
                                /* translators: 1. Redirect URL link starts 2.Redirect URL link ends */
                                $arm_link = sprintf( __('Please %s Click Here %s to Continue', 'ARM_DIRECT_LOGINS'), '<a href="javascript:void(0)" onclick="window.location.href=\'' . $redirect_url . '\'">', '</a>');
                                wp_die('<p>'.$armpa_dact_message.'<br/>'.$arm_link.'</p>'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - Text is escaped properly
                                die;
                            }

                        }
                        else
                        {
                            deactivate_plugins($myaddon_name, FALSE);
                            $redirect_url = network_admin_url('plugins.php?deactivate=true&arm_license_deactivate=true&arm_deactivate_plugin='.$myaddon_name);
                            $armpa_dact_message = __('Please activate license of ARMember premium plugin to use ARMember - Direct Login Addon', 'ARM_DIRECT_LOGINS');
                            /* translators: 1. Redirect URL link starts 2.Redirect URL link ends */
                            $arm_link = sprintf( __('Please %s Click Here %s to Continue', 'ARM_DIRECT_LOGINS'), '<a href="javascript:void(0)" onclick="window.location.href=\'' . $redirect_url . '\'">', '</a>');
                            wp_die('<p>'.$armpa_dact_message.'<br/>'.$arm_link.'</p>'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - Text is escaped properly
                            die;
                        }
                    }    
                }
            }

        }
		
        /*
         * Restrict Network Activation
         */
        public static function arm_direct_logins_check_network_activation($network_wide) {
            if (!$network_wide)
                return;

            deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

            header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
            exit;
        }
		function upgrade_data_directlogins() {
			global $arm_direct_logins_newdbversion;
	
			if (!isset($arm_direct_logins_newdbversion) || $arm_direct_logins_newdbversion == "")
				$arm_direct_logins_newdbversion = get_option('arm_direct_logins_version');
	
            if (version_compare($arm_direct_logins_newdbversion, '2.1', '<')) {
				$path = ARM_DIRECT_LOGINS_VIEW_DIR . '/upgrade_latest_data_directlogins.php';
				include($path);
			}
		}
		
        public static function uninstall() {
            global $wpdb;
            $wpdb->query("DELETE FROM `".$wpdb->usermeta."` WHERE  `meta_key` LIKE  'arm_direct_logins_%'");
            delete_option( 'arm_direct_logins_version' );
        }
        
        function arm_direct_logins_add_capabilities_to_new_user($user_id){
            global $ARMember;
            if( $user_id == '' ){
                return;
            }
            if( user_can($user_id,'administrator')){
                $armroles =  array( 'arm_direct_logins' => __('Direct Login', 'ARM_DIRECT_LOGINS' ) );
                $userObj = new WP_User($user_id);
                foreach ($armroles as $armrole => $armroledescription){
                    $userObj->add_cap($armrole);
                }
                unset($armrole);
                unset($armroles);
                unset($armroledescription);
            }
        }

        function arm_admin_notices($arm_license_notice = '') {
            global $pagenow, $arm_slugs;    
            if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))) {
                if( !$this->is_armember_support() )
                    $arm_license_notice .= "<div class='armember_notice_warning'>" . esc_html__('ARMember - Direct Login Addon plugin requires ARMember Plugin installed and active.', 'ARM_DIRECT_LOGINS') . "</div>";

                else if ( !$this->is_version_compatible() )
                    $arm_license_notice .= "<div class='armember_notice_warning'>" . esc_html__('ARMember - Direct Login Addon plugin requires ARMember plugin installed with version 3.2 or higher.', 'ARM_DIRECT_LOGINS') . "</div>";
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
        
		function armdirectlogin_getapiurl() {
			$api_url = 'https://www.arpluginshop.com/';
			return $api_url;
		}
		
        function arm_direct_logins_hide_update_notice() {
            $arm_direct_logins_pages = array('arm_direct_logins');
            if ( isset($_REQUEST['page']) && in_array($_REQUEST['page'], $arm_direct_logins_pages) ) {
                remove_action('admin_notices', 'update_nag', 3);
                remove_action('network_admin_notices', 'update_nag', 3);
                remove_action('admin_notices', 'maintenance_nag');
                remove_action('network_admin_notices', 'maintenance_nag');
                remove_action('admin_notices', 'site_admin_notice');
                remove_action('network_admin_notices', 'site_admin_notice');
                remove_action('load-update-core.php', 'wp_update_plugins');
            }
        }
        
        function is_armember_support() {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

            return is_plugin_active( 'armember/armember.php' );
        }
        
        function get_armember_version() {
            $arm_db_version = get_option( 'arm_version' );

            return ( isset( $arm_db_version ) ) ? $arm_db_version : 0;
        }
        
        function is_version_compatible() {
            if ( !version_compare( $this->get_armember_version(), '3.2', '>=' ) || !$this->is_armember_support() ) :
                return false;
            else : 
                return true;
            endif;
        }
        
        function is_armember_compatible() {
            if( $this->is_armember_support() && $this->is_version_compatible() )
                return true;
            else
                return false;
        }
        
        function arm_direct_logins_load_textdomain() {
            load_plugin_textdomain('ARM_DIRECT_LOGINS', false, dirname( plugin_basename( ARM_DIRECT_LOGINS_DIR . '/armemberdirectlogins.php'  ) ) . '/languages/');
        }
		
		function armdirectlogin_get_remote_post_params($plugin_info = "") {
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
				if (strpos(strtolower($plugin["Title"]), "armemberdirectlogins") !== false) {
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
       
        function arm_direct_logins_menu(){
            global $arm_slugs, $current_user;

            $arm_direct_logins_name    = __( 'Direct Login', 'ARM_DIRECT_LOGINS' );
            $arm_direct_logins_title   = __( 'Direct Login', 'ARM_DIRECT_LOGINS' );
            $arm_direct_logins_cap     = 'arm_direct_logins';
            $arm_direct_logins_slug    = 'arm_direct_logins';

            add_submenu_page( $arm_slugs->main, $arm_direct_logins_name, $arm_direct_logins_title, $arm_direct_logins_cap, $arm_direct_logins_slug, array( $this, 'route' ) );
        }
        
        function route() {
            if($_REQUEST['page'] == 'arm_direct_logins'){ //phpcs:ignore
                $pageWrapperClass = '';
                if (is_rtl()) {
                    $pageWrapperClass = 'arm_page_rtl';
                }
                echo '<div class="arm_page_wrapper '.esc_attr($pageWrapperClass).'" id="arm_page_wrapper">';

                $arm_admin_notice = '';
                echo apply_filters('arm_admin_notice',$arm_admin_notice);  //phpcs:ignore
                
                $this->admin_messages();
                
                if(file_exists(ARM_DIRECT_LOGINS_VIEW_DIR . 'arm_direct_logins.php')){
                    include_once ARM_DIRECT_LOGINS_VIEW_DIR . 'arm_direct_logins.php';
                }
                
                echo '</div>';
            }
        }
        
        function admin_messages() {
            echo '<div class="armclear"></div>
            <div class="arm_message arm_success_message" id="arm_success_message">
                    <div class="arm_message_text"><?php echo $success_msgs;?></div>
            </div>
            <div class="arm_message arm_error_message" id="arm_error_message">
                    <div class="arm_message_text"><?php echo $error_msgs;?></div>
            </div>
            <div class="armclear"></div>
            <div class="arm_toast_container" id="arm_toast_container"></div>';
        }
        
        function arm_direct_logins_scripts() {
            global $arm_direct_logins_version, $arm_version;
            $arm_direct_logins_pages = array('arm_direct_logins');
            if ( isset($_REQUEST['page']) && in_array($_REQUEST['page'], $arm_direct_logins_pages) ) {

                $arm_version_compatible = (version_compare($arm_version, '4.0.1', '>=')) ? 1 : 0;
                 
                wp_register_style( 'arm_direct_logins_css', ARM_DIRECT_LOGINS_CSS_URL . 'arm_direct_logins_admin.css', array(), $arm_direct_logins_version );
                wp_register_script( 'arm_direct_logins_js', ARM_DIRECT_LOGINS_JS_URL . 'arm_direct_logins_admin.js', array(), $arm_direct_logins_version );


                if($arm_version_compatible)
                {
                    wp_enqueue_style('datatables');
                }
                
                // wp_localize_script('jquery', 'imageurl', MEMBERSHIP_IMAGES_URL);
                echo '<script type="text/javascript" data-cfasync="false">';
                echo 'imageurl = "'.esc_url(MEMBERSHIP_IMAGES_URL).'";';
                echo 'armpleaseselect = "'.esc_html__("Please select one or more records.", 'ARM_DIRECT_LOGINS').'";';
                echo 'armbulkActionError = "'.esc_html__("Please select valid action.", 'ARM_DIRECT_LOGINS').'";';
                echo 'armsaveSettingsSuccess = "'.esc_html__("Settings has been saved successfully.", 'ARM_DIRECT_LOGINS').'";';
                echo 'armsaveSettingsError = "'.esc_html__("There is a error while updating settings, please try again.", 'ARM_DIRECT_LOGINS').'";';
                echo '</script>';


                wp_enqueue_style( 'arm_direct_logins_css' );
                wp_enqueue_script( 'arm_direct_logins_js' );
                
                wp_enqueue_script( 'jquery' );
                wp_enqueue_style( 'arm_admin_css' );
                wp_enqueue_style( 'arm-font-awesome-css' );
                wp_enqueue_style( 'arm_form_style_css' );
                wp_enqueue_style( 'arm_chosen_selectbox' );
                wp_enqueue_script( 'arm_chosen_jq_min' );
                wp_enqueue_script('jquery-ui-autocomplete');
                wp_enqueue_script( 'arm_tipso' );
                wp_enqueue_script( 'arm_icheck-js' );
                wp_enqueue_script( 'arm_bpopup' );
                wp_enqueue_style( 'armlite_admin_css' );
                wp_enqueue_style( 'arm_admin_js' );


                if($arm_version_compatible)
                {
                    wp_enqueue_script('datatables');
                    wp_enqueue_script('buttons-colvis');
                    wp_enqueue_script('fixedcolumns');
                    wp_enqueue_script('fourbutton');
                }
                else
                {
                    wp_enqueue_script( 'jquery_dataTables', ARM_DIRECT_LOGINS_ARMEMBER_URL . '/datatables/media/js/jquery.dataTables.js', array(), $arm_direct_logins_version );
                    wp_enqueue_script( 'FourButton', ARM_DIRECT_LOGINS_ARMEMBER_URL . '/datatables/media/js/four_button.js', array(), $arm_direct_logins_version );
                    wp_enqueue_script( 'FixedColumns', ARM_DIRECT_LOGINS_ARMEMBER_URL . '/datatables/media/js/FixedColumns.js', array(), $arm_direct_logins_version );
                }
                
            }
        }
        
    }    
}

global $arm_direct_logins;
$arm_direct_logins = new ARM_Direct_Logins();

if (file_exists(ARM_DIRECT_LOGINS_CLASSES_DIR . '/class.arm_direct_logins.php') && $arm_direct_logins->is_armember_compatible()){
    require_once(ARM_DIRECT_LOGINS_CLASSES_DIR . '/class.arm_direct_logins.php' );
}

global $armdirectlogin_api_url, $armdirectlogin_plugin_slug;

$armdirectlogin_api_url = $arm_direct_logins->armdirectlogin_getapiurl();
$armdirectlogin_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armdirectlogin_check_for_plugin_update');

function armdirectlogin_check_for_plugin_update($checked_data) {
    global $armdirectlogin_api_url, $armdirectlogin_plugin_slug, $wp_version, $arm_direct_logins_version,$arm_direct_logins;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armdirectlogin_plugin_slug,
        'version' => $arm_direct_logins_version,
        'other_variables' => $arm_direct_logins->armdirectlogin_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMDIRECTLOGIN-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armdirectlogin_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armdirectlogin_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armdirectlogin_plugin_slug . '/' . $armdirectlogin_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armdirectlogin_plugin_api_call', 10, 3);

function armdirectlogin_plugin_api_call($def, $action, $args) {
    global $armdirectlogin_plugin_slug, $armdirectlogin_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armdirectlogin_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armdirectlogin_plugin_slug . '/' . $armdirectlogin_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armdirectlogin_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMDIRECTLOGIN-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armdirectlogin_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', 'ARM_DIRECT_LOGINS'), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', 'ARM_DIRECT_LOGINS'), $request['body']);
    }

    return $res;
}