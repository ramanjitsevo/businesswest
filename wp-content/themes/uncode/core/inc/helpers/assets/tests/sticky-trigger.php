<?php
/**
 * Sticky Trigger test
 */

function uncode_page_require_asset_sticky_trigger( $content_array ) {
	global $uncode_post_data;

	if ( apply_filters( 'uncode_enqueue_sticky_trigger', false ) ) {
		return true;
	}

	// Check classes or parameter
	foreach ( $content_array as $content ) {
		preg_match('/el_class=["|\'](.*sticky-trigger?)["|\']/', $content, $matches);
		if ( strpos( $content, ' sticky_trigger="yes"' ) !== false || $matches ) {
			return true;
		}
		if ( strpos( $content, 'css_animation="text-reveal"' ) !== false || strpos( $content, 'css_animation="scroll-trigger"' ) !== false ) {
			return true;
		}
	}

	return false;
}
