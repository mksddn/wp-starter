<?php
/*
Plugin Name: Forms Handler
Description: Unified form processing system with REST API support
Version: 1.0
Author: mksddn
*/

// Подключаем Telegram Handler
require_once __DIR__ . '/telegram-handler.php';

// Подключаем Google Sheets Handler
require_once __DIR__ . '/google-sheets-handler.php';

// Регистрация кастомного типа записи Forms
add_action(
    'init',
    function () {
        register_post_type(
            'forms',
            array(
                'labels'              => array(
                    'name'               => 'Forms',
                    'singular_name'      => 'Form',
                    'menu_name'          => 'Forms',
                    'add_new'            => 'Add Form',
                    'add_new_item'       => 'Add New Form',
                    'edit_item'          => 'Edit Form',
                    'new_item'           => 'New Form',
                    'view_item'          => 'View Form',
                    'search_items'       => 'Search Forms',
                    'not_found'          => 'No forms found',
                    'not_found_in_trash' => 'No forms found in trash',
                ),
                'public'              => true,
                'show_ui'             => true,
                'show_in_menu'        => true,
                'show_in_rest'        => true,
                'rest_base'           => 'forms',
                'capability_type'     => 'post',
                'hierarchical'        => false,
                'rewrite'             => array( 'slug' => 'forms' ),
                'supports'            => array( 'title', 'custom-fields' ),
                'menu_icon'           => 'dashicons-feedback',
                'show_in_admin_bar'   => true,
                'can_export'          => true,
                'has_archive'         => false,
                'exclude_from_search' => true,
                'publicly_queryable'  => true,
                'capabilities'        => array(
                    'create_posts'       => 'manage_options',
                    'edit_post'          => 'manage_options',
                    'read_post'          => 'read',
                    'delete_post'        => 'manage_options',
                    'edit_posts'         => 'manage_options',
                    'edit_others_posts'  => 'manage_options',
                    'publish_posts'      => 'manage_options',
                    'read_private_posts' => 'read',
                    'delete_posts'       => 'manage_options',
                ),
            )
        );

        // Регистрация кастомного типа записи Submissions
        register_post_type(
            'form_submissions',
            array(
                'labels'              => array(
                    'name'               => 'Form Submissions',
                    'singular_name'      => 'Submission',
                    'menu_name'          => 'Submissions',
                    'add_new'            => 'Add Submission',
                    'add_new_item'       => 'Add New Submission',
                    'edit_item'          => 'Edit Submission',
                    'new_item'           => 'New Submission',
                    'view_item'          => 'View Submission',
                    'search_items'       => 'Search Submissions',
                    'not_found'          => 'No submissions found',
                    'not_found_in_trash' => 'No submissions found in trash',
                ),
                'public'              => false,
                'show_ui'             => true,
                'show_in_menu'        => true,
                'show_in_rest'        => false,
                'capability_type'     => 'post',
                'hierarchical'        => false,
                'supports'            => array( 'title', 'custom-fields' ),
                'menu_icon'           => 'dashicons-list-view',
                'show_in_admin_bar'   => false,
                'can_export'          => true,
                'has_archive'         => false,
                'exclude_from_search' => true,
                'publicly_queryable'  => false,
                'capabilities'        => array(
                    'create_posts'       => false,
                    'edit_post'          => 'manage_options',
                    'read_post'          => 'manage_options',
                    'delete_post'        => 'manage_options',
                    'edit_posts'         => 'manage_options',
                    'edit_others_posts'  => 'manage_options',
                    'publish_posts'      => 'manage_options',
                    'read_private_posts' => 'manage_options',
                    'delete_posts'       => 'manage_options',
                ),
            )
        );

        // Сбрасываем permalinks для корректной работы REST API
        flush_rewrite_rules();
    }
);

// Добавление мета-полей для форм
add_action( 'add_meta_boxes', 'add_forms_meta_boxes' );


function add_forms_meta_boxes() {
    add_meta_box(
        'form_settings',
        'Form Settings',
        'render_form_settings_meta_box',
        'forms',
        'normal',
        'high'
    );
}


// Добавление мета-полей для заявок
add_action( 'add_meta_boxes', 'add_submissions_meta_boxes' );


function add_submissions_meta_boxes() {
    add_meta_box(
        'submission_data',
        'Submission Data',
        'render_submission_data_meta_box',
        'form_submissions',
        'normal',
        'high'
    );

    add_meta_box(
        'submission_info',
        'Submission Info',
        'render_submission_info_meta_box',
        'form_submissions',
        'side',
        'high'
    );
}


