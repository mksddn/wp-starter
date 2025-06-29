<?php
get_header(); ?>

<div class="container" style="max-width: 1440px; padding: 50px 15px; margin: 0 auto;">
  <h2>Это демо страница твоего нового проекта, ты можешь ее удалить (<code>/front-page.php</code>)</h2>
  <h3>Унифицированная система форм с админ-панелью и REST API поддержкой</h3>
  <h4>Имей в виду, что локально отправка формы не работает</h4>
  
  <?php echo do_shortcode('[form id="contact-form"]'); ?>
</div>

<?php
get_footer();
