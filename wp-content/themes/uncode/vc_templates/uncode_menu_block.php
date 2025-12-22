<?php
$output = '';
extract( shortcode_atts( array(
	'uncode_shortcode_id' => '',
	'nav_menu' => '',
	'nav_menu_slug' => '',
	'mobile' => '',
	'menu_layout' => '',
	'columns_first' => '',
	'menu_gap' => '',
	'gutter_size' => 3,
    'text_font' => '',
    'items_color' => '',
	'items_color_type' => '',
	'items_color_solid' => '',
    'items_hover_color' => '',
	'items_hover_color_type' => '',
	'items_hover_color_solid' => '',
    'items_active_color' => '',
	'items_active_color_type' => '',
	'items_active_color_solid' => '',
	'text_size' => '',
    'heading_custom_size' => '',
    'text_weight' => '',
    'text_transform' => '',
    'text_height' => '',
    'text_space' => '',
    'text_italic' => '',
	'description_custom_size' => '',
	'title_accordion' => '',
	'accordion_icon' => '',
	'accordion_icon_close' => '',
	'icon_accordion_size' => '',
	'icon_accordion_min_width' => '',
	'icon_accordion_color' => '',
	'icon_accordion_color_type' => '',
	'icon_accordion_color_solid' => '',
	'no_link' => '',
    'titles_color' => '',
	'titles_color_type' => '',
	'titles_color_solid' => '',
    'titles_hover_color' => '',
	'titles_hover_color_type' => '',
	'titles_hover_color_solid' => '',
    'titles_active_color' => '',
	'titles_active_color_type' => '',
	'titles_active_color_solid' => '',
	'title_font' => '',
	'title_semantic' => 'div',
	'title_size' => '',
	'title_custom_size' => '',
	'sec_size' => '',
	'sec_custom_size' => '',
	'title_weight' => '',
	'title_transform' => '',
	'title_height' => '',
	'title_space' => '',
	'title_italic' => '',
	'titles_gap' => '',
	'nested_list_gap' => '',
	'indent' => '',
	'titles_top' => '',
	'nested_top' => '',
    'icon' => '',
	'badge' => '',
	'badge_pos' => '',
	'description' => '',
    'descriptions_color' => '',
	'descriptions_color_type' => '',
	'descriptions_color_solid' => '',
	'icon_replace' => '',
	'icon_alt' => '',
	'media' => '',	
    'icon_align' => '',
	'icon_space_reduce' => '',
    'icon_color' => '',
	'icon_color_type' => '',
	'icon_color_solid' => '',
	'icon_color_gradient' => '',
    'icon_bg_color' => '',
	'icon_bg_color_type' => '',
	'icon_bg_color_solid' => '',
	'icon_bg_color_gradient' => '',
	'background_style' => '',
	'font_icon_size' => '',
	'media_icon_size' => '',
	'bg_size' => '',
	'desktop_visibility' => '',
	'medium_visibility' => '',
	'mobile_visibility' => '',
	'slug' => '',
    'el_class' => '',
	'hook_id' => '',
), $atts ) );

if ( !apply_filters( 'uncode_activate_menu_badges', false ) ) {
	$badge = false;
}

$inline_style_css = uncode_get_dynamic_colors_css_from_shortcode( array(
	'type'       => 'uncode_menu_block',
	'id'         => $uncode_shortcode_id,
	'attributes' => array(
		'items_color'          => $items_color,
		'items_color_type'     => $items_color_type,
		'items_color_solid'    => $items_color_solid,
		'items_hover_color'          => $items_hover_color,
		'items_hover_color_type'     => $items_hover_color_type,
		'items_hover_color_solid'    => $items_hover_color_solid,
		'items_active_color'          => $items_active_color,
		'items_active_color_type'     => $items_active_color_type,
		'items_active_color_solid'    => $items_active_color_solid,
		'titles_color'          => $titles_color,
		'titles_color_type'     => $titles_color_type,
		'titles_color_solid'    => $titles_color_solid,
		'titles_hover_color'          => $titles_hover_color,
		'titles_hover_color_type'     => $titles_hover_color_type,
		'titles_hover_color_solid'    => $titles_hover_color_solid,
		'titles_active_color'          => $titles_active_color,
		'titles_active_color_type'     => $titles_active_color_type,
		'titles_active_color_solid'    => $titles_active_color_solid,
		'descriptions_color'          => $descriptions_color,
		'descriptions_color_type'     => $descriptions_color_type,
		'descriptions_color_solid'    => $descriptions_color_solid,
		'icon_color'          => $icon_color,
		'icon_color_type'     => $icon_color_type,
		'icon_color_solid'    => $icon_color_solid,
		'icon_color_gradient' => $icon_color_gradient,
		'icon_bg_color'          => $icon_bg_color,
		'icon_bg_color_type'     => $icon_bg_color_type,
		'icon_bg_color_solid'    => $icon_bg_color_solid,
		'icon_bg_color_gradient' => $icon_bg_color_gradient,
		'icon_accordion_color' => $icon_accordion_color,
		'icon_accordion_color_type' => $icon_accordion_color_type,
		'icon_accordion_color_solid' => $icon_accordion_color_solid,
	)
) );