function render_form_settings_meta_box( $post ) {
    wp_nonce_field( 'save_form_settings', 'form_settings_nonce' );

    // Проверяем, есть ли временные данные с ошибкой JSON
    $json_error       = get_transient( 'fields_config_json_error_' . get_current_user_id() );
    $json_error_value = get_transient( 'fields_config_json_value_' . get_current_user_id() );

    $recipients            = get_post_meta( $post->ID, '_recipients', true );
    $bcc_recipient         = get_post_meta( $post->ID, '_bcc_recipient', true );
    $subject               = get_post_meta( $post->ID, '_subject', true );
    $fields_config         = get_post_meta( $post->ID, '_fields_config', true );
    $telegram_bot_token    = get_post_meta( $post->ID, '_telegram_bot_token', true );
    $telegram_chat_ids     = get_post_meta( $post->ID, '_telegram_chat_ids', true );
    $send_to_telegram      = get_post_meta( $post->ID, '_send_to_telegram', true );
    $send_to_sheets        = get_post_meta( $post->ID, '_send_to_sheets', true );
    $sheets_spreadsheet_id = get_post_meta( $post->ID, '_sheets_spreadsheet_id', true );
    $sheets_sheet_name     = get_post_meta( $post->ID, '_sheets_sheet_name', true );
    $save_to_admin         = get_post_meta( $post->ID, '_save_to_admin', true );

    if ($json_error && $json_error_value !== false) {
        $fields_config = $json_error_value;
    }

    if (! $fields_config) {
        $fields_config = json_encode(
            array(
                array(
                    'name'     => 'name',
                    'label'    => 'Name',
                    'type'     => 'text',
                    'required' => true,
                ),
                array(
                    'name'     => 'email',
                    'label'    => 'Email',
                    'type'     => 'email',
                    'required' => true,
                ),
                array(
                    'name'     => 'message',
                    'label'    => 'Message',
                    'type'     => 'textarea',
                    'required' => true,
                ),
            )
        );
    }

    // Показываем уведомление об ошибке, если был невалидный JSON
    if ($json_error) {
        echo '<div class="notice notice-error"><p>Ошибка: Некорректный JSON в Fields Configuration! Проверьте синтаксис.</p></div>';
        delete_transient( 'fields_config_json_error_' . get_current_user_id() );
        delete_transient( 'fields_config_json_value_' . get_current_user_id() );
    }

    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="recipients">Recipients (comma separated)</label></th>
            <td>
                <input type="text" name="recipients" id="recipients" value="<?php echo esc_attr( $recipients ); ?>" class="regular-text" />
                <p class="description">Email addresses separated by commas</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bcc_recipient">BCC Recipient</label></th>
            <td>
                <input type="email" name="bcc_recipient" id="bcc_recipient" value="<?php echo esc_attr( $bcc_recipient ); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="subject">Email Subject</label></th>
            <td>
                <input type="text" name="subject" id="subject" value="<?php echo esc_attr( $subject ); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="fields_config">Fields Configuration</label></th>
            <td>
                <textarea name="fields_config" id="fields_config" rows="10" cols="50" class="large-text code"><?php echo esc_textarea( $fields_config ); ?></textarea>
                <p class="description">JSON configuration of form fields. Example: [{"name": "name", "label": "Name", "type": "text", "required": true}]</p>
            </td>
        </tr>
        
        <tr>
            <th scope="row" colspan="2">
                <h3 style="margin: 0; padding: 10px 0; border-bottom: 1px solid #ccc;">Telegram Notifications</h3>
            </th>
        </tr>
        <tr>
            <th scope="row">
                <label>
                    <input type="checkbox" name="send_to_telegram" id="send_to_telegram" value="1" <?php checked( $send_to_telegram, '1' ); ?> />
                    Send to Telegram
                </label>
            </th>
            <td>
                <p class="description">Enable Telegram notifications for this form</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="telegram_bot_token">Telegram Bot Token</label></th>
            <td>
                <input type="text" name="telegram_bot_token" id="telegram_bot_token" value="<?php echo esc_attr( $telegram_bot_token ); ?>" class="regular-text" />
                <p class="description">Your Telegram bot token (e.g., 123456789:ABCdefGHIjklMNOpqrsTUVwxyz)</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="telegram_chat_ids">Telegram Chat IDs</label></th>
            <td>
                <input type="text" name="telegram_chat_ids" id="telegram_chat_ids" value="<?php echo esc_attr( $telegram_chat_ids ); ?>" class="regular-text" />
                <p class="description">Chat IDs separated by commas (e.g., -1001234567890, -1009876543210)</p>
            </td>
        </tr>
        
        <tr>
            <th scope="row" colspan="2">
                <h3 style="margin: 0; padding: 10px 0; border-bottom: 1px solid #ccc;">Google Sheets Integration</h3>
            </th>
        </tr>
        <tr>
            <th scope="row">
                <label>
                    <input type="checkbox" name="send_to_sheets" id="send_to_sheets" value="1" <?php checked( $send_to_sheets, '1' ); ?> />
                    Send to Google Sheets
                </label>
            </th>
            <td>
                <p class="description">Enable Google Sheets integration for this form</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="sheets_spreadsheet_id">Spreadsheet ID</label></th>
            <td>
                <input type="text" name="sheets_spreadsheet_id" id="sheets_spreadsheet_id" value="<?php echo esc_attr( $sheets_spreadsheet_id ); ?>" class="regular-text" />
                <p class="description">Google Sheets spreadsheet ID (from URL: docs.google.com/spreadsheets/d/SPREADSHEET_ID)</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="sheets_sheet_name">Sheet Name</label></th>
            <td>
                <input type="text" name="sheets_sheet_name" id="sheets_sheet_name" value="<?php echo esc_attr( $sheets_sheet_name ); ?>" class="regular-text" />
                <p class="description">Sheet name (optional, defaults to first sheet)</p>
            </td>
        </tr>
        
        <tr>
            <th scope="row" colspan="2">
                <h3 style="margin: 0; padding: 10px 0; border-bottom: 1px solid #ccc;">Admin Panel Storage</h3>
            </th>
        </tr>
        <tr>
            <th scope="row">
                <label>
                    <input type="checkbox" name="save_to_admin" id="save_to_admin" value="1" <?php checked( $save_to_admin, '1' ); ?> />
                    Save submissions to admin panel
                </label>
            </th>
            <td>
                <p class="description">Enable saving form submissions to admin panel for viewing and export</p>
            </td>
        </tr>
    </table>
    <?php
}


function render_submission_data_meta_box( $post ) {
    $submission_data = get_post_meta( $post->ID, '_submission_data', true );
    $data_array      = json_decode( $submission_data, true );

    if (! $data_array) {
        echo '<p>No data available</p>';
        return;
    }

    echo '<table class="form-table">';
    foreach ($data_array as $key => $value) {
        echo '<tr>';
        echo '<th scope="row"><label>' . esc_html( $key ) . '</label></th>';
        echo '<td>' . esc_html( $value ) . '</td>';
        echo '</tr>';
    }

    echo '</table>';
}


function render_submission_info_meta_box( $post ) {
    $form_title      = get_post_meta( $post->ID, '_form_title', true );
    $submission_date = get_post_meta( $post->ID, '_submission_date', true );
    $submission_ip   = get_post_meta( $post->ID, '_submission_ip', true );
    $user_agent      = get_post_meta( $post->ID, '_submission_user_agent', true );

    echo '<table class="form-table">';
    echo '<tr><th>Form:</th><td>' . esc_html( $form_title ?: 'Unknown' ) . '</td></tr>';
    echo '<tr><th>Date:</th><td>' . esc_html( $submission_date ? date( 'd.m.Y H:i:s', strtotime( $submission_date ) ) : 'Unknown' ) . '</td></tr>';
    echo '<tr><th>IP Address:</th><td>' . esc_html( $submission_ip ?: 'Unknown' ) . '</td></tr>';
    echo '<tr><th>User Agent:</th><td>' . esc_html( $user_agent ?: 'Unknown' ) . '</td></tr>';
    echo '</table>';
}


// Сохранение мета-полей
add_action( 'save_post', 'save_form_settings' );


function save_form_settings( $post_id ) {
    if (! isset( $_POST['form_settings_nonce'] ) || ! wp_verify_nonce( $_POST['form_settings_nonce'], 'save_form_settings' )) {
        return;
    }

    if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) {
        return;
    }

    if (! current_user_can( 'edit_post', $post_id )) {
        return;
    }

    if (isset( $_POST['recipients'] )) {
        update_post_meta( $post_id, '_recipients', sanitize_text_field( $_POST['recipients'] ) );
    }

    if (isset( $_POST['bcc_recipient'] )) {
        update_post_meta( $post_id, '_bcc_recipient', sanitize_email( $_POST['bcc_recipient'] ) );
    }

    if (isset( $_POST['subject'] )) {
        update_post_meta( $post_id, '_subject', sanitize_text_field( $_POST['subject'] ) );
    }

    if (isset( $_POST['fields_config'] )) {
        $fields_config = wp_unslash( $_POST['fields_config'] );
        // Проверяем, что это валидный JSON, чтобы избежать сохранения некорректных данных
        if (json_decode( $fields_config ) !== null) {
            update_post_meta( $post_id, '_fields_config', $fields_config );
        } else {
            // Сохраняем ошибку и введённые данные во временное хранилище
            set_transient( 'fields_config_json_error_' . get_current_user_id(), true, 60 );
            set_transient( 'fields_config_json_value_' . get_current_user_id(), $fields_config, 60 );
        }
    }

    if (isset( $_POST['send_to_telegram'] )) {
        update_post_meta( $post_id, '_send_to_telegram', '1' );
    } else {
        update_post_meta( $post_id, '_send_to_telegram', '0' );
    }

    if (isset( $_POST['telegram_bot_token'] )) {
        update_post_meta( $post_id, '_telegram_bot_token', sanitize_text_field( $_POST['telegram_bot_token'] ) );
    }

    if (isset( $_POST['telegram_chat_ids'] )) {
        update_post_meta( $post_id, '_telegram_chat_ids', sanitize_text_field( $_POST['telegram_chat_ids'] ) );
    }

    if (isset( $_POST['send_to_sheets'] )) {
        update_post_meta( $post_id, '_send_to_sheets', '1' );
    } else {
        update_post_meta( $post_id, '_send_to_sheets', '0' );
    }

    if (isset( $_POST['sheets_spreadsheet_id'] )) {
        update_post_meta( $post_id, '_sheets_spreadsheet_id', sanitize_text_field( $_POST['sheets_spreadsheet_id'] ) );
    }

    if (isset( $_POST['sheets_sheet_name'] )) {
        update_post_meta( $post_id, '_sheets_sheet_name', sanitize_text_field( $_POST['sheets_sheet_name'] ) );
    }

    if (isset( $_POST['save_to_admin'] )) {
        update_post_meta( $post_id, '_save_to_admin', '1' );
    } else {
        update_post_meta( $post_id, '_save_to_admin', '0' );
    }
}


