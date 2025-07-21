<?php
/**
 * フロントエンド機能
 * includes/frontend.php
 */

// 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

// ログインフォームのラベル変更
add_filter('gettext', 'pms_change_login_form_labels', 20, 3);
function pms_change_login_form_labels($translated_text, $text, $domain) {
    switch ($translated_text) {
        case 'Username or Email Address':
        case 'Username':
            return '電話番号';
        case 'Password':
            return 'パスワード（4桁の数字）';
        case 'Username is required.':
        case 'ユーザー名は必須です。':
            return '電話番号は必須です。';
        case '<strong>Error:</strong> The username field is empty.':
        case '<strong>エラー:</strong> ユーザー名フィールドが空です。':
            return '<strong>エラー:</strong> 電話番号を入力してください。';
    }
    return $translated_text;
}

// ログインフォームのカスタマイズ
add_action('wp_footer', 'pms_customize_woocommerce_login');
function pms_customize_woocommerce_login() {
    if (is_account_page()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // ログインフォームのplaceholder変更
            $('#username').attr('placeholder', '電話番号（例：09012345678）');
            $('#password').attr('placeholder', 'パスワード');
            
            // ラベル変更
            $('label[for="username"]').html('電話番号 <span class="required">*</span>');
            $('label[for="password"]').html('パスワード <span class="required">*</span>');
        });
        </script>
        <?php
    }
}

// ログインボタンの下に案内を表示
add_action('woocommerce_login_form_end', 'pms_add_login_notice_after_button');
function pms_add_login_notice_after_button() {
    ?>
    <div style="background: #fff3cd; border: 1px solid #ffecb5; color: #856404; padding: 15px; margin: 20px 0; border-radius: 5px;">
        <h3 style="margin-top: 0;">📞 パスワードをお忘れの場合</h3>
        <p>パスワードを忘れた場合は担当者へお問い合わせください。</p>
    </div>
    <?php
}

// パスワードを忘れた場合のリンクをCSSで隠す（重要な機能なので残す）
add_action('wp_head', 'pms_hide_lost_password_link');
function pms_hide_lost_password_link() {
    if (is_account_page()) {
        ?>
        <style>
        .woocommerce-LostPassword,
        .lost_password,
        a[href*="lost-password"],
        a[href*="lostpassword"] {
            display: none !important;
        }
        </style>
        <?php
    }
}

// 電話番号会員のメールアドレスフィールドを非表示（重要な機能なので残す）
add_action('wp_head', 'pms_hide_email_field_css_only');
function pms_hide_email_field_css_only() {
    if (is_account_page()) {
        $current_user = wp_get_current_user();
        $is_phone_user = pms_is_phone_user($current_user->ID);
        
        if ($is_phone_user) {
            ?>
            <style>
            /* 電話番号会員のメールアドレスフィールドを完全に非表示 */
            .woocommerce-MyAccount-content input[name="account_email"],
            .woocommerce-MyAccount-content label[for="account_email"],
            .woocommerce-MyAccount-content .woocommerce-form-row--email {
                display: none !important;
            }
            
            /* メールアドレスを含む行全体を非表示 */
            .woocommerce-MyAccount-content p:has(input[name="account_email"]) {
                display: none !important;
            }
            </style>
            <?php
        }
    }
}

// ログイン後のダッシュボードで初期パスワードチェック
add_action('woocommerce_account_dashboard', 'pms_check_initial_password', 5);
function pms_check_initial_password() {
    $current_user = wp_get_current_user();
    $is_phone_user = pms_is_phone_user($current_user->ID);
    $password_changed = get_user_meta($current_user->ID, 'password_changed_from_initial', true);
    
    // 電話番号会員で、まだ初期パスワードから変更していない場合
    if ($is_phone_user && !$password_changed) {
        ?>
        <div style="background: #dc3545; border: 1px solid #dc3545; color: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; color: white;">🔒 セキュリティ向上のお願い</h3>
            <p><strong>初期パスワード（4桁数字）をご自身のパスワードに変更してください。</strong></p>
            <p>セキュリティ向上のため、ログイン用パスワードの変更を強くお勧めします。</p>
            <p style="margin-bottom: 15px;">
                <strong>📱 パスワード変更方法：</strong><br>
                1. 下の「パスワードを変更する」ボタンをクリック<br>
                2. 現在のパスワード（4桁数字）を入力<br>
                3. 新しいパスワードを入力（8文字以上推奨）
            </p>
            <p style="margin: 0;">
                <a href="<?php echo wc_get_endpoint_url('edit-account', '', wc_get_page_permalink('myaccount')); ?>" 
                   style="background: white; color: #dc3545; padding: 10px 20px; text-decoration: none; border-radius: 3px; font-weight: bold; display: inline-block;">
                    パスワードを変更する
                </a>
            </p>
        </div>
        <?php
    }
}

// アカウント詳細ページで電話番号会員のパスワード変更案内
add_action('woocommerce_edit_account_form_start', 'pms_allow_password_change_for_phone_users');
function pms_allow_password_change_for_phone_users() {
    $current_user = wp_get_current_user();
    $is_phone_user = pms_is_phone_user($current_user->ID);
    $password_changed = get_user_meta($current_user->ID, 'password_changed_from_initial', true);
    
    if ($is_phone_user && !$password_changed) {
        ?>
        <div style="background: #fff3cd; border: 1px solid #ffecb5; color: #856404; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3 style="margin-top: 0;">🔐 パスワード変更</h3>
            <p>初期パスワード（4桁数字）から、ご自身のパスワードに変更してください。</p>
            <p style="margin: 0; font-size: 14px;">
                <strong>推奨：</strong> 8文字以上、英数字を組み合わせたパスワード
            </p>
        </div>
        <?php
    } elseif ($is_phone_user && $password_changed) {
        ?>
        <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3 style="margin-top: 0;">✅ パスワード変更済み</h3>
            <p style="margin: 0;">セキュリティが向上しました。必要に応じてパスワードを再変更できます。</p>
        </div>
        <?php
    }
}

// パスワード変更時にフラグを更新
add_action('woocommerce_save_account_details', 'pms_mark_password_changed');
function pms_mark_password_changed($user_id) {
    // パスワードが変更された場合
    if (!empty($_POST['password_1'])) {
        $is_phone_user = pms_is_phone_user($user_id);
        
        if ($is_phone_user) {
            // パスワード変更完了フラグを設定
            update_user_meta($user_id, 'password_changed_from_initial', true);
            update_user_meta($user_id, 'password_change_date', current_time('mysql'));
        }
    }
}