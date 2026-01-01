<?php

/**
 * BW Dashboard - Main Entry Point
 *
 * @package Uncode Child Theme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

include_once 'includes/bw-constant.php';
include_once 'includes/bw-helper.php';

/**
 * Register the shortcode
 */
function bw_dashboard_shortcode($atts)
{
    // Enqueue ARMember Group Membership styles and scripts
    $arm_gm_css_path = WP_PLUGIN_DIR . '/armembergroupmembership/css/arm_gm_front.css';
    $arm_gm_js_path = WP_PLUGIN_DIR . '/armembergroupmembership/js/arm_gm_front.js';
    
    if (file_exists($arm_gm_css_path)) {
        wp_enqueue_style('arm-gm-front-css', plugins_url('armembergroupmembership/css/arm_gm_front.css'), array(), '2.1');
    }
    
    if (file_exists($arm_gm_js_path)) {
        wp_enqueue_script('arm-gm-front-js', plugins_url('armembergroupmembership/js/arm_gm_front.js'), array('jquery'), '2.1', true);
    }
    
    // Enqueue ARMember core styles if needed
    $arm_css_path = WP_PLUGIN_DIR . '/armember/css/arm_front_css.css';
    if (file_exists($arm_css_path)) {
        wp_enqueue_style('arm-front-css', plugins_url('armember/css/arm_front_css.css'), array(), MEMBERSHIP_VERSION);
    }

    wp_enqueue_style('bw-dashboard-css', get_stylesheet_directory_uri() . '/assets/css/bw-dashboard.css', array(), '1.0.0');
    wp_enqueue_script('bw-dashboard-js', get_stylesheet_directory_uri() . '/assets/js/bw-dashboard.js', array('jquery'), '1.0.0', true);
    

    $user_roles = wp_get_current_user()->roles;
    $user_plan_ids = get_user_meta(get_current_user_id(), 'arm_user_plan_ids', true);

    $arm_roles = array();
    if (!empty($user_plan_ids) && is_array($user_plan_ids)) {
        foreach ($user_plan_ids as $plan_id) {
            $arm_plan = new ARM_Plan($plan_id);
            $plan_name_lower = strtolower($arm_plan->name);

            if (strpos($plan_name_lower, 'owner') !== false || strpos($plan_name_lower, 'business_owner') !== false || strpos($plan_name_lower, 'business owner') !== false) {
                $arm_roles[] = ARM_ROLE_OWNER;
            }
            if (strpos($plan_name_lower, 'staff') !== false || strpos($plan_name_lower, 'employee') !== false) {
                $arm_roles[] = ARM_ROLE_STAFF;
            }
            if (strpos($plan_name_lower, 'admin') !== false || strpos($plan_name_lower, 'administrator') !== false) {
                $arm_roles[] = ARM_ROLE_ADMIN;
            }
        }
    }

    $arm_roles = array_unique($arm_roles);
    ob_start();

    if(empty($arm_roles)) {
        echo "<h2>" . __('This account is not associated with any Membership plans', 'uncode') . "</h2>";
        return;
    }
    
    // Include required files
    include_once 'includes/bw-banner.php';
    include_once 'includes/bw-tabs.php';
    include_once 'includes/bw-content-shortcodes.php';

    echo '<div class="bw-dashboard">';

    bw_render_banner();

    bw_render_tabs($arm_roles);

    echo '</div>';

    return ob_get_clean();
}
add_shortcode('bw_dashboard', 'bw_dashboard_shortcode');
