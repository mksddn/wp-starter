<?php
get_header(); ?>

<div class="container" style="max-width: 1440px; padding: 50px 15px; margin: 0 auto;">
  <h2>Это демо страница твоего нового проекта, ты можешь ее удалить (<code>/front-page.php</code>)</h2>
  <h3>Но обрати внимание на реализацию работы формы, это тебе может пригодиться 😉 (<code>/inc/form.php</code>)</h3>
  <h4>Имей в виду, что локально отправка формы не работает</h4>
  <?php get_template_part('template-parts/form'); ?>

</div>

<?php
get_footer();
