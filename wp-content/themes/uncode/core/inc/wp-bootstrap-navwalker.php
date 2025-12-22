<?php

/**
 * Class Name: wp_bootstrap_navwalker
 * GitHub URI: https://github.com/twittem/wp-bootstrap-navwalker
 * Description: A custom WordPress nav walker class to implement the Bootstrap 3 navigation style in a custom theme using the WordPress built in menu manager.
 * Version: 2.0.4
 * Author: Edward McIntyre - @twittem
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

class wp_bootstrap_navwalker extends Walker_Nav_Menu {

	/**
	 * @see Walker::start_lvl()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of page. Used for padding.
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		global $megamenu, $megachildren;

		$extra_class = '';
		if ( isset($args->extra_class) ) {
			$extra_class = join( ' ', $args->extra_class );
		}

		$indent = str_repeat( "\t", $depth );
		if ( $megamenu == 'megamenu' ) {
			switch ($megachildren) {
				case 1:
					$columns = 'mega-menu-one';
					break;
				case 2:
					$columns = 'mega-menu-two';
					break;
				case 3:
					$columns = 'mega-menu-three';
					break;
				case 4:
					$columns = 'mega-menu-four';
					break;
				case 5:
					$columns = 'mega-menu-five';
					break;
				case 6:
					$columns = 'mega-menu-six';
					break;
				case 7:
					$columns = 'mega-menu-seven';
					break;
				case 8:
					$columns = 'mega-menu-eight';
					break;
				default:
					$columns = '';
					break;
			}
			$output .= "\n$indent<ul role=\"menu\" class=\"mega-menu-inner un-submenu need-focus in-mega $columns $extra_class\" data-lenis-prevent>\n";
		} else {
			$output .= "\n$indent<ul role=\"menu\" class=\"drop-menu un-submenu $extra_class\" data-lenis-prevent>\n";
		}
	}

	/**
	 * @see Walker::start_el()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item Menu item data object.
	 * @param int $depth Depth of menu item. Used for padding.
	 * @param int $current_page Menu item ID.
	 * @param object $args
	 */
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		global $megamenu, $megachildren, $wpdb, $menutype;

		if ( isset($item->type) && $item->type === 'nav_menu' && !empty($item->object_id) ) {
			$menu_args = array(
				"menu" => $item->object_id,
				"container" => "false",
				"walker" => new wp_bootstrap_navwalker(),
				'fallback_cb' => false,
				'items_wrap' => '%3$s',
				'more_depth' => $depth,
				"depth" => 0,
				"echo" => 0
			);
			$menu_html = wp_nav_menu($menu_args);
			$parent_classes = empty( $item->classes ) ? array() : (array) $item->classes;
			$parent_classes = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $parent_classes ), $item, $args ) );

			$menu_html = preg_replace(
				'/<ul([^>]*)class="([^"]*)"/',
				'<ul$1class="$2 unmenu-composite ' . $parent_classes . '"',
				$menu_html
			);
			$output .= $menu_html;
			return;
		}

		$_depth = $depth;

		if ( isset($args->more_depth) ) {
			$_depth = $_depth + $args->more_depth;
		}

		$description = '';
		$icon_html = '';
		$badge_html = '';
		$megamenu = $item->megamenu;
		if ($megamenu == 'megamenu') {
			if ($args->has_children) {
				$megachildren_results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %d", '_menu_item_menu_item_parent', $item->ID));

				$megachildren = 0;

				if ( is_array( $megachildren_results ) ) {
					foreach	( $megachildren_results as $megachildren_result ) {
						$megachildren_obj_id = $megachildren_result->post_id;

						$megachildren_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->term_relationships WHERE object_id = %d", $megachildren_obj_id));

						if ( $megachildren_exists ) {
							$megachildren++;
						}
					}
				}
			}
		}
		$indent = ( $_depth ) ? str_repeat( "\t", $_depth ) : '';

		if ($item->description !== '' && ($menutype === 'menu-overlay' || $menutype === 'menu-overlay-center')) {
		// if ($item->description !== '') {
				$description = '<span class="menu-item-description depth-' . $_depth . '">' . $item->description . '</span>';
		}

		/**
		 * Get the icon
		 */
		if ( ! empty( $item->icon )) {
			$icon_html = '<i class="menu-icon ' . esc_attr( $item->icon ) . '"></i>';
		}

		if ( ! empty( $item->media ) ) {
			$icon_html = '<span class="menu-icon menu-media">' . wp_get_attachment_image( esc_attr( $item->media ), 'full' ) . '</span>';
		} 

		/**
		 * Get badge
		 */
		if ( apply_filters( 'uncode_activate_menu_badges', false ) ) {
			$badge_html = uncode_print_menu_badge_item( $item );
		}

		/**
		 * Dividers, Headers or Disabled
		 * =============================
		 * Determine whether the item is a Divider, Header, Disabled or regular
		 * menu item. To prevent errors we use the strcasecmp() function to so a
		 * comparison that is not case sensitive. The strcasecmp() function returns
		 * a 0 if the strings are equal.
		 */
		if ( ! is_null( $item->attr_title ) && strcasecmp( $item->attr_title, 'divider' ) == 0 && $_depth === 1 ) {
			$output .= $indent . '<li role="presentation" class="divider">';
		} else if ( ! is_null( $item->title ) && strcasecmp( $item->title, 'divider') == 0 && $_depth === 1 ) {
			$output .= $indent . '<li role="presentation" class="divider">';
		} else if ( ! is_null( $item->attr_title ) && strcasecmp( $item->attr_title, 'dropdown-header') == 0 && $_depth === 1 ) {
			$output .= $indent . '<li role="presentation" class="dropdown-header">' . esc_attr( $item->title );
		} else if ( ! is_null( $item->attr_title ) && strcasecmp($item->attr_title, 'disabled' ) == 0 ) {
			$output .= $indent . '<li role="presentation" class="disabled"><a href="#"><span>' . esc_attr( $item->title ) . '</span></a>';
		} else {

			$class_names = $value = '';

			$classes = empty( $item->classes ) ? array() : (array) $item->classes;

			$classes[] = 'depth-' . $_depth;
			$classes[] = 'menu-item-' . $item->ID;
			if ($item->button) {
				$classes[] = 'menu-btn-container';
			}

			if ( $args->has_children ) {
				$classes[] = 'dropdown';
			}

			if ( in_array( 'current-menu-item', $classes )) {
				$parse_link = parse_url($item->url);
				if (!isset($parse_link['fragment'])) {
					$classes[] = 'active';
				}
			}

			if ($item->button) {
				$classes[] = 'btn';
			} else {
				if ($_depth === 0) {
					$classes[] = 'menu-item-link';
				}
			}
			$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );

			$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

			$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
			$raw_id = intval( $item->ID );
			$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

			$megamenu_check = false;
			if ($_depth > 0 && isset($item->object) && $item->object === 'uncodeblock' && isset($item->object_id) ) {
				$megamenu_check = get_post_meta( $item->object_id, '_uncode_specific_megamenu_check', true );
			}

			if ($megamenu_check === 'on') {
				$megamenu_animation = get_post_meta( $item->object_id, '_uncode_specific_megamenu_animation', true );
				if ( $megamenu_animation === '' ) {
					if ( ot_get_option( '_uncode_menu_li_animation' ) === 'on' ) {
						$data_megamenu_block = 'animate_when_almost_visible slight-anim';
					} else {
						$data_megamenu_block = '';
					}
				} elseif ( $megamenu_animation === 'yes' ) { 
					$data_megamenu_block = 'animate_when_almost_visible slight-anim check';
				} elseif ( $megamenu_animation === 'no' ) { 
					$data_megamenu_block = 'no-anim';
				} else { 
					$data_megamenu_block = 'animate_when_almost_visible ' . $megamenu_animation;
				}

				if ( $data_megamenu_block !== '' ) {
					$data_megamenu_block = ' data-block="' . esc_html( $data_megamenu_block ) . '"';
				}

				$megamenu_event = get_post_meta( $item->object_id, '_uncode_specific_megamenu_event', true );
				$block_extra_classes = $megamenu_event !== '' ? ' trigger-' . $megamenu_event : '';

				$megamenu_mobile_top = get_post_meta( $item->object_id, '_uncode_specific_megamenu_space_top', true );
				$block_extra_classes .= $megamenu_mobile_top === 'on' ? ' block-mobile-top' : '';

				$megamenu_mobile_bottom = get_post_meta( $item->object_id, '_uncode_specific_megamenu_space_bottom', true );
				$block_extra_classes .= $megamenu_mobile_bottom === 'on' ? ' block-mobile-bottom' : '';

				$output .= '<li' . $id . ' class="megamenu-block-wrapper' . $block_extra_classes . '"' . $data_megamenu_block . '>';
				$mega_block = get_post_field('post_content', $item->object_id);
				$output .= $mega_block;
			} elseif ( ! is_null( $item->megamenu ) && strcasecmp($item->megamenu, 'megamenu' ) == 0 && $_depth === 0 ) {
				$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
				$class_names = $class_names ? ' ' . esc_attr( $class_names ) : '';

				$output .= $indent . '<li role="menuitem" ' . $id . ' class="mega-menu'.$class_names.'">';

			} else {
				$output .= $indent . '<li role="menuitem" ' . $id . $value . (!$item->button ? $class_names : ' class="menu-item-button"') . '>';
			}

			$atts = array();
			$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
			$atts['target'] = ! empty( $item->target )	? $item->target	: '';
			$atts['rel']    = ! empty( $item->xfn )		? $item->xfn	: '';

			// If item has_children add atts to a.
			if ( $args->has_children && $_depth === 0 ) {
				$atts['href'] = ! empty( $item->url ) ? $item->url : '#';
				$atts['data-toggle']	= 'dropdown';
				$atts['class']			= 'dropdown-toggle';
			} else {
				$atts['href'] = ! empty( $item->url ) ? $item->url : '';
			}

			if ( $badge_html ) {
				$atts['class'] = isset( $atts['class'] ) ? $atts['class'] . ' has-badge' : 'has-badge';
			}

			if ( ( !isset($atts['role']) || $atts['role'] === '' ) && ( empty( $item->url ) || $item->url === '#' ) ) {
				$atts['role'] = 'button';
			}

			$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args );

			$attributes = '';
			foreach ( $atts as $attr => $value ) {
				if ( ! empty( $value ) ) {
					$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
					$attributes .= ' ' . $attr . '="' . $value . '"';
				}
			}

			$item_output = $args->before;

			/*
			 * Glyphicons
			 * ===========
			 * Since the the menu item is NOT a Divider or Header we check the see
			 * if there is a value in the attr_title property. If the attr_title
			 * property is NOT null we apply it as the class name for the glyphicon.
			 */
			if ($megamenu_check !== 'on') {
				if (!isset($item->logo) && !$item->logo) {
					if ( $icon_html !== '' && ! $item->button ) {
						$item_output .= '<a'. $attributes .'>' . $icon_html;
					} else {
						$item_output .= ( $args->has_children) ? '<a'. $attributes .' data-type="title">' : '<a'. $attributes .'>';
					}

					if ($item->button) {
						$item_output .= '<div class="menu-btn-table"><div class="menu-btn-cell"><div'.$class_names.'><span>' . $args->link_before . $icon_html . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after . '</span></div></div></div>' . $badge_html . $description . '</a>';
					} else {
						$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $badge_html . $args->link_after;
						$item_output .= ( $args->has_children) ? '<i class="fa fa-angle-down fa-dropdown"></i>' . $description . '</a>' : '<i class="fa fa-angle-right fa-dropdown"></i>' . $description . '</a>';
					}
				} else {
					$item_output .= $item->title;
				}
			}

			$item_output .= $args->after;

			$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $_depth, $args );
		}
	}

	/**
	 * Ends the element output, if needed.
	 *
	 * @since 3.0.0
	 * @since 5.9.0 Renamed `$item` to `$data_object` to match parent class for PHP 8 named parameter support.
	 *
	 * @see Walker::end_el()
	 *
	 * @param string   $output      Used to append additional content (passed by reference).
	 * @param WP_Post  $data_object Menu item data object. Not used.
	 * @param int      $depth       Depth of page. Not Used.
	 * @param stdClass $args        An object of wp_nav_menu() arguments.
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
		global $mega_block_depth;
		if ( $mega_block_depth === $depth ) {
			$mega_block_depth = false;
		} else {
			if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
				$t = '';
				$n = '';		
			} else {
				$t = "\t";
				$n = "\n";
			}
			$output .= "</li>{$n}";
		}
	}

	/**
	 * Traverse elements to create list from elements.
	 *
	 * Display one element if the element doesn't have any children otherwise,
	 * display the element and its children. Will only traverse up to the max
	 * depth and no ignore elements under that depth.
	 *
	 * This method shouldn't be called directly, use the walk() method instead.
	 *
	 * @see Walker::start_el()
	 * @since 2.5.0
	 *
	 * @param object $element Data object
	 * @param array $children_elements List of elements to continue traversing.
	 * @param int $max_depth Max depth to traverse.
	 * @param int $depth Depth of current element.
	 * @param array $args
	 * @param string $output Passed by reference. Used to append additional content.
	 * @return null Null on failure with no changes to parameters.
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
        if ( ! $element ) {
            return;
        }

        $id_field = $this->db_fields['id'];

        // Display this element.
        if ( is_object( $args[0] ) ) {
           $args[0]->has_children = ! empty( $children_elements[ $element->$id_field ] );
        }

        parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
    }

	/**
	 * Menu Fallback
	 * =============
	 * If this function is assigned to the wp_nav_menu's fallback_cb variable
	 * and a manu has not been assigned to the theme location in the WordPress
	 * menu manager the function with display nothing to a non-logged in user,
	 * and will add a link to the WordPress menu manager if logged in as an admin.
	 *
	 * @param array $args passed from the wp_nav_menu function.
	 *
	 */
	public static function fallback( $args ) {
		if ( current_user_can( 'manage_options' ) ) {

			extract( $args );

			$fb_output = null;

			if ( $container ) {
				$fb_output = '<' . $container;

				if ( $container_id ) {
					$fb_output .= ' id="' . $container_id . '"';
				}

				if ( $container_class ) {
					$fb_output .= ' class="' . $container_class . '"';
				}

				$fb_output .= '>';
			}

			$fb_output .= '<ul';

			if ( $menu_id ) {
				$fb_output .= ' id="' . $menu_id . '"';
			}

			if ( $menu_class ) {
				$fb_output .= ' class="' . $menu_class . '"';
			}

			$fb_output .= '>';
			$fb_output .= '<li><a href="' . admin_url( 'nav-menus.php' ) . '">Add a menu</a></li>';
			$fb_output .= '</ul>';

			if ( $container ) {
				$fb_output .= '</' . $container . '>';
			}

			return $fb_output;
		}
	}
}