// Унифицированный обработчик форм
class FormsHandler {


    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        add_action( 'admin_post_submit_form', array( $this, 'handle_form_submission' ) );
        add_action( 'admin_post_nopriv_submit_form', array( $this, 'handle_form_submission' ) );
    }


    public function register_rest_routes() {
        register_rest_route(
            'wp/v2',
            '/forms/(?P<slug>[a-zA-Z0-9-]+)/submit',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_rest_form_submission' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'slug' => array(
                        'validate_callback' => function ( $param ) {
                            return ! empty( $param );
                        },
                    ),
                ),
            )
        );
    }


    public function handle_rest_form_submission( $request ) {
        $slug      = $request->get_param( 'slug' );
        $form_data = $request->get_json_params();

        if (! $form_data) {
            return new WP_Error( 'invalid_data', 'Invalid form data', array( 'status' => 400 ) );
        }

        // Проверяем размер данных (защита от слишком больших запросов)
        if (count( $form_data ) > 50) {
            return new WP_Error( 'too_many_fields', 'Too many form fields submitted', array( 'status' => 400 ) );
        }

        // Проверяем общий размер данных
        $total_size = 0;
        foreach ($form_data as $key => $value) {
            $total_size += strlen( $key ) + strlen( $value );
        }

        if ($total_size > 100000) { // Максимум 100KB общих данных
            return new WP_Error( 'data_too_large', 'Form data is too large', array( 'status' => 400 ) );
        }

        $result = $this->process_form_submission( $slug, $form_data );

        if (is_wp_error( $result )) {
            $response_data = array(
                'success' => false,
                'message' => $result->get_error_message(),
                'code'    => $result->get_error_code(),
                'status'  => $result->get_error_data()['status'] ?? 500,
            );

            // Добавляем дополнительную информацию для ошибок безопасности
            if ($result->get_error_code() === 'unauthorized_fields') {
                $response_data['unauthorized_fields'] = $result->get_error_data()['unauthorized_fields'] ?? array();
                $response_data['allowed_fields']      = $result->get_error_data()['allowed_fields'] ?? array();
            }

            // Добавляем результаты доставки для ошибок отправки
            if ($result->get_error_code() === 'send_error') {
                $response_data['delivery_results'] = $result->get_error_data()['delivery_results'] ?? array();
            }

            return new WP_REST_Response( $response_data, $response_data['status'] );
        } else {
            return new WP_REST_Response( $result, 200 );
        }
    }


    public function handle_form_submission() {
        // Проверка nonce для безопасности
        if (! isset( $_POST['form_nonce'] ) || ! wp_verify_nonce( $_POST['form_nonce'], 'submit_form_nonce' )) {
            wp_send_json_error( array( 'message' => 'Security error. Please try again.' ) );
        }

        $form_id   = sanitize_text_field( $_POST['form_id'] );
        $form_data = $_POST;

        // Удаляем служебные поля
        unset( $form_data['form_nonce'] );
        unset( $form_data['form_id'] );
        unset( $form_data['action'] );
        unset( $form_data['_wp_http_referer'] );

        // Проверяем размер данных (защита от слишком больших запросов)
        if (count( $form_data ) > 50) {
            wp_send_json_error( array( 'message' => 'Too many form fields submitted.' ) );
        }

        // Проверяем общий размер данных
        $total_size = 0;
        foreach ($form_data as $key => $value) {
            $total_size += strlen( $key ) + strlen( $value );
        }

        if ($total_size > 100000) { // Максимум 100KB общих данных
            wp_send_json_error( array( 'message' => 'Form data is too large.' ) );
        }

        $result = $this->process_form_submission( $form_id, $form_data );

        if (is_wp_error( $result )) {
            $response_data = array(
                'success' => false,
                'message' => $result->get_error_message(),
                'code'    => $result->get_error_code(),
            );

            // Добавляем дополнительную информацию для ошибок безопасности
            if ($result->get_error_code() === 'unauthorized_fields') {
                $response_data['unauthorized_fields'] = $result->get_error_data()['unauthorized_fields'] ?? array();
                $response_data['allowed_fields']      = $result->get_error_data()['allowed_fields'] ?? array();
            }

            // Добавляем результаты доставки для ошибок отправки
            if ($result->get_error_code() === 'send_error') {
                $response_data['delivery_results'] = $result->get_error_data()['delivery_results'] ?? array();
            }

            wp_send_json_error( $response_data );
        } else {
            wp_send_json_success( $result );
        }
    }


    private function process_form_submission( $form_id, $form_data ) {
        // Получаем форму по slug или ID
        $form = get_page_by_path( $form_id, OBJECT, 'forms' );
        if (! $form) {
            $form = get_post( $form_id );
        }

        if (! $form || $form->post_type !== 'forms') {
            return new WP_Error( 'form_not_found', 'Form not found', array( 'status' => 404 ) );
        }

        // Получаем настройки формы
        $recipients            = get_post_meta( $form->ID, '_recipients', true );
        $bcc_recipient         = get_post_meta( $form->ID, '_bcc_recipient', true );
        $subject               = get_post_meta( $form->ID, '_subject', true );
        $fields_config         = get_post_meta( $form->ID, '_fields_config', true );
        $send_to_telegram      = get_post_meta( $form->ID, '_send_to_telegram', true );
        $telegram_bot_token    = get_post_meta( $form->ID, '_telegram_bot_token', true );
        $telegram_chat_ids     = get_post_meta( $form->ID, '_telegram_chat_ids', true );
        $send_to_sheets        = get_post_meta( $form->ID, '_send_to_sheets', true );
        $sheets_spreadsheet_id = get_post_meta( $form->ID, '_sheets_spreadsheet_id', true );
        $sheets_sheet_name     = get_post_meta( $form->ID, '_sheets_sheet_name', true );
        $save_to_admin         = get_post_meta( $form->ID, '_save_to_admin', true );

        if (! $recipients || ! $subject) {
            return new WP_Error( 'form_config_error', 'Form is not configured correctly', array( 'status' => 500 ) );
        }

        // Фильтруем данные формы
        $filtered_form_data = $this->filter_form_data( $form_data, $fields_config );

        // Проверяем результат фильтрации
        if (is_wp_error( $filtered_form_data )) {
            $unauthorized_fields = $this->get_unauthorized_fields( $form_data, $fields_config );
            return new WP_Error(
                'unauthorized_fields',
                'Unauthorized fields detected: ' . implode( ', ', $unauthorized_fields ),
                array(
                    'status'              => 400,
                    'unauthorized_fields' => $unauthorized_fields,
                    'allowed_fields'      => $this->get_allowed_fields( $fields_config ),
                )
            );
        }

        // Валидация данных
        $validation_result = $this->validate_form_data( $filtered_form_data, $fields_config );
        if (is_wp_error( $validation_result )) {
            return $validation_result;
        }

        // Инициализируем результаты отправки
        $delivery_results = array(
            'email'         => array(
                'success' => false,
                'error'   => null,
            ),
            'telegram'      => array(
                'success' => false,
                'error'   => null,
                'enabled' => false,
            ),
            'google_sheets' => array(
                'success' => false,
                'error'   => null,
                'enabled' => false,
            ),
            'admin_storage' => array(
                'success' => false,
                'error'   => null,
                'enabled' => false,
            ),
        );

        // Подготовка email
        $email_result                         = $this->prepare_and_send_email( $recipients, $bcc_recipient, $subject, $filtered_form_data, $form->post_title );
        $delivery_results['email']['success'] = ! is_wp_error( $email_result );
        if (is_wp_error( $email_result )) {
            $delivery_results['email']['error'] = $email_result->get_error_message();
        }

        // Отправка в Telegram
        if ($send_to_telegram && $telegram_bot_token && $telegram_chat_ids) {
            $delivery_results['telegram']['enabled'] = true;
            $telegram_result                         = TelegramHandler::send_message( $telegram_bot_token, $telegram_chat_ids, $filtered_form_data, $form->post_title );
            $delivery_results['telegram']['success'] = ! is_wp_error( $telegram_result );
            if (is_wp_error( $telegram_result )) {
                $delivery_results['telegram']['error'] = $telegram_result->get_error_message();
            }
        }

        // Отправка в Google Sheets
        if ($send_to_sheets && $sheets_spreadsheet_id) {
            $delivery_results['google_sheets']['enabled'] = true;
            $sheets_result                                = GoogleSheetsHandler::send_data( $sheets_spreadsheet_id, $sheets_sheet_name, $filtered_form_data, $form->post_title );
            $delivery_results['google_sheets']['success'] = ! is_wp_error( $sheets_result );
            if (is_wp_error( $sheets_result )) {
                $delivery_results['google_sheets']['error'] = $sheets_result->get_error_message();
            }
        }

        // Сохранение в админке
        if ($save_to_admin === '1') {
            $delivery_results['admin_storage']['enabled'] = true;
            $submission_result                            = $this->save_submission( $form->ID, $filtered_form_data, $form->post_title );
            $delivery_results['admin_storage']['success'] = ! is_wp_error( $submission_result );
            if (is_wp_error( $submission_result )) {
                $delivery_results['admin_storage']['error'] = $submission_result->get_error_message();
            }
        }

        // Проверяем общий успех отправки
        $email_success    = $delivery_results['email']['success'];
        $telegram_success = ! $delivery_results['telegram']['enabled'] || $delivery_results['telegram']['success'];
        $sheets_success   = ! $delivery_results['google_sheets']['enabled'] || $delivery_results['google_sheets']['success'];
        $admin_success    = ! $delivery_results['admin_storage']['enabled'] || $delivery_results['admin_storage']['success'];

        // Возвращаем ошибку только если не удалось отправить ни email, ни Telegram, ни Google Sheets
        if (! $email_success && ! $telegram_success && ! $sheets_success) {
            return new WP_Error(
                'send_error',
                'Failed to send email, Telegram notification, and Google Sheets data',
                array(
                    'status'           => 500,
                    'delivery_results' => $delivery_results,
                )
            );
        }

        // Логирование успешной отправки
        $this->log_form_submission( $form->ID, $filtered_form_data, true );

        return array(
            'success'          => true,
            'message'          => 'Form submitted successfully!',
            'form_id'          => $form->ID,
            'form_title'       => $form->post_title,
            'delivery_results' => $delivery_results,
            'submitted_fields' => array_keys( $filtered_form_data ),
            'timestamp'        => current_time( 'mysql' ),
        );
    }


    /**
     * Получает список неразрешенных полей
     */
    private function get_unauthorized_fields( $form_data, $fields_config ) {
        if (! $fields_config) {
            return array_keys( $form_data );
        }

        $fields = json_decode( $fields_config, true );
        if (! $fields || ! is_array( $fields )) {
            return array_keys( $form_data );
        }

        $allowed_fields = array();
        foreach ($fields as $field) {
            $allowed_fields[] = $field['name'];
        }

        $unauthorized_fields = array();
        foreach ($form_data as $field_name => $field_value) {
            if (! in_array( $field_name, $allowed_fields )) {
                $unauthorized_fields[] = $field_name;
            }
        }

        return $unauthorized_fields;
    }


    /**
     * Получает список разрешенных полей
     */
    private function get_allowed_fields( $fields_config ) {
        if (! $fields_config) {
            return array();
        }

        $fields = json_decode( $fields_config, true );
        if (! $fields || ! is_array( $fields )) {
            return array();
        }

        $allowed_fields = array();
        foreach ($fields as $field) {
            $allowed_fields[] = $field['name'];
        }

        return $allowed_fields;
    }


    /**
     * Фильтрует данные формы, оставляя только разрешенные поля
     */
    private function filter_form_data( $form_data, $fields_config ) {
        if (! $fields_config) {
            return new WP_Error( 'security_error', 'Form fields configuration is missing', array( 'status' => 400 ) );
        }

        $fields = json_decode( $fields_config, true );
        if (! $fields || ! is_array( $fields )) {
            return new WP_Error( 'security_error', 'Invalid form fields configuration', array( 'status' => 400 ) );
        }

        $filtered_data       = array();
        $unauthorized_fields = array();

        // Создаем список разрешенных полей
        $allowed_fields = array();
        foreach ($fields as $field) {
            $allowed_fields[] = $field['name'];
        }

        // Извлекаем только разрешенные поля
        foreach ($form_data as $field_name => $field_value) {
            if (in_array( $field_name, $allowed_fields )) {
                $filtered_data[ $field_name ] = $field_value;
            } else {
                $unauthorized_fields[] = $field_name;
            }
        }

        // Логируем попытки отправки неразрешенных полей
        if (! empty( $unauthorized_fields )) {
            $log_entry = array(
                'timestamp'               => current_time( 'mysql' ),
                'ip'                      => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent'              => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'unauthorized_fields'     => $unauthorized_fields,
                'total_fields_submitted'  => count( $form_data ),
                'authorized_fields_count' => count( $filtered_data ),
            );
            error_log( 'Form security warning - unauthorized fields attempted: ' . json_encode( $log_entry ) );

            // Возвращаем ошибку при наличии неразрешенных полей
            return new WP_Error(
                'unauthorized_fields',
                'Unauthorized fields detected: ' . implode( ', ', $unauthorized_fields ),
                array( 'status' => 400 )
            );
        }

        return $filtered_data;
    }


    private function validate_form_data( $form_data, $fields_config ) {
        if (! $fields_config) {
            return new WP_Error( 'validation_error', 'Form fields configuration is missing', array( 'status' => 400 ) );
        }

        $fields = json_decode( $fields_config, true );
        if (! $fields || ! is_array( $fields )) {
            return new WP_Error( 'validation_error', 'Invalid form fields configuration', array( 'status' => 400 ) );
        }

        // Проверяем, что есть хотя бы одно поле
        if (empty( $fields )) {
            return new WP_Error( 'validation_error', 'No fields configured for this form', array( 'status' => 400 ) );
        }

        // Проверяем, что есть хотя бы какие-то данные
        if (empty( $form_data )) {
            return new WP_Error( 'validation_error', 'No form data provided', array( 'status' => 400 ) );
        }

        foreach ($fields as $field) {
            $field_name  = $field['name'];
            $field_label = isset( $field['label'] ) ? $field['label'] : $field_name;
            $is_required = isset( $field['required'] ) ? $field['required'] : false;
            $field_type  = isset( $field['type'] ) ? $field['type'] : 'text';

            // Проверка обязательных полей
            if ($is_required) {
                if ($field_type === 'checkbox') {
                    if (! isset( $form_data[ $field_name ] ) || $form_data[ $field_name ] != '1') {
                        return new WP_Error( 'validation_error', "Поле '{$field_label}' обязательно для согласия", array( 'status' => 400 ) );
                    }
                } elseif (! isset( $form_data[ $field_name ] ) || $form_data[ $field_name ] === '' || $form_data[ $field_name ] === null) {
                        return new WP_Error( 'validation_error', "Field '{$field_label}' is required", array( 'status' => 400 ) );
                }
            }

            // Проверка email
            if ($field_type === 'email' && isset( $form_data[ $field_name ] ) && ! empty( $form_data[ $field_name ] )) {
                if (! is_email( $form_data[ $field_name ] )) {
                    return new WP_Error( 'validation_error', "Field '{$field_label}' must contain a valid email address", array( 'status' => 400 ) );
                }
            }

            // Проверка длины полей
            if (isset( $form_data[ $field_name ] ) && ! empty( $form_data[ $field_name ] ) && $field_type !== 'checkbox') {
                $value_length = strlen( $form_data[ $field_name ] );
                if ($value_length > 10000) {
                    return new WP_Error( 'validation_error', "Field '{$field_label}' is too long (maximum 10,000 characters)", array( 'status' => 400 ) );
                }
            }
        }

        return true;
    }


    private function prepare_and_send_email( $recipients, $bcc_recipient, $subject, $form_data, $form_title ) {
        $recipients_array = array_map( 'trim', explode( ',', $recipients ) );

        // Валидация email адресов
        $valid_emails = array();
        foreach ($recipients_array as $recipient) {
            if (is_email( $recipient )) {
                $valid_emails[] = $recipient;
            } else {
                return new WP_Error( 'invalid_email', "Invalid email address: $recipient", array( 'status' => 500 ) );
            }
        }

        if (empty( $valid_emails )) {
            return new WP_Error( 'no_recipients', 'Recipient list is empty or invalid', array( 'status' => 500 ) );
        }

        // Forming the email body
        $body = $this->build_email_body( $form_data, $form_title );

        // Настройка заголовков
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        if ($bcc_recipient && is_email( $bcc_recipient )) {
            $headers[] = 'Bcc: ' . $bcc_recipient;
        }

        // Sending the email
        if (wp_mail( $valid_emails, $subject, $body, $headers )) {
            return true;
        } else {
            return new WP_Error( 'email_send_error', 'Failed to send email', array( 'status' => 500 ) );
        }
    }


    private function build_email_body( $form_data, $form_title ) {
        $body  = "<h2>Form Data: {$form_title}</h2>";
        $body .= "<table style='width: 100%; border-collapse: collapse;'>";
        $body .= "<tr style='background-color: #f8f8f8;'><th style='padding: 10px; border: 1px solid #e9e9e9; text-align: left;'>Field</th><th style='padding: 10px; border: 1px solid #e9e9e9; text-align: left;'>Value</th></tr>";

        foreach ($form_data as $key => $value) {
            $body .= '<tr>';
            $body .= "<td style='padding: 10px; border: 1px solid #e9e9e9;'><strong>" . esc_html( $key ) . '</strong></td>';
            $body .= "<td style='padding: 10px; border: 1px solid #e9e9e9;'>" . esc_html( $value ) . '</td>';
            $body .= '</tr>';
        }

        $body .= '</table>';
        $body .= '<p><small>Sent: ' . current_time( 'd.m.Y H:i:s' ) . '</small></p>';

        return $body;
    }


    private function log_form_submission( $form_id, $form_data, $success ) {
        $log_entry = array(
            'form_id'   => $form_id,
            'success'   => $success,
            'timestamp' => current_time( 'mysql' ),
            'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        );

        error_log( 'Form submission: ' . json_encode( $log_entry ) );
    }


    /**
     * Сохраняет заявку в базу данных
     */
    private function save_submission( $form_id, $form_data, $form_title ) {
        // Создаем заголовок для заявки
        $submission_title = sprintf(
            '%s - %s - %s',
            $form_title,
            isset( $form_data['name'] ) ? $form_data['name'] : 'Anonymous',
            current_time( 'd.m.Y H:i:s' )
        );

        // Создаем запись заявки
        $submission_data = array(
            'post_title'   => $submission_title,
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'form_submissions',
            'post_author'  => 1,
        );

        $submission_id = wp_insert_post( $submission_data );

        if (is_wp_error( $submission_id )) {
            return $submission_id;
        }

        // Сохраняем мета-данные
        update_post_meta( $submission_id, '_form_id', $form_id );
        update_post_meta( $submission_id, '_form_title', $form_title );
        update_post_meta( $submission_id, '_submission_data', json_encode( $form_data ) );
        update_post_meta( $submission_id, '_submission_date', current_time( 'mysql' ) );
        update_post_meta( $submission_id, '_submission_ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
        update_post_meta( $submission_id, '_submission_user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown' );

        return $submission_id;
    }


}

// Инициализация обработчика форм
new FormsHandler();

// Скрываем кнопку "Add New" для заявок
add_action( 'admin_menu', 'hide_add_new_submission_button', 999 );


function hide_add_new_submission_button() {
    remove_submenu_page( 'edit.php?post_type=form_submissions', 'post-new.php?post_type=form_submissions' );
}


// Дополнительно блокируем создание заявок через capabilities
add_filter( 'user_has_cap', 'block_submission_creation_cap', 10, 4 );


function block_submission_creation_cap( $allcaps, $caps, $args, $user ) {
    if (isset( $args[0] ) && $args[0] === 'create_posts' && isset( $args[2] ) && $args[2] === 'form_submissions') {
        $allcaps['create_posts'] = false;
    }

    return $allcaps;
}


// Скрываем кнопку "Add New" из списка заявок
add_filter( 'post_row_actions', 'hide_add_new_submission_row_action', 10, 2 );


function hide_add_new_submission_row_action( $actions, $post ) {
    if ($post && $post->post_type === 'form_submissions') {
        unset( $actions['inline hide-if-no-js'] );
        unset( $actions['edit'] );
        unset( $actions['trash'] );
        // Оставляем только удаление
    }

    return $actions;
}


// Скрываем кнопку "Add New" из заголовка страницы заявок
add_action( 'admin_head', 'hide_add_new_submission_button_css' );


function hide_add_new_submission_button_css() {
    global $post_type;
    if ($post_type === 'form_submissions') {
        echo '<style>
            .page-title-action,
            .wp-heading-inline + .page-title-action,
            a.page-title-action,
            .wrap .page-title-action {
                display: none !important;
            }
            .subsubsub .add-new-h2 {
                display: none !important;
            }
        </style>';
    }
}


// Отключаем редактирование заявок
add_action( 'admin_init', 'disable_submission_editing' );


function disable_submission_editing() {
    global $pagenow, $post_type, $post;

    // Проверяем, что мы на странице редактирования заявки
    if ($pagenow === 'post.php' && $post_type === 'form_submissions') {
        // Перенаправляем на список заявок при попытке редактирования
        wp_redirect( admin_url( 'edit.php?post_type=form_submissions&message=1' ) );
        exit;
    }

    // Также блокируем создание новых заявок
    if ($pagenow === 'post-new.php' && $post_type === 'form_submissions') {
        wp_redirect( admin_url( 'edit.php?post_type=form_submissions&message=2' ) );
        exit;
    }
}


// Отключаем создание заявок через REST API
add_filter( 'rest_pre_insert_form_submissions', 'disable_submission_creation_via_rest', 10, 2 );


function disable_submission_creation_via_rest( $prepared, $request ) {
    return new WP_Error( 'rest_forbidden', 'Creating submissions manually is not allowed', array( 'status' => 403 ) );
}


// Добавляем уведомления о блокировке создания заявок
add_action( 'admin_notices', 'submission_creation_blocked_notice' );


function submission_creation_blocked_notice() {
    global $pagenow, $post_type;

    if ($post_type === 'form_submissions' && isset( $_GET['message'] )) {
        $message = '';
        switch ($_GET['message']) {
            case '1':
                $message = 'Editing submissions is not allowed. Submissions can only be created automatically when forms are submitted.';
                break;
            case '2':
                $message = 'Creating submissions manually is not allowed. Submissions are created automatically when forms are submitted.';
                break;
        }

        if ($message) {
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
        }
    }
}


// Добавление колонок в админке для форм
add_filter( 'manage_forms_posts_columns', 'add_forms_admin_columns' );


function add_forms_admin_columns( $columns ) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[ $key ] = $value;
        if ($key === 'title') {
            $new_columns['recipients']    = 'Recipients';
            $new_columns['telegram']      = 'Telegram';
            $new_columns['sheets']        = 'Google Sheets';
            $new_columns['admin_storage'] = 'Admin Storage';
            $new_columns['shortcode']     = 'Shortcode';
            $new_columns['export']        = 'Export';
        }
    }

    return $new_columns;
}


