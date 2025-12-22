<?php
$output = '';
extract( shortcode_atts( array(
	'uncode_shortcode_id' => '',
	'position' => '',
	'top_position' => 'auto',
	'right_position' => 'auto',
	'bottom_position' => 'auto',
	'left_position' => 'auto',
	'z_index' => '',
    'el_class' => '',
), $atts ) );

$classes = array('uncode-close-offcanvas-mobile uncode-menu-toggle lines-button close navbar-mobile-el');
$classes[] = $el_class;

$style_custom = '';

if ( $position === '' ) {
    $classes[] = 'uncode-menu-toggle-absolute';
    if ( $top_position !== '' ) {
		if ( is_numeric($top_position) ) {
			$top_position .= 'px';
		}
        $style_custom .= '--menu-toggle-top: ' . esc_attr($top_position) . ';';
    }
    if ( $right_position !== '' ) {
		if ( is_numeric($right_position) ) {
			$right_position .= 'px';
		}
        $style_custom .= '--menu-toggle-right: ' . esc_attr($right_position) . ';';
    }
    if ( $bottom_position !== '' ) {
		if ( is_numeric($bottom_position) ) {
			$bottom_position .= 'px';
		}
        $style_custom .= '--menu-toggle-bottom: ' . esc_attr($bottom_position) . ';';
    }
    if ( $left_position !== '' ) {
		if ( is_numeric($left_position) ) {
			$left_position .= 'px';
		}
        $style_custom .= '--menu-toggle-left: ' . esc_attr($left_position) . ';';
    }
    if ( $z_index !== '' && is_numeric($z_index) ) {
        $style_custom .= '--menu-toggle-index: ' . esc_attr($z_index) . ';';
    }

    if ( $style_custom !== '' ) {
        $style_custom = ' style="' . $style_custom . '"';
        $classes[] = 'menu-toggle-custom';
    }
}

$output = '<div class="' . esc_attr(trim(implode( ' ', $classes ))) . '"' . $style_custom . '><span class="lines"></span></div>';

echo uncode_remove_p_tag($output);
