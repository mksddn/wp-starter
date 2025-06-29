<?php
/**
 * Telegram Handler for Forms Handler Plugin
 * 
 * Handles sending form submissions to Telegram chats
 */

class TelegramHandler {
    
    /**
     * Send form submission to Telegram
     * 
     * @param string $bot_token Telegram bot token
     * @param string $chat_ids Comma-separated chat IDs
     * @param array $form_data Form submission data
     * @param string $form_title Form title
     * @return true|WP_Error Success or error
     */
    public static function send_message($bot_token, $chat_ids, $form_data, $form_title) {
        if (empty($bot_token) || empty($chat_ids)) {
            return new WP_Error('telegram_config_error', 'Telegram bot token or chat IDs not configured', array('status' => 500));
        }
        
        // Формируем сообщение для Telegram
        $message = "📝 *New form submission: {$form_title}*\n\n";
        $message .= "📅 Date: " . current_time('d.m.Y H:i:s') . "\n\n";
        
        foreach ($form_data as $key => $value) {
            $message .= "• *" . ucfirst($key) . "*: " . $value . "\n";
        }
        
        $message .= "\n🌐 Website: " . get_site_url();
        
        // Подготавливаем данные для отправки
        $telegram_data = array(
            'text' => $message,
            'parse_mode' => 'Markdown'
        );
        
        // Отправляем в каждый чат
        $chat_ids_array = array_map('trim', explode(',', $chat_ids));
        $results = array();
        
        foreach ($chat_ids_array as $chat_id) {
            $chat_id = trim($chat_id);
            if (empty($chat_id)) continue;
            
            $telegram_data['chat_id'] = $chat_id;
            
            $response = wp_remote_post("https://api.telegram.org/bot{$bot_token}/sendMessage", array(
                'body' => $telegram_data,
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded'
                )
            ));
            
            if (is_wp_error($response)) {
                $results[] = array(
                    'chat_id' => $chat_id,
                    'success' => false,
                    'error' => $response->get_error_message()
                );
            } else {
                $body = wp_remote_retrieve_body($response);
                $result = json_decode($body, true);
                
                if ($result && isset($result['ok']) && $result['ok']) {
                    $results[] = array(
                        'chat_id' => $chat_id,
                        'success' => true,
                        'message_id' => $result['result']['message_id'] ?? null
                    );
                } else {
                    $results[] = array(
                        'chat_id' => $chat_id,
                        'success' => false,
                        'error' => $result['description'] ?? 'Unknown error'
                    );
                }
            }
        }
        
        // Проверяем, был ли хотя бы один успешный отправлен
        $success_count = 0;
        foreach ($results as $result) {
            if ($result['success']) {
                $success_count++;
            }
        }
        
        if ($success_count > 0) {
            return true;
        } else {
            $error_message = "Failed to send to any Telegram chats: " . json_encode($results);
            return new WP_Error('telegram_send_error', $error_message, array('status' => 500));
        }
    }
} 