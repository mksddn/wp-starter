<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
  <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
  <br>
  <?php the_content(); ?>
</article>