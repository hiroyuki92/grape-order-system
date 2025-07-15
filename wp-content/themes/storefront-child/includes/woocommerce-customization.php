<?php
/**
 * その他のWooCommerceカスタマイズ
 */

if (!defined('ABSPATH')) {
    exit;
}

// 請求先を「お送り主」に変更
add_filter('gettext', 'change_billing_text_to_sender', 20, 3);
function change_billing_text_to_sender($translated_text, $text, $domain) {
    if ($domain == 'woocommerce') {
        switch ($text) {
            case 'Billing address':
            case '請求先住所':
                return 'お送り主住所';
            case 'Billing details':
            case '請求先詳細':
                return 'お送り主詳細';
            case 'Billing information':
            case '請求先情報':
                return 'お送り主情報';
        }
    }
    return $translated_text;
}

// 請求先（送り主）のメールアドレスフィールドを非表示
add_filter('woocommerce_checkout_fields', 'hide_billing_email_field');
function hide_billing_email_field($fields) {
    unset($fields['billing']['billing_email']);
    return $fields;
}

// マイアカウントの住所ページでもメールアドレスフィールドを非表示
add_filter('woocommerce_billing_fields', 'hide_billing_email_field_myaccount');
add_filter('woocommerce_shipping_fields', 'hide_shipping_email_field_myaccount');
function hide_billing_email_field_myaccount($fields) {
    unset($fields['billing_email']);
    return $fields;
}
function hide_shipping_email_field_myaccount($fields) {
    unset($fields['shipping_email']);
    return $fields;
}

// 注文完了画面（thankyou）でメールアドレス表示を非表示
add_action('wp_head', 'hide_thankyou_email_css');
function hide_thankyou_email_css() {
    if (is_wc_endpoint_url('order-received')) {
        ?>
        <style>
        .woocommerce-order-overview__email {
            display: none !important;
        }
        </style>
        <?php
    }
}

// ショップページのタイトルスタイルを強制適用
add_action('wp_head', 'force_shop_title_style');
function force_shop_title_style() {
    if (is_shop() || is_product_category() || is_product_tag()) {
        ?>
        <style>
        .woocommerce-products-header h1,
        .woocommerce-products-header__title,
        .page-title {
            font-family: 'Hannari', serif !important;
            font-size: 32px !important;
            font-weight: normal !important;
            color: #2F4F2F !important;
            margin-bottom: 30px !important;
        }
        
        @media (max-width: 768px) {
            .woocommerce-products-header h1,
            .woocommerce-products-header__title,
            .page-title {
                font-size: 24px !important;
            }
        }
        </style>
        <?php
    }
}

// 住所ニックネームフィールドを非表示
add_filter('woocommerce_billing_fields', 'hide_address_nickname_fields', 20);
add_filter('woocommerce_shipping_fields', 'hide_address_nickname_fields', 20);
function hide_address_nickname_fields($fields) {
    // 住所ニックネームフィールドを削除
    unset($fields['billing_address_nickname']);
    unset($fields['shipping_address_nickname']);
    return $fields;
}