$items_color = uncode_get_shortcode_color_attribute_value( 'items_color', $uncode_shortcode_id, $items_color_type, $items_color, $items_color_solid, false );
$items_hover_color = uncode_get_shortcode_color_attribute_value( 'items_hover_color', $uncode_shortcode_id, $items_hover_color_type, $items_hover_color, $items_hover_color_solid, false );
$items_active_color = uncode_get_shortcode_color_attribute_value( 'items_active_color', $uncode_shortcode_id, $items_active_color_type, $items_active_color, $items_active_color_solid, false );
$titles_color = uncode_get_shortcode_color_attribute_value( 'titles_color', $uncode_shortcode_id, $titles_color_type, $titles_color, $titles_color_solid, false );
$titles_hover_color = uncode_get_shortcode_color_attribute_value( 'titles_hover_color', $uncode_shortcode_id, $titles_hover_color_type, $titles_hover_color, $titles_hover_color_solid, false );
$titles_active_color = uncode_get_shortcode_color_attribute_value( 'titles_active_color', $uncode_shortcode_id, $titles_active_color_type, $titles_active_color, $titles_active_color_solid, false );
$descriptions_color = uncode_get_shortcode_color_attribute_value( 'descriptions_color', $uncode_shortcode_id, $descriptions_color_type, $descriptions_color, $descriptions_color_solid, false );
$icon_color = uncode_get_shortcode_color_attribute_value( 'icon_color', $uncode_shortcode_id, $icon_color_type, $icon_color, $icon_color_solid, $icon_color_gradient );
$icon_bg_color = uncode_get_shortcode_color_attribute_value( 'icon_bg_color', $uncode_shortcode_id, $icon_bg_color_type, $icon_bg_color, $icon_bg_color_solid, $icon_bg_color_gradient );
$icon_accordion_color = uncode_get_shortcode_color_attribute_value( 'icon_accordion_color', $uncode_shortcode_id, $icon_accordion_color_type, $icon_accordion_color, $icon_accordion_color_solid, false );

$menu_style = false;

$classes = array('unmenu-block unmenu-inner-ul');
$classes[] = $el_class;
$title_classes = array('unmenu-title');
$li_classes = array();
$item_classes = array();
$first_title_classes = array();
$sec_title_classes = array();
$a_classes = array();
$desc_classes = array();

if ( $items_color ) {
	$item_classes[] = 'text-' . $items_color . '-color';
}
if ( $items_hover_color ) {
	$item_classes[] = 'hover-' . $items_hover_color . '-color';
}
if ( $items_active_color ) {
	$item_classes[] = 'active-' . $items_active_color . '-color';
}
if ( $titles_color ) {
	$title_classes[] = 'text-' . $titles_color . '-color';
}
if ( $titles_hover_color ) {
	$title_classes[] = 'hover-' . $titles_hover_color . '-color';
}
if ( $titles_active_color ) {
	$title_classes[] = 'active-' . $titles_active_color . '-color';
}

if ( $title_accordion !== '' ) {
	$classes[] = 'unmenu-collapse';
	$classes[] = 'unmenu-collapse-' . $title_accordion;
}

