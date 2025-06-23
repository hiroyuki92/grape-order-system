<?php
/**
 * 管理画面機能
 * includes/admin.php
 */

// 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

// 管理画面メニューの追加
add_action('admin_menu', 'pms_add_admin_menu');
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

// 会員一覧ページ
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
                        <th style="width: 100px;">PW変更</th>
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
                    $password_status = pms_get_password_status($user->ID);
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
                            <span style="color: <?php echo $password_status['color']; ?>; font-weight: bold;">
                                <?php echo $password_status['label']; ?>
                            </span>
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
    <?php
}

// 会員登録ページ
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
    <?php
}

// 会員登録処理
function pms_register_phone_member($phone, $name) {
    // 電話番号の正規化
    $phone_clean = pms_normalize_phone($phone);
    
    // 電話番号の形式チェック
    if (!pms_validate_phone($phone)) {
        return array(
            'success' => false,
            'message' => '正しい電話番号を入力してください。'
        );
    }
    
    // 既存会員チェック
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
    $password = pms_generate_4digit_password();
    
    // 仮メールアドレス生成
    $fake_email = 'member_' . $phone_clean . '@temp.local';
    
    // WordPressユーザーデータ
    $user_data = array(
        'user_login' => $phone_clean,
        'user_email' => $fake_email,
        'user_pass' => $password,
        'display_name' => $name,
        'first_name' => $name,
        'role' => 'customer',
        'show_admin_bar_front' => false
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
    
    // ユーザーメタの設定
    pms_set_user_meta($user_id, $phone_clean, $phone, $name);
    
    return array(
        'success' => true,
        'user_id' => $user_id,
        'password' => $password,
        'phone_clean' => $phone_clean
    );
}