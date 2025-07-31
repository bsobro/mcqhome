<?php
/**
 * Registration Redirect System
 * Automatically redirects users to their appropriate dashboard after registration
 */

if (!defined('ABSPATH')) {
    exit;
}

class MCQHome_Registration_Redirect {
    
    public function __construct() {
        add_action('user_register', [$this, 'redirect_after_registration'], 10, 1);
        add_filter('registration_redirect', [$this, 'custom_registration_redirect']);
        add_action('wp_login', [$this, 'redirect_after_login'], 10, 2);
        add_filter('login_redirect', [$this, 'custom_login_redirect'], 10, 3);
    }
    
    /**
     * Redirect after registration
     */
    public function redirect_after_registration($user_id) {
        $user = get_user_by('id', $user_id);
        
        // Store redirect URL in session
        $redirect_url = $this->get_dashboard_url($user);
        
        if (!empty($redirect_url)) {
            $_SESSION['mcqhome_registration_redirect'] = $redirect_url;
        }
    }
    
    /**
     * Custom registration redirect
     */
    public function custom_registration_redirect($redirect_to) {
        if (isset($_SESSION['mcqhome_registration_redirect'])) {
            $redirect_url = $_SESSION['mcqhome_registration_redirect'];
            unset($_SESSION['mcqhome_registration_redirect']);
            return $redirect_url;
        }
        
        return $redirect_to;
    }
    
    /**
     * Redirect after login
     */
    public function redirect_after_login($user_login, $user) {
        // Only redirect on first login after registration
        $first_login = get_user_meta($user->ID, '_mcqhome_first_login', true);
        
        if (empty($first_login)) {
            update_user_meta($user->ID, '_mcqhome_first_login', '1');
        }
    }
    
    /**
     * Custom login redirect
     */
    public function custom_login_redirect($redirect_to, $requested_redirect_to, $user) {
        if (is_wp_error($user)) {
            return $redirect_to;
        }
        
        $first_login = get_user_meta($user->ID, '_mcqhome_first_login', true);
        
        // Only redirect on first login
        if (empty($first_login) || $first_login === '1') {
            $dashboard_url = $this->get_dashboard_url($user);
            
            if (!empty($dashboard_url)) {
                update_user_meta($user->ID, '_mcqhome_first_login', '0');
                return $dashboard_url;
            }
        }
        
        return $redirect_to;
    }
    
    /**
     * Get dashboard URL based on user role
     */
    private function get_dashboard_url($user) {
        // Check if user is an institution with pending approval
        if (in_array('mcq_institution', $user->roles)) {
            return home_url('/institution-dashboard');
        } elseif (in_array('mcq_teacher', $user->roles)) {
            return home_url('/teacher-dashboard');
        } elseif (in_array('mcq_student', $user->roles)) {
            // Check if this is actually a pending institution
            $approval_status = get_user_meta($user->ID, 'institution_approval_status', true);
            if ($approval_status === 'pending') {
                // Redirect to student dashboard with notice
                return add_query_arg('pending_institution', '1', home_url('/student-dashboard'));
            }
            return home_url('/student-dashboard');
        }
        
        return home_url();
    }
}

// Initialize the redirect system
new MCQHome_Registration_Redirect();

/**
 * Helper function to get dashboard URL
 */
function mcqhome_get_dashboard_url($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $user = get_user_by('id', $user_id);
    
    if (in_array('mcq_institution', $user->roles)) {
        return home_url('/institution-dashboard');
    } elseif (in_array('mcq_teacher', $user->roles)) {
        return home_url('/teacher-dashboard');
    } elseif (in_array('mcq_student', $user->roles)) {
        // Check if this is actually a pending institution
        $approval_status = get_user_meta($user_id, 'institution_approval_status', true);
        if ($approval_status === 'pending') {
            return add_query_arg('pending_institution', '1', home_url('/student-dashboard'));
        }
        return home_url('/student-dashboard');
    }
    
    return home_url();
}

