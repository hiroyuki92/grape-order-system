
<?php
/**
 * その他のWooCommerceカスタマイズ
 */

if (!defined('ABSPATH')) {
    exit;
}

// 請求先を「お送り主」に変更
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

// 請求先（送り主）のメールアドレスフィールドを非表示
add_filter('woocommerce_checkout_fields', 'hide_billing_email_field');
function hide_billing_email_field($fields) {
    unset($fields['billing']['billing_email']);
    return $fields;
}

// マイアカウントの住所ページでもメールアドレスフィールドを非表示
add_filter('woocommerce_billing_fields', 'hide_billing_email_field_myaccount');
add_filter('woocommerce_shipping_fields', 'hide_shipping_email_field_myaccount');
function hide_billing_email_field_myaccount($fields) {
    unset($fields['billing_email']);
    return $fields;
}
function hide_shipping_email_field_myaccount($fields) {
    unset($fields['shipping_email']);
    return $fields;
}

// 注文完了画面（thankyou）でメールアドレス表示を非表示
add_action('wp_head', 'hide_thankyou_email_css');
function hide_thankyou_email_css() {
    if (is_wc_endpoint_url('order-received')) {
        ?>
        <style>
        .woocommerce-order-overview__email {
            display: none !important;
        }
        </style>
        <?php
    }
}

// ショップページのタイトルスタイルを強制適用
add_action('wp_head', 'force_shop_title_style');
function force_shop_title_style() {
    if (is_shop() || is_product_category() || is_product_tag()) {
        ?>
        <style>
        .woocommerce-products-header h1,
        .woocommerce-products-header__title,
        .page-title {
            font-family: 'Hannari', serif !important;
            font-size: 32px !important;
            font-weight: normal !important;
            color: #2F4F2F !important;
            margin-bottom: 30px !important;
        }
        
        @media (max-width: 768px) {
            .woocommerce-products-header h1,
            .woocommerce-products-header__title,
            .page-title {
                font-size: 24px !important;
            }
        }
        </style>
        <?php
    }
}

// 住所ニックネームフィールドを非表示
add_filter('woocommerce_billing_fields', 'hide_address_nickname_fields', 20);
add_filter('woocommerce_shipping_fields', 'hide_address_nickname_fields', 20);
function hide_address_nickname_fields($fields) {
    // 住所ニックネームフィールドを削除
    unset($fields['billing_address_nickname']);
    unset($fields['shipping_address_nickname']);
    return $fields;
}

// 商品画像のリンクを削除
add_action('init', 'remove_product_image_links');
function remove_product_image_links() {
    // ショップページ（商品一覧）の商品画像リンクを削除
    remove_action('woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10);
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);
    
    // 関連商品の商品画像リンクも削除
    remove_action('woocommerce_before_single_product_summary', 'woocommerce_template_loop_product_link_open', 10);
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_template_loop_product_link_close', 5);
}

// マイアカウントページの説明文を変更
add_filter('gettext', 'change_my_account_description', 20, 3);
function change_my_account_description($translated_text, $text, $domain) {
    if ($domain == 'woocommerce') {
        if (strpos($text, 'From your account dashboard you can view your') !== false) {
            return 'マイアカウントでは、これまでのご注文を確認したり、住所やパスワードを変更したりできます。';
        }
        if (strpos($translated_text, 'アカウントダッシュボードでは、') !== false) {
            return 'マイアカウントでは、これまでのご注文を確認したり、住所やパスワードを変更したりできます。';
        }
    }
    return $translated_text;
}

// マイアカウントページのダウンロードタブを非表示
add_filter('woocommerce_account_menu_items', 'hide_downloads_tab');
function hide_downloads_tab($items) {
    unset($items['downloads']);
    return $items;
}

// 請求先（お送り主）の電話番号を必須にする
add_filter('woocommerce_billing_fields', 'make_billing_phone_required');
function make_billing_phone_required($fields) {
    $fields['billing_phone']['required'] = true;
    return $fields;
}

// チェックアウトページでも電話番号を必須にする
add_filter('woocommerce_checkout_fields', 'make_checkout_phone_required');
function make_checkout_phone_required($fields) {
    $fields['billing']['billing_phone']['required'] = true;
    return $fields;
}

// 「別の住所へ配送」チェックボックスに説明テキストを追加
add_action('wp_footer', 'add_shipping_checkbox_description');
function add_shipping_checkbox_description() {
    if (!is_checkout()) {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        function addShippingDescription() {
            // 既存の説明文を削除
            $('.shipping-checkbox-description').remove();
            
            // チェックボックスを探す
            var $checkbox = $('#ship-to-different-address-checkbox');
            if ($checkbox.length > 0) {
                // チェックボックスの親要素（labelまたはp要素）を取得
                var $checkboxContainer = $checkbox.closest('label, p, .form-row');
                
                // 説明文のHTMLを作成
                var descriptionHtml = '<div class="shipping-checkbox-description" style="margin-top: 8px; margin-bottom: 15px; padding-left: 0;">' +
                    '<p style="margin: 0; font-size: 14px; color: #666; font-style: italic;">' +
                    'ご自宅へ配送をご希望の場合はチェックを外してください' +
                    '</p>' +
                    '</div>';
                
                // チェックボックスコンテナの直後に挿入
                $checkboxContainer.after(descriptionHtml);
                
                // 初期表示状態を設定
                updateShippingDescription();
            }
        }
        
        // チェックボックスの状態に応じて説明文の表示を調整
        function updateShippingDescription() {
            var isChecked = $('#ship-to-different-address-checkbox').is(':checked');
            if (isChecked) {
                $('.shipping-checkbox-description').show();
            } else {
                $('.shipping-checkbox-description').hide();
            }
        }
        
        // 初回実行
        addShippingDescription();
        
        // チェックボックスの変更時
        $(document).on('change', '#ship-to-different-address-checkbox', updateShippingDescription);
        
        // チェックアウト更新時（説明文を再追加）
        $(document.body).on('updated_checkout', function() {
            setTimeout(addShippingDescription, 100);
        });
    });
    </script>
    <?php
}