add_action( 'manage_forms_posts_custom_column', 'fill_forms_admin_columns', 10, 2 );


function fill_forms_admin_columns( $column, $post_id ) {
    switch ($column) {
        case 'recipients':
            $recipients = get_post_meta( $post_id, '_recipients', true );
            echo esc_html( $recipients ?: 'Not configured' );
            break;
        case 'telegram':
            $telegram_enabled = get_post_meta( $post_id, '_send_to_telegram', true ) === '1';
            echo $telegram_enabled ? 'Enabled' : 'Disabled';
            break;
        case 'sheets':
            $sheets_enabled = get_post_meta( $post_id, '_send_to_sheets', true ) === '1';
            echo $sheets_enabled ? 'Enabled' : 'Disabled';
            break;
        case 'admin_storage':
            $save_to_admin = get_post_meta( $post_id, '_save_to_admin', true ) === '1';
            echo $save_to_admin ? 'Enabled' : 'Disabled';
            break;
        case 'export':
            $submissions_count = get_posts(
                array(
                    'post_type'      => 'form_submissions',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'meta_query'     => array(
                        array(
                            'key'     => '_form_id',
                            'value'   => $post_id,
                            'compare' => '=',
                        ),
                    ),
                )
            );
            $count             = count( $submissions_count );
            if ($count > 0) {
                echo '<a href="' . admin_url( 'admin-post.php?action=export_submissions_csv&form_filter=' . $post_id . '&export_nonce=' . wp_create_nonce( 'export_submissions_csv' ) ) . '" target="_blank" class="button button-small">Export (' . $count . ')</a>';
            } else {
                echo '<span style="color: #999;">No submissions</span>';
            }
            break;
        case 'shortcode':
            $post = get_post( $post_id );
            echo '<code>[form id="' . esc_attr( $post->post_name ) . '"]</code>';
            break;
    }
}


