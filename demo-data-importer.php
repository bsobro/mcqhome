<?php
/**
 * MCQ Home Demo Data Importer
 * Populates the site with realistic mock data for testing
 */

if (!defined('ABSPATH')) {
    exit;
}

class MCQHome_Demo_Data_Importer {
    
    public static function import_demo_data() {
        // Check if demo data already exists
        if (get_option('mcqhome_demo_data_imported')) {
            return false;
        }
        
        // Import all demo data
        self::import_users();
        self::import_quizzes();
        self::import_questions();
        self::import_enrollments();
        self::import_results();
        
        // Mark as imported
        update_option('mcqhome_demo_data_imported', true);
        
        return true;
    }
    
    private static function import_users() {
        // Create demo institution
        $institution_id = wp_create_user('demo_institution', 'demo123', 'institution@demo.com');
        $user = new WP_User($institution_id);
        $user->set_role('mcq_institution');
        wp_update_user([
            'ID' => $institution_id,
            'display_name' => 'Demo Educational Institute'
        ]);
        update_user_meta($institution_id, 'description', 'A premier educational institution offering comprehensive online learning programs.');
        
        // Create demo teachers
        $teachers = [
            [
                'username' => 'demo_teacher1',
                'email' => 'teacher1@demo.com',
                'name' => 'Dr. Sarah Johnson',
                'bio' => 'Mathematics professor with 10+ years of experience in online education.'
            ],
            [
                'username' => 'demo_teacher2',
                'email' => 'teacher2@demo.com',
                'name' => 'Prof. Michael Chen',
                'bio' => 'Science educator specializing in physics and chemistry.'
            ],
            [
                'username' => 'demo_teacher3',
                'email' => 'teacher3@demo.com',
                'name' => 'Ms. Emily Rodriguez',
                'bio' => 'Language arts teacher passionate about digital learning.'
            ]
        ];
        
        $teacher_ids = [];
        foreach ($teachers as $teacher) {
            $teacher_id = wp_create_user($teacher['username'], 'demo123', $teacher['email']);
            $user = new WP_User($teacher_id);
            $user->set_role('mcq_teacher');
            wp_update_user([
                'ID' => $teacher_id,
                'display_name' => $teacher['name']
            ]);
            update_user_meta($teacher_id, 'description', $teacher['bio']);
            update_user_meta($teacher_id, 'institution_id', $institution_id);
            $teacher_ids[] = $teacher_id;
        }
        
        // Create demo students
        $students = [
            ['username' => 'demo_student1', 'email' => 'student1@demo.com', 'name' => 'Alice Williams'],
            ['username' => 'demo_student2', 'email' => 'student2@demo.com', 'name' => 'Bob Martinez'],
            ['username' => 'demo_student3', 'email' => 'student3@demo.com', 'name' => 'Carol Davis'],
            ['username' => 'demo_student4', 'email' => 'student4@demo.com', 'name' => 'David Wilson'],
            ['username' => 'demo_student5', 'email' => 'student5@demo.com', 'name' => 'Emma Thompson'],
            ['username' => 'demo_student6', 'email' => 'student6@demo.com', 'name' => 'Frank Anderson'],
            ['username' => 'demo_student7', 'email' => 'student7@demo.com', 'name' => 'Grace Lee'],
            ['username' => 'demo_student8', 'email' => 'student8@demo.com', 'name' => 'Henry Brown']
        ];
        
        $student_ids = [];
        foreach ($students as $student) {
            $student_id = wp_create_user($student['username'], 'demo123', $student['email']);
            $user = new WP_User($student_id);
            $user->set_role('mcq_student');
            wp_update_user([
                'ID' => $student_id,
                'display_name' => $student['name']
            ]);
            $student_ids[] = $student_id;
        }
    }
    
