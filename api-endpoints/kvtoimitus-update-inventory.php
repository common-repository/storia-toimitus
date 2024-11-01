<?php

/*
*
* Update the inventory of products.
*
*/

if (!defined('ABSPATH')) {
    die('No access');
}

add_action('rest_api_init', 'kvtoimitus_update_inventory_endpoint');

function kvtoimitus_update_inventory_endpoint() {
    register_rest_route('kirjavalitys/v1', '/update_inventory/', array(
        'methods' => 'POST',
        'callback' => 'kvtoimitus_update_inventory_callback',
        'permission_callback' => 'kvtoimitus_order_permission_callback'
    ));
}

function kvtoimitus_update_inventory_callback(WP_REST_Request $request) {
    $json_data = $request->get_json_params();

    $responses = array();

    foreach ($json_data as $product_data) {
        $response = kvtoimitus_update_single_product_quantity($product_data);
        $responses[] = $response;
    }

    return $responses;
}

function kvtoimitus_update_single_product_quantity($product_data) {
    $responses = array();

    if (empty($product_data['sku'])) {
        return new WP_Error('invalid_data', 'SKU missing', array('status' => 400));
    }

    $sku = sanitize_text_field($product_data['sku']);
    $stock_status = sanitize_text_field($product_data['stock_status']);
    $quantity = sanitize_text_field($product_data['stock_quantity']);
    $manage_stock = sanitize_text_field($product_data['manage_stock']);
    $backorders = sanitize_text_field($product_data['backorders']);

    $product_id = wc_get_product_id_by_sku($sku);

    if ($product_id) {
        update_post_meta($product_id, '_stock_status', $stock_status);
        update_post_meta($product_id, '_stock', $quantity);
        update_post_meta($product_id, '_manage_stock', $manage_stock);
        update_post_meta($product_id, '_backorders', $backorders);

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