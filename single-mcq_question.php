<?php get_header(); ?>

<main>
  <div class="single-mcq-container">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      
      <article class="single-mcq-question">
        <!-- Question Header -->
        <header class="mcq-question-header">
          <div class="breadcrumb">
            <a href="<?php echo esc_url(home_url('/questions')); ?>">MCQ Hub</a>
            <?php
            $subject = get_the_terms(get_the_ID(), 'mcq_subject');
            $exam = get_the_terms(get_the_ID(), 'mcq_exam');
            $topic = get_the_terms(get_the_ID(), 'mcq_topic');
            
            if ($subject) {
              echo ' > <a href="' . esc_url(get_term_link($subject[0])) . '">' . esc_html($subject[0]->name) . '</a>';
            }
            if ($exam) {
              echo ' > <a href="' . esc_url(get_term_link($exam[0])) . '">' . esc_html($exam[0]->name) . '</a>';
            }
            if ($topic) {
              echo ' > <a href="' . esc_url(get_term_link($topic[0])) . '">' . esc_html($topic[0]->name) . '</a>';
            }
            ?>
          </div>
          
          <h1 class="question-title"><?php the_title(); ?></h1>
          
          <div class="question-meta">
            <?php
            $subject = get_the_terms(get_the_ID(), 'mcq_subject');
            $exam = get_the_terms(get_the_ID(), 'mcq_exam');
            $topic = get_the_terms(get_the_ID(), 'mcq_topic');
            $difficulty = get_the_terms(get_the_ID(), 'mcq_difficulty');
            $year = get_field('year');
            ?>
            
            <div class="meta-tags">
              <?php if ($subject): ?>
                <span class="meta-tag subject-tag"><?php echo esc_html($subject[0]->name); ?></span>
              <?php endif; ?>
              
              <?php if ($exam): ?>
                <span class="meta-tag exam-tag"><?php echo esc_html($exam[0]->name); ?></span>
              <?php endif; ?>
              
              <?php if ($topic): ?>
                <span class="meta-tag topic-tag"><?php echo esc_html($topic[0]->name); ?></span>
              <?php endif; ?>
              
              <?php if ($difficulty): ?>
                <span class="meta-tag difficulty-tag difficulty-<?php echo esc_attr($difficulty[0]->slug); ?>">
                  <?php echo esc_html($difficulty[0]->name); ?>
                </span>
              <?php endif; ?>
              
              <?php if ($year): ?>
                <span class="meta-tag year-tag"><?php echo esc_html($year); ?></span>
              <?php endif; ?>
            </div>
          </div>
        </header>

        <!-- Question Content -->
        <div class="question-content">
          <div class="question-text">
            <?php echo get_field('question_text'); ?>
          </div>

          <!-- Options -->
          <div class="options-container">
            <?php
            $options = [
              'A' => get_field('option_a'),
              'B' => get_field('option_b'),
              'C' => get_field('option_c'),
              'D' => get_field('option_d')
            ];
            $correct = strtoupper(trim(get_field('correct_answer')));
            $explanation = get_field('explanation');
            $post_id = get_the_ID();
            ?>

            <div class="options-list">
              <?php foreach ($options as $label => $text): ?>
                <button class="mcq-option" 
                        data-option="<?php echo esc_attr($label); ?>" 
                        data-post="<?php echo esc_attr($post_id); ?>">
                  <span class="option-label"><?php echo esc_html($label); ?></span>
                  <span class="option-text"><?php echo esc_html($text); ?></span>
                </button>
              <?php endforeach; ?>
            </div>

            <!-- Feedback -->
            <div class="mcq-feedback" style="display:none;">
              <div class="feedback-content"></div>
              <?php if ($explanation): ?>
                <div class="explanation">
                  <h3>Explanation</h3>
                  <?php echo $explanation; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Question Actions -->
        <div class="question-actions">
          <div class="action-buttons">
            <button class="action-btn bookmark-btn" data-question="<?php echo $post_id; ?>">
              <i class="dashicons dashicons-bookmark"></i>
              Bookmark
            </button>
            
            <button class="action-btn report-btn" data-question="<?php echo $post_id; ?>">
              <i class="dashicons dashicons-flag"></i>
              Report
            </button>
          </div>

          <?php if (function_exists('wp_ulike')) : ?>
            <div class="like-container">
              <?php wp_ulike('get'); ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Related Questions -->
        <section class="related-questions">
          <h2>Related Questions</h2>
          <?php
          $related_args = [
            'post_type' => 'mcq_question',
            'posts_per_page' => 4,
            'post__not_in' => [get_the_ID()],
            'tax_query' => []
          ];

          if ($subject) {
            $related_args['tax_query'][] = [
              'taxonomy' => 'mcq_subject',
              'field' => 'term_id',
              'terms' => $subject[0]->term_id
            ];
          }

          if ($topic) {
            $related_args['tax_query'][] = [
              'taxonomy' => 'mcq_topic',
              'field' => 'term_id',
              'terms' => $topic[0]->term_id
            ];
          }

          $related = new WP_Query($related_args);
          
          if ($related->have_posts()):
            echo '<div class="related-grid">';
            while ($related->have_posts()): $related->the_post();
              get_template_part('template-parts/mcq-card');
            endwhile;
            echo '</div>';
            wp_reset_postdata();
          endif;
          ?>
        </section>

      </article>

    <?php endwhile; endif; ?>
  </div>
