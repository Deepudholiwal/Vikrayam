<?php
// admin_login.php - Enhanced Admin Login with Database Authentication
require 'config.php';
$err = '';

// Initialize login attempt tracking
if (!isset($_SESSION['admin_login_attempts'])) {
    $_SESSION['admin_login_attempts'] = 0;
}
if (!isset($_SESSION['admin_lock_until'])) {
    $_SESSION['admin_lock_until'] = 0;
}

// Simple math captcha challenge
if (empty($_SESSION['captcha_a']) || empty($_SESSION['captcha_b'])) {
    $_SESSION['captcha_a'] = rand(1,9);
    $_SESSION['captcha_b'] = rand(1,9);
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// Retrieve remembered username
$saved_username = $_COOKIE['admin_user'] ?? '';

// Random tip/hint for admin page
$admin_tips = [
    'Remember to change default credentials before deployment.',
    'Enable HTTPS to secure your admin panel.',
    'You can restrict access by IP address in config.php.',
    'Backup your data regularly to avoid loss.',
    'Keep software up to date to patch security holes.'
];
$tip = $admin_tips[array_rand($admin_tips)];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check lockout
    if (time() < $_SESSION['admin_lock_until']) {
        $remaining = $_SESSION['admin_lock_until'] - time();
        $err = "Too many failed attempts. Please wait {$remaining} seconds before trying again.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $token = $_POST['csrf'] ?? '';
        $captcha = trim($_POST['captcha'] ?? '');
        
        // Validate CSRF token
        if (!verify_csrf($token)) {
            $err = 'Invalid form submission. Please try again.';
        } 
        // Validate captcha
        elseif ($captcha === '' || $captcha != ($_SESSION['captcha_a'] + $_SESSION['captcha_b'])) {
            $err = 'Incorrect answer to the math challenge.';
            // Regenerate challenge
            $_SESSION['captcha_a'] = rand(1,9);
            $_SESSION['captcha_b'] = rand(1,9);
        }
        // Validate credentials
        elseif (empty($username) || empty($password)) {
            $err = 'Please enter both username and password.';
        }
        else {
            // Check admin user in database
            $stmt = $pdo->prepare("SELECT id, name, password, is_admin FROM users WHERE (name = ? OR email = ?) AND is_admin = 1 LIMIT 1");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                // Success: reset counters
                $_SESSION['admin_login_attempts'] = 0;
                $_SESSION['admin_lock_until'] = 0;
                $_SESSION['admin'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                
                // Remember username if requested
                if (!empty($_POST['remember'])) {
                    setcookie('admin_user', $username, time()+60*60*24*30, '/');
                } else {
                    setcookie('admin_user', '', time()-3600, '/');
                }
                
                // Record successful login
                $log_entry = date('Y-m-d H:i:s') . " - Admin Login: " . $admin['name'] . " (ID: " . $admin['id'] . ") from " . $_SERVER['REMOTE_ADDR'] . "\n";
                @file_put_contents(__DIR__ . '/admin_log.txt', $log_entry, FILE_APPEND);
                
                // Store last login time
                $_SESSION['admin_last_login'] = date('Y-m-d H:i:s');
                
                header('Location: admin_dashboard'); 
                exit;
            } else {
                // Log failed attempt
                $log_entry = date('Y-m-d H:i:s') . " - Failed Login Attempt: " . $username . " from " . $_SERVER['REMOTE_ADDR'] . "\n";
                @file_put_contents(__DIR__ . '/admin_log.txt', $log_entry, FILE_APPEND);
                
                $_SESSION['admin_login_attempts']++;
                $attempts_left = 5 - $_SESSION['admin_login_attempts'];
                
                if ($_SESSION['admin_login_attempts'] >= 5) {
                    $_SESSION['admin_lock_until'] = time() + 300; // 5 minutes lockout
                    $err = 'Too many failed attempts. Please wait 5 minutes.';
                } else {
                    $err = "Invalid admin credentials. {$attempts_left} attempts remaining.";
                }
            }
        }
    }
}
?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login - Vikrayam</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="icon" type="image/png" href="../assets/logo.png">
  <link rel="stylesheet" href="modern.css">
  <style>
    @keyframes fadeIn { from { opacity:0; transform: translateY(10px);} to {opacity:1; transform: translateY(0);} }
    .fade-in { opacity:0; transform: translateY(10px); transition: opacity 0.6s, transform 0.6s; }
    .fade-in.visible { opacity:1; transform: translateY(0); }
    .login-gradient {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center login-gradient">
  <div class="w-full max-w-md px-4">
    <!-- Logo/Header -->
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-2xl mb-4">
        <i class="bi bi-shield-lock text-4xl text-purple-600"></i>
      </div>
      <h1 class="text-3xl font-bold text-white">Admin Panel</h1>
      <p class="text-purple-200 mt-2">Vikrayam Management</p>
    </div>
    
    <!-- Login Card -->
    <div class="auth-card shadow-2xl fade-in">
      <div class="auth-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem;">
        <h3 class="text-xl font-bold text-white text-center">
          <i class="bi bi-box-arrow-in-right"></i> Admin Login
        </h3>
      </div>
      <div class="auth-body">
        <!-- Tips -->
        <div class="mb-4 p-3 bg-purple-50 rounded-lg">
          <p class="text-xs text-purple-600">
            <i class="bi bi-lightbulb"></i> <strong>Tip:</strong> <?=htmlspecialchars($tip)?>
          </p>
        </div>
        
        <!-- Error Message -->
        <?php if($err): ?>
          <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg">
            <i class="bi bi-exclamation-circle"></i> <?=htmlspecialchars($err)?>
          </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <form method="post">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? '')?>">
          
          <!-- Captcha -->
          <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              <i class="bi bi-calculator"></i> What is <?= $_SESSION['captcha_a'] ?> + <?= $_SESSION['captcha_b'] ?>?
            </label>
            <input name="captcha" type="text" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20" placeholder="Enter your answer" required autocomplete="off">
          </div>
          
          <!-- Username -->
          <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              <i class="bi bi-person"></i> Username or Email
            </label>
            <input name="username" value="<?=htmlspecialchars($saved_username)?>" type="text" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20" placeholder="Enter admin username" required>
          </div>
          
          <!-- Password -->
          <div class="mb-4 relative">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              <i class="bi bi-key"></i> Password
            </label>
            <input name="password" id="password" type="password" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20" placeholder="Enter password" required>
            <span id="togglePwd" class="absolute right-4 top-[42px] cursor-pointer text-gray-400 hover:text-purple-600">
              <i class="bi bi-eye"></i>
            </span>
          </div>
          
          <!-- Remember & Submit -->
          <div class="flex items-center justify-between mb-4">
            <label class="flex items-center cursor-pointer">
              <input type="checkbox" name="remember" id="remember" class="w-4 h-4 text-purple-600 rounded" <?= $saved_username ? 'checked' : '' ?>>
              <span class="ml-2 text-sm text-gray-600">Remember me</span>
            </label>
          </div>
          
          <button type="submit" class="w-full btn-gradient py-3 rounded-xl font-semibold" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <i class="bi bi-box-arrow-in-right"></i> Login
          </button>
        </form>
        
        <!-- Last Login Info -->
        <?php if(!empty($_SESSION['admin_last_login'])): ?>
          <p class="text-xs text-gray-500 text-center mt-4">
            <i class="bi bi-clock-history"></i> Last login: <?=htmlspecialchars($_SESSION['admin_last_login'])?>
          </p>
        <?php endif; ?>
        
        <!-- Server Time -->
        <p class="text-xs text-gray-400 text-center mt-2">
          <i class="bi bi-clock"></i> Server time: <span id="serverTime"><?=date('Y-m-d H:i:s')?></span>
        </p>
        
        <!-- Back to Site -->
        <div class="text-center mt-4 pt-4 border-t">
          <a href="index" class="text-purple-600 hover:text-purple-800 text-sm">
            <i class="bi bi-arrow-left"></i> Back to Website
          </a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>

<script>
// Toggle password visibility
(function(){
  const toggle = document.getElementById('togglePwd');
  const pwd = document.getElementById('password');
  if(toggle && pwd){
    toggle.addEventListener('click', ()=>{
      if(pwd.type === 'password'){
        pwd.type = 'text';
        toggle.innerHTML = '<i class="bi bi-eye-slash"></i>';
      } else {
        pwd.type = 'password';
        toggle.innerHTML = '<i class="bi bi-eye"></i>';
      }
    });
  }
})();

// Fade-in animation
document.addEventListener('DOMContentLoaded', function(){
  const card = document.querySelector('.fade-in');
  if(card){
    setTimeout(() => card.classList.add('visible'), 100);
  }

  // Update server clock
  const timeEl = document.getElementById('serverTime');
  if(timeEl){
    setInterval(()=>{
      const now = new Date();
      timeEl.textContent = now.getFullYear() + '-' +
        String(now.getMonth()+1).padStart(2,'0') + '-' +
        String(now.getDate()).padStart(2,'0') + ' ' +
        String(now.getHours()).padStart(2,'0') + ':' +
        String(now.getMinutes()).padStart(2,'0') + ':' +
        String(now.getSeconds()).padStart(2,'0');
    },1000);
  }
});
</script>
