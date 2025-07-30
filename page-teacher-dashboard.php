<?php
/**
 * Template Name: Teacher Dashboard
 */

get_header(); 

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$user = wp_get_current_user();
if (!in_array('mcq_teacher', $user->roles) && !in_array('mcq_institution', $user->roles)) {
    wp_redirect(home_url());
    exit;
}

$teacher_id = $user->ID;

// Get teacher's quizzes
$quizzes = new WP_Query([
    'post_type' => 'quiz',
    'author' => $teacher_id,
    'posts_per_page' => -1,
    'post_status' => 'any'
]);

// Get enrollment statistics
$enrollment_stats = $this->get_enrollment_stats($teacher_id);

// Get revenue statistics
$revenue_stats = $this->get_revenue_stats($teacher_id);

function get_enrollment_stats($teacher_id) {
    $quizzes = get_posts([
        'post_type' => 'quiz',
        'author' => $teacher_id,
        'posts_per_page' => -1,
        'fields' => 'ids'
    ]);
    
    if (empty($quizzes)) return ['total' => 0, 'active' => 0];
    
    $enrollments = new WP_Query([
        'post_type' => 'enrollment',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'quiz_id',
                'value' => $quizzes,
                'compare' => 'IN'
            ]
        ]
    ]);
    
    return [
        'total' => $enrollments->found_posts,
        'active' => $enrollments->found_posts
    ];
}

function get_revenue_stats($teacher_id) {
    $quizzes = get_posts([
        'post_type' => 'quiz',
        'author' => $teacher_id,
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_quiz_type',
                'value' => 'paid',
                'compare' => '='
            ]
        ]
    ]);
    
    $total_revenue = 0;
    foreach ($quizzes as $quiz) {
        $price = get_post_meta($quiz->ID, '_quiz_price', true);
        $enrollments = new WP_Query([
            'post_type' => 'enrollment',
            'meta_query' => [
                [
                    'key' => 'quiz_id',
                    'value' => $quiz->ID,
                    'compare' => '='
                ]
            ]
        ]);
        
        $total_revenue += floatval($price) * $enrollments->found_posts;
    }
    
    return $total_revenue;
}

?>

