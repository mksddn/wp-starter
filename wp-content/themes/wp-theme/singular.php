<?php
get_header();
?>
<main id="primary" class="site-main">

	<?php
	while (have_posts()) :
		the_post();
		get_template_part('template-parts/content');
		get_template_part('template-parts/acf-blocks');
	endwhile; ?>

</main>
<?php
get_footer();