    private static function import_quizzes() {
        $quizzes = [
            [
                'title' => 'Advanced Calculus - Integration Techniques',
                'description' => 'Comprehensive quiz covering advanced integration techniques including substitution, integration by parts, and partial fractions.',
                'teacher' => 'teacher1@demo.com',
                'type' => 'paid',
                'price' => 29.99,
                'duration' => 60,
                'questions' => 15,
                'subject' => 'Mathematics',
                'difficulty' => 'Advanced',
                'tags' => ['calculus', 'integration', 'mathematics', 'advanced']
            ],
            [
                'title' => 'Physics Fundamentals - Mechanics',
                'description' => 'Test your understanding of classical mechanics including Newton\'s laws, momentum, and energy conservation.',
                'teacher' => 'teacher2@demo.com',
                'type' => 'free',
                'price' => 0,
                'duration' => 45,
                'questions' => 20,
                'subject' => 'Physics',
                'difficulty' => 'Intermediate',
                'tags' => ['physics', 'mechanics', 'newton', 'energy']
            ],
            [
                'title' => 'English Literature - Shakespeare Analysis',
                'description' => 'Explore Shakespeare\'s major works with in-depth analysis questions on themes, characters, and literary devices.',
                'teacher' => 'teacher3@demo.com',
                'type' => 'paid',
                'price' => 19.99,
                'duration' => 50,
                'questions' => 12,
                'subject' => 'Literature',
                'difficulty' => 'Intermediate',
                'tags' => ['shakespeare', 'literature', 'english', 'analysis']
            ],
            [
                'title' => 'Organic Chemistry - Functional Groups',
                'description' => 'Identify and understand different organic functional groups and their chemical properties.',
                'teacher' => 'teacher2@demo.com',
                'type' => 'paid',
                'price' => 24.99,
                'duration' => 40,
                'questions' => 18,
                'subject' => 'Chemistry',
                'difficulty' => 'Advanced',
                'tags' => ['chemistry', 'organic', 'functional-groups', 'molecules']
            ],
            [
                'title' => 'Statistics - Probability Distributions',
                'description' => 'Master probability distributions including normal, binomial, and Poisson distributions with practical applications.',
                'teacher' => 'teacher1@demo.com',
                'type' => 'free',
                'price' => 0,
                'duration' => 55,
                'questions' => 16,
                'subject' => 'Statistics',
                'difficulty' => 'Intermediate',
                'tags' => ['statistics', 'probability', 'distributions', 'data']
            ],
            [
                'title' => 'Grammar Fundamentals - Parts of Speech',
                'description' => 'Strengthen your grammar foundation with comprehensive coverage of all parts of speech.',
                'teacher' => 'teacher3@demo.com',
                'type' => 'free',
                'price' => 0,
                'duration' => 30,
                'questions' => 25,
                'subject' => 'Grammar',
                'difficulty' => 'Beginner',
                'tags' => ['grammar', 'english', 'parts-of-speech', 'language']
            ]
        ];
        
        foreach ($quizzes as $quiz) {
            $teacher = get_user_by('email', $quiz['teacher']);
            
            $quiz_id = wp_insert_post([
                'post_title' => $quiz['title'],
                'post_content' => $quiz['description'],
                'post_status' => 'publish',
                'post_type' => 'quiz',
                'post_author' => $teacher->ID
            ]);
            
            update_post_meta($quiz_id, '_quiz_type', $quiz['type']);
            update_post_meta($quiz_id, '_quiz_price', $quiz['price']);
            update_post_meta($quiz_id, '_quiz_duration', $quiz['duration']);
            update_post_meta($quiz_id, '_quiz_questions', $quiz['questions']);
            update_post_meta($quiz_id, '_quiz_subject', $quiz['subject']);
            update_post_meta($quiz_id, '_quiz_difficulty', $quiz['difficulty']);
            
            // Set tags
            wp_set_object_terms($quiz_id, $quiz['tags'], 'quiz_tag');
        }
    }
    
