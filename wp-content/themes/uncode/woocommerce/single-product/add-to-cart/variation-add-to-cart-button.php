<?php
/**
 * Single variation cart button
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 10.2.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
?>
<div class="woocommerce-variation-add-to-cart variations_button">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<?php
	do_action( 'woocommerce_before_add_to_cart_quantity' );

	woocommerce_quantity_input( array(
		'min_value'   => $product->get_min_purchase_quantity(),
		'max_value'   => $product->get_max_purchase_quantity(),
		'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
	) );

	do_action( 'woocommerce_after_add_to_cart_quantity' );
	?>

	<?php // BEGIN UNCODE EDIT
		$button_class = isset( $button_class ) ? $button_class : 'alt btn btn-default';
	?>
	<button type="submit" class="single_add_to_cart_button button <?php echo esc_attr( uncode_btn_style() ); ?> <?php echo esc_attr( $button_class ); ?><?php echo esc_attr( uncode_wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . uncode_wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"><span><?php echo esc_html( $product->single_add_to_cart_text() ); ?></span></button>
	<?php // END UNCODE EDIT ?>

	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

	<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="variation_id" class="variation_id" value="0" />
</div>
