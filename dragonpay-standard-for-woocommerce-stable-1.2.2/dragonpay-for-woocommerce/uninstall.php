<?php
/**
 * WooCommerce Dragonpay Payment Gateway
 * By SiteShop.ph <support@siteshop.ph>
 * 
 * Uninstall - removes all options from DB when user deletes the plugin via WordPress backend.
 * @since 1.0.0
 * 
 **/
 

// If uninstall not called from WordPress exit
if ( !defined('WP_UNINSTALL_PLUGIN') ) {
    exit();
}

delete_option( 'woocommerce_dragonpay_settings' );		