if ($title_font !== '') {
	$title_classes[] = $title_font;
}
if ($title_height !== '') {
	$title_classes[] = $title_height;
}
if ($title_space !== '') {
	$title_classes[] = $title_space;
}
if ($title_weight !== '') {
	$title_classes[] = 'font-weight-' . $title_weight;
}
if ($title_size === 'custom' && $title_custom_size !== '') {
	$first_title_classes[] = 'fontsize-title-' . $uncode_shortcode_id . '-custom';
	$first_title_classes[] = 'font-size-title-custom';
	$first_title_classes[] = 'font-size-custom';
}
if ($sec_size === 'custom' && $sec_custom_size !== '') {
	$sec_title_classes[] = 'fontsize-title-' . $uncode_shortcode_id . '-sec-custom';
	$sec_title_classes[] = 'font-size-title-custom';
	$sec_title_classes[] = 'font-size-custom';
} else {
	if ( !empty($first_title_classes) ) {
		$sec_title_classes = $first_title_classes;
	}
}
if ($title_transform !== '') {
	$title_classes[] = 'text-' . $title_transform;
}
if ($title_italic === 'yes') {
	$title_classes[] = 'text-italic';
}

if ( $title_size === 'custom' && $title_custom_size !== '' ) {
	$font_style_array = array(
		'id'         => 'title-'.$uncode_shortcode_id,
		'font_size'  => $title_custom_size
	);
	$inline_style_css .= uncode_get_dynamic_css_font_size_shortcode( $font_style_array );
}

if ( $sec_size === 'custom' && $sec_custom_size !== '' ) {
	$font_style_array = array(
		'id'         => 'title-'.$uncode_shortcode_id.'-sec',
		'font_size'  => $sec_custom_size
	);
	$inline_style_css .= uncode_get_dynamic_css_font_size_shortcode( $font_style_array );
}

if ($text_font !== '') {
	$li_classes[] = $text_font;
}
if ($text_height !== '') {
	$li_classes[] = $text_height;
}
if ($text_space !== '') {
	$li_classes[] = $text_space;
}
if ($text_weight !== '') {
	$li_classes[] = 'font-weight-' . $text_weight;
}
if ($text_size === 'custom' && $heading_custom_size !== '') {
	$li_classes[] = 'fontsize-' . $uncode_shortcode_id . '-custom';
	$li_classes[] = 'font-size-custom';
}
if ($text_transform !== '') {
	$li_classes[] = 'text-' . $text_transform;
}
if ($text_italic === 'yes') {
	$li_classes[] = 'text-italic';
}

if ( $descriptions_color ) {
	$desc_classes[] = 'text-' . $descriptions_color . '-color';
}

if ( $text_size === 'custom' && $heading_custom_size !== '' ) {
	$font_style_array = array(
		'id'         => $uncode_shortcode_id,
		'font_size'  => $heading_custom_size
	);
	if ($text_italic === 'yes') {
		$font_style_array['font_style'] = 'italic';
	}
	$inline_style_css .= uncode_get_dynamic_css_font_size_shortcode( $font_style_array );
}

if ( 
	($description === 'yes' && $description_custom_size !== '')
	||
	$menu_gap !== ''
	||
	$titles_gap !== ''
	||
	$nested_list_gap !== ''
	||
	$indent !== ''
	||
	$titles_top !== ''
	||
	$nested_top !== ''
	||
	$font_icon_size !== ''
	||
	$media_icon_size !== ''
	||
	($background_style !== '' && $bg_size !== '')
) {
	$classes[] = 'menu-block-' . $uncode_shortcode_id . '-custom';

	$menu_block_data_array = array(
		'id' => $uncode_shortcode_id,
		'menu_gap' => $menu_gap,
		'titles_gap' => $titles_gap,
		'nested_list_gap' => $nested_list_gap,
		'indent' => $indent,
		'titles_top' => $titles_top,
		'nested_top' => $nested_top,
		'desc_size' => $description_custom_size,
		'icon_size' => $font_icon_size,
		'media_size' => $media_icon_size,
		'bg_size' => $bg_size,
	);
	$inline_style_css .= uncode_get_dynamic_css_menu_block_shortcode( $menu_block_data_array );
}

global $uncode_in_navbar, $metabox_data;
$menu_block_set = array();

if ( $no_link === 'yes' ) {
	$menu_block_set['no_link'] = true;
}

if ( $title_semantic !== '' ) {
	$menu_block_set['title_semantic'] = $title_semantic;
}

