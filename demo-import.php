<?php
/**
 * Demo Content Import Instructions
 * 
 * To test your new MCQ Hub, follow these steps:
 * 
 * 1. Install and activate Advanced Custom Fields (ACF) plugin
 * 2. Install and activate WP ULike plugin (optional, for likes)
 * 3. Go to Tools > Import > WordPress (install WordPress Importer if needed)
 * 4. Import the following sample content manually via WordPress admin
 * 
 * Sample MCQ Question Data:
 * 
 * Question 1:
 * - Title: "Basic PHP Syntax"
 * - Question Text: "Which of the following is the correct way to start a PHP script?"
 * - Option A: "<?php"
 * - Option B: "<script>"
 * - Option C: "<?"
 * - Option D: "<?php start"
 * - Correct Answer: "A"
 * - Explanation: "The correct opening tag for PHP is <?php. While <? is also valid in some configurations, <?php is universally supported."
 * - Subject: "Programming"
 * - Exam: "PHP Basics"
 * - Topic: "Syntax"
 * - Difficulty: "Easy"
 * - Year: "2024"
 * 
 * Question 2:
 * - Title: "WordPress Loop"
 * - Question Text: "Which function is used to check if there are posts in the current WordPress query?"
 * - Option A: "have_posts()"
 * - Option B: "the_posts()"
 * - Option C: "get_posts()"
 * - Option D: "query_posts()"
 * - Correct Answer: "A"
 * - Explanation: "have_posts() is the function used within The Loop to check if there are posts to display."
 * - Subject: "WordPress"
 * - Exam: "WordPress Development"
 * - Topic: "Loop"
 * - Difficulty: "Medium"
 * - Year: "2024"
 * 
 * Question 3:
 * - Title: "CSS Box Model"
 * - Question Text: "In CSS, what does the 'margin' property control?"
 * - Option A: "Space inside the element"
 * - Option B: "Space outside the element"
 * - Option C: "Border thickness"
 * - Option D: "Content width"
 * - Correct Answer: "B"
 * - Explanation: "The margin property controls the space outside the element, creating distance between the element and its neighbors."
 * - Subject: "Web Development"
 * - Exam: "CSS Fundamentals"
 * - Topic: "Box Model"
 * - Difficulty: "Easy"
 * - Year: "2024"
 * 
 * Manual Setup Steps:
 * 1. Create the following taxonomy terms:
 *    - Subjects: Programming, WordPress, Web Development
 *    - Exams: PHP Basics, WordPress Development, CSS Fundamentals
 *    - Topics: Syntax, Loop, Box Model
 *    - Difficulty: Easy, Medium, Hard
 * 
 * 2. Create new MCQ Questions via WordPress Admin:
 *    - Go to MCQ Questions > Add New
 *    - Fill in all fields as shown above
 *    - Assign appropriate taxonomy terms
 * 
 * 3. Visit yoursite.com/questions to see the MCQ Hub!
 * 
 * 4. Visit individual questions to test the single question view
 */

// Add this to functions.php if you want to auto-create sample content
// (Only for development - remove before production)

function mcqhub_create_sample_content() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if sample content already exists
    $existing_questions = get_posts([
        'post_type' => 'mcq_question',
        'posts_per_page' => 1
    ]);
    
    if (!empty($existing_questions)) {
        return;
    }
    
    // Create sample taxonomy terms
    $subjects = ['Programming', 'WordPress', 'Web Development'];
    $exams = ['PHP Basics', 'WordPress Development', 'CSS Fundamentals'];
    $topics = ['Syntax', 'Loop', 'Box Model'];
    $difficulties = ['Easy', 'Medium', 'Hard'];
    
    foreach ($subjects as $subject) {
        wp_insert_term($subject, 'mcq_subject');
    }
    
    foreach ($exams as $exam) {
        wp_insert_term($exam, 'mcq_exam');
    }
    
    foreach ($topics as $topic) {
        wp_insert_term($topic, 'mcq_topic');
    }
    
    foreach ($difficulties as $difficulty) {
        wp_insert_term($difficulty, 'mcq_difficulty');
    }
    
    // Sample questions data
    $sample_questions = [
        [
            'title' => 'Basic PHP Syntax',
            'question_text' => 'Which of the following is the correct way to start a PHP script?',
            'option_a' => '<?php',
            'option_b' => '<script>',
            'option_c' => '<?',
            'option_d' => '<?php start',
            'correct_answer' => 'A',
            'explanation' => 'The correct opening tag for PHP is <?php. While <? is also valid in some configurations, <?php is universally supported.',
            'subject' => 'Programming',
            'exam' => 'PHP Basics',
            'topic' => 'Syntax',
            'difficulty' => 'Easy',
            'year' => '2024'
        ],
        [
            'title' => 'WordPress Loop',
            'question_text' => 'Which function is used to check if there are posts in the current WordPress query?',
            'option_a' => 'have_posts()',
            'option_b' => 'the_posts()',
            'option_c' => 'get_posts()',
            'option_d' => 'query_posts()',
            'correct_answer' => 'A',
            'explanation' => 'have_posts() is the function used within The Loop to check if there are posts to display.',
            'subject' => 'WordPress',
            'exam' => 'WordPress Development',
            'topic' => 'Loop',
            'difficulty' => 'Medium',
            'year' => '2024'
        ],
        [
            'title' => 'CSS Box Model',
            'question_text' => 'In CSS, what does the "margin" property control?',
            'option_a' => 'Space inside the element',
            'option_b' => 'Space outside the element',
            'option_c' => 'Border thickness',
            'option_d' => 'Content width',
            'correct_answer' => 'B',
            'explanation' => 'The margin property controls the space outside the element, creating distance between the element and its neighbors.',
            'subject' => 'Web Development',
            'exam' => 'CSS Fundamentals',
            'topic' => 'Box Model',
            'difficulty' => 'Easy',
            'year' => '2024'
        ]
    ];
    
    foreach ($sample_questions as $question_data) {
        $post_id = wp_insert_post([
            'post_title' => $question_data['title'],
            'post_type' => 'mcq_question',
            'post_status' => 'publish'
        ]);
        
        if (!is_wp_error($post_id)) {
            // Set custom fields
            update_field('question_text', $question_data['question_text'], $post_id);
            update_field('option_a', $question_data['option_a'], $post_id);
            update_field('option_b', $question_data['option_b'], $post_id);
            update_field('option_c', $question_data['option_c'], $post_id);
            update_field('option_d', $question_data['option_d'], $post_id);
            update_field('correct_answer', $question_data['correct_answer'], $post_id);
            update_field('explanation', $question_data['explanation'], $post_id);
            update_field('year', $question_data['year'], $post_id);
            
            // Set taxonomy terms
            wp_set_object_terms($post_id, $question_data['subject'], 'mcq_subject');
            wp_set_object_terms($post_id, $question_data['exam'], 'mcq_exam');
            wp_set_object_terms($post_id, $question_data['topic'], 'mcq_topic');
            wp_set_object_terms($post_id, $question_data['difficulty'], 'mcq_difficulty');
        }
    }
}

// Uncomment the line below to enable auto-creation (only for development)
// add_action('init', 'mcqhub_create_sample_content');
?>