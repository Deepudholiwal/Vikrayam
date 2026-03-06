<?php
require 'config.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if (!$u || !password_verify($password, $u['password'])) {
        $err = "Invalid email or password. Please try again.";
    } else {
        $_SESSION['user_id'] = $u['id'];
        header('Location: index'); exit;
    }
}
require 'header.php';
?>
<div class="min-h-screen flex items-center justify-center py-10 px-4">
  <div class="w-full max-w-md">
    <div class="auth-card shadow-2xl">
      <div class="auth-header">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/20 mb-3">
          <i class="bi bi-box-arrow-in-right text-3xl text-white"></i>
        </div>
        <h3 class="text-2xl font-bold"><i class="bi bi-shield-lock"></i> Welcome Back</h3>
        <p class="text-white/80 text-sm mt-1">Sign in to your account</p>
      </div>
      <div class="auth-body">
        <?php if($err): ?>
          <div class="bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 mb-4 rounded-lg">
            <i class="bi bi-exclamation-circle"></i> <?=$err?>
          </div>
        <?php endif; ?>
        <form method="post" novalidate>
          <div class="mb-5">
            <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              <i class="bi bi-envelope"></i> Email Address
            </label>
            <input name="email" id="email" type="email" 
                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                   placeholder="Enter your email" required>
          </div>
          <div class="mb-5">
            <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              <i class="bi bi-key"></i> Password
            </label>
            <input name="password" id="password" type="password" 
                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                   placeholder="Enter your password" required>
          </div>
          <div class="mb-5">
            <button type="submit" class="w-full btn-gradient py-3 rounded-xl font-semibold text-lg">
              <i class="bi bi-box-arrow-in-right"></i> Sign In
            </button>
          </div>
        </form>
      </div>
      <div class="auth-footer">
        <p class="text-gray-600 dark:text-gray-400">
          Don't have an account? 
          <a href="register" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">
            Create one <i class="bi bi-arrow-right"></i>
          </a>
        </p>
        <div class="mt-3">
          <a href="forgot_password" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
            <i class="bi bi-question-circle"></i> Forgot password?
          </a>
        </div>
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
