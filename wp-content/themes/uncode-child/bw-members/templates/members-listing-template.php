<?php
/**
 * Members Directory Listing Template
 * 
 * This template displays the members directory with Google Map and profile grid
 * 
 * @package BusinessWest
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get initial members data
$members_data = bw_get_members_data();
$members = $members_data['members'];

?>

<div class="bw-members-directory" id="bw-members-directory">
    
    <!-- Search and Filter Section -->
    <div class="bw-members-filters">
        <div class="bw-filter-container">
            <div class="bw-search-box">
                <input 
                    type="text" 
                    id="bw-member-search" 
                    class="bw-search-input" 
                    placeholder="Search businesses by name..."
                    aria-label="Search businesses"
                />
                <button type="button" class="bw-search-btn" aria-label="Search">
                    <span class="dashicons dashicons-search"></span>
                </button>
            </div>            
            
            <div class="bw-results-count">
                <span id="bw-results-text">
                    Showing <strong id="bw-results-number"><?php echo count( $members ); ?></strong> of <strong id="bw-total-count"><?php echo $members_data['total']; ?></strong> businesses
                </span>
            </div>
        </div>
    </div>    

    
    <!-- Members Grid Section -->
    <div class="bw-members-grid-container">
        <div id="bw-members-grid" class="bw-members-grid">
            <?php if ( ! empty( $members ) ) : ?>
                <?php foreach ( $members as $member ) : ?>
                    <?php include( dirname( __FILE__ ) . '/member-card.php' ); ?>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="bw-no-results">
                    <p>No business members found. Please try a different search or filter.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Loading Indicator -->
        <div class="bw-grid-loading" id="bw-grid-loading" style="display: none;">
            <span class="spinner is-active"></span>
            <p>Loading members...</p>
        </div>
    </div>
    
    <!-- Pagination (if needed) -->
    <?php if ( $members_data['pages'] > 1 ) : ?>
        <div class="bw-pagination" id="bw-pagination">
            <button class="bw-page-btn bw-prev-btn" data-page="prev" <?php echo $members_data['current_page'] == 1 ? 'disabled' : ''; ?>>
                &laquo; Previous
            </button>
            <span class="bw-page-info">
                Page <span id="bw-current-page"><?php echo $members_data['current_page']; ?></span> 
                of <span id="bw-total-pages"><?php echo $members_data['pages']; ?></span>
            </span>
            <button class="bw-page-btn bw-next-btn" data-page="next" <?php echo $members_data['current_page'] >= $members_data['pages'] ? 'disabled' : ''; ?>>
                Next &raquo;
            </button>
        </div>
    <?php endif; ?>
    
</div>

<!-- Hidden data for JavaScript -->
<script type="application/json" id="bw-members-json">
<?php echo wp_json_encode( $members ); ?>
</script>
