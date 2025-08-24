<?php
/**
 * WooCommerce 注文データCSVエクスポート機能
 * 
 * 保存場所: wp-content/themes/storefront-child/includes/woocommerce-csv-export.php
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// ===========================================
// 管理画面にCSVエクスポートボタンを追加
// ===========================================

/**
 * 注文一覧ページにCSVエクスポートボタンを追加
 */
function add_csv_export_button() {
    $screen = get_current_screen();
    
    // WooCommerce注文画面かチェック（HPOS対応）
    if (!$screen || !in_array($screen->id, ['shop_order', 'woocommerce_page_wc-orders'])) {
        return;
    }
    
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // ボタン追加を少し遅延させる
        setTimeout(function() {
            addCSVExportButtons();
        }, 500);
        
        function addCSVExportButtons() {
            // 既にボタンが追加されている場合はスキップ
            if ($('#csv-export-button').length > 0) {
                return;
            }
            
            // 一括操作エリアを探す（HPOS対応）
            var targetContainer = $('.tablenav.top .actions').first();
            
            // 従来の注文ページとHPOSページの両方に対応
            if (targetContainer.length === 0) {
                targetContainer = $('.tablenav-pages').first().parent().find('.alignleft.actions').first();
            }
            
            if (targetContainer.length === 0) {
                // フォールバック：テーブルの上に直接追加
                targetContainer = $('table.wp-list-table').first().before('<div class="csv-export-container" style="margin-bottom: 10px;"></div>');
                targetContainer = $('.csv-export-container');
            }
            
            if (targetContainer.length > 0) {
                // CSVエクスポートボタンを追加
                var exportButton = '<input type="button" id="csv-export-button" class="button" value="選択注文をCSV出力" style="margin-left: 5px; margin-right: 5px;">';
                var exportAllButton = '<input type="button" id="csv-export-all-button" class="button" value="全注文をCSV出力" style="margin-left: 5px;">';
                
                targetContainer.append(exportButton + exportAllButton);
                
                // イベントハンドラーを設定
                setupCSVExportEvents();
            }
        }
        
        function setupCSVExportEvents() {
            // 選択された注文のCSVエクスポート
            $('#csv-export-button').click(function() {
                var selectedOrders = [];
                
                // 選択された注文IDを取得（HPOS対応）
                var checkboxSelector = 'input[name="id[]"]:checked, input[name="post[]"]:checked';
                $(checkboxSelector).each(function() {
                    selectedOrders.push($(this).val());
                });
                
                if (selectedOrders.length === 0) {
                    alert('CSVに出力する注文を選択してください。');
                    return;
                }
                
                // 確認ダイアログ
                if (confirm(selectedOrders.length + '件の注文データをCSVファイルでダウンロードしますか？')) {
                    exportOrdersToCSV(selectedOrders);
                }
            });
            
            // 全注文のCSVエクスポート
            $('#csv-export-all-button').click(function() {
                if (confirm('全ての注文データをCSVファイルでダウンロードしますか？')) {
                    exportOrdersToCSV([]);
                }
            });
        }
        
        function exportOrdersToCSV(orderIds) {
            var form = $('<form method="post" style="display:none;">');
            form.append('<input type="hidden" name="action" value="export_orders_csv">');
            form.append('<input type="hidden" name="order_ids" value="' + orderIds.join(',') + '">');
            form.append('<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('export_orders_csv'); ?>">');
            
            $('body').append(form);
            form.submit();
            form.remove();
        }
    });
    </script>
    <?php
}
add_action('admin_footer', 'add_csv_export_button');

// ===========================================
// CSVエクスポート処理
// ===========================================

/**
 * CSVエクスポート処理のメイン関数
 */
function handle_csv_export_request() {
    // POST リクエストでなければ処理しない
    if (!isset($_POST['action']) || $_POST['action'] !== 'export_orders_csv') {
        return;
    }
    
    // 管理者権限をチェック
    if (!current_user_can('manage_woocommerce')) {
        wp_die('権限がありません。');
    }
    
    // nonceチェック
    if (!wp_verify_nonce($_POST['nonce'], 'export_orders_csv')) {
        wp_die('セキュリティチェックに失敗しました。');
    }
    
    // 全ての出力バッファを完全にクリア
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // 注文IDを取得
    $order_ids = array();
    if (!empty($_POST['order_ids'])) {
        $order_ids = array_map('intval', explode(',', sanitize_text_field($_POST['order_ids'])));
        $order_ids = array_filter($order_ids); // 空の値を除去
    }
    
    // CSVを生成して出力
    export_orders_to_csv($order_ids);
}
// admin_initの代わりにinitを使用（より早い段階で処理）
add_action('init', 'handle_csv_export_request');