// Добавление колонок в админке для заявок
add_filter( 'manage_form_submissions_posts_columns', 'add_submissions_admin_columns' );


function add_submissions_admin_columns( $columns ) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[ $key ] = $value;
        if ($key === 'title') {
            $new_columns['form_name']       = 'Form';
            $new_columns['submission_date'] = 'Date';
            $new_columns['submission_ip']   = 'IP Address';
            $new_columns['submission_data'] = 'Data Preview';
        }
    }

    return $new_columns;
}


add_action( 'manage_form_submissions_posts_custom_column', 'fill_submissions_admin_columns', 10, 2 );


function fill_submissions_admin_columns( $column, $post_id ) {
    switch ($column) {
        case 'form_name':
            $form_title = get_post_meta( $post_id, '_form_title', true );
            echo esc_html( $form_title ?: 'Unknown Form' );
            break;
        case 'submission_date':
            $date = get_post_meta( $post_id, '_submission_date', true );
            echo $date ? date( 'd.m.Y H:i:s', strtotime( $date ) ) : 'Unknown';
            break;
        case 'submission_ip':
            $ip = get_post_meta( $post_id, '_submission_ip', true );
            echo esc_html( $ip ?: 'Unknown' );
            break;
        case 'submission_data':
            $data = get_post_meta( $post_id, '_submission_data', true );
            if ($data) {
                $data_array = json_decode( $data, true );
                if ($data_array) {
                    $preview = array();
                    foreach ($data_array as $key => $value) {
                        if (count( $preview ) < 3) {
                            $preview[] = $key . ': ' . substr( $value, 0, 20 ) . ( strlen( $value ) > 20 ? '...' : '' );
                        }
                    }

                    echo esc_html( implode( ', ', $preview ) );
                }
            }
            break;
    }
}


