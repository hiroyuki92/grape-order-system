<?php
/**
 * 領収書機能
 */

if (!defined('ABSPATH')) {
    exit;
}

// Japanized for WooCommerceの設定に合わせて領収書フィールドを追加
add_filter('woocommerce_checkout_fields', 'add_receipt_to_order_fields');
function add_receipt_to_order_fields($fields) {
    $fields['order']['receipt_required'] = array(
        'type' => 'checkbox',
        'label' => __('領収書が必要です'),
        'required' => false,
        'class' => array('form-row-wide'),
        'priority' => 25, // 注文メモの前に配置
    );
    
    $fields['order']['receipt_name'] = array(
        'type' => 'text',
        'label' => __('領収書宛名'),
        'required' => false,
        'class' => array('form-row-wide'),
        'priority' => 26,
        'placeholder' => __('領収書の宛名（法人名等）'),
    );
    
    return $fields;
}

// バリデーション
add_action('woocommerce_checkout_process', 'validate_receipt_fields');
function validate_receipt_fields() {
    // 領収書が必要な場合、宛名も必須にする
    if (!empty($_POST['receipt_required']) && empty($_POST['receipt_name'])) {
        wc_add_notice(__('領収書が必要な場合は宛名の入力が必要です。'), 'error');
    }
}

// データベース保存
add_action('woocommerce_checkout_update_order_meta', 'save_receipt_order_fields');
function save_receipt_order_fields($order_id) {
    // 領収書の要不要を保存
    if (!empty($_POST['receipt_required'])) {
        update_post_meta($order_id, '_receipt_required', 1);
    }
    
    // 領収書宛名を保存
    if (!empty($_POST['receipt_name'])) {
        update_post_meta($order_id, '_receipt_name', sanitize_text_field($_POST['receipt_name']));
    }
}

// チェックアウトページにJavaScriptを追加してリアルタイムバリデーション
add_action('wp_footer', 'add_receipt_validation_script');
function add_receipt_validation_script() {
    if (is_checkout() && !is_wc_endpoint_url()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // 領収書チェックボックスの状態変更時
            $('body').on('change', 'input[name="receipt_required"]', function() {
                var receiptNameField = $('input[name="receipt_name"]');
                var receiptNameRow = receiptNameField.closest('.form-row');
                
                if ($(this).is(':checked')) {
                    // チェックされた場合、宛名フィールドを必須にする
                    receiptNameField.attr('required', true);
                    receiptNameRow.addClass('validate-required');
                    
                    // ラベルに「*」を追加
                    var label = receiptNameRow.find('label');
                    if (!label.find('.required').length) {
                        label.append(' <abbr class="required" title="必須">*</abbr>');
                    }
                } else {
                    // チェックが外された場合、必須を解除する
                    receiptNameField.removeAttr('required');
                    receiptNameRow.removeClass('validate-required');
                    
                    // ラベルから「*」を削除
                    receiptNameRow.find('label .required').remove();
                }
            });
            
            // ページ読み込み時の初期状態設定
            $('input[name="receipt_required"]').trigger('change');
        });
        </script>
        <?php
    }
}

// お客様のマイアカウント注文詳細ページに領収書情報を表示
add_action('woocommerce_order_details_after_order_table', 'display_receipt_info_hpos_compatible');
function display_receipt_info_hpos_compatible($order) {
    if (!is_admin()) {
        // post_meta方式で取得（HPOS環境でも動作）
        $receipt_required = get_post_meta($order->get_id(), '_receipt_required', true);
        $receipt_name = get_post_meta($order->get_id(), '_receipt_name', true);
        
        if ($receipt_required) {
            echo '<section class="woocommerce-order-receipt-info" style="margin-top: 20px;">';
            echo '<h2 class="woocommerce-order-receipt-info__title">📄 領収書情報</h2>';
            echo '<table class="woocommerce-table woocommerce-table--order-receipt shop_table">';
            echo '<tbody>';
            echo '<tr>';
            echo '<th scope="row">領収書:</th>';
            echo '<td><span style="color: green;">✓ 必要</span></td>';
            echo '</tr>';
            if ($receipt_name) {
                echo '<tr>';
                echo '<th scope="row">宛名:</th>';
                echo '<td>' . esc_html($receipt_name) . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            echo '</section>';
        }
    }
}