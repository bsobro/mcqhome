<?php
/**
 * Template Name: Institution Dashboard
 */

get_header();

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$user = wp_get_current_user();
if (!in_array('mcq_institution', $user->roles)) {
    wp_redirect(home_url());
    exit;
}

$institution_id = $user->ID;

// Get all teachers in the institution
$teachers = get_users([
    'role' => 'mcq_teacher',
    'meta_key' => 'institution_id',
    'meta_value' => $institution_id,
    'number' => -1
]);

// Get all quizzes across the institution
$teacher_ids = array_map(function($teacher) { return $teacher->ID; }, $teachers);
$teacher_ids[] = $institution_id; // Include institution's own quizzes

$quizzes = new WP_Query([
    'post_type' => 'quiz',
    'author__in' => $teacher_ids,
    'posts_per_page' => -1,
    'post_status' => 'any'
]);

// Get enrollment statistics for entire institution
function get_institution_enrollment_stats($teacher_ids) {
    $quizzes = get_posts([
        'post_type' => 'quiz',
        'author__in' => $teacher_ids,
        'posts_per_page' => -1,
        'fields' => 'ids'
    ]);
    
    if (empty($quizzes)) return ['total' => 0, 'active' => 0, 'completed' => 0];
    
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
    
    $active_enrollments = new WP_Query([
        'post_type' => 'enrollment',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'quiz_id',
                'value' => $quizzes,
                'compare' => 'IN'
            ],
            [
                'key' => 'status',
                'value' => 'enrolled',
                'compare' => '='
            ]
        ]
    ]);
    
    $completed_enrollments = new WP_Query([
        'post_type' => 'enrollment',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'quiz_id',
                'value' => $quizzes,
                'compare' => 'IN'
            ],
            [
                'key' => 'status',
                'value' => 'completed',
                'compare' => '='
            ]
        ]
    ]);
    
    return [
        'total' => $enrollments->found_posts,
        'active' => $active_enrollments->found_posts,
        'completed' => $completed_enrollments->found_posts
    ];
}

