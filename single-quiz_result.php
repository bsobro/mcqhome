<?php
/**
 * Single Quiz Result Template
 */

get_header();

if (!have_posts()) {
    wp_redirect(home_url('/student-dashboard'));
    exit;
}

the_post();

$result_id = get_the_ID();
$user_id = get_current_user_id();

// Verify user can view this result
if (get_post_meta($result_id, 'user_id', true) != $user_id) {
    wp_redirect(home_url('/student-dashboard'));
    exit;
}

$quiz_id = get_post_meta($result_id, 'quiz_id', true);
$score = get_post_meta($result_id, 'score', true);
$correct_answers = get_post_meta($result_id, 'correct_answers', true);
$total_questions = get_post_meta($result_id, 'total_questions', true);
$answers = get_post_meta($result_id, 'answers', true);
$completed_at = get_post_meta($result_id, 'completed_at', true);

// Get quiz details
$quiz_title = get_the_title($quiz_id);
$quiz_permalink = get_permalink($quiz_id);

// Calculate performance
$performance_level = '';
if ($score >= 90) {
    $performance_level = 'Excellent';
} elseif ($score >= 80) {
    $performance_level = 'Very Good';
} elseif ($score >= 70) {
    $performance_level = 'Good';
} elseif ($score >= 60) {
    $performance_level = 'Satisfactory';
} else {
    $performance_level = 'Needs Improvement';
}

// Get questions for detailed review
$questions = new WP_Query([
    'post_type' => 'mcq_question',
    'posts_per_page' => -1,
    'post__in' => array_column($answers, 'question_id'),
    'orderby' => 'menu_order',
    'order' => 'ASC'
]);

$questions_map = [];
if ($questions->have_posts()) {
    while ($questions->have_posts()) {
        $questions->the_post();
        $questions_map[get_the_ID()] = [
            'title' => get_the_title(),
            'content' => get_the_content(),
            'explanation' => get_post_meta(get_the_ID(), 'explanation', true),
            'options' => [
                'a' => get_post_meta(get_the_ID(), 'option_a', true),
                'b' => get_post_meta(get_the_ID(), 'option_b', true),
                'c' => get_post_meta(get_the_ID(), 'option_c', true),
                'd' => get_post_meta(get_the_ID(), 'option_d', true)
            ]
        ];
    }
    wp_reset_postdata();
}

?>

<div class="quiz-result-page">
    <div class="result-header">
        <div class="container">
            <nav class="breadcrumb">
                <a href="<?php echo site_url('/student-dashboard'); ?>">Dashboard</a>
                <span>&gt;</span>
                <a href="<?php echo site_url('/quizzes'); ?>">Quizzes</a>
                <span>&gt;</span>
                <a href="<?php echo $quiz_permalink; ?>"><?php echo $quiz_title; ?></a>
                <span>&gt;</span>
                <span>Results</span>
            </nav>
            
            <div class="result-summary">
                <h1>Quiz Results: <?php echo $quiz_title; ?></h1>
                <p class="completion-date">Completed on <?php echo date('F j, Y g:i A', strtotime($completed_at)); ?></p>
            </div>
        </div>
    </div>

    <div class="result-content">
        <div class="container">
            <div class="result-layout">
                <div class="result-main">
                    <!-- Score Card -->
                    <div class="score-card">
                        <div class="score-display">
                            <div class="score-circle">
                                <svg class="score-circle-svg" viewBox="0 0 36 36">
                                    <path class="score-circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                    <path class="score-circle-progress" 
                                          stroke-dasharray="<?php echo $score; ?>, 100" 
                                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                </svg>
                                <div class="score-text">
                                    <span class="score-percentage"><?php echo $score; ?>%</span>
                                    <span class="score-label">Score</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="score-details">
                            <div class="score-stats">
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo $correct_answers; ?></span>
                                    <span class="stat-label">Correct Answers</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo $total_questions - $correct_answers; ?></span>
                                    <span class="stat-label">Incorrect Answers</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo $total_questions; ?></span>
                                    <span class="stat-label">Total Questions</span>
                                </div>
                            </div>
                            
                            <div class="performance-badge">
                                <span class="badge <?php echo strtolower(str_replace(' ', '-', $performance_level)); ?>">
                                    <?php echo $performance_level; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Question Review -->
                    <div class="question-review">
                        <h2>Question Review</h2>
                        
                        <?php foreach ($answers as $index => $answer) : ?>
                            <?php 
                            $question = $questions_map[$answer['question_id']] ?? [];
                            $is_correct = $answer['is_correct'];
                            ?>
                            
                            <div class="review-item <?php echo $is_correct ? 'correct' : 'incorrect'; ?>">
                                <div class="review-header">
                                    <h3>Question <?php echo $index + 1; ?></h3>
                                    <span class="result-indicator">
                                        <?php echo $is_correct ? '✓ Correct' : '✗ Incorrect'; ?>
                                    </span>
                                </div>
                                
                                <div class="question-content">
                                    <p><?php echo $question['content'] ?? ''; ?></p>
                                    
                                    <?php if (!empty($question['options'])) : ?>
                                        <div class="options-review">
                                            <?php foreach ($question['options'] as $key => $option) : ?>
                                                <?php if ($option) : ?>
                                                    <div class="option-review <?php 
                                                        echo $key === $answer['correct_answer'] ? 'correct-answer' : ''; 
                                                        echo $key === $answer['user_answer'] ? 'user-answer' : ''; 
                                                    ?>">
                                                        <span class="option-label"><?php echo strtoupper($key); ?>.</span>
                                                        <span class="option-text"><?php echo $option; ?></span>
                                                        
                                                        <?php if ($key === $answer['correct_answer']) : ?>
                                                            <span class="correct-mark">✓</span>
                                                        <?php elseif ($key === $answer['user_answer'] && !$is_correct) : ?>
                                                            <span class="incorrect-mark">✗</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($question['explanation'])) : ?>
                                    <div class="explanation">
                                        <h4>Explanation</h4>
                                        <p><?php echo $question['explanation']; ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="result-sidebar">
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="<?php echo $quiz_permalink; ?>" class="btn btn-primary">Retake Quiz</a>
                        <a href="<?php echo site_url('/student-dashboard'); ?>" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                    
                    <!-- Share Results -->
                    <div class="share-results">
                        <h3>Share Your Results</h3>
                        <div class="share-buttons">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php the_permalink(); ?>" 
                               class="share-btn facebook" target="_blank">Facebook</a>
                            <a href="https://twitter.com/intent/tweet?url=<?php the_permalink(); ?>&text=<?php echo urlencode('I scored ' . $score . '% on ' . $quiz_title . '!'); ?>" 
                               class="share-btn twitter" target="_blank">Twitter</a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php the_permalink(); ?>" 
                               class="share-btn linkedin" target="_blank">LinkedIn</a>
                        </div>
                    </div>
                    
                    <!-- Statistics -->
                    <div class="statistics">
                        <h3>Your Statistics</h3>
                        <div class="stat-item">
                            <span class="stat-label">Accuracy Rate</span>
                            <span class="stat-value"><?php echo $score; ?>%</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Points Earned</span>
                            <span class="stat-value"><?php echo $correct_answers; ?>/<?php echo $total_questions; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Performance</span>
                            <span class="stat-value"><?php echo $performance_level; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.quiz-result-page {
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

.result-header {
    background: white;
    border-bottom: 1px solid #e0e0e0;
    padding: 40px 0;
}

.result-summary h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
    color: #333;
}

