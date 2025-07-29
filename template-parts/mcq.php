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
?>

<!-- MCQ Container -->
<div id="mcq-quiz-<?php echo $post_id; ?>" style="border: 1px solid #ddd; padding: 20px; border-radius: 10px; margin: 30px 0;">
  
  <!-- Question Title -->
  <h3 style="margin-bottom: 15px;">Q. <?php the_title(); ?></h3>

  <!-- Options -->
  <?php foreach ($options as $label => $text): ?>
    <button class="mcq-option" 
            data-option="<?php echo esc_attr($label); ?>" 
            data-post="<?php echo esc_attr($post_id); ?>"
            style="cursor: pointer;">
      <span class="option-label-circle"><?php echo esc_html($label); ?></span>
<span class="option-text"><?php echo esc_html($text); ?></span>

    </button>
  <?php endforeach; ?>

  <!-- Feedback Placeholder -->
  <div class="mcq-feedback" style="display:none; margin-top:15px; font-weight: bold;"></div>

  <!-- Explanation Placeholder -->
  <?php if ($explanation): ?>
    <div class="mcq-explanation" style="display:none; margin-top:10px; color: #555;">
      <strong>Explanation:</strong> <?php echo esc_html($explanation); ?>
    </div>
  <?php endif; ?>

  <!-- Like Button (if plugin is active) -->
  <?php if (function_exists('wp_ulike')) : ?>
    <div style="margin-top: 20px; padding: 10px; border-radius: 10px; box-shadow: 0px 0px 20px rgba(0,0,0,0.1);">
      <?php wp_ulike('get'); ?>
    </div>
  <?php endif; ?>
</div>

<!-- MCQ Answer Logic -->
<script>
document.querySelectorAll('#mcq-quiz-<?php the_ID(); ?> .mcq-option').forEach(btn => {
  btn.addEventListener('click', function () {
    const selected = this.getAttribute('data-option');
    const correct = "<?php echo $correct; ?>";
    const container = this.closest('#mcq-quiz-<?php the_ID(); ?>');
    const feedback = container.querySelector('.mcq-feedback');
    const explanation = container.querySelector('.mcq-explanation');

    if (selected === correct) {
      feedback.innerHTML = '<span style="color: green;"><strong>Correct!</strong></span>';
    } else {
      feedback.innerHTML = '<span style="color: red;"><strong>Incorrect.</strong> The correct answer is ' + correct + '.</span>';
    }

    feedback.style.display = 'block';
    explanation.style.display = 'block';

    container.querySelectorAll('.mcq-option').forEach(b => b.disabled = true);
  });
});
</script>

<?php endif; ?>

<?php endif; ?>