// Шорткод для вывода формы
add_shortcode( 'form', 'render_form_shortcode' );


function render_form_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'id'   => '',
            'slug' => '',
        ),
        $atts
    );

    $form_id = $atts['id'] ?: $atts['slug'];
    if (! $form_id) {
        return '<p>Error: form ID or slug not specified</p>';
    }

    // Получаем форму
    $form = get_page_by_path( $form_id, OBJECT, 'forms' );
    if (! $form) {
        $form = get_post( $form_id );
    }

    if (! $form || $form->post_type !== 'forms') {
        return '<p>Form not found</p>';
    }

    $fields_config = get_post_meta( $form->ID, '_fields_config', true );
    $fields        = json_decode( $fields_config, true ) ?: array();

    ob_start();
    ?>
    <div class="form-container" data-form-id="<?php echo esc_attr( $form->post_name ); ?>">
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wp-form">
            <?php wp_nonce_field( 'submit_form_nonce', 'form_nonce' ); ?>
            <input type="hidden" name="action" value="submit_form">
            <input type="hidden" name="form_id" value="<?php echo esc_attr( $form->post_name ); ?>">
            
            <?php foreach ($fields as $field) : ?>
                <div class="form-field">
                    <label for="<?php echo esc_attr( $field['name'] ); ?>">
                        <?php echo esc_html( $field['label'] ); ?>
                        <?php if (isset( $field['required'] ) && $field['required']) : ?>
                            <span class="required">*</span>
                        <?php endif; ?>
                    </label>
                    
                    <?php if ($field['type'] === 'textarea') : ?>
                        <textarea 
                            name="<?php echo esc_attr( $field['name'] ); ?>" 
                            id="<?php echo esc_attr( $field['name'] ); ?>"
                            <?php echo ( isset( $field['required'] ) && $field['required'] ) ? 'required' : ''; ?>
                            rows="4"
                        ></textarea>
                    <?php elseif ($field['type'] === 'checkbox') : ?>
                        <input 
                            type="checkbox" 
                            name="<?php echo esc_attr( $field['name'] ); ?>" 
                            id="<?php echo esc_attr( $field['name'] ); ?>" 
                            value="1"
                            <?php echo ( isset( $field['required'] ) && $field['required'] ) ? 'required' : ''; ?>
                        >
                    <?php else : ?>
                        <input 
                            type="<?php echo esc_attr( $field['type'] ); ?>" 
                            name="<?php echo esc_attr( $field['name'] ); ?>" 
                            id="<?php echo esc_attr( $field['name'] ); ?>"
                            <?php echo ( isset( $field['required'] ) && $field['required'] ) ? 'required' : ''; ?>
                        >
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="form-submit">
                <button type="submit" class="submit-button">Send</button>
            </div>
        </form>
        
        <div class="form-message" style="display: none;"></div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('.wp-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $message = $form.siblings('.form-message');
            var $submitButton = $form.find('.submit-button');
            
            $submitButton.prop('disabled', true).text('Sending...');
            $message.hide();
            
            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response.success) {
                        var message = response.data.message;
                        
                        // Добавляем информацию о доставке
                        if (response.data.delivery_results) {
                            var delivery = response.data.delivery_results;
                            message += '<br><br><strong>Delivery Status:</strong><br>';
                            
                            // Email
                            if (delivery.email.success) {
                                message += '✅ Email: Sent successfully<br>';
                            } else {
                                message += '❌ Email: ' + (delivery.email.error || 'Failed') + '<br>';
                            }
                            
                            // Telegram
                            if (delivery.telegram.enabled) {
                                if (delivery.telegram.success) {
                                    message += '✅ Telegram: Sent successfully<br>';
                                } else {
                                    message += '❌ Telegram: ' + (delivery.telegram.error || 'Failed') + '<br>';
                                }
                            }
                            
                            // Google Sheets
                            if (delivery.google_sheets.enabled) {
                                if (delivery.google_sheets.success) {
                                    message += '✅ Google Sheets: Data saved<br>';
                                } else {
                                    message += '❌ Google Sheets: ' + (delivery.google_sheets.error || 'Failed') + '<br>';
                                }
                            }
                            
                            // Admin Storage
                            if (delivery.admin_storage.enabled) {
                                if (delivery.admin_storage.success) {
                                    message += '✅ Admin Panel: Submission saved<br>';
                                } else {
                                    message += '❌ Admin Panel: ' + (delivery.admin_storage.error || 'Failed') + '<br>';
                                }
                            }
                        }
                        
                        $message.removeClass('error').addClass('success').html(message).show();
                        $form[0].reset();
                    } else {
                        var errorMessage = response.data.message;
                        
                        // Добавляем информацию о неразрешенных полях
                        if (response.data.unauthorized_fields && response.data.unauthorized_fields.length > 0) {
                            errorMessage += '<br><br><strong>Unauthorized fields:</strong> ' + response.data.unauthorized_fields.join(', ');
                            if (response.data.allowed_fields && response.data.allowed_fields.length > 0) {
                                errorMessage += '<br><strong>Allowed fields:</strong> ' + response.data.allowed_fields.join(', ');
                            }
                        }
                        
                        // Добавляем информацию о результатах доставки для ошибок отправки
                        if (response.data.delivery_results) {
                            errorMessage += '<br><br><strong>Delivery Status:</strong><br>';
                            var delivery = response.data.delivery_results;
                            
                            if (delivery.email.success) {
                                errorMessage += '✅ Email: Sent successfully<br>';
                            } else {
                                errorMessage += '❌ Email: ' + (delivery.email.error || 'Failed') + '<br>';
                            }
                            
                            if (delivery.telegram.enabled) {
                                if (delivery.telegram.success) {
                                    errorMessage += '✅ Telegram: Sent successfully<br>';
                                } else {
                                    errorMessage += '❌ Telegram: ' + (delivery.telegram.error || 'Failed') + '<br>';
                                }
                            }
                            
                            if (delivery.google_sheets.enabled) {
                                if (delivery.google_sheets.success) {
                                    errorMessage += '✅ Google Sheets: Data saved<br>';
                                } else {
                                    errorMessage += '❌ Google Sheets: ' + (delivery.google_sheets.error || 'Failed') + '<br>';
                                }
                            }
                        }
                        
                        $message.removeClass('success').addClass('error').html(errorMessage).show();
                    }
                },
                error: function() {
                    $message.removeClass('success').addClass('error').html('An error occurred while sending the form').show();
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text('Send');
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}


