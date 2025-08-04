<?php
/**
 * 自宅配送機能のカスタマイズ
 * 「自宅に送る」チェックボックスで送り主住所の入力を簡略化
 */

if (!defined('ABSPATH')) {
    exit;
}

// チェックアウトページに「自宅に送る」チェックボックスを追加
add_action('woocommerce_checkout_before_customer_details', 'add_home_delivery_checkbox');
function add_home_delivery_checkbox() {
    ?>
    <div class="home-delivery-option" style="margin-bottom: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 8px; border-left: 4px solid #2F4F2F;">
        <label style="display: flex; align-items: center; font-size: 16px; font-weight: 500; color: #2F4F2F; cursor: pointer;">
            <input type="checkbox" id="home_delivery_checkbox" name="home_delivery" value="1" checked style="margin-right: 10px; transform: scale(1.2);">
            <span>自宅に送る</span>
        </label>
        <p style="margin: 8px 0 0 32px; font-size: 14px; color: #666;">
            チェックを入れると、ご自宅へお送りさせていただきます。<br>
            ギフトとして他の方へお送りする場合は、チェックを外してください。
        </p>
    </div>

    <script>
    jQuery(document).ready(function($) {
        function toggleAddressFields() {
            var isHomeDelivery = $('#home_delivery_checkbox').is(':checked');
            var $billingFields = $('.woocommerce-billing-fields');
            var $shippingFields = $('.woocommerce-shipping-fields');
            var $shippingTitle = $('.woocommerce-shipping-fields h3');
            var $billingTitle = $('.woocommerce-billing-fields h3');
            
            if (isHomeDelivery) {
                // 自宅配送の場合、両方の入力欄を非表示
                $billingFields.slideUp(300);
                $shippingFields.slideUp(300);
                
                // タイトルを変更（非表示でも設定）
                $billingTitle.text('お送り主詳細（自動設定済み）');
                $shippingTitle.text('お届け先住所（自動設定済み）');
                
                
                // ログインユーザーの場合、住所情報を自動設定
                <?php if (is_user_logged_in()): 
                    $user_id = get_current_user_id();
                    $user_address = array(
                        'first_name' => get_user_meta($user_id, 'billing_first_name', true),
                        'last_name' => get_user_meta($user_id, 'billing_last_name', true),
                        'company' => get_user_meta($user_id, 'billing_company', true),
                        'address_1' => get_user_meta($user_id, 'billing_address_1', true),
                        'address_2' => get_user_meta($user_id, 'billing_address_2', true),
                        'city' => get_user_meta($user_id, 'billing_city', true),
                        'postcode' => get_user_meta($user_id, 'billing_postcode', true),
                        'state' => get_user_meta($user_id, 'billing_state', true),
                        'phone' => get_user_meta($user_id, 'billing_phone', true),
                    );
                ?>
                var userAddress = <?php echo json_encode($user_address); ?>;
                
                // 請求先と配送先の両方に自動入力
                if (userAddress) {
                    // 請求先（お送り主）
                    $('#billing_first_name').val(userAddress.first_name);
                    $('#billing_last_name').val(userAddress.last_name);
                    $('#billing_company').val(userAddress.company);
                    $('#billing_address_1').val(userAddress.address_1);
                    $('#billing_address_2').val(userAddress.address_2);
                    $('#billing_city').val(userAddress.city);
                    $('#billing_postcode').val(userAddress.postcode);
                    $('#billing_state').val(userAddress.state);
                    $('#billing_phone').val(userAddress.phone);
                    
                    // 配送先（お届け先）も同じ住所に
                    $('#shipping_first_name').val(userAddress.first_name);
                    $('#shipping_last_name').val(userAddress.last_name);
                    $('#shipping_company').val(userAddress.company);
                    $('#shipping_address_1').val(userAddress.address_1);
                    $('#shipping_address_2').val(userAddress.address_2);
                    $('#shipping_city').val(userAddress.city);
                    $('#shipping_postcode').val(userAddress.postcode);
                    $('#shipping_state').val(userAddress.state);
                }
                <?php endif; ?>
                
            } else {
                // ギフト配送の場合、両方の入力欄を表示
                $billingFields.slideDown(300);
                $shippingFields.slideDown(300);
                $billingTitle.text('お送り主詳細');
                $shippingTitle.text('お届け先住所');
                
                // ギフト配送でも電話番号は登録済み番号を表示
                <?php if (is_user_logged_in()): 
                    $user_id = get_current_user_id();
                    $user_phone = get_user_meta($user_id, 'billing_phone', true);
                    if ($user_phone): ?>
                var userPhone = '<?php echo esc_js($user_phone); ?>';
                $('#billing_phone').val(userPhone);
                    <?php endif; ?>
                <?php endif; ?>
            }
        }
        
        // 初回実行
        toggleAddressFields();
        
        // チェックボックスの変更時
        $('#home_delivery_checkbox').on('change', function() {
            toggleAddressFields();
        });
        
        // チェックアウト更新時
        $(document.body).on('updated_checkout', function() {
            toggleAddressFields();
        });
    });
    </script>

    <style>
    .home-delivery-option {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .home-delivery-option input[type="checkbox"]:checked {
        accent-color: #2F4F2F;
    }
    
    .woocommerce-billing-fields.hidden-for-home-delivery {
        opacity: 0.5;
        pointer-events: none;
    }
    
    .woocommerce-billing-fields h3 {
        transition: all 0.3s ease;
    }
    </style>
    <?php
}

// 注文処理時に自宅配送フラグを保存
add_action('woocommerce_checkout_update_order_meta', 'save_home_delivery_flag');
function save_home_delivery_flag($order_id) {
    if (!empty($_POST['home_delivery'])) {
        update_post_meta($order_id, '_home_delivery', 'yes');
        
        // 自宅配送の場合、請求先住所を配送先住所と同じに設定
        $order = wc_get_order($order_id);
        if ($order) {
            $shipping_address = array(
                'first_name' => $order->get_shipping_first_name(),
                'last_name' => $order->get_shipping_last_name(),
                'company' => $order->get_shipping_company(),
                'address_1' => $order->get_shipping_address_1(),
                'address_2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'state' => $order->get_shipping_state(),
                'postcode' => $order->get_shipping_postcode(),
                'country' => $order->get_shipping_country(),
            );
            
            // 請求先住所を配送先と同じに設定
            foreach ($shipping_address as $key => $value) {
                $order->{"set_billing_$key"}($value);
            }
            
            // 電話番号も同期（ログインユーザーの電話番号を使用）
            if (is_user_logged_in()) {
                $user_phone = get_user_meta(get_current_user_id(), 'billing_phone', true);
                if ($user_phone) {
                    $order->set_billing_phone($user_phone);
                }
            }
            
            $order->save();
        }
    }
}

// 管理画面の注文詳細に自宅配送フラグを表示
add_action('woocommerce_admin_order_data_after_billing_address', 'display_home_delivery_flag_admin');
function display_home_delivery_flag_admin($order) {
    $home_delivery = get_post_meta($order->get_id(), '_home_delivery', true);
    if ($home_delivery === 'yes') {
        echo '<p><strong>配送種別:</strong> 自宅配送</p>';
    } else {
        echo '<p><strong>配送種別:</strong> ギフト配送</p>';
    }
}

// 注文確認画面でも自宅配送情報を表示
add_action('woocommerce_order_details_after_order_table', 'display_home_delivery_on_thankyou');
function display_home_delivery_on_thankyou($order) {
    $home_delivery = get_post_meta($order->get_id(), '_home_delivery', true);
    if ($home_delivery === 'yes') {
        echo '<div class="woocommerce-order-overview__delivery-type order_delivery_type" style="margin: 15px 0; padding: 10px; background-color: #f8f9fa; border-radius: 5px;">
                <strong>配送種別:</strong> 自宅配送
              </div>';
    }
}

// メール通知でも配送種別を表示
add_action('woocommerce_email_order_meta', 'add_home_delivery_to_email', 10, 3);
function add_home_delivery_to_email($order, $sent_to_admin, $plain_text) {
    $home_delivery = get_post_meta($order->get_id(), '_home_delivery', true);
    $delivery_type = ($home_delivery === 'yes') ? '自宅配送' : 'ギフト配送';
    
    if ($plain_text) {
        echo "\n配送種別: " . $delivery_type . "\n";
    } else {
        echo '<div style="margin: 15px 0;"><strong>配送種別:</strong> ' . $delivery_type . '</div>';
    }
}

// 自宅配送時のフィールドバリデーション
add_action('woocommerce_checkout_process', 'validate_home_delivery_fields');
function validate_home_delivery_fields() {
    // 自宅配送がチェックされている場合
    if (!empty($_POST['home_delivery'])) {
        // ログインチェック
        if (!is_user_logged_in()) {
            wc_add_notice('自宅配送をご利用いただくには、ログインが必要です。', 'error');
            return;
        }
        
        // ユーザーの住所情報が登録されているかチェック
        $user_id = get_current_user_id();
        $required_fields = array(
            'billing_first_name' => 'お名前（名）',
            'billing_last_name' => 'お名前（姓）',
            'billing_address_1' => '住所',
            'billing_postcode' => '郵便番号',
            'billing_phone' => '電話番号'
        );
        
        foreach ($required_fields as $field => $label) {
            $value = get_user_meta($user_id, $field, true);
            if (empty($value)) {
                wc_add_notice("自宅配送をご利用いただくには、マイアカウントで{$label}を登録してください。", 'error');
            }
        }
    }
}

// 自宅配送時に請求先フィールドを自動でコピー
add_filter('woocommerce_checkout_posted_data', 'auto_fill_billing_from_shipping_for_home_delivery');
function auto_fill_billing_from_shipping_for_home_delivery($data) {
    // 自宅配送がチェックされている場合
    if (!empty($data['home_delivery'])) {
        // 配送先の情報を請求先にコピー
        $data['billing_first_name'] = $data['shipping_first_name'];
        $data['billing_last_name'] = $data['shipping_last_name'];
        $data['billing_company'] = $data['shipping_company'];
        $data['billing_address_1'] = $data['shipping_address_1'];
        $data['billing_address_2'] = $data['shipping_address_2'];
        $data['billing_city'] = $data['shipping_city'];
        $data['billing_state'] = $data['shipping_state'];
        $data['billing_postcode'] = $data['shipping_postcode'];
        $data['billing_country'] = $data['shipping_country'];
        
        // ログインユーザーの電話番号を使用
        if (is_user_logged_in()) {
            $user_phone = get_user_meta(get_current_user_id(), 'billing_phone', true);
            if ($user_phone) {
                $data['billing_phone'] = $user_phone;
            }
        }
    }
    
    return $data;
}