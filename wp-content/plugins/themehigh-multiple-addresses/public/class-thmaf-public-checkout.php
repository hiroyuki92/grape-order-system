<?php
/**
 * The public-facing functionality of the plugin - Checkout page.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    themehigh-multiple-addresses
 * @subpackage themehigh-multiple-addresses/public
 */

if(!defined('WPINC')) { 
    die; 
}

if(!class_exists('THMAF_Public_Checkout')) :
    
    class THMAF_Public_Checkout extends THMAF_Public {

        public function __construct() {
            add_action('after_setup_theme', array($this, 'define_public_hooks'));
        }


        /**
         * Function for define public hooks.
         *
         * @return void
         */
        public function define_public_hooks(){

            // Add new shipping address.
            add_action('wp_ajax_add_new_shipping_address', array($this, 'add_new_shipping_address'));
            add_action('wp_ajax_nopriv_add_new_shipping_address', array($this, 'add_new_shipping_address'));

			add_action('wp_ajax_thmaf_save_address', array($this, 'thmaf_save_address'),999);
	    	add_action('wp_ajax_nopriv_thmaf_save_address', array($this, 'thmaf_save_address'),999);
        }


        /**
		 * Function for add new shipping addresses on multi-shipping section(checkout page).
         * Function for add new shipping address (cart page - ajax response).
         */
        public function add_new_shipping_address() {
            check_ajax_referer( 'add-new-shipping-address', 'security' );

            $country      = get_user_meta(get_current_user_id(), 'shipping_country', true);
            $address = WC()->countries->get_address_fields($country, 'shipping_');

            if(!empty($address) && is_array($address)) {
                foreach ($address as $key => $field) {
                	$address[ $key ]['value'] = '';
                }
            }
            ?>
			<div class="thmaf-cart-modal-content2" id="thmaf-cart-modal-content2">
				<div class="thmaf-cart-modal-title-bar2" >
					<span class="thmaf-cart-modal-close2" onclick="thmaf_close_cart_add_adr_modal(event);">&times;</span>
				</div>
				<div class="thmaf_hidden_error_mssgs"></div>
				<div id="cart_shipping_form_wrap">
				<form method="post" id="cart_shipping_form" name="thmaf_cart_ship_form_action">
					<?php wp_create_nonce('thmaf_cart_ship_form_action') ?>
					<?php echo '<input type="hidden" name="cart_ship_form_action" id="cart_ship_form_action" value="' . wp_create_nonce( 'thmaf_cart_ship_form_action' ) . '" />'; ?>
					<div>
						<?php if(!empty($address) && is_array($address)) {
							foreach ($address as $key => $field) {
								woocommerce_form_field($key, $field, wc_get_post_data_by_key($key, $field['value']));
							}
						}
						do_action("woocommerce_after_edit_address_form_shipping");?>
					</div>
					<p>
						<button id="thmaf_save_address_cart" type="submit" class="button form-row-odd" name="thmaf_save_address" onclick="thmaf_cart_save_address(event);" value="<?php esc_attr_e('Save address', 'woocommerce'); ?>"><?php esc_html_e('Save address', 'woocommerce'); ?></button>
					</p>
				</form>
				</div>
			</div>
			<?php exit();
        }


		/**
		 * Save new shipping address on multi-shipping section(checkout page).
		 * Save new shipping address from cart page(cart page).
		 *
		 * @return void
		 */
        public static function thmaf_save_address() {
        	// Start with cleaning any existing output
    		ob_start();

			check_ajax_referer( 'thmaf_cart_ship_form_action', 'security');

            $error_messgs = '';
			$user_id = get_current_user_id();
			$load_address = sanitize_key('shipping');
            
            $cart_shipping = isset($_POST['cart_shipping']) ? $_POST['cart_shipping'] : '';
            $cart_shipping_data = self::thmaf_unserialize_form($cart_shipping);

			$country = isset($cart_shipping_data['shipping_country']) ? $cart_shipping_data['shipping_country'] : '';
			$address = WC()->countries->get_address_fields(wc_clean(wp_unslash($country)), $load_address . '_');
			
			$address_data = array();
			$address_new = array();
			
			if(!empty($address) && is_array($address)){
				foreach ($address as $key => $field) {
					if(!empty($cart_shipping_data) && is_array($cart_shipping_data)){
						$address_data[$key] = array(
							'label' 		=> isset($field['label']) ? $field['label'] : '',
							'value' 		=> isset($cart_shipping_data[$key]) ? $cart_shipping_data[$key] : '',
							'required' 		=> isset($field['required']) ? $field['required'] : '',
							'type' 			=> isset($field['type']) ? $field['type'] : '',
							'validate' 		=> isset($field['validate']) ? $field['validate'] : ''
						);
						$address_new[$key] = isset($cart_shipping_data[$key]) ? $cart_shipping_data[$key] : '';
					}
				}
			}
			// Validate the form.
			$true_check = self::validate_cart_shipping_addr_data($address_data, $cart_shipping_data);
            $true_check_val = '';
			if($true_check == 'true') {
				THMAF_Utils::save_address_to_user($user_id, $address_new, 'shipping');
				$true_check_val = 'true';
			} else {
				$true_check_val = $true_check;
			}

			// Create new dropdown list of newly added address.
			$adr_key = '';
			$custom_address = THMAF_Utils::get_custom_addresses($user_id,'shipping');
			if(!empty($custom_address) && is_array($custom_address)) {
				foreach ($custom_address as $a_key => $a_value) {
					$adr_key = $a_key;
				}
			}
			$address_dropdown = '';
			$opt_key = $adr_key;
			$value = '';
			
			$adrsvalues_to_dd = array();
			
			if(!empty($cart_shipping_data) && is_array($cart_shipping_data)) {
				foreach ($cart_shipping_data as $adrs_key => $adrs_value) {
					if($adrs_key == 'shipping_address_1' || $adrs_key =='shipping_address_2' || $adrs_key =='shipping_city' || $adrs_key =='shipping_state' || $adrs_key =='shipping_postcode') {
						if($adrs_value) {
							$adrsvalues_to_dd[] = $adrs_value;
						}
					}
				}
			}
			$new_adrs_string = implode(', ', $adrsvalues_to_dd);
			if($true_check_val == 'true') {
				$address_dropdown .= '<option value="' . esc_attr($opt_key) . '" ' . selected($value, $opt_key, false) . ' >' . esc_attr($new_adrs_string) . '</option>';
			}
			$output_table = self::multiple_address_management_form();

			// Create new tile of shipping address.
			if($true_check_val == 'true') {
				$output_shipping = THMAF_Public::get_tile_field($user_id, 'shipping');
			} else {
				$output_shipping = '';
			}
			
			$address_count = '';
			$custom_addresses = get_user_meta($user_id, THMAF_Utils::ADDRESS_KEY, true);
			if($custom_addresses && isset($custom_addresses['shipping']) &&  !empty($custom_addresses['shipping'])){
                $address_count = count($custom_addresses['shipping']);
            }
			
			$response = array(
				'result_shipping' => $output_shipping,
				'true_check' => $true_check_val,
				'address_dropdown' => $address_dropdown,
				'output_table' => $output_table,
				'address_count' => $address_count,
			);

			// Clean all output buffers (in case any unwanted output was generated)
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Set headers for the JSON response
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, no-store, must-revalidate');

            wp_send_json($response); // Send the JSON response.

			exit();
        }
		
		/**
		 * Function for validate multi shipping address form address data(checkout page).
         * Function for validate cart shipping address form address data(cart page).
         *
         * @param array $address_data The address datas
		 * @param array $sipping_data The cart shipping details
         * 
         *
         * @return string.
         */

		public static function validate_cart_shipping_addr_data($address_data, $sipping_data) {
			$true_check = array();
			$error_check = '';

			if(!empty($address_data) && is_array($address_data)) {
				foreach($address_data as $dkey => $dvalue) {
					$value = $dvalue['value'];
					$required = $dvalue['required'];
					$ftype = $dvalue['type'];
					$validate = $dvalue['validate'];
				
					// check is field is required and value is empty.
					if (! empty($required) && empty($value)) {
						$error_check .= esc_html__($dvalue['label'].' is a required field.', 'woocommerce');
						$error_check .= '</br>';
						$true_check[] = false;
					} else {
						$true_check[] = true;
					}

					// Validation and formatting rules ( postcode, state ).
					if (!empty($value)) {
						if (! empty($validate) && is_array($validate)) {
							foreach ($validate as $rule) {
								if ($rule == 'postcode') {
									$country = isset($sipping_data['shipping_country']) ? $sipping_data['shipping_country'] : '';
									
									if ('' == $country) {
										$error_check .= esc_html__('Please enter a valid country', 'woocommerce');
										$error_check .= '</br>';
										$true_check[] = false;
									} else {
										$true_check[] = true;
									}

									$value   = wc_format_postcode($value, $country);
									if ('' !== $value && ! WC_Validation::is_postcode($value, $country)) {
										switch ($country) {
											case 'IE':
												$postcode_validation_notice = esc_html__('Please enter a valid Eircode.', 'woocommerce');
												break;
											default:
												$postcode_validation_notice = esc_html__('Please enter a valid postcode / ZIP.', 'woocommerce');
										}
										$error_check .= $postcode_validation_notice;
										$true_check[] = false;
									} else {
										$true_check[] = true;
									}
								}
							}
						}
					}
				}
				
			}else {
				$error_check .=  esc_html__('Your address fields are empty', 'woocommerce');
				$true_check[] = false;
			}
			$true_chk = array_unique($true_check);
			if (in_array(false, $true_chk)) {
				return $error_check;
			} else {
				return 'true';
			}
		}
		
		/**
		 * Function for unserialize address.
		 * 
         * @param string $str The given serialise address data
         *
         * @return array.
		 */
		public static function thmaf_unserialize_form($str) {
		    $returndata = array();
		    $strArray = explode("&", $str);
		    $i = 0;
		    if(!empty($strArray) && is_array($strArray)) {
			    foreach ($strArray as $item) {
			        $array = explode("=", $item);
			        $returndata[$array[0]] = urldecode($array[1]);
			    }
			}
		    return $returndata;
		}

    }
    
endif;
