<?php
if (have_rows('acf_blocks')) :
  while (have_rows('acf_blocks')) : the_row();

    if (get_row_layout() == 'hello_world') :
      get_template_part('template-parts/acf-blocks/hello_world');
    // elseif (get_row_layout() == 'another_greeting') :
    //   get_template_part('template-parts/acf-blocks/another_greeting');
    endif;

  endwhile;
endif;
