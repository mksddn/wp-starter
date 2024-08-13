<?php

function handle_contact_form_submission()
{
  // Проверка nonce
  if (!isset($_POST['contact_form_nonce_field']) || !wp_verify_nonce($_POST['contact_form_nonce_field'], 'send_contact_form_nonce')) {
    wp_send_json_error(array('message' => 'Security error. Please try again.'));
  }

  // Проверка наличия необходимых данных
  if (isset($_POST['recipients'], $_POST['subject'], $_POST['action'])) {

    $recipients = explode(',', sanitize_text_field($_POST['recipients']));
    $bcc_recipient  = trim($_POST["bcc_recipient"]);
    $subject = trim($_POST["subject"]);

    // Проверка каждого email на корректность
    $valid_emails = array();
    foreach ($recipients as $recipient) {
      $recipient = trim($recipient); // Убираем пробелы по краям
      if (is_email($recipient)) {
        $valid_emails[] = $recipient; // Если email корректный, добавляем его в список
      } else {
        // Если хотя бы один email некорректен, останавливаем выполнение и выводим ошибку
        wp_send_json_error(array('message' => "Incorrect email address: $recipient"));
      }
    }

    // Если список валидных email не пуст, отправляем письмо
    if (!empty($valid_emails)) {
      $body = "";
      $c = true;
      foreach ($_POST as $key => $value) {
        if ($value != "" && $key != "recipients" && $key != "subject" && $key != "action" && $key != "bcc_recipient" && $key != "contact_form_nonce_field" && $key != "_wp_http_referer") {
          $body .= "
        " . (($c = !$c) ? '<tr>' : '<tr style="background-color: #f8f8f8;">') . "
          <td style='padding: 10px; border: #e9e9e9 1px solid;'><b>" . sanitize_text_field($key) . "</b></td>
          <td style='padding: 10px; border: #e9e9e9 1px solid;'>" . sanitize_text_field($value) . "</td>
        </tr>
        ";
        }
      }
      $body = "<table style='width: 100%;'>$body</table>";

      // Отправка письма
      $headers[] = 'Content-Type: text/html; charset=UTF-8';
      $headers[] = 'Bcc: ' . $bcc_recipient . '';
      if (wp_mail($valid_emails, $subject, $body, $headers)) {
        // Если письмо успешно отправлено
        wp_send_json_success(array('message' => 'Message sent successfully!'));
      } else {
        // Если отправка письма не удалась
        wp_send_json_error(array('message' => 'Failed to send message. Please try again later.'));
      }
    } else {
      wp_send_json_error(array('message' => 'The recipient list is empty or invalid.'));
    }
  } else {
    // Если данные отсутствуют, перенаправляем обратно на форму
    wp_send_json_error(array('message' => 'Please fill in all required fields.'));
  }
}

add_action('admin_post_send_contact_form', 'handle_contact_form_submission');
add_action('admin_post_nopriv_send_contact_form', 'handle_contact_form_submission');
