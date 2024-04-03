<?php

/**
 * Template Name: Страница с блоками ACF
 */
get_header();
?>
<main id="primary" class="site-main">

	<?php
	get_template_part('template-parts/acf-blocks');
	?>

</main>
<?php
get_footer();
