<?php
/**
 * Business Members Directory Listing
 * 
 * Displays a Google Map with member location pins and a responsive grid of member profile cards
 * Includes search and filter functionality via AJAX
 * 
 * Shortcode: [bw_members_list]
 * 
 * @package BusinessWest
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the shortcode
 */
add_shortcode( 'bw_members_list', 'bw_members_list_shortcode' );

/**
 * Main shortcode function
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function bw_members_list_shortcode( $atts ) {
    
    $atts = shortcode_atts( array(
        'members_per_page' => 12,
        'default_lat' => '51.4545',
        'default_lng' => '-2.5879',
        'map_zoom' => 10,
    ), $atts, 'bw_members_list' );
    
    bw_enqueue_members_assets();   

    ob_start();
    include( dirname( __FILE__ ) . '/templates/members-listing-template.php' );
    return ob_get_clean();
}

/**
 * Enqueue all required scripts and styles
 */
function bw_enqueue_members_assets() {
    
    wp_enqueue_script( 'jquery' );
    $dependencies = array( 'jquery' );
    
    if ( defined( 'GOOGLE_MAPS_API_KEY' ) && ! empty( GOOGLE_MAPS_API_KEY ) ) {
        wp_enqueue_script(
            'google-maps',
            'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( GOOGLE_MAPS_API_KEY ) . '&libraries=places',
            array(),
            null,
            true
        );
        $dependencies[] = 'google-maps';
    }
    
    // Enqueue custom JavaScript
    wp_enqueue_script(
        'bw-members-map',
        get_stylesheet_directory_uri() . '/assets/js/members-map.js',
        $dependencies,
        '1.0.0',
        true
    );    
    
    wp_localize_script( 'bw-members-map', 'bwMembersData', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'bw_members_nonce' ),
        'default_lat' => 51.4545,
        'default_lng' => -2.5879,
        'default_zoom' => 10,
        'default_banner' => get_stylesheet_directory_uri() . '/assets/images/default-banner.jpg',
        'default_logo' => get_stylesheet_directory_uri() . '/assets/images/default-logo.png',
    ) );    
    
    wp_enqueue_style(
        'bw-members-style',
        get_stylesheet_directory_uri() . '/assets/css/members-style.css',
        array(),
        '1.0.0'
    );
}

/**
 * AJAX handler for filtering members
 */
add_action( 'wp_ajax_bw_filter_members', 'bw_ajax_filter_members' );
add_action( 'wp_ajax_nopriv_bw_filter_members', 'bw_ajax_filter_members' );

function bw_ajax_filter_members() {
    
    check_ajax_referer( 'bw_members_nonce', 'nonce' );    
    
    $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
    $industry = isset( $_POST['industry'] ) ? sanitize_text_field( $_POST['industry'] ) : '';
    $paged = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;    
    
    $members = bw_get_members_data( $search, $industry, $paged );    
    
    wp_send_json_success( $members );
}

/**
 * Get members data with filtering - Using ARMember prebuilt functions
 * 
 * Improvements:
 * - Uses arm_get_all_members() for optimized member retrieval
 * - Uses arm_get_member_detail() for complete user data with meta
 * - Reduces individual get_user_meta() calls (more efficient)
 * - Integrates seamlessly with ARMember status system
 * - Uses arm_get_user_profile_url() for correct profile links
 * 
 * @param string $search Search keyword
 * @param string $industry Industry category filter
 * @param int $paged Page number
 * @return array Members data
 */
