<?php
// header.php
require_once 'config.php';
$user = current_user($pdo);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
<title>Vikrayam</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/png" href="assets/logo.png">
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            dark: {
              bg: '#1a1a2e',
              card: '#16213e',
              text: '#eaeaea',
              border: '#2d3748'
            }
          }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="modern.css">

</head>
<body class="flex flex-col min-h-screen bg-gray-100 dark:bg-dark-bg text-gray-800 dark:text-dark-text transition-colors duration-300">
<nav class="navbar-custom py-1 md:py-2 bg-gray-200 dark:bg-dark-card shadow-sm md:shadow-lg rounded-lg mx-1 md:mx-2 my-1 md:my-2 sticky top-0 z-50">
  <div class="max-w-6xl mx-auto relative flex items-center justify-between px-2 md:px-4">
    <!-- Logo - smaller on mobile -->
<a class="flex items-center gap-2 text-lg md:text-2xl font-bold text-blue-600 hover:text-blue-700" href="index">
      <img src="assets/logo.png" alt="Vikrayam" class="h-8 w-auto">
    </a>
    
    <!-- Right side: Theme toggle + Menu toggle in same row -->
    <div class="flex items-center gap-1 md:gap-2">
      <!-- Theme Toggle Button - hidden on small mobile -->
      <button id="themeToggle" class="hidden sm:inline-flex p-1.5 md:p-2 rounded-full bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors" title="Toggle Dark Mode">
        <i class="bi bi-moon-stars dark:hidden text-lg md:text-xl"></i>
        <i class="bi bi-sun hidden dark:inline text-lg md:text-xl text-yellow-400"></i>
      </button>
      
      <!-- Mobile menu button - hidden on desktop (md and up) -->
      <button type="button" id="navToggle" class="md:hidden text-gray-700 dark:text-gray-300 focus:outline-none z-30 cursor-pointer p-1.5">
        <i class="bi bi-list text-xl" id="menuIcon"></i>
      </button>
    </div>
    
    <!-- Navigation Links -->
    <!-- On mobile: hidden by default, shown when toggled -->
    <!-- On desktop (md): always visible -->
    <div id="navLinks" class="hidden md:flex flex-col md:flex-row md:space-x-4 md:space-y-0 md:mt-0 mt-2 md:w-auto w-full bg-gray-200 dark:bg-dark-card p-2 md:p-0 rounded-md md:shadow-none z-50">
      <a class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition block w-full px-4 py-3 md:py-0 md:px-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" href="index"><i class="bi bi-house"></i> Home</a>
      <a class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition block w-full px-4 py-3 md:py-0 md:px-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" href="create_listing"><i class="bi bi-plus-circle"></i> Post Listing</a>
      <?php if($user): ?>
        <a class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition block w-full px-4 py-3 md:py-0 md:px-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" href="dashboard"><i class="bi bi-grid"></i> Dashboard</a>
        <a class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition block w-full px-4 py-3 md:py-0 md:px-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" href="favorites"><i class="bi bi-heart"></i> Favorites</a>
        <!-- User Avatar in Nav -->
        <a href="dashboard" class="flex items-center gap-2 text-blue-600 dark:text-blue-400 hover:underline px-4 py-3 md:py-0">
          <?php if(!empty($user['profile_image']) && file_exists(__DIR__.'/'.$user['profile_image'])): ?>
            <img src="<?=htmlspecialchars($user['profile_image'])?>" alt="<?=htmlspecialchars($user['name'])?>" class="w-8 h-8 rounded-full object-cover">
          <?php else: ?>
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold">
              <?=strtoupper(substr($user['name'], 0, 1))?>
            </div>
          <?php endif; ?>
          <span class="hidden md:inline"><?=htmlspecialchars($user['name'])?></span>
        </a>
      <?php else: ?>
        <a class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition block w-full px-4 py-3 md:py-0 md:px-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" href="register"><i class="bi bi-person-plus"></i> Register</a>
        <a class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition block w-full px-4 py-3 md:py-0 md:px-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" href="login"><i class="bi bi-box-arrow-in-right"></i> Login</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<?php if (!empty($_SESSION['flash'])): ?>
  <div class="max-w-6xl mx-auto mt-3 px-4">
    <div class="bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4" role="alert">
      <?=$_SESSION['flash']?>
    </div>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
<main class="flex-grow">
<?php $main_open = true; ?>

<script>
  // Theme toggle functionality
  (function() {
    const toggleBtn = document.getElementById('themeToggle');
    const html = document.documentElement;
    
    // Check for saved theme preference or default to light
    const savedTheme = localStorage.getItem('theme') || 'light';
    if (savedTheme === 'dark') {
      html.classList.add('dark');
    }
    
    if (toggleBtn) {
      toggleBtn.addEventListener('click', function() {
        html.classList.toggle('dark');
        const isDark = html.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
      });
    }
  })();

  // Mobile menu toggle
  (function() {
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');
    const navClose = document.getElementById('navClose');
    const menuIcon = document.getElementById('menuIcon');
    
    if (navToggle && navLinks) {
      navToggle.addEventListener('click', function(e) {
        e.preventDefault();
        navLinks.classList.toggle('hidden');
        // Toggle between hamburger and close icon
        if (menuIcon) {
          if (navLinks.classList.contains('hidden')) {
            menuIcon.className = 'bi bi-list text-xl';
          } else {
            menuIcon.className = 'bi bi-x-lg text-xl';
          }
        }
      });
    }
    
    if (navClose && navLinks) {
      navClose.addEventListener('click', function(e) {
        e.preventDefault();
        navLinks.classList.add('hidden');
        // Reset icon to hamburger
        if (menuIcon) {
          menuIcon.className = 'bi bi-list text-xl';
        }
      });
    }
  })();
</script>
