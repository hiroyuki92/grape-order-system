<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    themehigh-multiple-addresses
 * @subpackage themehigh-multiple-addresses/admin
 */
if(!defined('WPINC')) { 
    die; 
}

if(!class_exists('THMAF_Admin')):
 
    /**
     * Admin class.
    */
     #[AllowDynamicProperties]
    class THMAF_Admin {
        private $plugin_name;
        private $version;

        /**
         * Initialize the class and set its properties.
         *
         * @param      string    $plugin_name       The name of this plugin.
         * @param      string    $version    The version of this plugin.
         */
        public function __construct($plugin_name, $version) {
            $this->plugin_name = $plugin_name;
            $this->version = $version;
            $this->plugin_pages = array(
                'woocommerce_page_th_multiple_addresses_free', 'user-edit.php', 'profile.php', 'post.php',
            );
            $this->define_order_page_hook();
            add_action('admin_head', array( $this, 'review_request_banner_styles' ));

            // Plugin deactivation form.
            add_action('admin_footer-plugins.php', array($this, 'thmaf_deactivation_form'));
            add_action('wp_ajax_thmaf_deactivation_reason', array($this, 'thmaf_deactivation_reason'));
        }
        function review_request_banner_styles() {
            ?>

             <style>
                .thmaf-notice-action{padding: 8px 18px;background: #fff;color: #2a7cba;border-radius: 5px; border:1px solid #2a7cba;} 
                .thmaf-notice-action .thmaf-yes { background-color: #007cba; color: #fff; }
                .thmaf-notice-action:hover:not(.thmaf-yes) { background-color: #f2f5f6; }
                .thmaf-notice-action.thmaf-yes:hover { opacity: .9; }
                .thmaf-notice-action .dashicons{ display: none; }
                .thmaf-themehigh-logo { position: absolute; right: 20px; top: calc(50% - 13px); }
                .thmaf-notice-action { background-repeat: no-repeat; padding-left: 40px; background-position: 18px 8px; }
                .thmaf-themehigh-logo {  position: absolute; right: 20px; top: calc(50% - 13px); }
                a.thmaf-notice-action.thmaf-yes { background-color: #007cba; color: #fff; }
                .thmaf-review-wrapper { padding: 15px 28px 26px 10px !important; margin-top: 35px; !important}
                .thmaf-review-image { float: left; }
                .thmaf-review-content { padding-right: 180px; }
                .thmaf-review-content h3 { margin-top: 5px; margin-bottom: 10px; }
                .thmaf-yes{ background-image: url(<?php echo THMAF_ASSETS_URL_ADMIN; ?>css/images/tick.svg); }
                .thmaf-done { background-image: url(<?php echo THMAF_ASSETS_URL_ADMIN; ?>css/images/done.svg); }
                .thmaf-remind { background-image: url(<?php echo THMAF_ASSETS_URL_ADMIN; ?>css/images/reminder.svg); }
                .thmaf-dismiss { background-image: url(<?php echo THMAF_ASSETS_URL_ADMIN; ?>css/images/close.svg); }
                .thmaf-review-content p{ padding-bottom: 14px; }
              </style>
            <?php
            }

        /**
         * Enqueue style and script.
         *
         * @param string $hook The screen id
         *
         * @return void
         */
        public function enqueue_styles_and_scripts($hook) {
            if(!in_array($hook, $this->plugin_pages)) {
                return;
            }

            $screen = get_current_screen();
            $debug_mode = apply_filters('thmaf_debug_mode', false);
            $suffix = $debug_mode ? '' : '.min';        
            $this->enqueue_styles($suffix);
            $this->enqueue_scripts($suffix);
        }
        
        /**
         * Enqueue style.
         *
         * @param string $suffix The suffix of style sheets
         *
         * @return void
         */
        public function enqueue_styles($suffix) {
            //wp_enqueue_style('woocommerce_admin_styles', THMAF_WOO_ASSETS_URL.'css/admin.css');
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_style('thmaf-admin-style', THMAF_ASSETS_URL_ADMIN . 'css/thmaf-admin'. $suffix .'.css', $this->version);
        }

        /**
         * Enqueue script.
         *
         * @param string $suffix The suffix of js file
         *
         * @return void
         */
        public function enqueue_scripts($suffix) {
            $deps = array('jquery', 'jquery-ui-dialog', 'jquery-ui-sortable', 'jquery-tiptip', 'wc-enhanced-select', 'select2', 'wp-color-picker');
            
            wp_enqueue_script('thmaf-admin-script', THMAF_ASSETS_URL_ADMIN . 'js/thmaf-admin'. $suffix .'.js', $deps, $this->version, false);
            
            $script_var = array(
                'admin_url' => admin_url(),
                'ajaxurl'   => admin_url('admin-ajax.php'),
            );
            wp_localize_script('thmaf-admin-script', 'thmaf_var', $script_var);
        }

        /**
         * Function for set capability.
         *
         *
         * @return string
         */
        public function thmaf_capability() {
            $allowed = array('manage_woocommerce', 'manage_options');
            $capability = apply_filters('thmaf_required_capability', 'manage_options');

            if(!in_array($capability, $allowed)) {
                $capability = 'manage_woocommerce';
            }
            return $capability;
        }
        
        /**
         * Function for set admin menu.
         *
         *
         * @return void
         */
        public function admin_menu() {
            $capability = $this->thmaf_capability();
            $this->screen_id = add_submenu_page('woocommerce', esc_html__('WooCommerce Multiple Addresses', 'themehigh-multiple-addresses'), esc_html__('Manage Address', 'themehigh-multiple-addresses'), $capability, 'th_multiple_addresses_free', array($this, 'output_settings'));
        }
        
        /**
         * Function for setting screen id.
         *
         * @param string $ids The unique screen id
         *
         * @return string
         */
        public function add_screen_id($ids) {
            $ids[] = 'woocommerce_page_th_multiple_addresses_free';
            $ids[] = strtolower(THMAF_i18n::__t('WooCommerce')) .'_page_th_multiple_addresses_free';

            return $ids;
        }

        /**
         * function for setting link.
         *
         * @param string $links The plugin action link
         *
         * @return void
         */
        public function plugin_action_links($links) {
            // $settings_link = '<a href="'.esc_url(admin_url('admin.php?&page=th_multiple_addresses_free')).'">'. esc_html__('Settings', 'themehigh-multiple-addresses') .'</a>';
            // array_unshift($links, $settings_link);

            $premium_link = '<a href="'.esc_url('https://www.themehigh.com/product/woocommerce-multiple-addresses-pro').'">'. __('Premium plugin', 'themehigh-multiple-addresses') .'</a>';
            $settings_link = '<a href="'.esc_url(admin_url('admin.php?&page=th_multiple_addresses_free')).'">'. __('Settings', 'themehigh-multiple-addresses') .'</a>';
            array_unshift($links, $premium_link);
            array_unshift($links, $settings_link);
            
            if (array_key_exists('deactivate', $links)) {
                $links['deactivate'] = str_replace('<a', '<a class="thmaf-deactivate-link"', $links['deactivate']);
            }

            return $links;
        }
        
        /**
         * Function for premium version notice.
         *
         *
         * @return void
         */
        private function _output_premium_version_notice() { ?>
            <div id="message" class="wc-connect updated thpladmin-notice thmaf-admin-notice">
                <div class="squeezer">
                    <table>
                        <tr>
                            <td width="70%">
                                <!-- <p><strong><i>WooCommerce Multiple addresses Pro</i></strong> premium version provides more features to setup your checkout page and cart page.</p> -->
                                <p>
                                    <strong><i><a href="<?php echo esc_url('https://www.themehigh.com/product/woocommerce-multiple-addresses-pro/'); ?>">
                                        <?php echo esc_html__('WooCommerce Multiple addresses', 'themehigh-multiple-addresses');?>

                                    </a></i></strong><?php echo esc_html__('premium version provides more features to manage the users addresses', 'themehigh-multiple-addresses'); ?>
                                    <ul>
                                    <li>
                                    <?php echo esc_html__('Let Your Shoppers Choose from a List of Saved Addresses', 'themehigh-multiple-addresses'); ?>
                                    </li>
                                    <li>
                                    <?php echo esc_html__('Manage All Addresses from My Account Page', 'themehigh-multiple-addresses'); ?>
                                        
                                    </li>
                                    <li>
                                        <?php echo esc_html__('Save Time with Google Autocomplete Feature', 'themehigh-multiple-addresses'); ?>
                                    </li>
                                    <li>
                                        <?php echo esc_html__('Custom Address Formats through Overriding', 'themehigh-multiple-addresses'); ?>
                                    </li>
                                    <li>
                                        <?php echo esc_html__('Display Your Multiple Address Layouts in Style', 'themehigh-multiple-addresses'); ?> 
                                    </li>
                                    <li>
                                        <?php echo esc_html__('Highly Compatible with', 'themehigh-multiple-addresses'); ?> 
                                            <strong><i><a href="<?php echo esc_url('https://www.themehigh.com/product/woocommerce-checkout-field-editor-pro/'); ?>">
                                                <?php echo esc_html__('WooCommerce Checkout Field Editor', 'themehigh-multiple-addresses'); ?>
                                            </a></i></li>
                                    </ul>
                            </p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        <?php }

        /**
         * function for output settings.
         *
         * @return void
         */
        public function output_settings() {
            //$this->_output_premium_version_notice();
            $tab  = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general_settings';
            if($tab ==='general_settings') {
                $general_settings = THMAF_Admin_Settings_General::instance();   
                $general_settings->render_page();
            } else if($tab ==='pro') {
                $general_settings = THMAF_Admin_Settings_Pro::instance();   
                $general_settings->render_page();
            }
        }

        /**
         * Function for define order page hook.
         */
        public function define_order_page_hook() {
            add_action( 'admin_init', array( $this, 'thmaf_notice_actions' ), 20 );
            add_action( 'admin_notices', array($this, 'output_review_request_link'));
            add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'thwma_update_woo_order_status'),10,1);
        }

        /**
         * Function for update woocommerce order status.
         * 
         * @param array $order The order details
         */
        function thwma_update_woo_order_status($order) {
            $settings = THMAF_Utils::get_setting_value('settings_multiple_shipping');
            $enable_multi_shipping = isset($settings['enable_multi_shipping']) ? $settings['enable_multi_shipping']:'';
            $user_id = get_current_user_id();
            if($enable_multi_shipping == 'yes') {
                $enable_multi_ship_data = '';
                if (is_user_logged_in()) {
                    $enable_multi_ship_data = get_user_meta($user_id, THMAF_Utils::USER_META_ENABLE_MULTI_SHIP, true);
                }

                if($enable_multi_ship_data == 'yes') {
                    echo '<input type="hidden" name="multi_ship_enabled" value="yes" class="multi_ship_enabled">';
                } else {
                    echo '<input type="hidden" name="multi_ship_enabled" value="" class="multi_ship_enabled">';
                }
            } else {
                echo '<input type="hidden" name="multi_ship_enabled" value="" class="multi_ship_enabled">';
            }
        }



        function thmaf_notice_actions(){
            if( !(isset($_GET['thmaf_remind']) || isset($_GET['thmaf_dissmis']) || isset($_GET['thmaf_reviewed'])) ) {
                return;
            }

            $nonse = isset($_GET['thmaf_review_nonce']) ? $_GET['thmaf_review_nonce'] : false;

            if(!wp_verify_nonce($nonse, 'thmaf_notice_security')){
                die();
            }

            $now = time();

            $thmaf_remind = isset($_GET['thmaf_remind']) ? sanitize_text_field( wp_unslash($_GET['thmaf_remind'])) : false;
            if($thmaf_remind){
                update_user_meta( get_current_user_id(), 'thmaf_review_skipped', true );
                update_user_meta( get_current_user_id(), 'thmaf_review_skipped_time', $now );
            }

            $thmaf_dissmis = isset($_GET['thmaf_dissmis']) ? sanitize_text_field( wp_unslash($_GET['thmaf_dissmis'])) : false;
            if($thmaf_dissmis){
                update_user_meta( get_current_user_id(), 'thmaf_review_dismissed', true );
                update_user_meta( get_current_user_id(), 'thmaf_review_dismissed_time', $now );
            }

            $thmaf_reviewed = isset($_GET['thmaf_reviewed']) ? sanitize_text_field( wp_unslash($_GET['thmaf_reviewed'])) : false;
            if($thmaf_reviewed){
                update_user_meta( get_current_user_id(), 'thmaf_reviewed', true );
                update_user_meta( get_current_user_id(), 'thmaf_reviewed_time', $now );
            }
        }


        function output_review_request_link(){
            if(!apply_filters('thmaf_show_dismissable_admin_notice', true)){
                return;
            }

            if ( !current_user_can( 'manage_options' ) ) {
                return;
            }

            $thmaf_reviewed = get_user_meta( get_current_user_id(), 'thmaf_reviewed', true );
            if($thmaf_reviewed){
                return;
            }

            $now = time();
            $dismiss_life  = apply_filters('thmaf_dismissed_review_request_notice_lifespan', 3 * MONTH_IN_SECONDS);
            $reminder_life = apply_filters('thmaf_skip_review_request_notice_lifespan', 1 * DAY_IN_SECONDS);

            $is_dismissed   = get_user_meta( get_current_user_id(), 'thmaf_review_dismissed_', true );
            $dismisal_time  = get_user_meta( get_current_user_id(), 'thmaf_review_dismissed_time', true );
            $dismisal_time  = $dismisal_time ? $dismisal_time : 0;
            $dismissed_time = $now - $dismisal_time;
            if( $is_dismissed && ($dismissed_time < $dismiss_life) ){
                return;

            }

            $is_skipped = get_user_meta( get_current_user_id(), 'thmaf_review_skipped', true );
            $skipping_time = get_user_meta( get_current_user_id(), 'thmaf_review_skipped_time', true );
            $skipping_time = $skipping_time ? $skipping_time : 0;
            $remind_time = $now - $skipping_time;

            if($is_skipped && ($remind_time < $reminder_life) ){
                return;
            }

            $thmaf_since = get_option('thmaf_since');

            if(!$thmaf_since){
                $now = time();
                update_option('thmaf_since', $now, 'no' );
            }

            $thmaf_since = $thmaf_since ? $thmaf_since : $now;
            $show_notice_time  = 7 * DAY_IN_SECONDS;

            $value_period = $thmaf_since + $show_notice_time;

            if ($value_period > $now) {
                return ;
            }

            $this->render_review_request_notice();
        }

        /**
         * Function for review request notice.
         * 
         */
        function render_review_request_notice(){
            $remind_url   = add_query_arg(array('thmaf_remind' => true , 'thmaf_review_nonce' => wp_create_nonce('thmaf_notice_security')));
            $dismiss_url  = add_query_arg(array('thmaf_dissmis' => true, 'thmaf_review_nonce' => wp_create_nonce( 'thmaf_notice_security')));
            $reviewed_url = add_query_arg(array('thmaf_reviewed' => true , 'thmaf_review_nonce' => wp_create_nonce( 'thmaf_notice_security')));
            ?>

            <div class="notice notice-info thpladmin-notice is-dismissible thmaf-review-wrapper" data-nonce="<?php echo wp_create_nonce( 'thmaf_notice_security'); ?>">
                <div class="thmaf-review-image">
                    <img src="<?php echo esc_url(THMAF_URL .'admin/assets/css/images/review-left.png'); ?>" alt="themehigh">
                </div>
                <div class="thmaf-review-content">
                    <h3><?php _e('Tell us what you loved', 'themehigh-multiple-addresses'); ?></h3>
                    <p><?php _e('We are waiting to know your experience using the plugin Multiple Shipping Address for Woocommerce. Tell us what you loved about the latest improvements. Also, drop in your suggestions, review and help us grow better.', 'themehigh-multiple-addresses'); ?></p>
                    <div class="action-row">
                        <a class="thmaf-notice-action thmaf-yes" onclick="window.open('https://wordpress.org/plugins/themehigh-multiple-addresses/#reviews', '_blank')" style="margin-right:16px; text-decoration: none">
                            <?php _e("Ok, You deserve it", 'themehigh-multiple-addresses'); ?>
                        </a>

                        <a class="thmaf-notice-action thmaf-done" href="<?php echo esc_url($reviewed_url); ?>" style="margin-right:16px; text-decoration: none">
                            <?php _e('Already, Did', 'themehigh-multiple-addresses'); ?>
                        </a>

                        <a class="thmaf-notice-action thmaf-remind" href="<?php echo esc_url($remind_url); ?>" style="margin-right:16px; text-decoration: none">
                            <?php _e('Maybe later', 'themehigh-multiple-addresses'); ?>
                        </a>

                        <a class="thmaf-notice-action thmaf-dismiss" href="<?php echo esc_url($dismiss_url); ?>" style="margin-right:16px; text-decoration: none">
                            <?php _e("Nah, Never", 'themehigh-multiple-addresses'); ?>
                        </a>
                    </div>
                </div>
                <div class="thmaf-themehigh-logo">
                    <span class="logo" style="float: right">
                        <a target="_blank" href="https://www.themehigh.com">
                            <img src="<?php echo esc_url(THMAF_URL .'admin/assets/css/images/logo.svg'); ?>" style="height:19px;margin-top:4px;" alt="themehigh"/>
                        </a>
                    </span>
                </div>
            </div>

            <?php
        }

        /**
         * Get admin Url.
         *
         * @param string $tab the current tab name
         * @param string $section the current section name
         *
         * @return admin url
         */
        public function get_admin_url($tab = false, $section = false) {
            $url = 'admin.php?&page=th_multiple_addresses_free';
            if($tab && !empty($tab)) {
                $url .= '&tab='. $tab;
            }
            if($section && !empty($section)) {
                $url .= '&section='. $section;
            }
            return admin_url($url);
        }




        /**
         * function for displaying the deactivation form.
         *
         * @return 
         */

        public function thmaf_deactivation_form(){
            $is_snooze_time = get_user_meta( get_current_user_id(), 'thmaf_deactivation_snooze', true );
            $now = time();

            if($is_snooze_time && ($now < $is_snooze_time)){
                return;
            }

            $deactivation_reasons = $this->get_deactivation_reasons();
            ?>
            <div id="thmaf_deactivation_form" class="thpladmin-modal-mask">
                 <div class="thpladmin-modal">
                    <div class="modal-container">
                        <div class="modal-content">
                            <div class="modal-body">
                                <div class="model-header">
                                    <img class="th-logo" src="<?php echo esc_url(THMAF_URL .'admin/assets/css/images/themehigh.svg'); ?>" alt="themehigh-logo">
                                    <span><?php echo __('Quick Feedback', 'themehigh-multiple-addresses'); ?></span>
                                </div>

                                <main class="form-container main-full">
                                    <p class="thmaf-title-text"><?php echo __('If you have a moment, please let us know why you want to deactivate this plugin', 'themehigh-multiple-addresses'); ?></p>
                                    <ul class="deactivation-reason" data-nonce="<?php echo wp_create_nonce('thmaf_deactivate_nonce'); ?>">
                                        <?php 
                                        if($deactivation_reasons){
                                            foreach($deactivation_reasons as $key => $reason){
                                                $reason_type = isset($reason['reason_type']) ? $reason['reason_type'] : '';
                                                $reason_placeholder = isset($reason['reason_placeholder']) ? $reason['reason_placeholder'] : '';
                                                ?>
                                                <li data-type="<?php echo esc_attr($reason_type); ?>" data-placeholder="<?php echo esc_attr($reason_placeholder); ?> ">
                                                    <label>
                                                        <input type="radio" name="selected-reason" value="<?php echo esc_attr($key); ?>">
                                                        <span><?php echo esc_html($reason['radio_label']); ?></span>
                                                    </label>
                                                </li>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </ul>
                                    <p class="thmaf-privacy-cnt">
                                        <?php echo __('This form is only for getting your valuable feedback. We do not collect your personal data. To know more read our ', 'themehigh-multiple-addresses'); ?> 
                                        <a class="thmaf-privacy-link" target="_blank" href="<?php echo esc_url('https://www.themehigh.com/privacy-policy/');?>"><?php echo __('Privacy Policy', 'themehigh-multiple-addresses'); ?>
                                        </a>
                                    </p>

                                </main>
                                <footer class="modal-footer">
                                    <div class="thmaf-left">
                                        <a class="thmaf-link thmaf-left-link thmaf-deactivate" href="#"><?php echo __('Skip & Deactivate', 'themehigh-multiple-addresses'); ?></a>
                                    </div>
                                    <div class="thmaf-right">
                                        <a class="thmaf-link thmaf-right-link thmaf-active" target="_blank" href="https://help.themehigh.com/hc/en-us/requests/new"><?php echo __('Get Support', 'themehigh-multiple-addresses'); ?></a>
                                        <a class="thmaf-link thmaf-right-link thmaf-active thmaf-submit-deactivate" href="#"><?php echo __('Submit and Deactivate', 'themehigh-multiple-addresses'); ?></a>
                                        <a class="thmaf-link thmaf-right-link thmaf-close" href="#"><?php echo __('Cancel', 'themehigh-multiple-addresses'); ?></a>
                                    </div>
                                </footer>

                            </div>
                        </div>
                    </div>
                 </div>
            </div>

            <style type="text/css">
                .thpladmin-modal-mask{
                    position: fixed;
                    background-color: rgba(17,30,60,0.6);
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 9999;
                    overflow: scroll;
                    transition: opacity 250ms ease-in-out;
                    display: none;
                }
                .thpladmin-modal .modal-container{
                    position: absolute;
                    background: #fff;
                    border-radius: 2px;
                    overflow: hidden;
                    left: 50%;
                    top: 50%;
                    transform: translate(-50%,-50%);
                    width: 50%;
                    max-width: 960px;
                    /*min-height: 560px;*/
                    /*height: 70vh;*/
                    /*max-height: 640px;*/
                    animation: appear-down 250ms ease-in-out;
                    border-radius: 15px;
                }
                .modal-content{

                    max-width: 960px;
                    /*height: 80vh;*/
                    max-height: 640px;
                    /* min-height: 560px; */
                    position: relative;
                }
                .model-header {
                    padding: 21px;
                }
                .thpladmin-modal .model-header span {
                    font-size: 18px;
                    font-weight: bold;
                }
                .thpladmin-modal .model-header {
                    padding: 21px;
                    background: #ECECEC;
                }
                .thpladmin-modal .form-container {
                    margin-left: 23px;
                    clear: both;
                }
                .thmaf-title-text {
                    color: #2F2F2F;
                    font-weight: 500;
                    font-size: 15px;
                }
                .thpladmin-modal .thmaf-privacy-cnt {
                    color: #919191;
                    font-size: 12px;
                    margin-bottom: 31px;
                    margin-top: 18px;
                    max-width: 75%;
                }
                .thpladmin-modal .deactivation-reason li {
                    margin-bottom: 17px;
                }
                .thmaf-privacy-link {
                    font-style: italic;
                }
                .thpladmin-modal .modal-footer {
                    padding: 20px;
                    border-top: 1px solid #E7E7E7;
                    float: left;
                    width: 100%;
                    box-sizing: border-box;
                }
                .thmaf-left {
                    float: left;
                }
                .thmaf-link {
                    line-height: 31px;
                    font-size: 12px;
                }
                .thmaf-left-link {
                    font-style: italic;
                }
                .thmaf-right {
                    float: right;
                }
                .thmaf-right-link {
                    padding: 0px 20px;
                    border: 1px solid;
                    display: inline-block;
                    text-decoration: none;
                    border-radius: 5px;
                }
                .thmaf-right-link.thmaf-active {
                    background: #0773AC;
                    color: #fff;
                }
                .reason-input {
                    margin-left: 31px;
                    margin-top: 11px;
                    width: 70%;
                }
                .reason-input input {
                    width: 100%;
                    height: 40px;
                }
                .reason-input textarea {
                    width: 100%;
                    min-height: 80px;
                }

            </style>
             <script type="text/javascript">
                 (function($){
                    
                    var popup = $("#thmaf_deactivation_form");
                    var deactivation_link = '';

                    $('.thmaf-deactivate-link').on('click', function(e){
                        e.preventDefault();
                        deactivation_link = $(this).attr('href');
                        popup.css("display", "block");
                        popup.find('a.thmaf-deactivate').attr('href', deactivation_link);
                    })

                    popup.on('click', '.thmaf-close', function () {
                        popup.css("display", "none");
                    });

                    popup.on('click', 'input[type="radio"]', function () {
                        var parent = $(this).parents('li:first');
                        popup.find('.reason-input').remove();

                        var type = parent.data('type');
                        var placeholder = parent.data('placeholder');

                        var reason_input = '';

                        if('text' == type){
                            reason_input += '<div class="reason-input">';
                            reason_input += '<input type="text" placeholder="'+ placeholder +'">';
                            reason_input += '</div>';
                        }else if('textarea' == type){
                            reason_input += '<div class="reason-input">';
                            reason_input += '<textarea row="5" placeholder="'+ placeholder +'">';
                            reason_input += '</textarea>';
                            reason_input += '</div>';
                        }else if('checkbox' == type){
                            reason_input += '<div class="reason-input ">';
                            reason_input += '<input type="checkbox" id="th-snooze" name="th-snooze" class="th-snooze-checkbox">';
                            reason_input += '<label for="th-snooze">Snooze this panel while troubleshooting</label>';
                            reason_input += '<select name="th-snooze-time" class="th-snooze-select" disabled>';
                            reason_input += '<option value="<?php echo HOUR_IN_SECONDS ?>">1 Hour</option>';
                            reason_input += '<option value="<?php echo 12*HOUR_IN_SECONDS ?>">12 Hour</option>';
                            reason_input += '<option value="<?php echo DAY_IN_SECONDS ?>">24 Hour</option>';
                            reason_input += '<option value="<?php echo WEEK_IN_SECONDS ?>">1 Week</option>';
                            reason_input += '<option value="<?php echo MONTH_IN_SECONDS ?>">1 Month</option>';
                            reason_input += '</select>';
                            reason_input += '</div>';
                        }else if('reviewlink' == type){
                            reason_input += '<div class="reason-input thmaf-review-link">';
                            reason_input += '<input type="hidden" value="<?php _e('Upgraded', 'themehigh-multiple-addresses');?>">';
                            reason_input += '</div>';
                        }

                        if(reason_input !== ''){
                            parent.append($(reason_input));
                        }
                    })

                    popup.on('click', '.thmaf-submit-deactivate', function (e) {
                        e.preventDefault();
                        var button = $(this);
                        if (button.hasClass('disabled')) {
                            return;
                        }
                        var radio = $('.deactivation-reason input[type="radio"]:checked');
                        var parent_li = radio.parents('li:first');
                        var parent_ul = radio.parents('ul:first');
                        var input = parent_li.find('textarea, input[type="text"], input[type="hidden"]');
                        var maf_deacive_nonce = parent_ul.data('nonce');

                        $.ajax({
                            url : ajaxurl,
                            type : 'POST',
                            data : {
                                action : 'thmaf_deactivation_reason',
                                reason: (0 === radio.length) ? 'none' : radio.val(),
                                comments: (0 !== input.length) ? input.val().trim() : '',
                                security: maf_deacive_nonce,
                            },
                            beforeSend: function () {
                                button.addClass('disabled');
                                button.text('Processing...');
                            },
                            complete: function () {
                                window.location.href = deactivation_link;
                            }
                        });
                    })

                    popup.on('click', '#th-snooze', function () {
                        if($(this).is(':checked')){
                            popup.find('.th-snooze-select').prop("disabled", false);
                        }else{
                            popup.find('.th-snooze-select').prop("disabled", true);
                        }
                    });

                    }(jQuery)
                 )
             </script>
            <?php
        



        }

        /**
         * reasons of deactivation.
         *
         * @return array the reasons.
         */
        private function get_deactivation_reasons(){
            return array(

                'upgraded_to_pro' => array(
                    'radio_val'          => 'upgraded_to_pro',
                    'radio_label'        => __('Upgraded to premium.', 'themehigh-multiple-addresses'),
                    'reason_type'        => 'reviewlink',
                    'reason_placeholder' => '',
                ),

                'feature_missing'=> array(
                    'radio_val'          => 'feature_missing',
                    'radio_label'        => __('A specific feature is missing', 'themehigh-multiple-addresses'),
                    'reason_type'        => 'text',
                    'reason_placeholder' => __('Type in the feature', 'themehigh-multiple-addresses'),
                ),

                'error_or_not_working'=> array(
                    'radio_val'          => 'error_or_not_working',
                    'radio_label'        => __('Found an error in the plugin/ Plugin was not working', 'themehigh-multiple-addresses'),
                    'reason_type'        => 'text',
                    'reason_placeholder' => __('Specify the issue', 'themehigh-multiple-addresses'),
                ),

                'found_better_plugin' => array(
                    'radio_val'          => 'found_better_plugin',
                    'radio_label'        => __('I found a better Plugin', 'themehigh-multiple-addresses'),
                    'reason_type'        => 'text',
                    'reason_placeholder' => __('Could you please mention the plugin?', 'themehigh-multiple-addresses'),
                ),

                'hard_to_use' => array(
                    'radio_val'          => 'hard_to_use',
                    'radio_label'        => __('It was hard to use', 'themehigh-multiple-addresses'),
                    'reason_type'        => 'text',
                    'reason_placeholder' => __('How can we improve your experience?', 'themehigh-multiple-addresses'),
                ),

                'temporary' => array(
                    'radio_val'          => 'temporary',
                    'radio_label'        => __('It’s a temporary deactivation - I’m troubleshooting an issue', 'themehigh-multiple-addresses'),
                    'reason_type'        => 'checkbox',
                    'reason_placeholder' => __('Could you please mention the plugin?', 'themehigh-multiple-addresses'),
                ),

                'other' => array(
                    'radio_val'          => 'other',
                    'radio_label'        => __('Not mentioned here', 'themehigh-multiple-addresses'),
                    'reason_type'        => 'textarea',
                    'reason_placeholder' => __('Kindly tell us your reason, so that we can improve', 'themehigh-multiple-addresses'),
                ),
            );
        }

        public function thmaf_deactivation_reason(){
            global $wpdb;

            check_ajax_referer('thmaf_deactivate_nonce', 'security');

            if(!isset($_POST['reason'])){
                return;
            }

            if($_POST['reason'] === 'temporary'){
                $snooze_period = isset($_POST['th-snooze-time']) && $_POST['th-snooze-time'] ? $_POST['th-snooze-time'] : MINUTE_IN_SECONDS ;
                $time_now = time();
                $snooze_time = $time_now + $snooze_period;

                update_user_meta(get_current_user_id(), 'thmaf_deactivation_snooze', $snooze_time);

                return;
            }

            $data = array(
                'plugin'        => 'thmaf',
                'reason'        => sanitize_text_field($_POST['reason']),
                'comments'      => isset($_POST['comments']) ? sanitize_textarea_field(wp_unslash($_POST['comments'])) : '',
                'date'          => gmdate("M d, Y h:i:s A"),
                'software'      => $_SERVER['SERVER_SOFTWARE'],
                'php_version'   => phpversion(),
                'mysql_version' => $wpdb->db_version(),
                'wp_version'    => get_bloginfo('version'),
                'wc_version'    => (!defined('WC_VERSION')) ? '' : WC_VERSION,
                'locale'        => get_locale(),
                'multisite'     => is_multisite() ? 'Yes' : 'No',
                'plugin_version'=> THMAF_VERSION
            );

            $response = wp_remote_post('https://feedback.themehigh.in/api/add_feedbacks', array(
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => false,
                'headers'     => array( 'Content-Type' => 'application/json' ),
                'body'        => json_encode($data),
                'cookies'     => array()
                    )
            );

            wp_send_json_success();
        }

        
    }
endif;