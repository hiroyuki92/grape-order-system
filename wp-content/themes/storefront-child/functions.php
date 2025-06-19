<?php
function storefront_child_enqueue_styles() {
    // 親テーマのスタイル
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    
    // Google Fonts
    wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&family=Playfair+Display:wght@400;700&family=Hannari&display=swap', array(), null );
    
    // 子テーマのスタイル
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style', 'google-fonts') );
}
add_action( 'wp_enqueue_scripts', 'storefront_child_enqueue_styles' );

function add_hero_banner_after_header() {
    if (is_front_page()) {
        echo '<div class="top-hero-banner">';
        echo '<img src="' . get_stylesheet_directory_uri() . '/images/hero-banner.png" alt="Sano Farm - Shine Muscat" class="hero-banner-image">';
        echo '</div>';
    }
}
add_action('storefront_before_content', 'add_hero_banner_after_header');

// 郵便番号自動住所入力機能
add_action('wp_enqueue_scripts', function() {
    if (is_checkout() || is_account_page()) {
        wp_enqueue_script('ajaxzip3', 'https://ajaxzip3.github.io/ajaxzip3.js', array('jquery'), null, true);
        
        wp_add_inline_script('ajaxzip3', '
            jQuery(document).ready(function($) {
                $(document).on("keyup", "input[id*=\"postcode\"], input[name*=\"postcode\"]", function() {
                    var postcode = $(this).val().replace(/[^0-9]/g, "");
                    
                    if (postcode.length === 7) {
                        $.ajax({
                            url: "https://zipcloud.ibsnet.co.jp/api/search",
                            type: "GET",
                            dataType: "jsonp",
                            data: { zipcode: postcode },
                            success: function(data) {
                                if (data.results && data.results.length > 0) {
                                    var result = data.results[0];
                                    var form = $(this).closest("form");
                                    
                                    // 都道府県（セレクトボックス）
                                    var stateField = form.find("select[name*=\"state\"]");
                                    if (stateField.length) {
                                        var matchedOption = stateField.find("option").filter(function() {
                                            return $(this).text().indexOf(result.address1) >= 0 || 
                                                   $(this).val().indexOf(result.address1) >= 0;
                                        });
                                        if (matchedOption.length > 0) {
                                            stateField.val(matchedOption.val()).trigger("change");
                                        }
                                    }
                                    
                                    // 市区町村
                                    var cityField = form.find("input[name*=\"city\"]");
                                    if (cityField.length) {
                                        cityField.val(result.address2).trigger("change");
                                    }
                                    
                                    // 住所
                                    var addressField = form.find("input[name*=\"address_1\"]");
                                    if (addressField.length) {
                                        addressField.val(result.address3).trigger("change");
                                    }
                                }
                            }.bind(this)
                        });
                    }
                });
            });
        ');
    }
});

// Japanized for WooCommerce のブロック版機能を無効化
add_action('wp_enqueue_scripts', function() {
    // Japanized関連のブロック版スクリプトを無効化
    wp_dequeue_script('jp4wc-cod-wc-blocks');
    wp_deregister_script('jp4wc-cod-wc-blocks');
    
    // WooCommerceブロック版も無効化
    wp_dequeue_script('wc-blocks-data');
    wp_dequeue_script('wc-blocks-checkout');
    wp_dequeue_script('wc-blocks-cart');
    
    wp_deregister_script('wc-blocks-data');
    wp_deregister_script('wc-blocks-checkout');
    wp_deregister_script('wc-blocks-cart');
}, 999);

// WooCommerceブロック版機能を完全無効化
add_filter('woocommerce_feature_enabled', function($enabled, $feature) {
    $block_features = [
        'checkout_blocks',
        'cart_checkout_blocks',
        'experimental_blocks'
    ];
    
    if (in_array($feature, $block_features)) {
        return false;
    }
    
    return $enabled;
}, 10, 2);

// Select2住所選択の日本式表示対応（選択後も名前のみ表示版）
add_action('wp_enqueue_scripts', function() {
    if (is_checkout()) {
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                
                function formatJapaneseAddressText(text, nameOnly = false) {
                    // 海外式住所パターンを検知
                    if (text.match(/.*,.*,.*,.*JP\d*/)) {
                        var parts = text.split(",").map(function(part) { 
                            return part.trim(); 
                        });
                        
                        if (parts.length >= 3) {
                            var name = parts[0]; // 花子 鈴木
                            var address1 = parts[1]; // １−１−１
                            var city = parts[2]; // 弘前市
                            var postcode = parts[3] || ""; // JP02
                            
                            // 名前を日本式に変換（花子 鈴木 → 鈴木 花子）
                            var nameParts = name.split(" ");
                            var japaneseName = "";
                            if (nameParts.length >= 2) {
                                japaneseName = nameParts[1] + " " + nameParts[0] + " 様";
                            }
                            
                            // 名前のみ表示の場合
                            if (nameOnly) {
                                return japaneseName;
                            }
                            
                            // 詳細情報をtitle属性用に作成
                            var postcodeNumber = postcode.replace(/[^0-9]/g, "");
                            var formattedPostcode = "";
                            if (postcodeNumber.length >= 6) {
                                if (postcodeNumber.length === 7) {
                                    formattedPostcode = "〒" + postcodeNumber.substring(0, 3) + "-" + postcodeNumber.substring(3);
                                } else if (postcodeNumber.length === 6) {
                                    formattedPostcode = "〒" + postcodeNumber.substring(0, 3) + "-" + postcodeNumber.substring(3);
                                } else {
                                    formattedPostcode = "〒" + postcodeNumber;
                                }
                            }
                            
                            // 詳細住所（title用）
                            return formattedPostcode + " " + city + address1 + " " + japaneseName;
                        }
                    }
                    return text;
                }
                
                function updateSelect2Display() {
                    // Select2の選択表示（名前のみ表示）
                    $(".select2-selection__rendered").each(function() {
                        var $this = $(this);
                        var originalText = $this.text();
                        var titleText = $this.attr("title");
                        
                        if (originalText && originalText.includes(",") && originalText.includes("JP")) {
                            var nameOnly = formatJapaneseAddressText(originalText, true); // 名前のみ
                            var fullAddress = formatJapaneseAddressText(originalText, false); // 詳細
                            
                            $this.text(nameOnly);
                            $this.attr("title", fullAddress); // ホバー時に詳細表示
                        }
                        
                        if (titleText && titleText.includes(",") && titleText.includes("JP")) {
                            var nameOnly = formatJapaneseAddressText(titleText, true); // 名前のみ
                            var fullAddress = formatJapaneseAddressText(titleText, false); // 詳細
                            
                            $this.text(nameOnly);
                            $this.attr("title", fullAddress); // ホバー時に詳細表示
                        }
                    });
                    
                    // Select2のドロップダウンオプション（名前のみ表示）
                    $(".select2-results__option").each(function() {
                        var $this = $(this);
                        var text = $this.text();
                        
                        if (text && text.includes(",") && text.includes("JP")) {
                            var nameOnlyText = formatJapaneseAddressText(text, true); // 名前のみ
                            var fullAddress = formatJapaneseAddressText(text, false); // 詳細
                            
                            $this.text(nameOnlyText);
                            $this.attr("title", fullAddress); // ホバー時に詳細表示
                        }
                    });
                }
                
                // 初回実行
                setTimeout(updateSelect2Display, 500);
                
                // Select2が開かれた時にも実行
                $(document).on("select2:open", function() {
                    setTimeout(updateSelect2Display, 100);
                });
                
                // Select2選択後にも実行
                $(document).on("select2:select", function() {
                    setTimeout(updateSelect2Display, 100);
                });
                
                // DOM変更を監視
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes.length > 0) {
                            setTimeout(updateSelect2Display, 100);
                        }
                    });
                });
                
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
        ');
    }
});

