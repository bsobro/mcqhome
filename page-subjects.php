<?php
/**
 * Template Name: Subjects Page
 * Description: Displays MCQ questions organized by subjects with category listings
 */

get_header(); ?>

<main>
  <div class="subjects-container">
    <!-- Hero Section -->
    <section class="subjects-hero">
      <h1>MCQ Subjects</h1>
      <p>Explore questions by subject and dive deep into specific topics</p>
    </section>

    <!-- Subjects Grid -->
    <section class="subjects-grid">
      <?php
      $subjects = get_terms([
        'taxonomy' => 'mcq_subject',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
      ]);

      if ($subjects && !is_wp_error($subjects)) :
        foreach ($subjects as $subject) :
          $subject_count = $subject->count;
          $subject_link = get_term_link($subject);
          
          // Get topics under this subject
          $topics = get_terms([
            'taxonomy' => 'mcq_topic',
            'hide_empty' => false,
            'meta_query' => [
              [
                'key' => 'subject',
                'value' => $subject->term_id,
                'compare' => '='
              ]
            ]
          ]);
          
          // Get exams under this subject
          $exams = get_terms([
            'taxonomy' => 'mcq_exam',
            'hide_empty' => false
          ]);
          
          // Count questions for this subject
          $questions_count = new WP_Query([
            'post_type' => 'mcq_question',
            'posts_per_page' => -1,
            'tax_query' => [
              [
                'taxonomy' => 'mcq_subject',
                'field' => 'term_id',
                'terms' => $subject->term_id
              ]
            ]
          ]);
          $total_questions = $questions_count->found_posts;
          wp_reset_postdata();
          ?>
          
          <div class="subject-card">
            <div class="subject-header">
              <h2><?php echo esc_html($subject->name); ?></h2>
              <span class="question-count"><?php echo $total_questions; ?> Questions</span>
            </div>
            
            <?php if (!empty($subject->description)) : ?>
              <p class="subject-description"><?php echo esc_html($subject->description); ?></p>
            <?php endif; ?>
            
            <!-- Topics under this subject -->
            <?php
            $subject_topics = get_terms([
              'taxonomy' => 'mcq_topic',
              'hide_empty' => true,
              'object_ids' => get_posts([
                'post_type' => 'mcq_question',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'tax_query' => [
                  [
                    'taxonomy' => 'mcq_subject',
                    'field' => 'term_id',
                    'terms' => $subject->term_id
                  ]
                ]
              ])
            ]);
            
            if (!empty($subject_topics) && !is_wp_error($subject_topics)) : ?>
              <div class="subject-topics">
                <h4>Topics:</h4>
                <div class="topic-tags">
                  <?php foreach (array_slice($subject_topics, 0, 8) as $topic) : ?>
                    <a href="<?php echo get_term_link($topic); ?>" class="topic-tag">
                      <?php echo esc_html($topic->name); ?>
                      <span class="topic-count">(<?php echo $topic->count; ?>)</span>
                    </a>
                  <?php endforeach; ?>
                  <?php if (count($subject_topics) > 8) : ?>
                    <span class="more-topics">+<?php echo count($subject_topics) - 8; ?> more</span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>
            
            <!-- Difficulty levels for this subject -->
            <?php
            $difficulties = get_terms([
              'taxonomy' => 'mcq_difficulty',
              'hide_empty' => true,
              'object_ids' => get_posts([
                'post_type' => 'mcq_question',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'tax_query' => [
                  [
                    'taxonomy' => 'mcq_subject',
                    'field' => 'term_id',
                    'terms' => $subject->term_id
                  ]
                ]
              ])
            ]);
            
            if (!empty($difficulties) && !is_wp_error($difficulties)) : ?>
              <div class="subject-difficulties">
                <h4>Difficulty Levels:</h4>
                <div class="difficulty-tags">
                  <?php foreach ($difficulties as $difficulty) : ?>
                    <span class="difficulty-tag difficulty-<?php echo esc_attr($difficulty->slug); ?>">
                      <?php echo esc_html($difficulty->name); ?>
                      <span class="difficulty-count">(<?php echo $difficulty->count; ?>)</span>
                    </span>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
            
            <div class="subject-actions">
              <a href="<?php echo esc_url($subject_link); ?>" class="btn btn-primary">
                View All Questions
              </a>
              <a href="<?php echo esc_url(add_query_arg('mcq_subject', $subject->slug, home_url('/questions'))); ?>" class="btn btn-secondary">
                Practice Now
              </a>
            </div>
          </div>
          
        <?php endforeach; ?>
      else :
        echo '<p>No subjects found.</p>';
      endif;
      ?>
    </section>

    <!-- Recent Questions Section -->
    <section class="recent-questions-section">
      <h2>Recent Questions</h2>
      <div class="recent-mcqs">
        <?php
        $recent_questions = new WP_Query([
          'post_type' => 'mcq_question',
          'posts_per_page' => 3,
          'orderby' => 'date',
          'order' => 'DESC'
        ]);

        if ($recent_questions->have_posts()) :
          while ($recent_questions->have_posts()) : $recent_questions->the_post();
            get_template_part('template-parts/mcq');
          endwhile;
          wp_reset_postdata();
        else :
          echo '<p>No recent questions found.</p>';
        endif;
        ?>
      </div>
      
      <div class="view-all-link">
        <a href="<?php echo home_url('/questions'); ?>" class="btn btn-large">
          View All Questions
        </a>
      </div>
    </section>
  </div>
