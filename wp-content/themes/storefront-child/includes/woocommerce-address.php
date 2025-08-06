<?php
/**
 * 住所表示のカスタマイズ
 */

if (!defined('ABSPATH')) {
    exit;
}

// 住所選択を日本語名のみ表示（姓名順）
add_action('wp_footer', 'add_simple_address_display_script');
function add_simple_address_display_script() {
    if (is_checkout()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            function simplifyAddressDisplay() {
                // select要素のoptionを調整
                $('select[name*="address"] option').each(function() {
                    var text = $(this).text();
                    // カンマで区切って最初の部分（名前）のみ表示
                    if (text.includes(',')) {
                        var name = text.split(',')[0].trim();
                        // "様"を追加
                        if (name && name !== '住所を選択' && name !== 'Select an address') {
                            // 名姓を姓名に変換（例: "花子 鈴木" → "鈴木 花子"）
                            var nameParts = name.split(' ');
                            if (nameParts.length >= 2) {
                                // 最後の部分を姓、それ以外を名として処理
                                var lastName = nameParts[nameParts.length - 1];  // 姓
                                var firstName = nameParts.slice(0, -1).join(' '); // 名
                                name = lastName + ' ' + firstName;
                            }
                            $(this).text(name + ' 様');
                        }
                    }
                });
            }
            
            // 初回実行
            simplifyAddressDisplay();
            
            // チェックアウト更新時
            $(document.body).on('updated_checkout', function() {
                setTimeout(simplifyAddressDisplay, 50);  // 短い遅延
            });
        });
        </script>
        <?php
    }
}

// 支払いページの住所のオプション表記を非表示
add_action('wp_head', function() {
    if (is_checkout()) {
        echo '<style>
        .optional { display: none !important; }
        </style>';
    }
});

