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
    <div class="news-wrapper">
        <div class="bw-tab-content-header">
            <h2><?php _e(DASHBOARD_NEWS, 'uncode'); ?></h2>
        </div>
        <?php echo do_shortcode('[smart_post_show id="161110"]');?>
    </div>

    <div class="resources-wrapper">
        <?php echo do_shortcode('[bw_posts_grid category="resources" limit="2"]');?>
    </div>

     <div class="events-wrapper">
            <?php echo do_shortcode('[bw_upcoming_events_content]');?>
    </div>

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



/**
 * Upcoming Events Content Shortcode
 */
function bw_upcoming_events_content_shortcode($atts) {
    ob_start();
    
    // Get upcoming events
    $events = bw_get_upcoming_events(2);
    ?>
    <div class="bw-tab-content-header">
        <h2><?php _e(DASHBOARD_UPCOMING_EVENTS, 'uncode'); ?></h2>        
    </div>
    
    <div class="bw-card">
        <?php if (!empty($events)): ?>
            <div class="bw-events-grid">
                <?php foreach ($events as $event): ?>
                    <div class="bw-event-item">
                        <?php if ($event['featured_image']): ?>
                            <div class="bw-event-image">
                                <a href="<?php echo esc_url($event['link']); ?>">
                                    <img src="<?php echo esc_url($event['featured_image']); ?>" alt="<?php echo esc_attr($event['title']); ?>">
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="bw-event-content">
                            <h3 class="bw-event-name">
                                <a href="<?php echo esc_url($event['link']); ?>"><?php echo esc_html($event['title']); ?></a>
                            </h3>
                            
                            <!-- <div class="bw-event-date">
                                <i class="fa fa-calendar" role="presentation"></i>
                                <span><?php echo esc_html($event['date']); ?></span>
                            </div>
                            
                            <?php if ($event['time']): ?>
                                <div class="bw-event-time">
                                    <i class="fa fa-clock-o" role="presentation"></i>
                                    <span><?php echo esc_html($event['time']); ?></span>
                                </div>
                            <?php endif; ?> -->

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="bw-no-events"><?php _e('No upcoming events at this time.', 'uncode'); ?></p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('bw_upcoming_events_content', 'bw_upcoming_events_content_shortcode');


/**
 * Posts Grid by Category Shortcode
 * Usage: [bw_posts_grid category="resources" limit="6"]
 */
function bw_posts_grid_shortcode($atts) {
    // Parse shortcode attributes with defaults
    $atts = shortcode_atts(
        array(
            'category' => '',
            'limit' => 6,
            'title' => ''
        ),
        $atts,
        'bw_posts_grid'
    );
    
    ob_start();
    
    // Get posts by category
    $posts = bw_get_posts_by_category($atts['category'], intval($atts['limit']));
    
    // Generate dynamic title if not provided
    $header_title = $atts['title'];
    if (empty($header_title) && !empty($atts['category'])) {
        $category_obj = get_category_by_slug($atts['category']);
        $header_title = $category_obj ? $category_obj->name : ucfirst(str_replace('-', ' ', $atts['category']));
    } elseif (empty($header_title)) {
        $header_title = __(DASHBOARD_TAB_RESOURCES, 'uncode');
    }
    ?>
    <div class="bw-tab-content-header">
        <h2 style="margin-top:0"><?php echo esc_html($header_title); ?></h2>        
    </div>
    
    <div class="bw-card">
        <?php if (!empty($posts)): ?>
            <div class="bw-events-grid">
                <?php foreach ($posts as $post): ?>
                    <div class="bw-event-item">
                        <?php if ($post['featured_image']): ?>
                            <div class="bw-event-image">
                                <a href="<?php echo esc_url($post['link']); ?>">
                                    <img src="<?php echo esc_url($post['featured_image']); ?>" alt="<?php echo esc_attr($post['title']); ?>">
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="bw-event-content">
                            <h3 class="bw-event-name">
                                <a href="<?php echo esc_url($post['link']); ?>"><?php echo esc_html($post['title']); ?></a>
                            </h3>
                            
                            <!-- <div class="bw-event-date">
                                <i class="fa fa-calendar" role="presentation"></i>
                                <span><?php echo esc_html($post['date']); ?></span>
                            </div> -->
                            
                            <!-- <?php if ($post['author']): ?>
                                <div class="bw-event-time">
                                    <i class="fa fa-user" role="presentation"></i>
                                    <span><?php echo esc_html($post['author']); ?></span>
                                </div>
                            <?php endif; ?> -->
                            
                            <!-- <?php if ($post['categories']): ?>
                                <div class="bw-event-venue">
                                    <i class="fa fa-folder" role="presentation"></i>
                                    <span><?php echo esc_html($post['categories']); ?></span>
                                </div>
                            <?php endif; ?> -->

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="bw-no-events"><?php _e('No posts found.', 'uncode'); ?></p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('bw_posts_grid', 'bw_posts_grid_shortcode');


function bw_my_team(){
     ob_start();
    ?>
    <div class="team-wrapper">
        <div class="bw-tab-content-header">
            <h2><?php _e(DASHBOARD_TAB_MY_TEAM, 'uncode'); ?></h2>
        </div>
        <?php echo  do_shortcode('[arm_group_child_member_list display_refresh_invite_code_button="false"]');?>
    </div>
    <?php
    return ob_get_clean();
}

function bw_my_business(){
    ob_start();
    ?>
    <div class="team-wrapper">
        <div class="bw-tab-content-header">
            <h2><?php _e(DASHBOARD_TAB_MY_BUSINESS, 'uncode'); ?></h2>
        </div>
        <?php echo  do_shortcode('[arm_template type="profile" id="1"]');?>
    </div>
    <?php
    return ob_get_clean();
}

function bw_events(){

    ob_start();
    ?>
    <div class="team-wrapper">
        <div class="bw-tab-content-header">
            <h2><?php _e(DASHBOARD_TAB_EVENTS, 'uncode'); ?></h2>
        </div>
        <?php echo do_shortcode('[tribe_events view="list"]');?>
    </div>
    <?php
    return ob_get_clean();
    
}