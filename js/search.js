jQuery(document).ready(function($) {
    // Search suggestions
    let searchTimeout;
    
    $('#search-keyword').on('input', function() {
        const keyword = $(this).val();
        const $suggestions = $('#search-suggestions');
        
        clearTimeout(searchTimeout);
        
        if (keyword.length < 2) {
            $suggestions.empty().hide();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: mcq_search.ajax_url,
                type: 'GET',
                data: {
                    action: 'mcq_search_suggestions',
                    nonce: mcq_search.nonce,
                    keyword: keyword
                },
                success: function(response) {
                    displaySuggestions(response);
                }
            });
        }, 300);
    });
    
    function displaySuggestions(suggestions) {
        const $suggestions = $('#search-suggestions');
        
        if (suggestions.length === 0) {
            $suggestions.empty().hide();
            return;
        }
        
        let html = '<ul class="suggestions-list">';
        suggestions.forEach(function(suggestion) {
            html += '<li>' +
                   '<a href="' + suggestion.url + '">' +
                   '<strong>' + suggestion.title + '</strong>' +
                   (suggestion.subject ? ' <span>(' + suggestion.subject + ')</span>' : '') +
                   '</a>' +
                   '</li>';
        });
        html += '</ul>';
        
        $suggestions.html(html).show();
    }
    
    // Hide suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#search-keyword, #search-suggestions').length) {
            $('#search-suggestions').hide();
        }
    });
    
    // Dynamic filtering
    $('.advanced-search-form select').on('change', function() {
        submitSearch();
    });
    
    function submitSearch() {
        const form = $('.advanced-search-form form');
        const formData = form.serialize();
        
        $.ajax({
            url: form.attr('action'),
            type: 'GET',
            data: formData,
            beforeSend: function() {
                $('#questions-container').addClass('loading');
            },
            success: function(response) {
                const $newContent = $(response).find('#questions-container').html();
                $('#questions-container').html($newContent).removeClass('loading');
                
                // Update URL without page reload
                const newUrl = form.attr('action') + '?' + formData;
                history.pushState(null, null, newUrl);
            }
        });
    }
    
    // Reset filters
    $('.clear-filters').on('click', function(e) {
        e.preventDefault();
        $('.advanced-search-form select, .advanced-search-form input[type="checkbox"]').val('').prop('checked', false);
        $('.advanced-search-form input[type="text"]').val('');
        submitSearch();
    });
    
    // Quick stats
    $('.quick-stat').on('click', function() {
        const filter = $(this).data('filter');
        const value = $(this).data('value');
        
        if (filter && value) {
            $('#search-' + filter).val(value);
            submitSearch();
        }
    });
    
    // Load more questions
    let currentPage = 1;
    
    $('#load-more-questions').on('click', function() {
        const $button = $(this);
        const form = $('.advanced-search-form form');
        const formData = form.serialize() + '&paged=' + (currentPage + 1);
        
        $.ajax({
            url: form.attr('action'),
            type: 'GET',
            data: formData,
            beforeSend: function() {
                $button.text('Loading...').prop('disabled', true);
            },
            success: function(response) {
                const $newQuestions = $(response).find('.question-card');
                
                if ($newQuestions.length > 0) {
                    $('#questions-container').append($newQuestions);
                    currentPage++;
                    $button.text('Load More Questions');
                    
                    if ($newQuestions.length < 12) {
                        $button.hide();
                    }
                } else {
                    $button.text('No More Questions').hide();
                }
            }
        });
    });
    
    // Keyboard navigation for suggestions
    $('#search-keyword').on('keydown', function(e) {
        const $suggestions = $('#search-suggestions');
        const $items = $suggestions.find('li a');
        const $active = $items.filter('.active');
        
        if (e.keyCode === 40) { // Down arrow
            e.preventDefault();
            if ($active.length === 0) {
                $items.first().addClass('active');
            } else {
                $active.removeClass('active').parent().next().find('a').addClass('active');
            }
        } else if (e.keyCode === 38) { // Up arrow
            e.preventDefault();
            if ($active.length === 0) {
                $items.last().addClass('active');
            } else {
                $active.removeClass('active').parent().prev().find('a').addClass('active');
            }
        } else if (e.keyCode === 13) { // Enter
            e.preventDefault();
            if ($active.length > 0) {
                window.location.href = $active.attr('href');
            }
        }
    });
});