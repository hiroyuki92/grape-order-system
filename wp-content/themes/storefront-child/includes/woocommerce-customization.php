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