$icon_accordion_classes = array();
if ( $title_accordion !== '' ) {
	$menu_block_set['accordion'] = true;
	if ( $accordion_icon !== '' ) {
		$menu_block_set['accordion_icon'] = $accordion_icon;

		if ( $accordion_icon_close !== '' ) {
			$menu_block_set['accordion_icon_close'] = $accordion_icon_close;
		}
		if ( $icon_accordion_size !== '' ) {
			$icon_accordion_classes[] = 'fontsize-icon-' . $uncode_shortcode_id . '-custom';
			$icon_accordion_classes[] = 'font-size-custom';

			$icon_style_array = array(
				'id'         => 'icon-' . $uncode_shortcode_id,
				'font_size'  => $icon_accordion_size
			);
			$inline_style_css .= uncode_get_dynamic_css_font_size_shortcode( $icon_style_array );
		}
		if ( $icon_accordion_min_width !== '' ) {
			$menu_block_set['icon_accordion_min_width'] = $icon_accordion_min_width;
		}

		if ( $icon_accordion_color !== '' ) {
			$icon_accordion_classes[] = 'icon-' . $icon_accordion_color;
		}
	}
}

$menu_block_set['title_class'] = esc_attr(trim(implode( ' ', array_unique($title_classes) )));
$menu_block_set['first_title_class'] = esc_attr(trim(implode( ' ', array_unique($first_title_classes) )));
$menu_block_set['sec_title_class'] = esc_attr(trim(implode( ' ', array_unique($sec_title_classes) )));
$menu_block_set['li_class'] = esc_attr(trim(implode( ' ', array_unique($li_classes) )));
$menu_block_set['item_class'] = esc_attr(trim(implode( ' ', array_unique($item_classes) )));
$menu_block_set['a_class'] = esc_attr(trim(implode( ' ', $a_classes )));
$menu_block_set['desc_class'] = esc_attr(trim(implode( ' ', array_unique($desc_classes) )));
$menu_block_set['accordion_icon_class'] = esc_attr(trim(implode( ' ', array_unique($icon_accordion_classes) )));

$menu_block_set['icon'] = ($icon !== 'yes');

if ( $icon !== 'yes' ) {

	if ( $icon_replace !== '' ) {
		if ( $icon_replace === 'icon' && $icon_alt !== '' ) {
			$menu_block_set['icon_alt'] = $icon_alt;
		} elseif ( $icon_replace === 'media' && $media !== '' ) {
			$menu_block_set['icon_media'] = $media;
		}  
	}
	$icon_class = '';
	if ( strlen( $background_style ) > 0 ) {
		$icon_class .= 'fa fa-stack';
		$icon_class .= ' ' . $background_style;
		$menu_block_set['icon_background_style'] = $background_style;
	} else {
		$icon_class .= 'fa-fw';
	}
	if ( $icon_space_reduce === 'yes' ) {
		$icon_class .= ' space-sm';
	}
	if ( $icon_color !== '' ) {
		if ( $icon_bg_color !== '' ) {
			$icon_class .= ' text-' . $icon_color . '-color';
		} else {
			$icon_class .= ' icon-' . $icon_color;
		}
	}
	if ( $icon_bg_color !== '' ) {
		$icon_class .= ' after-bg-' . $icon_bg_color . '-color icon-bg-custom';
	}
	if ( $icon_class !== '' ) {
		$menu_block_set['icon_style'] = $icon_class;
	}
	if ( $icon_align !== '' ) {
		$menu_block_set['a_class'] .= ' align-center';
	}
}

if ( $description === 'yes' ) {
	$menu_block_set['description'] = true;
}
$menu_block_set['description'] = ($description === 'yes');

if ( $badge === 'yes' ) {
	$menu_block_set['badge'] = true;
	if ( $badge_pos !== '' ) {
		$classes[] = 'badge-absolute';
	}
}

if ( $menu_layout !== '' ) {
	$menu_columns = explode(',', $menu_layout);
	$def_cols = isset($menu_columns[0]) ? floatval($menu_columns[0]) : 3;
	if ( isset($menu_columns[0]) ) {
		$menu_style .= '--block_menu_col_l: ' . $def_cols . ';';
	}
	if ( isset($menu_columns[1]) ) {
		$menu_style .= '--block_menu_col_m: ' . floatval($menu_columns[1]) . ';';
		$def_cols = $menu_columns[1];
	} else {
		$menu_style .= '--block_menu_col_m: ' . $def_cols . ';';
	}
	if ( isset($menu_columns[2]) ) {
		$menu_style .= '--block_menu_col_s: ' . floatval($menu_columns[2]) . ';';
	} else {
		$menu_style .= '--block_menu_col_s: ' . $def_cols . ';';
	}

	if ( $columns_first === 'yes' ) {
		$menu_block_set['columns_first'] = true;
		$classes[] = 'first-grid';
	}

	switch ($gutter_size) {
		case '0':
			$classes[] = 'grid-no-gutter';
			break;

		case '1':
			$classes[] = 'grid-one-gutter';
			break;

		case '2':
			$classes[] = 'grid-half-gutter';
			break;

		case '4':
			$classes[] = 'grid-double-gutter';
			break;
	}

}

