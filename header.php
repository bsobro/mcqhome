<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<header class="site-header">
  <div class="header-container">
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" aria-label="Toggle mobile menu">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <!-- Site Branding -->
    <div class="site-branding">
      <h1 class="site-title">
        <a href="<?php echo home_url(); ?>" rel="home">
          <span class="logo-text">MCQ Home</span>
          <span class="logo-tagline">Master Your Knowledge</span>
        </a>
      </h1>
    </div>

    <!-- Primary Navigation -->
    <nav class="main-navigation" role="navigation">
      <?php
      wp_nav_menu([
        'theme_location' => 'primary',
        'menu_class' => 'nav-menu',
        'container' => false,
        'fallback_cb' => 'mcqhome_fallback_menu'
      ]);
      ?>
    </nav>

    <!-- Search Bar -->
    <div class="header-search">
      <form role="search" method="get" action="<?php echo home_url('/'); ?>">
        <label for="header-search" class="screen-reader-text">Search Questions</label>
        <input type="search" 
               id="header-search" 
               name="s" 
               placeholder="Search questions..."
               value="<?php echo get_search_query(); ?>"
               class="search-input">
        <input type="hidden" name="post_type" value="mcq_question">
        <button type="submit" class="search-submit" aria-label="Search">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
        </button>
      </form>
    </div>

    <!-- User Account Section -->
    <div class="user-account">
      <?php if (is_user_logged_in()) : ?>
        <div class="user-menu">
          <button class="user-menu-toggle" aria-label="User menu">
            <span class="user-avatar">
              <?php echo get_avatar(get_current_user_id(), 32); ?>
            </span>
            <span class="user-name">
              <?php echo wp_get_current_user()->display_name; ?>
            </span>
            <svg class="dropdown-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6,9 12,15 18,9"></polyline>
            </svg>
          </button>
          <div class="user-dropdown">
            <a href="<?php echo home_url('/my-dashboard'); ?>" class="dropdown-item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              My Dashboard
            </a>
            <a href="<?php echo home_url('/my-achievements'); ?>" class="dropdown-item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="8" r="7"></circle>
                <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
              </svg>
              Achievements
            </a>
            <a href="<?php echo home_url('/leaderboard'); ?>" class="dropdown-item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9l6 6 6-6"></path>
              </svg>
              Leaderboard
            </a>
            <div class="dropdown-divider"></div>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="dropdown-item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16,17 21,12 16,7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
              </svg>
              Logout
            </a>
          </div>
        </div>
      <?php else : ?>
        <div class="auth-links">
          <a href="<?php echo wp_login_url(get_permalink()); ?>" class="login-link">Login</a>
          <a href="<?php echo wp_registration_url(); ?>" class="register-link">Register</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</header>

<!-- Mobile Menu -->
<div class="mobile-menu">
  <div class="mobile-menu-header">
    <h2>Menu</h2>
    <button class="mobile-menu-close" aria-label="Close menu">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>
  </div>
  <nav class="mobile-navigation">
    <?php
    wp_nav_menu([
      'theme_location' => 'primary',
      'menu_class' => 'mobile-nav-menu',
      'container' => false,
      'fallback_cb' => 'mcqhome_fallback_mobile_menu'
    ]);
    ?>
  </nav>
  <div class="mobile-search">
    <form role="search" method="get" action="<?php echo home_url('/'); ?>">
      <input type="search" 
             name="s" 
             placeholder="Search questions..."
             value="<?php echo get_search_query(); ?>"
             class="mobile-search-input">
      <input type="hidden" name="post_type" value="mcq_question">
      <button type="submit" class="mobile-search-submit">Search</button>
    </form>
  </div>
  <div class="mobile-auth">
    <?php if (is_user_logged_in()) : ?>
      <a href="<?php echo home_url('/my-dashboard'); ?>" class="mobile-user-link">My Dashboard</a>
      <a href="<?php echo wp_logout_url(home_url()); ?>" class="mobile-logout-link">Logout</a>
    <?php else : ?>
      <a href="<?php echo wp_login_url(get_permalink()); ?>" class="mobile-login-link">Login</a>
      <a href="<?php echo wp_registration_url(); ?>" class="mobile-register-link">Register</a>
    <?php endif; ?>
  </div>
</div>

<!-- Page Header -->
<div class="page-header">
  <div class="container">
    <?php if (is_page()) : ?>
      <h1 class="page-title"><?php the_title(); ?></h1>
    <?php elseif (is_archive()) : ?>
      <h1 class="page-title"><?php the_archive_title(); ?></h1>
      <?php if (get_the_archive_description()) : ?>
        <p class="page-description"><?php the_archive_description(); ?></p>
      <?php endif; ?>
    <?php elseif (is_search()) : ?>
      <h1 class="page-title">Search Results for: <?php echo get_search_query(); ?></h1>
    <?php endif; ?>
  </div>
</div>