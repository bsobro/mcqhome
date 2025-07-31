/**
 * Dashboard Tab Navigation
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Tab navigation
        const $navLinks = $('.dashboard-nav a[data-tab]');
        const $tabContents = $('.tab-content');

        // Handle tab clicks
        $navLinks.on('click', function(e) {
            e.preventDefault();
            
            const targetTab = $(this).data('tab');
            
            // Remove active class from all nav links
            $navLinks.removeClass('active');
            $(this).addClass('active');
            
            // Hide all tab contents
            $tabContents.removeClass('active');
            
            // Show target tab content
            $(`#${targetTab}-tab`).addClass('active');
            
            // Update URL hash for bookmarking
            window.location.hash = targetTab;
        });

        // Handle hash-based navigation on page load
        function handleHashNavigation() {
            const hash = window.location.hash.substring(1);
            if (hash) {
                const $targetLink = $(`.dashboard-nav a[data-tab="${hash}"]`);
                if ($targetLink.length) {
                    $targetLink.trigger('click');
                }
            }
        }

        // Initialize hash navigation
        handleHashNavigation();

        // Handle browser back/forward
        $(window).on('hashchange', handleHashNavigation);

        // Responsive navigation toggle
        $('.dashboard-nav-toggle').on('click', function() {
            $('.dashboard-sidebar').toggleClass('open');
        });

        // Close sidebar when clicking outside on mobile
        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                const $sidebar = $('.dashboard-sidebar');
                const $toggle = $('.dashboard-nav-toggle');
                
                if (!$sidebar.is(e.target) && !$toggle.is(e.target) && 
                    $sidebar.has(e.target).length === 0 && $toggle.has(e.target).length === 0) {
                    $sidebar.removeClass('open');
                }
            }
        });

        // Auto-hide sidebar on mobile after navigation
        if ($(window).width() <= 768) {
            $('.dashboard-nav a').on('click', function() {
                setTimeout(() => {
                    $('.dashboard-sidebar').removeClass('open');
                }, 300);
            });
        }

        // Smooth scroll for anchor links
        $('.dashboard-nav a[href^="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 500);
            }
        });

        // Dashboard quick actions
        $('.quick-actions .btn').on('click', function(e) {
            const href = $(this).attr('href');
            if (href && href.startsWith('#')) {
                e.preventDefault();
                const targetTab = href.substring(1);
                $(`.dashboard-nav a[data-tab="${targetTab}"]`).trigger('click');
            }
        });

        // Loading states for dynamic content
        function showLoading($element) {
            $element.addClass('loading');
            $element.append('<div class="loading-spinner"><div class="spinner"></div></div>');
        }

        function hideLoading($element) {
            $element.removeClass('loading');
            $element.find('.loading-spinner').remove();
        }

        // Refresh dashboard data
        $('.refresh-dashboard').on('click', function() {
            const $btn = $(this);
            const originalText = $btn.text();
            
            $btn.text('Refreshing...').prop('disabled', true);
            
            // Simulate data refresh
            setTimeout(() => {
                location.reload();
            }, 1000);
        });

        // Initialize tooltips
        $('[data-tooltip]').each(function() {
            const tooltip = $(this).data('tooltip');
            $(this).attr('title', tooltip);
        });

        // Handle responsive navigation
        function handleResponsive() {
            const $sidebar = $('.dashboard-sidebar');
            const $main = $('.dashboard-main');
            
            if ($(window).width() <= 768) {
                if (!$('.dashboard-nav-toggle').length) {
                    $('<button class="dashboard-nav-toggle">☰ Menu</button>').insertBefore('.dashboard-sidebar');
                }
            } else {
                $('.dashboard-nav-toggle').remove();
                $sidebar.removeClass('open');
            }
        }

        // Initialize responsive handling
        handleResponsive();
        $(window).on('resize', handleResponsive);

        // Keyboard navigation
        $(document).on('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case '1':
                        e.preventDefault();
                        $('.dashboard-nav a[data-tab="overview"]').trigger('click');
                        break;
                    case '2':
                        e.preventDefault();
                        $('.dashboard-nav a[data-tab="quizzes"]').trigger('click');
                        break;
                    case '3':
                        e.preventDefault();
                        $('.dashboard-nav a[data-tab="students"]').trigger('click');
                        break;
                }
            }
        });

        // Initialize dashboard animations
        $('.stat-card').each(function(index) {
            $(this).css('animation-delay', `${index * 0.1}s`);
            $(this).addClass('animate-in');
        });
    });

})(jQuery);

// Utility functions for dashboard
const DashboardUtils = {
    // Format numbers with commas
    formatNumber: function(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },

    // Format currency
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    },

    // Format date
    formatDate: function(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },

    // Show notification
    showNotification: function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `dashboard-notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
};

// Add CSS for animations
const dashboardStyles = `
    .animate-in {
        animation: slideInUp 0.6s ease-out forwards;
        opacity: 0;
        transform: translateY(20px);
    }
    
    @keyframes slideInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .dashboard-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 6px;
        color: white;
        font-weight: 600;
        z-index: 1000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    }
    
    .dashboard-notification.show {
        transform: translateX(0);
    }
    
    .dashboard-notification.success {
        background: #28a745;
    }
    
    .dashboard-notification.error {
        background: #dc3545;
    }
    
    .dashboard-notification.info {
        background: #007cba;
    }
    
    .dashboard-nav-toggle {
        display: none;
        background: #007cba;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 6px;
        cursor: pointer;
        margin-bottom: 15px;
    }
    
    @media (max-width: 768px) {
        .dashboard-nav-toggle {
            display: block;
        }
        
        .dashboard-sidebar {
            position: fixed;
            top: 0;
            left: -300px;
            width: 250px;
            height: 100vh;
            background: white;
            z-index: 1000;
            transition: left 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        
        .dashboard-sidebar.open {
            left: 0;
        }
        
        .dashboard-sidebar .dashboard-nav {
            box-shadow: none;
            border-radius: 0;
        }
    }
`;

// Inject styles
const styleSheet = document.createElement('style');
styleSheet.textContent = dashboardStyles;
document.head.appendChild(styleSheet);