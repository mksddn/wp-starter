<?php
/**
 * Google Sheets Handler for Forms Handler Plugin
 *
 * Handles sending form submissions to Google Sheets
 */

// Add settings page to admin panel
add_action( 'admin_menu', 'add_google_sheets_settings_page' );


function add_google_sheets_settings_page() {
    add_options_page(
        'Google Sheets Settings',
        'Google Sheets',
        'manage_options',
        'google-sheets-settings',
        'render_google_sheets_settings_page'
    );
}


// Handle OAuth callback
add_action( 'admin_init', 'handle_google_oauth_callback' );


function handle_google_oauth_callback() {
    if (isset( $_GET['page'] ) && $_GET['page'] === 'google-sheets-settings' && isset( $_GET['code'] )) {
        $code          = sanitize_text_field( $_GET['code'] );
        $client_id     = get_option( 'google_sheets_client_id' );
        $client_secret = get_option( 'google_sheets_client_secret' );

        if ($client_id && $client_secret) {
            $response = wp_remote_post(
                'https://oauth2.googleapis.com/token',
                array(
                    'body'    => array(
                        'client_id'     => $client_id,
                        'client_secret' => $client_secret,
                        'code'          => $code,
                        'grant_type'    => 'authorization_code',
                        'redirect_uri'  => admin_url( 'options-general.php?page=google-sheets-settings' ),
                    ),
                    'timeout' => 30,
                )
            );

            if (! is_wp_error( $response )) {
                $result = json_decode( wp_remote_retrieve_body( $response ), true );

                if (isset( $result['refresh_token'] )) {
                    update_option( 'google_sheets_refresh_token', $result['refresh_token'] );
                    wp_redirect( admin_url( 'options-general.php?page=google-sheets-settings&success=1' ) );
                    exit;
                } else {
                    wp_redirect( admin_url( 'options-general.php?page=google-sheets-settings&error=auth_failed' ) );
                    exit;
                }
            } else {
                wp_redirect( admin_url( 'options-general.php?page=google-sheets-settings&error=network_error' ) );
                exit;
            }
        }
    }
}


// Save settings
add_action( 'admin_init', 'save_google_sheets_settings' );


function save_google_sheets_settings() {
    if (isset( $_POST['google_sheets_settings_nonce'] ) && wp_verify_nonce( $_POST['google_sheets_settings_nonce'], 'save_google_sheets_settings' )) {
        if (isset( $_POST['google_sheets_client_id'] )) {
            update_option( 'google_sheets_client_id', sanitize_text_field( $_POST['google_sheets_client_id'] ) );
        }

        if (isset( $_POST['google_sheets_client_secret'] )) {
            update_option( 'google_sheets_client_secret', sanitize_text_field( $_POST['google_sheets_client_secret'] ) );
        }

        wp_redirect( admin_url( 'options-general.php?page=google-sheets-settings&saved=1' ) );
        exit;
    }

    // Handle authentication revocation
    if (isset( $_POST['revoke_auth_nonce'] ) && wp_verify_nonce( $_POST['revoke_auth_nonce'], 'revoke_google_sheets_auth' )) {
        delete_option( 'google_sheets_refresh_token' );
        wp_redirect( admin_url( 'options-general.php?page=google-sheets-settings&revoked=1' ) );
        exit;
    }

    // Handle full settings clearing
    if (isset( $_POST['clear_all_nonce'] ) && wp_verify_nonce( $_POST['clear_all_nonce'], 'clear_google_sheets_all' )) {
        delete_option( 'google_sheets_client_id' );
        delete_option( 'google_sheets_client_secret' );
        delete_option( 'google_sheets_refresh_token' );

        wp_redirect( admin_url( 'options-general.php?page=google-sheets-settings&cleared=1' ) );
        exit;
    }
}