<div class="teacher-dashboard">
    <div class="dashboard-header">
        <div class="container">
            <h1>Teacher Dashboard</h1>
            <p>Welcome, <?php echo esc_html($user->display_name); ?></p>
        </div>
    </div>

    <div class="dashboard-stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?php echo $quizzes->found_posts; ?></h3>
                        <p>Total Quizzes</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3><?php echo $enrollment_stats['total']; ?></h3>
                        <p>Total Enrollments</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($revenue_stats, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-content">
                        <h3>4.5</h3>
                        <p>Average Rating</p>
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
                            <li><a href="#quizzes" data-tab="quizzes">My Quizzes</a></li>
                            <li><a href="#create" data-tab="create">Create New Quiz</a></li>
                            <li><a href="#students" data-tab="students">Students</a></li>
                            <li><a href="#analytics" data-tab="analytics">Analytics</a></li>
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
                                <?php
                                // Get recent enrollments
                                $recent_enrollments = new WP_Query([
                                    'post_type' => 'enrollment',
                                    'posts_per_page' => 5,
                                    'meta_query' => [
                                        [
                                            'key' => 'quiz_id',
                                            'value' => $quizzes->posts,
                                            'compare' => 'IN'
                                        ]
                                    ],
                                    'orderby' => 'date',
                                    'order' => 'DESC'
                                ]);
                                
                                if ($recent_enrollments->have_posts()) {
                                    while ($recent_enrollments->have_posts()) {
                                        $recent_enrollments->the_post();
                                        $quiz_id = get_post_meta(get_the_ID(), 'quiz_id', true);
                                        $student_id = get_post_meta(get_the_ID(), 'student_id', true);
                                        $student = get_userdata($student_id);
                                        ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">📝</div>
                                            <div class="activity-content">
                                                <p><strong><?php echo $student->display_name; ?></strong> enrolled in <strong><?php echo get_the_title($quiz_id); ?></strong></p>
                                                <span class="activity-time"><?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ago'; ?></span>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                    wp_reset_postdata();
                                } else {
                                    echo '<p>No recent activity.</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quizzes Tab -->
                    <div id="quizzes-tab" class="tab-content">
                        <div class="quizzes-header">
                            <h2>My Quizzes</h2>
                            <a href="#create" class="btn btn-primary" data-tab="create">Create New Quiz</a>
                        </div>
                        
                        <div class="quizzes-list">
                            <?php if ($quizzes->have_posts()) : ?>
                                <?php while ($quizzes->have_posts()) : $quizzes->the_post(); ?>
                                    <div class="quiz-item">
                                        <div class="quiz-info">
                                            <h3><?php the_title(); ?></h3>
                                            <p class="quiz-meta">
                                                <span class="quiz-status <?php echo get_post_status(); ?>"><?php echo ucfirst(get_post_status()); ?></span>
                                                <span class="quiz-type"><?php echo ucfirst(get_post_meta(get_the_ID(), '_quiz_type', true)); ?></span>
                                                <span class="quiz-price">$<?php echo number_format(get_post_meta(get_the_ID(), '_quiz_price', true), 2); ?></span>
                                            </p>
                                        </div>
                                        
                                        <div class="quiz-stats">
                                            <div class="stat">
                                                <span class="stat-value"><?php echo $this->get_quiz_enrollments(get_the_ID()); ?></span>
                                                <span class="stat-label">Enrollments</span>
                                            </div>
                                        </div>
                                        
                                        <div class="quiz-actions">
                                            <a href="<?php echo get_edit_post_link(); ?>" class="btn btn-secondary">Edit</a>
                                            <a href="<?php the_permalink(); ?>" class="btn btn-primary">View</a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                                <?php wp_reset_postdata(); ?>
                            <?php else : ?>
                                <p>No quizzes created yet. <a href="#create" data-tab="create">Create your first quiz</a></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Create Tab -->
                    <div id="create-tab" class="tab-content">
                        <h2>Create New Quiz</h2>
                        <?php echo do_shortcode('[create_quiz_form]'); ?>
                    </div>

                    <!-- Students Tab -->
                    <div id="students-tab" class="tab-content">
                        <h2>Student Management</h2>
                        
                        <div class="students-list">
                            <?php
                            // Get all students enrolled in teacher's quizzes
                            $students = $this->get_teacher_students($teacher_id);
                            
                            if (!empty($students)) {
                                foreach ($students as $student) {
                                    ?>
                                    <div class="student-item">
                                        <div class="student-info">
                                            <h4><?php echo $student->display_name; ?></h4>
                                            <p><?php echo $student->user_email; ?></p>
                                        </div>
                                        <div class="student-stats">
                                            <span><?php echo $student->enrollment_count; ?> enrollments</span>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo '<p>No students enrolled yet.</p>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Analytics Tab -->
                    <div id="analytics-tab" class="tab-content">
                        <h2>Analytics</h2>
                        
                        <div class="analytics-grid">
                            <div class="chart-container">
                                <h3>Enrollment Trends</h3>
                                <canvas id="enrollment-chart"></canvas>
                            </div>
                            
                            <div class="chart-container">
                                <h3>Revenue Overview</h3>
                                <canvas id="revenue-chart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Tab -->
                    <div id="settings-tab" class="tab-content">
                        <h2>Teacher Settings</h2>
                        
                        <form class="teacher-settings-form">
                            <div class="form-group">
                                <label>Profile Information</label>
                                <input type="text" name="display_name" value="<?php echo esc_attr($user->display_name); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Bio</label>
                                <textarea name="description" rows="4"><?php echo esc_textarea(get_user_meta($user->ID, 'description', true)); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.teacher-dashboard {
    background: #f8f9fa;
    min-height: 100vh;
}

.dashboard-header {
    background: #007cba;
    color: white;
    padding: 40px 0;
    text-align: center;
}

.dashboard-header h1 {
    margin: 0 0 10px 0;
    font-size: 36px;
}

.dashboard-stats {
    padding: 40px 0;
    background: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.stat-card {
    display: flex;
    align-items: center;
    padding: 30px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    font-size: 48px;
    margin-right: 20px;
}

.stat-content h3 {
    margin: 0;
    font-size: 32px;
    color: #007cba;
}

.stat-content p {
    margin: 5px 0 0 0;
    color: #666;
}

.dashboard-content {
    padding: 40px 0;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.dashboard-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.dashboard-nav a {
    display: block;
    padding: 15px 20px;
    color: #333;
    text-decoration: none;
    border-radius: 4px;
    margin-bottom: 5px;
    transition: all 0.3s;
}

.dashboard-nav a:hover,
.dashboard-nav a.active {
    background: #007cba;
    color: white;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.quiz-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: white;
    border-radius: 8px;
    margin-bottom: 15px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.quiz-meta {
    display: flex;
    gap: 10px;
    align-items: center;
}

.quiz-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.quiz-status.publish {
    background: #d4edda;
    color: #155724;
}

.quiz-status.draft {
    background: #fff3cd;
    color: #856404;
}

.quiz-type {
    background: #e9ecef;
    color: #495057;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.quiz-stats {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #007cba;
}

.stat-label {
    font-size: 12px;
    color: #666;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 15px;
    background: white;
    border-radius: 8px;
    margin-bottom: 10px;
}

.activity-icon {
    font-size: 24px;
    margin-right: 15px;
}

.activity-time {
    color: #666;
    font-size: 12px;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-sidebar {
        order: 2;
    }
    
    .quiz-item {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .quiz-actions {
        margin-top: 15px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.dashboard-nav a').on('click', function(e) {
        e.preventDefault();
        
        const tab = $(this).data('tab');
        
        // Update active nav
        $('.dashboard-nav a').removeClass('active');
        $(this).addClass('active');
        
        // Update active tab content
        $('.tab-content').removeClass('active');
        $(`#${tab}-tab`).addClass('active');
    });
});
</script>

<?php get_footer(); ?>