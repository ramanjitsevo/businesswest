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
            <h2><?php _e('Latest Updates and News', 'uncode'); ?></h2>
        </div>
            <?php echo do_shortcode('[smart_post_show id="161110"]');?>
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
 * Get Upcoming Events
 * 
 * @param int $limit Number of events to retrieve
 * @return array Array of event objects
 */
function bw_get_upcoming_events($limit = 2) {
    $args = array(
        'post_type' => 'tribe_events',
        'posts_per_page' => $limit,
        'post_status' => 'publish',
        'meta_key' => '_EventStartDate',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_query' => array(
            array(
                'key' => '_EventStartDate',
                'value' => current_time('Y-m-d H:i:s'),
                'compare' => '>=',
                'type' => 'DATETIME'
            )
        )
    );
    
    $events_query = new WP_Query($args);
    $events = array();
    
    if ($events_query->have_posts()) {
        while ($events_query->have_posts()) {
            $events_query->the_post();
            $event_id = get_the_ID();
            
            $events[] = array(
                'id' => $event_id,
                'title' => get_the_title(),
                'link' => get_permalink(),
                'date' => tribe_get_start_date($event_id, false, 'F j, Y'),
                'time' => tribe_get_start_date($event_id, false, 'g:i A'),
                'featured_image' => get_the_post_thumbnail_url($event_id, 'full'),
                'venue' => tribe_get_venue($event_id),
                'excerpt' => get_the_excerpt()
            );
        }
        wp_reset_postdata();
    }
    
    return $events;
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
        <h2><?php _e('Upcoming Events', 'uncode'); ?></h2>        
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
                            
                            <div class="bw-event-date">
                                <i class="fa fa-calendar" role="presentation"></i>
                                <span><?php echo esc_html($event['date']); ?></span>
                            </div>
                            
                            <?php if ($event['time']): ?>
                                <div class="bw-event-time">
                                    <i class="fa fa-clock-o" role="presentation"></i>
                                    <span><?php echo esc_html($event['time']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($event['venue']): ?>
                                <div class="bw-event-venue">
                                    <i class="fa fa-map-marker" role="presentation"></i>
                                    <span><?php echo esc_html($event['venue']); ?></span>
                                </div>
                            <?php endif; ?>
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