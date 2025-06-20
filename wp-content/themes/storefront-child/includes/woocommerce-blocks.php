<?php
/**
 * WooCommerceブロック機能の無効化
 */

if (!defined('ABSPATH')) {
    exit;
}

// Japanized for WooCommerce のブロック版機能を無効化
add_action('wp_enqueue_scripts', function() {
    // Japanized関連のブロック版スクリプトを無効化
    wp_dequeue_script('jp4wc-cod-wc-blocks');
    wp_deregister_script('jp4wc-cod-wc-blocks');
    
    // WooCommerceブロック版も無効化
    wp_dequeue_script('wc-blocks-data');
    wp_dequeue_script('wc-blocks-checkout');
    wp_dequeue_script('wc-blocks-cart');
    
    wp_deregister_script('wc-blocks-data');
    wp_deregister_script('wc-blocks-checkout');
    wp_deregister_script('wc-blocks-cart');
}, 999);

// WooCommerceブロック版機能を完全無効化
add_filter('woocommerce_feature_enabled', function($enabled, $feature) {
    $block_features = [
        'checkout_blocks',
        'cart_checkout_blocks',
        'experimental_blocks'
    ];
    
    if (in_array($feature, $block_features)) {
        return false;
    }
    
    return $enabled;
}, 10, 2);