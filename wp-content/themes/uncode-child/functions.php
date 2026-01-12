<?php

// Child theme styles
add_action('wp_enqueue_scripts', 'uncode_child_styles', 999);
function uncode_child_styles()
{
	//wp_dequeue_style( 'parent-style' );
	wp_enqueue_style('child-theme-style', get_stylesheet_directory_uri() . '/style.css',  false,  filemtime(get_stylesheet_directory()  . '/style.css'));
}

// Set custom version for styles containing 'themes/uncode' in the URL for cache busting
function custom_scripts_cache_busting($src)
{
	if (strpos($src, 'themes/uncode/') !== false) {
		$parts = explode('?ver', $src);
		$src = $parts[0] . '?ver=' . filemtime(get_template_directory() . '/style.css');
	}
	return $src;
}
add_filter('script_loader_src', 'custom_scripts_cache_busting', 15, 1);

// Set custom version for styles containing 'themes/uncode' in the URL for cache busting
function custom_stylesheet_cache_busting($src)
{
	if (strpos($src, 'themes/uncode/') !== false) {
		$parts = explode('?ver', $src);
		$src = $parts[0] . '?ver=' . filemtime(get_template_directory() . '/style.css');
	}
	return $src;
}
add_filter('style_loader_src', 'custom_stylesheet_cache_busting', 15, 1);

// Set custom version for uncode script for cache busting *WP Charged
function uncode_script_cache_busting($src)
{
	if (strpos($src, '/core/assets/css/admin-custom.css') !== false) {
		$src = $src . '?v=' . filemtime(get_template_directory()  . '/core/assets/css/admin-custom.css');
	}
	return $src;
}
add_filter('style_loader_src', 'uncode_script_cache_busting', 15, 1);


add_filter('uncode_ot_get_option_uncode_production', function () {
	return 'on';
});


// Don't need these image sizes
function remove_image_sizes() {
    remove_image_size( 'uncode_woocommerce_nav_thumbnail_regular' );
    remove_image_size( 'uncode_woocommerce_nav_thumbnail_crop' );
}
add_action( 'init', 'remove_image_sizes' );


// Disable widget cart in uncode
add_filter( 'woocommerce_widget_cart_is_hidden', '__return_true', 100 );
add_filter( 'uncode_woocommerce_sidecart_enabled', '__return_true', 100 ); // Seems uncode will only disable the cart if this is true. Might need to be checked on updates if they change this.
add_filter( 'uncode_woocommerce_sidecart_mobile_enabled', '__return_true', 100 );

// Disable uncode dynamic images
add_filter( 'uncode_ot_get_option_uncode_adaptive', function(){ return 'off';});

include_once get_stylesheet_directory() . '/global/bw-constant.php';

//  only if ARMember plugin is active
if ( is_plugin_active( 'armember/armember.php' ) || class_exists( 'ARMEMBERPLUGIN' ) ) {

	// Include BW Dashboard
    require_once get_stylesheet_directory() . '/bw-dashboard/bw-dashboard.php';
	
	//Include BW Members Directory
    require_once get_stylesheet_directory() . '/bw-members/bw-members-listing.php';
}

/**
 * Adds a custom logo to the admin login page.
 * @return void
 */
function bw_logo_on_admin_login() {
    ?>
    <style>
        body.login div#login h1.wp-login-logo a {
            background-image: url('<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/logo.webp' ); ?>') !important;
            height: 85px !important;
        }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'bw_logo_on_admin_login');