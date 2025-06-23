
<?php
/**
 * CSS読み込み管理
 * includes/css-loader.php
 */

// 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

// 管理画面用CSSの読み込み
add_action('admin_enqueue_scripts', 'pms_enqueue_admin_css');
function pms_enqueue_admin_css($hook) {
    // 電話番号会員管理ページでのみ読み込み
    if (strpos($hook, 'phone-member') !== false) {
        wp_enqueue_style(
            'pms-admin-css',
            PMS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PMS_VERSION
        );
    }
}

// フロントエンド用CSSの読み込み
add_action('wp_enqueue_scripts', 'pms_enqueue_frontend_css');
function pms_enqueue_frontend_css() {
    // マイアカウントページでのみ読み込み
    if (is_account_page()) {
        wp_enqueue_style(
            'pms-frontend-css',
            PMS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            PMS_VERSION
        );
    }
}

// 既存のインラインCSSを削除
add_action('init', 'pms_remove_inline_css', 5);
function pms_remove_inline_css() {
    // パスワードリセットリンクを隠すCSSは残す（重要な機能のため）
    // メールアドレスフィールドを隠すCSSは残す（重要な機能のため）
    
    // 管理画面の会員一覧でインラインCSSを削除
    add_filter('pms_member_list_inline_css', '__return_false');
}

// CSSファイルのキャッシュ対策
add_filter('style_loader_src', 'pms_add_css_version', 10, 2);
function pms_add_css_version($src, $handle) {
    if ($handle === 'pms-admin-css' || $handle === 'pms-frontend-css') {
        // 開発環境では常に最新版を読み込み
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $src = add_query_arg('v', time(), $src);
        }
    }
    return $src;
}