// Render settings page
function render_google_sheets_settings_page() {
    $client_id     = get_option( 'google_sheets_client_id' );
    $client_secret = get_option( 'google_sheets_client_secret' );
    $refresh_token = get_option( 'google_sheets_refresh_token' );

    ?>
    <div class="wrap">
        <h1>Google Sheets Settings</h1>
        
        <?php if (isset( $_GET['success'] )) : ?>
            <div class="notice notice-success">
                <p>✅ Google Sheets authentication successful! You can now use Google Sheets integration in your forms.</p>
            </div>
        <?php endif; ?>
        
        <?php if (isset( $_GET['error'] )) : ?>
            <div class="notice notice-error">
                <p>❌ Authentication failed. Please try again.</p>
            </div>
        <?php endif; ?>
        
        <?php if (isset( $_GET['saved'] )) : ?>
            <div class="notice notice-success">
                <p>Settings saved successfully!</p>
            </div>
        <?php endif; ?>
        
        <?php if (isset( $_GET['revoked'] )) : ?>
            <div class="notice notice-warning">
                <p>✅ Google Sheets authentication has been revoked. You can now re-authenticate with different credentials.</p>
            </div>
        <?php endif; ?>
        
        <?php if (isset( $_GET['cleared'] )) : ?>
            <div class="notice notice-info">
                <p>🗑️ All Google Sheets settings have been cleared. You can start fresh setup.</p>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Step 1: Google Cloud Console Setup</h2>
            <ol>
                <li>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                <li>Create a new project or select existing one</li>
                <li>Enable Google Sheets API:
                    <ul>
                        <li>Go to "APIs &amp; Services" → "Library"</li>
                        <li>Search for "Google Sheets API"</li>
                        <li>Click "Enable"</li>
                    </ul>
                </li>
                <li>Create OAuth 2.0 credentials:
                    <ul>
                        <li>Go to "APIs &amp; Services" → "Credentials"</li>
                        <li>Click "Create Credentials" → "OAuth 2.0 Client IDs"</li>
                        <li>Choose "Web application"</li>
                        <li>Add authorized redirect URI: <code><?php echo admin_url( 'options-general.php?page=google-sheets-settings' ); ?></code></li>
                        <li><strong>Important:</strong> Make sure this exact URL is added to your Google Cloud Console OAuth credentials</li>
                        <li>Save Client ID and Client Secret</li>
                    </ul>
                </li>
            </ol>
        </div>
        
        <div class="card">
            <h2>Step 2: Enter Credentials</h2>
            <form method="post" action="">
                <?php wp_nonce_field( 'save_google_sheets_settings', 'google_sheets_settings_nonce' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="google_sheets_client_id">Client ID</label></th>
                        <td>
                            <input type="text" name="google_sheets_client_id" id="google_sheets_client_id" 
                                    value="<?php echo esc_attr( $client_id ); ?>" class="regular-text" />
                            <p class="description">OAuth 2.0 Client ID from Google Cloud Console</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="google_sheets_client_secret">Client Secret</label></th>
                        <td>
                            <input type="password" name="google_sheets_client_secret" id="google_sheets_client_secret" 
                                    value="<?php echo esc_attr( $client_secret ); ?>" class="regular-text" />
                            <p class="description">OAuth 2.0 Client Secret from Google Cloud Console</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Credentials">
                </p>
            </form>
        </div>
        
        <div class="card">
            <h2>Step 3: Authorize Access</h2>
            <?php if ($refresh_token) : ?>
                <div class="notice notice-success">
                    <p>✅ <strong>Authenticated!</strong> Google Sheets integration is ready to use.</p>
                    <p>Refresh Token: <code><?php echo substr( $refresh_token, 0, 20 ) . '...'; ?></code></p>
                </div>
                
                <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;">
                    <h4>🔐 Authentication Management</h4>
                    <p>If you need to switch to a different Google account or re-authenticate:</p>
                    <form method="post" action="" style="display: inline;">
                        <?php wp_nonce_field( 'revoke_google_sheets_auth', 'revoke_auth_nonce' ); ?>
                        <button type="submit" class="button button-secondary" onclick="return confirm('Are you sure you want to revoke Google Sheets authentication? This will disconnect the current integration.')">
                            🚫 Revoke Authentication
                        </button>
                    </form>
                    <p style="margin-top: 10px; font-size: 12px; color: #666;">
                        <strong>Note:</strong> This will remove the current authentication. You'll need to re-authenticate to use Google Sheets integration again.
                    </p>
                </div>
            <?php elseif ($client_id && $client_secret) : ?>
                <p>Click the button below to authorize access to Google Sheets:</p>
                <a href="<?php echo GoogleSheetsHandler::get_auth_url(); ?>" class="button button-primary">
                    🔐 Authorize Google Sheets Access
                </a>
            <?php else : ?>
                <div class="notice notice-warning">
                    <p>⚠️ Please save your Client ID and Client Secret first.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($refresh_token) : ?>
            <div class="card">
                <h2>Step 4: Test Connection</h2>
                <p>Test your Google Sheets connection:</p>
                <button type="button" id="test-sheets-btn" class="button button-secondary">
                    Test Google Sheets Connection
                </button>
                <div id="test-result" style="margin-top: 10px;"></div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#test-sheets-btn').on('click', function() {
                    var $btn = $(this);
                    var $result = $('#test-result');
                    
                    $btn.prop('disabled', true).text('Testing...');
                    $result.html('');
                    
                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'test_google_sheets_connection',
                            nonce: '<?php echo wp_create_nonce( 'test_sheets_nonce' ); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $result.html('<div class="notice notice-success"><p>✅ ' + response.data.message + '</p></div>');
                            } else {
                                $result.html('<div class="notice notice-error"><p>❌ ' + response.data.message + '</p></div>');
                            }
                        },
                        error: function() {
                            $result.html('<div class="notice notice-error"><p>❌ Test failed</p></div>');
                        },
                        complete: function() {
                            $btn.prop('disabled', false).text('Test Google Sheets Connection');
                        }
                    });
                });
            });
            </script>
        <?php endif; ?>
        
        <div class="card">
            <h2>Usage</h2>
            <p>Once configured, you can enable Google Sheets integration in your forms:</p>
            <ol>
                <li>Go to <strong>Forms</strong> → <strong>Add New</strong> or edit existing form</li>
                <li>In the <strong>Google Sheets Integration</strong> section:</li>
                <ul>
                    <li>Check "Send to Google Sheets"</li>
                    <li>Enter your Spreadsheet ID (from URL: docs.google.com/spreadsheets/d/SPREADSHEET_ID)</li>
                    <li>Optionally specify Sheet Name</li>
                </ul>
                <li>Save the form</li>
            </ol>
        </div>
        
        <?php if ($client_id || $client_secret || $refresh_token) : ?>
        <div class="card" style="border-color: #dc3545;">
            <h2 style="color: #dc3545;">⚠️ Clear All Settings</h2>
            <p>If you want to completely remove all Google Sheets settings and start over:</p>
            <form method="post" action="" style="display: inline;">
                <?php wp_nonce_field( 'clear_google_sheets_all', 'clear_all_nonce' ); ?>
                <button type="submit" class="button button-secondary" style="background: #dc3545; border-color: #dc3545; color: white;" onclick="return confirm('⚠️ WARNING: This will permanently delete ALL Google Sheets settings including Client ID, Client Secret, and Refresh Token. This action cannot be undone. Are you absolutely sure?')">
                    🗑️ Clear All Google Sheets Settings
                </button>
            </form>
            <p style="margin-top: 10px; font-size: 12px; color: #666;">
                <strong>Use this when:</strong> You want to switch to different Google account, fix OAuth issues, or start fresh setup.
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}


