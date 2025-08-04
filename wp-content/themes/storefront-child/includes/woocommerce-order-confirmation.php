<?php
/**
 * WooCommerce 注文確認モーダル機能
 * 
 * 保存場所: wp-content/themes/storefront-child/includes/woocommerce-order-confirmation.php
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// ===========================================
// 注文確認モーダルの実装
// ===========================================

/**
 * チェックアウトページに確認モーダルを追加
 */
function add_order_confirmation_modal() {
    if (is_checkout() && !is_wc_endpoint_url()) {
        ?>
        <!-- 注文確認モーダル -->
        <div id="order-confirmation-modal" class="order-confirmation-modal" style="display: none;">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3>ご注文内容の確認</h3>
                    <button type="button" class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <p><strong>こちらの内容でご注文を確定してもよろしいですか？</strong></p>
                    
                    <!-- 注文内容のサマリー -->
                    <div class="order-summary">
                        <h4>ご注文商品</h4>
                        <div id="modal-order-items"></div>
                        
                        <h4>お送り主</h4>
                        <div id="modal-billing-address"></div>
                        
                        <h4>お届け先</h4>
                        <div id="modal-shipping-address"></div>
                        
                        <h4>配達希望日時</h4>
                        <div id="modal-delivery-date"></div>
                        
                        <h4>お支払い方法</h4>
                        <div id="modal-payment-method"></div>
                        
                        <h4>領収書</h4>
                        <div id="modal-receipt-info"></div>
                        
                        <h4>ご注文金額</h4>
                        <div id="modal-order-total"></div>
                    </div>
                    
                    <div class="modal-notice">
                        <p><small>※ 確定後の内容変更はできませんので、よくご確認ください。</small></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button secondary modal-cancel">内容を変更する</button>
                    <button type="button" class="button primary modal-confirm">この内容で注文を確定する</button>
                </div>
            </div>
        </div>

        <style>
        /* モーダルのスタイル */
        .order-confirmation-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
        }

        .modal-content {
            position: relative;
            background: white;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            font-family: 'Hannari', serif;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
        }

        .modal-header h3 {
            margin: 0;
            color: #2F4F2F;
            font-size: 1.3em;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            color: #333;
        }

        .modal-body {
            padding: 20px;
        }

        .order-summary {
            margin: 20px 0;
        }

        .order-summary h4 {
            color: #2F4F2F;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin: 15px 0 10px 0;
            font-size: 1.1em;
        }

        .order-summary div {
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .modal-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
        }

        .modal-notice p {
            margin: 0;
            color: #856404;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            padding: 20px;
            border-top: 1px solid #eee;
            background: #f8f9fa;
            border-radius: 0 0 8px 8px;
            justify-content: flex-end;
        }

        .modal-footer .button {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .modal-footer .button.secondary {
            background: #6c757d;
            color: white;
        }

        .modal-footer .button.secondary:hover {
            background: #5a6268;
        }

        .modal-footer .button.primary {
            background: #28a745;
            color: white;
        }

        .modal-footer .button.primary:hover {
            background: #218838;
        }

        /* レスポンシブ対応 */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 10px;
            }

            .modal-footer {
                flex-direction: column;
            }

            .modal-footer .button {
                width: 100%;
                margin-bottom: 10px;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var isConfirmed = false;
            var originalForm = null;
            
            // WooCommerce風エラーメッセージ表示関数（複数対応）
            function showErrorMessages(messages) {
                // 既存のエラーメッセージを削除
                $('.woocommerce-error, .woocommerce-message').remove();
                
                // 配列でない場合は配列に変換
                if (!Array.isArray(messages)) {
                    messages = [messages];
                }
                
                if (messages.length === 0) {
                    return;
                }
                
                // 複数のエラーメッセージを作成
                var errorHtml = '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' +
                    '<div class="woocommerce-error" role="alert">';
                
                if (messages.length === 1) {
                    errorHtml += '<strong>エラー:</strong> ' + messages[0];
                } else {
                    errorHtml += '<strong>以下のエラーを修正してください:</strong><ul>';
                    for (var i = 0; i < messages.length; i++) {
                        errorHtml += '<li>' + messages[i] + '</li>';
                    }
                    errorHtml += '</ul>';
                }
                
                errorHtml += '</div></div>';
                
                // フォームの上に挿入
                $('form.checkout').prepend(errorHtml);
                
                // ページトップにスクロール
                $('html, body').animate({
                    scrollTop: $('form.checkout').offset().top - 100
                }, 500);
            }
            
            // 単一エラー用のヘルパー関数（後方互換性）
            function showErrorMessage(message) {
                showErrorMessages([message]);
            }

            // 「注文する」ボタンをクリックした時の処理
            $('body').on('click', '#place_order', function(e) {
                // 既に確認済みの場合は通常の処理を実行
                if (isConfirmed) {
                    return true;
                }

                // 全てのバリデーションエラーを収集
                var errors = [];
                var firstErrorField = null;
                
                // 必須フィールドをチェック
                $('form.checkout .validate-required input, form.checkout .validate-required select, form.checkout .validate-required textarea').each(function() {
                    var $field = $(this);
                    var $wrapper = $field.closest('.form-row');
                    
                    // 非表示フィールドはスキップ
                    if ($wrapper.is(':visible') && $field.is(':visible')) {
                        if (!$field.val() || $field.val().trim() === '') {
                            var label = $wrapper.find('label').text().replace('*', '').trim();
                            if (label) {
                                errors.push(label + 'は必須項目です。');
                                if (!firstErrorField) {
                                    firstErrorField = $field;
                                }
                            }
                        }
                    }
                });
                
                // カスタムバリデーションも実行
                var customErrors = getCustomValidationErrors();
                errors = errors.concat(customErrors);
                
                // 重複するエラーメッセージを除去
                errors = errors.filter(function(item, index) {
                    return errors.indexOf(item) === index;
                });
                
                // エラーがある場合は表示
                if (errors.length > 0) {
                    showErrorMessages(errors);
                    if (firstErrorField) {
                        firstErrorField.focus();
                    }
                    e.preventDefault();
                    return false;
                }
                
                // HTML5バリデーションもチェック
                var form = $('form.checkout')[0];
                if (!form.checkValidity()) {
                    return true; // ブラウザの標準バリデーションに任せる
                }

                // 確認モーダルを表示
                e.preventDefault();
                showConfirmationModal();
                return false;
            });
            
            // カスタムバリデーションエラーを収集する関数
            function getCustomValidationErrors() {
                var errors = [];
                
                // 自宅配送のログインチェック
                var isHomeDelivery = $('#home_delivery_checkbox').is(':checked');
                if (isHomeDelivery && !$('body').hasClass('logged-in')) {
                    errors.push('自宅配送をご利用いただくには、ログインが必要です。');
                }
                
                // 領収書バリデーション
                var receiptRequired = $('input[name="receipt_required"]').is(':checked');
                var receiptName = $('input[name="receipt_name"]').val();
                
                if (receiptRequired && (!receiptName || receiptName.trim() === '')) {
                    errors.push('領収書が必要な場合は宛名の入力が必要です。');
                }
                
                return errors;
            }

            // 確認モーダルを表示する関数
            function showConfirmationModal() {
                // 注文内容を取得してモーダルに表示
                updateModalContent();
                $('#order-confirmation-modal').fadeIn(300);
                $('body').addClass('modal-open');
            }

            // モーダルの内容を更新
            function updateModalContent() {
                // 商品一覧
                var orderItems = '';
                $('.woocommerce-checkout-review-order-table .cart_item').each(function() {
                    var name = $(this).find('.product-name').text();
                    var total = $(this).find('.product-total').text();
                    orderItems += '<div><strong>' + name + '</strong> - ' + total + '</div>';
                });
                $('#modal-order-items').html(orderItems);

                // お送り主（請求先住所）
                var billingAddress = getAddressFromForm('billing');
                $('#modal-billing-address').html(billingAddress);

                // 配送先住所
                var shippingAddress = '';
                if ($('#ship-to-different-address-checkbox').is(':checked')) {
                    shippingAddress = getAddressFromForm('shipping');
                } else {
                    shippingAddress = '<div style="color: #666; font-style: italic;">お送り主と同じ</div>';
                }
                $('#modal-shipping-address').html(shippingAddress);

                // 配達希望日時
                var deliveryInfo = getDeliveryDateTime();
                $('#modal-delivery-date').html(deliveryInfo);

                // 支払い方法
                var paymentMethod = $('input[name="payment_method"]:checked').next('label').text();
                $('#modal-payment-method').html(paymentMethod);

                // 領収書情報
                var receiptInfo = getReceiptInfo();
                $('#modal-receipt-info').html(receiptInfo);

                // 合計金額
                var orderTotal = $('.order-total .woocommerce-Price-amount').text();
                $('#modal-order-total').html('<strong>' + orderTotal + '</strong>');
            }

            // 配達希望日時を取得する関数
            function getDeliveryDateTime() {
                var deliveryInfo = '';
                
                // WooCommerce for Japan プラグイン対応
                var wc4jpDate = $('select[name="wc4jp_delivery_date"]').val();
                var wc4jpDateText = $('select[name="wc4jp_delivery_date"]').find('option:selected').text();
                var wc4jpTime = $('select[name="wc4jp_delivery_time_zone"]').val();
                var wc4jpTimeText = $('select[name="wc4jp_delivery_time_zone"]').find('option:selected').text();
                
                console.log('WC4JP 配達日値:', wc4jpDate);
                console.log('WC4JP 配達日テキスト:', wc4jpDateText);
                console.log('WC4JP 配達時間値:', wc4jpTime);
                console.log('WC4JP 配達時間テキスト:', wc4jpTimeText);
                
                var deliveryDate = '';
                var deliveryTime = '';
                
                // WC4JP の配達日処理
                if (wc4jpDate && wc4jpDate !== '0') {
                    deliveryDate = wc4jpDateText; // 既に日本語形式になっている
                }
                
                // WC4JP の配達時間処理
                if (wc4jpTime && wc4jpTime !== '0') {
                    deliveryTime = wc4jpTimeText;
                }
                
                // 他のプラグイン対応（フォールバック）
                if (!deliveryDate || !deliveryTime) {
                    // 一般的な配達日時フィールド名をチェック
                    var dateFields = [
                        'input[name="delivery_date"]',
                        'input[name="shipping_date"]', 
                        'select[name="delivery_date"]',
                        'select[name="shipping_date"]',
                        'input[name="_delivery_date"]',
                        'input[name="_shipping_date"]',
                        'input[name="order_delivery_date"]',
                        'input[name="wc_delivery_date"]',
                        'input[name="delivery-date"]',
                        'input[name="shipping-date"]',
                        '#delivery_date',
                        '#shipping_date',
                        '#delivery-date',
                        '#order_delivery_date',
                        'input[type="date"]'
                    ];
                    
                    var timeFields = [
                        'select[name="delivery_time"]',
                        'select[name="shipping_time"]',
                        'input[name="delivery_time"]',
                        'input[name="shipping_time"]',
                        'select[name="_delivery_time"]',
                        'select[name="_shipping_time"]',
                        'select[name="order_delivery_time"]',
                        'select[name="wc_delivery_time"]',
                        'select[name="delivery-time"]',
                        'select[name="shipping-time"]',
                        '#delivery_time',
                        '#shipping_time',
                        '#delivery-time',
                        '#order_delivery_time'
                    ];

                    // 配達日を検索（WC4JPで見つからなかった場合）
                    if (!deliveryDate) {
                        for (var i = 0; i < dateFields.length; i++) {
                            var dateElement = $(dateFields[i]);
                            if (dateElement.length && dateElement.val()) {
                                if (dateElement.is('select')) {
                                    deliveryDate = dateElement.find('option:selected').text();
                                } else {
                                    deliveryDate = formatJapaneseDate(dateElement.val());
                                }
                                console.log('フォールバック配達日発見:', dateFields[i], '値:', deliveryDate);
                                break;
                            }
                        }
                    }

                    // 配達時間を検索（WC4JPで見つからなかった場合）
                    if (!deliveryTime) {
                        for (var i = 0; i < timeFields.length; i++) {
                            var timeElement = $(timeFields[i]);
                            if (timeElement.length && timeElement.val()) {
                                if (timeElement.is('select')) {
                                    deliveryTime = timeElement.find('option:selected').text();
                                } else {
                                    deliveryTime = timeElement.val();
                                }
                                console.log('フォールバック配達時間発見:', timeFields[i], '値:', deliveryTime);
                                break;
                            }
                        }
                    }

                    // WooCommerce Order Delivery プラグイン対応
                    if (!deliveryDate) {
                        var wcDeliveryDate = $('input[name="_wcod_delivery_date"]').val();
                        if (wcDeliveryDate) {
                            deliveryDate = formatJapaneseDate(wcDeliveryDate);
                            console.log('WC Order Delivery 日付:', wcDeliveryDate);
                        }
                    }
                    
                    if (!deliveryTime) {
                        var wcDeliveryTime = $('select[name="_wcod_delivery_time_frame"]').find('option:selected').text();
                        if (wcDeliveryTime && wcDeliveryTime !== '') {
                            deliveryTime = wcDeliveryTime;
                            console.log('WC Order Delivery 時間:', wcDeliveryTime);
                        }
                    }
                }

                // 日時情報をフォーマット
                console.log('最終的な配達日:', deliveryDate);
                console.log('最終的な配達時間:', deliveryTime);
                
                if (deliveryDate || deliveryTime) {
                    if (deliveryDate) {
                        deliveryInfo += '<strong>配達希望日:</strong> ' + deliveryDate;
                    }
                    
                    if (deliveryTime) {
                        if (deliveryDate) deliveryInfo += '<br>';
                        deliveryInfo += '<strong>配達希望時間:</strong> ' + deliveryTime;
                    }
                } else {
                    deliveryInfo = '<div style="color: #666; font-style: italic;">指定なし</div>';
                }

                return deliveryInfo;
            }

            // 日付を日本語形式にフォーマットする関数
            function formatJapaneseDate(dateString) {
                if (!dateString) return '';
                
                // YYYY-MM-DD形式の場合
                if (dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
                    var parts = dateString.split('-');
                    return parts[0] + '年' + parseInt(parts[1]) + '月' + parseInt(parts[2]) + '日';
                }
                
                // YYYY/MM/DD形式の場合
                if (dateString.match(/^\d{4}\/\d{1,2}\/\d{1,2}$/)) {
                    var parts = dateString.split('/');
                    return parts[0] + '年' + parseInt(parts[1]) + '月' + parseInt(parts[2]) + '日';
                }
                
                // MM/DD/YYYY形式の場合
                if (dateString.match(/^\d{1,2}\/\d{1,2}\/\d{4}$/)) {
                    var parts = dateString.split('/');
                    return parts[2] + '年' + parseInt(parts[0]) + '月' + parseInt(parts[1]) + '日';
                }
                
                // その他の場合はそのまま返す
                return dateString;
            }

            // 領収書情報を取得する関数
            function getReceiptInfo() {
                var receiptRequired = $('input[name="receipt_required"]').is(':checked');
                var receiptName = $('input[name="receipt_name"]').val();
                
                if (receiptRequired) {
                    var receiptInfo = '<strong style="color: green;">✓ 領収書が必要</strong>';
                    if (receiptName && receiptName.trim() !== '') {
                        receiptInfo += '<br><strong>宛名:</strong> ' + receiptName;
                    }
                    return receiptInfo;
                } else {
                    return '<span style="color: #666; font-style: italic;">不要</span>';
                }
            }
            function getAddressFromForm(type) {
                var prefix = type + '_';
                var address = '';
                
                var lastName = $('input[name="' + prefix + 'last_name"]').val() || '';
                var firstName = $('input[name="' + prefix + 'first_name"]').val() || '';
                var postcode = $('input[name="' + prefix + 'postcode"]').val() || '';
                var state = $('select[name="' + prefix + 'state"]').find('option:selected').text() || $('input[name="' + prefix + 'state"]').val() || '';
                var city = $('input[name="' + prefix + 'city"]').val() || '';
                var address1 = $('input[name="' + prefix + 'address_1"]').val() || '';
                var address2 = $('input[name="' + prefix + 'address_2"]').val() || '';

                address = lastName + ' ' + firstName + '<br>';
                address += '〒' + postcode + '<br>';
                address += state + city + '<br>';
                address += address1;
                if (address2) {
                    address += '<br>' + address2;
                }

                return address;
            }

            // 「この内容で注文を確定する」ボタンをクリック
            $('.modal-confirm').on('click', function() {
                isConfirmed = true;
                $('#order-confirmation-modal').fadeOut(300);
                $('body').removeClass('modal-open');
                
                // 実際の注文処理を実行
                $('#place_order').trigger('click');
            });

            // 「内容を変更する」ボタンまたは閉じるボタンをクリック
            $('.modal-cancel, .modal-close, .modal-overlay').on('click', function() {
                $('#order-confirmation-modal').fadeOut(300);
                $('body').removeClass('modal-open');
            });

            // ESCキーでモーダルを閉じる
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && $('#order-confirmation-modal').is(':visible')) {
                    $('#order-confirmation-modal').fadeOut(300);
                    $('body').removeClass('modal-open');
                }
            });
        });
        </script>

        <style>
        /* モーダル表示時のボディスクロール防止 */
        body.modal-open {
            overflow: hidden;
        }
        </style>
        <?php
    }
}
add_action('wp_footer', 'add_order_confirmation_modal');


?>