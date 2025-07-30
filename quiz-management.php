<?php
/**
 * MCQ Home Quiz Management System
 * Handles quiz creation, pricing, and enrollment
 */

if (!defined('ABSPATH')) {
    exit;
}

class MCQHome_Quiz_Management {
    
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_quiz_meta_boxes']);
        add_action('save_post_quiz', [$this, 'save_quiz_meta']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_quiz_scripts']);
        
        // Frontend quiz creation
        add_shortcode('create_quiz_form', [$this, 'create_quiz_form_shortcode']);
        add_action('wp_ajax_create_quiz', [$this, 'handle_create_quiz']);
        add_action('wp_ajax_nopriv_create_quiz', [$this, 'handle_create_quiz']);
        
        // Enrollment system
        add_shortcode('quiz_enrollment_form', [$this, 'enrollment_form_shortcode']);
        add_action('wp_ajax_enroll_in_quiz', [$this, 'handle_enrollment']);
        add_action('wp_ajax_nopriv_enroll_in_quiz', [$this, 'handle_enrollment']);
        
        // Quiz display
        add_shortcode('quiz_catalog', [$this, 'quiz_catalog_shortcode']);
        add_shortcode('my_quizzes', [$this, 'my_quizzes_shortcode']);
        
        // Payment integration hooks
        add_action('init', [$this, 'register_payment_status_taxonomy']);
        add_action('init', [$this, 'register_subscription_plans']);
    }
    
    /**
     * Add meta boxes for quiz pricing and settings
     */
    public function add_quiz_meta_boxes() {
        add_meta_box(
            'quiz_pricing',
            'Quiz Pricing & Settings',
            [$this, 'quiz_pricing_meta_box'],
            'quiz',
            'normal',
            'high'
        );
    }
    
