<?php

/*
*
* Get all WooCommerce orders with the status "processing".
*
*/

if (!defined('ABSPATH')) {
    die('No access');
}

add_action('rest_api_init', 'kvtoimitus_register_order_endpoint');

function kvtoimitus_register_order_endpoint() {
    register_rest_route('kirjavalitys/v1', '/orders', array(
        'methods' => 'GET',
        'callback' => 'kvtoimitus_get_orders_callback',
        'permission_callback' => 'kvtoimitus_order_permission_callback'
    ));
}

function kvtoimitus_get_orders_callback($data) {

    if (class_exists('WooCommerce')) {
        $orders = wc_get_orders(array(
            'status' => 'processing',
            'limit' => -1,
        ));

        $formatted_orders = array();

        if ($orders) {
            foreach ($orders as $order) {
                $formatted_order = array(
                    'order_id' => $order->get_id(),
                    'is_paid' => $order->is_paid(),
                    'order_number' => $order->get_order_number(),
                    'customer_id' => $order->get_customer_id(),
                    'status' => $order->get_status(),
                    'all_items_subtotal' => $order->get_subtotal(),

                    // Shipping details
                    'shipping_method' => $order->get_shipping_method(),
                    'shipping_total' => $order->get_shipping_total(),
                    'shipping_first_name' => $order->get_shipping_first_name(),
                    'shipping_last_name' => $order->get_shipping_last_name(),
                    'shipping_company' => $order->get_shipping_company(),
                    'shipping_address_1' => $order->get_shipping_address_1(),
                    'shipping_address_2' => $order->get_shipping_address_2(),
                    'shipping_city' => $order->get_shipping_city(),
                    'shipping_state' => $order->get_shipping_state(),
                    'shipping_postcode' => $order->get_shipping_postcode(),
                    'shipping_country' => $order->get_shipping_country(),

                    'delivery_phone' => $order->get_billing_phone(),
                    'delivery_email' => $order->get_billing_email(),

                    // Dates
                    'date_created' => $order->get_date_created() ? $order->get_date_created()->format('d-m-Y H:i:s') : "",
                    'date_modified' => $order->get_date_modified() ? $order->get_date_modified()->format('d-m-Y H:i:s') : "",
                    'date_completed' => $order->get_date_completed() ? $order->get_date_completed()->format('d-m-Y H:i:s') : "",
                    
                    'products' => array(),
                    'bundles' => array(),
                );
        
                $items = $order->get_items();
               
                foreach ($items as $item) {
         
                    $product_id = $item->get_product_id();
                    $variation_id = $item->get_variation_id();

                    if ($variation_id) {
                        $product = wc_get_product($variation_id);
                        $formatted_order['products'][] = array(
                            'product_id' => $item->get_product_id(),
                            'variation_id' => $item->get_variation_id(),
                            'name' => $product->get_name(),
                            'sku' => $product->get_sku(),
                            'total' => $item->get_total(),
                            'regular_price' => $product->get_regular_price(),
                            'sale_price' => $product->get_sale_price(),
                            'quantity' => $item->get_quantity(),
                            'type' => $product->get_type(),
                        );
                    }

                    if ($product_id && (!$variation_id || $variation_id === 0)) {
                        $product = wc_get_product($product_id);
                        if ($product->get_type() === 'woosb') {
                            $formatted_order['bundles'][] = array(
                                'product_id' => $item->get_product_id(),
                                'name' => $product->get_name(),
                                'is_bundle' => true,
                                'total' => $item->get_total(),
                                'regular_price' => $product->get_regular_price(),
                                'sale_price' => $product->get_sale_price(),
                                'quantity' => $item->get_quantity(),
                                'type' => $product->get_type(),
                            );
                        } else {
                        $formatted_order['products'][] = array(
                            'product_id' => $item->get_product_id(),
                            'variation_id' => $item->get_variation_id(),
                            'name' => $product->get_name(),
                            'sku' => $product->get_sku(),
                            'total' => $item->get_total(),
                            'regular_price' => $product->get_regular_price(),
                            'sale_price' => $product->get_sale_price(),
                            'quantity' => $item->get_quantity(),
                            'type' => $product->get_type(),
                        );
                        }
                    }
                }

                $formatted_orders[] = $formatted_order;
            }
            return new WP_REST_Response($formatted_orders, 200);
        } else {
            return new WP_Error('no_orders_found', 'Tilauksia ei löytynyt', array('status' => 404));
        }
    } else {
        return new WP_Error('woocommerce_not_active', 'WooCommercea ei ole aktivoitu', array('status' => 500));
    }
}

function kvtoimitus_order_permission_callback($request) {
    $api_key = $request->get_header('X-API-KEY');

    $stored_hashed_api_key = get_option('kvtoimitus_api_key');

    if (password_verify($api_key, $stored_hashed_api_key)) {
        return true;
    }
    return new WP_Error('rest_forbidden', 'Invalid API key.', array('status' => 401));
}
