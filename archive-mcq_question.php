<?php get_header(); ?>

<main>
  <div class="mcq-hub-container">
    <!-- Hero Section -->
    <section class="mcq-hero">
      <h1>MCQ Hub</h1>
      <p>Master your exams with thousands of practice questions</p>
    </section>

    <!-- Filter Section -->
    <section class="mcq-filters">
      <form class="filter-form" method="get" action="<?php echo esc_url(home_url('/questions')); ?>">
        <div class="filter-grid">
          <!-- Subject Filter -->
          <div class="filter-group">
            <label for="subject-filter">Subject</label>
            <select name="mcq_subject" id="subject-filter">
              <option value="">All Subjects</option>
              <?php
              $subjects = get_terms(['taxonomy' => 'mcq_subject', 'hide_empty' => false]);
              foreach ($subjects as $subject) {
                $selected = (get_query_var('mcq_subject') == $subject->slug) ? 'selected' : '';
                echo '<option value="' . esc_attr($subject->slug) . '" ' . $selected . '>' . esc_html($subject->name) . '</option>';
              }
              ?>
            </select>
          </div>

          <!-- Exam Filter -->
          <div class="filter-group">
            <label for="exam-filter">Exam</label>
            <select name="mcq_exam" id="exam-filter">
              <option value="">All Exams</option>
              <?php
              $exams = get_terms(['taxonomy' => 'mcq_exam', 'hide_empty' => false]);
              foreach ($exams as $exam) {
                $selected = (get_query_var('mcq_exam') == $exam->slug) ? 'selected' : '';
                echo '<option value="' . esc_attr($exam->slug) . '" ' . $selected . '>' . esc_html($exam->name) . '</option>';
              }
              ?>
            </select>
          </div>

          <!-- Topic Filter -->
          <div class="filter-group">
            <label for="topic-filter">Topic</label>
            <select name="mcq_topic" id="topic-filter">
              <option value="">All Topics</option>
              <?php
              $topics = get_terms(['taxonomy' => 'mcq_topic', 'hide_empty' => false]);
              foreach ($topics as $topic) {
                $selected = (get_query_var('mcq_topic') == $topic->slug) ? 'selected' : '';
                echo '<option value="' . esc_attr($topic->slug) . '" ' . $selected . '>' . esc_html($topic->name) . '</option>';
              }
              ?>
            </select>
          </div>

          <!-- Difficulty Filter -->
          <div class="filter-group">
            <label for="difficulty-filter">Difficulty</label>
            <select name="mcq_difficulty" id="difficulty-filter">
              <option value="">All Levels</option>
              <?php
              $difficulties = get_terms(['taxonomy' => 'mcq_difficulty', 'hide_empty' => false]);
              foreach ($difficulties as $difficulty) {
                $selected = (get_query_var('mcq_difficulty') == $difficulty->slug) ? 'selected' : '';
                echo '<option value="' . esc_attr($difficulty->slug) . '" ' . $selected . '>' . esc_html($difficulty->name) . '</option>';
              }
              ?>
            </select>
          </div>

          <button type="submit" class="filter-submit">Filter Questions</button>
        </div>
      </form>
    </section>

    <!-- Quick Stats -->
    <section class="mcq-stats">
      <div class="stats-grid">
        <div class="stat-card">
          <h3><?php echo wp_count_posts('mcq_question')->publish; ?></h3>
          <p>Total Questions</p>
        </div>
        <div class="stat-card">
          <h3><?php echo count(get_terms(['taxonomy' => 'mcq_subject', 'hide_empty' => false])); ?></h3>
          <p>Subjects</p>
        </div>
        <div class="stat-card">
          <h3><?php echo count(get_terms(['taxonomy' => 'mcq_exam', 'hide_empty' => false])); ?></h3>
          <p>Exams</p>
        </div>
        <div class="stat-card">
          <h3><?php echo count(get_terms(['taxonomy' => 'mcq_topic', 'hide_empty' => false])); ?></h3>
          <p>Topics</p>
        </div>
      </div>
    </section>

    <!-- Interactive MCQ Section -->
    <section class="interactive-mcq-section">
      <?php
      $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
      
      $args = [
        'post_type' => 'mcq_question',
        'posts_per_page' => 5,
        'paged' => $paged
      ];

      // Add taxonomy filters
      if (get_query_var('mcq_subject')) {
        $args['tax_query'][] = [
          'taxonomy' => 'mcq_subject',
          'field' => 'slug',
          'terms' => get_query_var('mcq_subject')
        ];
      }

      if (get_query_var('mcq_exam')) {
        $args['tax_query'][] = [
          'taxonomy' => 'mcq_exam',
          'field' => 'slug',
          'terms' => get_query_var('mcq_exam')
        ];
      }

      if (get_query_var('mcq_topic')) {
        $args['tax_query'][] = [
          'taxonomy' => 'mcq_topic',
          'field' => 'slug',
          'terms' => get_query_var('mcq_topic')
        ];
      }

      if (get_query_var('mcq_difficulty')) {
        $args['tax_query'][] = [
          'taxonomy' => 'mcq_difficulty',
          'field' => 'slug',
          'terms' => get_query_var('mcq_difficulty')
        ];
      }

      $questions = new WP_Query($args);

      if ($questions->have_posts()) :
        echo '<div class="interactive-questions-list">';
        while ($questions->have_posts()) : $questions->the_post();
          get_template_part('template-parts/mcq');
        endwhile;
        echo '</div>';

        // Pagination
        echo '<div class="pagination">';
        echo paginate_links([
          'total' => $questions->max_num_pages,
          'current' => $paged,
          'format' => '?paged=%#%',
          'show_all' => false,
          'end_size' => 2,
          'mid_size' => 1,
          'prev_next' => true,
          'prev_text' => __('&laquo; Previous'),
          'next_text' => __('Next &raquo;')
        ]);
        echo '</div>';

        wp_reset_postdata();
      else :
        echo '<p>No questions found. Try adjusting your filters.</p>';
      endif;
      ?>
    </section>
  </div>
</main>

<style>
.mcq-hub-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.mcq-hero {
  text-align: center;
  margin-bottom: 3rem;
}

.mcq-hero h1 {
  font-size: 2.5rem;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.mcq-hero p {
  font-size: 1.2rem;
  color: #64748b;
}

.mcq-filters {
  background: #f8fafc;
  padding: 2rem;
  border-radius: 12px;
  margin-bottom: 2rem;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  align-items: end;
}

.filter-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #374151;
}

.filter-group select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 1rem;
}

.filter-submit {
  background: #0077cc;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1rem;
  transition: background 0.3s;
}

.filter-submit:hover {
  background: #005fa3;
}

.mcq-stats {
  margin-bottom: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  text-align: center;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.stat-card h3 {
  font-size: 2rem;
  color: #0077cc;
  margin-bottom: 0.5rem;
}

.stat-card p {
  color: #64748b;
  margin: 0;
}

.questions-list {
  display: grid;
  gap: 1.5rem;
}

.pagination {
  margin-top: 2rem;
  text-align: center;
}

.pagination .page-numbers {
  display: inline-block;
  padding: 0.5rem 1rem;
  margin: 0 0.25rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  text-decoration: none;
  color: #374151;
}

.pagination .current {
  background: #0077cc;
  color: white;
  border-color: #0077cc;
}

@media (max-width: 768px) {
  .mcq-hub-container {
    padding: 1rem;
  }
  
  .filter-grid {
    grid-template-columns: 1fr;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>

<?php get_footer(); ?>