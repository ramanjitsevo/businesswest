<?php

extract(shortcode_atts(array(
	'uncode_shortcode_id' => '',
	'post_type' => 'post',
	'field_elements' => '',
	'manual_values' => '',
	'custom_typo' => '',
	'columns' => '',
	'rounded_icon' => '',
	'text_display' => '',
	'text_color_type' => '',
	'text_color' => '',
	'text_color_solid' => '',
	'text_color_gradient' => '',
	'text_size' => '',
	'custom_size' => '',
	'text_height' => '',
	'text_space' => '',
	'text_font' => '',
	'text_weight' => '',
	'text_transform' => '',
	'text_italic' => '',
	'el_id' => '',
	'el_class' => '',
) , $atts));

if ( $el_id !== '' ) {
	$el_id = ' id="' . esc_attr( trim( $el_id ) ) . '"';
} else {
	$el_id = '';
}

$inline_style_css = uncode_get_dynamic_colors_css_from_shortcode( array(
	'type'       => 'uncode_custom_fields',
	'id'         => $uncode_shortcode_id,
	'attributes' => array(
		'text_color'          => $text_color,
		'text_color_type'     => $text_color_type,
		'text_color_solid'    => $text_color_solid,
		'text_color_gradient' => $text_color_gradient,
	)
) );

if ( $text_size === 'custom' && $custom_size !== '' ) {
	$inline_style_css .= uncode_get_dynamic_css_font_size_shortcode( array(
		'id'         => $uncode_shortcode_id,
		'font_size'  => $custom_size
	) );
}

$css_class = apply_filters( VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, 'uncode-custom-fields', $this->settings['base'], $atts );

$classes = array( $css_class );

if ( $text_display === 'inline' ) {
	$classes[] = 'display-inline-block';
}

$classes[] = trim( $this->getExtraClass( $el_class ) );

$detail_container_class = array();
$detail_class = array();

if ( $field_elements !== '' ) {
	$detail_container_class[] = 'detail-container--single';
}

if ( $custom_typo === 'yes' ) {
	$detail_class[] = 'headings-color';
}

if ( $custom_typo === 'yes' && $text_font !== '' ) {
	$detail_class[] = $text_font;
}

if ( $custom_typo === 'yes' && $text_size !== '' ) {
	$detail_class[] = $text_size;
}

if ( $custom_typo === 'yes' && $text_weight !== '' ) {
	$detail_class[] = 'font-weight-' . $text_weight;
}

if ( $custom_typo === 'yes' && $text_transform !== '' ) {
	$detail_class[] = 'text-' . $text_transform;
}

if ( $custom_typo === 'yes' && $text_height !== '' ) {
	$detail_class[] = $text_height;
}

if ( $custom_typo === 'yes' && $text_space !== '' ) {
	$detail_class[] = $text_space;
}

if ( $custom_typo === 'yes' && $text_italic === 'yes' ) {
	$detail_class[] = 'text-italic';
}

if ( $custom_typo === 'yes' && $text_size === 'custom' && $custom_size !== '' ) {
	$detail_class[] = 'fontsize-' . $uncode_shortcode_id . '-custom';
}

$text_color = uncode_get_shortcode_color_attribute_value( 'text_color', $uncode_shortcode_id, $text_color_type, $text_color, $text_color_solid, $text_color_gradient );

if ($text_color !== '') {
	$detail_class[] = 'text-' . $text_color . '-color';
}

$detail_content = '';

if ( ! ( $field_elements === '' || $field_elements === 'label' || $field_elements === 'value' ) ) {
	$columns = $columns ? $columns : '1';

	$columns_conf = explode( ',', $columns );

	$data_fields_classes = ['data-fields'];

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
		$data_fields_classes[] = "data-fields--columns-desktop-{$desktop}";
	}

	if ( $tablet !== null ) {
		$data_fields_classes[] = "data-fields--columns-tablet-{$tablet}";
	}

	if ( $mobile !== null ) {
		$data_fields_classes[] = "data-fields--columns-mobile-{$mobile}";
	}

	$detail_content .= '<div class="' . esc_attr( implode( ' ', $data_fields_classes ) ) . '">';
}

