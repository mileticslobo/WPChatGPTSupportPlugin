<?php
/**
 * Plugin Name: ChatGPT Support
 * Plugin URI:  https://tvoj-sajt.com
 * Description: Plugin za korisničku podršku pomoću ChatGPT-a.
 * Version:     1.0
 * Author:      Tvoje ime
 * Author URI:  https://tvoj-sajt.com
 */

if (!defined('ABSPATH')) {
    exit; // Direktan pristup nije dozvoljen
}

// Dodavanje menija u admin panel
function chatgpt_support_menu() {
    add_options_page('ChatGPT Support', 'ChatGPT Support', 'manage_options', 'chatgpt-support', 'chatgpt_support_settings_page');
}
add_action('admin_menu', 'chatgpt_support_menu');

// Stranica sa podešavanjima
function chatgpt_support_settings_page() {
    ?>
    <div class="wrap">
        <h2>ChatGPT Support - Podešavanja</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('chatgpt_support_options');
            do_settings_sections('chatgpt-support');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Registracija opcija
function chatgpt_support_settings_init() {
    register_setting('chatgpt_support_options', 'chatgpt_api_key');

    add_settings_section('chatgpt_support_section', 'API Podešavanja', null, 'chatgpt-support');

    add_settings_field('chatgpt_api_key', 'OpenAI API ključ', 'chatgpt_api_key_callback', 'chatgpt-support', 'chatgpt_support_section');
}
add_action('admin_init', 'chatgpt_support_settings_init');

function chatgpt_api_key_callback() {
    $api_key = get_option('chatgpt_api_key');
    echo '<input type="text" name="chatgpt_api_key" value="' . esc_attr($api_key) . '" />';
}