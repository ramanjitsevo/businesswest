<?php

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
 * Get Posts by Category
 * 
 * @param string $category_slug Category slug to filter posts
 * @param int $limit Number of posts to retrieve
 * @return array Array of post objects
 */
function bw_get_posts_by_category($category_slug = '', $limit = 6) {
    // Build query arguments dynamically
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => $limit,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    // Add category filter if provided
    if (!empty($category_slug)) {
        $args['category_name'] = sanitize_text_field($category_slug);
    }
    
    $posts_query = new WP_Query($args);
    $posts = array();
    
    if ($posts_query->have_posts()) {
        while ($posts_query->have_posts()) {
            $posts_query->the_post();
            $post_id = get_the_ID();
            
            // Get categories for this post
            $categories = get_the_category($post_id);
            $category_names = array();
            if (!empty($categories)) {
                foreach ($categories as $cat) {
                    $category_names[] = $cat->name;
                }
            }
            
            $posts[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'link' => get_permalink(),
                'date' => get_the_date('F j, Y'),
                'featured_image' => get_the_post_thumbnail_url($post_id, 'full'),
                'excerpt' => get_the_excerpt(),
                'author' => get_the_author(),
                'categories' => implode(', ', $category_names)
            );
        }
        wp_reset_postdata();
    }
    
    return $posts;
}

function bw_current_user_plans() {

  $user_plan_ids = get_user_meta(get_current_user_id(), 'arm_user_plan_ids', true);

    $arm_plans = array();
    if (!empty($user_plan_ids) && is_array($user_plan_ids)) {
        foreach ($user_plan_ids as $plan_id) {
            $arm_plan = new ARM_Plan($plan_id);
            $plan_name_lower = strtolower($arm_plan->name);

            if (strpos($plan_name_lower, 'owner') !== false || strpos($plan_name_lower, 'business_owner') !== false || strpos($plan_name_lower, 'business owner') !== false) {
                $arm_plans[] = ARM_PLAN_OWNER;
            }
            if (strpos($plan_name_lower, 'staff') !== false || strpos($plan_name_lower, 'employee') !== false) {
                $arm_plans[] = ARM_PLAN_STAFF;
            }
            if (strpos($plan_name_lower, 'admin') !== false || strpos($plan_name_lower, 'administrator') !== false) {
                $arm_plans[] = ARM_PLAN_ADMIN;
            }
        }
    }

    $arm_plans = array_unique($arm_plans);
    return $arm_plans;
}
?>