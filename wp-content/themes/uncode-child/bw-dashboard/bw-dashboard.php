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

include_once 'includes/bw-helper.php';
include_once 'includes/bw-invite-role-manager.php';

function bw_enque_scripts() {
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
}

/**
 * Register the shortcode
 */
function bw_dashboard_shortcode($atts)
{    
    $arm_plans = bw_current_user_plans();
    ob_start();

    if(empty($arm_plans)) {
        echo "<h2>" . __('This account is not associated with any Membership plans', 'uncode') . "</h2>";
        return;
    }

    bw_enque_scripts();
    
    // Include required files
    include_once 'includes/bw-banner.php';
    include_once 'includes/bw-tabs.php';
    include_once 'includes/bw-content-shortcodes.php';

    echo '<div class="bw-dashboard">';

    bw_render_banner();

    bw_render_tabs($arm_plans);

    echo '</div>';

    return ob_get_clean();
}
add_shortcode('bw_dashboard', 'bw_dashboard_shortcode');
