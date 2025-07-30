/**
 * Quiz Management JavaScript
 */

(function($) {
    'use strict';

    // Quiz Management Object
    const QuizManager = {
        
        init: function() {
            this.bindEvents();
            this.setupFilters();
        },

        bindEvents: function() {
            // Quiz filtering
            $(document).on('change', '.quiz-filters select', this.handleFilterChange);
            $(document).on('submit', '.quiz-filters form', this.handleFilterSubmit);
            
            // Quiz enrollment
            $(document).on('click', '.enroll-btn', this.handleEnrollClick);
            
            // Quiz creation
            $(document).on('submit', '#create-quiz-form', this.handleCreateQuiz);
            
            // Quiz type change
            $(document).on('change', '#quiz_type', this.handleQuizTypeChange);
            
            // Search functionality
            $(document).on('input', '#quiz-search', this.handleSearch);
            
            // Mobile responsive
            this.setupMobileView();
        },

        setupFilters: function() {
            // Initialize quiz type filter
            const urlParams = new URLSearchParams(window.location.search);
            const quizType = urlParams.get('quiz_type');
            if (quizType) {
                $(`select[name="quiz_type"] option[value="${quizType}"]`).prop('selected', true);
            }
        },

        handleFilterChange: function() {
            $('.quiz-filters form').submit();
        },

        handleFilterSubmit: function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const params = new URLSearchParams();
            
            for (let [key, value] of formData.entries()) {
                if (value) {
                    params.append(key, value);
                }
            }
            
            const newUrl = window.location.pathname + '?' + params.toString();
            window.location.href = newUrl;
        },

        handleEnrollClick: function(e) {
            e.preventDefault();
            const quizId = $(this).data('quiz-id');
            
            if (!quizId) return;
            
            // Check if user is logged in
            if (!QuizManager.isUserLoggedIn()) {
                QuizManager.showLoginModal();
                return;
            }
            
            // Show enrollment modal
            QuizManager.showEnrollmentModal(quizId);
        },

        showLoginModal: function() {
            const modal = `
                <div class="modal-overlay" id="login-modal">
                    <div class="modal-content">
                        <h3>Login Required</h3>
                        <p>Please login to enroll in this quiz.</p>
                        <div class="modal-actions">
                            <a href="${mcqhome_ajax.login_url}" class="btn btn-primary">Login</a>
                            <button class="btn btn-secondary" onclick="QuizManager.closeModal()">Cancel</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modal);
            $('#login-modal').fadeIn();
        },

        showEnrollmentModal: function(quizId) {
            const quizTitle = $(`.quiz-card[data-quiz-id="${quizId}"]`).find('h3').text();
            
            const modal = `
                <div class="modal-overlay" id="enrollment-modal">
                    <div class="modal-content">
                        <h3>Enroll in Quiz</h3>
                        <p>Are you sure you want to enroll in "${quizTitle}"?</p>
                        <div class="modal-actions">
                            <button class="btn btn-primary" onclick="QuizManager.confirmEnrollment(${quizId})">Enroll Now</button>
                            <button class="btn btn-secondary" onclick="QuizManager.closeModal()">Cancel</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modal);
            $('#enrollment-modal').fadeIn();
        },

        confirmEnrollment: function(quizId) {
            const formData = new FormData();
            formData.append('action', 'enroll_in_quiz');
            formData.append('quiz_id', quizId);
            formData.append('enroll_quiz_nonce_field', $('#enroll_quiz_nonce_field').val());
            
            $.ajax({
                url: mcqhome_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        QuizManager.showSuccessMessage('Successfully enrolled!');
                        setTimeout(() => {
                            window.location.href = response.data.redirect_url;
                        }, 1500);
                    } else {
                        QuizManager.showErrorMessage(response.data);
                    }
                },
                error: function() {
                    QuizManager.showErrorMessage('An error occurred. Please try again.');
                }
            });
            
            QuizManager.closeModal();
        },

        closeModal: function() {
            $('.modal-overlay').fadeOut(function() {
                $(this).remove();
            });
        },

        handleCreateQuiz: function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_quiz');
            
            // Show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.text();
            submitBtn.text('Creating...').prop('disabled', true);
            
            $.ajax({
                url: mcqhome_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        QuizManager.showSuccessMessage('Quiz created successfully!');
                        $('#create-quiz-form')[0].reset();
                        
                        // Show edit link
                        $('#create-quiz-response').html(`
                            <div class="success">
                                Quiz created! <a href="${response.data.edit_url}">Edit Quiz</a>
                            </div>
                        `);
                    } else {
                        QuizManager.showErrorMessage(response.data);
                    }
                },
                error: function() {
                    QuizManager.showErrorMessage('An error occurred. Please try again.');
                },
                complete: function() {
                    submitBtn.text(originalText).prop('disabled', false);
                }
            });
        },

        handleQuizTypeChange: function() {
            const type = $(this).val();
            const priceField = $('#quiz_price').closest('.form-group');
            
            if (type === 'free') {
                priceField.hide();
                $('#quiz_price').val(0);
            } else {
                priceField.show();
            }
        },

        handleSearch: function() {
            const searchTerm = $(this).val().toLowerCase();
            
            $('.quiz-card').each(function() {
                const title = $(this).find('h3').text().toLowerCase();
                const description = $(this).find('.quiz-description').text().toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },

        setupMobileView: function() {
            // Responsive adjustments
            if ($(window).width() < 768) {
                $('.quiz-filters form').addClass('mobile-filters');
            }
            
            $(window).resize(function() {
                if ($(window).width() < 768) {
                    $('.quiz-filters form').addClass('mobile-filters');
                } else {
                    $('.quiz-filters form').removeClass('mobile-filters');
                }
            });
        },

        isUserLoggedIn: function() {
            return mcqhome_ajax.is_logged_in === '1';
        },

        showSuccessMessage: function(message) {
            const alert = $(`
                <div class="alert alert-success" style="display: none;">
                    ${message}
                </div>
            `);
            
            $('body').prepend(alert);
            alert.slideDown().delay(3000).slideUp(function() {
                $(this).remove();
            });
        },

        showErrorMessage: function(message) {
            const alert = $(`
                <div class="alert alert-error" style="display: none;">
                    ${message}
                </div>
            `);
            
            $('body').prepend(alert);
            alert.slideDown().delay(5000).slideUp(function() {
                $(this).remove();
            });
        },

        // Quiz progress tracking
        trackQuizProgress: function(quizId, answers) {
            const progress = {
                quizId: quizId,
                answers: answers,
                timestamp: Date.now()
            };
            
            localStorage.setItem(`quiz_progress_${quizId}`, JSON.stringify(progress));
        },

        getQuizProgress: function(quizId) {
            const saved = localStorage.getItem(`quiz_progress_${quizId}`);
            return saved ? JSON.parse(saved) : null;
        },

        clearQuizProgress: function(quizId) {
            localStorage.removeItem(`quiz_progress_${quizId}`);
        }
    };

    // Initialize quiz management
    $(document).ready(function() {
        QuizManager.init();
    });

    // Global functions for modal callbacks
    window.QuizManager = QuizManager;

})(jQuery);

// Additional utility functions
function formatPrice(price) {
    return '$' + parseFloat(price).toFixed(2);
}

function formatDuration(minutes) {
    if (minutes < 60) {
        return minutes + ' min';
    } else {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return hours + 'h ' + mins + 'm';
    }
}

// Smooth scrolling for quiz links
document.addEventListener('DOMContentLoaded', function() {
    const quizLinks = document.querySelectorAll('.quiz-card a');
    quizLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href').startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
});