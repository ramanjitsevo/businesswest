<?php
/**
 * Custom Fields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Print custom field element.
 */
function uncode_print_custom_field_entry( $block_data, $field_value, $element_key, $element_value, $meta_class, $layout ) {
	$cf_post_type = get_post_type( $block_data['id'] );
	$cf_class     = '';

	if ( is_array( $element_value ) ) {
		if ( $element_key === 'custom_fields_group' ) {
			$cf_layout = isset( $element_value[0] ) ? $element_value[0] : 'value';
		} else {
			$cf_layout = 'value';
			$cf_class  = isset( $element_value[0] ) && $element_value[0] && $element_value[0] !== '-' ? $element_value[0] : '';
		}
	}

	if ( strpos( $field_value, '|' ) !== false ) {
		$field_value = explode( '|', $field_value );
		$field_value = implode( ', ', $field_value );
	}

	if ( $element_key === 'custom_fields_group' ) {
		$cf_fields = isset( $element_value[1] ) && $element_value[1] && $element_value[1] !== '-' ? $element_value[1] : 'all';

		if ( $cf_fields === 'all' || $cf_fields === 'All' ) {
			$cf_fields = array();
			$all_custom_fields = function_exists( 'ot_get_option' ) ? ot_get_option( '_uncode_' . $cf_post_type . '_custom_fields' ) : array();

			foreach ( $all_custom_fields as $field ) {
				if ( isset( $field['_uncode_cf_unique_id'] ) ) {
					$cf_fields[] = $field['_uncode_cf_unique_id'];
				}
			}
		} else {
			$cf_fields = explode( ';', $cf_fields );
		}

		$extra_class = '';

		$wrapper_layout = isset( $element_value[2] ) && $element_value[2] && $element_value[2] === 'flex' ? 'flex' : 'grid';

		$extra_class .= $wrapper_layout === 'flex' ? ' data-fields--flex' : '';

		if ( $wrapper_layout === 'flex' ) {
			$flex_align = isset( $element_value[3] ) && $element_value[3] ? $element_value[3] : '';

			if ( $flex_align ) {
				$extra_class .= ' data-fields--align-' . $flex_align;
			}
		}

		if ( $wrapper_layout === 'grid' ) {
			$columns = isset( $element_value[4] ) && $element_value[4] && $element_value[4] !== '-' ? $element_value[4] : 1;

			$columns_conf = explode( ',', $columns );

			$responsive_classes = array();

			if ( count( $columns_conf ) === 1 ) {
				$desktop = intval( $columns_conf[0] );
				$tablet = null;
				$mobile = null;
			} else {
				$desktop = isset( $columns_conf[0] ) ? intval( $columns_conf[0] ) : null;
				$tablet  = isset( $columns_conf[1] ) ? intval( $columns_conf[1] ) : null;
				$mobile  = isset( $columns_conf[2] ) ? intval( $columns_conf[2] ) : null;
			}

			if ( $desktop !== null ) {
				$responsive_classes[] = "data-fields--columns-desktop-{$desktop}";
			}

			if ( $tablet !== null ) {
				$responsive_classes[] = "data-fields--columns-tablet-{$tablet}";
			}

			if ( $mobile !== null ) {
				$responsive_classes[] = "data-fields--columns-mobile-{$mobile}";
			}

			$extra_class .= ' ' . esc_attr( implode( ' ', $responsive_classes ) );
		}

		$output = '<div class="data-fields' . $extra_class . '">';

		foreach ( $cf_fields as $field_key ) {
			if ( ! $field_key ) {
				continue;
			}
			$field_value = get_post_meta( $block_data['id'], $field_key, true );
			$field_data  = uncode_get_custom_field_data( $field_key, $cf_post_type );

			if ( ! $field_value ) {
				continue;
			}

			if ( strpos( $field_value, '|' ) !== false ) {
				$field_value = explode( '|', $field_value );
				$field_value = implode( ', ', $field_value );
			}

			$output .= uncode_print_single_custom_field( $block_data, $field_value, $field_data, $field_key, $meta_class, $layout, $cf_layout, $cf_post_type );
		}

		$output .= '</div>';
	} else {
		$output = '<div class="t-entry-cf-'.$element_key;

		if ( isset( $block_data['table_heading'] ) ) {
			$output .= ' ' . trim(implode(' ', $meta_class));
		}

		if ( $cf_class ) {
			$output .= ' ' . trim( $cf_class );
		}

		$field_value = apply_filters( 'uncode_custom_field_value', $field_value, $element_key, $cf_post_type );

		$output.= '">' . apply_filters( 'uncode_get_layout_cf_val', $field_value, $element_key, $field_value, $block_data, $layout ) . '</div>';
	}

	return $output;
}

