<?php get_header(); ?>
<main>
<article class="single-post">
  <h1><?php the_title(); ?></h1>
  
  <?php if (get_post_type() === 'mcq_question'): ?>
    <!-- MCQ Question - Use dedicated template -->
    <?php get_template_part('template-parts/mcq'); ?>
  <?php else: ?>
    <!-- Regular Post - Standard layout -->
    <div class="post-meta">
      <span class="post-date"><?php echo get_the_date(); ?></span>
      <span class="post-author">By <?php the_author(); ?></span>
    </div>
    
    <div class="post-content">
      <?php the_content(); ?>
    </div>
    
    <?php if (has_tag() || has_category()): ?>
      <div class="post-terms">
        <?php if (has_category()): ?>
          <div class="post-categories">
            <?php the_category(', '); ?>
          </div>
        <?php endif; ?>
        
        <?php if (has_tag()): ?>
          <div class="post-tags">
            <?php the_tags('', ', ', ''); ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    
    <?php get_template_part('template-parts/comments-toggle'); ?>
  <?php endif; ?>
</article>
</main>
<?php get_footer(); ?>
