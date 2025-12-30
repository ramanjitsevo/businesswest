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

/**
 * Register the shortcode
 */
function bw_dashboard_shortcode($atts)
{

    wp_enqueue_style('bw-dashboard-css', get_stylesheet_directory_uri() . '/assets/css/bw-dashboard.css', array(), '1.0.0');
    wp_enqueue_script('bw-dashboard-js', get_stylesheet_directory_uri() . '/assets/js/bw-dashboard.js', array('jquery'), '1.0.0', true);

    $user_roles = wp_get_current_user()->roles;
    $user_plan_ids = get_user_meta(get_current_user_id(), 'arm_user_plan_ids', true);

    $arm_roles = array();
    if (!empty($user_plan_ids) && is_array($user_plan_ids)) {
        foreach ($user_plan_ids as $plan_id) {
            $arm_plan = new ARM_Plan($plan_id);
            $plan_name_lower = strtolower($arm_plan->name);

            if (strpos($plan_name_lower, 'owner') !== false || strpos($plan_name_lower, 'business owner') !== false) {
                $arm_roles[] = 'owner';
            }
            if (strpos($plan_name_lower, 'staff') !== false || strpos($plan_name_lower, 'employee') !== false) {
                $arm_roles[] = 'staff';
            }
            if (strpos($plan_name_lower, 'admin') !== false || strpos($plan_name_lower, 'administrator') !== false) {
                $arm_roles[] = 'admin';
            }
        }
    }

    $arm_roles = array_unique($arm_roles);
    ob_start();

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
