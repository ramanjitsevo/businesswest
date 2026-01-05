<?php
/**
 * BW Dashboard - Banner Component
 *
 * @package Uncode Child Theme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the dashboard banner
 */
function bw_render_banner() {
    ?>
    <div class="bw-banner">
        <div class="bw-container">
            <h1 class="bw-banner-title"> <?php _e(DASHBOARD_WELCOME, 'uncode'); ?> <?php echo wp_get_current_user()->display_name; ?></h1>
            <p class="bw-banner-subtitle"><?php _e(DASHBOARD_WELCOME_TEXT, 'uncode'); ?></p>
        </div>
    </div>
    <?php
}