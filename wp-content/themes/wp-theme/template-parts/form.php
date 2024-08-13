<form class="contact-form" method="POST">

  <?php
  global $wp;
  // Укажи получателей 👇
  $recipients = get_bloginfo('admin_email');
  // Укажи скрытого получателя 👇
  $bcc_recipient = '';
  // Укажи тему письма (заголовок) 👇
  $subject = 'New message from website ' . get_bloginfo('name');
  ?>

  <!-- Обязательно указывай атрибут name -->
  <input type="text" id="name" name="Name" placeholder="Name"><br><br>
  <input type="email" id="email" name="Email" placeholder="Email"><br><br>
  <textarea id="message" name="Message" placeholder="Message"></textarea><br><br>
  <input type="submit" value="Send">
  <!-- END Обязательно указывай атрибут name -->

  <!-- Скрытые поля не трогаем -->
  <input type="hidden" name="recipients" value="<?php echo $recipients; ?>">
  <input type="hidden" name="bcc_recipient" value="<?php echo $bcc_recipient; ?>">
  <input type="hidden" name="subject" value="<?php echo $subject; ?>">
  <input type="hidden" name="URL" value="<?php echo home_url($wp->request); ?>">
  <input type="hidden" name="action" value="send_contact_form">
  <?php wp_nonce_field('send_contact_form_nonce', 'contact_form_nonce_field'); ?>
  <!-- END Скрытые поля не трогаем -->

</form>