// Get revenue statistics for entire institution
function get_institution_revenue_stats($teacher_ids) {
    $quizzes = get_posts([
        'post_type' => 'quiz',
        'author__in' => $teacher_ids,
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

// Get institution stats
$enrollment_stats = get_institution_enrollment_stats($teacher_ids);
$revenue_stats = get_institution_revenue_stats($teacher_ids);

?>

<div class="institution-dashboard">
    <div class="dashboard-header">
        <div class="container">
            <h1>Institution Dashboard</h1>
            <p>Welcome, <?php echo esc_html($user->display_name); ?></p>
            <?php mcqhome_dashboard_navigation(); ?>
        </div>
    </div>

    <div class="dashboard-stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👨‍🏫</div>
                    <div class="stat-content">
                        <h3><?php echo count($teachers); ?></h3>
                        <p>Total Teachers</p>
                    </div>
                </div>
                
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
                            <li><a href="#teachers" data-tab="teachers">Teachers</a></li>
                            <li><a href="#quizzes" data-tab="quizzes">All Quizzes</a></li>
                            <li><a href="#students" data-tab="students">Students</a></li>
                            <li><a href="#analytics" data-tab="analytics">Analytics</a></li>
                            <li><a href="#settings" data-tab="settings">Settings</a></li>
                        </ul>
                    </nav>
                </div>

                <div class="dashboard-main">
                    <!-- Overview Tab -->
                    <div id="overview-tab" class="tab-content active">
                        <h2>Institution Overview</h2>
                        
                        <div class="overview-section">
                            <h3>Recent Activity</h3>
                            <div class="activity-list">
                                <?php
                                // Get recent enrollments across all teachers
                                $recent_enrollments = new WP_Query([
                                    'post_type' => 'enrollment',
                                    'posts_per_page' => 5,
                                    'orderby' => 'date',
                                    'order' => 'DESC'
                                ]);
                                
                                if ($recent_enrollments->have_posts()) {
                                    while ($recent_enrollments->have_posts()) {
                                        $recent_enrollments->the_post();
                                        $quiz_id = get_post_meta(get_the_ID(), 'quiz_id', true);
                                        $quiz = get_post($quiz_id);
                                        $student_id = get_post_meta(get_the_ID(), 'student_id', true);
                                        $student = get_userdata($student_id);
                                        $teacher = get_userdata($quiz->post_author);
                                        
                                        if (in_array($quiz->post_author, $teacher_ids)) {
                                            ?>
                                            <div class="activity-item">
                                                <div class="activity-icon">📝</div>
                                                <div class="activity-content">
                                                    <p><strong><?php echo $student->display_name; ?></strong> enrolled in <strong><?php echo get_the_title($quiz_id); ?></strong> by <?php echo $teacher->display_name; ?></p>
                                                    <span class="activity-time"><?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ago'; ?></span>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    }
                                    wp_reset_postdata();
                                } else {
                                    echo '<p>No recent activity.</p>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="overview-section">
                            <h3>Quick Actions</h3>
                            <div class="quick-actions">
                                <a href="<?php echo site_url('/wp-admin/user-new.php?role=mcq_teacher'); ?>" class="btn btn-primary">Add New Teacher</a>
                                <a href="#teachers" data-tab="teachers" class="btn btn-secondary">Manage Teachers</a>
                                <a href="#quizzes" data-tab="quizzes" class="btn btn-secondary">View All Quizzes</a>
                            </div>
                        </div>
                    </div>

                    <!-- Teachers Tab -->
                    <div id="teachers-tab" class="tab-content">
                        <h2>Manage Teachers</h2>
                        
                        <div class="teachers-list">
                            <?php if (!empty($teachers)) : ?>
                                <table class="teachers-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Quizzes Created</th>
                                            <th>Total Enrollments</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teachers as $teacher) : 
                                            $teacher_quizzes = new WP_Query([
                                                'post_type' => 'quiz',
                                                'author' => $teacher->ID,
                                                'posts_per_page' => -1
                                            ]);
                                            
                                            $teacher_enrollments = new WP_Query([
                                                'post_type' => 'enrollment',
                                                'posts_per_page' => -1,
                                                'meta_query' => [
                                                    [
                                                        'key' => 'quiz_id',
                                                        'value' => wp_list_pluck($teacher_quizzes->posts, 'ID'),
                                                        'compare' => 'IN'
                                                    ]
                                                ]
                                            ]);
                                        ?>
                                            <tr>
                                                <td><?php echo esc_html($teacher->display_name); ?></td>
                                                <td><?php echo esc_html($teacher->user_email); ?></td>
                                                <td><?php echo $teacher_quizzes->found_posts; ?></td>
                                                <td><?php echo $teacher_enrollments->found_posts; ?></td>
                                                <td>
                                                    <a href="<?php echo get_author_posts_url($teacher->ID); ?>" class="btn btn-sm">View Profile</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else : ?>
                                <p>No teachers found. <a href="<?php echo site_url('/wp-admin/user-new.php?role=mcq_teacher'); ?>" class="btn btn-primary">Add your first teacher</a></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quizzes Tab -->
                    <div id="quizzes-tab" class="tab-content">
                        <h2>All Institution Quizzes</h2>
                        
                        <div class="quizzes-list">
                            <?php if ($quizzes->have_posts()) : ?>
                                <table class="quizzes-table">
                                    <thead>
                                        <tr>
                                            <th>Quiz Title</th>
                                            <th>Teacher</th>
                                            <th>Type</th>
                                            <th>Price</th>
                                            <th>Enrollments</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($quizzes->have_posts()) : $quizzes->the_post(); 
                                            $teacher = get_userdata(get_the_author_meta('ID'));
                                            $quiz_type = get_post_meta(get_the_ID(), '_quiz_type', true);
                                            $quiz_price = get_post_meta(get_the_ID(), '_quiz_price', true);
                                            
                                            $quiz_enrollments = new WP_Query([
                                                'post_type' => 'enrollment',
                                                'meta_query' => [
                                                    [
                                                        'key' => 'quiz_id',
                                                        'value' => get_the_ID(),
                                                        'compare' => '='
                                                    ]
                                                ]
                                            ]);
                                        ?>
                                            <tr>
                                                <td><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></td>
                                                <td><?php echo esc_html($teacher->display_name); ?></td>
                                                <td><?php echo esc_html($quiz_type ?: 'Free'); ?></td>
                                                <td>$<?php echo esc_html($quiz_price ?: '0'); ?></td>
                                                <td><?php echo $quiz_enrollments->found_posts; ?></td>
                                                <td>
                                                    <a href="<?php echo get_edit_post_link(); ?>" class="btn btn-sm">Edit</a>
                                                    <a href="<?php the_permalink(); ?>" class="btn btn-sm">View</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; wp_reset_postdata(); ?>
                                    </tbody>
                                </table>
                            <?php else : ?>
                                <p>No quizzes found.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Students Tab -->
                    <div id="students-tab" class="tab-content">
                        <h2>All Students</h2>
                        
                        <div class="students-list">
                            <?php
                            // Get all students enrolled in institution quizzes
                            $all_enrollments = new WP_Query([
                                'post_type' => 'enrollment',
                                'posts_per_page' => -1,
                                'meta_query' => [
                                    [
                                        'key' => 'quiz_id',
                                        'value' => wp_list_pluck($quizzes->posts, 'ID'),
                                        'compare' => 'IN'
                                    ]
                                ]
                            ]);
                            
                            $student_ids = [];
                            if ($all_enrollments->have_posts()) {
                                while ($all_enrollments->have_posts()) {
                                    $all_enrollments->the_post();
                                    $student_ids[] = get_post_meta(get_the_ID(), 'student_id', true);
                                }
                                wp_reset_postdata();
                            }
                            
                            $unique_students = array_unique($student_ids);
                            $students = get_users([
                                'include' => $unique_students,
                                'number' => -1
                            ]);
                            ?>
                            
                            <?php if (!empty($students)) : ?>
                                <table class="students-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Enrollments</th>
                                            <th>Completed</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student) : 
                                            $student_enrollments = new WP_Query([
                                                'post_type' => 'enrollment',
                                                'meta_query' => [
                                                    [
                                                        'key' => 'student_id',
                                                        'value' => $student->ID,
                                                        'compare' => '='
                                                    ],
                                                    [
                                                        'key' => 'quiz_id',
                                                        'value' => wp_list_pluck($quizzes->posts, 'ID'),
                                                        'compare' => 'IN'
                                                    ]
                                                ]
                                            ]);
                                            
                                            $completed_count = new WP_Query([
                                                'post_type' => 'enrollment',
                                                'meta_query' => [
                                                    [
                                                        'key' => 'student_id',
                                                        'value' => $student->ID,
                                                        'compare' => '='
                                                    ],
                                                    [
                                                        'key' => 'quiz_id',
                                                        'value' => wp_list_pluck($quizzes->posts, 'ID'),
                                                        'compare' => 'IN'
                                                    ],
                                                    [
                                                        'key' => 'status',
                                                        'value' => 'completed',
                                                        'compare' => '='
                                                    ]
                                                ]
                                            ]);
                                        ?>
                                            <tr>
                                                <td><?php echo esc_html($student->display_name); ?></td>
                                                <td><?php echo esc_html($student->user_email); ?></td>
                                                <td><?php echo $student_enrollments->found_posts; ?></td>
                                                <td><?php echo $completed_count->found_posts; ?></td>
                                                <td>
                                                    <a href="<?php echo get_author_posts_url($student->ID); ?>" class="btn btn-sm">View Profile</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else : ?>
                                <p>No students enrolled in your institution's quizzes yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Analytics Tab -->
                    <div id="analytics-tab" class="tab-content">
                        <h2>Institution Analytics</h2>
                        
                        <div class="analytics-section">
                            <h3>Performance Overview</h3>
                            <div class="analytics-grid">
                                <div class="analytics-card">
                                    <h4>Total Teachers</h4>
                                    <p><?php echo count($teachers); ?></p>
                                </div>
                                <div class="analytics-card">
                                    <h4>Total Quizzes</h4>
                                    <p><?php echo $quizzes->found_posts; ?></p>
                                </div>
                                <div class="analytics-card">
                                    <h4>Total Revenue</h4>
                                    <p>$<?php echo number_format($revenue_stats, 2); ?></p>
                                </div>
                                <div class="analytics-card">
                                    <h4>Active Enrollments</h4>
                                    <p><?php echo $enrollment_stats['active']; ?></p>
                                </div>
                            </div>
                            
                            <div class="charts-container">
                                <div class="chart-placeholder">
                                    <p>📊 Revenue Chart</p>
                                    <small>Institution-wide revenue trends</small>
                                </div>
                                <div class="chart-placeholder">
                                    <p>📈 Enrollment Chart</p>
                                    <small>Student enrollment trends</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Tab -->
                    <div id="settings-tab" class="tab-content">
                        <h2>Institution Settings</h2>
                        
                        <div class="settings-section">
                            <h3>Profile Settings</h3>
                            <form class="settings-form">
                                <div class="form-group">
                                    <label for="institution_name">Institution Name</label>
                                    <input type="text" id="institution_name" name="institution_name" value="<?php echo esc_attr($user->display_name); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="institution_email">Contact Email</label>
                                    <input type="email" id="institution_email" name="institution_email" value="<?php echo esc_attr($user->user_email); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="institution_description">Description</label>
                                    <textarea id="institution_description" name="institution_description"><?php echo esc_textarea(get_user_meta($user->ID, 'description', true)); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php get_footer(); ?>