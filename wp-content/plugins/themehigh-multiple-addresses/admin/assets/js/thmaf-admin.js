var thmaf_base = (function($, window, document) {
    'use strict';
    
    function escapeHTML(html) {
        var fn = function(tag) {
            var charsToReplace = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&#34;'
            };
            return charsToReplace[tag] || tag;
        }
        return html.replace(/[&<>"]/g, fn);
    }
         
    function isHtmlIdValid(id) {
        var re = /^[a-z\_]+[a-z0-9\_]*$/;
        return re.test(id.trim());
    }
    
    function isValidHexColor(value) {    
        if (preg_match('/^#[a-f0-9]{6}$/i', value)) { // If user insert a HEX color with #     
            return true;
        }     
        return false;
    }
    
    function setup_tiptip_tooltips(){
        var tiptip_args = {
            'attribute': 'data-tip',
            'fadeIn': 50,
            'fadeOut': 50,
            'delay': 200
        };

        $('.tips').tipTip(tiptip_args);
    }
    
    function setup_enhanced_multi_select(parent){
        parent.find('select.thpladmin-enhanced-multi-select').each(
            function(){
                if(!$(this).hasClass('enhanced')) {
                    $(this).select2(
                        {
                            minimumResultsForSearch: 10,
                            allowClear : true,
                            placeholder: $(this).data('placeholder')
                        }
                    ).addClass('enhanced');
                }
            }
        );
    }
    
    function setup_enhanced_multi_select_with_value(parent){
        parent.find('select.thpladmin-enhanced-multi-select').each(
            function(){
                if(!$(this).hasClass('enhanced')) {
                    $(this).select2(
                        {
                            minimumResultsForSearch: 10,
                            allowClear : true,
                            placeholder: $(this).data('placeholder')
                        }
                    ).addClass('enhanced');
                    
                    var value = $(this).data('value');
                    value = value.split(",");
                    
                    $(this).val(value);
                    $(this).trigger('change');
                }
            }
        );
    }
    
    function prepare_field_order_indexes(elm) {
        $(elm+" tbody tr").each(
            function(index, el) {
                $('input.f_order', el).val(parseInt($(el).index(elm+" tbody tr")));
            }
        );
    }

    function setup_sortable_table(parent, elm, left){
        parent.find(elm+" tbody").sortable(
            {
                items:'tr',
                cursor:'move',
                axis:'y',
                handle: 'td.sort',
                scrollSensitivity:40,
                helper:function(e,ui){
                    ui.children().each(
                        function(){
                            $(this).width($(this).width());
                        }
                    );
                    ui.css('left', left);
                    return ui;
                }       
            }
        ); 
        
        $(elm+" tbody").on(
            "sortstart", function(event, ui){
                ui.item.css('background-color','#f6f6f6');                                      
            }
        );
        $(elm+" tbody").on(
            "sortstop", function(event, ui ){
                ui.item.removeAttr('style');
                prepare_field_order_indexes(elm);
            }
        );
    }
    
    function get_property_field_value(form, type, name){
        var value = ''; 
        switch(type) {
        case 'select':
            value = form.find("select[name=i_"+name+"]").val();
            value = value == null ? '' : value;
            break;
                
        case 'checkbox':
                value = form.find("input[name=i_"+name+"]").prop('checked');
                value = value ? 1 : 0;
                break;
                
        default:
                value = form.find("input[name=i_"+name+"]").val();
                value = value == null ? '' : value;
        }   
        
        return value;
    }
    
    function set_property_field_value(form, type, name, value, multiple){
        switch(type) {
        case 'select':
            if(multiple == 1 && typeof(value) === 'string') {
                value = value.split(",");
                name = name+"[]";
            }
            form.find('select[name="i_'+name+'"]').val(value);
            break;
                
        case 'checkbox':
            value = value == 1 ? true : false;
            form.find("input[name=i_"+name+"]").prop('checked', value);
            break;
                
        default:
                form.find("input[name=i_"+name+"]").val(value);
        }   
    }
        
    return {
        escapeHTML : escapeHTML,
        isHtmlIdValid : isHtmlIdValid,
        isValidHexColor : isValidHexColor,
        setup_tiptip_tooltips : setup_tiptip_tooltips,
        setupEnhancedMultiSelect : setup_enhanced_multi_select,
        setupEnhancedMultiSelectWithValue : setup_enhanced_multi_select_with_value,
        setupSortableTable : setup_sortable_table,
        get_property_field_value : get_property_field_value,
        set_property_field_value : set_property_field_value,
    };
}(window.jQuery, window, document));
var thmaf_settings = (
    function($, window, document) {
        'use strict';
        var MSG_INVALID_NAME = 'NAME/ID must begin with a lowercase letter ([a-z]) and may be followed by any number of lowercase letters, digits ([0-9]) and underscores ("_")';
                
        /*------------------------------------
        *---- ON-LOAD FUNCTIONS - SATRT -----   
        *------------------------------------*/
        $(function() {
            var settings_form = $('#thmaf_product_fields_form');            
            thmaf_base.setup_tiptip_tooltips();
            disable_multi_shipping_fields();
            hide_edit_button_on_admin_order();
            //number_field_validation();
        });
      
        function disable_multi_shipping_fields(){
            var enable_multi_shipping = $('input[name=i_enable_multi_shipping]');
            var enable_product_variation = $('input[name=i_enable_product_variation]');

            /* Disable Billing */
            if ($('input[name=i_enable_billing]').is(":checked")) {
                $("input#thb_limit_value").prop('disabled', false);
            } else {
                $("input#thb_limit_value").prop('disabled', true);
            }
            $(document).on('click','input[name=i_enable_billing]',function() {
                if ($(this).prop("checked")) {
                    $("input#thb_limit_value").prop('disabled', false);
                } else {
                    $("input#thb_limit_value").prop('disabled', true);
                }
            });

            /* Disable shipping */
            if ($('input[name=i_enable_shipping]').is(":checked")) {
                enable_multi_shipping.prop("disabled", false);
                enable_product_variation.prop("disabled", false);
                $("input#ths_limit_value").prop('disabled', false);
            } else {               
                enable_multi_shipping.prop("disabled", true);
                enable_product_variation.prop("disabled", true);
                $("input#ths_limit_value").prop('disabled', true);
            }
            $(document).on('click','input[name=i_enable_shipping]',function() {
                if ($(this).prop("checked")) {                    
                    enable_multi_shipping.prop("disabled", false);
                    enable_product_variation.prop("disabled", false);
                    $("input#ths_limit_value").prop('disabled', false);
                } else {              
                    enable_multi_shipping.prop("disabled", true);
                    enable_product_variation.prop("disabled", true);
                    $("input#ths_limit_value").prop('disabled', true);
                }
            });

            /*  Disable multi-shipping */
            if (enable_multi_shipping.is(":checked")) {
                enable_product_variation.prop("disabled", false);
            } else {
                enable_product_variation.prop("disabled", true);
            }
            $(document).on('click','input[name=i_enable_multi_shipping]',function() {
                if ($(this).prop("checked")) {
                    enable_product_variation.prop("disabled", false);
                } else {
                    enable_product_variation.prop("disabled", true);
                }
            });
        }

        // function number_field_validation(){ 
        //     $('#thb_limit_value').keyup(function(e) {
        //         var num_field = $('#thb_limit_value');
        //         test_fn($(this).val(), num_field);
        //         // if($(this).val() == ''){
        //         //     num_field.val('1');
        //         // }
        //     });
        //     $('#ths_limit_value').keyup(function(e) {
        //         var num_field = $('#ths_limit_value');
        //         test_fn($(this).val(), num_field);
        //         // if($(this).val() == ''){
        //         //     num_field.val('1');
        //         // }
        //     });
        // }
        function test_fn(test_value, num_field){
            var test_value = test_value.replace(/[^0-9]+/g, "");
            num_field.val('');
            num_field.val(test_value);
        }

        function hide_edit_button_on_admin_order() {
            if($('.multi_ship_enabled').val() == 'yes') {
                var parent_elm = $('.load_customer_shipping').parent();
                var edit_button = parent_elm.siblings('.edit_address').hide();
            } else {
                var parent_elm = $('.load_customer_shipping').parent();
                var edit_button = parent_elm.siblings('.edit_address').show();
            }
        }

        return {
            disable_multi_shipping_fields : disable_multi_shipping_fields,
            hide_edit_button_on_admin_order : hide_edit_button_on_admin_order,
            //number_field_validation : number_field_validation
        }
    }
(window.jQuery, window, document)); 
