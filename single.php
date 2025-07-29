<?php get_header(); ?>
<main>
<article>
<!--   <h2>Q. <?php the_title(); ?></h2> -->
  <?php the_content(); ?>
  <?php get_template_part('template-parts/mcq'); ?>
</article>
</main>
<?php get_footer(); ?>
