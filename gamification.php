<?php
/**
 * Gamification Features for MCQ Hub
 * Phase 2: Badges, Points & Leaderboards
 */

// Initialize gamification system
function mcqhome_init_gamification() {
    // Add points system
    add_option('mcqhome_points_correct', 10);
    add_option('mcqhome_points_incorrect', 2);
    add_option('mcqhome_points_streak', 5);
    
    // Create badges table
    global $wpdb;
    $table_name = $wpdb->prefix . 'mcq_badges';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        icon varchar(255),
        condition_type varchar(50),
        condition_value int DEFAULT 0,
        points int DEFAULT 0,
        rarity varchar(20) DEFAULT 'common',
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Create user badges table
    $table_name = $wpdb->prefix . 'mcq_user_badges';
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        badge_id bigint(20) NOT NULL,
        earned_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_user_badge (user_id, badge_id)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Create leaderboard table
    $table_name = $wpdb->prefix . 'mcq_leaderboard';
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        total_points int DEFAULT 0,
        correct_answers int DEFAULT 0,
        streak int DEFAULT 0,
        last_activity datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_user (user_id)
    ) $charset_collate;";
    
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'mcqhome_init_gamification');

// Award points for answers
function mcqhome_award_points($user_id, $question_id, $is_correct) {
    $points_correct = get_option('mcqhome_points_correct', 10);
    $points_incorrect = get_option('mcqhome_points_incorrect', 2);
    
    $points = $is_correct ? $points_correct : $points_incorrect;
    
    // Update leaderboard
    global $wpdb;
    $table_name = $wpdb->prefix . 'mcq_leaderboard';
    
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d",
        $user_id
    ));
    
    if ($existing) {
        $new_points = $existing->total_points + $points;
        $new_correct = $is_correct ? $existing->correct_answers + 1 : $existing->correct_answers;
        $new_streak = $is_correct ? $existing->streak + 1 : 0;
        
        $wpdb->update($table_name, [
            'total_points' => $new_points,
            'correct_answers' => $new_correct,
            'streak' => $new_streak,
            'last_activity' => current_time('mysql')
        ], ['user_id' => $user_id]);
    } else {
        $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'total_points' => $points,
            'correct_answers' => $is_correct ? 1 : 0,
            'streak' => $is_correct ? 1 : 0,
            'last_activity' => current_time('mysql')
        ]);
    }
    
    // Check for badges
    mcqhome_check_badges($user_id);
    
    return $points;
}

// Create default badges
function mcqhome_create_default_badges() {
    $badges = [
        [
            'name' => 'First Steps',
            'description' => 'Answer your first question',
            'icon' => '🎯',
            'condition_type' => 'total_questions',
            'condition_value' => 1,
            'points' => 50,
            'rarity' => 'common'
        ],
        [
            'name' => 'Quick Learner',
            'description' => 'Answer 10 questions correctly',
            'icon' => '🧠',
            'condition_type' => 'correct_answers',
            'condition_value' => 10,
            'points' => 100,
            'rarity' => 'common'
        ],
        [
            'name' => 'Subject Expert',
            'description' => 'Get 50% accuracy in any subject',
            'icon' => '📚',
            'condition_type' => 'subject_accuracy',
            'condition_value' => 50,
            'points' => 150,
            'rarity' => 'rare'
        ],
        [
            'name' => 'Perfect Score',
            'description' => 'Answer 20 questions in a row correctly',
            'icon' => '🔥',
            'condition_type' => 'streak',
            'condition_value' => 20,
            'points' => 300,
            'rarity' => 'epic'
        ],
        [
            'name' => 'Master Mind',
            'description' => 'Reach 1000 total points',
            'icon' => '👑',
            'condition_type' => 'total_points',
            'condition_value' => 1000,
            'points' => 500,
            'rarity' => 'legendary'
        ]
    ];
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'mcq_badges';
    
    foreach ($badges as $badge) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE name = %s",
            $badge['name']
        ));
        
        if (!$existing) {
            $wpdb->insert($table_name, $badge);
        }
    }
}
add_action('init', 'mcqhome_create_default_badges');

// Check and award badges
function mcqhome_check_badges($user_id) {
    global $wpdb;
    
    // Get user stats
    $leaderboard = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mcq_leaderboard WHERE user_id = %d",
        $user_id
    ));
    
    if (!$leaderboard) return;
    
    // Get all badges
    $badges = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mcq_badges");
    
    foreach ($badges as $badge) {
        $earned = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}mcq_user_badges WHERE user_id = %d AND badge_id = %d",
            $user_id, $badge->id
        ));
        
        if ($earned) continue;
        
        $should_award = false;
        
        switch ($badge->condition_type) {
            case 'total_questions':
                $total = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}mcq_user_progress WHERE user_id = %d",
                    $user_id
                ));
                $should_award = $total >= $badge->condition_value;
                break;
                
            case 'correct_answers':
                $should_award = $leaderboard->correct_answers >= $badge->condition_value;
                break;
                
            case 'total_points':
                $should_award = $leaderboard->total_points >= $badge->condition_value;
                break;
                
            case 'streak':
                $should_award = $leaderboard->streak >= $badge->condition_value;
                break;
                
            case 'subject_accuracy':
                $subjects = mcqhome_get_subject_progress($user_id);
                foreach ($subjects as $subject) {
                    $accuracy = $subject->total > 0 ? ($subject->correct / $subject->total) * 100 : 0;
                    if ($accuracy >= $badge->condition_value) {
                        $should_award = true;
                        break;
                    }
                }
                break;
        }
        
        if ($should_award) {
            $wpdb->insert($wpdb->prefix . 'mcq_user_badges', [
                'user_id' => $user_id,
                'badge_id' => $badge->id,
                'earned_date' => current_time('mysql')
            ]);
            
            // Award bonus points
            $wpdb->update(
                $wpdb->prefix . 'mcq_leaderboard',
                ['total_points' => $leaderboard->total_points + $badge->points],
                ['user_id' => $user_id]
            );
        }
    }
}

