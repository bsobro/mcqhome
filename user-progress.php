<?php
/**
 * User Progress Tracking for MCQ Hub
 * Phase 2: User Accounts & Progress System
 */

// Add user progress tracking tables on theme activation
function mcqhome_create_progress_tables() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mcq_user_progress';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        question_id bigint(20) NOT NULL,
        is_correct tinyint(1) DEFAULT 0,
        attempts int DEFAULT 1,
        time_spent int DEFAULT 0,
        answered_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_user_question (user_id, question_id),
        KEY user_id (user_id),
        KEY question_id (question_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'mcqhome_create_progress_tables');

// Track user answer
function mcqhome_track_answer($user_id, $question_id, $is_correct, $time_spent = 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mcq_user_progress';
    
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d AND question_id = %d",
        $user_id, $question_id
    ));
    
    if ($existing) {
        $wpdb->update($table_name, [
            'is_correct' => $is_correct ? 1 : 0,
            'attempts' => $existing->attempts + 1,
            'time_spent' => $time_spent,
            'answered_date' => current_time('mysql')
        ], [
            'user_id' => $user_id,
            'question_id' => $question_id
        ]);
    } else {
        $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'question_id' => $question_id,
            'is_correct' => $is_correct ? 1 : 0,
            'time_spent' => $time_spent,
            'answered_date' => current_time('mysql')
        ]);
    }
}

// Get user progress summary
function mcqhome_get_user_progress($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) return false;
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'mcq_user_progress';
    
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
        $user_id
    ));
    
    $correct = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND is_correct = 1",
        $user_id
    ));
    
    return [
        'total_answered' => (int)$total,
        'correct_answers' => (int)$correct,
        'accuracy' => $total > 0 ? round(($correct / $total) * 100, 1) : 0
    ];
}

// Get user progress by subject
function mcqhome_get_subject_progress($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) return [];
    
    global $wpdb;
    $progress_table = $wpdb->prefix . 'mcq_user_progress';
    $posts_table = $wpdb->posts;
    $term_relationships = $wpdb->term_relationships;
    $terms_table = $wpdb->terms;
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT t.name as subject, t.term_id, COUNT(*) as total, SUM(p.is_correct) as correct
        FROM $progress_table p
        JOIN $posts_table posts ON p.question_id = posts.ID
        JOIN $term_relationships tr ON posts.ID = tr.object_id
        JOIN $terms_table t ON tr.term_taxonomy_id = t.term_id
        JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id
        WHERE p.user_id = %d AND tt.taxonomy = 'mcq_subject'
        GROUP BY t.term_id
    ", $user_id));
    
    return $results;
}

// AJAX handler for tracking answers
function mcqhome_track_answer_ajax() {
    check_ajax_referer('mcq_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_die('Not logged in');
    }
    
    $question_id = intval($_POST['question_id']);
    $is_correct = intval($_POST['is_correct']);
    $time_spent = intval($_POST['time_spent']);
    
    mcqhome_track_answer($user_id, $question_id, $is_correct, $time_spent);
    
    wp_send_json_success([
        'progress' => mcqhome_get_user_progress($user_id)
    ]);
}
add_action('wp_ajax_mcq_track_answer', 'mcqhome_track_answer_ajax');
add_action('wp_ajax_nopriv_mcq_track_answer', 'mcqhome_track_answer_ajax');

// Enqueue progress tracking scripts
function mcqhome_enqueue_progress_scripts() {
    if (is_singular('mcq_question')) {
        wp_enqueue_script('mcq-progress', get_template_directory_uri() . '/js/progress.js', ['jquery'], '1.0.0', true);
        wp_localize_script('mcq-progress', 'mcq_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mcq_nonce')
        ]);
    }
}
add_action('wp_enqueue_scripts', 'mcqhome_enqueue_progress_scripts');

// Add user dashboard shortcode
function mcqhome_user_dashboard() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to view your dashboard.</p>';
    }
    
    $user_id = get_current_user_id();
    $progress = mcqhome_get_user_progress($user_id);
    $subject_progress = mcqhome_get_subject_progress($user_id);
    
    ob_start();
    ?>
    <div class="user-dashboard">
        <h2>Your MCQ Dashboard</h2>
        
        <div class="progress-overview">
            <div class="stat-card">
                <h3>Questions Answered</h3>
                <span class="stat-number"><?php echo $progress['total_answered']; ?></span>
            </div>
            <div class="stat-card">
                <h3>Correct Answers</h3>
                <span class="stat-number"><?php echo $progress['correct_answers']; ?></span>
            </div>
            <div class="stat-card">
                <h3>Accuracy Rate</h3>
                <span class="stat-number"><?php echo $progress['accuracy']; ?>%</span>
            </div>
        </div>
        
        <?php if ($subject_progress): ?>
        <div class="subject-progress">
            <h3>Progress by Subject</h3>
            <div class="progress-bars">
                <?php foreach ($subject_progress as $subject): 
                    $accuracy = $subject->total > 0 ? round(($subject->correct / $subject->total) * 100, 1) : 0;
                ?>
                <div class="progress-item">
                    <div class="progress-label">
                        <span><?php echo esc_html($subject->subject); ?></span>
                        <span><?php echo $accuracy; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $accuracy; ?>%"></div>
                    </div>
                    <small><?php echo $subject->correct; ?>/<?php echo $subject->total; ?> correct</small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="dashboard-actions">
            <a href="/questions" class="btn btn-primary">Continue Practicing</a>
            <a href="/questions?unanswered=1" class="btn btn-secondary">Practice Unanswered</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('mcq_dashboard', 'mcqhome_user_dashboard');

// Add registration/login form styles
function mcqhome_login_styles() {
    if (in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php'])) {
        wp_enqueue_style('mcq-login', get_template_directory_uri() . '/css/login.css', [], '1.0.0');
    }
}
add_action('login_enqueue_scripts', 'mcqhome_login_styles');

// Add custom registration fields
function mcqhome_registration_form() {
    ?>
    <p>
        <label for="user_grade">Grade/Level<br>
        <input type="text" name="user_grade" id="user_grade" class="input" value="" size="25"></label>
    </p>
    <p>
        <label for="user_interests">Subjects of Interest<br>
        <input type="text" name="user_interests" id="user_interests" class="input" value="" size="25"></label>
    </p>
    <?php
}
add_action('register_form', 'mcqhome_registration_form');

// Save custom registration fields
function mcqhome_save_registration_fields($user_id) {
    if (isset($_POST['user_grade'])) {
        update_user_meta($user_id, 'user_grade', sanitize_text_field($_POST['user_grade']));
    }
    if (isset($_POST['user_interests'])) {
        update_user_meta($user_id, 'user_interests', sanitize_text_field($_POST['user_interests']));
    }
}
add_action('user_register', 'mcqhome_save_registration_fields');