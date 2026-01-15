<?php
/**
 * Single Member Card Template
 * 
 * Displays individual member profile card
 * Variable $member is passed from parent template
 * 
 * @package BusinessWest
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="bw-member-card" 
     data-member-id="<?php echo esc_attr( $member['id'] ); ?>" 
     data-lat="<?php echo esc_attr( $member['latitude'] ); ?>" 
     data-lng="<?php echo esc_attr( $member['longitude'] ); ?>">
    
    <!-- Member Banner -->
    <div class="bw-member-banner">       
        <div class="bw-banner-placeholder"></div>       
    </div>
    
    <!-- Member Logo -->
    <?php if ( ! empty( $member['logo_url'] ) ) : ?>
    <div class="bw-member-logo">
        <img src="<?php echo esc_url( $member['logo_url'] ); ?>" 
             alt="<?php echo esc_attr( $member['business_name'] ); ?> logo" 
             class="bw-logo-image"
             onerror="this.onerror=null; this.src='https://www.gravatar.com/avatar/?d=mystery&amp;s=150';">
    </div>
    <?php else : ?>
    <div class="bw-member-logo">
        <img src="https://www.gravatar.com/avatar/?d=mystery&amp;s=150" 
             alt="<?php echo esc_attr( $member['business_name'] ); ?> logo" 
             class="bw-logo-image">
    </div>
    <?php endif; ?>
    
    <!-- Member Content -->
    <div class="bw-member-content">
        <h4 class="bw-member-name">
            <a href="<?php echo esc_url( $member['profile_url'] ); ?>" 
               class="bw-member-link"
               target="_blank"
               rel="noopener">
                <?php echo esc_html( $member['business_name'] ); ?>
            </a>
        </h4>
        
    </div>
    
    <!-- Member Actions -->
    <div class="bw-member-actions">
        <a href="<?php echo esc_url( $member['profile_url'] ); ?>" 
           class="bw-view-profile-btn"
           target="_blank"
           rel="noopener">
            View Profile
        </a>       
    </div>
    
</div>
