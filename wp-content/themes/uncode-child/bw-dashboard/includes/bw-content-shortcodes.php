<?php
/**
 * BW Dashboard - Content Shortcodes
 *
 * @package Uncode Child Theme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard/Home Content Shortcode
 */
function bw_dashboard_home_content() {
    ob_start();
    ?>
    <div class="bw-tab-content-header">
                
        <?php echo do_shortcode('[arm_profile_detail id="105"]');  ?>
        
    </div>
    <?php
    return ob_get_clean();
}
