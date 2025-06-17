var address_slider= (
    function($, window, document) {
        'use strict';
		
		var activeBillNxt = true;
		var activeShipNxt = true;
		
		/*************************************/
		/***** Delete specific addresses. ****/
		/*************************************/
		function delete_address(elm,type,key){
			activeBillNxt = true;
			activeShipNxt = true;

            if(type == 'billing'){
                $("#thmaf-billing-tile-field").append('<div class="ajaxBusy"> <i class="fa fa-spinner" aria-hidden="true"></i></div>');
            }else{
                $("#thmaf-shipping-tile-field").append('<div class="ajaxBusy"> <i class="fa fa-spinner" aria-hidden="true"></i></div>');
            }
            var selected_address_id = key;
            var selected_type = type;
            var data = {
                action: 'delete_address_with_id',
                security: thmaf_public_var.delete_address_with_id_nonce,
                selected_address_id: selected_address_id,
                selected_type : selected_type,
            };
            $('.ajaxBusy').show();
            $.ajax({
                url: thmaf_public_var.ajax_url,
                data: data,
                type: 'POST',
                success: function (response) {

                    $('#thmaf-billing-tile-field').html(response.result_billing);
                    $('#thmaf-shipping-tile-field').html(response.result_shipping);
                    $('.ajaxBusy').hide();

					$('.thmaf-shipping-adrs-count').val(response.address_count);
                    setup_shipping_address_slider('shipping');
                    setup_billing_address_slider('billing');

                }
            });
        }
		// function enable_disable_prev_next_action(prevBtn, nextBtn, startPos, endPos, totalItems, itemsPerView){
		// 	var disablePrev = false;
		// 	var disableNext = false;
		// 	if(startPos === 0){
		// 		disablePrev = true;
		// 	}
		// 	if(endPos === totalItems){
		// 		disableNext = true;
		// 	}
		// 	prevBtn.removeClass('disabled');
		// 	nextBtn.removeClass('disabled');
		// 	if(disablePrev){
		//     	prevBtn.addClass('disabled');
		// 	}
		// 	if(disableNext){
		//     	nextBtn.addClass('disabled');
		// 	}
		// }

		/*************************************/
		/*** Slider function on page load. ***/
		/*************************************/

		var exist_slider = $('.thmaf-thslider-box').length;
		if(exist_slider>0){
			if($(window).width() > 600){
				var items_per_view = 1;

				setup_shipping_address_slider('shipping');
				setup_billing_address_slider('billing');
			}
		}
		

		function setup_billing_address_slider(type){
	    	var list = $('.thmaf-thslider-list.bill');
	    	var prevBtn = $('.control-buttons .thmaf-thslider-prev.billing');
	    	var nextBtn = $('.control-buttons .thmaf-thslider-next.billing');
	    	var total_items = $('.thmaf-thslider-item.'+type).length;
	    	var item_width = 210+20;
	    	var total_width = (total_items*item_width);
	    	var initialPos = 0;
	    	var items_limit = total_items - 2;	

			list.css('width', total_width);

			prevBtn.click(function () {
				activeBillNxt = true;
				if (initialPos != 0) {
					initialPos -= 1;
					move_sliders(list, total_items, '-=230', items_limit, initialPos, type);
				}
			});
			nextBtn.click(function() {
				if (activeBillNxt) {
					initialPos += 1;
					move_sliders(list, total_items, '+=230', items_limit, initialPos, type);
				}
			});
		}

		function move_sliders(slider, total_items, slid_side, items_limit, initialPos, type){
			if (items_limit == initialPos) { 
				type == 'billing' ? activeBillNxt = false : activeShipNxt = false;
			}
			slider.animate(
	        	{right: slid_side}, 
	        	{duration:500, 
	        		complete: function(){ 
	        			// enable_disable_prev_next_action(prevBtn, nextBtn, startPos, endPos, totalItems, itemsPerView);
	        		} 
	        	}
	        );
		}

		function setup_shipping_address_slider(type){
	    	var ship_list = $('.thmaf-thslider-list.ship');
	    	var ship_prevBtn = $('.control-buttons .thmaf-thslider-prev.shipping');
	    	var ship_nextBtn = $('.control-buttons .thmaf-thslider-next.shipping');
	    	var ship_total_items = $('.thmaf-thslider-item.'+type).length;
	    	var item_width = 210+20;
	    	var ship_total_width = ship_total_items*item_width;
	    	var ship_initialPos = 0;
	    	var ship_items_limit = ship_total_items - 2;
	    	ship_list.css('width', ship_total_width);
	    	
			ship_prevBtn.click(function () {
				activeShipNxt = true;
				if (ship_initialPos != 0) {
					ship_initialPos -= 1;
					move_sliders(ship_list, ship_total_items, '-=230', ship_items_limit, ship_initialPos, type);
				}
			});

			ship_nextBtn.click(function() {
				if (activeShipNxt) {
					ship_initialPos += 1;
					move_sliders(ship_list, ship_total_items, '+=230', ship_items_limit, ship_initialPos, type);
				}
			});
		}

        return {
        	// slider_arrow_limits : slider_arrow_limits,
        	delete_address : delete_address,
        };
    }(window.jQuery, window, document)
);


