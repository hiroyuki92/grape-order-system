<?php
/**
 * Plugin Name: 電話番号会員管理システム
 * Plugin URI: https://yoursite.com
 * Description: 電話番号と名前だけで会員登録・ログインができるWooCommerce連携システム
 * Version: 1.1.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: phone-member-system
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 */

// セキュリティ: 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

// プラグインの定数定義
define('PMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PMS_VERSION', '1.1.0');

// WooCommerce HPOS 対応宣言
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// WooCommerceが有効かチェック
add_action('plugins_loaded', 'pms_init_plugin');
function pms_init_plugin() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'pms_woocommerce_missing_notice');
        return;
    }
    
    // WooCommerceが有効な場合のみ機能を読み込み
    pms_load_modules();
}

function pms_woocommerce_missing_notice() {
    echo '<div class="notice notice-error">
        <p><strong>電話番号会員管理システム:</strong> このプラグインはWooCommerceが必要です。WooCommerceをインストール・有効化してください。</p>
    </div>';
}

// 機能モジュールの読み込み
function pms_load_modules() {
    // ファイルが存在するかチェックしてから読み込み
    $modules = array(
        'utilities.php',
        'css-loader.php',
        'admin.php',
        'authentication.php',
        'frontend.php',
        'password-management.php'
    );
    
    foreach ($modules as $module) {
        $file_path = PMS_PLUGIN_DIR . 'includes/' . $module;
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            // デバッグ用：ファイルが見つからない場合の警告
            error_log("PMS Plugin: モジュールファイルが見つかりません: " . $file_path);
        }
    }
}