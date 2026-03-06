<?php
require 'config.php';

$msg = '';
$error = '';
$token = $_GET['token'] ?? '';

// Verify token
if (empty($token)) {
    $error = 'Invalid reset token.';
} else {
    $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()");
    $stmt->execute([$token]);
    $resetToken = $stmt->fetch();
    
    if (!$resetToken) {
        $error = 'Invalid or expired reset token.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resetToken) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    // Verify CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        $error = 'Invalid form submission.';
    } elseif (empty($password)) {
        $error = 'Please enter a new password.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Validate password strength
        $passwordErrors = validate_password_strength($password);
        if (!empty($passwordErrors)) {
            $error = implode('<br>', $passwordErrors);
        } else {
            // Update password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$hash, $resetToken['user_id']]);
            
            // Mark token as used
            $used = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
            $used->execute([$resetToken['id']]);
            
            $msg = 'Password has been reset successfully! <a href="login" class="text-blue-600 hover:underline">Click here to login</a>';
            $resetToken = null; // Prevent form from showing
        }
    }
}

require 'header.php';
?>
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-10">
  <div class="card-custom shadow-lg w-full max-w-md">
    <div class="bg-blue-600 text-white text-center rounded-t-lg p-4">
      <h3 class="mb-0"><i class="bi bi-key"></i> Reset Password</h3>
    </div>
    <div class="p-6">
      <?php if($error): ?>
        <div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4'><?=$error?></div>
      <?php endif; ?>
      
      <?php if($msg): ?>
        <div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4'><?=$msg?></div>
      <?php elseif($resetToken): ?>
        <p class="text-gray-600 mb-4">Enter your new password below.</p>
        <form method="post" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
          <div class="mb-4">
            <label for="password" class="block text-sm font-semibold">New Password <span class="text-red-500">*</span></label>
            <input name="password" id="password" type="password" class="w-full mt-1 p-2 border rounded" placeholder="Enter new password" required minlength="8">
            <small class="text-gray-500">Min 8 chars with uppercase, lowercase, number, special char</small>
          </div>
          <div class="mb-4">
            <label for="confirm_password" class="block text-sm font-semibold">Confirm Password <span class="text-red-500">*</span></label>
            <input name="confirm_password" id="confirm_password" type="password" class="w-full mt-1 p-2 border rounded" placeholder="Confirm new password" required>
          </div>
          <div class="text-center">
            <button class="inline-block px-6 py-3 bg-blue-600 text-white rounded hover:bg-blue-700 transition" type="submit">
              <i class="bi bi-check-circle"></i> Reset Password
            </button>
          </div>
        </form>
      <?php endif; ?>
      <div class="text-center mt-3">
        <small><a href="login" class="text-blue-600 hover:underline">Back to Login</a></small>
      </div>
    </div>
  </div>
</div>
<?php require 'footer.php'; ?>
