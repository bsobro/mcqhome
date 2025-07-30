<?php
/**
 * Single Quiz Template
 */

get_header(); 

if (!have_posts()) {
    wp_redirect(home_url('/quizzes'));
    exit;
}

the_post();

$quiz_id = get_the_ID();
$quiz_title = get_the_title();
$quiz_description = get_the_content();
$quiz_type = get_post_meta($quiz_id, '_quiz_type', true);
$quiz_price = get_post_meta($quiz_id, '_quiz_price', true);
$quiz_duration = get_post_meta($quiz_id, '_quiz_duration', true);
$quiz_max_attempts = get_post_meta($quiz_id, '_quiz_max_attempts', true);
$quiz_instructions = get_post_meta($quiz_id, '_quiz_instructions', true);

// Check if user is enrolled
$is_enrolled = false;
$enrollment_data = [];
$remaining_attempts = $quiz_max_attempts;

if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $enrollments = new WP_Query([
        'post_type' => 'enrollment',
        'author' => $user_id,
        'meta_query' => [
            [
                'key' => 'quiz_id',
                'value' => $quiz_id,
                'compare' => '='
            ]
        ],
        'posts_per_page' => 1
    ]);
    
    if ($enrollments->have_posts()) {
        $is_enrolled = true;
        $enrollment_data = $enrollments->posts[0];
        $attempts_count = get_post_meta($enrollment_data->ID, 'attempts_count', true) ?: 0;
        $remaining_attempts = max(0, $quiz_max_attempts - $attempts_count);
    }
    wp_reset_postdata();
}

// Get quiz questions
$questions = new WP_Query([
    'post_type' => 'mcq_question',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => 'quiz_id',
            'value' => $quiz_id,
            'compare' => '='
        ]
    ],
    'orderby' => 'menu_order',
    'order' => 'ASC'
]);

$question_count = $questions->found_posts;

?>