// Функция для создания формы по умолчанию при активации темы
function create_default_contact_form() {
    // Проверяем, существует ли уже форма с таким slug
    $existing_form = get_page_by_path( 'contact-form', OBJECT, 'forms' );

    if (! $existing_form) {
        $form_data = array(
            'post_title'   => 'Contact Form',
            'post_name'    => 'contact-form',
            'post_status'  => 'publish',
            'post_type'    => 'forms',
            'post_content' => 'Default contact form',
        );

        $form_id = wp_insert_post( $form_data );

        if ($form_id && ! is_wp_error( $form_id )) {
            // Устанавливаем мета-поля
            update_post_meta( $form_id, '_recipients', get_option( 'admin_email' ) );
            update_post_meta( $form_id, '_subject', 'New message from website' );

            // Конфигурация полей по умолчанию
            $default_fields = json_encode(
                array(
                    array(
                        'name'     => 'name',
                        'label'    => 'Name',
                        'type'     => 'text',
                        'required' => true,
                    ),
                    array(
                        'name'     => 'email',
                        'label'    => 'Email',
                        'type'     => 'email',
                        'required' => true,
                    ),
                    array(
                        'name'     => 'phone',
                        'label'    => 'Phone',
                        'type'     => 'tel',
                        'required' => false,
                    ),
                    array(
                        'name'     => 'message',
                        'label'    => 'Message',
                        'type'     => 'textarea',
                        'required' => true,
                    ),
                )
            );
            update_post_meta( $form_id, '_fields_config', $default_fields );
        }
    }
}


// Создаем форму по умолчанию при активации темы
add_action( 'after_switch_theme', 'create_default_contact_form' );

