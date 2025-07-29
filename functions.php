<?php
function mcqhome_setup() {
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  register_nav_menus([
    'main-menu' => 'Main Menu'
  ]);
}
add_action('after_setup_theme', 'mcqhome_setup');

function mcqhome_enqueue() {
  wp_enqueue_style('mcqhome-style', get_stylesheet_uri());
}
add_action('wp_enqueue_scripts', 'mcqhome_enqueue');

// Phase 1: Custom Post Type and Taxonomies for MCQ Hub
function mcqhome_register_post_types() {
  // Register MCQ Question Post Type
  register_post_type('mcq_question', [
    'labels' => [
      'name' => 'MCQ Questions',
      'singular_name' => 'MCQ Question',
      'add_new' => 'Add New Question',
      'add_new_item' => 'Add New MCQ Question',
      'edit_item' => 'Edit MCQ Question',
      'new_item' => 'New MCQ Question',
      'view_item' => 'View MCQ Question',
      'search_items' => 'Search MCQ Questions',
      'not_found' => 'No MCQ Questions found',
      'not_found_in_trash' => 'No MCQ Questions found in Trash'
    ],
    'public' => true,
    'has_archive' => true,
    'rewrite' => ['slug' => 'questions'],
    'supports' => ['title', 'editor', 'thumbnail', 'comments'],
    'menu_icon' => 'dashicons-welcome-learn-more',
    'show_in_rest' => true
  ]);
}
add_action('init', 'mcqhome_register_post_types');

function mcqhome_register_taxonomies() {
  // Register Subjects Taxonomy
  register_taxonomy('mcq_subject', ['mcq_question'], [
    'labels' => [
      'name' => 'Subjects',
      'singular_name' => 'Subject',
      'search_items' => 'Search Subjects',
      'all_items' => 'All Subjects',
      'parent_item' => 'Parent Subject',
      'parent_item_colon' => 'Parent Subject:',
      'edit_item' => 'Edit Subject',
      'update_item' => 'Update Subject',
      'add_new_item' => 'Add New Subject',
      'new_item_name' => 'New Subject Name'
    ],
    'hierarchical' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => ['slug' => 'subject'],
    'show_in_rest' => true
  ]);

  // Register Exams Taxonomy
  register_taxonomy('mcq_exam', ['mcq_question'], [
    'labels' => [
      'name' => 'Exams',
      'singular_name' => 'Exam',
      'search_items' => 'Search Exams',
      'all_items' => 'All Exams',
      'edit_item' => 'Edit Exam',
      'update_item' => 'Update Exam',
      'add_new_item' => 'Add New Exam',
      'new_item_name' => 'New Exam Name'
    ],
    'hierarchical' => false,
    'show_ui' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => ['slug' => 'exam'],
    'show_in_rest' => true
  ]);

  // Register Topics Taxonomy
  register_taxonomy('mcq_topic', ['mcq_question'], [
    'labels' => [
      'name' => 'Topics',
      'singular_name' => 'Topic',
      'search_items' => 'Search Topics',
      'all_items' => 'All Topics',
      'parent_item' => 'Parent Topic',
      'parent_item_colon' => 'Parent Topic:',
      'edit_item' => 'Edit Topic',
      'update_item' => 'Update Topic',
      'add_new_item' => 'Add New Topic',
      'new_item_name' => 'New Topic Name'
    ],
    'hierarchical' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => ['slug' => 'topic'],
    'show_in_rest' => true
  ]);

  // Register Difficulty Taxonomy
  register_taxonomy('mcq_difficulty', ['mcq_question'], [
    'labels' => [
      'name' => 'Difficulty Levels',
      'singular_name' => 'Difficulty Level',
      'search_items' => 'Search Difficulty Levels',
      'all_items' => 'All Difficulty Levels',
      'edit_item' => 'Edit Difficulty Level',
      'update_item' => 'Update Difficulty Level',
      'add_new_item' => 'Add New Difficulty Level',
      'new_item_name' => 'New Difficulty Level Name'
    ],
    'hierarchical' => false,
    'show_ui' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => ['slug' => 'difficulty'],
    'show_in_rest' => true
  ]);
}
add_action('init', 'mcqhome_register_taxonomies');

// Add custom fields for MCQ Questions
function mcqhome_add_custom_fields() {
  if( function_exists('acf_add_local_field_group') ) {
    acf_add_local_field_group([
      'key' => 'group_mcq_fields',
      'title' => 'MCQ Details',
      'fields' => [
        [
          'key' => 'field_question_text',
          'label' => 'Question Text',
          'name' => 'question_text',
          'type' => 'wysiwyg',
          'required' => 1
        ],
        [
          'key' => 'field_option_a',
          'label' => 'Option A',
          'name' => 'option_a',
          'type' => 'text',
          'required' => 1
        ],
        [
          'key' => 'field_option_b',
          'label' => 'Option B',
          'name' => 'option_b',
          'type' => 'text',
          'required' => 1
        ],
        [
          'key' => 'field_option_c',
          'label' => 'Option C',
          'name' => 'option_c',
          'type' => 'text',
          'required' => 1
        ],
        [
          'key' => 'field_option_d',
          'label' => 'Option D',
          'name' => 'option_d',
          'type' => 'text',
          'required' => 1
        ],
        [
          'key' => 'field_correct_answer',
          'label' => 'Correct Answer',
          'name' => 'correct_answer',
          'type' => 'select',
          'choices' => [
            'A' => 'A',
            'B' => 'B',
            'C' => 'C',
            'D' => 'D'
          ],
          'required' => 1
        ],
        [
          'key' => 'field_explanation',
          'label' => 'Explanation',
          'name' => 'explanation',
          'type' => 'wysiwyg'
        ],
        [
          'key' => 'field_year',
          'label' => 'Year',
          'name' => 'year',
          'type' => 'number',
          'min' => 2000,
          'max' => date('Y')
        ]
      ],
      'location' => [
        [
          [
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'mcq_question'
          ]
        ]
      ]
    ]);
  }
}
add_action('acf/init', 'mcqhome_add_custom_fields');

// Add custom rewrite rules for filtering
function mcqhome_rewrite_rules() {
  add_rewrite_rule('^questions/subject/([^/]+)/?', 'index.php?mcq_subject=$matches[1]', 'top');
  add_rewrite_rule('^questions/exam/([^/]+)/?', 'index.php?mcq_exam=$matches[1]', 'top');
  add_rewrite_rule('^questions/topic/([^/]+)/?', 'index.php?mcq_topic=$matches[1]', 'top');
  add_rewrite_rule('^questions/difficulty/([^/]+)/?', 'index.php?mcq_difficulty=$matches[1]', 'top');
}
add_action('init', 'mcqhome_rewrite_rules');

// Add query vars for filtering
function mcqhome_query_vars($vars) {
  $vars[] = 'mcq_subject';
  $vars[] = 'mcq_exam';
  $vars[] = 'mcq_topic';
  $vars[] = 'mcq_difficulty';
  return $vars;
}
add_filter('query_vars', 'mcqhome_query_vars');
?>