<?php
/**
 * MCQ Home Roles & Permissions System
 * Handles teacher/institution and student roles with quiz creation and enrollment
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MCQHome_Roles_System {
    
    public function __construct() {
        add_action('init', [$this, 'register_custom_roles']);
        add_action('init', [$this, 'register_quiz_post_type']);
        add_action('init', [$this, 'register_enrollment_post_type']);
        add_action('admin_init', [$this, 'add_role_capabilities']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_role_scripts']);
        
        // User registration modifications
        add_action('register_form', [$this, 'add_registration_role_field']);
        add_action('user_register', [$this, 'save_user_role']);
        
        // Dashboard modifications
        add_action('wp_dashboard_setup', [$this, 'custom_dashboard']);
        add_action('admin_menu', [$this, 'custom_admin_menus']);
        
        // Frontend user dashboard
        add_shortcode('mcq_teacher_dashboard', [$this, 'teacher_dashboard_shortcode']);
        add_shortcode('mcq_student_dashboard', [$this, 'student_dashboard_shortcode']);
    }
    
    /**
     * Register custom user roles
     */
    public function register_custom_roles() {
        // Teacher role
        add_role('mcq_teacher', 'Teacher/Instructor', [
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'publish_posts' => true,
            'upload_files' => true,
            'edit_published_posts' => true,
            'delete_published_posts' => true,
            'edit_quiz' => true,
            'edit_quizzes' => true,
            'edit_published_quizzes' => true,
            'publish_quizzes' => true,
            'read_quiz' => true,
            'delete_quiz' => true,
            'delete_quizzes' => true,
            'edit_others_quizzes' => false,
            'delete_others_quizzes' => false,
        ]);
        
        // Institution role
        add_role('mcq_institution', 'Institution', [
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'publish_posts' => true,
            'upload_files' => true,
            'edit_published_posts' => true,
            'delete_published_posts' => true,
            'edit_quiz' => true,
            'edit_quizzes' => true,
            'edit_published_quizzes' => true,
            'publish_quizzes' => true,
            'read_quiz' => true,
            'delete_quiz' => true,
            'delete_quizzes' => true,
            'edit_others_quizzes' => true,
            'delete_others_quizzes' => true,
            'manage_categories' => true,
        ]);
        
        // Student role (extends subscriber)
        add_role('mcq_student', 'Student', [
            'read' => true,
            'enroll_in_quiz' => true,
            'take_quiz' => true,
            'view_results' => true,
            'track_progress' => true,
        ]);
    }
    
    /**
     * Register Quiz custom post type
     */
    public function register_quiz_post_type() {
        $labels = [
            'name' => 'Quizzes',
            'singular_name' => 'Quiz',
            'menu_name' => 'Quizzes',
            'name_admin_bar' => 'Quiz',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Quiz',
            'new_item' => 'New Quiz',
            'edit_item' => 'Edit Quiz',
            'view_item' => 'View Quiz',
            'all_items' => 'All Quizzes',
            'search_items' => 'Search Quizzes',
            'parent_item_colon' => 'Parent Quizzes:',
            'not_found' => 'No quizzes found.',
            'not_found_in_trash' => 'No quizzes found in Trash.',
        ];
        
        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'quiz', 'with_front' => false],
            'capability_type' => 'quiz',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'taxonomies' => ['quiz_category', 'quiz_tag'],
        ];
        
        register_post_type('quiz', $args);
    }
    
    /**
     * Register Enrollment custom post type
     */
    public function register_enrollment_post_type() {
        $labels = [
            'name' => 'Enrollments',
            'singular_name' => 'Enrollment',
            'menu_name' => 'Enrollments',
            'name_admin_bar' => 'Enrollment',
        ];
        
        $args = [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=quiz',
            'capability_type' => 'post',
            'supports' => ['title', 'custom-fields'],
        ];
        
        register_post_type('enrollment', $args);
    }
    
    /**
     * Add custom capabilities
     */
    public function add_role_capabilities() {
        $roles = ['administrator', 'mcq_teacher', 'mcq_institution'];
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $role->add_cap('edit_quiz');
                $role->add_cap('edit_quizzes');
                $role->add_cap('edit_published_quizzes');
                $role->add_cap('publish_quizzes');
                $role->add_cap('read_quiz');
                $role->add_cap('delete_quiz');
                $role->add_cap('delete_quizzes');
            }
        }
        
        // Institution gets additional capabilities
        $institution = get_role('mcq_institution');
        if ($institution) {
            $institution->add_cap('edit_others_quizzes');
            $institution->add_cap('delete_others_quizzes');
            $institution->add_cap('read_private_quizzes');
        }
    }
    
    /**
     * Add role selection to registration form
     */
    public function add_registration_role_field() {
        wp_enqueue_style('mcq-roles-style', get_template_directory_uri() . '/css/roles.css');
        ?>
        <div class="mcq-role-selection">
            <p class="form-row">
                <label for="mcq_role">I am a:</label>
                <select name="mcq_role" id="mcq_role" required>
                    <option value="">Select your role</option>
                    <option value="mcq_student">Student (Take quizzes)</option>
                    <option value="mcq_teacher">Teacher (Create quizzes)</option>
                    <option value="mcq_institution">Institution (Manage teachers & quizzes)</option>
                </select>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save user role during registration
     */
    public function save_user_role($user_id) {
        if (isset($_POST['mcq_role'])) {
            $role = sanitize_text_field($_POST['mcq_role']);
            $user = new WP_User($user_id);
            $user->set_role($role);
        }
    }
    
    /**
     * Custom dashboard for different roles
     */
    public function custom_dashboard() {
        $user = wp_get_current_user();
        
        if (in_array('mcq_teacher', $user->roles) || in_array('mcq_institution', $user->roles)) {
            wp_add_dashboard_widget(
                'mcq_teacher_stats',
                'Your Quiz Statistics',
                [$this, 'teacher_dashboard_widget']
            );
        }
        
        if (in_array('mcq_student', $user->roles)) {
            wp_add_dashboard_widget(
                'mcq_student_progress',
                'Your Progress',
                [$this, 'student_dashboard_widget']
            );
        }
    }
    
    /**
     * Teacher dashboard widget
     */
    public function teacher_dashboard_widget() {
        $user_id = get_current_user_id();
        $quizzes = $this->get_teacher_quizzes($user_id);
        $total_enrollments = 0;
        
        foreach ($quizzes as $quiz) {
            $total_enrollments += $this->get_quiz_enrollments_count($quiz->ID);
        }
        
        echo '<div class="mcq-stats">';
        echo '<p><strong>Total Quizzes:</strong> ' . count($quizzes) . '</p>';
        echo '<p><strong>Total Enrollments:</strong> ' . $total_enrollments . '</p>';
        echo '<p><a href="' . admin_url('post-new.php?post_type=quiz') . '" class="button">Create New Quiz</a></p>';
        echo '</div>';
    }
    
    /**
     * Student dashboard widget
     */
    public function student_dashboard_widget() {
        $user_id = get_current_user_id();
        $enrollments = $this->get_student_enrollments($user_id);
        $completed = array_filter($enrollments, function($e) { return $e->status === 'completed'; });
        
        echo '<div class="mcq-stats">';
        echo '<p><strong>Enrolled Quizzes:</strong> ' . count($enrollments) . '</p>';
        echo '<p><strong>Completed:</strong> ' . count($completed) . '</p>';
        echo '<p><a href="' . site_url('/quizzes') . '" class="button">Browse Quizzes</a></p>';
        echo '</div>';
    }
    
    /**
     * Custom admin menus
     */
    public function custom_admin_menus() {
        $user = wp_get_current_user();
        
        if (in_array('mcq_student', $user->roles)) {
            // Hide some menu items for students
            remove_menu_page('edit.php');
            remove_menu_page('tools.php');
        }
    }
    
    /**
     * Teacher dashboard shortcode
     */
    public function teacher_dashboard_shortcode() {
        if (!is_user_logged_in() || !current_user_can('edit_quizzes')) {
            return '<p>Please <a href="' . wp_login_url() . '">login</a> as a teacher to access this page.</p>';
        }
        
        ob_start();
        ?>
        <div class="teacher-dashboard">
            <h2>Teacher Dashboard</h2>
            
            <div class="dashboard-stats">
                <?php
                $user_id = get_current_user_id();
                $quizzes = $this->get_teacher_quizzes($user_id);
                $total_enrollments = 0;
                
                foreach ($quizzes as $quiz) {
                    $total_enrollments += $this->get_quiz_enrollments_count($quiz->ID);
                }
                ?>
                
                <div class="stat-card">
                    <h3><?php echo count($quizzes); ?></h3>
                    <p>Total Quizzes</p>
                </div>
                
                <div class="stat-card">
                    <h3><?php echo $total_enrollments; ?></h3>
                    <p>Total Enrollments</p>
                </div>
            </div>
            
            <div class="dashboard-actions">
                <a href="<?php echo admin_url('post-new.php?post_type=quiz'); ?>" class="btn btn-primary">Create New Quiz</a>
                <a href="<?php echo admin_url('edit.php?post_type=quiz'); ?>" class="btn btn-secondary">Manage Quizzes</a>
            </div>
            
            <div class="recent-quizzes">
                <h3>Recent Quizzes</h3>
                <?php
                $recent_quizzes = new WP_Query([
                    'post_type' => 'quiz',
                    'author' => $user_id,
                    'posts_per_page' => 5,
                    'orderby' => 'date',
                    'order' => 'DESC'
                ]);
                
                if ($recent_quizzes->have_posts()) {
                    echo '<ul>';
                    while ($recent_quizzes->have_posts()) {
                        $recent_quizzes->the_post();
                        echo '<li><a href="' . get_edit_post_link() . '">' . get_the_title() . '</a></li>';
                    }
                    echo '</ul>';
                    wp_reset_postdata();
                } else {
                    echo '<p>No quizzes created yet.</p>';
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Student dashboard shortcode
     */
    public function student_dashboard_shortcode() {
        if (!is_user_logged_in() || !current_user_can('enroll_in_quiz')) {
            return '<p>Please <a href="' . wp_login_url() . '">login</a> as a student to access this page.</p>';
        }
        
        ob_start();
        ?>
        <div class="student-dashboard">
            <h2>Student Dashboard</h2>
            
            <div class="dashboard-stats">
                <?php
                $user_id = get_current_user_id();
                $enrollments = $this->get_student_enrollments($user_id);
                $completed = array_filter($enrollments, function($e) { return $e->status === 'completed'; });
                ?>
                
                <div class="stat-card">
                    <h3><?php echo count($enrollments); ?></h3>
                    <p>Enrolled Quizzes</p>
                </div>
                
                <div class="stat-card">
                    <h3><?php echo count($completed); ?></h3>
                    <p>Completed</p>
                </div>
            </div>
            
            <div class="dashboard-actions">
                <a href="<?php echo site_url('/quizzes'); ?>" class="btn btn-primary">Browse Quizzes</a>
                <a href="<?php echo site_url('/my-enrollments'); ?>" class="btn btn-secondary">My Enrollments</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Helper methods
    
    public function get_teacher_quizzes($teacher_id) {
        return get_posts([
            'post_type' => 'quiz',
            'author' => $teacher_id,
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft']
        ]);
    }
    
    public function get_quiz_enrollments_count($quiz_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}postmeta WHERE meta_key = 'quiz_id' AND meta_value = %d",
            $quiz_id
        ));
    }
    
    public function get_student_enrollments($student_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = 'student_id' AND meta_value = %d",
            $student_id
        ));
    }
    
    public function enqueue_role_scripts() {
        wp_enqueue_style('mcq-roles-style', get_template_directory_uri() . '/css/roles.css');
    }
}

// Initialize the roles system
new MCQHome_Roles_System();