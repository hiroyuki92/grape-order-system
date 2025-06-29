<?php
/**
 * WooCommerce カスタム注文ステータス機能
 * 
 * 保存場所: wp-content/themes/storefront-child/includes/woocommerce-order-status.php
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// ===========================================
// カスタム注文ステータスの登録
// ===========================================

/**
 * カスタム注文ステータスを登録
 */
function register_custom_order_statuses() {
    // 「準備中」ステータス
    register_post_status('wc-preparing', array(
        'label'                     => '準備中',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('準備中 (%s)', '準備中 (%s)')
    ));
    
    // 「配送待ち」ステータス
    register_post_status('wc-awaiting-shipment', array(
        'label'                     => '配送待ち',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('配送待ち (%s)', '配送待ち (%s)')
    ));
    
    // 「出荷済み」ステータス
    register_post_status('wc-shipped', array(
        'label'                     => '出荷済み',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('出荷済み (%s)', '出荷済み (%s)')
    ));
}
add_action('init', 'register_custom_order_statuses');

/**
 * WooCommerceの注文ステータス一覧に追加
 */
function add_custom_order_statuses($order_statuses) {
    $new_order_statuses = array();
    
    // 既存のステータスを維持しつつ、新しいステータスを挿入
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        
        // 「保留中」の後に「準備中」を追加
        if ('wc-pending' === $key) {
            $new_order_statuses['wc-preparing'] = '準備中';
        }
        
        // 「処理中」の後にカスタムステータスを追加
        if ('wc-processing' === $key) {
            $new_order_statuses['wc-awaiting-shipment'] = '配送待ち';
            $new_order_statuses['wc-shipped'] = '出荷済み';
        }
    }
    
    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_custom_order_statuses');

// ===========================================
// ステータス表示のカスタマイズ
// ===========================================

/**
 * 管理画面でのステータス色をカスタマイズ
 */
function custom_order_status_styles() {
    echo '<style>
        .order-status.status-preparing {
            background: #ffba00;
            color: white;
            border-radius: 3px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: bold;
        }
        .order-status.status-awaiting-shipment {
            background: #2ea2cc;
            color: white;
            border-radius: 3px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: bold;
        }
        .order-status.status-shipped {
            background: #28a745;
            color: white;
            border-radius: 3px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: bold;
        }
        
        /* 注文一覧でのステータス表示も調整 */
        .widefat .column-order_status mark.order-status {
            background: none !important;
            color: inherit !important;
            font-weight: normal;
        }
        
        .widefat .column-order_status mark.status-preparing {
            background: #ffba00 !important;
            color: white !important;
        }
        
        .widefat .column-order_status mark.status-awaiting-shipment {
            background: #2ea2cc !important;
            color: white !important;
        }
        
        .widefat .column-order_status mark.status-shipped {
            background: #28a745 !important;
            color: white !important;
        }
    </style>';
}
add_action('admin_head', 'custom_order_status_styles');

/**
 * 注文一覧ページでのステータス表示をカスタマイズ
 */
function custom_order_status_column($column, $post_id) {
    if ($column == 'order_status') {
        $order = wc_get_order($post_id);
        if (!$order) return;
        
        $status = $order->get_status();
        $status_name = wc_get_order_status_name($status);
        
        // カスタムステータス用のCSSクラスを追加
        $css_class = '';
        switch ($status) {
            case 'preparing':
                $css_class = 'status-preparing';
                break;
            case 'awaiting-shipment':
                $css_class = 'status-awaiting-shipment';
                break;
            case 'shipped':
                $css_class = 'status-shipped';
                break;
        }
        
        if ($css_class) {
            echo '<mark class="order-status ' . esc_attr($css_class) . '">' . esc_html($status_name) . '</mark>';
        }
    }
}
add_action('manage_shop_order_posts_custom_column', 'custom_order_status_column', 10, 2);

?>