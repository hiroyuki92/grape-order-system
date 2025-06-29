<?php
/**
 * Storefront Child Theme Functions
 * 整理版 - 機能別にファイル分割済み
 */

// ===========================================
// 基本テーマ設定
// ===========================================

function storefront_child_enqueue_styles() {
    // 親テーマのスタイル
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    
    // Google Fonts
    wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&family=Playfair+Display:wght@400;700&family=Hannari&display=swap', array(), null );
    
    // 子テーマのスタイル
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style', 'google-fonts') );
}
add_action( 'wp_enqueue_scripts', 'storefront_child_enqueue_styles' );

function add_hero_banner_after_header() {
    if (is_front_page()) {
        echo '<div class="top-hero-banner">';
        echo '<img src="' . get_stylesheet_directory_uri() . '/images/hero-banner.png" alt="Sano Farm - Shine Muscat" class="hero-banner-image">';
        echo '</div>';
    }
}
add_action('storefront_before_content', 'add_hero_banner_after_header');

// ===========================================
// WooCommerce機能の読み込み
// ===========================================

// 修正：子テーマのパスを使用
$includes_dir = get_stylesheet_directory() . '/includes/';

// 郵便番号自動入力機能
if (file_exists($includes_dir . 'woocommerce-postcode.php')) {
    require_once $includes_dir . 'woocommerce-postcode.php';
}

// ブロック機能無効化
if (file_exists($includes_dir . 'woocommerce-blocks.php')) {
    require_once $includes_dir . 'woocommerce-blocks.php';
}

// 住所表示カスタマイズ
if (file_exists($includes_dir . 'woocommerce-address.php')) {
    require_once $includes_dir . 'woocommerce-address.php';
}

// 領収書機能
if (file_exists($includes_dir . 'woocommerce-receipt.php')) {
    require_once $includes_dir . 'woocommerce-receipt.php';
}

// 注文ステータス機能
if (file_exists($includes_dir . 'woocommerce-order-status.php')) {
    require_once $includes_dir . 'woocommerce-order-status.php';
}

// カート配送先住所非表示機能
if (file_exists($includes_dir . 'woocommerce-cart-shipping.php')) {
    require_once $includes_dir . 'woocommerce-cart-shipping.php';
}

// その他カスタマイズ
if (file_exists($includes_dir . 'woocommerce-customization.php')) {
    require_once $includes_dir . 'woocommerce-customization.php';
}