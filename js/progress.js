jQuery(document).ready(function($) {
    // Track MCQ answer
    function trackMCQAnswer(questionId, isCorrect, timeSpent) {
        $.ajax({
            url: mcq_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mcq_track_answer',
                nonce: mcq_ajax.nonce,
                question_id: questionId,
                is_correct: isCorrect ? 1 : 0,
                time_spent: timeSpent || 0
            },
            success: function(response) {
                if (response.success) {
                    updateProgressDisplay(response.data.progress);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error tracking answer:', error);
            }
        });
    }

    // Update progress display
    function updateProgressDisplay(progress) {
        // Update any progress indicators on the page
        $('.progress-total').text(progress.total_answered);
        $('.progress-correct').text(progress.correct_answers);
        $('.progress-accuracy').text(progress.accuracy + '%');
        
        // Update progress bars
        $('.progress-bar-fill').css('width', progress.accuracy + '%');
    }

    // Handle MCQ option selection
    $('.mcq-option').on('click', function() {
        var $this = $(this);
        var $question = $this.closest('.mcq-question');
        var questionId = $question.data('question-id');
        var selectedOption = $this.data('option');
        var correctAnswer = $question.data('correct-answer');
        var startTime = $question.data('start-time');
        
        // Calculate time spent
        var timeSpent = startTime ? Math.round((Date.now() - startTime) / 1000) : 0;
        
        // Check if answer is correct
        var isCorrect = selectedOption === correctAnswer;
        
        // Track the answer
        trackMCQAnswer(questionId, isCorrect, timeSpent);
        
        // Visual feedback
        $this.addClass(isCorrect ? 'correct' : 'incorrect');
        
        // Disable all options
        $question.find('.mcq-option').prop('disabled', true);
        
        // Show explanation
        $question.find('.mcq-explanation').slideDown();
        
        // Highlight correct answer
        $question.find('.mcq-option[data-option="' + correctAnswer + '"]').addClass('correct');
    });

    // Initialize question timer
    $('.mcq-question').each(function() {
        $(this).data('start-time', Date.now());
    });

    // Dashboard filtering
    $('#filter-unanswered').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        
        $.ajax({
            url: mcq_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mcq_get_unanswered',
                nonce: mcq_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayUnansweredQuestions(response.data);
                }
            }
        });
    });

    // Display unanswered questions
    function displayUnansweredQuestions(questions) {
        var $container = $('#questions-container');
        $container.empty();
        
        if (questions.length === 0) {
            $container.html('<p>No unanswered questions found. Great job!</p>');
            return;
        }
        
        questions.forEach(function(question) {
            var html = '<div class="question-card">' +
                        '<h3><a href="' + question.permalink + '">' + question.title + '</a></h3>' +
                        '<p>' + question.excerpt + '</p>' +
                        '<div class="question-meta">' +
                        '<span class="subject">' + question.subject + '</span>' +
                        '<span class="difficulty">' + question.difficulty + '</span>' +
                        '</div></div>';
            $container.append(html);
        });
    }

    // Real-time progress updates
    if ($('.user-dashboard').length > 0) {
        setInterval(function() {
            $.ajax({
                url: mcq_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mcq_get_progress',
                    nonce: mcq_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateProgressDisplay(response.data);
                    }
                }
            });
        }, 30000); // Update every 30 seconds
    }
});