/**
 * 注文データをCSV形式で出力
 * @param array $order_ids 出力する注文ID（空の場合は全注文）
 */
function export_orders_to_csv($order_ids = array()) {
    // 全ての出力バッファを完全にクリア
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // 注文データを取得
    $args = array(
        'limit' => -1,
        'status' => 'any',
        'orderby' => 'ID',
        'order' => 'ASC',
    );
    
    if (!empty($order_ids)) {
        $args['post__in'] = $order_ids;
    }
    
    $orders = wc_get_orders($args);
    
    if (empty($orders)) {
        wp_die('出力する注文データがありません。');
    }
    
    // CSVファイル名を生成
    $filename = 'orders_' . date('Y-m-d_H-i-s') . '.csv';
    
    // HTTPヘッダーを設定
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    header('Pragma: public');
    
    // UTF-8 BOM を追加（Excel対応）
    echo "\xEF\xBB\xBF";
    
    // CSV出力用のファイルハンドルを開く
    $output = fopen('php://output', 'w');
    
    // CSVヘッダーを出力
    $headers = get_csv_headers();
    fputcsv($output, $headers);
    
    // 注文データをCSVに出力
    foreach ($orders as $order) {
        $row_data = get_order_csv_data($order);
        fputcsv($output, $row_data);
    }
    
    fclose($output);
    exit;
}

/**
 * 都道府県コードを日本語名に変換
 * @param string $state_code 都道府県コード（JP01, JP13など）
 * @return string 日本語都道府県名
 */
function convert_state_code_to_japanese($state_code) {
    // 都道府県コードマッピング
    $state_mapping = array(
        'JP01' => '北海道',
        'JP02' => '青森県',
        'JP03' => '岩手県',
        'JP04' => '宮城県',
        'JP05' => '秋田県',
        'JP06' => '山形県',
        'JP07' => '福島県',
        'JP08' => '茨城県',
        'JP09' => '栃木県',
        'JP10' => '群馬県',
        'JP11' => '埼玉県',
        'JP12' => '千葉県',
        'JP13' => '東京都',
        'JP14' => '神奈川県',
        'JP15' => '新潟県',
        'JP16' => '富山県',
        'JP17' => '石川県',
        'JP18' => '福井県',
        'JP19' => '山梨県',
        'JP20' => '長野県',
        'JP21' => '岐阜県',
        'JP22' => '静岡県',
        'JP23' => '愛知県',
        'JP24' => '三重県',
        'JP25' => '滋賀県',
        'JP26' => '京都府',
        'JP27' => '大阪府',
        'JP28' => '兵庫県',
        'JP29' => '奈良県',
        'JP30' => '和歌山県',
        'JP31' => '鳥取県',
        'JP32' => '島根県',
        'JP33' => '岡山県',
        'JP34' => '広島県',
        'JP35' => '山口県',
        'JP36' => '徳島県',
        'JP37' => '香川県',
        'JP38' => '愛媛県',
        'JP39' => '高知県',
        'JP40' => '福岡県',
        'JP41' => '佐賀県',
        'JP42' => '長崎県',
        'JP43' => '熊本県',
        'JP44' => '大分県',
        'JP45' => '宮崎県',
        'JP46' => '鹿児島県',
        'JP47' => '沖縄県'
    );
    
    // コードが存在する場合は日本語名を返す、そうでなければ元の値を返す
    if (isset($state_mapping[$state_code])) {
        return $state_mapping[$state_code];
    }
    
    return $state_code;
}

/**
 * CSVファイルのヘッダー項目を取得
 * @return array CSVヘッダー配列
 */
function get_csv_headers() {
    return array(
        '注文ID',
        '注文日時',
        '注文ステータス',
        '顧客名（ユーザー名）',
        '顧客電話番号',
        '顧客メール',
        '送り主名',
        '送り主会社名',
        '送り主郵便番号',
        '送り主都道府県',
        '送り主市区町村',
        '送り主住所1',
        '送り主住所2',
        '送り主電話番号',
        'お届け先名',
        'お届け先会社名',
        'お届け先郵便番号',
        'お届け先都道府県',
        'お届け先市区町村',
        'お届け先住所1',
        'お届け先住所2',
        'お届け先電話番号',
        '商品名',
        '商品カテゴリー',
        '商品数量',
        '商品価格',
        '小計',
        '配送料',
        '税額',
        '合計金額',
        '支払い方法',
        '配送希望日',
        '配送希望時間',
        '領収書希望',
        '領収書宛名',
        '担当者',
        '備考',
        '注文メモ'
    );
}

