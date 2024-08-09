<form id="contactForm" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST">

  <?php
  global $wp;
  // Укажи получателей 👇
  $recipients = get_bloginfo('admin_email');
  // Укажи скрытого получателя 👇
  $bcc_recipient = '';
  // Укажи тему письма (заголовок) 👇
  $subject = 'Новое сообщение с сайта ' . get_bloginfo('name');
  ?>

  <!-- Обязательно указывай атрибут name -->
  <input type="text" id="name" name="Name" placeholder="Имя" required><br><br>
  <input type="email" id="email" name="Email" placeholder="Email" required><br><br>
  <textarea id="message" name="Message" placeholder="Сообщение" required></textarea><br><br>
  <input type="submit" value="Отправить">
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


<div id="formErrors" style="color: red; margin-top: 10px;"></div>
<div id="formSuccess" style="color: green; margin-top: 10px;"></div>


<script>
  // ХОТЬ ЗАВАЛИДИРУЙСЯ 👇
  document.getElementById('contactForm').addEventListener('submit', function(event) {
    // Очистка предыдущих ошибок
    document.getElementById('formErrors').innerHTML = '';
    document.getElementById('formSuccess').innerHTML = '';
    // Получаем значения полей
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const message = document.getElementById('message').value.trim();
    const errors = [];
    // Проверка имени (должно быть заполнено)
    if (name === '') {
      errors.push('Пожалуйста, введите ваше имя.');
    }
    // Проверка email (должен быть валидным)
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email === '') {
      errors.push('Пожалуйста, введите ваш email.');
    } else if (!emailPattern.test(email)) {
      errors.push('Пожалуйста, введите корректный email.');
    }
    // Проверка сообщения (должно быть заполнено)
    if (message === '') {
      errors.push('Пожалуйста, введите сообщение.');
    }
    // Если есть ошибки, отменяем отправку формы и показываем ошибки
    if (errors.length > 0) {
      event.preventDefault();
      document.getElementById('formErrors').innerHTML = errors.join('<br>');
    }
  });


  // РЕНДЕР УВЕДОМЛЕНИЙ 👇
  document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
      document.getElementById('formErrors').innerText = 'Ошибка отправки формы. Попробуйте снова.';
    }
    if (urlParams.has('success')) {
      document.getElementById('formSuccess').innerText = 'Ваше сообщение успешно отправлено!';
    }
  });
</script>