.completion-date {
    color: #666;
    font-size: 16px;
}

.result-content {
    padding: 40px 0;
}

.result-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.score-card {
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 40px;
    margin-bottom: 30px;
}

.score-display {
    position: relative;
}

.score-circle {
    position: relative;
    width: 120px;
    height: 120px;
}

.score-circle-svg {
    width: 120px;
    height: 120px;
    transform: rotate(-90deg);
}

.score-circle-bg {
    fill: none;
    stroke: #e0e0e0;
    stroke-width: 3;
}

.score-circle-progress {
    fill: none;
    stroke: #007cba;
    stroke-width: 3;
    stroke-linecap: round;
    transition: stroke-dasharray 0.5s ease;
}

.score-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.score-percentage {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #007cba;
}

.score-label {
    font-size: 12px;
    color: #666;
}

.score-details {
    flex: 1;
}

.score-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.stat-item {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.stat-label {
    font-size: 14px;
    color: #666;
}

.performance-badge {
    text-align: center;
}

.badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
}

.badge.excellent {
    background: #d4edda;
    color: #155724;
}

.badge.very-good {
    background: #cce5ff;
    color: #004085;
}

.badge.good {
    background: #d1ecf1;
    color: #0c5460;
}

.badge.satisfactory {
    background: #fff3cd;
    color: #856404;
}

.badge.needs-improvement {
    background: #f8d7da;
    color: #721c24;
}

.question-review {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.question-review h2 {
    margin: 0 0 30px 0;
    color: #333;
}

.review-item {
    margin-bottom: 30px;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid;
}

.review-item.correct {
    border-left-color: #28a745;
    background: #f8fff9;
}

.review-item.incorrect {
    border-left-color: #dc3545;
    background: #fff8f8;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.review-header h3 {
    margin: 0;
    color: #333;
}

.result-indicator {
    font-weight: bold;
    font-size: 14px;
}

.review-item.correct .result-indicator {
    color: #28a745;
}

.review-item.incorrect .result-indicator {
    color: #dc3545;
}

.question-content {
    margin-bottom: 20px;
}

.question-content p {
    font-size: 16px;
    line-height: 1.6;
    margin: 0 0 15px 0;
}

.options-review {
    margin-bottom: 20px;
}

.option-review {
    padding: 10px;
    margin-bottom: 8px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.option-review.correct-answer {
    background: #d4edda;
    border: 1px solid #c3e6cb;
}

.option-review.user-answer {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
}

.option-review.user-answer.incorrect {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
}

.option-label {
    font-weight: bold;
    min-width: 20px;
}

.correct-mark,
.incorrect-mark {
    font-size: 16px;
    font-weight: bold;
}

.correct-mark {
    color: #28a745;
}

.incorrect-mark {
    color: #dc3545;
}

.explanation {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin-top: 15px;
}

.explanation h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.explanation p {
    margin: 0;
    line-height: 1.6;
}

.result-sidebar {
    position: sticky;
    top: 20px;
    height: fit-content;
}

.action-buttons,
.share-results,
.statistics {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.action-buttons h3,
.share-results h3,
.statistics h3 {
    margin: 0 0 15px 0;
    font-size: 18px;
    color: #333;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.btn {
    display: inline-block;
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
    text-align: center;
    font-weight: bold;
    transition: all 0.3s;
}

.btn-primary {
    background: #007cba;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn:hover {
    opacity: 0.9;
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

.statistics .stat-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 14px;
}

.statistics .stat-label {
    color: #666;
}

.statistics .stat-value {
    font-weight: bold;
    color: #333;
}

@media (max-width: 768px) {
    .result-layout {
        grid-template-columns: 1fr;
    }
    
    .result-sidebar {
        order: -1;
        position: static;
    }
    
    .score-card {
        flex-direction: column;
        text-align: center;
    }
    
    .score-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>