<?php
/**
 * WooCommerce settings for theme.
 *
 * @package wp-theme
 */

if (class_exists( 'WooCommerce' )) {
    add_action( 'after_setup_theme', 'woocommerce_support' );


    /**
     * Adds WooCommerce support to the theme.
     */
    function woocommerce_support() {
        add_theme_support( 'woocommerce' );
    }


}
