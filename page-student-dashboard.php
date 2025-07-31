<?php
/**
 * Template Name: Student Dashboard
 */

get_header();

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$user = wp_get_current_user();
if (!in_array('mcq_student', $user->roles)) {
    wp_redirect(home_url());
    exit;
}

$student_id = $user->ID;

// Include registration redirect functions
require_once get_template_directory() . '/registration-redirect.php';

// Get student's enrollments
$enrollments = new WP_Query([
    'post_type' => 'enrollment',
    'author' => $student_id,
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

// Get enrolled quizzes with details
$enrolled_quizzes = [];
if ($enrollments->have_posts()) {
    while ($enrollments->have_posts()) {
        $enrollments->the_post();
        $quiz_id = get_post_meta(get_the_ID(), 'quiz_id', true);
        $quiz = get_post($quiz_id);
        
        if ($quiz) {
            $enrolled_quizzes[] = [
                'quiz' => $quiz,
                'enrollment_id' => get_the_ID(),
                'status' => get_post_meta(get_the_ID(), 'status', true),
                'attempts' => get_post_meta(get_the_ID(), 'attempts_count', true),
                'enrollment_date' => get_post_meta(get_the_ID(), 'enrollment_date', true),
                'last_attempt' => get_post_meta(get_the_ID(), 'last_attempt_date', true),
                'best_score' => get_post_meta(get_the_ID(), 'best_score', true),
                'completion_date' => get_post_meta(get_the_ID(), 'completion_date', true)
            ];
        }
    }
    wp_reset_postdata();
}

// Separate quizzes by status
$active_quizzes = array_filter($enrolled_quizzes, function($item) {
    return $item['status'] === 'enrolled';
});

$completed_quizzes = array_filter($enrolled_quizzes, function($item) {
    return $item['status'] === 'completed';
});

// Get student's achievements
function get_student_achievements($student_id) {
    $achievements = [];
    
    // Get badges from gamification system
    $badges = get_user_meta($student_id, 'earned_badges', true);
    if ($badges) {
        $achievements = array_merge($achievements, $badges);
    }
    
    // Get total points
    $total_points = get_user_meta($student_id, 'total_points', true) ?: 0;
    
    return [
        'badges' => $achievements,
        'total_points' => $total_points,
        'level' => calculate_student_level($total_points)
    ];
}

function calculate_student_level($points) {
    if ($points < 100) return 'Beginner';
    if ($points < 500) return 'Intermediate';
    if ($points < 1000) return 'Advanced';
    return 'Expert';
}

$achievements = get_student_achievements($student_id);

?>

<div class="student-dashboard">
    <div class="dashboard-header">
        <div class="container">
            <h1>Student Dashboard</h1>
            <p>Welcome back, <?php echo esc_html($user->display_name); ?></p>
            <?php mcqhome_dashboard_navigation(); ?>
        </div>
    </div>

    <div class="dashboard-stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-content">
                        <h3><?php echo count($active_quizzes); ?></h3>
                        <p>Active Quizzes</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?php echo count($completed_quizzes); ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-content">
                        <h3><?php echo $achievements['total_points']; ?></h3>
                        <p>Total Points</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🏆</div>
                    <div class="stat-content">
                        <h3><?php echo $achievements['level']; ?></h3>
                        <p>Current Level</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-content">
        <div class="container">
            <div class="dashboard-grid">
                <div class="dashboard-sidebar">
                    <nav class="dashboard-nav">
                        <ul>
                            <li><a href="#overview" class="active" data-tab="overview">Overview</a></li>
                            <li><a href="#active" data-tab="active">Active Quizzes</a></li>
                            <li><a href="#completed" data-tab="completed">Completed</a></li>
                            <li><a href="#achievements" data-tab="achievements">Achievements</a></li>
                            <li><a href="#progress" data-tab="progress">Progress</a></li>
                            <li><a href="#settings" data-tab="settings">Settings</a></li>
                        </ul>
                    </nav>
                </div>

                <div class="dashboard-main">
                    <!-- Overview Tab -->
                    <div id="overview-tab" class="tab-content active">
                        <h2>Overview</h2>
                        
                        <div class="overview-section">
                            <h3>Recent Activity</h3>
                            <div class="activity-list">
                                <?php if (!empty($enrolled_quizzes)) : ?>
                                    <?php 
                                    // Sort by enrollment date (most recent first)
                                    usort($enrolled_quizzes, function($a, $b) {
                                        return strtotime($b['enrollment_date']) - strtotime($a['enrollment_date']);
                                    });
                                    
                                    $recent_enrollments = array_slice($enrolled_quizzes, 0, 5);
                                    foreach ($recent_enrollments as $enrollment) : 
                                    ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">📚</div>
                                            <div class="activity-content">
                                                <p>Enrolled in <strong><?php echo get_the_title($enrollment['quiz']->ID); ?></strong></p>
                                                <span class="activity-time"><?php echo human_time_diff(strtotime($enrollment['enrollment_date']), current_time('timestamp')) . ' ago'; ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <p>No recent activity. <a href="<?php echo site_url('/quizzes'); ?>">Browse quizzes to enroll</a></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="overview-section">
                            <h3>Quick Actions</h3>
                            <div class="quick-actions">
                                <a href="<?php echo site_url('/quizzes'); ?>" class="btn btn-primary">Browse Quizzes</a>
                                <a href="#progress" data-tab="progress" class="btn btn-secondary">View Progress</a>
                                <a href="#achievements" data-tab="achievements" class="btn btn-secondary">View Achievements</a>
                            </div>
                        </div>
                    </div>

                    <!-- Active Quizzes Tab -->
                    <div id="active-tab" class="tab-content">
                        <h2>Active Quizzes</h2>
                        
                        <div class="quizzes-list">
                            <?php if (!empty($active_quizzes)) : ?>
                                <?php foreach ($active_quizzes as $enrollment) : ?>
                                    <div class="quiz-item">
                                        <div class="quiz-info">
                                            <h3><a href="<?php the_permalink($enrollment['quiz']->ID); ?>"><?php echo get_the_title($enrollment['quiz']->ID); ?></a></h3>
                                            <p class="quiz-meta">
                                                <span class="quiz-type"><?php echo ucfirst(get_post_meta($enrollment['quiz']->ID, '_quiz_type', true)); ?></span>
                                                <span class="quiz-duration"><?php echo get_post_meta($enrollment['quiz']->ID, '_quiz_duration', true); ?> min</span>
                                                <span class="quiz-attempts">Attempts: <?php echo $enrollment['attempts']; ?>/<?php echo get_post_meta($enrollment['quiz']->ID, '_quiz_max_attempts', true); ?></span>
                                            </p>
                                        </div>
                                        
                                        <div class="quiz-progress">
                                            <?php if ($enrollment['attempts'] > 0) : ?>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo ($enrollment['best_score'] ?: 0); ?>%;"></div>
                                                </div>
                                                <span class="progress-text">Best Score: <?php echo $enrollment['best_score'] ?: 0; ?>%</span>
                                            <?php else : ?>
                                                <span class="progress-text">Not started</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="quiz-actions">
                                            <a href="<?php the_permalink($enrollment['quiz']->ID); ?>" class="btn btn-primary">Start Quiz</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p>No active quizzes. <a href="<?php echo site_url('/quizzes'); ?>">Browse quizzes to enroll</a></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Completed Quizzes Tab -->
                    <div id="completed-tab" class="tab-content">
                        <h2>Completed Quizzes</h2>
                        
                        <div class="quizzes-list">
                            <?php if (!empty($completed_quizzes)) : ?>
                                <?php foreach ($completed_quizzes as $enrollment) : ?>
                                    <div class="quiz-item completed">
                                        <div class="quiz-info">
                                            <h3><?php echo get_the_title($enrollment['quiz']->ID); ?></h3>
                                            <p class="quiz-meta">
                                                <span class="quiz-completion">Completed: <?php echo date('M j, Y', strtotime($enrollment['completion_date'])); ?></span>
                                            </p>
                                        </div>
                                        
                                        <div class="quiz-result">
                                            <div class="score-badge">
                                                <span class="score-value"><?php echo $enrollment['best_score']; ?>%</span>
                                            </div>
                                        </div>
                                        
                                        <div class="quiz-actions">
                                            <a href="<?php echo site_url('/quiz-results/' . $enrollment['quiz']->ID); ?>" class="btn btn-secondary">View Results</a>
                                            <a href="<?php the_permalink($enrollment['quiz']->ID); ?>" class="btn btn-primary">Retake Quiz</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p>No completed quizzes yet. Complete some quizzes to see them here.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Achievements Tab -->
                    <div id="achievements-tab" class="tab-content">
                        <h2>Achievements</h2>
                        
                        <div class="achievements-section">
                            <div class="achievement-stats">
                                <div class="achievement-card">
                                    <h3>Total Points</h3>
                                    <span class="points-value"><?php echo $achievements['total_points']; ?></span>
                                </div>
                                <div class="achievement-card">
                                    <h3>Current Level</h3>
                                    <span class="level-value"><?php echo $achievements['level']; ?></span>
                                </div>
                                <div class="achievement-card">
                                    <h3>Badges Earned</h3>
                                    <span class="badges-count"><?php echo count($achievements['badges']); ?></span>
                                </div>
                            </div>
                            
                            <div class="badges-grid">
                                <?php if (!empty($achievements['badges'])) : ?>
                                    <?php foreach ($achievements['badges'] as $badge) : ?>
                                        <div class="badge-item">
                                            <div class="badge-icon">🏆</div>
                                            <h4><?php echo $badge['name']; ?></h4>
                                            <p><?php echo $badge['description']; ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <p>No badges earned yet. Complete quizzes to earn achievements!</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Tab -->
                    <div id="progress-tab" class="tab-content">
                        <h2>Learning Progress</h2>
                        
                        <div class="progress-section">
                            <div class="progress-chart">
                                <canvas id="progress-chart"></canvas>
                            </div>
                            
                            <div class="progress-stats">
                                <div class="stat-card">
                                    <h3>Average Score</h3>
                                    <span class="stat-value">
                                        <?php 
                                        $total_score = 0;
                                        $completed_count = count($completed_quizzes);
                                        foreach ($completed_quizzes as $quiz) {
                                            $total_score += $quiz['best_score'];
                                        }
                                        echo $completed_count > 0 ? round($total_score / $completed_count) : 0;
                                        ?>%
                                    </span>
                                </div>
                                <div class="stat-card">
                                    <h3>Quizzes Completed</h3>
                                    <span class="stat-value"><?php echo count($completed_quizzes); ?></span>
                                </div>
                                <div class="stat-card">
                                    <h3>Study Time</h3>
                                    <span class="stat-value">
                                        <?php 
                                        $total_time = 0;
                                        foreach ($completed_quizzes as $quiz) {
                                            $duration = get_post_meta($quiz['quiz']->ID, '_quiz_duration', true);
                                            $total_time += $duration;
                                        }
                                        echo $total_time . ' min';
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Tab -->
                    <div id="settings-tab" class="tab-content">
                        <h2>Student Settings</h2>
                        
                        <form class="student-settings-form">
                            <div class="form-group">
                                <label>Profile Information</label>
                                <input type="text" name="display_name" value="<?php echo esc_attr($user->display_name); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Grade/Level</label>
                                <select name="grade_level">
                                    <option value="">Select Grade</option>
                                    <option value="elementary" <?php selected(get_user_meta($user->ID, 'grade_level', true), 'elementary'); ?>>Elementary</option>
                                    <option value="middle" <?php selected(get_user_meta($user->ID, 'grade_level', true), 'middle'); ?>>Middle School</option>
                                    <option value="high" <?php selected(get_user_meta($user->ID, 'grade_level', true), 'high'); ?>>High School</option>
                                    <option value="college" <?php selected(get_user_meta($user->ID, 'grade_level', true), 'college'); ?>>College</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Subjects of Interest</label>
                                <select name="subjects_interest[]" multiple>
                                    <option value="math" <?php selected(in_array('math', (array)get_user_meta($user->ID, 'subjects_interest', true))); ?>>Mathematics</option>
                                    <option value="science" <?php selected(in_array('science', (array)get_user_meta($user->ID, 'subjects_interest', true))); ?>>Science</option>
                                    <option value="english" <?php selected(in_array('english', (array)get_user_meta($user->ID, 'subjects_interest', true))); ?>>English</option>
                                    <option value="history" <?php selected(in_array('history', (array)get_user_meta($user->ID, 'subjects_interest', true))); ?>>History</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>