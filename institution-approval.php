<?php
/**
 * Institution Approval System
 * Allows administrators to approve institution accounts
 */

if (!defined('ABSPATH')) {
    exit;
}

class MCQHome_Institution_Approval {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_approval_menu']);
        add_action('admin_init', [$this, 'handle_approval_actions']);
        add_filter('user_row_actions', [$this, 'add_user_row_actions'], 10, 2);
        add_action('admin_notices', [$this, 'display_approval_notices']);
        add_filter('manage_users_columns', [$this, 'add_user_columns']);
        add_action('manage_users_custom_column', [$this, 'manage_user_custom_column'], 10, 3);
        add_action('restrict_manage_users', [$this, 'add_institution_filter']);
        add_filter('pre_get_users', [$this, 'filter_users_by_approval']);
    }
    
    /**
     * Add approval menu to admin
     */
    public function add_approval_menu() {
        add_submenu_page(
            'users.php',
            'Institution Approvals',
            'Institution Approvals',
            'manage_options',
            'institution-approvals',
            [$this, 'render_approval_page']
        );
        
        // Add notice to users page if there are pending institutions
        add_action('admin_notices', [$this, 'add_pending_notice']);
    }
    
    /**
     * Add notice to admin users page about pending institutions
     */
    public function add_pending_notice() {
        if (get_current_screen()->id !== 'users') {
            return;
        }
        
        $pending_count = count(get_users([
            'meta_key' => 'institution_approval_status',
            'meta_value' => 'pending',
            'number' => -1
        ]));
        
        if ($pending_count > 0) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . sprintf('%d institution(s) are pending approval.', $pending_count) . '</strong></p>';
            echo '<p><a href="' . admin_url('users.php?page=institution-approvals') . '" class="button button-primary">Review Pending Institutions</a></p>';
            echo '</div>';
        }
    }
    
    /**
     * Render approval page
     */
    public function render_approval_page() {
        // Get pending institutions
        $pending_institutions = get_users([
            'meta_key' => 'institution_approval_status',
            'meta_value' => 'pending',
            'number' => -1
        ]);
        
        // Get approved institutions
        $approved_institutions = get_users([
            'role' => 'mcq_institution',
            'meta_key' => 'institution_approval_status',
            'meta_value' => 'approved',
            'number' => -1
        ]);
        
        // Get rejected institutions
        $rejected_institutions = get_users([
            'meta_key' => 'institution_approval_status',
            'meta_value' => 'rejected',
            'number' => -1
        ]);
        
        ?>
        <div class="wrap">
            <h1>Institution Approval Management</h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="#pending" class="nav-tab nav-tab-active">Pending (<?php echo count($pending_institutions); ?>)</a>
                <a href="#approved" class="nav-tab">Approved (<?php echo count($approved_institutions); ?>)</a>
                <a href="#rejected" class="nav-tab">Rejected (<?php echo count($rejected_institutions); ?>)</a>
            </h2>
            
            <div id="pending" class="tab-content">
                <h3>Pending Approval</h3>
                
                <?php if (empty($pending_institutions)): ?>
                    <p>No pending institution approvals.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_institutions as $user): ?>
                                <tr>
                                    <td><?php echo esc_html($user->user_login); ?></td>
                                    <td><?php echo esc_html($user->display_name); ?></td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td><?php echo date('F j, Y', strtotime($user->user_registered)); ?></td>
                                    <td>
                                        <a href="<?php echo wp_nonce_url(admin_url('users.php?page=institution-approvals&action=approve&user_id=' . $user->ID), 'approve_institution'); ?>" class="button button-primary">Approve</a>
                                        <a href="<?php echo wp_nonce_url(admin_url('users.php?page=institution-approvals&action=reject&user_id=' . $user->ID), 'reject_institution'); ?>" class="button">Reject</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div id="approved" class="tab-content" style="display: none;">
                <h3>Approved Institutions</h3>
                
                <?php if (empty($approved_institutions)): ?>
                    <p>No approved institutions.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Teachers</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($approved_institutions as $user): 
                                // Count teachers under this institution
                                $teachers_count = count(get_users([
                                    'role' => 'mcq_teacher',
                                    'meta_key' => 'institution_id',
                                    'meta_value' => $user->ID,
                                    'number' => -1
                                ]));
                            ?>
                                <tr>
                                    <td><?php echo esc_html($user->user_login); ?></td>
                                    <td><?php echo esc_html($user->display_name); ?></td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td><?php echo $teachers_count; ?></td>
                                    <td>
                                        <a href="<?php echo wp_nonce_url(admin_url('users.php?page=institution-approvals&action=revoke&user_id=' . $user->ID), 'revoke_institution'); ?>" class="button">Revoke Approval</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div id="rejected" class="tab-content" style="display: none;">
                <h3>Rejected Institutions</h3>
                
                <?php if (empty($rejected_institutions)): ?>
                    <p>No rejected institutions.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rejected_institutions as $user): ?>
                                <tr>
                                    <td><?php echo esc_html($user->user_login); ?></td>
                                    <td><?php echo esc_html($user->display_name); ?></td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td><?php echo date('F j, Y', strtotime($user->user_registered)); ?></td>
                                    <td>
                                        <a href="<?php echo wp_nonce_url(admin_url('users.php?page=institution-approvals&action=approve&user_id=' . $user->ID), 'approve_institution'); ?>" class="button button-primary">Approve</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab navigation
            $('.nav-tab-wrapper a').on('click', function(e) {
                e.preventDefault();
                
                // Update active tab
                $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Show selected tab content
                $('.tab-content').hide();
                $($(this).attr('href')).show();
            });
            
            // Check URL hash for tab selection
            if (window.location.hash) {
                $('.nav-tab-wrapper a[href="' + window.location.hash + '"]').click();
            }
        });
        </script>
        <?php
    }
    
    /**
     * Handle approval actions
     */
    public function handle_approval_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'institution-approvals') {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        
        if (!$user_id) {
            return;
        }
        
        switch ($action) {
            case 'approve':
                if (!wp_verify_nonce($_GET['_wpnonce'], 'approve_institution')) {
                    wp_die('Security check failed');
                }
                
                $this->approve_institution($user_id);
                wp_redirect(admin_url('users.php?page=institution-approvals&approved=1'));
                exit;
                
            case 'reject':
                if (!wp_verify_nonce($_GET['_wpnonce'], 'reject_institution')) {
                    wp_die('Security check failed');
                }
                
                $this->reject_institution($user_id);
                wp_redirect(admin_url('users.php?page=institution-approvals&rejected=1'));
                exit;
                
            case 'revoke':
                if (!wp_verify_nonce($_GET['_wpnonce'], 'revoke_institution')) {
                    wp_die('Security check failed');
                }
                
                $this->revoke_institution($user_id);
                wp_redirect(admin_url('users.php?page=institution-approvals&revoked=1'));
                exit;
        }
    }
    
    /**
     * Approve institution
     */
    private function approve_institution($user_id) {
        $user = new WP_User($user_id);
        
        // Set role to institution
        $user->set_role('mcq_institution');
        
        // Update approval status
        update_user_meta($user_id, 'institution_approval_status', 'approved');
        
        // Send notification email
        $subject = 'Your Institution Account Has Been Approved';
        $message = "Dear " . $user->display_name . ",\n\n";
        $message .= "Your institution account on " . get_bloginfo('name') . " has been approved.\n";
        $message .= "You can now log in and access all institution features.\n\n";
        $message .= "Login URL: " . wp_login_url() . "\n";
        $message .= "Dashboard: " . home_url('/institution-dashboard') . "\n\n";
        $message .= "Thank you for joining our platform!\n\n";
        $message .= get_bloginfo('name');
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Reject institution
     */
    private function reject_institution($user_id) {
        $user = new WP_User($user_id);
        
        // Keep as student
        $user->set_role('mcq_student');
        
        // Update approval status
        update_user_meta($user_id, 'institution_approval_status', 'rejected');
        
        // Send notification email
        $subject = 'Your Institution Account Application Status';
        $message = "Dear " . $user->display_name . ",\n\n";
        $message .= "We regret to inform you that your institution account application on " . get_bloginfo('name') . " has not been approved at this time.\n\n";
        $message .= "You can continue using the platform as a student.\n\n";
        $message .= "If you have any questions, please contact the site administrator.\n\n";
        $message .= get_bloginfo('name');
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Revoke institution approval
     */
    private function revoke_institution($user_id) {
        $user = new WP_User($user_id);
        
        // Set role to student
        $user->set_role('mcq_student');
        
        // Update approval status
        update_user_meta($user_id, 'institution_approval_status', 'rejected');
        
        // Send notification email
        $subject = 'Your Institution Account Status Has Changed';
        $message = "Dear " . $user->display_name . ",\n\n";
        $message .= "Your institution account on " . get_bloginfo('name') . " has been revoked.\n\n";
        $message .= "You can continue using the platform as a student.\n\n";
        $message .= "If you have any questions, please contact the site administrator.\n\n";
        $message .= get_bloginfo('name');
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Add user row actions
     */
    public function add_user_row_actions($actions, $user) {
        if (!current_user_can('manage_options')) {
            return $actions;
        }
        
        $approval_status = get_user_meta($user->ID, 'institution_approval_status', true);
        
        if ($approval_status === 'pending') {
            $actions['approve_institution'] = '<a href="' . wp_nonce_url(admin_url('users.php?page=institution-approvals&action=approve&user_id=' . $user->ID), 'approve_institution') . '">Approve Institution</a>';
            $actions['reject_institution'] = '<a href="' . wp_nonce_url(admin_url('users.php?page=institution-approvals&action=reject&user_id=' . $user->ID), 'reject_institution') . '">Reject Institution</a>';
        } elseif ($approval_status === 'approved' && in_array('mcq_institution', $user->roles)) {
            $actions['revoke_institution'] = '<a href="' . wp_nonce_url(admin_url('users.php?page=institution-approvals&action=revoke&user_id=' . $user->ID), 'revoke_institution') . '">Revoke Institution</a>';
        } elseif ($approval_status === 'rejected') {
            $actions['approve_institution'] = '<a href="' . wp_nonce_url(admin_url('users.php?page=institution-approvals&action=approve&user_id=' . $user->ID), 'approve_institution') . '">Approve Institution</a>';
        }
        
        return $actions;
    }
    
    /**
     * Display approval notices
     */
    public function display_approval_notices() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'institution-approvals') {
            return;
        }
        
        if (isset($_GET['approved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Institution approved successfully.</p></div>';
        }
        
        if (isset($_GET['rejected'])) {
            echo '<div class="notice notice-warning is-dismissible"><p>Institution rejected.</p></div>';
        }
        
        if (isset($_GET['revoked'])) {
            echo '<div class="notice notice-warning is-dismissible"><p>Institution approval revoked.</p></div>';
        }
    }
    
    /**
     * Add user columns
     */
    public function add_user_columns($columns) {
        $columns['institution_status'] = 'Institution Status';
        return $columns;
    }
    
    /**
     * Manage user custom column
     */
    public function manage_user_custom_column($value, $column_name, $user_id) {
        if ($column_name !== 'institution_status') {
            return $value;
        }
        
        $approval_status = get_user_meta($user_id, 'institution_approval_status', true);
        
        if (empty($approval_status)) {
            return 'N/A';
        }
        
        switch ($approval_status) {
            case 'pending':
                return '<span style="color: #f0ad4e;">Pending Approval</span>';
            case 'approved':
                return '<span style="color: #5cb85c;">Approved</span>';
            case 'rejected':
                return '<span style="color: #d9534f;">Rejected</span>';
            default:
                return 'N/A';
        }
    }
    
    /**
     * Add institution filter
     */
    public function add_institution_filter() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $status = isset($_GET['institution_status']) ? $_GET['institution_status'] : '';
        ?>
        <label for="institution-status-filter" class="screen-reader-text">Filter by institution status</label>
        <select name="institution_status" id="institution-status-filter">
            <option value="">All Institution Statuses</option>
            <option value="pending" <?php selected($status, 'pending'); ?>>Pending Approval</option>
            <option value="approved" <?php selected($status, 'approved'); ?>>Approved</option>
            <option value="rejected" <?php selected($status, 'rejected'); ?>>Rejected</option>
        </select>
        <?php
    }
    
    /**
     * Filter users by approval
     */
    public function filter_users_by_approval($query) {
        global $pagenow;
        
        if (!is_admin() || $pagenow !== 'users.php') {
            return $query;
        }
        
        if (!current_user_can('manage_options')) {
            return $query;
        }
        
        if (isset($_GET['institution_status']) && !empty($_GET['institution_status'])) {
            $status = sanitize_text_field($_GET['institution_status']);
            
            $query->query_vars['meta_key'] = 'institution_approval_status';
            $query->query_vars['meta_value'] = $status;
        }
        
        return $query;
    }
}

// Initialize the institution approval system
new MCQHome_Institution_Approval();