// Функция для получения всех форм
function get_all_forms() {
    return get_posts(
        array(
            'post_type'      => 'forms',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );
}


// Функция для получения формы по slug
function get_form_by_slug( $slug ) {
    return get_page_by_path( $slug, OBJECT, 'forms' );
}


// Функция для получения конфигурации полей формы
function get_form_fields_config( $form_id ) {
    $fields_config = get_post_meta( $form_id, '_fields_config', true );
    return json_decode( $fields_config, true ) ?: array();
}


// Функция для валидации данных формы
function validate_form_data( $form_data, $form_id ) {
    $fields_config = get_form_fields_config( $form_id );

    foreach ($fields_config as $field) {
        $field_name  = $field['name'];
        $is_required = isset( $field['required'] ) ? $field['required'] : false;
        $field_type  = isset( $field['type'] ) ? $field['type'] : 'text';

        // Проверка обязательных полей
        if ($is_required && ( empty( $form_data[ $field_name ] ) || $form_data[ $field_name ] === '' )) {
            return new WP_Error( 'validation_error', "Field '{$field['label']}' is required" );
        }

        // Проверка email
        if ($field_type === 'email' && ! empty( $form_data[ $field_name ] )) {
            if (! is_email( $form_data[ $field_name ] )) {
                return new WP_Error( 'validation_error', "Field '{$field['label']}' must contain a valid email" );
            }
        }
    }

    return true;
}


// Функционал экспорта заявок
add_action( 'admin_menu', 'add_submissions_export_menu' );


function add_submissions_export_menu() {
    add_submenu_page(
        'edit.php?post_type=form_submissions',
        'Export Submissions',
        'Export Submissions',
        'manage_options',
        'export-by-form',
        'render_export_by_form_page'
    );
}


// Добавляем обработчик для экспорта
add_action( 'admin_post_export_submissions_csv', 'handle_export_submissions_csv' );
add_action( 'admin_post_nopriv_export_submissions_csv', 'handle_export_submissions_csv' );


function handle_export_submissions_csv() {
    // Проверяем права доступа
    if (! current_user_can( 'manage_options' )) {
        wp_die( 'Access denied' );
    }

    // Проверяем nonce (для POST и GET запросов)
    $nonce = isset( $_POST['export_nonce'] ) ? $_POST['export_nonce'] : ( isset( $_GET['export_nonce'] ) ? $_GET['export_nonce'] : '' );
    if (! $nonce || ! wp_verify_nonce( $nonce, 'export_submissions_csv' )) {
        wp_die( 'Security check failed' );
    }

    // Получаем параметры фильтрации (из POST или GET)
    $form_filter = isset( $_POST['form_filter'] ) ? intval( $_POST['form_filter'] ) : ( isset( $_GET['form_filter'] ) ? intval( $_GET['form_filter'] ) : 0 );
    $date_from   = isset( $_POST['date_from'] ) ? sanitize_text_field( $_POST['date_from'] ) : ( isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '' );
    $date_to     = isset( $_POST['date_to'] ) ? sanitize_text_field( $_POST['date_to'] ) : ( isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '' );

    // Проверяем, что выбрана форма
    if (! $form_filter) {
        wp_die( 'Please select a form to export.' );
    }

    // Формируем запрос
    $args = array(
        'post_type'      => 'form_submissions',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => array(
            array(
                'key'     => '_form_id',
                'value'   => $form_filter,
                'compare' => '=',
            ),
        ),
    );

    // Добавляем фильтр по дате
    if ($date_from || $date_to) {
        $date_query = array();
        if ($date_from) {
            $date_query['after'] = $date_from;
        }

        if ($date_to) {
            $date_query['before'] = $date_to . ' 23:59:59';
        }

        $args['date_query'] = $date_query;
    }

    $submissions = get_posts( $args );

    if (empty( $submissions )) {
        wp_die( 'No submissions found for the selected form and criteria.' );
    }

    // Получаем название формы для имени файла
    $form      = get_post( $form_filter );
    $form_name = $form ? sanitize_title( $form->post_title ) : 'form';

    // Очищаем буфер вывода
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Устанавливаем заголовки для скачивания CSV
    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $form_name . '-submissions-' . date( 'Y-m-d-H-i-s' ) . '.csv"' );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );

    // Создаем файловый указатель для вывода
    $output = fopen( 'php://output', 'w' );

    // Добавляем BOM для корректного отображения кириллицы в Excel
    fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

    // Заголовки CSV
    $headers = array(
        'ID',
        'Form Title',
        'Submission Date',
        'IP Address',
        'User Agent',
    );

    // Получаем все уникальные поля из данных заявок
    $all_fields = array();
    foreach ($submissions as $submission) {
        $data = get_post_meta( $submission->ID, '_submission_data', true );
        if ($data) {
            $data_array = json_decode( $data, true );
            if ($data_array) {
                foreach (array_keys( $data_array ) as $field) {
                    if (! in_array( $field, $all_fields )) {
                        $all_fields[] = $field;
                    }
                }
            }
        }
    }

    // Добавляем поля данных в заголовки
    $headers = array_merge( $headers, $all_fields );

    // Записываем заголовки
    fputcsv( $output, $headers );

    // Записываем данные
    foreach ($submissions as $submission) {
        $row = array(
            $submission->ID,
            get_post_meta( $submission->ID, '_form_title', true ),
            get_post_meta( $submission->ID, '_submission_date', true ),
            get_post_meta( $submission->ID, '_submission_ip', true ),
            get_post_meta( $submission->ID, '_submission_user_agent', true ),
        );

        // Добавляем данные формы
        $data       = get_post_meta( $submission->ID, '_submission_data', true );
        $data_array = json_decode( $data, true ) ?: array();

        foreach ($all_fields as $field) {
            $row[] = isset( $data_array[ $field ] ) ? $data_array[ $field ] : '';
        }

        fputcsv( $output, $row );
    }

    fclose( $output );
    exit;
}


function render_export_by_form_page() {
    $forms = get_all_forms();

    // Получаем статистику по формам
    $form_stats = array();
    foreach ($forms as $form) {
        $submissions_count = get_posts(
            array(
                'post_type'      => 'form_submissions',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_query'     => array(
                    array(
                        'key'     => '_form_id',
                        'value'   => $form->ID,
                        'compare' => '=',
                    ),
                ),
            )
        );

        $form_stats[ $form->ID ] = count( $submissions_count );
    }

    ?>
    <div class="wrap">
        <h1>Export Submissions</h1>
        <p>Select a form to export all its submissions to CSV:</p>
        
        <div class="form-export-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
            <?php foreach ($forms as $form) : ?>
                <div class="form-export-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 5px; background: #fff;">
                    <h3><?php echo esc_html( $form->post_title ); ?></h3>
                    <p><strong>Submissions:</strong> <?php echo $form_stats[ $form->ID ]; ?></p>
                    <p><strong>Slug:</strong> <code><?php echo esc_html( $form->post_name ); ?></code></p>
                    
                    <div style="margin-top: 15px;">
                        <a href="<?php echo admin_url( 'admin-post.php?action=export_submissions_csv&form_filter=' . $form->ID . '&export_nonce=' . wp_create_nonce( 'export_submissions_csv' ) ); ?>" 
                            class="button button-primary" 
                            target="_blank"
                            style="margin-right: 10px;">
                            Export All
                        </a>
                        
                        <button type="button" 
                                class="button button-secondary export-with-filters" 
                                data-form-id="<?php echo $form->ID; ?>"
                                data-form-name="<?php echo esc_attr( $form->post_title ); ?>">
                            Export by Date
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Модальное окно для фильтров -->
        <div id="export-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100000;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 5px; min-width: 400px;">
                <h2 id="modal-title">Export by Date</h2>
                
                <form id="export-filters-form" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" target="_blank">
                    <input type="hidden" name="action" value="export_submissions_csv">
                    <input type="hidden" name="export_nonce" value="<?php echo wp_create_nonce( 'export_submissions_csv' ); ?>">
                    <input type="hidden" name="form_filter" id="modal-form-filter">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="modal_date_from">Date From</label>
                            </th>
                            <td>
                                <input type="date" name="date_from" id="modal_date_from" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="modal_date_to">Date To</label>
                            </th>
                            <td>
                                <input type="date" name="date_to" id="modal_date_to" />
                            </td>
                        </tr>
                    </table>
                    
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="button" class="button" onclick="closeExportModal()">Cancel</button>
                        <button type="submit" class="button button-primary">Export</button>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        function openExportModal(formId, formName) {
            document.getElementById('modal-title').textContent = 'Export ' + formName + ' by Date';
            document.getElementById('modal-form-filter').value = formId;
            document.getElementById('export-modal').style.display = 'block';
        }
        
        function closeExportModal() {
            document.getElementById('export-modal').style.display = 'none';
            document.getElementById('export-filters-form').reset();
        }
        
        // Закрытие модального окна при клике вне его
        document.getElementById('export-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeExportModal();
            }
        });
        
        // Обработчик кнопок "Export by Date"
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.export-with-filters').forEach(function(button) {
                button.addEventListener('click', function() {
                    var formId = this.getAttribute('data-form-id');
                    var formName = this.getAttribute('data-form-name');
                    openExportModal(formId, formName);
                });
            });
        });
        </script>
    </div>
    <?php
}
