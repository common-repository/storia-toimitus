<?php

/*
*
* Update the stock status of products.
*
*/

if (!defined('ABSPATH')) {
    die('No access');
}

add_action('rest_api_init', 'kvtoimitus_update_stock_status_endpoint');

function kvtoimitus_update_stock_status_endpoint() {
    register_rest_route('kirjavalitys/v1', '/update_stock/', array(
        'methods' => 'POST',
        'callback' => 'kvtoimitus_update_stock_callback',
        'permission_callback' => 'kvtoimitus_order_permission_callback'
    ));
}

function kvtoimitus_update_stock_callback(WP_REST_Request $request) {
    $json_data = $request->get_json_params();

    $responses = array();

    foreach ($json_data as $product_data) {
        $response = kvtoimitus_update_single_product($product_data);
        $responses[] = $response;
    }

    return $responses;
}

function kvtoimitus_update_single_product($product_data) {
    $responses = array();

    if (empty($product_data['sku']) || empty($product_data['stock_status'])) {
        return new WP_Error('invalid_data', 'SKU or stock_status missing', array('status' => 400));
    }

    $sku = sanitize_text_field($product_data['sku']);
    $stock_status = sanitize_text_field($product_data['stock_status']);

    $product_id = wc_get_product_id_by_sku($sku);

    if ($product_id) {
        update_post_meta($product_id, '_stock_status', $stock_status);
        $responses = array("sku" => $sku, "status" => "updated");
    }

    if (!$product_id) {
        $responses = array("sku" => $sku, "status" => "not_found");
    }

    return $responses;
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