/**
 * Print single custom field.
 */
function uncode_print_single_custom_field( $block_data, $field_value, $field_data, $element_key, $meta_class, $layout, $cf_layout, $cf_post_type ) {
	$cf_label = '';
	$cf_icon  = '';

	if ( $cf_layout === 'value_label' ) {
		$cf_label = uncode_get_custom_field_label( $field_data );
		$cf_label = '<span class="data-field-label">' . $cf_label . '</span> ';
	} else if ( $cf_layout === 'icon_value' ) {
		$cf_icon  = uncode_get_custom_field_icon( $field_data );

		if ( $cf_icon ) {
			$image = wp_get_attachment_image_src( $cf_icon, 'full' );
			$width = isset( $image[1] ) && $image[1] ? 'width="' . esc_attr( $image[1] ) . '"' : '';
			$height = isset( $image[2] ) && $image[2] ? 'height="' . esc_attr( $image[2] ) . '"' : '';
			$image_alt = get_post_meta( $cf_icon, '_wp_attachment_image_alt', true );
			$cf_icon  = '<div class="data-field-icon"><img src="' . esc_attr( $image[0] ) . '" alt="' . esc_attr( $image_alt ) . '" ' . $width . ' ' . $height . ' /></div>';
		}
	} else if ( $cf_layout === 'icon_value_label' ) {
		$cf_label = uncode_get_custom_field_label( $field_data );
		$cf_label = '<span class="data-field-label">' . $cf_label . '</span> ';
		$cf_icon  = uncode_get_custom_field_icon( $field_data );

		if ( $cf_icon ) {
			$image = wp_get_attachment_image_src( $cf_icon, 'full' );
			$width = isset( $image[1] ) && $image[1] ? 'width="' . esc_attr( $image[1] ) . '"' : '';
			$height = isset( $image[2] ) && $image[2] ? 'height="' . esc_attr( $image[2] ) . '"' : '';
			$image_alt = get_post_meta( $cf_icon, '_wp_attachment_image_alt', true );
			$cf_icon  = '<div class="data-field-icon"><img src="' . esc_attr( $image[0] ) . '" alt="' . esc_attr( $image_alt ) . '" ' . $width . ' ' . $height . ' /></div>';
		}
	}

	$output = '<div class="data-field data-field--' . $element_key;

	if ( $cf_layout === 'icon_value_label' || $cf_layout === 'label_value' ) {
		$output .= ' data-field--has-label';
	}

	if ( $cf_layout === 'icon_value' ) {
		$output .= ' data-field--has-icon-value';
	}

	if ( isset( $block_data['table_heading'] ) ) {
		$output .= ' ' . trim(implode(' ', $meta_class));
	}

	$output.= '">';

	// Icon
	if ( $cf_icon ) {
		$output .= $cf_icon;
	}

	$output .= '<div class="data-field-content">';

	// Label
	if ( $cf_label ) {
		$output .= $cf_label;
	}

	$field_value = apply_filters( 'uncode_custom_field_value', $field_value, $element_key, $cf_post_type );

	$output .= '<span class="data-field-value">' . apply_filters( 'uncode_get_layout_cf_val', $field_value, $element_key, $field_value, $block_data, $layout ) . '</span>';

	$output.= '</div></div>';

	return $output;
}

/**
 * Get custom field data.
 */
function uncode_get_custom_field_data( $field_id, $post_type ) {
	$all_custom_fields = function_exists( 'ot_get_option' ) ? ot_get_option( '_uncode_' . $post_type . '_custom_fields' ) : array();

	if ( ! is_array( $all_custom_fields ) ) {
		return array();
	}

	foreach ( $all_custom_fields as $field ) {
		if ( isset( $field['_uncode_cf_unique_id'] ) && $field['_uncode_cf_unique_id'] === $field_id ) {
			return $field;
		}
	}
}

/**
 * Get custom field label.
 */
function uncode_get_custom_field_label( $field_data ) {
	return isset( $field_data['title'] ) ? $field_data['title'] : '';
}

/**
 * Get custom field icon.
 */
function uncode_get_custom_field_icon( $field_data ) {
	return isset( $field_data['_uncode_cf_image'] ) ? $field_data['_uncode_cf_image'] : '';
}
