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
    // 姓名を分割
    $name_parts = pms_split_japanese_name($name);
    
    // 基本情報
    update_user_meta($user_id, 'phone_number', $phone_clean);
    update_user_meta($user_id, 'phone_display', $phone_display);
    update_user_meta($user_id, 'is_phone_user', true);
    update_user_meta($user_id, 'registration_method', 'admin');
    update_user_meta($user_id, 'registration_date', current_time('mysql'));
    
    // パスワード関連
    update_user_meta($user_id, 'password_changed_from_initial', false);
    update_user_meta($user_id, 'initial_password_date', current_time('mysql'));
    
    // WooCommerce用（姓名を分割して設定）
    update_user_meta($user_id, 'billing_last_name', $name_parts['last_name']);
    update_user_meta($user_id, 'billing_first_name', $name_parts['first_name']);
    update_user_meta($user_id, 'billing_phone', $phone_display);
    
    // WordPressユーザー情報も更新
    wp_update_user(array(
        'ID' => $user_id,
        'first_name' => $name_parts['first_name'],
        'last_name' => $name_parts['last_name']
    ));
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

/**
 * 日本語名前を姓名に分割
 */
function pms_split_japanese_name($full_name) {
    $full_name = trim($full_name);
    
    // スペースで区切られている場合
    if (preg_match('/^(.+?)\s+(.+)$/', $full_name, $matches)) {
        return array(
            'last_name' => trim($matches[1]),
            'first_name' => trim($matches[2])
        );
    }
    
    // 全角スペースで区切られている場合
    if (preg_match('/^(.+?)　+(.+)$/', $full_name, $matches)) {
        return array(
            'last_name' => trim($matches[1]),
            'first_name' => trim($matches[2])
        );
    }
    
    // スペースがない場合の推測分割
    $length = mb_strlen($full_name, 'UTF-8');
    
    if ($length >= 3) {
        // 3文字以上の場合、最初の1-2文字を姓とする
        if ($length >= 4) {
            // 4文字以上の場合は2文字目まで姓にする可能性を考慮
            $last_name = mb_substr($full_name, 0, 2, 'UTF-8');
            $first_name = mb_substr($full_name, 2, null, 'UTF-8');
        } else {
            // 3文字の場合は1文字目を姓に
            $last_name = mb_substr($full_name, 0, 1, 'UTF-8');
            $first_name = mb_substr($full_name, 1, null, 'UTF-8');
        }
        
        return array(
            'last_name' => $last_name,
            'first_name' => $first_name
        );
    }
    
    // 2文字以下の場合は全て名前に
    return array(
        'last_name' => '',
        'first_name' => $full_name
    );
}

/**
 * 最終ログイン時刻を記録
 */
function pms_update_last_login($user_id) {
    update_user_meta($user_id, 'last_login', current_time('mysql'));
}

// ログイン時に最終ログイン時刻を記録
add_action('wp_login', 'pms_record_last_login', 10, 2);
function pms_record_last_login($user_login, $user) {
    // 電話番号会員の場合のみ記録
    if (pms_is_phone_user($user->ID)) {
        pms_update_last_login($user->ID);
        
        // 姓名が正しく分割されていない場合は修正
        pms_fix_name_fields_if_needed($user->ID);
    }
}

/**
 * 必要に応じて姓名フィールドを修正
 */
function pms_fix_name_fields_if_needed($user_id) {
    $user = get_userdata($user_id);
    $display_name = $user->display_name;
    
    // 姓が空で、名にフルネームが入っている場合
    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name = get_user_meta($user_id, 'last_name', true);
    
    if (empty($last_name) && !empty($first_name) && $first_name === $display_name) {
        // 姓名を分割して再設定
        $name_parts = pms_split_japanese_name($display_name);
        
        // WordPressユーザー情報を更新
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $name_parts['first_name'],
            'last_name' => $name_parts['last_name']
        ));
        
        // WooCommerceの請求先情報も更新
        update_user_meta($user_id, 'billing_last_name', $name_parts['last_name']);
        update_user_meta($user_id, 'billing_first_name', $name_parts['first_name']);
    }
}