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
            <h1 class="bw-banner-title">Welcome to Your Dashboard</h1>
            <p class="bw-banner-subtitle">Manage your account and settings</p>
        </div>
    </div>
    <?php
}