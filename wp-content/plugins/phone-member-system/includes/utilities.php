<?php
/**
 * ユーティリティ関数
 * includes/utilities.php
 */

// 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ユーザーメタデータを設定
 */
function pms_set_user_meta($user_id, $phone_clean, $phone_display, $name) {
    // 基本情報
    update_user_meta($user_id, 'phone_number', $phone_clean);
    update_user_meta($user_id, 'phone_display', $phone_display);
    update_user_meta($user_id, 'is_phone_user', true);
    update_user_meta($user_id, 'registration_method', 'admin');
    update_user_meta($user_id, 'registration_date', current_time('mysql'));
    
    // パスワード関連
    update_user_meta($user_id, 'password_changed_from_initial', false);
    update_user_meta($user_id, 'initial_password_date', current_time('mysql'));
    
    // WooCommerce用
    update_user_meta($user_id, 'billing_first_name', $name);
    update_user_meta($user_id, 'billing_phone', $phone_display);
}

/**
 * 電話番号会員かどうかをチェック
 */
function pms_is_phone_user($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    return get_user_meta($user_id, 'is_phone_user', true);
}

/**
 * パスワード変更状態を取得
 */
function pms_get_password_status($user_id) {
    $password_changed = get_user_meta($user_id, 'password_changed_from_initial', true);
    $admin_reset = get_user_meta($user_id, 'admin_password_reset_date', true);
    
    if ($password_changed) {
        return array(
            'status' => 'changed',
            'label' => '✓ 変更済み',
            'color' => '#28a745'
        );
    } elseif ($admin_reset) {
        return array(
            'status' => 'admin_reset',
            'label' => '📝 管理者設定',
            'color' => '#ffc107'
        );
    } else {
        return array(
            'status' => 'initial',
            'label' => '⚠ 初期PW',
            'color' => '#dc3545'
        );
    }
}

/**
 * 4桁パスワード生成
 */
function pms_generate_4digit_password() {
    return sprintf('%04d', rand(1000, 9999));
}

/**
 * 電話番号の正規化
 */
function pms_normalize_phone($phone) {
    return preg_replace('/[^0-9]/', '', $phone);
}

/**
 * 電話番号の形式チェック
 */
function pms_validate_phone($phone) {
    $phone_clean = pms_normalize_phone($phone);
    return strlen($phone_clean) >= 10 && strlen($phone_clean) <= 11;
}