</main>

<style>
.single-mcq-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem;
}

.single-mcq-question {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.mcq-question-header {
  padding: 2rem;
  border-bottom: 1px solid #f1f5f9;
}

.breadcrumb {
  margin-bottom: 1rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.breadcrumb a {
  color: #0077cc;
  text-decoration: none;
}

.breadcrumb a:hover {
  text-decoration: underline;
}

.question-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 1rem;
  line-height: 1.4;
}

.question-meta {
  margin-bottom: 1rem;
}

.meta-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.meta-tag {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  color: white;
}

.subject-tag {
  background: #0077cc;
}

.exam-tag {
  background: #6b7280;
}

.topic-tag {
  background: #8b5cf6;
}

.difficulty-tag {
  background: #f59e0b;
}

.difficulty-easy {
  background: #10b981;
}

.difficulty-medium {
  background: #f59e0b;
}

.difficulty-hard {
  background: #ef4444;
}

.year-tag {
  background: #6366f1;
}

.question-content {
  padding: 2rem;
}

.question-text {
  font-size: 1.125rem;
  line-height: 1.6;
  margin-bottom: 2rem;
  color: #374151;
}

.options-container {
  margin-bottom: 2rem;
}

.options-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.mcq-option {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: all 0.3s;
  width: 100%;
  text-align: left;
}

.mcq-option:hover {
  border-color: #0077cc;
  background: #f8fafc;
}

.mcq-option.correct {
  border-color: #10b981;
  background: #f0fdf4;
}

.mcq-option.incorrect {
  border-color: #ef4444;
  background: #fef2f2;
}

.mcq-option:disabled {
  cursor: not-allowed;
  opacity: 0.8;
}

.option-label {
  background: #0077cc;
  color: white;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  flex-shrink: 0;
}

.option-text {
  flex: 1;
  text-align: left;
  font-size: 1rem;
  line-height: 1.5;
}

.mcq-feedback {
  margin-top: 2rem;
  padding: 1.5rem;
  border-radius: 8px;
  background: #f8fafc;
}

.feedback-content {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.feedback-content.correct {
  color: #10b981;
}

.feedback-content.incorrect {
  color: #ef4444;
}

.explanation {
  border-top: 1px solid #e5e7eb;
  padding-top: 1rem;
}

.explanation h3 {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #374151;
}

.question-actions {
  padding: 2rem;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.action-buttons {
  display: flex;
  gap: 1rem;
}

.action-btn {
  background: none;
  border: 1px solid #d1d5db;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.875rem;
  color: #6b7280;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.action-btn:hover {
  background: #f3f4f6;
  border-color: #9ca3af;
}

.like-container {
  margin-left: auto;
}

.related-questions {
  padding: 2rem;
  border-top: 1px solid #f1f5f9;
}

.related-questions h2 {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  color: #1f2937;
}

.related-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
}

@media (max-width: 768px) {
  .single-mcq-container {
    padding: 1rem;
  }
  
  .question-actions {
    flex-direction: column;
    align-items: stretch;
  }
  
  .action-buttons {
    justify-content: center;
  }
  
  .like-container {
    margin-left: 0;
    text-align: center;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const options = document.querySelectorAll('.mcq-option');
  const feedback = document.querySelector('.mcq-feedback');
  const feedbackContent = document.querySelector('.feedback-content');
  
  const correctAnswer = '<?php echo esc_js(get_field('correct_answer')); ?>';
  
  options.forEach(option => {
    option.addEventListener('click', function() {
      const selected = this.getAttribute('data-option');
      
      // Disable all options
      options.forEach(opt => opt.disabled = true);
      
      // Add correct/incorrect classes
      options.forEach(opt => {
        const optValue = opt.getAttribute('data-option');
        if (optValue === correctAnswer) {
          opt.classList.add('correct');
        } else if (optValue === selected && optValue !== correctAnswer) {
          opt.classList.add('incorrect');
        }
      });
      
      // Show feedback
      if (selected === correctAnswer) {
        feedbackContent.innerHTML = '<i class="dashicons dashicons-yes"></i> Correct! Well done!';
        feedbackContent.className = 'feedback-content correct';
      } else {
        feedbackContent.innerHTML = '<i class="dashicons dashicons-no"></i> Incorrect. The correct answer is ' + correctAnswer + '.';
        feedbackContent.className = 'feedback-content incorrect';
      }
      
      feedback.style.display = 'block';
    });
  });
});
</script>

<?php get_footer(); ?>