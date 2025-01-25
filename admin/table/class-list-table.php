<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class cpmwp_metamask_list extends WP_List_Table
{

    public function get_columns()
    {
        $columns = array(
            'order_id' => __("Order Id", "cpmwp"),
            'transaction_id' => __("Transaction ID", "cpmwp"),
            'sender' => __("Sender", "cpmwp"),
            'chain_name' => __("Network", "cpmwp"),
            'selected_currency' => __("Coin", "cpmwp"),
            'crypto_price' => __(" Crypto Price", "cpmwp"),
            'order_price' => __("Fiat Price", "cpmwp"),
            'status' => __("Payment Confirmation", "cpmwp"),
            'order_status' => __("Order Status", "cpmwp"),
            'last_updated' => __("Date", "cpmwp"),
        );
        return $columns;
    }

    public function prepare_items()
    {

        global $wpdb, $_wp_column_headers;
        //    echo '<h1>Coins List</h1><form method="post">';
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $query = 'SELECT * FROM ' . $wpdb->base_prefix . 'cpmw_transaction';
        /*  $this->cmc_process_bulk_action();
        $this->cmc_perform_row_actions(); */
        // delete_option('cpmwp-coins-search');

        // search keyword


        $user_search_keyword = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';
        // $processing = isset($_REQUEST['cpmwp_processing']) ? wp_unslash(trim($_REQUEST['cpmwp_processing'])) : '';
        // $canceled = isset($_REQUEST['cpmwp_canceled']) ? wp_unslash(trim($_REQUEST['cpmwp_canceled'])) : '';
        // $completed = isset($_REQUEST['cpmwp_completed']) ? wp_unslash(trim($_REQUEST['cpmwp_completed'])) : '';
        // $onhold = isset($_REQUEST['cpmwp_on_hold']) ? wp_unslash(trim($_REQUEST['cpmwp_on_hold'])) : '';
        $status= isset($_REQUEST['payment_status']) ? wp_unslash(trim($_REQUEST['payment_status'])) : '';
        /*      if( !empty($user_search_keyword) ){

        update_option('cpmwp-coins-search', $user_search_keyword );
        }else if( false != get_option('cpmwp-coins-search', false) && empty($user_search_keyword) ){
        $user_search_keyword = get_option('cpmwp-coins-search', '');
        } */

        if (isset($user_search_keyword) && !empty($user_search_keyword)) {
            $query .= ' where ( order_id LIKE "%' . $user_search_keyword . '%" OR chain_name LIKE "%' . $user_search_keyword . '%" OR selected_currency LIKE "%' . $user_search_keyword . '%" OR transaction_id LIKE "%' . $user_search_keyword . '%") ';
        } elseif (isset($status) && !empty($status)) {
            $query .= ' where ( status LIKE "' . $status . '" ) ';

        }
        //  elseif (isset($canceled) && !empty($canceled)) {
        //     $query .= ' where ( status LIKE "%' . $canceled . '%" ) ';

        // } elseif (isset($completed) && !empty($completed)) {
        //     $query .= ' where ( status LIKE "%' . $completed . '%" ) ';

        // } elseif (isset($onhold) && !empty($onhold)) {
        //     $query .= ' where ( status LIKE "%' . $onhold . '%" ) ';

        // }

        // Ordering parameters
        $orderby = !empty($_REQUEST["orderby"]) ? esc_sql($_REQUEST["orderby"]) : 'last_updated';
        $order = !empty($_REQUEST["order"]) ? esc_sql($_REQUEST["order"]) : 'DESC';
        if (!empty($orderby) & !empty($order)) {
            $query .= ' ORDER BY ' . $orderby . ' ' . $order;
        }

        // Pagination parameters
        $totalitems = $wpdb->query($query);
        $perpage = 10;
        if (!is_numeric($perpage) || empty($perpage)) {
            $perpage = 10;
        }

        $paged = !empty($_REQUEST["paged"]) ? esc_sql($_REQUEST["paged"]) : false;

        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }
        $totalpages = ceil($totalitems / $perpage);

        if (!empty($paged) && !empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
            $query .= ' LIMIT ' . (int) $offset . ',' . (int) $perpage;
        }

        // Register the pagination & build link
        $this->set_pagination_args(array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        )
        );

        // Get feedback data from database
        $this->items = $wpdb->get_results($query);

    }

    public function column_default($item, $column_name)
    {
        wp_enqueue_style('woocommerce_admin_styles');
        $block_explorer = $this->cpmwp_get_explorer_url();
        $order = wc_get_order($item->order_id);
        switch ($column_name) {
            case 'order_id':
                return '<a href="' . admin_url() . 'post.php?post=' . $item->order_id . '&action=edit">#' . $item->order_id . ' ' . $item->user_name . '</a>';

            case 'transaction_id':
                if ($item->transaction_id != "false") {

                    if (isset($block_explorer[$item->chain_id]) && $item->transaction_id != "false") {
                        return '<a href="' . $block_explorer[$item->chain_id] . 'tx/' . $item->transaction_id . '" target="_blank">' . $item->transaction_id . '</a>';

                    }

                }
                //$order = wc_get_order($item->order_id);
                return "--";
                break;

            case 'sender':
                if (isset($block_explorer[$item->chain_id])) {
                    return '<a href="' . $block_explorer[$item->chain_id] . 'address/' . $item->sender . '" target="_blank">' . $item->sender . '</a>';

                }
                return $item->sender;

            case 'chain_name':
                return $item->chain_name;

            case 'selected_currency':
                return $item->selected_currency;

            case 'crypto_price':
                return $item->crypto_price;

            case 'order_price':
                return $item->order_price;
            case 'status':        
                if ($order == false) {
                    return '<span class="order-status status-deleted tips"><span>Deleted</span></span>';
                }        
               if ($item->status == 'completed'||$item->status == 'processing') {
                    return '<span class="order-status status-processing tips"><span>' .__('Confirmed','cpmwp') . '</span></span>';
                }
                elseif ($item->status == "awaiting") {
                    return '<span class="order-status status-cancelled tips"><span>' .__('Awaiting','cpmwp') . '</span></span>';
                }
                 elseif ($item->status == "pending"||$item->status == "canceled") {
                    return '<span class="order-status status-cancelled tips"><span>' .__('Unknown','cpmwp') . '</span></span>';
                }else {
                    return '<span class="order-status status-cancelled tips"><span>' .__('Failed','cpmwp') . '</span></span>';
                }
              

            case 'order_status':                
                if ($order == false) {
                    return '<span class="order-status status-deleted tips"><span>Deleted</span></span>';
                }
                if ($order->get_status() == "canceled") {
                    return '<span class="order-status status-cancelled tips"><span>' . ucfirst($order->get_status()) . '</span></span>';
                } elseif ($order->get_status() == "completed") {
                    return '<span class="order-status status-completed tips"><span>' . ucfirst($order->get_status()) . '</span></span>';
                } elseif ($order->get_status() == "processing") {
                    return '<span class="order-status status-processing tips"><span>' . ucfirst($order->get_status()) . '</span></span>';
                } elseif ($order->get_status() == "on-hold") {
                    return '<span class="order-status status-on-hold tips"><span>' . ucfirst($order->get_status()) . '</span></span>';
                } else {
                    return '<span class="order-status status-cancelled tips"><span>' . ucfirst($order->get_status()) . '</span></span>';
                }

            case 'last_updated':
                if ($order == false) {
                    return $item->last_updated;
                }
                return $this->timeAgo($order);
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    public function get_sortable_columns()
    {
        $sortable_columns = array(
            //'id' => array('id', false),
            //    'transaction_id' => array('Transaction Id', false),
            'order_id' => array('order_id', false),
            //   'sender' => array('Sender', false),
            'chain_name' => array('chain_name', false),
            'selected_currency' => array('selected_currency', false),
            'crypto_price' => array('crypto_price', false),
            'order_price' => array('order_price', false),
            //  'status' => array('Status', false),
            'last_updated' => array('last_updated', false),
        );
        return $sortable_columns;
    }

    public function timeAgo($order)
    {       
        $order_date = $order->get_date_created();
        $time_ago = $order_date->getTimestamp();
        $time_difference = time() -$time_ago;

        if ($time_difference < 60) {
            return $time_difference.' seconds ago';
        } elseif ($time_difference >= 60 && $time_difference < 3600) {
            $minutes = round($time_difference / 60);
            return ($minutes == 1) ? '1 minute' : $minutes . ' minutes ago';
        } elseif ($time_difference >= 3600 && $time_difference < 86400) {
            $hours = round($time_difference / 3600);
            return ($hours == 1) ? '1 hour ago' : $hours . ' hours ago';
        } elseif ($time_difference >= 86400) {
            if (round($time_difference / 86400) == 1) {
                return date_i18n('M j, Y', $time_ago);
            } else {
                return date_i18n('M j, Y', $time_ago);
            }
        }
    }

    /**
     * Get explorer url
     */
    public function cpmwp_get_explorer_url()
    {
            $options = get_option('cpmw_settings');
            $explorer_url = array();
            if (isset($options['custom_networks']) && !empty($options['custom_networks'])) {
                foreach ($options['custom_networks'] as $key => $value) {
                    $explorer_url[$value['chainId']] = $value['blockExplorerUrls'];

                }
                return $explorer_url;
            }

    }

}
