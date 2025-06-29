<?php
/**
 * WooCommerce カート配送先住所非表示機能
 * 
 * 保存場所: wp-content/themes/storefront-child/includes/woocommerce-cart-shipping.php
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// ===========================================
// カートページの配送先住所表示を非表示
// ===========================================

/**
 * カートページの配送計算機を非表示にする
 */
function hide_cart_shipping_calculator() {
    // カートページでのみ実行
    if (is_cart()) {
        remove_action('woocommerce_cart_collaterals', 'woocommerce_shipping_calculator', 20);
    }
}
add_action('wp', 'hide_cart_shipping_calculator');

/**
 * 配送計算機のリンク（「配送先を計算する」）も非表示
 */
function hide_shipping_calculator_trigger() {
    if (is_cart()) {
        ?>
        <style>
            .woocommerce-shipping-calculator {
                display: none !important;
            }
            
            /* 配送先表示部分も非表示 */
            .cart-collaterals .shipping_calculator {
                display: none !important;
            }
            
            /* 配送方法選択部分も非表示 */
            .woocommerce-cart table.cart .shipping {
                display: none !important;
            }
            
            /* 「住所を変更」リンクも非表示 */
            .woocommerce-cart .shipping-calculator-button {
                display: none !important;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'hide_shipping_calculator_trigger');

/**
 * カート小計エリアの配送関連情報をカスタマイズ
 * （配送先住所表示を削除し、シンプルな表示に）
 */
function customize_cart_totals_shipping_display() {
    if (is_cart()) {
        ?>
        <style>
            /* 配送先住所表示部分を非表示 */
            .cart-collaterals .cart_totals .shipping p {
                display: none;
            }
            
            /* 配送方法の選択は残す場合 */
            .cart-collaterals .cart_totals .shipping ul#shipping_method {
                display: block;
            }
            
            /* 配送計算機全体を非表示 */
            .cart-collaterals .shipping_calculator {
                display: none !important;
            }
            
            /* 「配送先を計算する」リンクを非表示 */
            .shipping-calculator-button {
                display: none !important;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'customize_cart_totals_shipping_display');
?>