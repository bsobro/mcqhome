<?php
/**
 * Quiz AJAX Handlers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MCQHome_Quiz_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_submit_quiz', [$this, 'submit_quiz']);
        add_action('wp_ajax_save_quiz_progress', [$this, 'save_quiz_progress']);
        add_action('wp_ajax_get_quiz_results', [$this, 'get_quiz_results']);
    }
    
    public function submit_quiz() {
        // Verify nonce
        if (!isset($_POST['submit_quiz_nonce_field']) || 
            !wp_verify_nonce($_POST['submit_quiz_nonce_field'], 'submit_quiz_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $quiz_id = intval($_POST['quiz_id']);
        $enrollment_id = intval($_POST['enrollment_id']);
        $user_id = get_current_user_id();
        
        if (!$quiz_id || !$enrollment_id || !$user_id) {
            wp_send_json_error('Invalid data');
        }
        
        // Verify enrollment belongs to user
        $enrollment = get_post($enrollment_id);
        if (!$enrollment || $enrollment->post_author != $user_id) {
            wp_send_json_error('Unauthorized');
        }
        
        // Get questions
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
        
        $correct_answers = 0;
        $total_questions = $questions->found_posts;
        $user_answers = [];
        
        // Process answers
        foreach ($questions->posts as $question) {
            $question_id = $question->ID;
            $user_answer = isset($_POST['question_' . $question_id]) ? 
                           sanitize_text_field($_POST['question_' . $question_id]) : '';
            $correct_answer = get_post_meta($question_id, 'correct_answer', true);
            
            $is_correct = ($user_answer === $correct_answer);
            if ($is_correct) {
                $correct_answers++;
            }
            
            $user_answers[] = [
                'question_id' => $question_id,
                'user_answer' => $user_answer,
                'correct_answer' => $correct_answer,
                'is_correct' => $is_correct,
                'points' => get_post_meta($question_id, 'points', true) ?: 1
            ];
        }
        
        $score_percentage = ($total_questions > 0) ? 
                           round(($correct_answers / $total_questions) * 100, 2) : 0;
        
        // Create result post
        $result_id = wp_insert_post([
            'post_type' => 'quiz_result',
            'post_title' => sprintf('Result - %s - %s', get_the_title($quiz_id), wp_get_current_user()->display_name),
            'post_status' => 'publish',
            'post_author' => $user_id
        ]);
        
        if ($result_id && !is_wp_error($result_id)) {
            // Save result metadata
            update_post_meta($result_id, 'quiz_id', $quiz_id);
            update_post_meta($result_id, 'user_id', $user_id);
            update_post_meta($result_id, 'score', $score_percentage);
            update_post_meta($result_id, 'correct_answers', $correct_answers);
            update_post_meta($result_id, 'total_questions', $total_questions);
            update_post_meta($result_id, 'answers', $user_answers);
            update_post_meta($result_id, 'completed_at', current_time('mysql'));
            
            // Update enrollment
            $attempts_count = get_post_meta($enrollment_id, 'attempts_count', true) ?: 0;
            update_post_meta($enrollment_id, 'attempts_count', $attempts_count + 1);
            
            $best_score = get_post_meta($enrollment_id, 'best_score', true) ?: 0;
            if ($score_percentage > $best_score) {
                update_post_meta($enrollment_id, 'best_score', $score_percentage);
            }
            
            wp_send_json_success([
                'result_url' => get_permalink($result_id)
            ]);
        }
        
        wp_send_json_error('Failed to save result');
    }
    
    public function save_quiz_progress() {
        // Verify nonce
        if (!isset($_POST['submit_quiz_nonce_field']) || 
            !wp_verify_nonce($_POST['submit_quiz_nonce_field'], 'submit_quiz_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $quiz_id = intval($_POST['quiz_id']);
        $enrollment_id = intval($_POST['enrollment_id']);
        $user_id = get_current_user_id();
        
        if (!$quiz_id || !$enrollment_id || !$user_id) {
            wp_send_json_error('Invalid data');
        }
        
        // Save progress data
        $progress_data = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'question_') === 0) {
                $progress_data[$key] = sanitize_text_field($value);
            }
        }
        
        update_post_meta($enrollment_id, 'quiz_progress_' . $quiz_id, $progress_data);
        wp_send_json_success('Progress saved');
    }
    
    public function get_quiz_results() {
        $result_id = intval($_POST['result_id']);
        $user_id = get_current_user_id();
        
        if (!$result_id || !$user_id) {
            wp_send_json_error('Invalid data');
        }
        
        $result = get_post($result_id);
        if (!$result || $result->post_author != $user_id) {
            wp_send_json_error('Unauthorized');
        }
        
        $quiz_id = get_post_meta($result_id, 'quiz_id', true);
        $score = get_post_meta($result_id, 'score', true);
        $correct_answers = get_post_meta($result_id, 'correct_answers', true);
        $total_questions = get_post_meta($result_id, 'total_questions', true);
        $answers = get_post_meta($result_id, 'answers', true);
        
        wp_send_json_success([
            'quiz_title' => get_the_title($quiz_id),
            'score' => $score,
            'correct_answers' => $correct_answers,
            'total_questions' => $total_questions,
            'answers' => $answers
        ]);
    }
}

// Initialize
new MCQHome_Quiz_Ajax();

// Register quiz result post type
function mcqhome_register_quiz_result_post_type() {
    register_post_type('quiz_result', [
        'labels' => [
            'name' => 'Quiz Results',
            'singular_name' => 'Quiz Result',
            'menu_name' => 'Quiz Results',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Quiz Result',
            'edit_item' => 'Edit Quiz Result',
            'new_item' => 'New Quiz Result',
            'view_item' => 'View Quiz Result',
            'search_items' => 'Search Quiz Results',
            'not_found' => 'No quiz results found',
            'not_found_in_trash' => 'No quiz results found in trash'
        ],
        'public' => true,
        'has_archive' => true,
        'show_ui' => true,
        'show_in_menu' => 'edit.php?post_type=quiz',
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'supports' => ['title', 'author'],
        'rewrite' => ['slug' => 'quiz-results']
    ]);
}
add_action('init', 'mcqhome_register_quiz_result_post_type');