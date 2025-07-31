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
if (!in_array('mcq_teacher', $user->roles)) {
    wp_redirect(home_url());
    exit;
}

$teacher_id = $user->ID;

// Include registration redirect functions
require_once get_template_directory() . '/registration-redirect.php';

// Get teacher's quizzes
$quizzes = new WP_Query([
    'post_type' => 'quiz',
    'author' => $teacher_id,
    'posts_per_page' => -1,
    'post_status' => 'any'
]);

// Get enrollment statistics
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

// Get enrollment and revenue stats
$enrollment_stats = get_enrollment_stats($teacher_id);
$revenue_stats = get_revenue_stats($teacher_id);

?>

<div class="teacher-dashboard">
    <div class="dashboard-header">
        <div class="container">
            <h1>Teacher Dashboard</h1>
            <p>Welcome, <?php echo esc_html($user->display_name); ?></p>
            <?php mcqhome_dashboard_navigation(); ?>
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
                                                <span class="stat-value"><?php 
                                                $enrollment_count = new WP_Query([
                                                    'post_type' => 'enrollment',
                                                    'meta_query' => [
                                                        [
                                                            'key' => 'quiz_id',
                                                            'value' => get_the_ID(),
                                                            'compare' => '='
                                                        ]
                                                    ]
                                                ]);
                                                echo $enrollment_count->found_posts;
                                                ?></span>
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
                            // Get students who enrolled in this teacher's quizzes
                            $teacher_quizzes = get_posts([
                                'post_type' => 'quiz',
                                'author' => $teacher_id,
                                'posts_per_page' => -1,
                                'fields' => 'ids'
                            ]);
                            
                            $student_ids = [];
                            if (!empty($teacher_quizzes)) {
                                $enrollments = new WP_Query([
                                    'post_type' => 'enrollment',
                                    'posts_per_page' => -1,
                                    'meta_query' => [
                                        [
                                            'key' => 'quiz_id',
                                            'value' => $teacher_quizzes,
                                            'compare' => 'IN'
                                        ]
                                    ]
                                ]);
                                
                                while ($enrollments->have_posts()) {
                                    $enrollments->the_post();
                                    $student_id = get_post_meta(get_the_ID(), 'student_id', true);
                                    if ($student_id) {
                                        $student_ids[] = $student_id;
                                    }
                                }
                                wp_reset_postdata();
                            }
                            
                            $student_ids = array_unique($student_ids);
                            
                            if (!empty($student_ids)) {
                                foreach ($student_ids as $student_id) {
                                    $student = get_userdata($student_id);
                                    if ($student) {
                                        // Count enrollments for this student in teacher's quizzes
                                        $enrollment_count = new WP_Query([
                                            'post_type' => 'enrollment',
                                            'meta_query' => [
                                                [
                                                    'key' => 'student_id',
                                                    'value' => $student_id,
                                                    'compare' => '='
                                                ],
                                                [
                                                    'key' => 'quiz_id',
                                                    'value' => $teacher_quizzes,
                                                    'compare' => 'IN'
                                                ]
                                            ]
                                        ]);
                                        ?>
                                        <div class="student-item">
                                            <div class="student-info">
                                                <h4><?php echo $student->display_name; ?></h4>
                                                <p><?php echo $student->user_email; ?></p>
                                            </div>
                                            <div class="student-stats">
                                                <span><?php echo $enrollment_count->found_posts; ?> enrollments</span>
                                            </div>
                                        </div>
                                        <?php
                                        wp_reset_postdata();
                                    }
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

<?php get_footer(); ?>