// 支払いページの住所のオプション表記を非表示
add_action('wp_head', function() {
    if (is_checkout()) {
        echo '<style>
        .optional { display: none !important; }
        </style>';
    }
});

// 子テーマのfunctions.phpに追加
add_filter('gettext', 'change_billing_text_to_sender', 20, 3);
function change_billing_text_to_sender($translated_text, $text, $domain) {
    if ($domain == 'woocommerce') {
        switch ($text) {
            case 'Billing address':
            case '請求先住所':
                return 'お送り主住所';
            case 'Billing details':
            case '請求先詳細':
                return 'お送り主詳細';
            case 'Billing information':
            case '請求先情報':
                return 'お送り主情報';
        }
    }
    return $translated_text;
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

// 2. データベース保存
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

// ===========================================
// 管理画面に会員登録ページを追加
// ===========================================

add_action('admin_menu', 'add_phone_member_registration_menu');
function add_phone_member_registration_menu() {
    add_menu_page(
        '電話番号会員登録',
        '会員登録',
        'manage_options',
        'phone-member-registration',
        'phone_member_registration_page',
        'dashicons-phone',
        26
    );
}

// ===========================================
// 会員登録ページの表示
// ===========================================

function phone_member_registration_page() {
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
            $result = register_phone_member($phone, $name);
            
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
        <h1>📱 電話番号会員登録</h1>
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
// 実際の会員登録処理
// ===========================================

function register_phone_member($phone, $name) {
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
// 確認用：登録された会員の簡易一覧表示
// ===========================================

add_action('admin_notices', 'show_phone_members_count');
function show_phone_members_count() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_phone-member-registration') {
        $phone_users = get_users(array(
            'meta_key' => 'is_phone_user',
            'meta_value' => true
        ));
        
        $count = count($phone_users);
        
        if ($count > 0) {
            echo '<div class="notice notice-info">
                <p>📊 現在 <strong>' . $count . '名</strong> の電話番号会員が登録されています。</p>
            </div>';
        }
    }
}
?>