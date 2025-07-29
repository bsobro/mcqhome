<?php
$question_id = get_the_ID();
$subject = get_the_terms($question_id, 'mcq_subject');
$exam = get_the_terms($question_id, 'mcq_exam');
$topic = get_the_terms($question_id, 'mcq_topic');
$difficulty = get_the_terms($question_id, 'mcq_difficulty');
$year = get_field('year', $question_id);

// Get taxonomy colors
$subject_colors = [
  'mathematics' => '#0077cc',
  'science' => '#28a745',
  'history' => '#ffc107',
  'literature' => '#6f42c1',
  'current-affairs' => '#dc3545',
  'geography' => '#17a2b8',
  'economics' => '#fd7e14',
  'polity' => '#e83e8c'
];

$subject_color = '#0077cc';
if ($subject && isset($subject[0])) {
  $subject_color = $subject_colors[$subject[0]->slug] ?? '#0077cc';
}
?>

<article class="mcq-card" data-question-id="<?php echo $question_id; ?>">
  <div class="mcq-card-header">
    <div class="mcq-meta">
      <?php if ($subject): ?>
        <span class="subject-tag" style="background-color: <?php echo esc_attr($subject_color); ?>">
          <?php echo esc_html($subject[0]->name); ?>
        </span>
      <?php endif; ?>
      
      <?php if ($exam): ?>
        <span class="exam-tag"><?php echo esc_html($exam[0]->name); ?></span>
      <?php endif; ?>
      
      <?php if ($topic): ?>
        <span class="topic-tag"><?php echo esc_html($topic[0]->name); ?></span>
      <?php endif; ?>
      
      <?php if ($difficulty): ?>
        <span class="difficulty-tag difficulty-<?php echo esc_attr($difficulty[0]->slug); ?>">
          <?php echo esc_html($difficulty[0]->name); ?>
        </span>
      <?php endif; ?>
      
      <?php if ($year): ?>
        <span class="year-tag"><?php echo esc_html($year); ?></span>
      <?php endif; ?>
    </div>
  </div>

  <div class="mcq-card-body">
    <h3 class="question-title"><?php the_title(); ?></h3>
    <div class="question-preview">
      <?php 
      $question_text = get_field('question_text', $question_id);
      echo wp_trim_words(wp_strip_all_tags($question_text), 20, '...'); 
      ?>
    </div>
  </div>

  <div class="mcq-card-footer">
    <div class="question-stats">
      <span class="view-count">
        <i class="dashicons dashicons-visibility"></i>
        <?php echo get_post_meta($question_id, 'post_views_count', true) ?: '0'; ?> views
      </span>
      <span class="like-count">
        <i class="dashicons dashicons-thumbs-up"></i>
        <?php echo get_post_meta($question_id, 'like_count', true) ?: '0'; ?> likes
      </span>
    </div>
    
    <a href="<?php the_permalink(); ?>" class="practice-btn">
      Practice Question
      <i class="dashicons dashicons-arrow-right-alt2"></i>
    </a>
  </div>
</article>

<style>
.mcq-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s, box-shadow 0.3s;
  overflow: hidden;
}

.mcq-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.mcq-card-header {
  padding: 1rem;
  border-bottom: 1px solid #f1f5f9;
}

.mcq-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
}

.subject-tag,
.exam-tag,
.topic-tag,
.difficulty-tag,
.year-tag {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  color: white;
}

.subject-tag {
  background-color: var(--subject-color, #0077cc);
}

.exam-tag {
  background-color: #6b7280;
}

.topic-tag {
  background-color: #8b5cf6;
}

.difficulty-tag {
  background-color: #f59e0b;
}

.difficulty-easy {
  background-color: #10b981;
}

.difficulty-medium {
  background-color: #f59e0b;
}

.difficulty-hard {
  background-color: #ef4444;
}

.year-tag {
  background-color: #6366f1;
}

.mcq-card-body {
  padding: 1.5rem;
}

.question-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.75rem;
  line-height: 1.4;
}

.question-preview {
  color: #6b7280;
  line-height: 1.5;
}

.mcq-card-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.question-stats {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.question-stats i {
  margin-right: 0.25rem;
}

.practice-btn {
  background: #0077cc;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 600;
  transition: background 0.3s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.practice-btn:hover {
  background: #005fa3;
}

@media (max-width: 768px) {
  .mcq-card-footer {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .question-stats {
    justify-content: center;
  }
  
  .practice-btn {
    justify-content: center;
  }
}
</style>