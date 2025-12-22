<?php
/*
  Plugin Name: ARMember - Group/Umbrella Membership Addon
  Description: Extension for ARMember plugin for Group/Umbrella Membership.
  Version: 2.1
  Plugin URI: https://www.armemberplugin.com
  Author: Repute Infosystems
  AUthor URI: https://www.armemberplugins.com
  Text Domain: ARMGroupMembership
 */

define('ARM_GROUP_MEMBERSHIP_DIR_NAME', 'armembergroupmembership');
define('ARM_GROUP_MEMBERSHIP_DIR', WP_PLUGIN_DIR . '/' . ARM_GROUP_MEMBERSHIP_DIR_NAME);

require_once ARM_GROUP_MEMBERSHIP_DIR.'/autoload.php';