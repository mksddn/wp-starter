<?php
/**
 * Template part for displaying search result content.
 *
 * @package wp-theme
 */

?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
    <?php the_excerpt(); ?>
</article>