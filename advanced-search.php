<?php
/**
 * Advanced Search & Filtering for MCQ Hub
 * Phase 2: Enhanced Search & Filtering
 */

// Add advanced search form shortcode
function mcqhome_advanced_search_form() {
    ob_start();
    ?>
    <div class="advanced-search-form">
        <form method="get" action="<?php echo esc_url(home_url('/questions')); ?>">
            <div class="search-row">
                <div class="search-field">
                    <label for="search-keyword">Search Questions</label>
                    <input type="text" 
                           name="s" 
                           id="search-keyword" 
                           placeholder="Enter keywords..."
                           value="<?php echo get_search_query(); ?>">
                </div>
                
                <div class="search-field">
                    <label for="search-subject">Subject</label>
                    <select name="subject" id="search-subject">
                        <option value="">All Subjects</option>
                        <?php
                        $subjects = get_terms(['taxonomy' => 'mcq_subject', 'hide_empty' => false]);
                        foreach ($subjects as $subject) {
                            $selected = isset($_GET['subject']) && $_GET['subject'] == $subject->slug ? 'selected' : '';
                            echo '<option value="' . $subject->slug . '" ' . $selected . '>' . $subject->name . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="search-field">
                    <label for="search-exam">Exam</label>
                    <select name="exam" id="search-exam">
                        <option value="">All Exams</option>
                        <?php
                        $exams = get_terms(['taxonomy' => 'mcq_exam', 'hide_empty' => false]);
                        foreach ($exams as $exam) {
                            $selected = isset($_GET['exam']) && $_GET['exam'] == $exam->slug ? 'selected' : '';
                            echo '<option value="' . $exam->slug . '" ' . $selected . '>' . $exam->name . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="search-field">
                    <label for="search-difficulty">Difficulty</label>
                    <select name="difficulty" id="search-difficulty">
                        <option value="">All Levels</option>
                        <?php
                        $difficulties = get_terms(['taxonomy' => 'mcq_difficulty', 'hide_empty' => false]);
                        foreach ($difficulties as $difficulty) {
                            $selected = isset($_GET['difficulty']) && $_GET['difficulty'] == $difficulty->slug ? 'selected' : '';
                            echo '<option value="' . $difficulty->slug . '" ' . $selected . '>' . $difficulty->name . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="search-field">
                    <label for="search-year">Year</label>
                    <select name="year" id="search-year">
                        <option value="">All Years</option>
                        <?php
                        global $wpdb;
                        $years = $wpdb->get_col("
                            SELECT DISTINCT meta_value 
                            FROM $wpdb->postmeta 
                            WHERE meta_key = 'year' 
                            ORDER BY meta_value DESC
                        ");
                        foreach ($years as $year) {
                            if ($year) {
                                $selected = isset($_GET['year']) && $_GET['year'] == $year ? 'selected' : '';
                                echo '<option value="' . $year . '" ' . $selected . '>' . $year . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="search-row">
                <div class="search-field">
                    <label>
                        <input type="checkbox" name="unanswered" value="1" 
                               <?php checked(isset($_GET['unanswered']) && $_GET['unanswered']); ?>>
                        Show only unanswered questions
                    </label>
                </div>
                
                <div class="search-field">
                    <label>
                        <input type="checkbox" name="incorrect" value="1" 
                               <?php checked(isset($_GET['incorrect']) && $_GET['incorrect']); ?>>
                        Review incorrect answers
                    </label>
                </div>
            </div>
            
            <div class="search-actions">
                <button type="submit" class="btn btn-primary">Search Questions</button>
                <button type="button" class="btn btn-secondary" onclick="clearSearch()">Clear</button>
            </div>
        </form>
        
        <script>
        function clearSearch() {
            document.querySelector('.advanced-search-form form').reset();
            window.location.href = '<?php echo esc_url(home_url("/questions")); ?>';
        }
        </script>
    </div>
    
    <style>
    .advanced-search-form {
        background: #f8f9fa;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
    }
    
    .search-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .search-field label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
    }
    
    .search-field input,
    .search-field select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .search-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background: #667eea;
        color: white;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    @media (max-width: 768px) {
        .search-row {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('mcq_advanced_search', 'mcqhome_advanced_search_form');

// Modify query for advanced search
function mcqhome_modify_search_query($query) {
    if (!is_admin() && $query->is_main_query() && is_post_type_archive('mcq_question')) {
        
        // Taxonomy filters
        if (isset($_GET['subject']) && $_GET['subject']) {
            $query->set('tax_query', [
                [
                    'taxonomy' => 'mcq_subject',
                    'field' => 'slug',
                    'terms' => $_GET['subject']
                ]
            ]);
        }
        
        if (isset($_GET['exam']) && $_GET['exam']) {
            $tax_query = $query->get('tax_query') ?: [];
            $tax_query[] = [
                'taxonomy' => 'mcq_exam',
                'field' => 'slug',
                'terms' => $_GET['exam']
            ];
            $query->set('tax_query', $tax_query);
        }
        
        if (isset($_GET['difficulty']) && $_GET['difficulty']) {
            $tax_query = $query->get('tax_query') ?: [];
            $tax_query[] = [
                'taxonomy' => 'mcq_difficulty',
                'field' => 'slug',
                'terms' => $_GET['difficulty']
            ];
            $query->set('tax_query', $tax_query);
        }
        
        // Meta filters
        if (isset($_GET['year']) && $_GET['year']) {
            $meta_query = $query->get('meta_query') ?: [];
            $meta_query[] = [
                'key' => 'year',
                'value' => $_GET['year'],
                'compare' => '='
            ];
            $query->set('meta_query', $meta_query);
        }
        
        // User-specific filters
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            
            if (isset($_GET['unanswered']) && $_GET['unanswered']) {
                global $wpdb;
                $answered_questions = $wpdb->get_col($wpdb->prepare(
                    "SELECT question_id FROM {$wpdb->prefix}mcq_user_progress WHERE user_id = %d",
                    $user_id
                ));
                
                if (!empty($answered_questions)) {
                    $query->set('post__not_in', $answered_questions);
                }
            }
            
            if (isset($_GET['incorrect']) && $_GET['incorrect']) {
                global $wpdb;
                $incorrect_questions = $wpdb->get_col($wpdb->prepare(
                    "SELECT question_id FROM {$wpdb->prefix}mcq_user_progress WHERE user_id = %d AND is_correct = 0",
                    $user_id
                ));
                
                if (!empty($incorrect_questions)) {
                    $query->set('post__in', $incorrect_questions);
                } else {
                    $query->set('post__in', [0]); // No posts
                }
            }
        }
    }
}
add_action('pre_get_posts', 'mcqhome_modify_search_query');

// Add search suggestions endpoint
function mcqhome_search_suggestions() {
    $keyword = isset($_GET['keyword']) ? sanitize_text_field($_GET['keyword']) : '';
    
    if (strlen($keyword) < 2) {
        wp_send_json([]);
    }
    
    $args = [
        'post_type' => 'mcq_question',
        'posts_per_page' => 10,
        's' => $keyword,
        'post_status' => 'publish'
    ];
    
    $query = new WP_Query($args);
    $suggestions = [];
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $suggestions[] = [
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'url' => get_permalink(),
                'subject' => get_the_terms(get_the_ID(), 'mcq_subject')[0]->name ?? ''
            ];
        }
    }
    
    wp_send_json($suggestions);
}
add_action('wp_ajax_mcq_search_suggestions', 'mcqhome_search_suggestions');
add_action('wp_ajax_nopriv_mcq_search_suggestions', 'mcqhome_search_suggestions');

// Enqueue search scripts
function mcqhome_enqueue_search_scripts() {
    if (is_post_type_archive('mcq_question')) {
        wp_enqueue_script('mcq-search', get_template_directory_uri() . '/js/search.js', ['jquery'], '1.0.0', true);
        wp_localize_script('mcq-search', 'mcq_search', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mcq_nonce')
        ]);
    }
}
add_action('wp_enqueue_scripts', 'mcqhome_enqueue_search_scripts');