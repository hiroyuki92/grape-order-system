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