function thmaf_delete_selected_address(elm,type,key) {
     address_slider.delete_address(elm,type,key);
}

var thmaf_address= (
    function($, window, document) {
        'use strict';
        var default_shipping = $('#thmaf_checkbox_shipping').val();
        ship_to_multi_address();
        select_cart_mutlti_shipping();
        shipping_method_change();
        // initialize_select2();

        function populate_selected_address(elm, address_type, key) {
            var selected_address_id = key;
            var data = {
                action: 'get_address_with_id',
                security: thmaf_public_var.get_address_with_id_nonce,
                selected_address_id: selected_address_id, 
                selected_type:address_type,
            };           
            $.ajax(
                {
                    url: thmaf_public_var.ajax_url,
                    data: data,
                    type: 'POST',
                    success: function (response) {
                        if (response === 1) {
                            alert("The selected address is doesn't exist, Please reload the page.");
                        }
                        var sell_countries = thmaf_public_var.sell_countries;
                        var sell_countries_size = Object.keys(sell_countries).length;
                        var address_fields = [];
                        if(address_type == 'billing') {
                            address_fields = thmaf_public_var.address_fields_billing;
                        }else{
                            address_fields = thmaf_public_var.address_fields_shipping;
                        }

                        $.each( 
                            address_fields, function(f_key, f_type) {
                                var input_elm = '';
                                if(f_type == 'radio' || f_type == 'checkboxgroup') {
                                    input_elm = $("input[name="+f_key+"]");
                                }else{
                                    input_elm = $('#'+f_key);
                                }
                                var skip = (sell_countries_size == 1 && f_key == address_type+'_country') ? true : false;
                                if (sell_countries_size == 1) {
                                    if(f_key == 'billing_country') {
                                        skip = true;
                                    } else {
                                        skip = false;
                                    }
                                }
                                if (!skip && input_elm.length) {
                                    var _type = input_elm.getType();
                                    var _value = response[f_key];
                                    if(f_type === 'file') {
                                        _type = 'file';
                                    }
                                    thmaf_public_base.set_field_value_by_elm(input_elm, _type, _value);
                                }
                            }
                        );
                    }
                }
            );

            if(address_type == 'billing') {
                $('#thmaf_hidden_field_billing').val(selected_address_id); 
                bpopup.dialog('close');
            }else{
                $('#thmaf_hidden_field_shipping').val(selected_address_id);
                spopup.dialog('close');
            }
        }
        var active = false;
        var bpopup = $("#thmaf-billing-tile-field");
        var spopup = $("#thmaf-shipping-tile-field");

        // Initialise popups.
        if($(window).width()<600){
            var popupwidth = $(window).width() - 20;
            bpopup.dialog({
               'dialogClass'    : 'wp-dialog thmaf-popup',
               'title'          : thmaf_public_var.billing_address,
               'modal'          : true,
               'autoOpen'       : false,
               'width'          : popupwidth,
            });

            spopup.dialog({
               'dialogClass'   : 'wp-dialog thmaf-popup',
               'title'         : thmaf_public_var.shipping_address,
               'modal'         : true,
               'autoOpen'      : false,
               'width'         : popupwidth,
            });

        }else{
            bpopup.dialog({
               'dialogClass'    : 'wp-dialog thmaf-popup',
               'title'          : thmaf_public_var.billing_address,
               'modal'          : true,
               'autoOpen'       : false,
               'minHeight'      : 400,
               'maxHeight'      : 600,
               'width'          : 780,
            });

            spopup.dialog({
               'dialogClass'   : 'wp-dialog thmaf-popup',
               'title'         : thmaf_public_var.shipping_address,
               'modal'         : true,
               'autoOpen'      : false,
               'minHeight'      : 400,
               'maxHeight'     : 600,
               'width'         : 780,
            });
        }
       
        // Show billing popup.
        function show_billing_popup(e){
            e.preventDefault();

            $('.thwma-btn').removeClass('slctd-adrs');
            var selected_address = $('#thwma_hidden_field_billing').val();

            if(selected_address){
                $('.'+selected_address).addClass('slctd-adrs');
            }

            bpopup.dialog('open');
            // if addresses is > 2 to remove the 'add new address' button.
            var address_count = $('.thmaf-billing-adrs-count').val();
            if(address_count >= 2){
                $('.button.btn-add-address.primary.button').css('display','none');
            }else{
                $('.button.btn-add-address.primary.button').css('display','inline-block');
            }
            active = false;
        }

        // Show shipping popup.
        function show_shipping_popup(e){
            e.preventDefault();
            spopup.dialog('open');
            // if addresses is > 2 to remove the 'add new address' button.
            var address_count = $('.thmaf-shipping-adrs-count').val();
            if(address_count >= 2){
                $('.button.btn-add-address.primary.button').css('display','none');
            }else{
                $('.button.btn-add-address.primary.button').css('display','inline-block');
            }
            // populate_selected_address('','shipping')
            active = false;
        }

        function add_new_address(elm, address_type, e) {
            $('#thmaf-cart-shipping-form-section').css('display', 'block');
            if('shipping' === address_type){
                $('.woocommerce-shipping-fields__field-wrapper').css('display','none');
                add_new_shipping_address_model(e,'','shipping');
                var data = {
                    action: 'add_new_shipping_address',
                    security: thmaf_public_var.add_new_shipping_address_nonce,
                    selected_type : address_type,
                    multiple_ship : true,
                };
                $.ajax({
                    url: thmaf_public_var.ajax_url,
                    data: data,
                    type: 'POST',
                    success: function (response) {
                        $('#thmaf-cart-shipping-form-section').html(response);
                        initialize_select2()
                    }
                });
            }
            
            var sell_countries = thmaf_public_var.sell_countries;
            var sell_countries_size = Object.keys(sell_countries).length;
            var address_fields = [];
              
            if(address_type=='billing') {
                address_fields = thmaf_public_var.address_fields_billing;
                $('#thmaf_hidden_field_billing').val('add_address');
            }else{
                address_fields = thmaf_public_var.address_fields_shipping;
                $('#thmaf_hidden_field_shipping').val('add_address');
            }
              
            $.each( 
                address_fields, function(f_key, f_type) {
                    var input_elm = '';

                    if(f_type == 'radio' || f_type == 'checkboxgroup') {
                        input_elm = $("input[name="+f_key+"]");
                    }else{
                        input_elm = $('#'+f_key);
                    }

                    var skip = (sell_countries_size == 1 && f_key == address_type+'_country') ? true : false;
                    if (sell_countries_size == 1) {
                        if(f_key == 'billing_country') {
                            skip = true;
                        } else {
                            skip = false;
                        }
                    }
                      
                    if (!skip && input_elm.length) {
                        var _type = input_elm.getType();
                        if(f_type === 'file') {
                            _type = 'file';
                        }
                        thmaf_public_base.thmaf_set_field_value_by_elm(input_elm, _type, '');
                    }
                }
            );
            bpopup.dialog('close');  
            spopup.dialog('close');  
        }

        $("#thmaf_billing_alt").change(
            function(event) {
                event.preventDefault();
                var select_type = this.value;
                var type = 'billing';
                var elm = '';
                if(select_type == 'add_address') {
                    add_new_address(elm,type);
                }else {
                    populate_selected_address(elm, type, select_type);
                } 
            }
        );

        $("#thmaf_shipping_alt").change(
            function(event) {
                event.preventDefault();
                var select_type = this.value;
                var type = 'shipping';
                var elm = '';
                if(select_type == 'add_address') {
                    add_new_address(elm,type, event);
                }else{
                    populate_selected_address(elm, type, select_type);
                } 
            }
        );
          ///////////////////////////
        $('#ship-to-different-address-checkbox').change(
            function() {
                if ($('#ship-to-different-address-checkbox').is(':checked')) {
                    $('#thmaf_checkbox_shipping').val('ship_select');
                }
                else {
                    $('#thmaf_checkbox_shipping').val(default_shipping);
                }
            }
        );

        function ship_to_multi_address(){
            var $show = false;
            var checkout_check = thmaf_public_var.is_checkout_page;
            var ship_to_dif_adr_checkbox = $("input[name=ship_to_multi_address]");
            var ship_address_count = $('.thmaf-shipping-adrs-count').val();
            var bill_address_count = $('.thmaf-billing-adrs-count').val();
            
            // if shipping address count is greater than 2, to remove the 'add new address' option on the dropdown.
            if(ship_address_count >= 2){
                $('.thmaf-add-new-address-link').css('display','none');
                $('select#thmaf_shipping_alt').find("option[value='add_address']").remove();
            }
            // if billing address count is greater than 2, to remove the 'add new address' option on the dropdown.
            if(bill_address_count >= 2){
                $('select#thmaf_billing_alt').find("option[value='add_address']").remove();
            }
            // Initial condition.
            var ship_to_multi_address_f = ship_to_dif_adr_checkbox.val();
            if(ship_to_multi_address_f == 'no') {
                $show = false;
                ship_to_dif_adr_checkbox.removeClass('active_multi_ship');
                hide_and_show_multi_ship_section($show);
            } else {
                $show = true;
                ship_to_dif_adr_checkbox.addClass('active_multi_ship');
                hide_and_show_multi_ship_section($show);
            }

            // Enable/disable ship to different address checkbox.
            $('.woocommerce').on('click', 'input[name=ship_to_different_address]', function() {  
                if($(this).prop("checked") == false) {
                    ship_to_dif_adr_checkbox.val('no');
                    var value  = 'no';
                } else if($(this).prop("checked") == true){
                    if ($(".active_multi_ship")[0]){
                        ship_to_dif_adr_checkbox.val('yes');
                        var value  = 'yes';             
                    }
                }
                enable_ship_to_multi_address_ajax(value);
            });

            // Ship to diffrent address checkbox propt conditions.
            var shipping_section = $("input[name=ship_to_different_address]").prop("checked");
            var ship_to_multi_address = $("input[name=ship_to_multi_address]").val();
            if(checkout_check == true) { 
                var ship_to_multi_address = $('input[name="ship_to_multi_address"]').val();
                if(ship_to_multi_address != null){
                    if(ship_to_multi_address.length){
                        if(shipping_section == true) {
                            if(ship_to_multi_address == 'yes'){
                                $show = true;
                                ship_to_dif_adr_checkbox.addClass('active_multi_ship');
                                hide_and_show_multi_ship_section($show);
                                var value  = 'yes';
                            } else {
                                $show = false;
                                ship_to_dif_adr_checkbox.removeClass('active_multi_ship');
                                hide_and_show_multi_ship_section($show);
                            }
                        } else {
                            var value  = 'no';
                        }
                        enable_ship_to_multi_address_ajax(value);
                    }
                } else {
                    $show = false;
                    hide_and_show_multi_ship_section($show);        
                }
            }

            // Enable / disable multi-shipping checkbox.
            $('.woocommerce').on('click', 'input[name=ship_to_multi_address]', function() {
                if ($(this).is(":checked")) {
                    ship_to_dif_adr_checkbox.addClass('active_multi_ship');
                    var address_count = $(this).attr('data-address_count');
                    if(address_count == 0) {
                        $show = false;
                        hide_and_show_multi_ship_section($show);
                    } else{                 
                        $show = true;
                        hide_and_show_multi_ship_section($show);
                    }
                    $(this).val('yes');

                    // Multi-shipping.
                    $('#shipping_tiles').css('display','none');
                    $('.woocommerce-shipping-fields__field-wrapper').css('display','none');
                    $('p#thwma-shipping-alt_field').css('display','none');
                    
                    var qunatity = $(".ship_to_diff_adr").attr("data-cart_quantity");
                    $(".ship_to_diff_adr").each(function(index) {
                        var qunatity = $(this).attr("data-cart_quantity");
                        if(qunatity > 1) {
                            $(this).css('display','block');
                        } else{
                            $(this).css('display','none');
                        }
                    });

                } else if($(this).prop("checked") == false){    

                    // Remove the multishipping address form content.
                    $('#shipping_tiles').css('display','block');
                    ship_to_dif_adr_checkbox.removeClass('active_multi_ship');
                    $show = false;
                    hide_and_show_multi_ship_section($show);
                    $(this).val('no');
                }
                var value  = $(this).val();
                enable_ship_to_multi_address_ajax(value);
            });
        }

        // Enable / disable popup button. In page load.
        if($('input.active_multi_ship').is(":checked")){
            $('#shipping_tiles').css('display','none');
        }else{
            $('#shipping_tiles').css('display','block');
        }

        function enable_ship_to_multi_address_ajax(value) {
            var data = {
                action: 'enable_ship_to_multi_address',
                security: thmaf_public_var.enable_ship_to_multi_address_nonce,
                value: value,
            };
            $.ajax({
                url: thmaf_public_var.ajax_url,
                data: data,
                type: 'POST',
                success: function (response) {
                    $('body').trigger('update_checkout');
                }
            });
        }
        // 'Do you want to ship to multiple addresses?' checkbox on click.
        function hide_and_show_multi_ship_section($show) {
            var address_count = $('.thmaf-shipping-adrs-count').val();
            var multi_ship_wrap = $('.multi-shipping-wrapper');
            var ms_table = $('.multi-shipping-table');
            var ms_dropdown = $('.thwma_cart_multi_shipping_display');
            var shipping_dp = $('#thmaf_shipping_alt');
            var default_fields = $('.woocommerce-shipping-fields__field-wrapper');
            var add_new_address = $('.thmaf-add-new-address-link');

            if($show == true) {
                ms_table.css('display','table');
                ms_dropdown.css('display','block');
                shipping_dp.css('display','none');
                default_fields.css('display','none');
                add_new_address.css('display', 'block');
                multi_ship_wrap.css('display', 'block')
                
                if(address_count >= 2){
                    $('.thmaf-add-new-address-link').css('display','none');
                    $('select#thmaf_shipping_alt').find("option[value='add_address']").remove();
                }
            } else {
                ms_table.css('display','none');
                ms_dropdown.css('display','none');
                shipping_dp.css('display','block');
                default_fields.css('display','block');
                add_new_address.css('display', 'none');
                $('#thmaf-cart-shipping-form-section').find('#thmaf-cart-modal-content2').remove();
            }
        }

        function select_cart_mutlti_shipping(){
            $('.woocommerce').on('change', 'select.thwma-cart-shipping-options', function() {
                $(".multi-shipping-table-overlay").append('<div class="ajaxBusy"> <i class="fa fa-spinner" aria-hidden="true"></i></div>');
                $(".multi-shipping-table-overlay").css('display', 'block');
                $('.ajaxBusy').show();
                var value = $(this).val();
                var $this = $(this);
                var product_id = $(this).attr("data-product_id");
                var cart_key = $(this).attr("data-cart_key");
                var check_multi_shipping = $(this).attr("data-exist_multi_adr");

                var multi_ship_item = $this.closest("tr").find(".multi-ship-item");
                var multi_ship_id = multi_ship_item.data("multi_ship_id");
                var type = 'shipping';
                var elm = '';

                var exist_multi_ship = $('.multi-shipping-adr-data').val();
                var multi_ship_data = [];
                if(multi_ship_data != ''){
                    if(exist_multi_ship != undefined) {
                        var multi_ship_data = JSON.parse(exist_multi_ship);             
                    }
                
                    jQuery.each(multi_ship_data, function( index, val ) {
                        multi_ship_data[cart_key] = {'product_id': product_id, 'address_name': value};
                    });
                }
                var new_multi_ship_dta = '';
                if(multi_ship_data != undefined) {
                    var new_multi_ship_dta = JSON.stringify(multi_ship_data);
                }
                $('.multi-shipping-adr-data').val(new_multi_ship_dta);
                
                // Populate the selected address on the hidden checkout form.
                var first_tr = $this.closest('table').find('tr:nth-child(2)');
                var first_set_adrs = first_tr.find("select.thwma-cart-shipping-options").val();
                if((first_set_adrs != null) && (first_set_adrs !='')) {
                    populate_selected_address(elm,type,first_set_adrs);
                } else {
                    set_default_adr_to_shipping_fields();
                }
                var data = {
                    action: 'save_multi_selected_shipping',
                    security: thmaf_public_var.save_multi_selected_shipping_nonce,
                    value: value,
                    product_id: product_id,
                    cart_key: cart_key,
                    multi_ship_id : multi_ship_id,
                };
                $.ajax({
                    url: thmaf_public_var.ajax_url,
                    data: data,
                    type: 'POST',
                    success: function (response) {
                        $('.address-limiting-message').hide();
                        if(response.length > 0){
                            $( ".multi-shipping-table-wrapper" ).prepend( "<p class='address-limiting-message woocommerce-error'>"+response+"</p>" );
                            $this.css('border-color', 'red');  
                            $this.val('');                          
                        } else {
                            $('.address-limiting-message').hide();
                            $this.css('border-color', '#767676');  
                        }
                        shipping_method_change();
                        $('.ajaxBusy').hide();
                        $(".multi-shipping-table-overlay").css('display', 'none');
                    }
                });
            }); 
        }

        // rendering select2 
        function initialize_select2(){
            $("select#shipping_country").select2({
                tags: true,
            });

            $("select#shipping_state").select2({
                tags: true,
            });
        }

        // Set a default address to the shipping field in the case of multi-shipping.
        function set_default_adr_to_shipping_fields() {
            //populate_selected_address(elm,type,first_set_adrs);
            var first_set_adrs = '';
            var first_tr = $('table.multi-shipping-table').find('tr:nth-child(2)');
            var first_set_adrs = first_tr.find("select.thwma-cart-shipping-options").val();
            if((first_set_adrs != null) && (first_set_adrs !='')) {
                var elm = '';
                var type = 'shipping';
                populate_selected_address(elm,type,first_set_adrs);
            } else {
                var address_type = 'shipping';
                var ship_default_adr = jQuery("[name='ship_default_adr']").val();
                if(ship_default_adr != null) {
                    var response = '';
                    if(ship_default_adr != undefined) {
                        var response = jQuery.parseJSON(ship_default_adr);
                    }
                    
                    var sell_countries = thmaf_public_var.sell_countries;
                    var sell_countries_size = Object.keys(sell_countries).length;
                    var address_fields = [];

                    if(address_type == 'shipping'){
                        address_fields = thmaf_public_var.address_fields_shipping;
                    }       
                    $.each( address_fields, function(f_key, f_type) {
                        var input_elm = '';

                        if(f_type == 'radio' || f_type == 'checkboxgroup'){
                            input_elm = $("input[name="+f_key+"]");
                        }else{
                            input_elm = $('#'+f_key);
                        }
                        var skip = (sell_countries_size == 1 && f_key == address_type+'_country') ? true : false;
                        if (sell_countries_size == 1){
                            if(f_key == 'billing_country'){
                                skip = true;
                            } else {
                                skip = false;
                            }
                        }
                        if (!skip && input_elm.length) {
                            var _type = input_elm.getType();
                            var _value = response[f_key];

                            if(f_type === 'file'){
                                _type = 'file';
                            }
                            thmaf_public_base.set_field_value_by_elm(input_elm, _type, _value, f_key);
                        }
                    });
                }
            }
        }

        function shipping_method_change(){
            $('body').trigger('update_checkout');
            var data = {};
            var ship_mthd_arry = [];
            
            var method_id = '';
            var nearest_ul_id = '';
            var ship_cart_key = '';
            var item_name = '';
            var item_qty = '';
            var product_id = '';
            var shipping_adrs = '';
            var shipping_name = '';
            setTimeout(
                function() {
                    $( 'select.shipping_method, input[name^="shipping_method"][type="radio"]:checked' ).each( function() {
                        var method_id = $( this ).val();
                        var nearest_ul_id = $( this ).closest('ul').attr('id');
                        var ship_cart_key = $( this ).closest('ul').siblings('.ship-cart-key').val();
                        var ship_cart_unique_key = $( this ).closest('ul').siblings('.ship-cart-unique-key').val();
                        var item_name = $( this ).closest('ul').siblings('.ship-product-name').text();
                        var item_qty = $( this ).closest('ul').siblings('.ship-product-qty').val();
                        var product_id = $( this ).closest('ul').siblings('.ship-product-id').val();
                        var shipping_adrs = $( this ).closest('ul').siblings('.ship-address-formated').val();

                        var shipping_name = $( this ).closest('ul').siblings('.ship-address-name').val();
                        item_name = item_name.replace(":","");
                        var shipping_array = { method_id : method_id, parent_ul_id : nearest_ul_id, cart_key : ship_cart_key, cart_unique_key : ship_cart_unique_key, item_name : item_name, product_id : product_id, shipping_adrs : shipping_adrs, shipping_name : shipping_name, item_qty : item_qty } ;
                        ship_mthd_arry.push(shipping_array);

                    } );
                    
                    var data = {
                        action: 'save_shipping_method_details',
                        security: thmaf_public_var.save_shipping_method_details_nonce,
                        ship_method_arr: ship_mthd_arry,
                    };
                    $.ajax({
                        url: thmaf_public_var.ajax_url,
                        data: data,
                        type: 'POST',
                        success: function (response) {
                        }
                    });

            }, 2000);

            var checkout_form =  $( 'form.checkout' );
            var ship_mthd_arrys = [];
            checkout_form.on('change', 'input[name^="shipping_method"]', function(e){
                var method_id = $( this ).val();
                var nearest_ul_id = $( this ).closest('ul').attr('id');
                var ship_cart_key = $( this ).closest('ul').siblings('.ship-cart-key').val();
                var ship_cart_unique_key = $( this ).closest('ul').siblings('.ship-cart-unique-key').val();
                var item_name = $( this ).closest('ul').siblings('.ship-product-name').text();
                var item_qty = $( this ).closest('ul').siblings('.ship-product-qty').val();
                var product_id = $( this ).closest('ul').siblings('.ship-product-id').val();
                var shipping_adrs = $( this ).closest('ul').siblings('.ship-address-formated').val();

                var shipping_name = $( this ).closest('ul').siblings('.ship-address-name').val();
                item_name = item_name.replace(":","");
                
                var map = Object.create(null);
                ship_mthd_arry.forEach(function(entry) {
                    if (entry.cart_key == ship_cart_key) {
                        entry.method_id = method_id;
                    }
                });
                var data = {
                    action: 'save_shipping_method_details',
                    security: thmaf_public_var.save_shipping_method_details_nonce,
                    ship_method_arr: ship_mthd_arry,
                };
                $.ajax({
                    url: thmaf_public_var.ajax_url,
                    data: data,
                    type: 'POST',
                    success: function (response) {
                    }
                });
            });             
        }

        // ajax call checkout page save addresses form
        function add_new_shipping_address_model(e, elm, type){
            e.preventDefault();
            $('#thmaf-cart-shipping-form-section').css('display', 'block');
            var data = {
                action: 'add_new_shipping_address',
                security: thmaf_public_var.add_new_shipping_address_nonce,
                selected_type : type,
            };
            $.ajax({
                url: thmaf_public_var.ajax_url,
                data: data,
                type: 'POST',
                success: function (response) {
                    $('#thmaf-cart-shipping-form-section').html(response);
                    initialize_select2();
                }
            })

        }

        // function for saving the addresses on the checkout page.
        function cart_save_address(e) {
            $('.woocodfdfmmerce-shipping-fields__field-wrapper').html('<div class="ajaxBusy"> <i class="fa fa-spinner" aria-hidden="true"></i></div>');
            e.preventDefault();
   		    $('.ajaxBusy').show();

            var cart_shipping = [];
            var data_arr = [];

            cart_shipping = $('#cart_shipping_form_wrap :input').serialize();
            var data = {
                action: 'thmaf_save_address',
                security: $( '#cart_ship_form_action' ).val(),
                cart_shipping: cart_shipping,
            };
            $.ajax({
                url: thmaf_public_var.ajax_url,
                data: data,
       		    type: 'POST',
                success : function(response){
                    if(response.true_check == 'true'){
                        $('.multi-shipping-wrapper').html(response.output_table);
                        $('select#thmaf_shipping_alt').append(response.address_dropdown);
                        $('.multi-shipping-wrapper').css('display','none');
                        var mul_ship_enable = $('#thmaf-enable-multiple-shipping').val()
                        $('.woocommerce-shipping-fields__field-wrapper').css('display','block');
                        if('yes' == mul_ship_enable){
                            $('.multi-shipping-wrapper').css('display','block');
                            $('.woocommerce-shipping-fields__field-wrapper').css('display','none');
                        }
                        $('.ajaxBusy').hide();
                        $('#thmaf-cart-shipping-form-section').find('#thmaf-cart-modal-content2').remove();

                        $('.thmaf-shipping-adrs-count').val(response.address_count);
                        $('.thmaf-thslider').html(response.result_shipping);
                        if(response.address_count >= 2){
                            $('.thmaf-add-new-address-link').css('display','none');
                            $('select#thmaf_shipping_alt').find("option[value='add_address']").remove();
                        }
                    }else{
                        $('.thmaf_hidden_error_mssgs').addClass('show_msgs');
                        $('.thmaf_hidden_error_mssgs.show_msgs').html(response.true_check);
					    $('.ajaxBusy').hide();
                    }
                }
            })
        }

        //function for closing form on the checkout page.
        function close_cart_add_adr_modal(e){
            $('#thmaf-cart-shipping-form-section').find('#thmaf-cart-modal-content2').remove();
            $('.woocommerce-shipping-fields__field-wrapper').css('display','block');
            var mul_ship_enable = $('#thmaf-enable-multiple-shipping').val()
            if('yes' == mul_ship_enable){
                $('.woocommerce-shipping-fields__field-wrapper').css('display','none');
            }
        }
        return {
            show_billing_popup : show_billing_popup,
            show_shipping_popup : show_shipping_popup,
            populate_selected_address:populate_selected_address,
            add_new_address : add_new_address,
            ship_to_multi_address: ship_to_multi_address,
            select_cart_mutlti_shipping : select_cart_mutlti_shipping,
            shipping_method_change : shipping_method_change,
            add_new_shipping_address_model : add_new_shipping_address_model,
            cart_save_address : cart_save_address,
            close_cart_add_adr_modal : close_cart_add_adr_modal,
        };
    }(window.jQuery, window, document)
);

