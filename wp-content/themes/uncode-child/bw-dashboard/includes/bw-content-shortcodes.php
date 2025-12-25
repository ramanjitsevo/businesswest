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
        <h2>Latest Updates and News</h2>
    </div>
        <?php echo do_shortcode('[smart_post_show id="161110"]');?>
    <?php
    return ob_get_clean();
}

function bw_my_profile() {
    ob_start();
    ?>
    <div class="bw-tab-content">
                
        <?php echo do_shortcode('[arm_profile_detail id="105"]');  ?>
        
    </div>
    <?php
    return ob_get_clean();
}
