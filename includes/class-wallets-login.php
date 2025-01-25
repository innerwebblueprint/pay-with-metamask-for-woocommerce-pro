<?php
if (!defined('ABSPATH')) {
    exit();
}
if (!class_exists('CPMWP_METAMASK_BUTTON')) {
    class CPMWP_METAMASK_BUTTON
    {
        use CPMWP_HELPER;

        public function __construct()
        {
            $this->add_actions();
        }

        private function add_actions()
        {   $options = $this->get_options();  
            $enabled_at=$options['enable_metamask_button'];          
            if(isset($enabled_at['enable_at_wp_admin'])&&$enabled_at['enable_at_wp_admin']=="1"){
            add_action('login_footer', array($this, 'cpmwp_metamask_logins'));  
            }  
            if(isset($enabled_at['enable_at_wc_form'])&&$enabled_at['enable_at_wc_form']=="1"){
            add_action('woocommerce_after_customer_login_form', array($this, 'cpmwp_metamask_logins'));     
            }   
            add_shortcode("cpmwp_wallet_login_button",array($this, 'cpmwp_metamask_logins'));
            if(isset($enabled_at['enable_at_wc_chk_form'])&&$enabled_at['enable_at_wc_chk_form']=="1"){
            add_action('woocommerce_after_order_notes', array($this, 'cpmwp_metamask_logins'));
            }
        }

        public function cpmwp_metamask_logins($atts)
        {
            $atrtibute = shortcode_atts(array(
                'type' => 'login',
                'size' => 'small'
            ), $atts);

            $licenseKey = get_option("PayWithMetaMaskForWooCommercePro_lic_Key");
                    
            if(isset($licenseKey) && !empty($licenseKey) && !is_user_logged_in()){
                $this->enqueue_scripts_and_styles();
                return $this->localize_scripts($atrtibute);
            }
        }

        private function enqueue_scripts_and_styles()
        {
            $filePaths = glob(CPMWP_PATH . '/assets/pay-with-metamask/build/login-widget' . '/*.php');
            $fileName = pathinfo($filePaths[0], PATHINFO_FILENAME);
            $jsbuildUrl=str_replace('.asset','',$fileName);
            wp_enqueue_style('dashicons'); 
            wp_enqueue_style('cpmwp_button_css', CPMWP_URL . 'assets/css/login-button.css', array(), CPMWP_VERSION, null, 'all'); 
            wp_enqueue_script('cpmwp_metamask_login11', CPMWP_URL . 'assets/js/wallets-login.js', array('jquery'), CPMWP_VERSION, true);
            wp_enqueue_script( 'cpmwp_metamask_login', CPMWP_URL . 'assets/pay-with-metamask/build/login-widget/'.$jsbuildUrl.'.js', array( 'wp-element' ,'cpmwp_metamask_login11'), CPMWP_VERSION, true );
        }

        private function localize_scripts($atrtibute)
        {
            $options = $this->get_options();
            $const_msg = $this->cpmwp_const_messages();
            $nonce = wp_create_nonce('cpmwp_metamask_login');

            wp_localize_script('cpmwp_metamask_login11',
            'login_data_db',
            array(              
                'enable_metamask_button' => is_array($options['enable_metamask_button']) ? array_map('esc_html', $options['enable_metamask_button']) : esc_html($options['enable_metamask_button']),
                'text_color' => esc_html($options['metamask_label_color']),
                'login_label' => esc_html($options['metamask_label']),
                'const_msg' => array_map('esc_html', $const_msg),
                "wallets_enable" => is_array($options['wallets_enable']) ? array_map('esc_html', $options['wallets_enable']) : esc_html($options['wallets_enable']),
                'register_label' => esc_html($options['metamask_register_label']),                    
                'logo_url' => esc_url($options['logo_url']),            
                'label_bg_color' => esc_html($options['metamask_label_bg_color']),
            ));

            wp_localize_script('cpmwp_metamask_login',
                'login_data',
                array(
                    'nonce' => wp_create_nonce('wp_rest'),
                    'url' => CPMWP_URL,
                    'restUrl' => get_rest_url().'pay-with-metamask/v1/',
                    'ajax' => admin_url( 'admin-ajax.php' ),
                    'sign_message' => $options['sign_message'],
                    'site_url' => $options['redirect'],
                    'enable_metamask_button' => $options['enable_metamask_button'],
                    'text_color' => $options['metamask_label_color'],
                    'login_label' => $options['metamask_label'],
                    'const_msg' => $const_msg,
                    "wallets_enable" => $options['wallets_enable'],
                    'register_label' => $options['metamask_register_label'],                    
                    'logo_url' => $options['logo_url'],
                    'checkout_page' => is_checkout(),
                    "rpc_urls" => $this->cpmwp_get_settings('rpcUrls'),
                    "projectId" => $options['infura_id'],                    
                    'label_bg_color' => $options['metamask_label_bg_color'],
                    'enable_disconnect'=>($options['enable_disconnect_btn']=="1")?true:false
                ));

            $btn_type = (isset($atrtibute['type']) && $atrtibute['type'] == "login" ) ? $options['metamask_label'] : '';
            return '<div class="cpmwp_metamask_wraper '.$atrtibute['size'].'"><span id="cpmwp_metamask_login">'.$btn_type.'</span></div>';
        }

        private function get_options()
        {
            $options = get_option( 'cpmw_settings' );
            return array(
                'metamask_label' => isset($options['mmetamask_login_label']) ? $options['mmetamask_login_label'] : "MetaMask Login",
                'metamask_register_label' => isset($options['metamask_register_label']) ? $options['metamask_register_label'] : "Register Account With MetaMask",
                'metamask_label_color' => isset($options['cpmwp_login_btn_color']) ? $options['cpmwp_login_btn_color'] : "",  
                'metamask_label_bg_color' => isset($options['cpmwp_login_btn_bg_color']) ? $options['cpmwp_login_btn_bg_color'] : "", 
                'sign_message' => isset($options['metamask_sign_message']) ? $options['metamask_sign_message'] : "",
                'enable_metamask_button' => isset($options['enable_metamask_button']) ? $options['enable_metamask_button'] : "",
                'wallets_enable' => (isset($options['supported_wallets']) && !empty($options['supported_wallets'])) ? $options['supported_wallets'] : "",
                'redirect' => (isset($options['mmetamask_redirect']) && !empty($options['mmetamask_redirect'])) ? $options['mmetamask_redirect'] : get_site_url() . '/my-account',
                'logo_url' => (isset($options['payment_gateway_logo']['url']) && !empty($options['payment_gateway_logo']['url'])) ? $options['payment_gateway_logo']['url'] : CPMWP_URL . 'assets/images/pay-with-crypto.png',
                'infura_id' => isset($options['project_id']) ? $options['project_id'] : "",
                'enable_disconnect_btn' => isset($options['enable_disconnect_btn']) ? $options['enable_disconnect_btn'] : "",
            );
        }


    }

    $login_obj = new CPMWP_METAMASK_BUTTON();
}