if ( $menu_style ) {
	$menu_style = ' style="' . $menu_style . '"';
}

if ($desktop_visibility === 'yes') {
	$classes[] = 'desktop-hidden';
}
if ($medium_visibility === 'yes') {
	$classes[] = 'tablet-hidden';
}
if ($mobile_visibility === 'yes') {
	$classes[] = 'mobile-hidden';
}
if ($mobile === 'yes') {
	$classes[] = 'menu-smart menu-primary-inner';
}

$hook_id = $hook_id === '' ? $uncode_shortcode_id : esc_attr( $hook_id );

if ( $slug === 'yes' ) {
	$nav_menu = $nav_menu_slug;
}

$nav_menu = apply_filters( 'uncode_menu_block_nav_menu_id', $nav_menu, $hook_id );

if ( $nav_menu === '' ) {
	$output_nav = '';
} else {
	if ( $nav_menu === 'inherit' ) {
		if (isset($metabox_data['_uncode_specific_menu'][0]) && $metabox_data['_uncode_specific_menu'][0] !== '') {
			$nav_menu = $metabox_data['_uncode_specific_menu'][0];
		} else {
			$post_type = isset( $post->post_type ) ? $post->post_type : 'post';
			$menu_generic = ot_get_option( '_uncode_'.$post_type.'_menu');
			if ($menu_generic !== '') {
				$nav_menu = $menu_generic;
			} else {
				$nav_menu = '';
				$theme_locations = get_nav_menu_locations();
				if (isset($theme_locations['primary'])) {
					$menu_obj = get_term( $theme_locations['primary'], 'nav_menu' );
					if (isset($menu_obj->name)) {
						$nav_menu = $menu_obj->name;
					}
				}
			}
		}		
	} 
	$menu_items = wp_get_nav_menu_items( $nav_menu, [ 'order' => 'ASC', 'output' => ARRAY_A ] );
	if ( is_array($menu_items) ) {
		foreach ( $menu_items as $item ) {
			if ( $item->menu_item_parent != 0 ) {
				$menu_block_set['has_titles'] = true;
				break;
			}
		}
	}

	$menu_walker = new uncode_block_navwalker();
	$menu_args = array(
		"menu" => $nav_menu,
		"container" => "false",
		"walker" => $menu_walker,
		'menu_block_set' => $menu_block_set,
		'fallback_cb' => false,
		'items_wrap' => '<ul id="%1$s" class="%2$s ' . esc_attr(trim(implode( ' ', $classes ))) . '" role="menu"' . $menu_style . '>%3$s</ul>',
		"depth" => 0,
		"echo" => 0
	);
	$output_nav = wp_nav_menu($menu_args);

	$is_grid = strpos($output_nav, ' is-grid') !== false ? '' : ' is-grid';

	$output_nav = preg_replace(
		'#<ul([^>]*)class="([^"]*)"#',
		'<ul$1class="$2' . $is_grid . '"',
		$output_nav,
		1
	);

}

$output .= $output_nav;

// $output .= wp_nav_menu(
// 	array(
// 		"menu" => $nav_menu,
// 		"container" => "false",
// 		"walker" => $menu_walker,
// 		'fallback_cb' => false,
// 		'menu_block_set' => $menu_block_set,
// 		'items_wrap' => '<ul id="%1$s" class="%2$s ' . esc_attr(trim(implode( ' ', $classes ))) . '" data-depth="' .$max_depth .'" role="menu"' . $menu_style . '>%3$s</ul>',
// 		"depth" => 0,
// 		"echo" => 0
// 	)
// );

$output .= uncode_print_dynamic_inline_style( $inline_style_css );
if ( $uncode_in_navbar !== true || ( function_exists('vc_is_page_editable') && vc_is_page_editable() ) ) {
	$output = '<div class="main-menu-container">
		<div class="menu-horizontal-inner">
			<ul class="menu-smart unmenu-block-in-page"><li>' . $output . '</li></ul>
		</div>
	</div>';
}
echo uncode_remove_p_tag($output);
