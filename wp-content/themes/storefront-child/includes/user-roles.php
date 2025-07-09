<?php
/**
 * ユーザー権限管理
 * 
 * 保存場所: wp-content/themes/storefront-child/includes/user-roles.php
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// ===========================================
// 注文担当者の権限グループ作成
// ===========================================

/**
 * 注文担当者の権限グループを作成
 */
function create_order_manager_role() {
    // 既存の権限グループを確認
    $role = get_role('order_manager');
    if (!$role) {
        // 注文担当者の権限グループを作成
        add_role('order_manager', '注文担当者', array(
            // 基本的な権限
            'read' => true,
            'read_private_posts' => false,
            'edit_posts' => false,
            'edit_others_posts' => false,
            'edit_private_posts' => false,
            'edit_published_posts' => false,
            'publish_posts' => false,
            'delete_posts' => false,
            'delete_private_posts' => false,
            'delete_published_posts' => false,
            'delete_others_posts' => false,
            'manage_categories' => false,
            'manage_links' => false,
            'moderate_comments' => false,
            'upload_files' => false,
            'import' => false,
            'unfiltered_html' => false,
            
            // WooCommerce権限
            'manage_woocommerce' => true,
            'view_woocommerce_reports' => true,
            'read_shop_order' => true,
            'read_private_shop_orders' => true,
            'edit_shop_order' => true,
            'edit_shop_orders' => true,
            'edit_others_shop_orders' => true,
            'edit_private_shop_orders' => true,
            'edit_published_shop_orders' => true,
            'publish_shop_orders' => true,
            'delete_shop_order' => false,
            'delete_shop_orders' => false,
            'delete_private_shop_orders' => false,
            'delete_published_shop_orders' => false,
            'delete_others_shop_orders' => false,
            
            // 商品権限（読み取り専用）
            'read_product' => true,
            'read_private_products' => true,
            'edit_product' => false,
            'edit_products' => false,
            'edit_others_products' => false,
            'edit_private_products' => false,
            'edit_published_products' => false,
            'publish_products' => false,
            'delete_product' => false,
            'delete_products' => false,
            'delete_private_products' => false,
            'delete_published_products' => false,
            'delete_others_products' => false,
            
            // 顧客権限
            'read_shop_customer' => true,
            'edit_shop_customer' => true,
            'edit_shop_customers' => true,
            'edit_others_shop_customers' => true,
            'edit_private_shop_customers' => true,
            'edit_published_shop_customers' => true,
            'publish_shop_customers' => true,
            'delete_shop_customer' => false,
            'delete_shop_customers' => false,
            'delete_private_shop_customers' => false,
            'delete_published_shop_customers' => false,
            'delete_others_shop_customers' => false,
            
            // クーポン権限（読み取り専用）
            'read_shop_coupon' => true,
            'read_private_shop_coupons' => true,
            'edit_shop_coupon' => false,
            'edit_shop_coupons' => false,
            'edit_others_shop_coupons' => false,
            'edit_private_shop_coupons' => false,
            'edit_published_shop_coupons' => false,
            'publish_shop_coupons' => false,
            'delete_shop_coupon' => false,
            'delete_shop_coupons' => false,
            'delete_private_shop_coupons' => false,
            'delete_published_shop_coupons' => false,
            'delete_others_shop_coupons' => false,
            
            // 管理画面アクセス
            'read' => true,
            'level_0' => true,
            'level_1' => true,
            'level_2' => true,
        ));
    }
}

/**
 * 注文担当者の権限グループを削除
 */
function remove_order_manager_role() {
    remove_role('order_manager');
}

// テーマ有効化時に権限グループを作成
add_action('after_switch_theme', 'create_order_manager_role');

// テーマ無効化時に権限グループを削除
add_action('switch_theme', 'remove_order_manager_role');

// ===========================================
// 管理画面メニューの制限
// ===========================================

/**
 * 注文担当者の管理画面メニューを制限
 */
function restrict_order_manager_menu() {
    if (current_user_can('order_manager') && !current_user_can('manage_options')) {
        // 投稿関連を非表示
        remove_menu_page('edit.php');
        remove_menu_page('edit.php?post_type=page');
        remove_menu_page('edit-comments.php');
        
        // メディアを非表示
        remove_menu_page('upload.php');
        
        // 外観を非表示
        remove_menu_page('themes.php');
        
        // プラグインを非表示
        remove_menu_page('plugins.php');
        
        // ユーザー管理を非表示
        remove_menu_page('users.php');
        
        // ツールを非表示
        remove_menu_page('tools.php');
        
        // 設定を非表示
        remove_menu_page('options-general.php');
        
        // WooCommerce関連のみ表示
        // WooCommerce -> 注文
        // WooCommerce -> 顧客
        // WooCommerce -> レポート
        // ダッシュボード（基本情報用）
        
        // 不要なWooCommerceサブメニューを非表示
        remove_submenu_page('woocommerce', 'wc-settings');
        remove_submenu_page('woocommerce', 'wc-status');
        remove_submenu_page('woocommerce', 'wc-addons');
    }
}
add_action('admin_menu', 'restrict_order_manager_menu', 999);

