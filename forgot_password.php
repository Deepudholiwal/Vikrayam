<?php
require 'config.php';

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    // Verify CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        $error = 'Invalid form submission.';
    } elseif (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $insert = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $insert->execute([$user['id'], $token, $expires]);
            
            // In production, send email with reset link
            // For demo, show the link
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset_password.php?token=" . $token;
            
            $msg = 'Password reset link generated. In production, an email would be sent to your address.<br><br>';
            $msg .= '<strong>Reset Link (Demo):</strong> <a href="' . $reset_link . '" class="text-blue-600 hover:underline">' . $reset_link . '</a>';
            
            // Log for debugging (remove in production)
            error_log("Password reset for user {$user['id']}: $reset_link");
        } else {
            // Don't reveal if email exists or not
            $msg = 'If an account with that email exists, a reset link has been sent.';
        }
    }
}

require 'header.php';
?>
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-10">
  <div class="card-custom shadow-lg w-full max-w-md">
    <div class="bg-red-600 text-white text-center rounded-t-lg p-4">
      <h3 class="mb-0"><i class="bi bi-key"></i> Forgot Password</h3>
    </div>
    <div class="p-6">
      <?php if($error): ?>
        <div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4'><?=$error?></div>
      <?php endif; ?>
      
      <?php if($msg): ?>
        <div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4'><?=$msg?></div>
      <?php else: ?>
        <p class="text-gray-600 mb-4">Enter your email address and we'll send you a link to reset your password.</p>
        <form method="post" novalidate>
          <?= csrf_field() ?>
          <div class="mb-4">
            <label for="email" class="block text-sm font-semibold">Email Address <span class="text-red-500">*</span></label>
            <input name="email" id="email" type="email" class="w-full mt-1 p-2 border rounded" placeholder="Enter your email" required>
          </div>
          <div class="text-center">
            <button class="inline-block px-6 py-3 bg-red-600 text-white rounded hover:bg-red-700 transition" type="submit">
              <i class="bi bi-envelope"></i> Send Reset Link
            </button>
          </div>
        </form>
      <?php endif; ?>
      <div class="text-center mt-3">
        <small>Remember your password? <a href="login" class="text-blue-600 hover:underline">Login here</a></small>
      </div>
    </div>
  </div>
</div>
<?php require 'footer.php'; ?>