</main>

<style>
.subjects-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.subjects-hero {
  text-align: center;
  margin-bottom: 3rem;
}

.subjects-hero h1 {
  font-size: 2.5rem;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.subjects-hero p {
  font-size: 1.2rem;
  color: #64748b;
}

.subjects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 2rem;
  margin-bottom: 3rem;
}

.subject-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.subject-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.subject-header h2 {
  font-size: 1.5rem;
  color: #1e293b;
  margin: 0;
}

.question-count {
  background: #3b82f6;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 500;
}

.subject-description {
  color: #64748b;
  margin-bottom: 1.5rem;
  line-height: 1.5;
}

.subject-topics,
.subject-difficulties {
  margin-bottom: 1.5rem;
}

.subject-topics h4,
.subject-difficulties h4 {
  font-size: 1rem;
  color: #374151;
  margin-bottom: 0.5rem;
}

.topic-tags,
.difficulty-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.topic-tag,
.difficulty-tag {
  background: #f3f4f6;
  color: #374151;
  padding: 0.25rem 0.75rem;
  border-radius: 16px;
  font-size: 0.875rem;
  text-decoration: none;
  transition: background-color 0.2s;
}

.topic-tag:hover {
  background: #e5e7eb;
}

.difficulty-tag.easy {
  background: #dcfce7;
  color: #166534;
}

.difficulty-tag.medium {
  background: #fef3c7;
  color: #92400e;
}

.difficulty-tag.hard {
  background: #fee2e2;
  color: #991b1b;
}

.subject-actions {
  display: flex;
  gap: 1rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 500;
  transition: background-color 0.2s;
  border: none;
  cursor: pointer;
  display: inline-block;
  text-align: center;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background: #4b5563;
}

.btn-large {
  background: #1e293b;
  color: white;
  padding: 1rem 2rem;
  font-size: 1.125rem;
}

.recent-questions-section {
  margin-top: 4rem;
  padding-top: 3rem;
  border-top: 1px solid #e2e8f0;
}

.recent-questions-section h2 {
  text-align: center;
  font-size: 2rem;
  color: #1e293b;
  margin-bottom: 2rem;
}

.recent-mcqs {
  margin-bottom: 2rem;
}

.view-all-link {
  text-align: center;
}

@media (max-width: 768px) {
  .subjects-grid {
    grid-template-columns: 1fr;
  }
  
  .subject-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .subject-actions {
    flex-direction: column;
  }
}
</style>

<?php get_footer(); ?>