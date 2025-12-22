<?php
/**
 * Sticky Trigger test
 */

function uncode_page_require_asset_thumbs_reveal( $content_array ) {
	global $uncode_post_data;

	if ( apply_filters( 'uncode_enqueue_thumbs_reveal', false ) ) {
		return true;
	}

	// Check classes or parameter
	foreach ( $content_array as $content ) {
		if ( strpos( $content, 'css_animation="mask"' ) !== false || strpos( $content, 'image_anim="scroll"' ) !== false ) {
			return true;
		}
	}

	return false;
}