function  thwma_show_billing_popup(e) {
    thmaf_address.show_billing_popup(e);
}

function  thmaf_show_shipping_popup(e) {
    thmaf_address.show_shipping_popup(e);
}

function thmaf_add_new_address(e,elm,type) {
    e.preventDefault();
    thmaf_address.add_new_address(elm,type, e);
}

function thmaf_add_new_shipping_address(e, elm, type) {
    e.preventDefault();
	thmaf_address.add_new_shipping_address_model(e,elm, type);
}

function thmaf_populate_selected_address(e, elm, type, key) {
    e.preventDefault();
    thmaf_address.populate_selected_address(elm,type,key);
}

function thmaf_cart_save_address(event) {
	event.preventDefault();
	thmaf_address.cart_save_address(event);
}

function thmaf_close_cart_add_adr_modal(e) {
	thmaf_address.close_cart_add_adr_modal(e);
}
var thmaf_public_base = (
    function($, window, document) {
        'use strict';  
        function isEmpty(val) {
            return (val === undefined || val == null || val.length <= 0) ? true : false;
        }
                
        $.fn.getType = function() {
            try{
                return this[0].tagName == "INPUT" ? this[0].type.toLowerCase() : this[0].tagName.toLowerCase(); 
            }catch(err) {
                return 'E001';
            }
        }
        
        /*
         * Character count funcion.
         */
        function display_char_count(elm, isCount) {
            var fid = elm.prop('id');
            var len = elm.val().length;
            var displayElm = $('#'+fid+"-char-count");
            
            if(isCount) {
                displayElm.text('('+len+' characters)');
            }else {
                var maxLen = elm.prop('maxlength');
                var left = maxLen-len;
                displayElm.text('('+left+' characters left)');
                if(rem < 0) {
                    displayElm.css('color', 'red');
                }
            }
        }

        /*
         * Set field values on checkout form.
         */
        function set_field_value_by_elm(elm, type, value) {
            switch(type) {
            case 'radio':
                elm.val([value]);
                break;
            case 'checkbox':
                if(elm.data('multiple') == 1) {
                    value = value ? value : [];
                    elm.val([value]).change();
                }else {
                    elm.val([value]).change();
                }
                break;
            case 'select':
                var options_append = thmaf_public_var.select_options;
                if(options_append == true) {
                    var option_values = [];
                    elm.find('option').each(
                        function(option_key,option_val) {
                            if($(this).val() != "") {
                                option_values[option_key] = $(this).val();
                            }
                        }
                    );
                    
                    if( $.inArray(value,option_values) != -1) {
                        if(elm.prop('multiple')) {
                            elm.val(value);
                        }else {
                            elm.val([value]).change();
                        }
                    }else {
                        elm.append($("<option></option>").attr("value",value).text(value)); 
                        elm.val([value]).change();
                    }
                }else {

                    if(elm.prop('multiple')) {
                        elm.val(value);
                    }else {
                        elm.val([value]).change();
                    }
                }
                break;
            case 'multiselect':               
                if(elm.prop('multiple')) {
                    if(typeof(value) != "undefined") {
                        elm.val(value.split(',')).change();
                    }
                }else {
                    elm.val([value]).change();
                }
                break;              
            case 'hidden':
                break;
            default:
                elm.val(value).change();
                //elm.trigger("change")
                break;
            }
        }

        /*
         * Set field values on checkout form case of add new address.
         */
        function thmaf_set_field_value_by_elm(elm, type, value) {
            switch(type) {
            case 'radio':
                elm.prop('checked',false);
                break;
            case 'checkbox':
                if(elm.data('multiple') == 1) {
                    value = value ? value : [];
                    elm.prop('checked',false);
                }else{
                    elm.prop('checked',false);
                }
                break;
            case 'select':
                var options_append = thmaf_public_var.select_options;
                if(options_append == true) {
                    var option_values = [];
                    elm.find('option').each(
                        function(option_key,option_val) {
                            if($(this).val() != "") {
                                option_values[option_key] = $(this).val();
                            }
                        }
                    );
                    
                    if( $.inArray(value,option_values) != -1) {
                        if(elm.prop('multiple')) {
                            elm.val('');
                        }else {
                            elm.val('');
                        }
                    }else {
                        elm.append($("<option></option>").attr("value",value).text(value)); 
                        elm.val('');
                    }
                }else {

                    if(elm.prop('multiple')) {
                        elm.val('');
                    }else {
                        elm.val([value]).change();
                        $('.form-row.woocommerce-invalid .select2-selection').css(
                            {
                                "border-color": "#aaa", 
                                "border-width":"1px", 
                                "border-style":"solid"
                            }
                        );
                    }
                }
                break;
            case 'multiselect':               
                if(elm.prop('multiple')) {
                    if(typeof(value) != "undefined") {
                        elm.val(value.split(',')).val('');
                        
                    }
                }else {
                    elm.val('');
                }
                break;               
            case 'hidden':
                break;
            case 'email':
                elm.val('');
                elm.attr('autocomplete', 'off'); 
                break;
            default:
                elm.val('');
                break;
            }
            elm.closest('p').removeClass('woocommerce-validated');
            elm.closest('p').removeClass('woocommerce-invalid');
        }
        
        /*
         * get field value function.
         */
        function get_field_value(type, elm, name) {
            var value = '';
            switch(type) {
            case 'radio':
                value = $("input[type=radio][name="+name+"]:checked").val();
                break;
            case 'checkbox':
                if(elm.data('multiple') == 1) {
                    var valueArr = [];
                    $("input[type=checkbox][name='"+name+"[]']:checked").each(
                        function() {
                            valueArr.push($(this).val());
                        }
                    );
                    value = valueArr;//.toString();
                }else {
                    value = $("input[type=checkbox][name="+name+"]:checked").val();
                }
                break;
            case 'select':
                value = elm.val();
                break;
            case 'multiselect':
                value = elm.val();
                break;
            default:
                value = elm.val();
                break;
            }
            return value;
        }
        
        return {           
            display_char_count : display_char_count,
            set_field_value_by_elm : set_field_value_by_elm,
            thmaf_set_field_value_by_elm : thmaf_set_field_value_by_elm,
            get_field_value : get_field_value,
        };
    }
(window.jQuery, window, document));
(function( $ ) {
    'use strict';

    function initialize_thmaf(){
        var form_wrapper = $('.wrapper-class');
        if(form_wrapper) {                                            
        }       
    }   
    /**
     * Initialise the jquery function.
     */
    initialize_thmaf();
})( jQuery );
