<?php
/**
 * 住所表示のカスタマイズ
 */

if (!defined('ABSPATH')) {
    exit;
}

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