function bw_get_members_data( $search = '', $industry = '', $paged = 1 ) {
    global $arm_members_class, $arm_global_settings;
    
    // Get all active members using ARMember's prebuilt function
    // Status: 1 = Active, 2 = Inactive, 3 = Pending
    $arm_members = $arm_members_class->arm_get_all_members( 1 ); // Get active members only
    
    if ( empty( $arm_members ) ) {
        return array(
            'members' => array(),
            'total' => 0,
            'pages' => 0,
            'current_page' => $paged,
        );
    }
    
    $members = array();
    
    foreach ( $arm_members as $arm_member ) {
        $user_id = $arm_member->ID;
        
        // Get complete user details using ARMember's method
        $user_detail = $arm_members_class->arm_get_member_detail( $user_id );
        if ( ! $user_detail ) {
            continue;
        }
        
        // Get all user meta using ARMember's method
        $user_meta = isset( $user_detail->user_meta ) ? $user_detail->user_meta : array();
        
        // Map ARMember custom field names to standard field names
        // These custom field names come from ARMember form builder
        $field_mapping = array(
            'business_name' => array( 'text_6ebg0', 'business_name', 'company' ),
            'phone' => array( 'text_xzwca', 'phone', 'mobile', 'telephone' ),
            'industry' => array( 'text_vikw7', 'industry', 'business_category', 'sector' ),
            'address' => array( 'text_mancr', 'address' ),
            'city' => array( 'text_uca4m', 'city' ),
            'state' => array( 'text_zcbrf', 'state' ),
            'zip_code' => array( 'text_aclrk', 'zip_code', 'postcode' ),
            'country' => array( 'country' ),
        );
        
        // Helper function to get field value by trying multiple possible field names
        $get_field_value = function( $possible_names ) use ( $user_meta ) {
            foreach ( $possible_names as $field_name ) {
                if ( ! empty( $user_meta[ $field_name ] ) ) {
                    return $user_meta[ $field_name ];
                }
            }
            return '';
        };
        
        // Extract member fields using field mapping
        $business_name = $get_field_value( $field_mapping['business_name'] );
        if ( empty( $business_name ) ) {
            $business_name = $user_detail->display_name;
        }
        
        // Get industry/sector
        $member_industry = $get_field_value( $field_mapping['industry'] );
        
        // Apply search filter
        if ( ! empty( $search ) ) {
            $search_lower = strtolower( $search );
            $match = false;
            
            // Search in business name
            if ( stripos( $business_name, $search ) !== false ) {
                $match = true;
            }
            // Search in display name
            elseif ( stripos( $user_detail->display_name, $search ) !== false ) {
                $match = true;
            }
            // Search in email
            elseif ( stripos( $user_detail->user_email, $search ) !== false ) {
                $match = true;
            }
            // Search in industry
            elseif ( stripos( $member_industry, $search ) !== false ) {
                $match = true;
            }
            
            if ( ! $match ) {
                continue; // Skip this member
            }
        }
        
        // Apply industry filter
        if ( ! empty( $industry ) && $industry !== 'all' ) {
            if ( $member_industry !== $industry ) {
                continue; // Skip this member
            }
        }
        
        // Get location coordinates
        $latitude = ! empty( $user_meta['latitude'] ) ? $user_meta['latitude'] : '';
        $longitude = ! empty( $user_meta['longitude'] ) ? $user_meta['longitude'] : '';
        
        // Get phone number
        $phone = $get_field_value( $field_mapping['phone'] );
        
        // Get company name
        $company = $get_field_value( $field_mapping['business_name'] );
        
        // Build address from ARMember fields
        $address = $get_field_value( $field_mapping['address'] );
        $city = $get_field_value( $field_mapping['city'] );
        $state = $get_field_value( $field_mapping['state'] );
        $zip_code = $get_field_value( $field_mapping['zip_code'] );
        $country = $get_field_value( $field_mapping['country'] );
        
        $full_address = trim( implode( ', ', array_filter( array( $address, $city, $state, $zip_code, $country ) ) ) );
        
        // Get logo (ARMember avatar field)
        $logo_url = $get_field_value( array( 'avatar', 'profile_picture', 'user_avatar' ) );
        if ( empty( $logo_url ) ) {
            $logo_url = get_avatar_url( $user_id, array( 'size' => 150 ) );
        }
        
        // If still empty, use WordPress's default mystery man
        if ( empty( $logo_url ) ) {
            $logo_url = 'https://www.gravatar.com/avatar/?d=mystery&s=150';
        }
        
        // Get banner (ARMember profile_cover field)
        $banner_url = $get_field_value( array( 'profile_cover', 'banner_image', 'cover_photo' ) );
        if ( empty( $banner_url ) ) {
            $banner_url = $get_field_value( array( 'banner_url' ) );
        }
        
        // Get description
        $description = ! empty( $user_meta['description'] ) ? $user_meta['description'] : '';
        if ( empty( $description ) ) {
            $description = ! empty( $user_meta['bio'] ) ? $user_meta['bio'] : '';
        }
        
        // Truncate description
        $short_description = wp_trim_words( $description, 20, '...' );
        
        // Get member plan (ARMember subscription)
        $member_plan = '';
        if ( ! empty( $user_meta['arm_user_plan_ids'] ) && is_array( $user_meta['arm_user_plan_ids'] ) ) {
            global $arm_subscription_plans;
            if ( class_exists( 'ARM_subscription_plans_Lite' ) && isset( $arm_subscription_plans ) ) {
                $plan_names = array();
                foreach ( $user_meta['arm_user_plan_ids'] as $plan_id ) {
                    $plan_info = $arm_subscription_plans->arm_get_subscription_plan( $plan_id );
                    if ( ! empty( $plan_info ) && isset( $plan_info->arm_subscription_plan_name ) ) {
                        $plan_names[] = $plan_info->arm_subscription_plan_name;
                    }
                }
                $member_plan = ! empty( $plan_names ) ? implode( ', ', $plan_names ) : '';
            }
        }
        
        // Get profile URL using ARMember's method
        $profile_url = '';
        if ( isset( $arm_global_settings ) && method_exists( $arm_global_settings, 'arm_get_user_profile_url' ) ) {
            $profile_url = $arm_global_settings->arm_get_user_profile_url( $user_id );
        }
        if ( empty( $profile_url ) ) {
            $profile_url = get_author_posts_url( $user_id );
        }
        
        // Get member since date (user registration date)
        $member_since = $user_detail->user_registered;
        $member_since_formatted = date_i18n( get_option( 'date_format' ), strtotime( $member_since ) );
        
        // Build member data array
        $member_data = array(
            'id' => $user_id,
            'business_name' => esc_html( $business_name ),
            'company' => esc_html( $company ),
            'phone' => esc_html( $phone ),
            'address' => esc_html( $full_address ),
            'latitude' => ! empty( $latitude ) ? floatval( $latitude ) : 0,
            'longitude' => ! empty( $longitude ) ? floatval( $longitude ) : 0,
            'industry' => esc_html( $member_industry ),
            'member_plan' => esc_html( $member_plan ),
            'logo_url' => esc_url( $logo_url ),
            'banner_url' => esc_url( $banner_url ),
            'description' => esc_html( $short_description ),
            'profile_url' => esc_url( $profile_url ),
            'member_since' => esc_html( $member_since ),
            'member_since_formatted' => esc_html( $member_since_formatted ),
        );
        
        // Include all members regardless of coordinates
        // Members without lat/lng will show in grid but not on map
        $members[] = $member_data;
    }
    
    // Calculate pagination
    $total = count( $members );
    $per_page = 12;
    $total_pages = ceil( $total / $per_page );
    
    // Slice array for pagination
    $offset = ( $paged - 1 ) * $per_page;
    $members_paged = array_slice( $members, $offset, $per_page );
    
    return array(
        'members' => $members_paged,
        'total' => $total,
        'pages' => $total_pages,
        'current_page' => $paged,
    );
}

/**
 * Get ARMember field mapping
 * 
 * This function returns the mapping between standard field names and ARMember custom field names.
 * If your ARMember form uses different field IDs, update the mappings here.
 * 
 * To find your field names:
 * 1. Add debug code: print_r($user_meta); in bw_get_members_data()
 * 2. View the directory page and check the user_meta array output
 * 3. Update the field mappings below with your actual ARMember field names
 * 
 * @return array Field mapping
 */
function bw_get_armember_field_mapping() {
    return array(
        'business_name' => array( 'text_6ebg0', 'business_name', 'company' ),
        'phone' => array( 'text_xzwca', 'phone', 'mobile', 'telephone' ),
        'industry' => array( 'text_vikw7', 'industry', 'business_category', 'sector' ),
        'address' => array( 'text_mancr', 'address' ),
        'city' => array( 'text_uca4m', 'city' ),
        'state' => array( 'text_zcbrf', 'state' ),
        'zip_code' => array( 'text_aclrk', 'zip_code', 'postcode' ),
        'country' => array( 'country' ),
        'latitude' => array( 'latitude', 'lat' ),
        'longitude' => array( 'longitude', 'lng', 'long' ),
    );
}


