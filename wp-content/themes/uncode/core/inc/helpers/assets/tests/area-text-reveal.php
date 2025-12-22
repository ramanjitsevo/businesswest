<?php
/**
 * Sticky Trigger test
 */

function uncode_page_require_asset_area_text_reveal( $content_array ) {
	global $uncode_post_data;

	if ( apply_filters( 'uncode_enqueue_area_text_reveal', false ) ) {
		return true;
	}

	// Check classes or parameter
	foreach ( $content_array as $content ) {
		if ( strpos( $content, 'css_animation="text-reveal"' ) !== false || strpos( $content, 'css_animation="scroll-trigger"' ) !== false  || strpos( $content, 'css_animation="inner-rows"' ) !== false ) {
			return true;
		}
	}

	return false;
}
