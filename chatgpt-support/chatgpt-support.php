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

function chatgpt_request_handler() {
    if (!isset($_POST['message']) || empty($_POST['message'])) {
        wp_send_json_error('Greška: Nema poruke!');
        return;
    }

    $message = sanitize_text_field($_POST['message']);
    $api_key = get_option('chatgpt_api_key');

    // Poziv prema OpenAI API-u
    $url = "https://api.openai.com/v1/chat/completions";

    $data = array(
        "model" => "gpt-3.5-turbo",  // Besplatna verzija ChatGPT modela
        "messages" => array(
            array("role" => "system", "content" => "Ti si korisnička podrška."),
            array("role" => "user", "content" => $message)
        ),
        "temperature" => 0.7
    );

    $args = array(
        "body"    => json_encode($data),
        "headers" => array(
            "Content-Type"  => "application/json",
            "Authorization" => "Bearer " . $api_key
        ),
        "method"  => "POST"
    );

    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
        wp_send_json_error("Greška u komunikaciji sa OpenAI API-jem.");
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (!isset($result['choices'][0]['message']['content'])) {
        wp_send_json_error("Greška: Neispravan odgovor API-ja.");
        return;
    }

    wp_send_json_success($result['choices'][0]['message']['content']);
}
add_action('wp_ajax_chatgpt_request', 'chatgpt_request_handler');
add_action('wp_ajax_nopriv_chatgpt_request', 'chatgpt_request_handler');

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

// Dodavanje CSS i JavaScript fajlova
function chatgpt_enqueue_scripts() {
    wp_enqueue_script('chatgpt-widget', plugins_url('chatgpt-widget.js', __FILE__), array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'chatgpt_enqueue_scripts');

function chatgpt_enqueue_styles() {
    wp_enqueue_style('chatgpt-style', plugins_url('chatgpt-style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'chatgpt_enqueue_styles');