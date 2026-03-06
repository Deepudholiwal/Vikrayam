<?php
require 'config.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || !$password) {
        $err = "Please fill in all required fields.";
    } elseif (strlen($password) < 6) {
        $err = "Password must be at least 6 characters long.";
    } else {
        $exists = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $exists->execute([$email]);
        if ($exists->fetch()) {
            $err = "This email is already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password) VALUES (?,?,?)");
            $stmt->execute([$name,$email,$hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            header('Location: index'); exit;
        }
    }
}
require 'header.php';
?>
<div class="min-h-screen flex items-center justify-center py-10 px-4">
  <div class="w-full max-w-md">
    <div class="auth-card shadow-2xl">
      <div class="auth-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/20 mb-3">
          <i class="bi bi-person-plus text-3xl text-white"></i>
        </div>
        <h3 class="text-2xl font-bold"><i class="bi bi-shield-check"></i> Create Account</h3>
        <p class="text-white/80 text-sm mt-1">Join our community today</p>
        </div>
      <div class="auth-body">
        <?php if($err): ?>
          <div class="bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 mb-4 rounded-lg">
            <i class="bi bi-exclamation-circle"></i> <?=$err?>
          </div>
        <?php endif; ?>
        <form method="post" novalidate>
          <div class="mb-5">
            <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              <i class="bi bi-person"></i> Full Name
            </label>
            <input name="name" id="name" type="text" 
                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all"
                   placeholder="Enter your full name" required>
          </div>
          <div class="mb-5">
            <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              <i class="bi bi-envelope"></i> Email Address
            </label>
            <input name="email" id="email" type="email" 
                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all"
                   placeholder="Enter your email" required>
          </div>
          <div class="mb-5">
            <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              <i class="bi bi-key"></i> Password
            </label>
            <input name="password" id="password" type="password" 
                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all"
                   placeholder="Create a password" required minlength="6">
            <p class="text-xs text-gray-500 mt-1"><i class="bi bi-info-circle"></i> Must be at least 6 characters</p>
          </div>
          <div class="mb-5">
            <button type="submit" class="w-full btn-gradient btn-gradient-green py-3 rounded-xl font-semibold text-lg">
              <i class="bi bi-check-circle"></i> Create Account
            </button>
          </div>
        </form>
      </div>
      <div class="auth-footer">
        <p class="text-gray-600 dark:text-gray-400">
          Already have an account? 
          <a href="login" class="text-green-600 dark:text-green-400 font-semibold hover:underline">
            Sign in <i class="bi bi-arrow-right"></i>
          </a>
        </p>
      </div>
    </div>
  </div>
</div>
<script>
  (function () {
    'use strict'
    const forms = document.querySelectorAll('form')
    Array.prototype.slice.call(forms).forEach(function(form) {
      form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        form.classList.add('was-validated')
      }, false)
    })
  })()
</script>
<?php require 'footer.php'; ?>
