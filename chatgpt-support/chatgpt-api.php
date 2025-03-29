<?php
if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

function chatgpt_get_response($message) {
    $api_key = get_option('chatgpt_api_key');

    if (!$api_key) {
        return 'Error: API key is not set. Please configure it in the settings.';
    }

    $url = 'https://api.openai.com/v1/chat/completions';

    $data = array(
        'model' => 'gpt-3.5-turbo', // Ensure consistency with other files
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
        error_log('ChatGPT API Error: ' . $response->get_error_message());
        return 'Error: Unable to communicate with the API. Please try again later.';
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (!isset($result['choices'][0]['message']['content'])) {
        error_log('ChatGPT API Invalid Response: ' . $body);
        return 'Error: Invalid response from the API.';
    }

    return $result['choices'][0]['message']['content'];
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