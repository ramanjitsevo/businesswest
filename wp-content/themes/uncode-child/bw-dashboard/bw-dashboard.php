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


/**
 * Register the shortcode
 */
function bw_dashboard_shortcode($atts){

    wp_enqueue_style('bw-dashboard-css', get_stylesheet_directory_uri() . '/assets/css/bw-dashboard.css', array(), '1.0.0');
    wp_enqueue_script('bw-dashboard-js', get_stylesheet_directory_uri() . '/assets/js/bw-dashboard.js', array('jquery'), '1.0.0', true);

    $user_roles = wp_get_current_user()->roles;

    ob_start();

    // Include required files
    include_once 'includes/bw-banner.php';
    include_once 'includes/bw-tabs.php';
    include_once 'includes/bw-helpers.php';
    include_once 'includes/bw-content-shortcodes.php';


    echo '<div class="bw-dashboard">';

    bw_render_banner();

    bw_render_tabs($user_roles);

    echo '</div>';

    return ob_get_clean();
}
add_shortcode('bw_dashboard', 'bw_dashboard_shortcode');

?>