<div class="single-quiz-page">
    <div class="quiz-header">
        <div class="container">
            <nav class="breadcrumb">
                <a href="<?php echo site_url('/quizzes'); ?>">Quizzes</a>
                <span>&gt;</span>
                <span><?php echo $quiz_title; ?></span>
            </nav>
            
            <div class="quiz-info">
                <h1><?php echo $quiz_title; ?></h1>
                <p class="quiz-meta">
                    <span class="quiz-type <?php echo $quiz_type; ?>"><?php echo ucfirst($quiz_type); ?></span>
                    <?php if ($quiz_type !== 'free') : ?>
                        <span class="quiz-price">$<?php echo number_format($quiz_price, 2); ?></span>
                    <?php endif; ?>
                    <span class="quiz-duration"><?php echo $quiz_duration; ?> minutes</span>
                    <span class="quiz-questions"><?php echo $question_count; ?> questions</span>
                </p>
                
                <div class="quiz-author">
                    <p>Created by <strong><?php the_author(); ?></strong></p>
                </div>
            </div>
        </div>
    </div>

    <div class="quiz-content">
        <div class="container">
            <div class="quiz-layout">
                <div class="quiz-main">
                    <?php if (!$is_enrolled && is_user_logged_in()) : ?>
                        <!-- Enrollment Section -->
                        <div class="enrollment-section">
                            <h2>Enroll in this Quiz</h2>
                            <p><?php echo $quiz_description; ?></p>
                            
                            <?php if ($quiz_instructions) : ?>
                                <div class="quiz-instructions">
                                    <h3>Instructions</h3>
                                    <p><?php echo $quiz_instructions; ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="enrollment-form">
                                <?php echo do_shortcode('[quiz_enrollment_form quiz_id="' . $quiz_id . '"]'); ?>
                            </div>
                        </div>
                    
                    <?php elseif (!$is_enrolled && !is_user_logged_in()) : ?>
                        <!-- Login Required -->
                        <div class="login-required">
                            <h2>Login Required</h2>
                            <p>Please login to enroll in this quiz.</p>
                            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="btn btn-primary">Login</a>
                            <a href="<?php echo wp_registration_url(); ?>" class="btn btn-secondary">Register</a>
                        </div>
                    
                    <?php elseif ($is_enrolled && $remaining_attempts <= 0) : ?>
                        <!-- No Attempts Left -->
                        <div class="no-attempts">
                            <h2>Maximum Attempts Reached</h2>
                            <p>You have used all <?php echo $quiz_max_attempts; ?> attempts for this quiz.</p>
                            <a href="<?php echo site_url('/student-dashboard'); ?>" class="btn btn-primary">View Dashboard</a>
                        </div>
                    
                    <?php else : ?>
                        <!-- Quiz Interface -->
                        <div class="quiz-interface">
                            <div class="quiz-controls">
                                <div class="timer">
                                    <span class="timer-label">Time Remaining:</span>
                                    <span id="quiz-timer" class="timer-display"><?php echo $quiz_duration; ?>:00</span>
                                </div>
                                
                                <div class="attempts-info">
                                    <span>Attempts: <?php echo $remaining_attempts; ?> remaining</span>
                                </div>
                            </div>
                            
                            <form id="quiz-form" method="post">
                                <?php wp_nonce_field('submit_quiz_nonce', 'submit_quiz_nonce_field'); ?>
                                <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                                <input type="hidden" name="enrollment_id" value="<?php echo $enrollment_data->ID; ?>">
                                
                                <div class="questions-container">
                                    <?php if ($questions->have_posts()) : ?>
                                        <?php $question_number = 1; ?>
                                        <?php while ($questions->have_posts()) : $questions->the_post(); ?>
                                            <div class="question-card" data-question-id="<?php the_ID(); ?>">
                                                <div class="question-header">
                                                    <h3>Question <?php echo $question_number; ?></h3>
                                                    <span class="question-points"><?php echo get_post_meta(get_the_ID(), 'points', true) ?: '1'; ?> point</span>
                                                </div>
                                                
                                                <div class="question-content">
                                                    <p class="question-text"><?php the_content(); ?></p>
                                                    
                                                    <?php if (has_post_thumbnail()) : ?>
                                                        <div class="question-image">
                                                            <?php the_post_thumbnail('medium'); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="question-options">
                                                    <?php
                                                    $options = [
                                                        'a' => get_post_meta(get_the_ID(), 'option_a', true),
                                                        'b' => get_post_meta(get_the_ID(), 'option_b', true),
                                                        'c' => get_post_meta(get_the_ID(), 'option_c', true),
                                                        'd' => get_post_meta(get_the_ID(), 'option_d', true)
                                                    ];
                                                    
                                                    foreach ($options as $key => $option) : 
                                                        if ($option) :
                                                    ?>
                                                        <div class="option-item">
                                                            <input type="radio" 
                                                                   name="question_<?php the_ID(); ?>" 
                                                                   id="option_<?php the_ID(); ?>_<?php echo $key; ?>" 
                                                                   value="<?php echo $key; ?>">
                                                            <label for="option_<?php the_ID(); ?>_<?php echo $key; ?>">
                                                                <span class="option-label"><?php echo strtoupper($key); ?></span>
                                                                <span class="option-text"><?php echo $option; ?></span>
                                                            </label>
                                                        </div>
                                                    <?php 
                                                        endif;
                                                    endforeach; 
                                                    ?>
                                                </div>
                                            </div>
                                            <?php $question_number++; ?>
                                        <?php endwhile; ?>
                                        <?php wp_reset_postdata(); ?>
                                    <?php else : ?>
                                        <p>No questions available for this quiz yet.</p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="quiz-actions">
                                    <button type="submit" class="btn btn-primary" id="submit-quiz">Submit Quiz</button>
                                    <button type="button" class="btn btn-secondary" id="save-progress">Save Progress</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="quiz-sidebar">
                    <div class="quiz-summary">
                        <h3>Quiz Summary</h3>
                        <div class="summary-item">
                            <span class="summary-label">Questions:</span>
                            <span class="summary-value"><?php echo $question_count; ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Duration:</span>
                            <span class="summary-value"><?php echo $quiz_duration; ?> min</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Attempts:</span>
                            <span class="summary-value"><?php echo $quiz_max_attempts; ?></span>
                        </div>
                        
                        <?php if ($quiz_type !== 'free') : ?>
                            <div class="summary-item">
                                <span class="summary-label">Price:</span>
                                <span class="summary-value">$<?php echo number_format($quiz_price, 2); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($is_enrolled && $remaining_attempts > 0) : ?>
                        <div class="quiz-progress">
                            <h3>Your Progress</h3>
                            <div class="progress-item">
                                <span class="progress-label">Attempts Used:</span>
                                <span class="progress-value"><?php echo $quiz_max_attempts - $remaining_attempts; ?>/<?php echo $quiz_max_attempts; ?></span>
                            </div>
                            <div class="progress-item">
                                <span class="progress-label">Best Score:</span>
                                <span class="progress-value"><?php echo get_post_meta($enrollment_data->ID, 'best_score', true) ?: 'N/A'; ?>%</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="quiz-share">
                        <h3>Share Quiz</h3>
                        <div class="share-buttons">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php the_permalink(); ?>" 
                               class="share-btn facebook" target="_blank">Facebook</a>
                            <a href="https://twitter.com/intent/tweet?url=<?php the_permalink(); ?>&text=<?php echo urlencode($quiz_title); ?>" 
                               class="share-btn twitter" target="_blank">Twitter</a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php the_permalink(); ?>" 
                               class="share-btn linkedin" target="_blank">LinkedIn</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.single-quiz-page {
    background: #f8f9fa;
    min-height: 100vh;
}

