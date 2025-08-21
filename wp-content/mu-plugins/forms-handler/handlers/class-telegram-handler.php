<?php
namespace FormsHandler;

use WP_Error;

/**
 * Handles Telegram notifications
 */
class TelegramHandler {


    /**
     * Send message to Telegram
     */
    public static function send_message($bot_token, $chat_ids, $form_data, string $form_title): WP_Error|true {
        if (!$bot_token || !$chat_ids) {
            return new WP_Error('telegram_config_error', 'Telegram bot token or chat IDs not configured');
        }

        $chat_ids_array = array_map('trim', explode(',', (string) $chat_ids));
        $success_count = 0;
        $error_messages = [];

        foreach ($chat_ids_array as $chat_id) {
            $chat_id = trim($chat_id);
            if ($chat_id === '' || $chat_id === '0') {
                continue;
            }

            $message = self::build_telegram_message($form_data, $form_title);
            $result = self::send_telegram_request($bot_token, $chat_id, $message);

            if (is_wp_error($result)) {
                $error_messages[] = 'Chat ' . $chat_id . ': ' . $result->get_error_message();
            } else {
                $success_count++;
            }
        }

        if ($success_count === 0) {
            return new WP_Error('telegram_send_error', 'Failed to send to any Telegram chat: ' . implode(', ', $error_messages));
        }

        return true;
    }


    /**
     * Build Telegram message
     */
    private static function build_telegram_message($form_data, string $form_title): string {
        $message = "📝 *New Form Submission*\n\n";
        $message .= "📋 *Form:* " . $form_title . "\n";
        $message .= "🕐 *Time:* " . current_time('d.m.Y H:i:s') . "\n\n";
        $message .= "*Form Data:*\n";

        foreach ($form_data as $key => $value) {
            $message .= "• *" . ucfirst((string) $key) . ":* " . $value . "\n";
        }

        return $message;
    }


    /**
     * Send request to Telegram API
     */
    private static function send_telegram_request($bot_token, string $chat_id, string $message): WP_Error|true {
        $url = sprintf('https://api.telegram.org/bot%s/sendMessage', $bot_token);

        $response = wp_remote_post($url, [
            'body' => [
                'chat_id'    => $chat_id,
                'text'       => $message,
                'parse_mode' => 'Markdown',
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('telegram_request_error', 'Failed to send Telegram request: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['ok']) || !$data['ok']) {
            $error_message = $data['description'] ?? 'Unknown error';
            return new WP_Error('telegram_api_error', 'Telegram API error: ' . $error_message);
        }

        return true;
    }


}
