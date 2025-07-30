/**
 * MCQ Hub Header JavaScript
 * Handles mobile menu functionality
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const mobileMenu = document.querySelector('.mobile-menu');
        const mobileMenuClose = document.querySelector('.mobile-menu-close');
        const body = document.body;

        // Function to open mobile menu
        function openMobileMenu() {
            mobileMenu.classList.add('active');
            body.style.overflow = 'hidden';
        }

        // Function to close mobile menu
        function closeMobileMenu() {
            mobileMenu.classList.remove('active');
            body.style.overflow = '';
        }

        // Toggle mobile menu
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', openMobileMenu);
        }

        // Close mobile menu
        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', closeMobileMenu);
        }

        // Close mobile menu when clicking outside
        mobileMenu.addEventListener('click', function(e) {
            if (e.target === mobileMenu) {
                closeMobileMenu();
            }
        });

        // Close mobile menu when clicking on links
        const mobileLinks = document.querySelectorAll('.mobile-nav-menu a');
        mobileLinks.forEach(function(link) {
            link.addEventListener('click', closeMobileMenu);
        });

        // Close mobile menu on window resize if above mobile breakpoint
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });

        // Handle escape key to close mobile menu
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
                closeMobileMenu();
            }
        });

        // Handle search form submission in mobile menu
        const mobileSearchForm = document.querySelector('.mobile-search form');
        if (mobileSearchForm) {
            mobileSearchForm.addEventListener('submit', function(e) {
                const input = this.querySelector('input[type="search"]');
                if (!input.value.trim()) {
                    e.preventDefault();
                    input.focus();
                }
            });
        }

        // Handle search form submission in header
        const headerSearchForm = document.querySelector('.header-search form');
        if (headerSearchForm) {
            headerSearchForm.addEventListener('submit', function(e) {
                const input = this.querySelector('input[type="search"]');
                if (!input.value.trim()) {
                    e.preventDefault();
                    input.focus();
                }
            });
        }
    });

})();