.breadcrumb {
    padding: 20px 0;
    font-size: 14px;
}

.breadcrumb a {
    color: #007cba;
    text-decoration: none;
}

.breadcrumb span {
    margin: 0 5px;
    color: #666;
}

.quiz-header {
    background: white;
    border-bottom: 1px solid #e0e0e0;
    padding: 40px 0;
}

.quiz-info h1 {
    margin: 0 0 15px 0;
    font-size: 32px;
    color: #333;
}

.quiz-meta {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.quiz-type {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.quiz-type.free {
    background: #d4edda;
    color: #155724;
}

.quiz-type.paid {
    background: #fff3cd;
    color: #856404;
}

.quiz-type.subscription {
    background: #d1ecf1;
    color: #0c5460;
}

.quiz-price {
    color: #28a745;
    font-weight: bold;
    font-size: 18px;
}

.quiz-duration,
.quiz-questions {
    color: #666;
    font-size: 14px;
}

.quiz-author {
    margin-top: 10px;
    color: #666;
}

.quiz-content {
    padding: 40px 0;
}

.quiz-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.enrollment-section,
.login-required,
.no-attempts {
    background: white;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.quiz-instructions {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    text-align: left;
}

.quiz-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.timer {
    display: flex;
    align-items: center;
    gap: 10px;
}

.timer-label {
    font-size: 14px;
    color: #666;
}

.timer-display {
    font-size: 24px;
    font-weight: bold;
    color: #007cba;
}

.timer-display.warning {
    color: #dc3545;
}

.question-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.question-header h3 {
    margin: 0;
    color: #333;
}

.question-points {
    background: #007cba;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.question-text {
    font-size: 18px;
    margin-bottom: 20px;
    line-height: 1.6;
}

.question-image {
    margin-bottom: 20px;
    text-align: center;
}

.question-image img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
}

.option-item {
    margin-bottom: 15px;
}

.option-item input[type="radio"] {
    display: none;
}

.option-item label {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border: 2px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.option-item label:hover {
    background: #e9ecef;
}

.option-item input[type="radio"]:checked + label {
    background: #007cba;
    color: white;
    border-color: #007cba;
}

.option-label {
    font-weight: bold;
    margin-right: 15px;
    min-width: 25px;
}

.quiz-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.quiz-sidebar {
    position: sticky;
    top: 20px;
    height: fit-content;
}

.quiz-summary,
.quiz-progress,
.quiz-share {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.quiz-summary h3,
.quiz-progress h3,
.quiz-share h3 {
    margin: 0 0 15px 0;
    font-size: 18px;
    color: #333;
}

.summary-item,
.progress-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 14px;
}

.summary-label,
.progress-label {
    color: #666;
}

.share-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.share-btn {
    padding: 10px;
    border-radius: 4px;
    text-decoration: none;
    text-align: center;
    font-size: 14px;
    transition: all 0.3s;
}

.share-btn.facebook {
    background: #1877f2;
    color: white;
}

.share-btn.twitter {
    background: #1da1f2;
    color: white;
}

.share-btn.linkedin {
    background: #0077b5;
    color: white;
}

.share-btn:hover {
    opacity: 0.9;
}

@media (max-width: 768px) {
    .quiz-layout {
        grid-template-columns: 1fr;
    }
    
    .quiz-sidebar {
        order: -1;
        position: static;
    }
    
    .quiz-controls {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .quiz-actions {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    let timeLeft = <?php echo $quiz_duration * 60; ?>;
    let timerInterval;
    
    // Start timer if quiz is active
    if ($('#quiz-timer').length && timeLeft > 0) {
        startTimer();
    }
    
    function startTimer() {
        timerInterval = setInterval(function() {
            timeLeft--;
            updateTimerDisplay();
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                submitQuiz();
            }
        }, 1000);
    }
    
    function updateTimerDisplay() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        const display = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        $('#quiz-timer').text(display);
        
        if (timeLeft <= 60) {
            $('#quiz-timer').addClass('warning');
        }
    }
    
    // Submit quiz
    $('#submit-quiz').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to submit this quiz?')) {
            submitQuiz();
        }
    });
    
    function submitQuiz() {
        clearInterval(timerInterval);
        
        const formData = new FormData($('#quiz-form')[0]);
        formData.append('action', 'submit_quiz');
        
        $.ajax({
            url: mcqhome_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.result_url;
                } else {
                    alert('Error submitting quiz: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while submitting the quiz.');
            }
        });
    }
    
    // Save progress
    $('#save-progress').on('click', function() {
        const formData = new FormData($('#quiz-form')[0]);
        formData.append('action', 'save_quiz_progress');
        
        $.ajax({
            url: mcqhome_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Progress saved successfully!');
                }
            }
        });
    });
});
</script>

<?php get_footer(); ?>