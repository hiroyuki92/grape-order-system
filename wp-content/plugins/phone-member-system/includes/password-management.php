<?php
/**
 * パスワード管理機能
 * includes/password-management.php
 */

// 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

// 管理画面でのパスワード自動生成を4桁にする
add_filter('random_password', 'pms_generate_4digit_password_for_phone_users', 10, 1);
function pms_generate_4digit_password_for_phone_users($password) {
    // 管理画面でユーザー編集中かチェック
    if (is_admin() && isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        $is_phone_user = pms_is_phone_user($user_id);
        
        if ($is_phone_user) {
            // 電話番号会員の場合は4桁の数字を生成
            return pms_generate_4digit_password();
        }
    }
    
    return $password;
}

// ユーザープロファイル更新時に4桁パスワードを適用
add_action('user_profile_update_errors', 'pms_validate_phone_user_password', 10, 3);
function pms_validate_phone_user_password($errors, $update, $user) {
    if ($update && isset($_POST['pass1']) && !empty($_POST['pass1'])) {
        $is_phone_user = pms_is_phone_user($user->ID);
        
        if ($is_phone_user) {
            $new_password = $_POST['pass1'];
            
            // 4桁の数字以外の場合は警告（但し、保存は許可）
            if (!preg_match('/^\d{4}$/', $new_password)) {
                $errors->add('password_format', 
                    '<strong>注意:</strong> 電話番号会員には4桁の数字のパスワードを推奨します。' .
                    '<br>現在のパスワード: <code>' . esc_html($new_password) . '</code>' .
                    '<br><a href="#" onclick="jQuery(\'#pass1, #pass2\').val(\'' . pms_generate_4digit_password() . '\'); return false;" class="button">4桁パスワードを自動生成</a>'
                );
            }
        }
    }
}

// 管理画面のパスワード生成ボタンをカスタマイズ
add_action('admin_footer', 'pms_customize_password_generator');
function pms_customize_password_generator() {
    global $pagenow;
    
    // ユーザー編集ページでのみ実行
    if (($pagenow == 'user-edit.php' || $pagenow == 'profile.php') && isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        $is_phone_user = pms_is_phone_user($user_id);
        
        if ($is_phone_user) {
            ?>
            <script>
            jQuery(document).ready(function($) {
                // 既存の「パスワードを生成」ボタンの動作を変更
                $('#pass1').closest('tr').find('.wp-generate-pw').after(
                    '<button type="button" class="button wp-generate-4digit-pw" style="margin-left: 10px;">4桁パスワード生成</button>'
                );
                
                // 4桁パスワード生成ボタンのクリックイベント
                $('.wp-generate-4digit-pw').on('click', function(e) {
                    e.preventDefault();
                    var fourDigitPassword = Math.floor(1000 + Math.random() * 9000).toString();
                    $('#pass1, #pass2').val(fourDigitPassword);
                    
                    // パスワード強度表示を更新
                    if (typeof wp !== 'undefined' && wp.passwordStrength) {
                        $('#pass-strength-result').removeClass().addClass('short').html('4桁の数字（電話番号会員用）');
                    }
                    
                    alert('4桁パスワードを生成しました: ' + fourDigitPassword);
                });
                
                // 電話番号会員用の説明を追加
                $('#pass1').closest('tr').after(
                    '<tr><th></th><td><div style="background: #fff3cd; border: 1px solid #ffecb5; color: #856404; padding: 10px; border-radius: 3px; margin-top: 10px;">' +
                    '<strong>📱 電話番号会員:</strong> 4桁の数字パスワードを推奨しています。' +
                    '</div></td></tr>'
                );
            });
            </script>
            <?php
        }
    }
}

// ユーザー作成・更新時にパスワード変更フラグを適切に設定
add_action('profile_update', 'pms_handle_password_change_flag');
function pms_handle_password_change_flag($user_id) {
    $is_phone_user = pms_is_phone_user($user_id);
    
    if ($is_phone_user && isset($_POST['pass1']) && !empty($_POST['pass1'])) {
        $new_password = $_POST['pass1'];
        
        // 4桁の数字の場合
        if (preg_match('/^\d{4}$/', $new_password)) {
            // 管理者が設定した場合は「変更済み」にはしない（顧客自身の変更を促すため）
            if (current_user_can('manage_options')) {
                update_user_meta($user_id, 'password_changed_from_initial', false);
                update_user_meta($user_id, 'admin_password_reset_date', current_time('mysql'));
            }
        } else {
            // 4桁以外の場合は「変更済み」とする
            update_user_meta($user_id, 'password_changed_from_initial', true);
            update_user_meta($user_id, 'password_change_date', current_time('mysql'));
        }
    }
}

// 管理画面のユーザー一覧でパスワード状況を表示
add_action('manage_users_custom_column', 'pms_show_password_status_in_user_list', 10, 3);
function pms_show_password_status_in_user_list($value, $column_name, $user_id) {
    if ($column_name == 'password_status') {
        $is_phone_user = pms_is_phone_user($user_id);
        
        if ($is_phone_user) {
            $password_status = pms_get_password_status($user_id);
            return '<span style="color: ' . $password_status['color'] . ';">' . $password_status['label'] . '</span>';
        }
    }
    
    return $value;
}