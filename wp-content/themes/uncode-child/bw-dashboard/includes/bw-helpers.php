<?php
/**
 * BW Dashboard - Helper Functions
 *
 * @package Uncode Child Theme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if user has specific role
 */
function bw_user_has_role($role) {
    $user = wp_get_current_user();
    return in_array($role, (array) $user->roles);
}

/**
 * Get user display name
 */
function bw_get_user_display_name() {
    $user = wp_get_current_user();
    return !empty($user->display_name) ? $user->display_name : $user->user_login;
}

/**
 * Get user avatar
 */
function bw_get_user_avatar($size = 40) {
    $user = wp_get_current_user();
    return get_avatar($user->ID, $size);
}

/**
 * Format date for display
 */
function bw_format_date($date) {
    return date_i18n(get_option('date_format'), strtotime($date));
}

/**
 * Sanitize output
 */
function bw_sanitize_output($output) {
    return esc_html($output);
}