/**
* Navigation Menu widget class extended
*
* @since 3.0.0
*/
class Uncode_Nav_Menu_Widget extends WP_Nav_Menu_Widget {

	function widget($args, $instance) {

		// Get menu
		$nav_menu = ! empty( $instance['nav_menu'] ) ? wp_get_nav_menu_object( $instance['nav_menu'] ) : false;

		if ( !$nav_menu ) {
			return;
		}

		$instance['title'] = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		echo wp_kses_post( $args['before_widget'] );

		if ( !empty($instance['title']) ) {
			echo wp_kses_post( $args['before_title'] . $instance['title'] . $args['after_title'] );
		}

		wp_nav_menu( array( 'fallback_cb' => '', 'menu' => $nav_menu, 'menu_class' => isset($args['menu_class']) ? $args['menu_class'] : 'menu' ) );

		echo wp_kses_post( $args['after_widget'] );
	}

}

if ( function_exists( 'uncode_custom_menu_widget' ) ) {
	add_action("widgets_init", "uncode_custom_menu_widget");
}

/**
* Additional mobile menu elements
*
* @since 2.8.0
*/
if ( ! function_exists( 'uncode_mobile_menu_additional_elems' ) ) {
	function uncode_mobile_menu_additional_elems() {
		global $menutype;

		$nav_menu = $additional_text_visibility = '';

		$additional_textarea = ot_get_option('_uncode_vmenu_textarea');
		$additional_mobile_textarea = ot_get_option('_uncode_menu_mobile_centered_textarea');

		if ( ( strpos($menutype, 'vmenu') !== false || strpos($menutype, 'menu-overlay') !== false ) && $additional_textarea !== '' ) {
			$additional_textarea = wpautop($additional_textarea);
		} else {
			$additional_textarea = '';
		}

		if ( $additional_textarea === '' ) {
			$additional_text_visibility = 'desktop-hidden';
		}

		if ( ot_get_option('_uncode_menu_sticky_mobile') === 'on' && ot_get_option('_uncode_menu_mobile_centered') !== 'off' && $additional_mobile_textarea !== ''  ) {
			$additional_mobile_textarea = wpautop($additional_mobile_textarea);
		} else {
			$additional_mobile_textarea = '';
		}

		if ( $additional_mobile_textarea === '' ) {
			$additional_text_visibility = 'mobile-hidden tablet-hidden';
		}

		if ( $additional_textarea !== '' || $additional_mobile_textarea !== '' ) {
			$nav_block_class = uncode_get_menu_mobile_block() !== '' ? ' mobile-hidden tablet-hidden' : '';
			$nav_menu .= '<div class="uncode-menu-additional-text navbar-mobile-el ' . esc_attr($additional_text_visibility . $nav_block_class) . '">';
			if ( $additional_textarea !== '' ) {
				$nav_menu .= '<div class="mobile-hidden tablet-hidden">' . $additional_textarea . '</div>';
			}
			if ( $additional_mobile_textarea !== '' && uncode_get_menu_mobile_block() === '' ) {
				$nav_menu .= '<div class="desktop-hidden">' . $additional_mobile_textarea . '</div>';
			}
			$nav_menu .= '</div>';
		}

		$nav_menu .= '<div class="uncode-close-offcanvas-mobile lines-button close navbar-mobile-el"><span class="lines"></span></div>';

		return $nav_menu;
	}
}
add_filter("uncode_menu_before_socials", "uncode_mobile_menu_additional_elems", 10);
add_filter("uncode_menu_mobile_block_before_close", "uncode_mobile_menu_additional_elems", 10);

