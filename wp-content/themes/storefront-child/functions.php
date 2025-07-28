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
        echo '<img src="' . get_stylesheet_directory_uri() . '/images/wide-top-banner.png" alt="Sano Farm - Shine Muscat" class="hero-banner-image">';
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

// 注文確認モーダル機能
if (file_exists($includes_dir . 'woocommerce-order-confirmation.php')) {
    require_once $includes_dir . 'woocommerce-order-confirmation.php';
}

// その他カスタマイズ
if (file_exists($includes_dir . 'woocommerce-customization.php')) {
    require_once $includes_dir . 'woocommerce-customization.php';
}

// CSVエクスポート機能
if (file_exists($includes_dir . 'woocommerce-csv-export.php')) {
    require_once $includes_dir . 'woocommerce-csv-export.php';
}

// ユーザー権限管理（基本機能のみ）
if (file_exists($includes_dir . 'user-roles-basic.php')) {
    require_once $includes_dir . 'user-roles-basic.php';
}

// ===========================================
// 注文詳細の商品名リンクを削除
// ===========================================

// マイアカウントの注文詳細で商品名のリンクを削除
function remove_product_link_in_order_details($product_name, $item, $is_visible) {
    if (is_account_page() && is_wc_endpoint_url('view-order')) {
        // 商品名からaタグを削除してテキストのみ表示
        return strip_tags($product_name);
    }
    return $product_name;
}
add_filter('woocommerce_order_item_name', 'remove_product_link_in_order_details', 10, 3);

// ===========================================
// JavaScriptでメールアドレスリンクを削除
// ===========================================

function add_email_removal_script() {
    if (is_account_page() && is_wc_endpoint_url('view-order')) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // メールアドレス専用のp要素を削除
            var emailElements = document.querySelectorAll('p.woocommerce-customer-details--email, .woocommerce-customer-details--email');
            emailElements.forEach(function(element) {
                element.style.display = 'none';
                element.remove();
            });
            
            // メールアドレスリンクを削除
            var emailLinks = document.querySelectorAll('a[href*="mailto:"]');
            emailLinks.forEach(function(link) {
                link.style.display = 'none';
                link.remove();
            });
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'add_email_removal_script');