// Get user badges
function mcqhome_get_user_badges($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) return [];
    
    global $wpdb;
    
    $badges = $wpdb->get_results($wpdb->prepare("
        SELECT b.*, ub.earned_date
        FROM {$wpdb->prefix}mcq_user_badges ub
        JOIN {$wpdb->prefix}mcq_badges b ON ub.badge_id = b.id
        WHERE ub.user_id = %d
        ORDER BY ub.earned_date DESC
    ", $user_id));
    
    return $badges;
}

// Get leaderboard
function mcqhome_get_leaderboard($limit = 10) {
    global $wpdb;
    
    $leaderboard = $wpdb->get_results("
        SELECT l.*, u.display_name, u.user_nicename
        FROM {$wpdb->prefix}mcq_leaderboard l
        JOIN {$wpdb->users} u ON l.user_id = u.ID
        ORDER BY l.total_points DESC, l.correct_answers DESC
        LIMIT $limit
    ");
    
    return $leaderboard;
}

// Display leaderboard shortcode
function mcqhome_leaderboard_shortcode() {
    $leaderboard = mcqhome_get_leaderboard(10);
    
    ob_start();
    ?>
    <div class="mcq-leaderboard">
        <h2>🏆 Leaderboard</h2>
        
        <?php if ($leaderboard): ?>
        <div class="leaderboard-list">
            <?php foreach ($leaderboard as $index => $user): ?>
            <div class="leaderboard-item rank-<?php echo $index + 1; ?>">
                <div class="rank">#<?php echo $index + 1; ?></div>
                <div class="user-info">
                    <strong><?php echo esc_html($user->display_name); ?></strong>
                    <div class="stats">
                        <span><?php echo $user->total_points; ?> points</span>
                        <span><?php echo $user->correct_answers; ?> correct</span>
                        <?php if ($user->streak > 0): ?>
                        <span class="streak">🔥 <?php echo $user->streak; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p>No leaderboard data available yet.</p>
        <?php endif; ?>
    </div>
    
    <style>
    .mcq-leaderboard {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .leaderboard-item {
        display: flex;
        align-items: center;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 8px;
        background: #f8f9fa;
    }
    
    .leaderboard-item.rank-1 {
        background: linear-gradient(135deg, #ffd700, #ffed4e);
        font-weight: bold;
    }
    
    .leaderboard-item.rank-2 {
        background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
    }
    
    .leaderboard-item.rank-3 {
        background: linear-gradient(135deg, #cd7f32, #daa520);
    }
    
    .rank {
        font-size: 24px;
        font-weight: bold;
        margin-right: 20px;
        min-width: 50px;
    }
    
    .user-info strong {
        display: block;
        margin-bottom: 5px;
    }
    
    .stats span {
        margin-right: 15px;
        font-size: 14px;
        color: #666;
    }
    
    .streak {
        color: #ff6b6b;
        font-weight: bold;
    }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('mcq_leaderboard', 'mcqhome_leaderboard_shortcode');

// Display user badges shortcode
function mcqhome_user_badges_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to view your badges.</p>';
    }
    
    $badges = mcqhome_get_user_badges();
    
    ob_start();
    ?>
    <div class="user-badges">
        <h2>🏅 Your Achievements</h2>
        
        <?php if ($badges): ?>
        <div class="badges-grid">
            <?php foreach ($badges as $badge): ?>
            <div class="badge-item rarity-<?php echo $badge->rarity; ?>">
                <div class="badge-icon"><?php echo $badge->icon; ?></div>
                <h3><?php echo esc_html($badge->name); ?></h3>
                <p><?php echo esc_html($badge->description); ?></p>
                <small>Earned: <?php echo date('M j, Y', strtotime($badge->earned_date)); ?></small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p>No badges earned yet. Start practicing to unlock achievements!</p>
        <?php endif; ?>
    </div>
    
    <style>
    .user-badges {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .badges-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .badge-item {
        text-align: center;
        padding: 20px;
        border-radius: 10px;
        border: 2px solid #e1e1e1;
        transition: transform 0.3s ease;
    }
    
    .badge-item:hover {
        transform: translateY(-5px);
    }
    
    .badge-icon {
        font-size: 48px;
        margin-bottom: 10px;
    }
    
    .badge-item h3 {
        margin: 10px 0;
        font-size: 18px;
    }
    
    .badge-item p {
        font-size: 14px;
        color: #666;
        margin-bottom: 10px;
    }
    
    .badge-item.rarity-common { border-color: #6c757d; }
    .badge-item.rarity-rare { border-color: #007bff; }
    .badge-item.rarity-epic { border-color: #6f42c1; }
    .badge-item.rarity-legendary { border-color: #fd7e14; }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('mcq_user_badges', 'mcqhome_user_badges_shortcode');