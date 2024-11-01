<?php

/*
* Plugin Name: Storian Toimitus ja Tuotesaldot
* Plugin URI: https://www.storia.fi/fi
* Description: WooCommerce integraatio Storian toimitukseen
* Version: 1.0.0
* Author: Storia Oy
* License: GPL v2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Requires Plugins: woocommerce
* Text Domain: storia-toimitus
* Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    die('No access');
}

include_once('api-endpoints/kvtoimitus-get-orders.php');
include_once('api-endpoints/kvtoimitus-get-order-by-id.php');
include_once('api-endpoints/kvtoimitus-update-order-status.php');
include_once('api-endpoints/kvtoimitus-update-stock.php');
include_once('api-endpoints/kvtoimitus-update-inventory.php');
include_once('api-key-generation/kvtoimitus-api-key-generation.php');

add_action('init', 'kvtoimitus_load_translated_textdomain');

function kvtoimitus_load_translated_textdomain()
{
    load_plugin_textdomain('storia-toimitus', false, dirname(plugin_basename(__FILE__)) . '/languages');
}