// AJAX handler for testing connection
add_action( 'wp_ajax_test_google_sheets_connection', 'handle_test_google_sheets_connection' );


function handle_test_google_sheets_connection() {
    if (! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'test_sheets_nonce' )) {
        wp_send_json_error( array( 'message' => 'Security check failed' ) );
    }

    if (! current_user_can( 'manage_options' )) {
        wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
    }

    $test_spreadsheet_id = '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms';
    $result              = GoogleSheetsHandler::test_connection( $test_spreadsheet_id );

    if ($result['authentication']['valid'] && $result['spreadsheet']['accessible']) {
        wp_send_json_success( array( 'message' => 'Connection successful! Google Sheets integration is working properly.' ) );
    } else {
        $errors = array();
        if (! $result['authentication']['valid']) {
            $errors[] = 'Authentication: ' . $result['authentication']['error'];
        }

        if (! $result['spreadsheet']['accessible']) {
            $errors[] = 'Spreadsheet access: ' . $result['spreadsheet']['error'];
        }

        wp_send_json_error( array( 'message' => 'Connection failed: ' . implode( ', ', $errors ) ) );
    }
}


class GoogleSheetsHandler {


    /**
     * Send form submission to Google Sheets
     *
     * @param string $spreadsheet_id Google Sheets spreadsheet ID
     * @param string $sheet_name Sheet name (optional, defaults to first sheet)
     * @param array  $form_data Form submission data
     * @param string $form_title Form title
     * @return true|WP_Error Success or error
     */
    public static function send_data( $spreadsheet_id, $sheet_name, $form_data, $form_title ) {
        $validation_result = self::validate_config( $spreadsheet_id, $sheet_name );
        if (is_wp_error( $validation_result )) {
            return $validation_result;
        }

        $access_token = self::get_access_token();
        if (is_wp_error( $access_token )) {
            return $access_token;
        }

        $headers = array( 'Timestamp', 'Form Title' );
        foreach (array_keys( $form_data ) as $field_name) {
            $headers[] = ucfirst( $field_name );
        }

        $row_values = array(
            current_time( 'Y-m-d H:i:s' ),
            $form_title,
        );

        foreach ($form_data as $value) {
            $row_values[] = $value;
        }

        $sheets_data = array( 'values' => array( $row_values ) );
        $range       = $sheet_name ? $sheet_name . '!A:Z' : 'A:Z';

        $response = wp_remote_post(
            "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}/values/{$range}:append?valueInputOption=RAW",
            array(
                'body'    => json_encode( $sheets_data ),
                'timeout' => 30,
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $access_token,
                ),
            )
        );

