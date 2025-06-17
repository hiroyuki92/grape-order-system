<?php
function storefront_child_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
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

/* // 住所管理プラグインの日本語化
function force_translate_all_texts($translated_text, $text, $domain) {
    $translations = array(
        'The following addresses will be used on the checkout page by default.' => '以下の住所がチェックアウトページでデフォルトとして使用されます。',
        'Billing address' => '請求先住所',
        'Shipping address' => '配送先住所',
        'You have not set up this type of address yet.' => 'この種類の住所はまだ設定されていません。',
        // 新しい翻訳を追加
        'Additional billing addresses' => '追加請求先住所',
        'Additional shipping addresses' => '追加配送先住所',
        'There are no saved addresses yet' => 'まだ保存された住所がありません',
        'Add New Address' => '新しい住所を追加',
        'Edit' => '編集',
    );
    
    if (isset($translations[$text])) {
        return $translations[$text];
    }
    
    return $translated_text;
}
add_filter('gettext', 'force_translate_all_texts', 999, 3);
add_filter('ngettext', 'force_translate_all_texts', 999, 3);
 */

?>