class uncode_block_navwalker extends Walker_Nav_Menu {
	public $has_grandchildren = [];

	public function start_lvl( &$output, $depth = 0, $args = array() ) {

		$menu_block_set = $args->menu_block_set;

		$indent = str_repeat( "\t", $depth );
        $gchildren = " is-grid";
		if ( !empty( $this->has_grandchildren[ $this->last_item_id ] ) || isset($menu_block_set['columns_first']) ) {
            $gchildren = "";
        }
		if ( empty( $this->has_grandchildren[ $this->last_item_id ] ) ) {
            $gchildren .= " no-grandchildren";
        }
		$output .= "\n$indent<ul role=\"menu\" class=\"sub-menu un-submenu unmenu-block" . $gchildren . "\" data-lenis-prevent>\n";
	}

	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
		if ( !empty( $children_elements[$element->ID] ) ) {
			foreach ( $children_elements[$element->ID] as $child ) {
				if ( $child->type === 'nav_menu' ) {
					$nav_menu_items = wp_get_nav_menu_items($child->object_id);
					foreach ( $nav_menu_items as $nav_menu_item ) {
						if ( $nav_menu_item->menu_item_parent != 0 ) {
							$this->has_grandchildren[ $element->ID ] = true;
							continue;
						}
					}
				}
				if ( !empty( $children_elements[$child->ID] ) ) {
					$this->has_grandchildren[ $element->ID ] = true;
					continue;
				}
			}
		}
        parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
    }
	
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {

		global $post;
		$current_page_id = isset($post->ID) ? $post->ID : null;

        $this->last_item_id = $item->ID;

		$menu_block_set = $args->menu_block_set;

		if ($item->type === 'nav_menu' && !empty($item->object_id)) {
			$menu_args = array(
				"menu" => $item->object_id,
				"container" => "false",
				"walker" => new uncode_block_navwalker(),
				'menu_block_set' => $args->menu_block_set,
				'fallback_cb' => false,
				'items_wrap' => '%3$s',
				'more_depth' => $depth,
				"depth" => 0,
				"echo" => 0
			);
			$output .= wp_nav_menu($menu_args);
			return;
		}

		$_depth = $depth;

		if ( isset($args->more_depth) ) {
			$_depth = $_depth + $args->more_depth;
		}

		$icon_color = '';
		$icon_class = array('menu-icon');

		$has_children = $args->walker->has_children;
		$is_parent = $has_children || ( isset($menu_block_set['has_titles']) && $menu_block_set['has_titles'] === true && $_depth === 0 );
		$is_accordion = isset($menu_block_set['accordion']) && $menu_block_set['accordion'] === true && $is_parent;
		$is_link = true;
		if ( isset($menu_block_set['no_link']) && $menu_block_set['no_link'] === true && $is_parent ) {
			$is_link = false;
		}
		
		$has_icon = isset($menu_block_set['icon']) && $menu_block_set['icon'] === true;

		$has_description = isset($menu_block_set['description']) && $menu_block_set['description'] === true;

		if ( isset($menu_block_set['icon_style']) ) {
			$icon_class[] = $menu_block_set['icon_style'];
		}

		$description = '';
		$icon_html = '';
		$badge_html = '';

		if ($item->description !== '' && $has_description) {
			$desc_class = 'menu-item-description depth-' . $_depth;
			if ( isset($menu_block_set['desc_class']) ) {
				$desc_class .= ' ' . esc_attr($menu_block_set['desc_class']);
			}
			$description = '<span class="' . $desc_class . '">' . $item->description . '</span>';
		}

		if ( $has_icon ) {
			$item_icon = false;
			$item_media = false;
			if ( isset($menu_block_set['icon_alt']) && $menu_block_set['icon_alt'] !== '' ) {
				$item_icon = $menu_block_set['icon_alt'];
			} elseif ( isset($menu_block_set['icon_media']) && $menu_block_set['icon_media'] !== '' ) {
				$item_media = $menu_block_set['icon_media'];
			} elseif ( ! empty( $item->media )) {
				$item_media = $item->media;
			} elseif ( ! empty( $item->icon ) ) {
				$item_icon = $item->icon;
			}

			$icon_color = '';
			if ( ! empty( $item->icon_color ) ) {
				$icon_color .= '--icon_color: ' . $item->icon_color . ';';
				$icon_class[] = 'icon-single-color';
			}
			if ( ! empty( $item->icon_bg ) ) {
				$icon_color .= '--icon_bg: ' . $item->icon_bg . ';';
				$icon_class[] = 'icon-single-bg';
			}
			if ( ! empty( $icon_color ) ) {
				$icon_color = ' style="' . $icon_color . '"';
			}

			if ( $item_media !== false ) {
				if ( isset($menu_block_set['icon_background_style']) && $menu_block_set['icon_background_style']!=='' ) {
					$icon_html = '<span class="menu-icon ' . esc_attr(trim(implode( ' ', $icon_class ))) . '"' . $icon_color . '><span class="menu-icon menu-media">' . wp_get_attachment_image( esc_attr( $item_media ), 'full' ) . '</span></span>';
				} else {
					$icon_html = '<span class="menu-icon menu-media ' . esc_attr(trim(implode( ' ', $icon_class ))) . '"' . $icon_color . '>' . wp_get_attachment_image( esc_attr( $item_media ), 'full' ) . '</span>';
				}
			} elseif ( $item_icon !== false ) {
				$icon_class[] = esc_attr( $item_icon );
				$icon_html = '<i class="' . esc_attr(trim(implode( ' ', $icon_class ))) . '"' . $icon_color . '></i>';
			}
		}

		if ( isset($menu_block_set['badge']) && $menu_block_set['badge'] === true ) {
			$badge_html = uncode_print_menu_badge_item( $item );
		}

		$class_names = $value = '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;
		if ($item->button) {
			$classes[] = 'menu-btn-container';
		}

		if ( in_array( 'current-menu-item', $classes )) {
			$parse_link = parse_url($item->url);
			if (!isset($parse_link['fragment'])) {
				$classes[] = 'active';
			}
		}

		if ($item->button) {
			$classes[] = 'btn';
		} else {
			if ($_depth === 0) {
				$classes[] = 'menu-item-link';
			}
		}

		$title_class = '';
		if ( isset($menu_block_set['title_class']) ) {
			$title_class .= ' ' . esc_attr($menu_block_set['title_class']);
		}
		if ( $_depth === 0 && isset($menu_block_set['first_title_class'])  ) {
			$title_class .= ' ' . esc_attr($menu_block_set['first_title_class']);
		} elseif ( $_depth > 0 && isset($menu_block_set['sec_title_class'])  ) {
			$title_class .= ' ' . esc_attr($menu_block_set['sec_title_class']);
		} 
		$title_class = isset($title_class) && $title_class !== '' ? ' class="' . esc_attr( $title_class ) . '"' : '';

		$classes[] = $menu_block_set['li_class'];
		if ( !$is_parent ) {
			$classes[] = $menu_block_set['item_class'];
		}
		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );

		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
		$raw_id = intval( $item->ID );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		$megamenu_check = false;
		if ($_depth > 0 && isset($item->object) && $item->object === 'uncodeblock' && isset($item->object_id) && $current_page_id !== $item->object_id ) {
			$megamenu_check = get_post_meta( $item->object_id, '_uncode_specific_megamenu_check', true );
		}

		// if ($megamenu_check === 'on') {
		// 	$megamenu_animation = get_post_meta( $item->object_id, '_uncode_specific_megamenu_animation', true );
		// 	$megamenu_animation = $megamenu_animation === '' ? ot_get_option( '_uncode_menu_li_animation' ) === 'on' : $megamenu_animation === 'yes';

		// 	$data_megamenu_block = $megamenu_animation === true ? 'animate_when_almost_visible slight-anim' : '';

		// 	$output .= '<li' . $id . ' class="megamenu-block-wrapper" data-block="' . esc_html( $data_megamenu_block ) . '">';
		// 	$mega_block = get_post_field('post_content', $item->object_id);
		// 	$output .= $mega_block;
		// } else {
			$output .= '<li role="menuitem" ' . $id . $value . (!$item->button ? $class_names : ' class="menu-item-button"') . '>';
		// }

		$atts = array();
		$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target'] = ! empty( $item->target )	? $item->target	: '';
		$atts['rel']    = ! empty( $item->xfn )		? $item->xfn	: '';

		$atts['href'] = ! empty( $item->url ) ? $item->url : '';

		if ( $badge_html ) {
			$atts['class'] = isset( $atts['class'] ) ? $atts['class'] . ' has-badge' : 'has-badge';
		}

		if ( is_array($menu_block_set) && isset($menu_block_set['a_class']) ) {
			if ( !isset($atts['class']) ) {
				$atts['class'] = '';
			}
			$atts['class'] .= ' ' . $menu_block_set['a_class'];
		}

		if ( ( !isset($atts['role']) || $atts['role'] === '' ) && ( empty( $item->url ) || $item->url === '#' ) ) {
			$atts['role'] = 'button';
		}

		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args );

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( ! empty( $value ) ) {
				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				if ( $has_children && $is_accordion && 'href' === $attr  ) {
					$value = '#';
				}
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$item_output = $args->before;

		$item_title = $item->title;

		$accordion_icon = '';
		if ( $has_children && $is_accordion && isset($menu_block_set['accordion_icon']) ) {
			$clps_wrpr_style = '';
			$accordion_icon_class = isset( $menu_block_set['accordion_icon_class'] ) ? ' ' . esc_attr( $menu_block_set['accordion_icon_class'] ) : '';
			if ( isset( $menu_block_set['icon_accordion_min_width'] ) && $menu_block_set['icon_accordion_min_width'] !== '' ) {
				$icon_accordion_min_width = $menu_block_set['icon_accordion_min_width'];
				if ( is_numeric($icon_accordion_min_width) ) {
					$icon_accordion_min_width .= 'px';
				}
				$clps_wrpr_style = ' style="min-width: ' . esc_html( $icon_accordion_min_width ) . '"';
			}
			$accordion_icon = '<div class="collapsible-icon-wrapper"' . $clps_wrpr_style . '>';
			if ( isset($menu_block_set['accordion_icon_close']) ) {
				$accordion_icon .= '<i class="collapsible-icon icon-open ' . esc_attr( $menu_block_set['accordion_icon'] ) . $accordion_icon_class . '"></i>';
				$accordion_icon .= '<i class="collapsible-icon icon-close ' . esc_attr( $menu_block_set['accordion_icon_close'] ) . $accordion_icon_class . '"></i>';
			} else {
				$accordion_icon .= '<i class="collapsible-icon ' . esc_attr( $menu_block_set['accordion_icon'] ) . $accordion_icon_class . '"></i>';
			}
			$accordion_icon .= '</div>';
		}

		if (!isset($item->logo) && !$item->logo) {
			if ( isset($menu_block_set['title_semantic']) && $menu_block_set['title_semantic'] !== '' && $is_parent ) {
				$item_output .= '<' . $menu_block_set['title_semantic'] . $title_class . '>';
			}

			if ( !( $has_children && $is_accordion ) && $is_link ) {
				$item_output .= '<a '. $attributes .'>';
			}

			if ( $icon_html ) {
				$item_output .= $icon_html;
			}

			if ($item->button && !$is_accordion) {
				$item_output .= '<div class="menu-btn-table"><div class="menu-btn-cell"><div'.$class_names.'><span>' . $args->link_before . $icon_html . apply_filters( 'the_title', $item_title, $item->ID ) . $args->link_after . '</span></div></div></div>' . $badge_html . $description;
			} else {
				$item_output .= '<span>' . $args->link_before . apply_filters( 'the_title', $item_title, $item->ID ) . $badge_html . $args->link_after;
				$item_output .= $description . '</span>';
			}

			if ( !( $has_children && $is_accordion ) && $is_link ) {
				$item_output .= '</a>';
			}

			if ( isset($menu_block_set['title_semantic']) && $menu_block_set['title_semantic'] !== '' && $is_parent ) {
				$item_output .= $accordion_icon . '</' . $menu_block_set['title_semantic'] . '>';
			}

		} else {
			if ( isset($menu_block_set['title_semantic']) && $menu_block_set['title_semantic'] !== '' && $is_parent ) {
				$item_title = '<' . $menu_block_set['title_semantic'] . $title_class . '>' . $item_title . $accordion_icon . '<' . $menu_block_set['title_semantic'] . '>';
			}
			$item_output .= $item_title;
		}

		$item_output .= $args->after;

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $_depth, $args );
	}
}