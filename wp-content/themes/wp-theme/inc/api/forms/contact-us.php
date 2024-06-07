<?php

add_action('rest_api_init', function () {
  register_rest_route('custom/v1', '/contact-us', array(
    'methods' => 'POST',
    'callback' => 'handle_submit_form',
    'permission_callback' => '__return_true',
  ));
});

function handle_submit_form(WP_REST_Request $request)
{
  $parameters = $request->get_json_params();

  $name = sanitize_text_field($parameters['name'] ?? null);
  $email = sanitize_email($parameters['email'] ?? null);
  $phone = sanitize_text_field($parameters['phone'] ?? null);
  $message = sanitize_textarea_field($parameters['message'] ?? null);
  $is_agree = isset($parameters['isAgree']) ? filter_var($parameters['isAgree'], FILTER_VALIDATE_BOOLEAN) : false;

  if (!is_bool($is_agree)) {
    wp_send_json_error('You need to confirm the agreement');
  }

  if (empty($name) || empty($email) || empty($phone)) {
    wp_send_json_error('Fill in all required fields');
  }

  if (empty($email) || !is_email($email)) {
    wp_send_json_error('Enter the correct email');
  }

  // Check the emails!
  $emails = get_field('email_for_notifications', 53);

  if (!$emails) {
    wp_send_json_success('The form has been processed, but no mail has been specified for sending');
  }

  // Check the subject!
  $subject = 'You have a new message from the website!';

  $body = "Name: $name\n";
  $body .= "Phone: $phone\n";
  $body .= "Email: $email\n";
  $body .= "Message: $message\n";

  $is_send = wp_mail(
    $emails,
    $subject,
    $body
  );

  if ($is_send) {
    wp_send_json_success('The form has been sent');
  } else {
    wp_send_json_error('Error sending - try again');
  }
}
