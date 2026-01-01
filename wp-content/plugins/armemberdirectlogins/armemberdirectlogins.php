<?php 
/*
Plugin Name: ARMember - Direct Login Addon
Description: Extension for ARMember plugin to login without entering username & password.
Version: 2.1
Plugin URI: https://www.armemberplugin.com
Author: Repute InfoSystems
Author URI: https://www.armemberplugin.com
Text Domain: ARM_DIRECT_LOGINS
*/

define('ARM_DIRECT_LOGINS_DIR_NAME', 'armemberdirectlogins');
define('ARM_DIRECT_LOGINS_DIR', WP_PLUGIN_DIR . '/' . ARM_DIRECT_LOGINS_DIR_NAME);

if (file_exists( ARM_DIRECT_LOGINS_DIR . '/autoload.php')) {
    require_once ARM_DIRECT_LOGINS_DIR . '/autoload.php';
}