<?php
function storefront_child_enqueue_styles() {
    // 親テーマのスタイル
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    
    // Google Fonts
    wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&family=Playfair+Display:wght@400;700&family=Hannari&display=swap', array(), null );
    
    // 子テーマのスタイル（最後に読み込み）
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
?>