global $post;

$current_post_type = uncode_get_current_post_type();

$all_custom_fields = function_exists( 'ot_get_option' ) ? ot_get_option( '_uncode_' . $post_type . '_custom_fields' ) : array();

$is_frontend_editor_or_cb = ( function_exists('vc_is_page_editable') && vc_is_page_editable() ) || $current_post_type == 'uncodeblock';

if ( isset( $atts['custom_fields_single_' . $post_type] ) && $atts['custom_fields_single_' . $post_type] ) {
	// There is a specific field
	$cf_fields = array( $atts['custom_fields_single_' . $post_type] );
} else {
	// Show all fields
	if ( $manual_values !== '' ) {
		// But only the specified ones
		$cf_fields = explode( ';', $manual_values );
	} else {
		// Show all fields
		$cf_fields = array();
	}
}

if ( count( $cf_fields ) > 0 ) {
	$custom_fields = array();

	foreach ( $cf_fields as $field_key ) {
		if ( ! $field_key ) {
			continue;
		}

		if ( $is_frontend_editor_or_cb ) {
			$custom_fields[] = array(
				'title'                => __( 'Field Title', 'uncode' ),
				'_uncode_cf_unique_id' => $field_key,
				'_uncode_cf_image'     => 'example-image-id',
			);
		} else {
			foreach ( $all_custom_fields as $field ) {
				if ( isset( $field['_uncode_cf_unique_id'] ) && $field['_uncode_cf_unique_id'] === $field_key ) {
					$custom_fields[] = $field;
				}
			}
		}
	}
} else {
	if ( $is_frontend_editor_or_cb ) {
		$custom_fields = array(
			array(
				'title'                => __( 'Field Label', 'uncode' ),
				'_uncode_cf_unique_id' => 'example-detail-123',
				'_uncode_cf_image'     => 'example-image-id',
			),
			array(
				'title'                => __( 'Field Label', 'uncode' ),
				'_uncode_cf_unique_id' => 'example-detail-456',
				'_uncode_cf_image'     => 'example-image-id',
			),
			array(
				'title'                => __( 'Field Label', 'uncode' ),
				'_uncode_cf_unique_id' => 'example-detail-789',
				'_uncode_cf_image'     => 'example-image-id',
			),
			array(
				'title'                => __( 'Field Label', 'uncode' ),
				'_uncode_cf_unique_id' => 'example-detail-1011',
				'_uncode_cf_image'     => 'example-image-id',
			),
		);
	} else {
		$custom_fields = $all_custom_fields;
	}
}

