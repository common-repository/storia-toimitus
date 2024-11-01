<?php

/*
*
* Sub-menu for API key generation.
* Allows users to create an API key, which is then stored in WordPress options.
*
*/

if (!defined('ABSPATH')) {
    die('No access');
}

add_action('admin_menu', 'kvtoimitus_apikey_generation_menu');

function kvtoimitus_apikey_generation_menu() {
    add_menu_page(
        esc_html__('Storia', 'storia-toimitus'), // Submenu page title
        esc_html__('Storia', 'storia-toimitus'), // Submenu label
        'manage_options', // Capability required to access the page
        'kvtoimitus-api-settings', // Submenu page slug
        'kvtoimitus_api_page_content', // Callback function to display the page content
        'dashicons-store', // Menu icon
        3 // Position in the menu
    );
}

function kvtoimitus_generatePassword(int $length = 64): string {
    $chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789";

    $password = "";
    while (preg_match('/^(?:\d+|[A-Z]+|[a-z]+)?$/', $password) != 0) {
        $generated = '';
        for ($i = 0; $i < $length; $i++) {
            $generated .= $chars[wp_rand(0, strlen($chars) - 1)];
        }

        $password = $generated;
    }

    return $password;
}

function kvtoimitus_api_page_content() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Api-avaimen luonti', 'storia-toimitus') ?></h1>
        
        <p><?php esc_html_e('Luo tästä API-avain ja toimita se sen jälkeen Storia Logistiikkaan. Api-avain mahdollistaa Storian toiminnoille pääsyn tarvittaviin tilausten tietoihin.', 'storia-toimitus') ?></p>
        <p><?php esc_html_e('Jos luot uuden API-avaimen, vanhan avaimen toiminta lakkaa ja yhteys katkeaa. Pidä huoli, että Storialla on tiedossaan ajantasainen API-avain.', 'storia-toimitus') ?></p>
        
        <div>
            <form method="post">
                <?php wp_nonce_field('kvtoimitus_nonce_action', 'kvtoimitus_nonce'); ?>
                <input type="submit" name="generate_api_key" value="<?php esc_attr_e('Luo uusi API-avain', 'storia-toimitus') ?>">
            </form>
        </div>
        
        <?php

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kvtoimitus_nonce'])) {
        $nonce_field = sanitize_text_field( wp_unslash( $_POST['kvtoimitus_nonce'] ) );

        if ( ! wp_verify_nonce( $nonce_field, 'kvtoimitus_nonce_action' ) ) {
            die('Nonce verification failed!');
        }

        $apikey = kvtoimitus_generatePassword();
        esc_html_e('Api-avain on: ', 'storia-toimitus');
        echo esc_html($apikey);
        
        $hashedPassword = password_hash($apikey, PASSWORD_DEFAULT);
        $hashedPassword = sanitize_text_field($hashedPassword);
        update_option('kvtoimitus_api_key', $hashedPassword);
    }
        ?>
    </div>
    <?php
}
