<?php get_header(); ?>
<main>
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
  <article>
<!--     <h2>
		<span><?php the_title(); ?></span>
	  </h2> -->
	  
    <?php the_content(); ?>
    <?php get_template_part('template-parts/mcq'); ?>
  </article>
<?php endwhile; endif; ?>
</main>
<?php get_footer(); ?>
