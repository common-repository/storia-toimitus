<?php

/*
*
* Takes the order ID, updates the order status to 'completed'.
*
*/

if (!defined('ABSPATH')) {
    die('No access');
}

add_action('rest_api_init', 'kvtoimitus_register_update_order_status_endpoint');

function kvtoimitus_register_update_order_status_endpoint() {
    register_rest_route('kirjavalitys/v1', '/update/(?P<id>\d+)', array(
        'methods' => 'PATCH',
        'callback' => 'kvtoimitus_update_order_status',
        'permission_callback' => 'kvtoimitus_order_permission_callback'
    ));
}

function kvtoimitus_update_order_status($request) {
    $id = absint($request['id']);

    $order = wc_get_order($id);

    if ($order) {
        $order->update_status('completed');
        return new WP_REST_Response('Order status updated to completed', 200);
    } else {
        return new WP_Error('invalid_order_id', 'Invalid Order ID', array('status' => 404));
    }
}

if (!function_exists('kvtoimitus_order_permission_callback')) {
    function kvtoimitus_order_permission_callback($request) {
        $api_key = $request->get_header('X-API-KEY');
    
        $stored_hashed_api_key = get_option('kvtoimitus_api_key');
    
        if (password_verify($api_key, $stored_hashed_api_key)) {
            return true;
        }
        return new WP_Error('rest_forbidden', 'Invalid API key.', array('status' => 401));
    }
}