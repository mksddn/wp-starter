<?php
get_header();
?>
<main id="primary" class="site-main">

	<?php
	if (function_exists('yoast_breadcrumb')) {
		yoast_breadcrumb('<p id="breadcrumbs">', '</p>');
	}
	?>

	<section class="error-404 not-found">
		<article>
			<h1>
				404
			</h1>
			<br>
			<p>Oops! That page can’t be found.</p>
			</p>It looks like nothing was found at this location.</p>
		</article>
	</section>

</main>
<?php
get_footer();
