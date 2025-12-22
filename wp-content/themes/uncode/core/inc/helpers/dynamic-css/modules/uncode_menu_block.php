<?php
/**
 * Uncode Menu Block CSS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Generate the CSS for the module
 */
function uncode_get_dynamic_colors_css_for_shortcode_uncode_menu_block( $shortcode, $custom_color_keys ) {
	$accepted_keys = array(
		'items_color' => array( 'text' ),
		'items_hover_color' => array( 'hover' ),
		'items_active_color' => array( 'active' ),
		'titles_color' => array( 'text' ),
		'titles_hover_color' => array( 'hover' ),
		'titles_active_color' => array( 'active' ),
		'descriptions_color' => array( 'text' ),
		'back_color' => array( 'bg' ),
		'text_stroke' => array( 'stroke' ),
		'icon_color'  => array( 'text', 'icon' ),
		'icon_bg_color' => array( 'after' ),
		'icon_accordion_color' => array( 'icon' )
	);

	$css = '';

	foreach ( $custom_color_keys as $custom_color_key ) {
		if ( ! array_key_exists( $custom_color_key, $accepted_keys ) ) {
			continue;
		}

		$css_value = uncode_get_dynamic_color_attr_data( $shortcode, $custom_color_key, $accepted_keys[$custom_color_key] );

		if ( $css_value ) {
			$css .= $css_value;
		}
	}

	return $css;
}