/**
 * 注文データをCSV行データに変換
 * @param WC_Order $order 注文オブジェクト
 * @return array CSV行データ
 */
function get_order_csv_data($order) {
    // 基本情報
    $order_id = $order->get_id();
    $order_date = $order->get_date_created()->date('Y-m-d H:i:s');
    $order_status = wc_get_order_status_name($order->get_status());
    
    // 顧客情報（ユーザー名）
    $customer_id = $order->get_customer_id();
    $customer_name = '';
    if ($customer_id) {
        $user = get_user_by('id', $customer_id);
        if ($user) {
            // ユーザーの表示名を使用
            $customer_name = $user->display_name;
        }
    }
    // 顧客情報がない場合は請求先名前を使用
    if (empty($customer_name)) {
        $customer_name = $order->get_formatted_billing_full_name();
    }
    
    $customer_phone = $order->get_billing_phone();
    $customer_email = $order->get_billing_email();
    
    // 送り主情報（請求先）
    $billing_name = $order->get_formatted_billing_full_name();
    $billing_company = $order->get_billing_company();
    $billing_postcode = $order->get_billing_postcode();
    $billing_state = $order->get_billing_state();
    $billing_city = $order->get_billing_city();
    $billing_address_1 = $order->get_billing_address_1();
    $billing_address_2 = $order->get_billing_address_2();
    $billing_phone = $order->get_billing_phone();
    
    // 送り主の都道府県コードを日本語名に変換
    $billing_state = convert_state_code_to_japanese($billing_state);
    
    // お届け先情報（配送先）
    $shipping_name = $order->get_formatted_shipping_full_name();
    $shipping_company = $order->get_shipping_company();
    $shipping_postcode = $order->get_shipping_postcode();
    $shipping_state = $order->get_shipping_state();
    $shipping_city = $order->get_shipping_city();
    $shipping_address_1 = $order->get_shipping_address_1();
    $shipping_address_2 = $order->get_shipping_address_2();
    $shipping_phone = $order->get_meta('_shipping_phone', true);
    
    // 都道府県コードを日本語名に変換
    $shipping_state = convert_state_code_to_japanese($shipping_state);
    
    // 商品情報（複数商品の場合は改行で区切る）
    $items = $order->get_items();
    $product_names = array();
    $product_categories = array();
    $product_quantities = array();
    $product_prices = array();
    
    foreach ($items as $item) {
        $product_names[] = $item->get_name();
        $product_quantities[] = $item->get_quantity();
        $product_prices[] = number_format($item->get_subtotal(), 0);
        
        // 商品カテゴリーを取得
        $product_id = $item->get_product_id();
        $product_categories_terms = get_the_terms($product_id, 'product_cat');
        $category_names = array();
        
        if ($product_categories_terms && !is_wp_error($product_categories_terms)) {
            foreach ($product_categories_terms as $term) {
                $category_names[] = $term->name;
            }
        }
        
        $product_categories[] = implode(', ', $category_names);
    }
    
    $products_str = implode("\n", $product_names);
    $categories_str = implode("\n", $product_categories);
    $quantities_str = implode("\n", $product_quantities);
    $prices_str = implode("\n", $product_prices);
    
    // 金額情報
    $subtotal = $order->get_subtotal();
    $shipping_total = $order->get_shipping_total();
    $tax_total = $order->get_total_tax();
    $total = $order->get_total();
    
    // 支払い方法
    $payment_method = $order->get_payment_method_title();
    
    // 配送希望日・時間（カスタムフィールドから取得）
    $delivery_date = $order->get_meta('wc4jp-delivery-date', true);
    $delivery_time = $order->get_meta('wc4jp-delivery-time-zone', true);
    
    // 配送希望日をフォーマット（必要に応じて）
    if ($delivery_date) {
        // 既に "2025/06/26" 形式の場合は、Y-m-d 形式に変換
        if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $delivery_date)) {
            $delivery_date = str_replace('/', '-', $delivery_date);
        }
    } else {
        $delivery_date = '';
    }
    
    // 配送時間がない場合は空文字
    if (!$delivery_time) {
        $delivery_time = '';
    }
    
    // 領収書情報
    $receipt_required = get_post_meta($order_id, '_receipt_required', true);
    $receipt_name = get_post_meta($order_id, '_receipt_name', true);
    
    // 領収書希望を日本語で表示
    $receipt_status = $receipt_required ? '必要' : '不要';
    
    // 領収書宛名がない場合は空文字
    if (!$receipt_name) {
        $receipt_name = '';
    }
    
    // 担当者情報
    $assigned_manager = '';
    $customer_id = $order->get_customer_id();
    if ($customer_id) {
        $assigned_manager_id = get_user_meta($customer_id, 'assigned_order_manager', true);
        if ($assigned_manager_id) {
            $manager = get_user_by('id', $assigned_manager_id);
            if ($manager) {
                $assigned_manager = $manager->display_name;
            }
        }
    }
    
    // 備考・メモ
    $customer_note = $order->get_customer_note();
    $order_notes = '';
    
    // 注文メモを取得
    $notes = wc_get_order_notes(array('order_id' => $order_id, 'order_note_type' => 'private'));
    $note_contents = array();
    foreach ($notes as $note) {
        $note_contents[] = strip_tags($note->content);
    }
    $order_notes = implode("\n", $note_contents);
    
    return array(
        $order_id,
        $order_date,
        $order_status,
        $customer_name,
        $customer_phone,
        $customer_email,
        $billing_name,
        $billing_company,
        $billing_postcode,
        $billing_state,
        $billing_city,
        $billing_address_1,
        $billing_address_2,
        $billing_phone,
        $shipping_name,
        $shipping_company,
        $shipping_postcode,
        $shipping_state,
        $shipping_city,
        $shipping_address_1,
        $shipping_address_2,
        $shipping_phone,
        $products_str,
        $categories_str,
        $quantities_str,
        $prices_str,
        number_format($subtotal, 0),
        number_format($shipping_total, 0),
        number_format($tax_total, 0),
        number_format($total, 0),
        $payment_method,
        $delivery_date,
        $delivery_time,
        $receipt_status,
        $receipt_name,
        $assigned_manager,
        $customer_note,
        $order_notes
    );
}

