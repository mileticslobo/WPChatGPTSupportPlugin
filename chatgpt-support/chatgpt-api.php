<?php
if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

function chatgpt_get_response($message) {
    $api_key = get_option('chatgpt_api_key');

    if (!$api_key) {
        return 'API key is not set!';
    }

    $url = 'https://api.openai.com/v1/chat/completions';

    $data = array(
        'model' => 'gpt-4',
        'messages' => array(
            array('role' => 'system', 'content' => 'You are customer support.'),
            array('role' => 'user', 'content' => $message)
        ),
        'temperature' => 0.7
    );

    $args = array(
        'body'    => json_encode($data),
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'method'  => 'POST'
    );

    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
        return 'Error communicating with the API!';
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    return $result['choices'][0]['message']['content'] ?? 'No response!';
}

// AJAX handler
function chatgpt_ajax_handler() {
    if (!isset($_POST['message'])) {
        wp_send_json_error('No message!');
    }

    $response = chatgpt_get_response(sanitize_text_field($_POST['message']));
    wp_send_json_success($response);
}
add_action('wp_ajax_chatgpt_request', 'chatgpt_ajax_handler');
add_action('wp_ajax_nopriv_chatgpt_request', 'chatgpt_ajax_handler');