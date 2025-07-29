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
?>