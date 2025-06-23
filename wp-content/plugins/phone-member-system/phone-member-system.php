<?php
/**
 * Plugin Name: 電話番号会員管理システム
 * Plugin URI: https://yoursite.com
 * Description: 電話番号と名前だけで会員登録・ログインができるWooCommerce連携システム
 * Version: 1.0.0
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

// WooCommerce HPOS 対応宣言
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// WooCommerceが有効かチェック
add_action('plugins_loaded', 'pms_check_woocommerce');
function pms_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'pms_woocommerce_missing_notice');
        return;
    }
    
    // WooCommerceが有効な場合のみ機能を読み込み
    pms_init_features();
}

function pms_woocommerce_missing_notice() {
    echo '<div class="notice notice-error">
        <p><strong>電話番号会員管理システム:</strong> このプラグインはWooCommerceが必要です。WooCommerceをインストール・有効化してください。</p>
    </div>';
}

function pms_init_features() {
    // 管理画面機能
    add_action('admin_menu', 'pms_add_admin_menu');
    
    // ログイン機能
    add_filter('authenticate', 'pms_authenticate_phone_user', 20, 3);
    
    // フロントエンド機能
    add_filter('gettext', 'pms_change_login_form_labels', 20, 3);
    add_action('wp_footer', 'pms_customize_woocommerce_login');
}

// ===========================================
// 管理画面メニュー
// ===========================================

function pms_add_admin_menu() {
    add_menu_page(
        '電話番号会員管理',
        '会員管理',
        'manage_options',
        'phone-member-management',
        'pms_member_list_page',
        'dashicons-phone',
        26
    );
    
    add_submenu_page(
        'phone-member-management',
        '会員一覧',
        '会員一覧',
        'manage_options',
        'phone-member-management',
        'pms_member_list_page'
    );
    
    add_submenu_page(
        'phone-member-management',
        '新規会員登録',
        '新規登録',
        'manage_options',
        'phone-member-registration',
        'pms_member_registration_page'
    );
}

// ===========================================
// 会員一覧ページ
// ===========================================

function pms_member_list_page() {
    // 電話番号会員を取得
    $phone_users = get_users(array(
        'meta_key' => 'is_phone_user',
        'meta_value' => true,
        'orderby' => 'registered',
        'order' => 'DESC'
    ));
    
    ?>
    <div class="wrap">
        <h1>📱 電話番号会員一覧</h1>
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <a href="<?php echo admin_url('admin.php?page=phone-member-registration'); ?>" class="button button-primary">新規会員登録</a>
            </div>
            <div class="alignright">
                <span class="displaying-num"><?php echo count($phone_users); ?>名の会員</span>
            </div>
        </div>
        
        <?php if (empty($phone_users)): ?>
            <div style="text-align: center; padding: 40px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">
                <p style="font-size: 18px; color: #666;">まだ会員が登録されていません</p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=phone-member-registration'); ?>" class="button button-primary">
                        最初の会員を登録する
                    </a>
                </p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 140px;">電話番号</th>
                        <th>お名前</th>
                        <th style="width: 120px;">登録日</th>
                        <th style="width: 120px;">最終ログイン</th>
                        <th style="width: 80px;">注文数</th>
                        <th style="width: 100px;">PW変更</th>  <!-- 新しい列 -->
                        <th style="width: 100px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($phone_users as $user): ?>
                    <?php
                    $phone_display = get_user_meta($user->ID, 'phone_display', true);
                    $phone_number = get_user_meta($user->ID, 'phone_number', true);
                    $last_login = get_user_meta($user->ID, 'last_login', true);
                    $order_count = function_exists('wc_get_customer_order_count') ? wc_get_customer_order_count($user->ID) : 0;
                    $password_changed = get_user_meta($user->ID, 'password_changed_from_initial', true);  // 新しい列のデータ
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($phone_display ?: $phone_number); ?></strong>
                        </td>
                        <td>
                            <?php echo esc_html($user->display_name); ?>
                            <div style="color: #666; font-size: 12px;">
                                ID: <?php echo $user->ID; ?>
                            </div>
                        </td>
                        <td>
                            <?php echo date('Y/m/d', strtotime($user->user_registered)); ?>
                            <div style="color: #666; font-size: 12px;">
                                <?php echo date('H:i', strtotime($user->user_registered)); ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($last_login): ?>
                                <?php echo date('Y/m/d', strtotime($last_login)); ?>
                                <div style="color: #666; font-size: 12px;">
                                    <?php echo date('H:i', strtotime($last_login)); ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #999;">未ログイン</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="order-count"><?php echo $order_count; ?>件</span>
                        </td>
                        <td>
                            <!-- 新しい列：パスワード変更状況 -->
                            <?php if ($password_changed): ?>
                                <span style="color: #28a745; font-weight: bold;">✓ 変更済み</span>
                            <?php else: ?>
                                <span style="color: #dc3545; font-weight: bold;">⚠ 初期PW</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo get_edit_user_link($user->ID); ?>" class="button button-small">編集</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div style="margin-top: 20px; padding: 15px; background: #f0f8ff; border-left: 4px solid #007cba;">
            <h3>💡 会員管理のヒント</h3>
            <ul style="margin: 10px 0;">
                <li><strong>パスワードリセット:</strong> 「編集」ボタンから新しいパスワードを設定できます</li>
                <li><strong>注文履歴:</strong> WooCommerce > 注文 で各会員の注文を確認できます</li>
                <li><strong>連絡方法:</strong> 電話番号が登録されているので、直接連絡が可能です</li>
                <li><strong>初期PW:</strong> ⚠マークの会員は初期パスワード（4桁）のままです</li>
            </ul>
        </div>
    </div>
    
    <style>
    .wp-list-table th,
    .wp-list-table td {
        vertical-align: top;
        padding: 12px 8px;
    }
    .order-count {
        font-weight: bold;
        color: #0073aa;
    }
    .button-small {
        padding: 2px 8px;
        font-size: 11px;
        line-height: 1.5;
        height: auto;
    }
    </style>
    <?php
}

// ===========================================
// 会員登録ページ
// ===========================================

function pms_member_registration_page() {
    $message = '';
    
    // フォームが送信された場合の処理
    if (isset($_POST['register_member']) && wp_verify_nonce($_POST['_wpnonce'], 'register_phone_member')) {
        $phone = sanitize_text_field($_POST['phone']);
        $name = sanitize_text_field($_POST['name']);
        
        // 入力チェック
        if (empty($phone) || empty($name)) {
            $message = '<div class="notice notice-error"><p>電話番号と名前は必須です。</p></div>';
        } else {
            // 会員登録実行
            $result = pms_register_phone_member($phone, $name);
            
            if ($result['success']) {
                $message = '<div class="notice notice-success">
                    <p><strong>会員登録が完了しました！</strong></p>
                    <p>電話番号: ' . esc_html($phone) . '</p>
                    <p>名前: ' . esc_html($name) . '</p>
                    <p>パスワード: <code>' . esc_html($result['password']) . '</code></p>
                    <p><small>※ パスワードを顧客にお伝えください</small></p>
                </div>';
            } else {
                $message = '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
            }
        }
    }
    
    ?>
    <div class="wrap">
        <h1>📱 新規会員登録</h1>
        <p>電話番号と名前だけで新しい会員を登録できます。</p>
        
        <?php echo $message; ?>
        
        <form method="post" style="max-width: 600px; background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
            <?php wp_nonce_field('register_phone_member'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="phone">電話番号 <span style="color: red;">*</span></label>
                    </th>
                    <td>
                        <input type="tel" 
                               name="phone" 
                               id="phone" 
                               class="regular-text" 
                               placeholder="090-1234-5678 または 09012345678" 
                               required 
                               value="<?php echo isset($_POST['phone']) ? esc_attr($_POST['phone']) : ''; ?>" />
                        <p class="description">ハイフンありなしどちらでも登録できます</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="name">お名前 <span style="color: red;">*</span></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               class="regular-text" 
                               placeholder="山田太郎" 
                               required 
                               value="<?php echo isset($_POST['name']) ? esc_attr($_POST['name']) : ''; ?>" />
                        <p class="description">フルネームでの登録をお勧めします</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" 
                       name="register_member" 
                       class="button button-primary button-large" 
                       value="会員登録" />
            </p>
        </form>
        
        <div style="margin-top: 30px; padding: 15px; background: #f0f8ff; border-left: 4px solid #007cba;">
            <h3>📋 登録後の流れ</h3>
            <ol>
                <li>自動で4桁のパスワードが生成されます</li>
                <li>生成されたパスワードを顧客にお伝えください</li>
                <li>顧客は電話番号とパスワードでログインできます</li>
                <li>WooCommerceでの注文も可能になります</li>
            </ol>
        </div>
        
        <?php
        // 登録済み会員数を表示
        $phone_users = get_users(array(
            'meta_key' => 'is_phone_user',
            'meta_value' => true
        ));
        
        $count = count($phone_users);
        
        if ($count > 0) {
            echo '<div class="notice notice-info" style="margin-top: 20px;">
                <p>📊 現在 <strong>' . $count . '名</strong> の電話番号会員が登録されています。
                <a href="' . admin_url('admin.php?page=phone-member-management') . '">会員一覧を見る</a></p>
            </div>';
        }
        ?>
    </div>
    
    <style>
    .form-table th {
        width: 150px;
    }
    .regular-text {
        width: 300px;
    }
    </style>
    <?php
}

// ===========================================
// 会員登録処理
// ===========================================

function pms_register_phone_member($phone, $name) {
    // 電話番号の正規化（数字のみにする）
    $phone_clean = preg_replace('/[^0-9]/', '', $phone);
    
    // 電話番号の形式チェック
    if (strlen($phone_clean) < 10 || strlen($phone_clean) > 11) {
        return array(
            'success' => false,
            'message' => '正しい電話番号を入力してください。'
        );
    }
    
    // 既存会員チェック（電話番号の重複確認）
    $existing_user = get_users(array(
        'meta_key' => 'phone_number',
        'meta_value' => $phone_clean,
        'number' => 1
    ));
    
    if (!empty($existing_user)) {
        return array(
            'success' => false,
            'message' => 'この電話番号は既に登録されています。'
        );
    }
    
    // 4桁のパスワード生成
    $password = sprintf('%04d', rand(1000, 9999));
    
    // 仮メールアドレス生成（内部処理用）
    $fake_email = 'member_' . $phone_clean . '@temp.local';
    
    // WordPressユーザーデータ
    $user_data = array(
        'user_login' => $phone_clean,        // ユーザー名として電話番号を使用
        'user_email' => $fake_email,         // 仮メールアドレス
        'user_pass' => $password,            // 4桁パスワード
        'display_name' => $name,             // 表示名
        'first_name' => $name,               // 名前
        'role' => 'customer',                // WooCommerceの顧客ロール
        'show_admin_bar_front' => false      // フロントエンドの管理バーを非表示
    );
    
    // ユーザー作成実行
    $user_id = wp_insert_user($user_data);
    
    // エラーチェック
    if (is_wp_error($user_id)) {
        return array(
            'success' => false,
            'message' => 'ユーザー登録でエラーが発生しました: ' . $user_id->get_error_message()
        );
    }
    
    // 追加情報をユーザーメタとして保存
    update_user_meta($user_id, 'phone_number', $phone_clean);           // 正規化された電話番号
    update_user_meta($user_id, 'phone_display', $phone);               // 表示用電話番号（ハイフンあり）
    update_user_meta($user_id, 'is_phone_user', true);                 // 電話番号ユーザーフラグ
    update_user_meta($user_id, 'registration_method', 'admin');        // 登録方法
    update_user_meta($user_id, 'registration_date', current_time('mysql')); // 登録日時
    
    // パスワード変更関連のフラグを設定
    update_user_meta($user_id, 'password_changed_from_initial', false); // 初期パスワードのまま
    update_user_meta($user_id, 'initial_password_date', current_time('mysql')); // 初期パスワード設定日
    
    // WooCommerce用の請求先情報も設定
    update_user_meta($user_id, 'billing_first_name', $name);
    update_user_meta($user_id, 'billing_phone', $phone);
    
    return array(
        'success' => true,
        'user_id' => $user_id,
        'password' => $password,
        'phone_clean' => $phone_clean
    );
}

// ===========================================
// ログイン機能
// ===========================================

function pms_authenticate_phone_user($user, $username, $password) {
    // 既に認証済みまたは空の場合は通常の処理に任せる
    if (!is_null($user) || empty($username) || empty($password)) {
        return $user;
    }
    
    // 数字のみの場合のみ電話番号として処理
    $phone_clean = preg_replace('/[^0-9]/', '', $username);
    
    // 電話番号として有効でない場合（短すぎる、元の文字列に数字以外が多すぎる）は通常処理
    if (strlen($phone_clean) < 10 || strlen($phone_clean) > 11) {
        return $user;
    }
    
    // 元の文字列に@が含まれている場合はメールアドレスなので通常処理
    if (strpos($username, '@') !== false) {
        return $user;
    }
    
    // 電話番号でユーザー検索
    $users = get_users(array(
        'meta_key' => 'phone_number',
        'meta_value' => $phone_clean,
        'number' => 1
    ));
    
    if (empty($users)) {
        return $user; // 通常のログイン処理に戻す
    }
    
    $phone_user = $users[0];
    
    // パスワード確認
    if (wp_check_password($password, $phone_user->user_pass, $phone_user->ID)) {
        // 最終ログイン時刻を記録
        update_user_meta($phone_user->ID, 'last_login', current_time('mysql'));
        return $phone_user;
    }
    
    return $user; // 認証失敗時も通常処理に戻す
}

// ===========================================
// フロントエンド表示のカスタマイズ
// ===========================================

function pms_change_login_form_labels($translated_text, $text, $domain) {
    switch ($translated_text) {
        case 'Username or Email Address':
        case 'Username':
            return '電話番号';
        case 'Password':
            return 'パスワード（4桁の数字）';
    }
    return $translated_text;
}

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

// ===========================================
// パスワードリセット機能を完全無効化
// ===========================================

// パスワードリセットページを無効化
add_action('init', 'pms_disable_password_reset');
function pms_disable_password_reset() {
    // パスワードリセットページにアクセスされた場合
    if (isset($_GET['action']) && $_GET['action'] === 'lostpassword') {
        // マイアカウントページにリダイレクト
        wp_redirect(wc_get_page_permalink('myaccount'));
        exit;
    }
}

// パスワードを忘れた場合のリンクを無効化
add_filter('gettext', 'pms_remove_lost_password_text', 20, 3);
function pms_remove_lost_password_text($translated_text, $text, $domain) {
    if ($domain == 'woocommerce') {
        if ($text == 'Lost your password?' || $translated_text == 'パスワードをお忘れですか？') {
            return '';
        }
    }
    return $translated_text;
}

// パスワードを忘れた場合のリンクをCSSで隠す
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

// ログイン前：ログインフォームに案内を表示
add_action('woocommerce_login_form_end', 'pms_add_login_notice_after_button');
function pms_add_login_notice_after_button() {
    ?>
    <div style="background: #fff3cd; border: 1px solid #ffecb5; color: #856404; padding: 15px; margin: 20px 0; border-radius: 5px;">
        <h3 style="margin-top: 0;">📞 パスワードをお忘れの場合</h3>
        <p>パスワードを忘れた場合は担当者へお問い合わせください。</p>
    </div>
    <?php
}

// アカウント詳細画面でメールアドレス非表示
add_action('wp_head', 'pms_hide_email_field_css_only');
function pms_hide_email_field_css_only() {
    if (is_account_page()) {
        $current_user = wp_get_current_user();
        $is_phone_user = get_user_meta($current_user->ID, 'is_phone_user', true);
        
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
    $is_phone_user = get_user_meta($current_user->ID, 'is_phone_user', true);
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

// アカウント詳細ページで電話番号会員のパスワード変更を許可
add_action('woocommerce_edit_account_form_start', 'pms_allow_password_change_for_phone_users');
function pms_allow_password_change_for_phone_users() {
    $current_user = wp_get_current_user();
    $is_phone_user = get_user_meta($current_user->ID, 'is_phone_user', true);
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
        $is_phone_user = get_user_meta($user_id, 'is_phone_user', true);
        
        if ($is_phone_user) {
            // パスワード変更完了フラグを設定
            update_user_meta($user_id, 'password_changed_from_initial', true);
            update_user_meta($user_id, 'password_change_date', current_time('mysql'));
        }
    }
}


// ログイン時に初回ログインかチェック
add_action('wp_login', 'pms_check_first_login', 10, 2);
function pms_check_first_login($user_login, $user) {
    $is_phone_user = get_user_meta($user->ID, 'is_phone_user', true);
    $first_login = get_user_meta($user->ID, 'first_login_completed', true);
    
    if ($is_phone_user && !$first_login) {
        update_user_meta($user->ID, 'first_login_completed', true);
        update_user_meta($user->ID, 'first_login_date', current_time('mysql'));
    }
}

// 管理者がパスワードリセット時に4桁の数字を生成

// 管理画面でのパスワード自動生成を4桁にする
add_filter('random_password', 'pms_generate_4digit_password_for_phone_users', 10, 1);
function pms_generate_4digit_password_for_phone_users($password) {
    // 管理画面でユーザー編集中かチェック
    if (is_admin() && isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        $is_phone_user = get_user_meta($user_id, 'is_phone_user', true);
        
        if ($is_phone_user) {
            // 電話番号会員の場合は4桁の数字を生成
            return sprintf('%04d', rand(1000, 9999));
        }
    }
    
    return $password;
}

// ユーザープロファイル更新時に4桁パスワードを適用
add_action('user_profile_update_errors', 'pms_validate_phone_user_password', 10, 3);
function pms_validate_phone_user_password($errors, $update, $user) {
    if ($update && isset($_POST['pass1']) && !empty($_POST['pass1'])) {
        $is_phone_user = get_user_meta($user->ID, 'is_phone_user', true);
        
        if ($is_phone_user) {
            $new_password = $_POST['pass1'];
            
            // 4桁の数字以外の場合は警告（但し、保存は許可）
            if (!preg_match('/^\d{4}$/', $new_password)) {
                $errors->add('password_format', 
                    '<strong>注意:</strong> 電話番号会員には4桁の数字のパスワードを推奨します。' .
                    '<br>現在のパスワード: <code>' . esc_html($new_password) . '</code>' .
                    '<br><a href="#" onclick="jQuery(\'#pass1, #pass2\').val(\'' . sprintf('%04d', rand(1000, 9999)) . '\'); return false;" class="button">4桁パスワードを自動生成</a>'
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
        $is_phone_user = get_user_meta($user_id, 'is_phone_user', true);
        
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
    $is_phone_user = get_user_meta($user_id, 'is_phone_user', true);
    
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

// 管理画面の会員一覧でリセット情報も表示
add_action('manage_users_custom_column', 'pms_show_password_status_in_user_list', 10, 3);
function pms_show_password_status_in_user_list($value, $column_name, $user_id) {
    if ($column_name == 'password_status') {
        $is_phone_user = get_user_meta($user_id, 'is_phone_user', true);
        
        if ($is_phone_user) {
            $password_changed = get_user_meta($user_id, 'password_changed_from_initial', true);
            $admin_reset = get_user_meta($user_id, 'admin_password_reset_date', true);
            
            if ($password_changed) {
                return '<span style="color: #28a745;">✓ 変更済み</span>';
            } elseif ($admin_reset) {
                return '<span style="color: #ffc107;">📝 管理者設定</span>';
            } else {
                return '<span style="color: #dc3545;">⚠ 初期PW</span>';
            }
        }
    }
    
    return $value;
}