    private static function import_questions() {
        $questions = [
            // Calculus questions
            [
                'quiz' => 'Advanced Calculus - Integration Techniques',
                'question' => 'Evaluate the integral: ∫x²eˣ dx',
                'options' => [
                    'x²eˣ - 2xeˣ + 2eˣ + C',
                    'x²eˣ + 2xeˣ + 2eˣ + C',
                    'x²eˣ - xeˣ + eˣ + C',
                    'x²eˣ + xeˣ - eˣ + C'
                ],
                'correct' => 0,
                'explanation' => 'Using integration by parts twice: ∫x²eˣ dx = x²eˣ - 2∫xeˣ dx = x²eˣ - 2(xeˣ - eˣ) + C = x²eˣ - 2xeˣ + 2eˣ + C'
            ],
            [
                'quiz' => 'Advanced Calculus - Integration Techniques',
                'question' => 'Find ∫(3x + 2)/(x² + 4x + 3) dx',
                'options' => [
                    '3/2 ln|x² + 4x + 3| - 2 ln|(x+1)/(x+3)| + C',
                    '3/2 ln|x² + 4x + 3| + 2 ln|(x+1)/(x+3)| + C',
                    '3 ln|x² + 4x + 3| - 2 ln|x+1| + C',
                    '2 ln|x² + 4x + 3| + 3 ln|(x+1)/(x+3)| + C'
                ],
                'correct' => 0,
                'explanation' => 'Factor denominator and use partial fractions: (3x+2)/[(x+1)(x+3)] = A/(x+1) + B/(x+3). Solve for A and B, then integrate.'
            ],
            
            // Physics questions
            [
                'quiz' => 'Physics Fundamentals - Mechanics',
                'question' => 'A 5 kg object experiences a net force of 20 N. What is its acceleration?',
                'options' => [
                    '4 m/s²',
                    '5 m/s²',
                    '20 m/s²',
                    '100 m/s²'
                ],
                'correct' => 0,
                'explanation' => 'Using Newton\'s second law: F = ma, so a = F/m = 20N/5kg = 4 m/s²'
            ],
            [
                'quiz' => 'Physics Fundamentals - Mechanics',
                'question' => 'What is the kinetic energy of a 2 kg object moving at 10 m/s?',
                'options' => [
                    '100 J',
                    '200 J',
                    '50 J',
                    '20 J'
                ],
                'correct' => 0,
                'explanation' => 'KE = ½mv² = ½(2 kg)(10 m/s)² = ½(2)(100) = 100 J'
            ],
            
            // Literature questions
            [
                'quiz' => 'English Literature - Shakespeare Analysis',
                'question' => 'In Hamlet, what is the significance of the line "To be, or not to be"?',
                'options' => [
                    'It represents Hamlet\'s contemplation of suicide',
                    'It refers to Hamlet\'s indecision about killing Claudius',
                    'It symbolizes the struggle between good and evil',
                    'It foreshadows Hamlet\'s eventual death'
                ],
                'correct' => 0,
                'explanation' => 'This soliloquy reflects Hamlet\'s deep philosophical questioning about life, death, and existence, particularly his consideration of suicide as an escape from life\'s troubles.'
            ],
            
            // Chemistry questions
            [
                'quiz' => 'Organic Chemistry - Functional Groups',
                'question' => 'Which functional group contains a carbon double bonded to oxygen?',
                'options' => [
                    'Carbonyl',
                    'Hydroxyl',
                    'Carboxyl',
                    'Amino'
                ],
                'correct' => 0,
                'explanation' => 'The carbonyl group (C=O) is found in aldehydes and ketones, characterized by a carbon atom double bonded to an oxygen atom.'
            ],
            
            // Statistics questions
            [
                'quiz' => 'Statistics - Probability Distributions',
                'question' => 'For a normal distribution with μ = 50 and σ = 10, what is the probability of X > 60?',
                'options' => [
                    '0.1587',
                    '0.8413',
                    '0.5',
                    '0.0228'
                ],
                'correct' => 0,
                'explanation' => 'Z = (60-50)/10 = 1. P(Z > 1) = 1 - P(Z ≤ 1) = 1 - 0.8413 = 0.1587'
            ],
            
            // Grammar questions
            [
                'quiz' => 'Grammar Fundamentals - Parts of Speech',
                'question' => 'In the sentence "The quick brown fox jumps over the lazy dog," what part of speech is "quick"?',
                'options' => [
                    'Adjective',
                    'Adverb',
                    'Noun',
                    'Verb'
                ],
                'correct' => 0,
                'explanation' => '"Quick" is an adjective that modifies the noun "fox" by describing its quality.'
            ]
        ];
        
        foreach ($questions as $question) {
            $quiz = get_page_by_title($question['quiz'], OBJECT, 'quiz');
            
            $question_id = wp_insert_post([
                'post_title' => $question['question'],
                'post_content' => $question['question'],
                'post_status' => 'publish',
                'post_type' => 'mcq_question',
                'post_parent' => $quiz->ID
            ]);
            
            update_post_meta($question_id, '_question_options', $question['options']);
            update_post_meta($question_id, '_correct_answer', $question['correct']);
            update_post_meta($question_id, '_question_explanation', $question['explanation']);
        }
    }
    
