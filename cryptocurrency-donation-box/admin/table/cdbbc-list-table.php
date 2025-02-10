<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Cdbbc_donation_list extends WP_List_Table
{

    public function get_columns()
    {
        $columns = array(
            'id' => '#',
            'transaction_id' => __("Transaction Id", 'cryptocurrency-donation-box'),
            'sender' => __("Sender", 'cryptocurrency-donation-box'),
            'recever' => __("Reciever", 'cryptocurrency-donation-box'),
            'currency' => __("Currency", 'cryptocurrency-donation-box'),
            'amount' => __("Amount", 'cryptocurrency-donation-box'),
            'wallet_name' => __("Wallet", 'cryptocurrency-donation-box'),
            'network' => __("Network", 'cryptocurrency-donation-box'),
            'payment_status' => __("Payment Status", 'cryptocurrency-donation-box'),
            'user_email' => __("Email", 'cryptocurrency-donation-box'),
            'transaction_status' => __("Transaction Status", 'cryptocurrency-donation-box'),
            'blockchain' => __("Blockchain", 'cryptocurrency-donation-box'),
            'last_updated' => __("Last Updated", 'cryptocurrency-donation-box'),
        );
        return $columns;
    }

    public function prepare_items()
    {
        global $wpdb, $_wp_column_headers;
    
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
    
        // Sanitize and initialize variables
        $user_search_keyword = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';
        $allowed_order_columns = array('id', 'last_updated');
        $allowed_order_direction = array('ASC', 'DESC');
        $orderby = isset($_REQUEST["orderby"]) && in_array($_REQUEST["orderby"], $allowed_order_columns, true) 
        ? sanitize_key($_REQUEST["orderby"]) 
        : 'last_updated';
    
        $order = isset($_REQUEST["order"]) && in_array($_REQUEST["order"], $allowed_order_direction, true) 
        ? sanitize_key($_REQUEST["order"]) 
        : 'DESC';
    
        // Construct the base query
        $base_table = esc_sql($wpdb->base_prefix . 'cdbbc_transaction');
        $query = "SELECT * FROM $base_table";
        $where_clauses = [];
    
        // Add search filter if provided
        if (!empty($user_search_keyword)) {
            $like_keyword = '%' . $wpdb->esc_like($user_search_keyword) . '%';
            $where_clauses[] = $wpdb->prepare('(user_email LIKE %s OR selected_network LIKE %s OR currency LIKE %s)', $like_keyword, $like_keyword, $like_keyword);
        }
    
        // Append WHERE clauses
        if (!empty($where_clauses)) {
            $query .= ' WHERE ' . implode(' AND ', $where_clauses);
        }
    
        // Add ORDER BY clause
        if (!empty($orderby) && !empty($order)) {
            $query .= " ORDER BY $orderby $order"; // Ensure $orderby and $order are validated earlier
        }
    
        // Count total items for pagination
        $count_query = str_replace('SELECT *', 'SELECT COUNT(*)', $query);
        $totalitems = $wpdb->get_var($count_query);
    
        // Pagination
        $perpage = 10;
        $paged = isset($_REQUEST["paged"]) && is_numeric($_REQUEST["paged"]) && $_REQUEST["paged"] > 0 ? (int) $_REQUEST["paged"] : 1;
        $offset = ($paged - 1) * $perpage;
        $query .= $wpdb->prepare(' LIMIT %d, %d', $offset, $perpage);
    
        // Set pagination arguments
        $this->set_pagination_args(array(
            "total_items" => $totalitems,
            "total_pages" => ceil($totalitems / $perpage),
            "per_page"    => $perpage,
        ));
    
        // Fetch results
        $this->items = $wpdb->get_results($query);
    }
    

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
                return $item->id;
            case 'transaction_id':
                return $item->transaction_id;
            case 'sender':
                return $item->sender;
            case 'recever':
                return $item->recever;
            case 'currency':
                return $item->currency;
            case 'amount':
                return $item->amount;
            case 'wallet_name':
                return $item->wallet_name;
            case 'network':
                return $item->selected_network;
            case 'payment_status':
                return $item->payment_status;
            case 'user_email':
                return $item->user_email;
            case 'transaction_status':
                return $item->transaction_status;
            case 'blockchain':
                return $item->blockchain;
            case 'last_updated':
                return $this->timeAgo($item->last_updated);
            default:
                return print_r($item, true); 
        }
    }

    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'id' => array('id', true),
            'last_updated' => array('last_updated', true),
        );
        return $sortable_columns;
    }

    public function timeAgo($time_ago)
    {
        $time_ago = strtotime($time_ago) ? strtotime($time_ago) : $time_ago;
        $time = time() - $time_ago;
        switch ($time):
            case $time < 60:
                return '1 minute ago';
            case $time >= 60 && $time < 3600:
                return (round($time / 60) == 1) ? '1 minute' : round($time / 60) . ' minutes ago';
            case $time >= 3600 && $time < 86400:
                return (round($time / 3600) == 1) ? '1 hour ago' : round($time / 3600) . ' hours ago';
            case $time >= 86400:
                return (round($time / 86400) == 1) ? date_i18n('M j, Y', $time_ago) : date_i18n('M j, Y', $time_ago);
        endswitch;
    }
}