    /**
     * Quiz pricing meta box
     */
    public function quiz_pricing_meta_box($post) {
        wp_nonce_field('quiz_pricing_nonce', 'quiz_pricing_nonce_field');
        
        $price = get_post_meta($post->ID, '_quiz_price', true);
        $type = get_post_meta($post->ID, '_quiz_type', true);
        $duration = get_post_meta($post->ID, '_quiz_duration', true);
        $max_attempts = get_post_meta($post->ID, '_quiz_max_attempts', true);
        $instructions = get_post_meta($post->ID, '_quiz_instructions', true);
        
        ?>
        <div class="quiz-meta-fields">
            <p>
                <label for="quiz_type">Quiz Type:</label><br>
                <select name="quiz_type" id="quiz_type" required>
                    <option value="free" <?php selected($type, 'free'); ?>>Free</option>
                    <option value="paid" <?php selected($type, 'paid'); ?>>Paid</option>
                    <option value="subscription" <?php selected($type, 'subscription'); ?>>Subscription</option>
                </select>
            </p>
            
            <p>
                <label for="quiz_price">Price (USD):</label><br>
                <input type="number" name="quiz_price" id="quiz_price" 
                       value="<?php echo esc_attr($price); ?>" step="0.01" min="0">
            </p>
            
            <p>
                <label for="quiz_duration">Duration (minutes):</label><br>
                <input type="number" name="quiz_duration" id="quiz_duration" 
                       value="<?php echo esc_attr($duration); ?>" min="1">
            </p>
            
            <p>
                <label for="quiz_max_attempts">Maximum Attempts:</label><br>
                <input type="number" name="quiz_max_attempts" id="quiz_max_attempts" 
                       value="<?php echo esc_attr($max_attempts); ?>" min="1">
            </p>
            
            <p>
                <label for="quiz_instructions">Instructions:</label><br>
                <textarea name="quiz_instructions" id="quiz_instructions" rows="4" 
                          style="width: 100%;"><?php echo esc_textarea($instructions); ?></textarea>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save quiz meta data
     */
    public function save_quiz_meta($post_id) {
        if (!isset($_POST['quiz_pricing_nonce_field']) || 
            !wp_verify_nonce($_POST['quiz_pricing_nonce_field'], 'quiz_pricing_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = [
            'quiz_price' => '_quiz_price',
            'quiz_type' => '_quiz_type',
            'quiz_duration' => '_quiz_duration',
            'quiz_max_attempts' => '_quiz_max_attempts',
            'quiz_instructions' => '_quiz_instructions',
        ];
        
        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, $meta_key, $value);
            }
        }
    }
    
    /**
     * Quiz catalog shortcode
     */
    public function quiz_catalog_shortcode($atts) {
        $atts = shortcode_atts([
            'filter' => 'all',
            'category' => '',
            'teacher' => '',
            'search' => '',
        ], $atts);
        
        ob_start();
        
        // Filter form
        ?>
        <div class="quiz-filters">
            <form method="get" action="">
                <select name="quiz_type">
                    <option value="">All Types</option>
                    <option value="free" <?php selected($_GET['quiz_type'], 'free'); ?>>Free</option>
                    <option value="paid" <?php selected($_GET['quiz_type'], 'paid'); ?>>Paid</option>
                </select>
                
                <input type="text" name="search" placeholder="Search quizzes..." 
                       value="<?php echo esc_attr($_GET['search'] ?? ''); ?>">
                
                <button type="submit">Filter</button>
            </form>
        </div>
        
        <div class="quiz-catalog">
            <?php
            $paged = get_query_var('paged') ? get_query_var('paged') : 1;
            
            $args = [
                'post_type' => 'quiz',
                'post_status' => 'publish',
                'posts_per_page' => 12,
                'paged' => $paged,
            ];
            
            // Apply filters
            if (!empty($_GET['quiz_type'])) {
                $args['meta_query'][] = [
                    'key' => '_quiz_type',
                    'value' => sanitize_text_field($_GET['quiz_type']),
                ];
            }
            
            if (!empty($_GET['search'])) {
                $args['s'] = sanitize_text_field($_GET['search']);
            }
            
            $quizzes = new WP_Query($args);
            
            if ($quizzes->have_posts()) {
                echo '<div class="quiz-grid">';
                while ($quizzes->have_posts()) {
                    $quizzes->the_post();
                    $this->render_quiz_card(get_post());
                }
                echo '</div>';
                
                echo '<div class="pagination">';
                echo paginate_links([
                    'total' => $quizzes->max_num_pages,
                    'current' => $paged,
                    'format' => '?paged=%#%',
                ]);
                echo '</div>';
                
                wp_reset_postdata();
            } else {
                echo '<p>No quizzes found.</p>';
            }
            ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render individual quiz card
     */
    private function render_quiz_card($quiz) {
        $price = get_post_meta($quiz->ID, '_quiz_price', true);
        $type = get_post_meta($quiz->ID, '_quiz_type', true);
        $duration = get_post_meta($quiz->ID, '_quiz_duration', true);
        
        $is_enrolled = $this->is_user_enrolled(get_current_user_id(), $quiz->ID);
        
        ?>
        <div class="quiz-card">
            <div class="quiz-image">
                <?php if (has_post_thumbnail($quiz->ID)) : ?>
                    <?php echo get_the_post_thumbnail($quiz->ID, 'medium'); ?>
                <?php else : ?>
                    <div class="quiz-placeholder">Quiz</div>
                <?php endif; ?>
            </div>
            
            <div class="quiz-content">
                <h3><a href="<?php the_permalink(); ?>"><?php echo get_the_title(); ?></a></h3>
                <p class="quiz-meta">
                    <span class="quiz-type <?php echo esc_attr($type); ?>">
                        <?php echo ucfirst($type); ?>
                    </span>
                    <?php if ($type !== 'free') : ?>
                        <span class="quiz-price">$<?php echo number_format($price, 2); ?></span>
                    <?php endif; ?>
                    <span class="quiz-duration"><?php echo $duration; ?> min</span>
                </p>
                
                <p class="quiz-author">
                    By <?php echo get_the_author(); ?>
                </p>
                
                <div class="quiz-actions">
                    <?php if ($is_enrolled) : ?>
                        <a href="<?php the_permalink(); ?>" class="btn btn-primary">Start Quiz</a>
                    <?php else : ?>
                        <button class="btn btn-primary enroll-btn" 
                                data-quiz-id="<?php echo $quiz->ID; ?>">
                            Enroll Now
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Create quiz form shortcode
     */
    public function create_quiz_form_shortcode() {
        if (!is_user_logged_in() || !current_user_can('publish_quizzes')) {
            return '<p>Please <a href="' . wp_login_url() . '">login</a> as a teacher to create quizzes.</p>';
        }
        
        ob_start();
        ?>
        <div class="create-quiz-form">
            <h2>Create New Quiz</h2>
            
            <form id="create-quiz-form" method="post">
                <?php wp_nonce_field('create_quiz_nonce', 'create_quiz_nonce_field'); ?>
                
                <div class="form-group">
                    <label for="quiz_title">Quiz Title *</label>
                    <input type="text" name="quiz_title" id="quiz_title" required>
                </div>
                
                <div class="form-group">
                    <label for="quiz_description">Description</label>
                    <textarea name="quiz_description" id="quiz_description" rows="4"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="quiz_type">Quiz Type *</label>
                        <select name="quiz_type" id="quiz_type" required>
                            <option value="">Select type</option>
                            <option value="free">Free</option>
                            <option value="paid">Paid</option>
                            <option value="subscription">Subscription</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quiz_price">Price ($)</label>
                        <input type="number" name="quiz_price" id="quiz_price" step="0.01" min="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="quiz_duration">Duration (minutes) *</label>
                        <input type="number" name="quiz_duration" id="quiz_duration" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="quiz_max_attempts">Max Attempts</label>
                        <input type="number" name="quiz_max_attempts" id="quiz_max_attempts" min="1" value="1">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="quiz_instructions">Instructions</label>
                    <textarea name="quiz_instructions" id="quiz_instructions" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Quiz</button>
            </form>
            
            <div id="create-quiz-response"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#create-quiz-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'create_quiz');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#create-quiz-response').html('<div class="success">Quiz created successfully! <a href="' + response.data.edit_url + '">Edit Quiz</a></div>');
                            $('#create-quiz-form')[0].reset();
                        } else {
                            $('#create-quiz-response').html('<div class="error">' + response.data + '</div>');
                        }
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle quiz creation via AJAX
     */
    public function handle_create_quiz() {
        if (!isset($_POST['create_quiz_nonce_field']) || 
            !wp_verify_nonce($_POST['create_quiz_nonce_field'], 'create_quiz_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!is_user_logged_in() || !current_user_can('publish_quizzes')) {
            wp_send_json_error('Permission denied');
        }
        
        $quiz_title = sanitize_text_field($_POST['quiz_title']);
        $quiz_description = sanitize_textarea_field($_POST['quiz_description']);
        $quiz_type = sanitize_text_field($_POST['quiz_type']);
        $quiz_price = floatval($_POST['quiz_price']);
        $quiz_duration = intval($_POST['quiz_duration']);
        $quiz_max_attempts = intval($_POST['quiz_max_attempts']);
        $quiz_instructions = sanitize_textarea_field($_POST['quiz_instructions']);
        
        if (empty($quiz_title) || empty($quiz_type) || empty($quiz_duration)) {
            wp_send_json_error('Please fill in all required fields');
        }
        
        $quiz_id = wp_insert_post([
            'post_title' => $quiz_title,
            'post_content' => $quiz_description,
            'post_type' => 'quiz',
            'post_status' => 'draft',
            'post_author' => get_current_user_id(),
        ]);
        
        if ($quiz_id && !is_wp_error($quiz_id)) {
            update_post_meta($quiz_id, '_quiz_type', $quiz_type);
            update_post_meta($quiz_id, '_quiz_price', $quiz_price);
            update_post_meta($quiz_id, '_quiz_duration', $quiz_duration);
            update_post_meta($quiz_id, '_quiz_max_attempts', $quiz_max_attempts);
            update_post_meta($quiz_id, '_quiz_instructions', $quiz_instructions);
            
            wp_send_json_success([
                'quiz_id' => $quiz_id,
                'edit_url' => get_edit_post_link($quiz_id)
            ]);
        } else {
            wp_send_json_error('Failed to create quiz');
        }
    }
    
    /**
     * Enrollment form shortcode
     */
    public function enrollment_form_shortcode($atts) {
        $atts = shortcode_atts([
            'quiz_id' => 0,
        ], $atts);
        
        if (!$atts['quiz_id']) {
            return '<p>Quiz not specified.</p>';
        }
        
        $quiz = get_post($atts['quiz_id']);
        if (!$quiz || $quiz->post_type !== 'quiz') {
            return '<p>Quiz not found.</p>';
        }
        
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to enroll.</p>';
        }
        
        $user = wp_get_current_user();
        if (!in_array('mcq_student', $user->roles)) {
            return '<p>Only students can enroll in quizzes.</p>';
        }
        
        $is_enrolled = $this->is_user_enrolled(get_current_user_id(), $atts['quiz_id']);
        
        ob_start();
        ?>
        <div class="quiz-enrollment">
            <h3>Enroll in: <?php echo get_the_title($atts['quiz_id']); ?></h3>
            
            <?php if ($is_enrolled) : ?>
                <p>You are already enrolled in this quiz.</p>
                <a href="<?php the_permalink($atts['quiz_id']); ?>" class="btn btn-primary">Start Quiz</a>
            <?php else : ?>
                <form id="quiz-enrollment-form" method="post">
                    <?php wp_nonce_field('enroll_quiz_nonce', 'enroll_quiz_nonce_field'); ?>
                    <input type="hidden" name="quiz_id" value="<?php echo $atts['quiz_id']; ?>">
                    
                    <div class="enrollment-details">
                        <p><strong>Type:</strong> <?php echo ucfirst(get_post_meta($atts['quiz_id'], '_quiz_type', true)); ?></p>
                        <p><strong>Price:</strong> 
                            <?php 
                            $type = get_post_meta($atts['quiz_id'], '_quiz_type', true);
                            $price = get_post_meta($atts['quiz_id'], '_quiz_price', true);
                            echo $type === 'free' ? 'Free' : '$' . number_format($price, 2);
                            ?>
                        </p>
                        <p><strong>Duration:</strong> <?php echo get_post_meta($atts['quiz_id'], '_quiz_duration', true); ?> minutes</p>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Enroll Now</button>
                </form>
                
                <div id="enrollment-response"></div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#quiz-enrollment-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'enroll_in_quiz');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#enrollment-response').html('<div class="success">Successfully enrolled! Redirecting...</div>');
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 2000);
                        } else {
                            $('#enrollment-response').html('<div class="error">' + response.data + '</div>');
                        }
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle enrollment via AJAX
     */
    public function handle_enrollment() {
        if (!isset($_POST['enroll_quiz_nonce_field']) || 
            !wp_verify_nonce($_POST['enroll_quiz_nonce_field'], 'enroll_quiz_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please login to enroll');
        }
        
        $user = wp_get_current_user();
        if (!in_array('mcq_student', $user->roles)) {
            wp_send_json_error('Only students can enroll');
        }
        
        $quiz_id = intval($_POST['quiz_id']);
        $quiz = get_post($quiz_id);
        
        if (!$quiz || $quiz->post_type !== 'quiz') {
            wp_send_json_error('Quiz not found');
        }
        
        $user_id = get_current_user_id();
        
        // Check if already enrolled
        if ($this->is_user_enrolled($user_id, $quiz_id)) {
            wp_send_json_error('Already enrolled in this quiz');
        }
        
        // Create enrollment record
        $enrollment_id = wp_insert_post([
            'post_title' => 'Enrollment: ' . $user->user_login . ' - ' . get_the_title($quiz_id),
            'post_type' => 'enrollment',
            'post_status' => 'publish',
            'post_author' => $user_id,
        ]);
        
        if ($enrollment_id && !is_wp_error($enrollment_id)) {
            update_post_meta($enrollment_id, 'quiz_id', $quiz_id);
            update_post_meta($enrollment_id, 'student_id', $user_id);
            update_post_meta($enrollment_id, 'enrollment_date', current_time('mysql'));
            update_post_meta($enrollment_id, 'status', 'enrolled');
            update_post_meta($enrollment_id, 'attempts_count', 0);
            
            wp_send_json_success([
                'redirect_url' => get_permalink($quiz_id)
            ]);
        } else {
            wp_send_json_error('Failed to enroll');
        }
    }
    
    /**
     * Check if user is enrolled in quiz
     */
    public function is_user_enrolled($user_id, $quiz_id) {
        $enrollments = new WP_Query([
            'post_type' => 'enrollment',
            'author' => $user_id,
            'meta_query' => [
                [
                    'key' => 'quiz_id',
                    'value' => $quiz_id,
                ]
            ],
            'posts_per_page' => 1,
        ]);
        
        return $enrollments->have_posts();
    }
    
    /**
     * My quizzes shortcode for students
     */
    public function my_quizzes_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url() . '">login</a> to view your quizzes.</p>';
        }
        
        $user = wp_get_current_user();
        
        ob_start();
        ?>
        <div class="my-quizzes">
            <h2>My Enrolled Quizzes</h2>
            
            <?php
            $enrollments = new WP_Query([
                'post_type' => 'enrollment',
                'author' => $user->ID,
                'posts_per_page' => -1,
            ]);
            
            if ($enrollments->have_posts()) {
                echo '<div class="quiz-list">';
                while ($enrollments->have_posts()) {
                    $enrollments->the_post();
                    $quiz_id = get_post_meta(get_the_ID(), 'quiz_id', true);
                    $quiz = get_post($quiz_id);
                    
                    if ($quiz) {
                        $this->render_enrolled_quiz($quiz, get_the_ID());
                    }
                }
                echo '</div>';
                wp_reset_postdata();
            } else {
                echo '<p>No quizzes enrolled yet. <a href="' . site_url('/quizzes') . '">Browse quizzes</a></p>';
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render enrolled quiz item
     */
    private function render_enrolled_quiz($quiz, $enrollment_id) {
        $status = get_post_meta($enrollment_id, 'status', true);
        $attempts = get_post_meta($enrollment_id, 'attempts_count', true);
        $max_attempts = get_post_meta($quiz->ID, '_quiz_max_attempts', true);
        ?>
        <div class="enrolled-quiz">
            <div class="quiz-info">
                <h3><a href="<?php the_permalink($quiz->ID); ?>"><?php echo get_the_title($quiz->ID); ?></a></h3>
                <p class="quiz-meta">
                    <span class="quiz-status">Status: <?php echo ucfirst($status); ?></span>
                    <span class="quiz-attempts">Attempts: <?php echo $attempts; ?>/<?php echo $max_attempts; ?></span>
                </p>
            </div>
            
            <div class="quiz-actions">
                <?php if ($status === 'enrolled' && $attempts < $max_attempts) : ?>
                    <a href="<?php the_permalink($quiz->ID); ?>" class="btn btn-primary">Start Quiz</a>
                <?php elseif ($status === 'completed') : ?>
                    <a href="<?php echo site_url('/quiz-results/' . $quiz->ID); ?>" class="btn btn-secondary">View Results</a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Register payment status taxonomy
     */
    public function register_payment_status_taxonomy() {
        register_taxonomy('payment_status', 'enrollment', [
            'labels' => [
                'name' => 'Payment Status',
                'singular_name' => 'Payment Status',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=quiz',
            'hierarchical' => false,
        ]);
    }
    
    /**
     * Register subscription plans
     */
    public function register_subscription_plans() {
        // This would integrate with payment gateway
        // For now, placeholder function
    }
    
    /**
     * Enqueue quiz scripts
     */
    public function enqueue_quiz_scripts() {
        wp_enqueue_style('quiz-management', get_template_directory_uri() . '/css/quiz-management.css');
        wp_enqueue_script('quiz-management', get_template_directory_uri() . '/js/quiz-management.js', ['jquery'], '1.0.0', true);
    }
}

// Initialize quiz management
new MCQHome_Quiz_Management();