if ( is_array( $custom_fields ) ) {
	foreach ( $custom_fields as $field_key => $field ) {

		if ( $is_frontend_editor_or_cb ) {
			$value = __( 'Field Value Example', 'uncode' );
		} else {
			$value = get_post_meta( $post->ID, $field['_uncode_cf_unique_id'], 1 );
		}

		if ( $value !== '' ) {
			if ( $field_elements === '' || $field_elements === 'label' || $field_elements === 'value' ) {
				// Legacy support for old custom fields
				$detail_content .= '<span class="detail-container ' . esc_attr( trim( implode( ' ', $detail_container_class ) ) ) . ' ' . esc_attr( $field['_uncode_cf_unique_id'] ) . '">';
				if ( $field_elements === '' || ( $field_elements && $field_elements === 'label' ) ) {
					$detail_content .= '<span class="detail-label ' . esc_attr( trim( implode( ' ', $detail_class ) ) ) . '">' . esc_html( $field['title'] ) . '</span>';
				}
				if ( $field_elements === '' || ( $field_elements && $field_elements === 'value' ) ) {
					if ( strpos( $value, '|' ) !== false ) {
						$value = explode( '|', $value );
						$value = implode( ', ', $value );
					}

					$value = apply_filters( 'uncode_custom_field_value', $value, $field['_uncode_cf_unique_id'], $current_post_type );

					$detail_content .= '<span class="detail-value ' . esc_attr( trim( implode( ' ', $detail_class ) ) ) . '">' . wp_kses_post( $value ) . '</span>';
				}
				$detail_content .= '</span>';
			} else {
				// New custom fields style
				$has_icon = isset( $field['_uncode_cf_image'] ) && $field['_uncode_cf_image'] && ( $field_elements === 'icon_new' || $field_elements === 'icon_value_new' || $field_elements === 'icon_label_value_new' );
				$has_label = isset( $field['title'] ) && $field['title'] && ( $field_elements === 'label_new' || $field_elements === 'label_value_new' || $field_elements === 'icon_label_value_new' );
				$has_value = $field_elements === 'value_new' || $field_elements === 'label_value_new' || $field_elements === 'icon_value_new' || $field_elements === 'icon_label_value_new';

				$extra_classes = '';

				if ( $field_elements === 'label_value_new' || $field_elements === 'icon_label_value_new' ) {
					$extra_classes .= ' data-field--has-label';
				}

				if ( $field_elements === 'icon_value_new' ) {
					$extra_classes .= ' data-field--has-icon-value';
				}

				$detail_content .= '<div class="data-field data-field--' . esc_attr( $field['_uncode_cf_unique_id'] ) . $extra_classes . '">';

				// Icon
				if ( $has_icon ) {
					$icon_class = $rounded_icon === 'yes' ? 'data-field-icon--rounded' : '';

					if ( ( function_exists('vc_is_page_editable') && vc_is_page_editable() ) || $current_post_type == 'uncodeblock' ) {
						$detail_content .= '<div class="data-field-icon ' . $icon_class . '"><img src="' . get_template_directory_uri() . '/library/img/generic-icon.svg' . '"></div>';
					} else {
						$icon = wp_get_attachment_image_src( $field['_uncode_cf_image'], 'full' );

						if ( $icon && is_array( $icon ) ) {
							$width = isset( $icon[1] ) && $icon[1] ? 'width="' . esc_attr( $icon[1] ) . '"' : '';
							$height = isset( $icon[2] ) && $icon[2] ? 'height="' . esc_attr( $icon[2] ) . '"' : '';
							$icon_alt = get_post_meta( $field['_uncode_cf_image'], '_wp_attachment_image_alt', true );

							$detail_content .= '<div class="data-field-icon ' . $icon_class . '"><img src="' . esc_attr( $icon[0] ) . '" alt="' . esc_attr( $icon_alt ) . '" ' . $width . ' ' . $height . ' /></div>';
						}
					}
				}

				if ( $has_label || $has_value ) {
					$detail_content .= '<div class="data-field-content">';
				}

				// Label
				if ( $has_label ) {
					$detail_content .= '<span class="data-field-label">' . esc_html( $field['title'] ) . '</span>';
				}

				// Value
				if ( $has_value ) {
					if ( strpos( $value, '|' ) !== false ) {
						$value = explode( '|', $value );
						$value = implode( ', ', $value );
					}

					$value = apply_filters( 'uncode_custom_field_value', $value, $field['_uncode_cf_unique_id'], $current_post_type );

					$detail_content .= '<span class="data-field-value">' . wp_kses_post( $value ) . '</span>';
				}

				if ( $has_label || $has_value ) {
					$detail_content .= '</div>';
				}

				$detail_content .= '</div>';
			}
		}
	}
}

if ( ! ( $field_elements === '' || $field_elements === 'label' || $field_elements === 'value' ) ) {
	$detail_content .= '</div>';
}

$output = '<div class="uncode-wrapper ' . esc_attr( trim( implode( ' ', $classes ) ) ) . '">';
	$output .= '<p>' . $detail_content . '</p>';
	$output .= uncode_print_dynamic_inline_style( $inline_style_css );
$output .= '</div>';

echo uncode_remove_p_tag($output);