/**
 * Helper function to get dashboard navigation
 */
function mcqhome_get_dashboard_navigation() {
    $user = wp_get_current_user();
    
    if (in_array('mcq_institution', $user->roles)) {
        return [
            'overview' => [
                'title' => 'Overview',
                'url' => home_url('/institution-dashboard'),
                'icon' => '📊'
            ],
            'teachers' => [
                'title' => 'Manage Teachers',
                'url' => home_url('/institution-dashboard#teachers'),
                'icon' => '👨‍🏫'
            ],
            'quizzes' => [
                'title' => 'All Quizzes',
                'url' => home_url('/institution-dashboard#quizzes'),
                'icon' => '📚'
            ],
            'students' => [
                'title' => 'All Students',
                'url' => home_url('/institution-dashboard#students'),
                'icon' => '👥'
            ],
            'analytics' => [
                'title' => 'Analytics',
                'url' => home_url('/institution-dashboard#analytics'),
                'icon' => '📈'
            ],
            'settings' => [
                'title' => 'Settings',
                'url' => home_url('/institution-dashboard#settings'),
                'icon' => '⚙️'
            ]
        ];
    } elseif (in_array('mcq_teacher', $user->roles)) {
        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'url' => home_url('/teacher-dashboard'),
                'icon' => '📊'
            ],
            'quizzes' => [
                'title' => 'My Quizzes',
                'url' => home_url('/teacher-dashboard#quizzes'),
                'icon' => '📝'
            ],
            'students' => [
                'title' => 'Students',
                'url' => home_url('/teacher-dashboard#students'),
                'icon' => '👥'
            ],
            'create' => [
                'title' => 'Create Quiz',
                'url' => home_url('/wp-admin/post-new.php?post_type=quiz'),
                'icon' => '➕'
            ],
            'profile' => [
                'title' => 'Profile',
                'url' => home_url('/wp-admin/profile.php'),
                'icon' => '👤'
            ]
        ];
    } elseif (in_array('mcq_student', $user->roles)) {
        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'url' => home_url('/student-dashboard'),
                'icon' => '📊'
            ],
            'my-quizzes' => [
                'title' => 'My Quizzes',
                'url' => home_url('/student-dashboard#my-quizzes'),
                'icon' => '📝'
            ],
            'catalog' => [
                'title' => 'Browse Quizzes',
                'url' => home_url('/quiz-catalog'),
                'icon' => '📚'
            ],
            'achievements' => [
                'title' => 'Achievements',
                'url' => home_url('/student-dashboard#achievements'),
                'icon' => '🏆'
            ],
            'profile' => [
                'title' => 'Profile',
                'url' => home_url('/wp-admin/profile.php'),
                'icon' => '👤'
            ]
        ];
    }
    
    return [];
}

/**
 * Helper function to display dashboard navigation
 */
function mcqhome_dashboard_navigation() {
    $navigation = mcqhome_get_dashboard_navigation();
    
    if (empty($navigation)) {
        return;
    }
    
    echo '<nav class="dashboard-nav">';
    echo '<ul>';
    
    foreach ($navigation as $key => $item) {
        $current = (is_page(str_replace(home_url(), '', $item['url'])) || 
                   strpos($_SERVER['REQUEST_URI'], str_replace(home_url(), '', $item['url'])) !== false) ? 'current' : '';
        
        echo '<li class="' . esc_attr($current) . '">';
        echo '<a href="' . esc_url($item['url']) . '">';
        echo '<span class="nav-icon">' . esc_html($item['icon']) . '</span>';
        echo '<span class="nav-title">' . esc_html($item['title']) . '</span>';
        echo '</a>';
        echo '</li>';
    }
    
    echo '</ul>';
    echo '</nav>';
}

// Start session for redirect functionality
add_action('init', function() {
    if (!session_id()) {
        session_start();
    }
});