// ===========================================
// 管理画面の表示制限
// ===========================================

/**
 * 注文担当者用の管理画面通知
 */
function order_manager_admin_notice() {
    if (current_user_can('order_manager') && !current_user_can('manage_options')) {
        $current_screen = get_current_screen();
        
        if ($current_screen && in_array($current_screen->id, ['dashboard', 'woocommerce_page_wc-orders', 'shop_order'])) {
            ?>
            <div class="notice notice-info">
                <p>
                    <strong>注文担当者モード</strong>
                    <br>
                    あなたは注文担当者として、注文管理、顧客管理、レポート閲覧の権限があります。
                    <br>
                    商品の追加・編集や設定変更は管理者権限が必要です。
                </p>
            </div>
            <?php
        }
    }
}
add_action('admin_notices', 'order_manager_admin_notice');

/**
 * 注文担当者の権限説明をユーザー一覧に追加
 */
function add_order_manager_role_description($roles) {
    $roles['order_manager'] = '注文担当者';
    return $roles;
}
add_filter('editable_roles', 'add_order_manager_role_description');

// ===========================================
// CSV エクスポート権限の調整
// ===========================================

/**
 * CSV エクスポート機能の権限チェックを調整
 */
function adjust_csv_export_capability() {
    // CSVエクスポート機能を注文担当者にも許可
    if (current_user_can('order_manager')) {
        add_filter('user_has_cap', function($allcaps, $cap, $args) {
            if (in_array('manage_woocommerce', $cap)) {
                $allcaps['manage_woocommerce'] = true;
            }
            return $allcaps;
        }, 10, 3);
    }
}
add_action('init', 'adjust_csv_export_capability');

// ===========================================
// 顧客への注文担当者割り当て機能
// 一時的に無効化（ユーザー一覧問題の解決後に再有効化）
// ===========================================

/**
 * 顧客プロフィールに注文担当者の選択フィールドを追加
 */
