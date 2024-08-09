<?php

function handle_contact_form_submission()
{
  // Проверка nonce
  if (!isset($_POST['contact_form_nonce_field']) || !wp_verify_nonce($_POST['contact_form_nonce_field'], 'send_contact_form_nonce')) {
    wp_die('Ошибка безопасности. Попробуйте снова.');
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
        wp_die("Некорректный email адрес: $recipient");
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
        wp_redirect(home_url('/?success=1'));
        exit();
      } else {
        // Если отправка письма не удалась
        wp_redirect(home_url('/?error=1'));
        exit();
      }
    } else {
      wp_die('Не удалось отправить письмо: список получателей пуст или некорректен.');
    }
  } else {
    // Если данные отсутствуют, перенаправляем обратно на форму
    wp_redirect(home_url('/?error=1'));
    exit();
  }
}

add_action('admin_post_send_contact_form', 'handle_contact_form_submission');
add_action('admin_post_nopriv_send_contact_form', 'handle_contact_form_submission');