        if (is_wp_error( $response )) {
            return new WP_Error( 'sheets_send_error', 'Failed to send data to Google Sheets: ' . $response->get_error_message(), array( 'status' => 500 ) );
        }

        $body   = wp_remote_retrieve_body( $response );
        $result = json_decode( $body, true );

        if ($result && isset( $result['updates'] )) {
            return true;
        } else {
            $error_message = isset( $result['error']['message'] ) ? $result['error']['message'] : 'Unknown error';
            return new WP_Error( 'sheets_api_error', 'Google Sheets API error: ' . $error_message, array( 'status' => 500 ) );
        }
    }


    /**
     * Get Google OAuth access token
     *
     * @return string|WP_Error Access token or error
     */
    private static function get_access_token() {
        $client_id     = get_option( 'google_sheets_client_id' );
        $client_secret = get_option( 'google_sheets_client_secret' );
        $refresh_token = get_option( 'google_sheets_refresh_token' );

        if (empty( $client_id ) || empty( $client_secret ) || empty( $refresh_token )) {
            return new WP_Error( 'sheets_auth_error', 'Google Sheets authentication not configured', array( 'status' => 500 ) );
        }

        $response = wp_remote_post(
            'https://oauth2.googleapis.com/token',
            array(
                'body'    => array(
                    'client_id'     => $client_id,
                    'client_secret' => $client_secret,
                    'refresh_token' => $refresh_token,
                    'grant_type'    => 'refresh_token',
                ),
                'timeout' => 30,
            )
        );

        if (is_wp_error( $response )) {
            return new WP_Error( 'sheets_auth_error', 'Failed to get access token: ' . $response->get_error_message(), array( 'status' => 500 ) );
        }

        $body   = wp_remote_retrieve_body( $response );
        $result = json_decode( $body, true );

        if ($result && isset( $result['access_token'] )) {
            return $result['access_token'];
        } else {
            $error_message = isset( $result['error_description'] ) ? $result['error_description'] : 'Unknown error';
            return new WP_Error( 'sheets_auth_error', 'Failed to get access token: ' . $error_message, array( 'status' => 500 ) );
        }
    }


    /**
     * Validate Google Sheets configuration
     *
     * @param string $spreadsheet_id Spreadsheet ID
     * @param string $sheet_name Sheet name
     * @return bool|WP_Error Validation result
     */
    public static function validate_config( $spreadsheet_id, $sheet_name = '' ) {
        if (empty( $spreadsheet_id )) {
            return new WP_Error( 'sheets_config_error', 'Spreadsheet ID is required', array( 'status' => 400 ) );
        }

        if (! preg_match( '/^[a-zA-Z0-9-_]+$/', $spreadsheet_id )) {
            return new WP_Error( 'sheets_config_error', 'Invalid spreadsheet ID format', array( 'status' => 400 ) );
        }

        $access_token = self::get_access_token();
        if (is_wp_error( $access_token )) {
            return $access_token;
        }

        $response = wp_remote_get(
            "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}",
            array(
                'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
                'timeout' => 30,
            )
        );

        if (is_wp_error( $response )) {
            return new WP_Error( 'sheets_config_error', 'Cannot access spreadsheet: ' . $response->get_error_message(), array( 'status' => 400 ) );
        }

        $body   = wp_remote_retrieve_body( $response );
        $result = json_decode( $body, true );

        if (! $result || isset( $result['error'] )) {
            $error_message = isset( $result['error']['message'] ) ? $result['error']['message'] : 'Cannot access spreadsheet';
            return new WP_Error( 'sheets_config_error', $error_message, array( 'status' => 400 ) );
        }

        return true;
    }


    /**
     * Test connection to Google Sheets
     *
     * @param string $spreadsheet_id Spreadsheet ID to test
     * @return array Test results
     */
    public static function test_connection( $spreadsheet_id ) {
        $results = array(
            'authentication' => array(
                'valid' => false,
                'error' => '',
            ),
            'spreadsheet'    => array(
                'accessible' => false,
                'error'      => '',
            ),
        );

        $access_token = self::get_access_token();
        if (is_wp_error( $access_token )) {
            $results['authentication']['error'] = $access_token->get_error_message();
            return $results;
        }

        $results['authentication']['valid'] = true;

        $response = wp_remote_get(
            "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}",
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type'  => 'application/json',
                ),
                'timeout' => 30,
            )
        );

        if (is_wp_error( $response )) {
            $results['spreadsheet']['error'] = 'Network error: ' . $response->get_error_message();
            return $results;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ($status_code === 200) {
            $results['spreadsheet']['accessible'] = true;
        } else {
            $body                            = json_decode( wp_remote_retrieve_body( $response ), true );
            $error_message                   = isset( $body['error']['message'] ) ? $body['error']['message'] : 'Unknown error';
            $results['spreadsheet']['error'] = "HTTP {$status_code}: {$error_message}";
        }

        return $results;
    }


    /**
     * Get Google OAuth authorization URL
     *
     * @return string Authorization URL
     */
    public static function get_auth_url() {
        $client_id    = get_option( 'google_sheets_client_id' );
        $redirect_uri = admin_url( 'options-general.php?page=google-sheets-settings' );

        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query(
            array(
                'client_id'     => $client_id,
                'redirect_uri'  => $redirect_uri,
                'scope'         => 'https://www.googleapis.com/auth/spreadsheets',
                'response_type' => 'code',
                'access_type'   => 'offline',
                'prompt'        => 'consent select_account',
            )
        );
    }


}
