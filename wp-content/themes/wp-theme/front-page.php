<?php
/**
 * Front page template.
 *
 * @package wp-theme
 */

get_header(); ?>

<div class="container" style="max-width: 1440px; padding: 50px 15px; margin: 0 auto;">
    <h2>Это демо страница твоего нового проекта, ты можешь ее удалить (<code>/front-page.php</code>)</h2>
    <h3>Но обрати внимание на реализацию работы формы, это тебе может пригодиться 😉</h3>
  
    <?php echo do_shortcode( '[form id="contact-form"]' ); ?>
</div>

<?php
get_footer();