// 注文一覧の「操作」を「注文詳細」、「ステータス」を「注文状況」に変更
add_filter('gettext', 'change_order_table_headers', 20, 3);
function change_order_table_headers($translated_text, $text, $domain) {
    if ($domain == 'woocommerce') {
        if ($text == 'Actions' || $translated_text == '操作') {
            return '注文詳細';
        }
        if ($text == 'Status' || $translated_text == 'ステータス') {
            return '注文状況';
        }
    }
    return $translated_text;
}

// 検索アイコンを商品一覧用に変更
add_action('wp_head', 'change_search_icon_to_shop_link');
function change_search_icon_to_shop_link() {
    ?>
    <style>
    /* 検索メニューを表示させる */
    li.search,
    li.search.active {
        display: block !important;
    }
    
    /* 元の検索アイコンを表示（検索機能は無効のまま） */
    li.search,
    li.search.active {
        display: block !important;
    }
    
    /* 検索フォームのみ無効化（アイコンは残す） */
    li.search .site-search,
    li.search.active .site-search,
    .site-search form,
    .site-search .widget {
        display: none !important;
    }
    
    /* スマホでの名前フィールド（姓・名）を横並びにする */
    @media (min-width: 387px) and (max-width: 768px) {
        .woocommerce-MyAccount-content .woocommerce-form-row--first,
        .woocommerce-MyAccount-content .woocommerce-form-row--last {
            width: 48% !important;
            display: inline-block !important;
            vertical-align: top !important;
        }
        
        .woocommerce-MyAccount-content .woocommerce-form-row--first {
            margin-right: 4% !important;
        }
        
        .woocommerce-MyAccount-content .woocommerce-form-row--last {
            margin-left: 0 !important;
        }
        
        /* フィールド間のスペース調整 */
        .woocommerce-MyAccount-content .form-row {
            margin-bottom: 20px !important;
        }
    }
    
    /* 386px以下の極小画面では縦並びに戻す */
    @media (max-width: 386px) {
        .woocommerce-MyAccount-content .woocommerce-form-row--first,
        .woocommerce-MyAccount-content .woocommerce-form-row--last {
            width: 100% !important;
            display: block !important;
            margin-right: 0 !important;
            margin-left: 0 !important;
        }
    }
    </style>
    <?php
}


// MDI（Material Design Icons）を読み込み
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'mdi',
        'https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css',
        [],
        '7.4.47'
    );
    wp_enqueue_script('jquery');
});

// フッターバーの検索アイコンを“ぶどう”に置き換え
add_action('wp_head', function(){ ?>
<style>
/* 元の虫眼鏡 (::before) を消す */
.storefront-handheld-footer-bar ul li.search > a::before { content: none !important; }

/* アイコン＋ラベルを中央寄せ */
.storefront-handheld-footer-bar ul li.search a{
  text-indent: 0 !important;
  display: flex !important;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  color:#333;
}

/* 初期表示時の「検索」テキストのみを非表示にする */
.storefront-handheld-footer-bar ul li.search > a:not([data-grapes-initialized]) {
  color: transparent !important;
}

/* ブドウアイコン初期化後は色を戻す */
.storefront-handheld-footer-bar ul li.search > a[data-grapes-initialized] {
  color: #333 !important;
}

/* アイコンサイズ（ここを変更すると大きさ調整できる） */
.storefront-handheld-footer-bar ul li.search a .mdi{
  font-family: "Material Design Icons" !important;
  font-size: 30px;
  line-height: 1;
  display: inline-block !important;
}

/* ラベル */
.storefront-handheld-footer-bar ul li.search a .label{
  font-size: 12px;
  line-height: 1;
}

/* アイコンの"中身"を直指定（強制マッピング） */
.mdi.mdi-fruit-grapes::before{ content: "\F1044" !important; }
.mdi.mdi-fruit-grapes-outline::before{ content: "\F1045" !important; }

/* 検索フォームは非表示 */
.storefront-handheld-footer-bar li.search .site-search,
.storefront-handheld-footer-bar li.search.active .site-search,
.storefront-handheld-footer-bar .site-search form,
.storefront-handheld-footer-bar .site-search .widget {
  display: none !important;
}
</style>
<?php });

// クリックで商品一覧へ遷移
add_action('wp_footer', function () { ?>
<script>
jQuery(function($){
  var selector = 'li.search a, li.search.active a';

  // 中身を置き換え（ぶどう + ラベル）
  $(selector).each(function(){
    var $a = $(this);
    if ($a.data('grapes-initialized')) return;
    $a.data('grapes-initialized', true);
    $a.attr('data-grapes-initialized', 'true'); // HTML属性も設定
    $a.html('<i class="mdi mdi-fruit-grapes" aria-hidden="true"></i>');
  });

  // クリックでショップへ
  $(document).on('click', selector, function(e){
    e.preventDefault();
    e.stopPropagation();
    window.location.href = '<?php echo esc_js( esc_url_raw( wc_get_page_permalink( 'shop' ) ) ); ?>';
    return false;
  });
});
</script>
<?php });