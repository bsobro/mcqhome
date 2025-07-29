<?php if (get_post_type() === 'mcq_question') : ?>

<?php
if (function_exists('get_field')) :
  // Fetch MCQ options from ACF fields
  $options = [
    'A' => get_field('option_a'),
    'B' => get_field('option_b'),
    'C' => get_field('option_c'),
    'D' => get_field('option_d')
  ];

  // Fetch the correct answer and explanation
  $correct = strtoupper(trim(get_field('correct_answer')));
  $explanation = get_field('explanation');
  $post_id = get_the_ID();
  
  // Get subject and topic for context
  $subjects = get_the_terms($post_id, 'mcq_subject');
  $topics = get_the_terms($post_id, 'mcq_topic');
?>

<!-- MCQ Quiz Container -->
<div id="mcq-quiz-<?php echo $post_id; ?>" class="mcq-quiz-container">
  
  <!-- Question Context -->
  <div class="question-meta">
    <?php if ($subjects && !is_wp_error($subjects)) : ?>
      <span class="question-subject"><?php echo esc_html($subjects[0]->name); ?></span>
    <?php endif; ?>
    
    <?php if ($topics && !is_wp_error($topics)) : ?>
      <span class="question-topic"><?php echo esc_html($topics[0]->name); ?></span>
    <?php endif; ?>
  </div>

  <!-- Question Title -->
  <h3 class="question-title"><?php the_title(); ?></h3>

  <!-- Question Content (if any) -->
  <?php if (get_the_content()) : ?>
    <div class="question-content">
      <?php the_content(); ?>
    </div>
  <?php endif; ?>

  <!-- Options -->
  <div class="mcq-options">
    <?php foreach ($options as $label => $text): 
      if (!empty($text)): ?>
      <button class="mcq-option" 
              data-option="<?php echo esc_attr($label); ?>" 
              data-post="<?php echo esc_attr($post_id); ?>"
              data-correct="<?php echo esc_attr($correct); ?>">
        <span class="option-label"><?php echo esc_html($label); ?></span>
        <span class="option-text"><?php echo esc_html($text); ?></span>
      </button>
    <?php endif; endforeach; ?>
  </div>

  <!-- Feedback Placeholder -->
  <div class="mcq-feedback" style="display:none;"></div>

  <!-- Explanation Placeholder -->
  <?php if ($explanation): ?>
    <div class="mcq-explanation" style="display:none;">
      <strong>Explanation:</strong> <?php echo esc_html($explanation); ?>
    </div>
  <?php endif; ?>

  <!-- Question Footer -->
  <div class="question-footer">
    <div class="question-stats">
      <span class="question-year">
        <?php 
        $year = get_field('year'); 
        echo $year ? 'Year: ' . esc_html($year) : '';
        ?>
      </span>
      <span class="question-difficulty">
        <?php 
        $difficulties = get_the_terms($post_id, 'mcq_difficulty');
        echo $difficulties && !is_wp_error($difficulties) ? 'Difficulty: ' . esc_html($difficulties[0]->name) : '';
        ?>
      </span>
    </div>

    <!-- Like Button (if plugin is active) -->
    <?php if (function_exists('wp_ulike')) : ?>
      <div class="question-actions">
        <?php wp_ulike('get'); ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- MCQ Answer Logic -->
<script>
(function() {
  const container = document.getElementById('mcq-quiz-<?php echo $post_id; ?>');
  const options = container.querySelectorAll('.mcq-option');
  const feedback = container.querySelector('.mcq-feedback');
  const explanation = container.querySelector('.mcq-explanation');
  const correctAnswer = '<?php echo $correct; ?>';

  options.forEach(option => {
    option.addEventListener('click', function() {
      const selected = this.getAttribute('data-option');
      const isCorrect = selected === correctAnswer;

      // Add visual feedback to selected option
      options.forEach(opt => {
        opt.disabled = true;
        if (opt.getAttribute('data-option') === correctAnswer) {
          opt.classList.add('correct');
        }
        if (opt === this && !isCorrect) {
          opt.classList.add('incorrect');
        }
      });

      // Show feedback
      feedback.style.display = 'block';
      if (isCorrect) {
        feedback.className = 'mcq-feedback correct';
        feedback.innerHTML = '<strong>✓ Correct!</strong> Well done!';
      } else {
        feedback.className = 'mcq-feedback incorrect';
        feedback.innerHTML = '<strong>✗ Incorrect.</strong> The correct answer is ' + correctAnswer + '.';
      }

      // Show explanation if available
      if (explanation) {
        explanation.style.display = 'block';
      }

      // Track answer for progress (if user is logged in)
      <?php if (is_user_logged_in()) : ?>
      fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'track_mcq_answer',
          post_id: <?php echo $post_id; ?>,
          selected_answer: selected,
          is_correct: isCorrect ? 1 : 0,
          nonce: '<?php echo wp_create_nonce('mcq_nonce'); ?>'
        })
      });
      <?php endif; ?>
    });
  });
})();
</script>

<?php endif; ?>

<?php endif; ?>