    private static function import_enrollments() {
        $students = get_users(['role' => 'mcq_student']);
        $quizzes = get_posts(['post_type' => 'quiz', 'posts_per_page' => -1]);
        
        foreach ($students as $student) {
            // Randomly enroll students in 2-4 quizzes
            $enroll_count = rand(2, 4);
            $selected_quizzes = array_rand($quizzes, min($enroll_count, count($quizzes)));
            
            if (!is_array($selected_quizzes)) {
                $selected_quizzes = [$selected_quizzes];
            }
            
            foreach ($selected_quizzes as $quiz_index) {
                $quiz = $quizzes[$quiz_index];
                
                $enrollment_id = wp_insert_post([
                    'post_title' => $student->display_name . ' - ' . $quiz->post_title,
                    'post_status' => 'publish',
                    'post_type' => 'enrollment',
                    'post_author' => $student->ID
                ]);
                
                update_post_meta($enrollment_id, 'student_id', $student->ID);
                update_post_meta($enrollment_id, 'quiz_id', $quiz->ID);
                
                // 70% chance of completion
                $status = (rand(1, 10) <= 7) ? 'completed' : 'enrolled';
                update_post_meta($enrollment_id, 'status', $status);
                
                if ($status === 'completed') {
                    $completion_date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
                    update_post_meta($enrollment_id, 'completion_date', $completion_date);
                    update_post_meta($enrollment_id, 'score', rand(70, 95));
                }
            }
        }
    }
    
    private static function import_results() {
        $enrollments = get_posts([
            'post_type' => 'enrollment',
            'meta_query' => [
                [
                    'key' => 'status',
                    'value' => 'completed',
                    'compare' => '='
                ]
            ]
        ]);
        
        foreach ($enrollments as $enrollment) {
            $student_id = get_post_meta($enrollment->ID, 'student_id', true);
            $quiz_id = get_post_meta($enrollment->ID, 'quiz_id', true);
            $score = get_post_meta($enrollment->ID, 'score', true);
            
            $result_id = wp_insert_post([
                'post_title' => 'Result: ' . get_the_title($quiz_id),
                'post_status' => 'publish',
                'post_type' => 'quiz_result',
                'post_author' => $student_id
            ]);
            
            update_post_meta($result_id, 'quiz_id', $quiz_id);
            update_post_meta($result_id, 'student_id', $student_id);
            update_post_meta($result_id, 'score', $score);
            update_post_meta($result_id, 'time_taken', rand(25, 55));
            update_post_meta($result_id, 'completion_date', get_post_meta($enrollment->ID, 'completion_date', true));
        }
    }
}

// Hook to run on theme activation
add_action('after_switch_theme', function() {
    MCQHome_Demo_Data_Importer::import_demo_data();
    
    // Add admin notice
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible"><p>MCQ Home theme activated! <a href="' . admin_url('tools.php?page=mcqhome-import-demo') . '">Click here to import demo data</a> and see the theme in action.</p></div>';
    });
});

// Add admin menu item for manual import
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Import Demo Data',
        'Import Demo Data',
        'manage_options',
        'mcqhome-import-demo',
        function() {
            if (isset($_POST['import_demo_data'])) {
                if (MCQHome_Demo_Data_Importer::import_demo_data()) {
                    echo '<div class="updated"><p>Demo data imported successfully!</p></div>';
                } else {
                    echo '<div class="error"><p>Demo data already exists or import failed.</p></div>';
                }
            }
            ?>
            <div class="wrap">
                <h1>Import Demo Data</h1>
                <p>Click the button below to import demo data including users, quizzes, questions, and enrollments.</p>
                <form method="post">
                    <?php wp_nonce_field('import_demo_data', 'demo_nonce'); ?>
                    <input type="submit" name="import_demo_data" class="button button-primary" value="Import Demo Data">
                </form>
                
                <h2>Demo Data Includes:</h2>
                <ul>
                    <li><strong>1 Institution</strong>: Demo Educational Institute</li>
                    <li><strong>3 Teachers</strong>: Each with different subject specializations</li>
                    <li><strong>8 Students</strong>: With varied enrollment patterns</li>
                    <li><strong>6 Quizzes</strong>: Covering Mathematics, Physics, Literature, Chemistry, Statistics, and Grammar</li>
                    <li><strong>Questions</strong>: 2-3 questions per quiz with detailed explanations</li>
                    <li><strong>Enrollments</strong>: Students enrolled in 2-4 quizzes each</li>
                    <li><strong>Results</strong>: Completed quizzes with scores and completion dates</li>
                </ul>
            </div>
            <?php
        }
    );
});