function add_assigned_manager_field_to_customer_profile($user) {
    if (!current_user_can('manage_options') && !current_user_can('order_manager')) {
        return;
    }
    
    // 現在割り当てられている注文担当者を取得
    $assigned_manager = get_user_meta($user->ID, 'assigned_order_manager', true);
    
    // 注文担当者一覧を取得
    $order_managers = get_users(array('role' => 'order_manager'));
    ?>
    <h3>注文担当者</h3>
    <table class="form-table">
        <tr>
            <th><label for="assigned_order_manager">割り当て注文担当者</label></th>
            <td>
                <select name="assigned_order_manager" id="assigned_order_manager">
                    <option value="">未割り当て</option>
                    <?php foreach ($order_managers as $manager) : ?>
                        <option value="<?php echo $manager->ID; ?>" <?php selected($assigned_manager, $manager->ID); ?>>
                            <?php echo esc_html($manager->display_name . ' (' . $manager->user_login . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">この顧客の注文を管理する注文担当者を選択します。</p>
            </td>
        </tr>
    </table>
    <?php
}
// 一時的に無効化
// add_action('show_user_profile', 'add_assigned_manager_field_to_customer_profile');
// add_action('edit_user_profile', 'add_assigned_manager_field_to_customer_profile');

/**
 * 顧客プロフィールの注文担当者の保存
 */
function save_assigned_manager_field($user_id) {
    if (!current_user_can('manage_options') && !current_user_can('order_manager')) {
        return;
    }
    
    if (isset($_POST['assigned_order_manager'])) {
        $assigned_manager = sanitize_text_field($_POST['assigned_order_manager']);
        if (empty($assigned_manager)) {
            delete_user_meta($user_id, 'assigned_order_manager');
        } else {
            update_user_meta($user_id, 'assigned_order_manager', $assigned_manager);
        }
    }
}
// 一時的に無効化
// add_action('personal_options_update', 'save_assigned_manager_field');
// add_action('edit_user_profile_update', 'save_assigned_manager_field');

/**
 * 顧客一覧ページに注文担当者の列を追加
 */
function add_assigned_manager_column_to_users_table($columns) {
    // 管理者または注文担当者のみに表示
    if (current_user_can('manage_options') || current_user_can('order_manager')) {
        $columns['assigned_manager'] = '注文担当者';
    }
    return $columns;
}
// 一時的にユーザー列の機能を無効化（問題が解決したら再有効化）
// add_filter('manage_users_columns', 'add_assigned_manager_column_to_users_table');

/**
 * 顧客一覧ページの注文担当者列の内容を表示
 */
function show_assigned_manager_column_content($value, $column_name, $user_id) {
    if ($column_name == 'assigned_manager' && (current_user_can('manage_options') || current_user_can('order_manager'))) {
        $assigned_manager_id = get_user_meta($user_id, 'assigned_order_manager', true);
        if ($assigned_manager_id) {
            $manager = get_user_by('id', $assigned_manager_id);
            if ($manager) {
                $value = esc_html($manager->display_name);
            } else {
                $value = '未割り当て';
            }
        } else {
            $value = '未割り当て';
        }
    }
    return $value;
}
// add_filter('manage_users_custom_column', 'show_assigned_manager_column_content', 10, 3);

/**
 * 注文詳細ページに担当者情報を表示
 */
function show_assigned_manager_in_order_details($order) {
    $customer_id = $order->get_customer_id();
    if (!$customer_id) {
        return;
    }
    
    $assigned_manager_id = get_user_meta($customer_id, 'assigned_order_manager', true);
    if ($assigned_manager_id) {
        $manager = get_user_by('id', $assigned_manager_id);
        if ($manager) {
            echo '<div class="order-assigned-manager" style="margin: 10px 0; padding: 10px; background: #f0f0f1; border-left: 4px solid #0073aa;">';
            echo '<strong>担当者:</strong> ' . esc_html($manager->display_name) . ' (' . esc_html($manager->user_login) . ')';
            echo '</div>';
        }
    }
}
// 一時的に無効化
// add_action('woocommerce_admin_order_data_after_billing_address', 'show_assigned_manager_in_order_details');

/**
 * 注文一覧ページに担当者フィルターを追加
 */
function add_assigned_manager_filter_to_orders() {
    global $typenow;
    
    if ($typenow == 'shop_order' || (isset($_GET['page']) && $_GET['page'] == 'wc-orders')) {
        $order_managers = get_users(array('role' => 'order_manager'));
        $selected_manager = isset($_GET['assigned_manager']) ? $_GET['assigned_manager'] : '';
        
        echo '<select name="assigned_manager" id="assigned_manager_filter">';
        echo '<option value="">全ての担当者</option>';
        foreach ($order_managers as $manager) {
            $selected = selected($selected_manager, $manager->ID, false);
            echo '<option value="' . $manager->ID . '" ' . $selected . '>' . esc_html($manager->display_name) . '</option>';
        }
        echo '</select>';
    }
}
// 一時的に無効化
// add_action('restrict_manage_posts', 'add_assigned_manager_filter_to_orders');

/**
 * 注文一覧の担当者フィルターの実装
 */
function filter_orders_by_assigned_manager($query) {
    global $pagenow, $typenow;
    
    if ($pagenow == 'edit.php' && $typenow == 'shop_order' && isset($_GET['assigned_manager']) && !empty($_GET['assigned_manager'])) {
        $assigned_manager = sanitize_text_field($_GET['assigned_manager']);
        
        // 指定された担当者が割り当てられた顧客のIDを取得
        $customers_with_manager = get_users(array(
            'meta_key' => 'assigned_order_manager',
            'meta_value' => $assigned_manager,
            'fields' => 'ids'
        ));
        
        if (!empty($customers_with_manager)) {
            $query->set('meta_query', array(
                array(
                    'key' => '_customer_user',
                    'value' => $customers_with_manager,
                    'compare' => 'IN'
                )
            ));
        } else {
            // 該当する顧客がいない場合は結果を空にする
            $query->set('post__in', array(0));
        }
    }
}
// 一時的に無効化
// add_action('parse_query', 'filter_orders_by_assigned_manager');

/**
 * 注文担当者の権限制限（自分の担当顧客の注文のみ表示）
 */
function restrict_order_manager_to_assigned_customers($query) {
    global $pagenow, $typenow;
    
    // 管理画面でのみ動作、かつ注文ページでのみ動作
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    // 注文担当者で、管理者権限がない場合のみ制限
    if (current_user_can('order_manager') && !current_user_can('manage_options')) {
        // 注文ページでのみ制限を適用
        if (($pagenow == 'edit.php' && $typenow == 'shop_order') || 
            (isset($_GET['page']) && $_GET['page'] == 'wc-orders')) {
            
            $current_user_id = get_current_user_id();
            
            // 現在のユーザーが担当している顧客のIDを取得
            $assigned_customers = get_users(array(
                'meta_key' => 'assigned_order_manager',
                'meta_value' => $current_user_id,
                'fields' => 'ids'
            ));
            
            if (!empty($assigned_customers)) {
                $query->set('meta_query', array(
                    array(
                        'key' => '_customer_user',
                        'value' => $assigned_customers,
                        'compare' => 'IN'
                    )
                ));
            } else {
                // 担当顧客がいない場合は結果を空にする
                $query->set('post__in', array(0));
            }
        }
    }
}
// 一時的に無効化
// add_action('pre_get_posts', 'restrict_order_manager_to_assigned_customers');

// ===========================================
// 権限グループの管理機能
// ===========================================

/**
 * 管理者用：権限グループ管理ページの追加
 */
function add_role_management_page() {
    if (current_user_can('manage_options')) {
        add_users_page(
            '注文担当者管理',
            '注文担当者管理',
            'manage_options',
            'order-manager-users',
            'order_manager_users_page'
        );
    }
}
add_action('admin_menu', 'add_role_management_page');

/**
 * 注文担当者管理ページの表示
 */
function order_manager_users_page() {
    if (!current_user_can('manage_options')) {
        wp_die('権限がありません。');
    }
    
    // ユーザーの権限変更処理
    if (isset($_POST['action']) && $_POST['action'] === 'update_user_role') {
        if (wp_verify_nonce($_POST['nonce'], 'update_user_role')) {
            $user_id = intval($_POST['user_id']);
            $new_role = sanitize_text_field($_POST['new_role']);
            
            if ($new_role === 'order_manager' || $new_role === 'remove') {
                $user = get_user_by('id', $user_id);
                if ($user) {
                    if ($new_role === 'order_manager') {
                        $user->set_role('order_manager');
                        echo '<div class="notice notice-success"><p>ユーザーを注文担当者に変更しました。</p></div>';
                    } else {
                        $user->remove_role('order_manager');
                        echo '<div class="notice notice-success"><p>注文担当者権限を削除しました。</p></div>';
                    }
                }
            }
        }
    }
    
    // 注文担当者の一覧を取得
    $order_managers = get_users(array('role' => 'order_manager'));
    ?>
    <div class="wrap">
        <h1>注文担当者管理</h1>
        
        <h2>現在の注文担当者</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ユーザー名</th>
                    <th>表示名</th>
                    <th>メールアドレス</th>
                    <th>登録日</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($order_managers)) : ?>
                    <tr>
                        <td colspan="5">注文担当者はいません。</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($order_managers as $user) : ?>
                        <tr>
                            <td><?php echo esc_html($user->user_login); ?></td>
                            <td><?php echo esc_html($user->display_name); ?></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td><?php echo esc_html($user->user_registered); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="update_user_role">
                                    <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">
                                    <input type="hidden" name="new_role" value="remove">
                                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('update_user_role'); ?>">
                                    <button type="submit" class="button button-secondary" onclick="return confirm('注文担当者権限を削除しますか？')">権限削除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <h2>注文担当者に変更</h2>
        <p>既存のユーザーを注文担当者に変更できます。</p>
        
        <?php
        // 注文担当者以外のユーザーを取得
        $all_users = get_users(array('role__not_in' => array('order_manager')));
        ?>
        
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="user_select">ユーザー選択</label></th>
                    <td>
                        <select name="user_id" id="user_select" required>
                            <option value="">ユーザーを選択してください</option>
                            <?php foreach ($all_users as $user) : ?>
                                <option value="<?php echo $user->ID; ?>">
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_login . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <input type="hidden" name="action" value="update_user_role">
            <input type="hidden" name="new_role" value="order_manager">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('update_user_role'); ?>">
            
            <p class="submit">
                <input type="submit" class="button button-primary" value="注文担当者に変更">
            </p>
        </form>
        
        <h2>権限の詳細</h2>
        <p><strong>注文担当者の権限:</strong></p>
        <ul>
            <li>✓ 注文の閲覧・編集・ステータス変更</li>
            <li>✓ 顧客情報の閲覧・編集</li>
            <li>✓ 注文レポートの閲覧</li>
            <li>✓ CSVエクスポート機能の使用</li>
            <li>✓ 商品情報の閲覧（読み取り専用）</li>
            <li>✗ 商品の追加・編集・削除</li>
            <li>✗ 設定の変更</li>
            <li>✗ プラグインの管理</li>
            <li>✗ ユーザー管理</li>
        </ul>
    </div>
    <?php
}

?>