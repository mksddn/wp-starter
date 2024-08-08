<?php

function handle_contact_form_submission()
{
  // Лучше здесь вообще ничего не менять
  $c = true;
  $recipient  = trim($_POST["recipient"]);
  $subject = trim($_POST["subject"]);
  if (isset($_POST['recipient'], $_POST['subject'], $_POST['action'])) {
    $body = "";
    foreach ($_POST as $key => $value) {
      if ($value != "" && $key != "recipient" && $key != "subject" && $key != "action") {
        $body .= "
        " . (($c = !$c) ? '<tr>' : '<tr style="background-color: #f8f8f8;">') . "
          <td style='padding: 10px; border: #e9e9e9 1px solid;'><b>$key</b></td>
          <td style='padding: 10px; border: #e9e9e9 1px solid;'>$value</td>
        </tr>
        ";
      }
    }
    $body = "<table style='width: 100%;'>$body</table>";

    // Отправка письма
    $headers = array('Content-Type: text/html; charset=UTF-8');
    if (wp_mail($recipient, $subject, $body, $headers)) {
      // Если письмо успешно отправлено
      wp_redirect(home_url('/?success=1'));
      exit();
    } else {
      // Если отправка письма не удалась
      wp_redirect(home_url('/?error=1'));
      exit();
    }
  } else {
    // Если данные отсутствуют, перенаправляем обратно на форму
    wp_redirect(home_url('/?error=1'));
    exit();
  }
}

add_action('admin_post_send_contact_form', 'handle_contact_form_submission');
add_action('admin_post_nopriv_send_contact_form', 'handle_contact_form_submission');
