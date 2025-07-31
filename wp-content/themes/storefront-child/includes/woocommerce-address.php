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
                            
                            // フルネームのみを抽出（住所の種類や呼び方は除去）
                            var nameParts = name.split(" ");
                            var japaneseName = "";
                            
                            // 住所の種類を除去するための配列
                            var addressTypes = ["実家", "自宅", "会社", "職場", "事務所"];
                            
                            // 名前部分のみを抽出（住所の種類を除外）
                            var cleanNameParts = nameParts.filter(function(part) {
                                return !addressTypes.some(function(type) {
                                    return part.includes(type);
                                });
                            });
                            
                            if (cleanNameParts.length >= 2) {
                                // 名前を日本式に変換（花子 鈴木 → 鈴木 花子）
                                japaneseName = cleanNameParts[1] + " " + cleanNameParts[0] + " 様";
                            } else if (cleanNameParts.length === 1) {
                                japaneseName = cleanNameParts[0] + " 様";
                            } else {
                                // すべて住所の種類だった場合は、最初の部分を使用（住所の種類は除去）
                                japaneseName = "お客様";
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
                
                // CSS で Select2 を初期非表示（ドロップダウンオプションも含む）
                if (!$("#hide-select2-initial-css").length) {
                    $("head").append(\'<style id="hide-select2-initial-css">.select2-container { opacity: 0; transition: opacity 0.3s ease; } .select2-results__options { opacity: 0; transition: opacity 0.2s ease; } .select2-results__option { opacity: 0; transition: opacity 0.2s ease; }</style>\');
                }
                
                // select要素のoption自体を名前に変更（根本的解決）
                function updateSelectOptions() {
                    $("select option").each(function() {
                        var $option = $(this);
                        var originalText = $option.text();
                        
                        if (originalText && originalText.includes(",") && originalText.includes("JP")) {
                            var nameOnly = formatJapaneseAddressText(originalText, true);
                            if (nameOnly) {
                                $option.text(nameOnly);
                                $option.attr("title", formatJapaneseAddressText(originalText, false));
                            }
                        }
                    });
                }
                
                function updateSelect2Display() {
                    // まずoption要素自体を更新
                    updateSelectOptions();
                    
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
                    
                    // フォーマット処理完了後にSelect2を表示（ドロップダウンオプションも含む）
                    $(".select2-container").css("opacity", "1");
                    $(".select2-results__options").css("opacity", "1");
                    $(".select2-results__option").css("opacity", "1");
                }
                
                // 初回実行（まずoption要素から変更）
                updateSelectOptions(); // option要素を即座に変更
                setTimeout(updateSelectOptions, 100);
                setTimeout(updateSelectOptions, 300);
                
                updateSelect2Display(); // 即座に実行
                setTimeout(updateSelect2Display, 100);
                setTimeout(updateSelect2Display, 300);
                setTimeout(updateSelect2Display, 500);
                setTimeout(updateSelect2Display, 800);
                
                // ページ読み込み完了時にも実行
                $(window).on("load", function() {
                    updateSelectOptions();
                    setTimeout(updateSelect2Display, 100);
                });
                
                // 処理済みフラグ
                var searchDisabled = false;
                var displayUpdated = false;
                
                // Select2の検索機能を無効化
                function disableSelect2Search() {
                    if (searchDisabled) return; // 既に実行済みなら何もしない
                    
                    // まずoption要素を更新
                    updateSelectOptions();
                    
                    // 既存のselect要素をすべて対象にする
                    $("select").each(function() {
                        var $select = $(this);
                        
                        // Select2が適用されていて、まだ無効化されていない場合のみ
                        if ($select.hasClass("select2-hidden-accessible") && !$select.data("search-disabled")) {
                            // 一度破棄して再初期化
                            $select.select2("destroy");
                            
                            // Select2を検索無効で再初期化
                            $select.select2({
                                minimumResultsForSearch: Infinity, // 検索欄を完全に無効化
                                width: "100%",
                                dropdownAutoWidth: true
                            });
                            
                            // 処理済みマーク
                            $select.data("search-disabled", true);
                        }
                    });
                    
                    // CSSで検索欄を強制的に非表示
                    if (!$("#hide-select2-search-css").length) {
                        $("head").append(\'<style id="hide-select2-search-css">.select2-search { display: none !important; } .select2-search__field { display: none !important; }</style>\');
                    }
                    
                    // 再初期化後に表示フォーマットを適用
                    setTimeout(updateSelect2Display, 100);
                    
                    searchDisabled = true;
                }
                
                // 初回のSelect2検索無効化（表示処理の後に実行）
                setTimeout(disableSelect2Search, 1000);
                
                // チェックアウトページが更新された際にも実行（フラグをリセット）
                $(document.body).on("updated_checkout", function() {
                    // Select2を一旦非表示
                    $(".select2-container").css("opacity", "0");
                    
                    // まずoption要素を更新
                    updateSelectOptions();
                    // その後表示フォーマットを適用
                    setTimeout(updateSelect2Display, 50);
                    setTimeout(updateSelect2Display, 200);
                    // その後で検索無効化
                    searchDisabled = false; // フラグをリセット
                    setTimeout(disableSelect2Search, 300);
                });
                
                // Select2が開かれる直前にも制御
                $(document).on("select2:opening", function() {
                    // まずoption要素を更新
                    updateSelectOptions();
                    // ドロップダウンオプションを事前に非表示
                    $(".select2-results__options").css("opacity", "0");
                    $(".select2-results__option").css("opacity", "0");
                });
                
                // Select2が開かれた時にも実行
                $(document).on("select2:open", function() {
                    // ドロップダウンオプションを一旦非表示
                    $(".select2-results__options").css("opacity", "0");
                    $(".select2-results__option").css("opacity", "0");
                    
                    // 表示を確実にする
                    $(".select2-container").css("opacity", "1");
                    
                    // フォーマット処理を実行してから表示
                    setTimeout(function() {
                        updateSelect2Display();
                        // オプションを表示
                        $(".select2-results__options").css("opacity", "1");
                        $(".select2-results__option").css("opacity", "1");
                    }, 20);
                    
                    // 開かれた時に検索欄を非表示にする
                    setTimeout(function() {
                        $(".select2-search").hide();
                        $(".select2-search__field").hide();
                    }, 50);
                });
                
                // Select2選択後にも実行
                $(document).on("select2:select", function() {
                    setTimeout(updateSelect2Display, 100);
                });
                
                // DOM変更を監視（スロットリング付き）
                var mutationTimeout;
                var observer = new MutationObserver(function(mutations) {
                    clearTimeout(mutationTimeout);
                    mutationTimeout = setTimeout(function() {
                        var hasNewNodes = mutations.some(function(mutation) {
                            return mutation.addedNodes.length > 0;
                        });
                        
                        if (hasNewNodes && !displayUpdated) {
                            updateSelect2Display();
                            displayUpdated = true;
                            setTimeout(function() { displayUpdated = false; }, 1000);
                        }
                    }, 300); // 300ms の間隔で実行を制限
                });
                
                // 初回読み込み時はフラグに関係なく実行
                setTimeout(function() {
                    displayUpdated = false; // フラグをリセットして確実に実行
                }, 50);
                
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