/**
 * 管理画面にCSVエクスポート機能の説明を追加
 */
function add_csv_export_admin_notice() {
    // CSVエクスポート処理中やAJAX処理中は何も出力しない
    if (isset($_POST['action']) && $_POST['action'] === 'export_orders_csv') {
        return;
    }
    
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }
    
    $screen = get_current_screen();
    
    // HPOS対応のスクリーンIDもチェック
    if ($screen && in_array($screen->id, ['edit-shop_order', 'woocommerce_page_wc-orders'])) {
        ?>
        <div class="notice notice-info">
            <p>
                <strong>CSVエクスポート機能：</strong>
                注文データをCSV形式でダウンロードできます。
                <br>
                ・特定の注文のみ出力：チェックボックスで注文を選択してから「選択注文をCSV出力」ボタンをクリック
                <br>
                ・全注文データ出力：「全注文をCSV出力」ボタンをクリック
                <br>
                ※ボタンが表示されない場合は、ページを再読み込みしてください。
            </p>
            
            <!-- 直接的なCSVエクスポートボタンも追加 -->
            <div style="margin-top: 10px;">
                <button type="button" id="csv-export-direct" class="button button-primary">選択注文をCSV出力</button>
                <button type="button" id="csv-export-all-direct" class="button button-secondary" style="margin-left: 10px;">全注文をCSV出力</button>
            </div>
            
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#csv-export-direct').click(function() {
                    var selectedOrders = [];
                    var checkboxSelector = 'input[name="id[]"]:checked, input[name="post[]"]:checked';
                    $(checkboxSelector).each(function() {
                        selectedOrders.push($(this).val());
                    });
                    
                    if (selectedOrders.length === 0) {
                        alert('CSVに出力する注文を選択してください。');
                        return;
                    }
                    
                    if (confirm(selectedOrders.length + '件の注文データをCSVファイルでダウンロードしますか？')) {
                        submitCSVExport(selectedOrders);
                    }
                });
                
                $('#csv-export-all-direct').click(function() {
                    if (confirm('全ての注文データをCSVファイルでダウンロードしますか？')) {
                        submitCSVExport([]);
                    }
                });
                
                function submitCSVExport(orderIds) {
                    var form = $('<form method="post" style="display:none;">');
                    form.append('<input type="hidden" name="action" value="export_orders_csv">');
                    form.append('<input type="hidden" name="order_ids" value="' + orderIds.join(',') + '">');
                    form.append('<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('export_orders_csv'); ?>">');
                    
                    $('body').append(form);
                    form.submit();
                    form.remove();
                }
            });
            </script>
        </div>
        <?php
    }
}
add_action('admin_notices', 'add_csv_export_admin_notice');

?>