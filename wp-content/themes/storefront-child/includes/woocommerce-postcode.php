<?php
/**
 * 郵便番号自動住所入力機能
 */

if (!defined('ABSPATH')) {
    exit;
}

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