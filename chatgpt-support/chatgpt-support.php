<?php
/**
 * Plugin Name: WordPress ChatGPT Support Plugin
 * Plugin URI:  https://plusinnovative.com
 * Description: WordPress plugin for integrating OpenAI ChatGPT to provide automated chatbot support.
 * Version:     1.0
 * Author:      Slobodan Miletic
 * Author URI:  https://plusinnovative.com
 */

if (!defined('ABSPATH')) {
    exit; // Direct access is not allowed
}

// Adding a menu to the admin panel
function chatgpt_support_menu() {
    add_options_page('ChatGPT Support', 'ChatGPT Support', 'manage_options', 'chatgpt-support', 'chatgpt_support_settings_page');
}
add_action('admin_menu', 'chatgpt_support_menu');

// Settings page
function chatgpt_support_settings_page() {
    ?>
    <div class="wrap">
        <h2>ChatGPT Support - Settings</h2>
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
        wp_send_json_error('Error: No message!');
        return;
    }

    $message = sanitize_text_field($_POST['message']);
    $api_key = chatgpt_decrypt_api_key(get_option('chatgpt_api_key'));

    if (empty($api_key)) {
        wp_send_json_error('API key is not set. Please contact the administrator.');
        return;
    }

    // Rate limiting
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $rate_limit_key = 'chatgpt_rate_limit_' . $user_ip;
    $rate_limit = get_transient($rate_limit_key);
    
    if ($rate_limit && $rate_limit >= 10) {
        wp_send_json_error('Too many requests. Please wait a few minutes.');
        return;
    }
    
    set_transient($rate_limit_key, ($rate_limit ? $rate_limit + 1 : 1), 60);

    // Cache check
    $cache_key = 'chatgpt_response_' . md5($message);
    $cached_response = get_transient($cache_key);
    
    if ($cached_response !== false) {
        wp_send_json_success($cached_response);
        return;
    }

    // Call to OpenAI API
    $url = "https://api.openai.com/v1/chat/completions";

    $data = array(
        "model" => "gpt-3.5-turbo",  // ChatGPT model version
        "messages" => array(
            array("role" => "system", "content" => "You are customer support."),
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
        error_log('ChatGPT API Error: ' . $response->get_error_message());
        wp_send_json_error("Error communicating with the OpenAI API. Please try again.");
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (!isset($result['choices'][0]['message']['content'])) {
        error_log('ChatGPT API Invalid Response: ' . $body);
        wp_send_json_error("Error: Invalid API response.");
        return;
    }

    $response_content = $result['choices'][0]['message']['content'];
    
    // Cache the response for 1 hour
    set_transient($cache_key, $response_content, 3600);
    
    wp_send_json_success($response_content);
}
add_action('wp_ajax_chatgpt_request', 'chatgpt_request_handler');
add_action('wp_ajax_nopriv_chatgpt_request', 'chatgpt_request_handler');

// Registering options
function chatgpt_support_settings_init() {
    register_setting('chatgpt_support_options', 'chatgpt_api_key', array(
        'sanitize_callback' => 'chatgpt_encrypt_api_key'
    ));

    add_settings_section('chatgpt_support_section', 'API Settings', null, 'chatgpt-support');

    add_settings_field('chatgpt_api_key', 'OpenAI API Key', 'chatgpt_api_key_callback', 'chatgpt-support', 'chatgpt_support_section');
}
add_action('admin_init', 'chatgpt_support_settings_init');

function chatgpt_encrypt_api_key($api_key) {
    if (empty($api_key)) {
        return '';
    }
    return base64_encode($api_key);
}

function chatgpt_decrypt_api_key($encrypted_key) {
    if (empty($encrypted_key)) {
        return '';
    }
    return base64_decode($encrypted_key);
}

function chatgpt_api_key_callback() {
    $api_key = chatgpt_decrypt_api_key(get_option('chatgpt_api_key'));
    wp_nonce_field('chatgpt_api_key_nonce', 'chatgpt_api_key_nonce');
    echo '<input type="password" name="chatgpt_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
    echo '<p class="description">Enter your OpenAI API key. Leave blank to keep the current key.</p>';
}

// Adding CSS and JavaScript files
function chatgpt_enqueue_scripts() {
    wp_enqueue_script('chatgpt-widget', plugins_url('chatgpt-widget.js', __FILE__), array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'chatgpt_enqueue_scripts');

function chatgpt_enqueue_styles() {
    wp_enqueue_style('chatgpt-style', plugins_url('chatgpt-style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'chatgpt_enqueue_styles');