<?php defined('ABSPATH') || exit;

if (class_exists('CSF_Setup')):
    class CPMWP_CSF extends CSF_Setup
{
    }
    $prefix = 'cpmw_settings';

    CPMWP_CSF::createOptions(
        $prefix,
        array(
            'framework_title' => esc_html__('Settings', 'cpmwp'),
            'menu_title' => false,
            'menu_slug' => 'cpmw-metamask-settings',
            'menu_capability' => 'manage_options',
            'menu_type' => 'submenu',
            'menu_parent' => 'woocommerce',
            'menu_position' => 103,
            'menu_hidden' => true,
            'nav' => 'inline',
            'show_bar_menu' => false,
            'show_sub_menu' => false,
            'show_reset_section' => false,
            'show_reset_all' => false,
            'footer_text' => '',
            'theme' => 'light',

        )
    );

    CPMWP_CSF::createSection(
        $prefix,
        array(

            'id' => 'general_options',
            'title' => esc_html__('General Options', 'cpmwp'),
            'icon' => 'fa fa-cog',
            'fields' => array(

                array(
                    'id' => 'user_wallet',
                    'title' => __('Payment Address <span style="color:red">(Required)</span>', 'cpmwp'),
                    'type' => 'text',
                    'placeholder' => '0x1dCXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                    'validate' => 'csf_validate_required',
                    'help' => esc_html__('Enter your default wallet address to receive crypto payments.', 'cpmwp'),
                    'desc' => esc_html__('Enter your default wallet address to receive crypto payments.', 'cpmwp'),
                ),
                array(
                    'id' => 'project_id',
                    'title' => __('WalletConnect Project ID <span style="color:red">(Required)</span>', 'cpmwp'),
                    'type' => 'text',
                    'help' => esc_html__('Please create Project on Wallet connect and enter here project ID. ', 'cpmwp'),
                    'desc' => 'Check -<a href="https://paywithcryptocurrency.net/get-walletconnect-project-id/" target="_blank"> How to retrieve your WalletConnect project id?</a>.',
                ),
                array(
                    'id' => 'openexchangerates_key',
                    'title' => __('Openexchangerates API Key', 'cpmwp'),
                    'type' => 'text',
                    'help' => esc_html__('Please be sure to provide the API key if your store currency is other than USD, as it is required for proper functionality.', 'cpmwp'),
                    // 'dependency' => array('currency_conversion_api', '==', 'openexchangerates'),
                    'desc' => 'Please provide the API key if you are utilizing a store currency other than USD. Check -<a href="https://paywithcryptocurrency.net/get-openexchangerates-free-api-key/" target="_blank">How to retrieve openexchangerates free api key?</a>',

                ),
                array(
                    'id' => 'currency_conversion_api',
                    'title' => esc_html__('Crypto Price API', 'cpmwp'),
                    'type' => 'select',
                    'options' => array(
                        'cryptocompare' => __('CryptoCompare', 'cpmwp'),
                        'openexchangerates' => __('Binance', 'cpmwp'),
                    ),
                    'default' => 'openexchangerates',
                    'desc' => 'It will convert product price from fiat currency to cryptocurrency in real time. Match your token symbol with CryptoCompare or Binance listed tokens for accurate pricing.',
                ),
                array(
                    'id' => 'crypto_compare_key',
                    'title' => __('CryptoCompare API Key <span style="color:red">(Required)</span>', 'cpmwp'),
                    'type' => 'text',
                    'dependency' => array('currency_conversion_api', '==', 'cryptocompare'),
                    'desc' => 'Check -<a href=" https://paywithcryptocurrency.net/get-cryptocompare-free-api-key/" target="_blank">How to retrieve CryptoCompare free API key?</a>',
                ),
                array(
                    'id' => 'enable_refund',
                    'title' => esc_html__('Enable Refund ', 'cpmwp'),
                    'type' => 'switcher',
                    'text_on' => 'Enable',
                    'text_off' => 'Disable',
                    'text_width' => 80,
                    'desc' => 'Send a refund to your customer in cryptocurrency from the order page.',
                    'help' => esc_html__('Enable refund option', 'cpmwp'),
                    'default' => true,
                ),

                array(
                    'id' => 'payment_status',
                    'title' => esc_html__('Payment Success: Order Status', 'cpmwp'),
                    'type' => 'select',
                    'options' => apply_filters(
                        'cpmwp_settings_order_statuses',
                        array(
                            'default' => __('Woocommerce Default Status', 'cpmwp'),
                            'on-hold' => __('On Hold', 'cpmwp'),
                            'processing' => __('Processing', 'cpmwp'),
                            'completed' => __('Completed', 'cpmwp'),
                        )
                    ),
                    'desc' => __('Order status upon successful cryptocurrency payment.', 'cpmwp'),
                    'default' => 'default',
                ),

                array(
                    'id' => 'redirect_page',
                    'title' => esc_html__('Payment Success: Redirect Page', 'cpmwp'),
                    'type' => 'text',
                    'placeholder' => 'https://coolplugins.net/my-account/orders/',
                    'desc' => 'Enter a custom URL to redirect or leave it blank to update the order status on the same page.',
                ),
                array(
                    'id' => 'payment_gateway_logo',
                    'title' => esc_html__('Payment Gateway Logo', 'cpmwp'),
                    'type' => 'media',
                    'library' => 'image',
                    'url' => false,
                    'button_title' => 'Upload Logo',
                    'default' => array(
                        'url' => CPMWP_URL . 'assets/images/pay-with-crypto.png',
                        'thumbnail' => CPMWP_URL . 'assets/images/pay-with-crypto.png',
                    ),
                    'desc' => esc_html__('This logo will be visible on the checkout page alongside the payment gateway.', 'cpmwp'),
                ),
                array(
                    'id' => 'dynamic_messages',
                    'title' => esc_html__('Customize Text Display', 'cpmwp'),
                    'type' => 'select',
                    'options' => array(
                        'confirm_msg' => __('Payment Confirmation (Popup)', 'cpmwp'),
                        'payment_process_msg' => __('Payment Processing (Popup)', 'cpmwp'),
                        'rejected_message' => __('Payment Rejected (Popup)', 'cpmwp'),
                        'payment_msg' => __('Payment Completed (Popup)', 'cpmwp'),
                        'place_order_button' => __('Place Order Button (Checkout page)', 'cpmwp'),
                        'select_a_currency' => __('Select Coin (Checkout page)', 'cpmwp'),
                        'select_a_network' => __('Select Network (Checkout page)', 'cpmwp'),
                        'refund_order_btn' => __('Refund Order Button (Order page)', 'cpmwp'),
                    ),

                    'desc' => __('Customize the text displayed by the plugin on the frontend.', 'cpmwp'),
                    'default' => 'place_order_button',
                ),
                array(
                    'id' => 'confirm_msg',
                    'title' => esc_html__('Payment Confirmation (Popup)', 'cpmwp'),
                    'type' => 'text',
                    'dependency' => array('dynamic_messages', '==', 'confirm_msg'),
                    'desc' => 'You can change it to your preferred text or leave it blank to keep the default text.',
                    'placeholder' => __('Confirm Payment Inside Your Wallet!', 'cpmwp'),
                ),
                array(
                    'id' => 'payment_process_msg',
                    'title' => esc_html__('Payment Processing (Popup)', 'cpmwp'),
                    'type' => 'text',
                    'dependency' => array('dynamic_messages', '==', 'payment_process_msg'),
                    'desc' => 'Custom message to show  while processing payment via blockchain.',
                    'placeholder' => __('Payment in process.', 'cpmwp'),
                ),
                array(
                    'id' => 'rejected_message',
                    'title' => esc_html__('Payment Rejected (Popup)', 'cpmwp'),
                    'type' => 'text',
                    'dependency' => array('dynamic_messages', '==', 'rejected_message'),
                    'desc' => 'Custom message to show  if you rejected payment via metamask.',
                    'placeholder' => __('Transaction rejected. ', 'cpmwp'),
                ),
                array(
                    'id' => 'payment_msg',
                    'title' => esc_html__('Payment Completed (Popup)', 'cpmwp'),
                    'type' => 'text',
                    'dependency' => array('dynamic_messages', '==', 'payment_msg'),
                    'placeholder' => __('Payment completed successfully.', 'cpmwp'),
                    'desc' => 'Custom message to show  if  payment confirm  by blockchain.',

                ),
                array(
                    'id' => 'place_order_button',
                    'title' => esc_html__('Place Order Button (Checkout page)', 'cpmwp'),
                    'type' => 'text',
                    'dependency' => array('dynamic_messages', '==', 'place_order_button'),
                    'placeholder' => __('Pay With Crypto Wallets', 'cpmwp'),
                    'desc' => 'Please specify a name for the "Place Order" button on the checkout page.',

                ),
                array(
                    'id' => 'select_a_currency',
                    'title' => esc_html__('Select Coin (Checkout page)', 'cpmwp'),
                    'type' => 'text',
                    'dependency' => array('dynamic_messages', '==', 'select_a_currency'),
                    'placeholder' => __('Please Select a Currency', 'cpmwp'),
                    'desc' => 'Please provide a name for the label that selects the currency on the checkout page.',

                ),
                array(
                    'id' => 'select_a_network',
                    'title' => esc_html__('Select Network (Checkout page)', 'cpmwp'),
                    'type' => 'text',
                    'dependency' => array('dynamic_messages', '==', 'select_a_network'),
                    'placeholder' => __('Select Payment Network', 'cpmwp'),
                    'desc' => 'Please provide a name for the label that selects the network on the checkout page.',

                ),
                array(
                    'id' => 'refund_order_btn',
                    'title' => esc_html__('Refund Order Button (Order page)', 'cpmwp'),
                    'type' => 'text',
                    'dependency' => array('dynamic_messages', '==', 'refund_order_btn'),
                    'placeholder' => __('Refund via Crypto wallet', 'cpmwp'),
                    'desc' => 'Please specify a name for the "Refund Order" button on the Order page.',

                ),
                array(
                    'id' => 'enable_debug_log',
                    'title' => esc_html__('Debug mode ', 'cpmwp'),
                    'type' => 'switcher',
                    'text_on' => 'Enable',
                    'text_off' => 'Disable',
                    'text_width' => 80,
                    'desc' => 'When enabled, payment error logs will be saved to WooCommerce > Status > <a href="' . esc_url(get_admin_url(null, "admin.php?page=wc-status&tab=logs")) . '">Logs.</a>',
                    'help' => esc_html__('Enable debug mode', 'cpmwp'),
                    'default' => true,
                ),

            ),
        )
    );

    CPMWP_CSF::createSection(
        $prefix,
        array(
            'title' => 'Wallets',
            'icon' => 'fas fa-wallet',
            'fields' => array(

                array(
                    'id' => 'supported_wallets',
                    'title' => esc_html__('Supported Wallets', 'cpmwp'),
                    'type' => 'fieldset',
                    'fields' => array(
                        array(
                            'id' => 'metamask_wallet',
                            'title' => esc_html__('Enable MetaMask Wallet', 'cpmwp'),
                            'type' => 'switcher',
                            'help' => esc_html__('Enable this wallet for payment', 'cpmwp'),
                            'default' => true,
                        ),
                        array(
                            'id' => 'wallet_connect',
                            'title' => esc_html__('Enable Wallet Connect', 'cpmwp'),
                            'type' => 'switcher',
                            'help' => esc_html__('Enable this wallet for payment', 'cpmwp'),
                            'default' => false,
                        ),
                    ),
                ),
            ),

        )
    );

    CPMWP_CSF::createSection(
        $prefix,
        array(
            'title' => 'Networks/Chains',
            'icon' => 'fas fa-network-wired',
            'fields' => array(

                array(
                    'id' => 'custom_networks',
                    'title' => esc_html__('Networks/Chains', 'cpmwp'),
                    'type' => 'group',
                    'accordion_title_by' => array('chainName', 'enable'),
                    'accordion_title_by_prefix' => ' | ',
                    'button_title' => esc_html__('Add new', 'cpmwp'),
                    'default' => array(
                        array(
                            'chainName' => 'Ethereum Mainnet (ERC20)',
                            'rpcUrls' => '',
                            'chainId' => '0x1',
                            'blockExplorerUrls' => 'https://etherscan.io/',
                            'enable' => true,
                            'nativeCurrency' => array(
                                'enable' => true,
                                'name' => 'Ethereum',
                                'symbol' => 'ETH',
                                'decimals' => 18,
                                'default_currency' => true,
                                'image' => CPMWP_URL . '/assets/images/ETH.svg',
                            ),
                            'currencies' => array(
                                array(
                                    'symbol' => 'USDT',
                                    'contract_address' => '0xdac17f958d2ee523a2206206994597c13d831ec7',
                                    'image' => CPMWP_URL . '/assets/images/USDT.svg',
                                    'enable' => true,
                                ),

                            ),
                        ),
                        array(
                            'chainName' => 'Ethereum Sepolia (Testnet)',
                            'rpcUrls' => 'https://rpc.sepolia.dev',
                            'chainId' => '0xaa36a7',
                            'blockExplorerUrls' => 'https://sepolia.etherscan.io/',
                            'enable' => false,
                            'nativeCurrency' => array(
                                'enable' => true,
                                'name' => 'Ethereum',
                                'symbol' => 'ETH',
                                'decimals' => 18,
                                'default_currency' => true,
                                'image' => CPMWP_URL . '/assets/images/ETH.svg',
                            ),
                        ),
                        array(
                            'chainName' => 'Binance Smart Chain (BEP20)',
                            'rpcUrls' => 'https://bsc-dataseed.binance.org/',
                            'chainId' => '0x38',
                            'blockExplorerUrls' => 'https://bscscan.com/',
                            'enable' => true,
                            'nativeCurrency' => array(
                                'enable' => true,
                                'name' => 'BNB',
                                'symbol' => 'BNB',
                                'decimals' => 18,
                                'default_currency' => true,
                                'image' => CPMWP_URL . '/assets/images/BNB.svg',
                            ),
                            'currencies' => array(
                                array(
                                    'symbol' => 'BUSD',
                                    'contract_address' => '0xe9e7cea3dedca5984780bafc599bd69add087d56',
                                    'image' => CPMWP_URL . '/assets/images/BUSD.svg',
                                    'enable' => true,
                                ),

                            ),
                        ),

                        array(
                            'chainName' => 'Binance Smart Chain (Testnet)',
                            'rpcUrls' => 'https://data-seed-prebsc-1-s1.binance.org:8545',
                            'chainId' => '0x61',
                            'blockExplorerUrls' => 'https://testnet.bscscan.com/',
                            'enable' => false,
                            'nativeCurrency' => array(
                                'enable' => true,
                                'name' => 'BNB',
                                'symbol' => 'BNB',
                                'decimals' => 18,
                                'default_currency' => true,
                                'image' => CPMWP_URL . '/assets/images/BNB.svg',
                            ),
                            'currencies' => array(
                                array(
                                    'symbol' => 'BUSD',
                                    'contract_address' => '0xeD24FC36d5Ee211Ea25A80239Fb8C4Cfd80f12Ee',
                                    'image' => CPMWP_URL . '/assets/images/BUSD.svg',
                                    'enable' => true,
                                ),

                            ),
                        ),
                        array(
                            'chainName' => 'Avalanche (AVAX C-Chain)',
                            'rpcUrls' => 'https://api.avax.network/ext/bc/C/rpc',
                            'chainId' => '0xa86a',
                            'blockExplorerUrls' => 'https://cchain.explorer.avax.network/',
                            'enable' => true,
                            'nativeCurrency' => array(
                                'enable' => true,
                                'name' => 'AVAX',
                                'symbol' => 'AVAX',
                                'decimals' => 18,
                                'default_currency' => true,
                                'image' => CPMWP_URL . '/assets/images/AVAX.svg',
                            ),
                            'currencies' => array(
                                array(
                                    'symbol' => '',
                                    'contract_address' => '',
                                    'image' => '',
                                    'enable' => false,
                                ),

                            ),
                        ),
                        array(
                            'chainName' => 'Avalanche (Testnet)',
                            'rpcUrls' => 'https://api.avax-test.network/ext/bc/C/rpc',
                            'chainId' => '0xa869',
                            'blockExplorerUrls' => 'https://cchain.explorer.avax-test.network/',
                            'enable' => false,
                            'nativeCurrency' => array(
                                'enable' => true,
                                'name' => 'AVAX',
                                'symbol' => 'AVAX',
                                'decimals' => 18,
                                'default_currency' => true,
                                'image' => CPMWP_URL . '/assets/images/AVAX.svg',
                            ),
                            'currencies' => array(
                                array(
                                    'symbol' => '',
                                    'contract_address' => '',
                                    'image' => '',
                                    'enable' => false,
                                ),

                            ),
                        ),
                        array(
                            'chainName' => 'Polygon Mainnet',
                            'rpcUrls' => 'https://polygon-rpc.com/',
                            'chainId' => '0x89',
                            'blockExplorerUrls' => 'https://polygonscan.com/',
                            'enable' => false,
                            'nativeCurrency' => array(
                                'enable' => true,
                                'name' => 'MATIC',
                                'symbol' => 'MATIC',
                                'decimals' => 18,
                                'default_currency' => true,
                                'image' => CPMWP_URL . '/assets/images/MATIC.svg',
                            ),
                            'currencies' => array(
                                array(
                                    'symbol' => '',
                                    'contract_address' => '',
                                    'image' => '',
                                    'enable' => false,
                                ),

                            ),
                        ),
                        array(
                            'chainName' => 'Polygon (Mumbai Testnet)',
                            'rpcUrls' => 'https://matic-mumbai.chainstacklabs.com/',
                            'chainId' => '0x13881',
                            'blockExplorerUrls' => 'https://mumbai.polygonscan.com/',
                            'enable' => false,
                            'nativeCurrency' => array(
                                'enable' => true,
                                'name' => 'MATIC',
                                'symbol' => 'MATIC',
                                'decimals' => 18,
                                'default_currency' => true,
                                'image' => CPMWP_URL . '/assets/images/MATIC.svg',
                            ),
                            'currencies' => array(
                                array(
                                    'symbol' => '',
                                    'contract_address' => '',
                                    'image' => '',
                                    'enable' => false,
                                ),

                            ),
                        ),

                    ),

                    'fields' => array(
                        array(
                            'id' => 'enable',
                            'title' => esc_html__('Enable Network', 'cpmwp'),
                            'type' => 'switcher',
                            'desc' => 'Get your network details <a href="https://chainlist.org/" target="_blank">Click Here</a>',
                            'help' => esc_html__('Enable this network for payment', 'cpmwp'),
                            'default' => true,
                        ),

                        array(
                            'id' => 'recever_wallet',
                            'title' => __('Payment Address', 'cpmwp'),
                            'type' => 'text',
                            'placeholder' => '0x1dCXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                            'dependency' => array('enable', '==', true),
                            'desc' => '<strong>Leave this field empty if you want to use default payment address.</strong>',
                            'help' => esc_html__('Leave this field empty if you want to use default payment address.', 'cpmwp'),
                        ),

                        array(
                            'title' => esc_html__('Network Name', 'cpmwp'),
                            'id' => 'chainName',
                            'dependency' => array('enable', '==', true),
                            'type' => 'text',
                        ),
                        array(
                            'title' => esc_html__('Network RPC URL', 'cpmwp'),
                            'id' => 'rpcUrls',
                            'dependency' => array('enable', '==', true),
                            'type' => 'text',
                        ),
                        array(
                            'title' => esc_html__('Network Chain ID', 'cpmwp'),
                            'id' => 'chainId',
                            'dependency' => array('enable', '==', true),
                            'placeholder' => '0xXXXXXX',
                            'class' => 'cpmwp_chain_id',
                            'type' => 'text',
                            'desc' => '<strong style="color:red;">Enter chain id in hex form Like:-"0xa86a" </strong>',
                        ),
                        array(
                            'title' => esc_html__('Block Explorer URL', 'cpmwp'),
                            'id' => 'blockExplorerUrls',
                            'dependency' => array('enable', '==', true),
                            'type' => 'text',
                        ),

                        array(
                            'id' => 'nativeCurrency',
                            'dependency' => array('enable', '==', true),
                            'type' => 'fieldset',
                            'title' => esc_html__('Network Main Currency', 'cpmwp'),
                            'fields' => array(
                                array(
                                    'id' => 'enable',
                                    'title' => esc_html__('Enable Currency', 'cpmwp'),
                                    'type' => 'switcher',
                                    'help' => esc_html__('Enable this currency', 'cpmwp'),
                                    'default' => true,
                                ),
                                array(
                                    'id' => 'name',
                                    'type' => 'text',
                                    'dependency' => array('enable', '==', true),
                                    'title' => esc_html__('Name', 'cpmwp'),
                                ),
                                array(
                                    'id' => 'symbol',
                                    'type' => 'text',
                                    'dependency' => array('enable', '==', true),
                                    'title' => esc_html__('Symbol', 'cpmwp'),
                                ),
                                array(
                                    'id' => 'decimals',
                                    'type' => 'number',
                                    'dependency' => array('enable', '==', true),
                                    'title' => esc_html__('Decimals', 'cpmwp'),
                                ),
                                array(
                                    'title' => esc_html__('Image', 'cpmwp'),
                                    'id' => 'image',
                                    'dependency' => array('enable', '==', true),
                                    'type' => 'upload',
                                ),
                                array(
                                    'id' => 'currency_discount',
                                    'type' => 'number',
                                    'dependency' => array('enable', '==', true),
                                    'title' => 'Order Discount',
                                    'desc' => esc_html__('Add(%) discount on order if customer select this option as payment.', 'cpmwp'),
                                ),
                                array(
                                    'id' => 'enable_custom',
                                    'title' => esc_html__('Enable Custom Price', 'cpmwp'),
                                    'type' => 'switcher',
                                    'dependency' => array('enable', '==', true),
                                    'help' => esc_html__('Enable this to add custom native currency price', 'cpmwp'),
                                    'default' => false,
                                ),
                                array(
                                    'title' => esc_html__('Custom Price', 'cpmwp'),
                                    'id' => 'custom_native_price',
                                    'dependency' => array(array('enable_custom', '==', true), array('enable', '==', true)),
                                    'type' => 'text',
                                    'desc' => '1 Token=How much of your store currency.',
                                ),

                            ),
                        ),
                        array(
                            'id' => 'currencies',
                            'dependency' => array('enable', '==', true),
                            'type' => 'group',
                            'accordion_title_by' => array('symbol', 'enable'),
                            'accordion_title_by_prefix' => ' | ',
                            'title' => esc_html__('Tokens', 'cpmwp'),
                            'button_title' => esc_html__('Add new', 'cpmwp'),
                            'fields' => array(
                                array(
                                    'id' => 'enable',
                                    'title' => esc_html__('Enable Currency', 'cpmwp'),
                                    'type' => 'switcher',
                                    'help' => esc_html__('Enable this Token to show in Network', 'cpmwp'),
                                    'default' => true,
                                ),
                                array(
                                    'title' => esc_html__('Symbol', 'cpmwp'),
                                    'id' => 'symbol',
                                    'dependency' => array('enable', '==', true),
                                    'type' => 'text',
                                ),
                                array(
                                    'title' => esc_html__('Contract Address', 'cpmwp'),
                                    'id' => 'contract_address',
                                    'placeholder' => '0x1dCXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                                    'dependency' => array('enable', '==', true),
                                    'type' => 'text',
                                ),
                                array(
                                    'title' => esc_html__('Image', 'cpmwp'),
                                    'id' => 'image',
                                    'dependency' => array('enable', '==', true),
                                    'type' => 'upload',
                                ),
                                array(
                                    'id' => 'token_discount',
                                    'type' => 'number',
                                    'dependency' => array('enable', '==', true),
                                    'title' => 'Order Discount',
                                    'desc' => esc_html__('Add(%) discount on order if customer select this option as payment.', 'cpmwp'),
                                ),
                                array(
                                    'id' => 'enable_custom_price',
                                    'title' => esc_html__('Custom Price', 'cpmwp'),
                                    'type' => 'switcher',
                                    'dependency' => array('enable', '==', true),
                                    'help' => esc_html__('Enable this option if your token not listed on Cryptocompare or Binance', 'cpmwp'),
                                    'desc' => esc_html__('Enable this option if your token not listed on Cryptocompare or Binance', 'cpmwp'),
                                    'default' => false,
                                ),
                                array(
                                    'id' => 'custom_token',
                                    'type' => 'radio',
                                    'title' => false,
                                    'dependency' => array(
                                        array('enable', '==', true),
                                        array('enable_custom_price', '==', true),
                                    ),
                                    'inline' => true,
                                    'options' => array(
                                        'custom_token_price' => 'Custom price',
                                        'pancake_swap' => 'Coin Brain (Check this option if coin listed on Coin Brain)',
                                    ),
                                    'help' => __('Use Openexchangerates Api for <a href="https://coinbrain.com/" target="_blank">Coin Brain</a> option to work', 'cpmwp'),
                                    'desc' => '<span style="color:red"> Use Openexchangerates api for these options to work</span>',
                                    'default' => 'custom_token_price',
                                ),
                                array(
                                    'title' => esc_html__('Token price', 'cpmwp'),
                                    'id' => 'token_price',
                                    'dependency' => array(
                                        array('enable', '==', true),
                                        array('enable_custom_price', '==', true),
                                        array('custom_token', '==', 'custom_token_price'),
                                    ),
                                    'type' => 'text',
                                    'desc' => '1 Token=How much of your store currency.<br>',
                                ),

                            ),
                        ),
                    ),
                ),
            ),

        )
    );

    CPMWP_CSF::createSection(
        $prefix,
        array(
            'title' => 'Login With Crypto Wallets',
            'icon' => 'fas fa-key',

            'fields' => array(
                array(
                    'id' => 'enable_metamask_button',
                    'title' => esc_html__('Enable Wallet Login On', 'cpmwp'),
                    'type' => 'fieldset',
                    'fields' => array(
                        array(
                            'id' => 'enable_at_wp_admin',
                            'title' => esc_html__('Wordpress Login Form', 'cpmwp'),
                            'type' => 'switcher',
                            'desc' => esc_html__('Enable this option to show Wallet login button on Wordpress login form.', 'cpmwp'),
                            'default' => false,
                        ),

                        array(
                            'id' => 'enable_at_wc_form',
                            'title' => esc_html__('WooCommerce Login Form', 'cpmwp'),
                            'type' => 'switcher',
                            'desc' => esc_html__('Enable this option to show Wallet login button on WooCommerce login form.', 'cpmwp'),
                            'default' => false,
                        ),

                        array(
                            'id' => 'enable_at_wc_chk_form',
                            'title' => esc_html__('WooCommerce Checkout Page', 'cpmwp'),
                            'type' => 'switcher',
                            'desc' => esc_html__('Enable this option to show Wallet login button on WooCommerce checkout form.', 'cpmwp'),
                            'default' => false,
                        ),

                    ),
                ),

                array(
                    'title' => esc_html__('User Role', 'cpmwp'),
                    'id' => 'metamask_user_role',
                    'type' => 'select',
                    'default' => 'customer',
                    'options' => array(
                        'subscriber' => __('Subscriber ', 'cpmwp'),
                        'customer' => __('Customer', 'cpmwp'),
                        // 'administrator' => __('Administrator ', 'cpmwp'),
                        // 'author' => __('Author ', 'cpmwp'),
                        // 'contributor' => __('Contributor  ', 'cpmwp'),

                    ),
                    'desc' => 'Select role for user when he register',
                ),
                array(
                    'title' => esc_html__('Wallet Login Button Label', 'cpmwp'),
                    'id' => 'mmetamask_login_label',
                    'type' => 'text',
                    'placeholder' => 'Enter Label Text For Login Button',
                    'default' => 'Login With Crypto Wallet',
                    'desc' => 'Enter text to show on Wallet login button',
                ),

                array(
                    'title' => esc_html__('Wallet Authenticate Message', 'cpmwp'),
                    'id' => 'metamask_sign_message',
                    'type' => 'text',
                    'default' => 'Sign this message to prove you have access to this wallet and we’ll log you in. - This won’t cost you any Tokens. - This signature does not give you access to control your tokens  To stop hackers using your wallet, here’s a unique message ID they can’t guess: ',
                    'desc' => 'This message will show when user sign the login request',
                ),
                array(
                    'title' => esc_html__('Redirect Page', 'cpmwp'),
                    'id' => 'mmetamask_redirect',
                    'type' => 'text',
                    'placeholder' => 'https://coolplugins.net/my-account',
                    'desc' => 'User will redirect to this page after login.',
                ),
                array(
                    'id' => 'enable_disconnect_btn',
                    'title' => esc_html__('Enable disconnect Button', 'cpmwp'),
                    'type' => 'switcher',
                    'desc' => esc_html__('Enable this option to show disconnect button on connected wallet', 'cpmwp'),
                    'default' => false,
                ),
                array(
                    'id' => 'cpmwp_login_btn_color',
                    'type' => 'color',
                    'title' => 'Text Color',
                    'default' => '',
                ),
                array(
                    'id' => 'cpmwp_login_btn_bg_color',
                    'type' => 'color',
                    'title' => 'Background Color',
                    'default' => '',
                ),
                array(
                    'id' => 'cpmwp_shortcode',
                    'type' => 'text',
                    'title' => 'Wallet Login Button shortcode',
                    'default' => '[cpmwp_wallet_login_button size="small"]',
                    'help' => esc_html__('You can use this shortcode to add Wallet Login Button any where in post/page', 'cpmwp'),
                    'desc' => 'Supported attribute  <strong> size="small/medium/large" </strong>',
                    'attributes' => array(
                        'readonly' => 'readonly',
                    ),
                ),

            ),

        )
    );

    CPMWP_CSF::createSection(
        $prefix,
        array(
            'title' => 'Free Test Tokens',
            'icon' => 'fas fa-rocket',
            'fields' => array(
                array(
                    'type' => 'heading',
                    'content' => 'Get Free Test Tokens to Test Payment via Metamask on Test Networks/Chains.',
                ),
                array(
                    'type' => 'subheading',
                    'content' => ' ETH Test Token For Sepolia Network: <a href="https://sepoliafaucet.com/" target="_blank">https://sepoliafaucet.com</a>',
                ),
                array(
                    'type' => 'subheading',
                    'content' => 'AVAX Test Token For AVAX Network: <a href="https://faucet.avax-test.network/" target="_blank">https://faucet.avax-test.network</a>',
                ),
                array(
                    'type' => 'subheading',
                    'content' => 'MATIC Test Token For Polygon Network: <a href="https://faucet.polygon.technology/" target="_blank">https://faucet.polygon.technology</a>',
                ),

                array(
                    'type' => 'subheading',
                    'content' => 'Binance Test Tokens For Binance Network: <a href="https://testnet.binance.org/faucet-smart" target="_blank">https://testnet.binance.org/faucet-smart</a>',
                ),

            ),

        )
    );

    CPMWP_CSF::createSection(
        $prefix,
        array(
            'title' => 'Support',
            'icon' => 'fas fa-user',
            'fields' => array(
                array(
                    'type' => 'submessage',
                    'content' => '<b>Q1. Are you facing any issue ?</b> <br><p>Ans:-Please contact premeium support team at <b><a href="mailto:contact@coolplugins.net">contact@coolplugins.net </a></b>, we will try to provide a solution in next 24-48 hours.</p>',
                ),
                array(
                    'type' => 'submessage',
                    'content' => '<b>Q2. Where is plugin documentation ?</b> <br><p>Ans:-Check this <a href="https://paywithcryptocurrency.net/docs/plugin-documentation/" target="_blank">Docs link</a></p>',
                ),
                array(
                    'type' => 'submessage',
                    'content' => '<b>Q3. Need help in pluign customization or setup.</b> <br><p>Ans:-You can hire our developer for any customization or plugin setup at <a href="https://coolplugins.net/#hire-developers">Hire Developer</a></p>',
                ),

            ),

        )
    );

endif;
