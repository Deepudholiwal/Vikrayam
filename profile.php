<?php
require 'config.php';
if (!is_logged_in()) {
    header('Location: login'); exit;
}
$user = current_user($pdo);

$err = '';
$msg = '';

// Handle profile picture upload
if (isset($_POST['upload_avatar']) && !empty($_FILES['avatar']['name'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        $err = 'Invalid form submission.';
    } else {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $err = 'Invalid image format. Allowed: jpg, jpeg, png, gif';
        } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
            $err = 'Image size must be less than 2MB';
        } else {
            // Delete old profile picture if exists
            if (!empty($user['profile_image']) && file_exists(__DIR__ . '/' . $user['profile_image'])) {
                unlink(__DIR__ . '/' . $user['profile_image']);
            }
            
            $filename = 'uploads/avatar_' . $user['id'] . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], __DIR__ . '/' . $filename)) {
                // Update database
                $update = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $update->execute([$filename, $user['id']]);
                $user['profile_image'] = $filename;
                $msg = 'Profile picture updated successfully!';
            } else {
                $err = 'Failed to upload image.';
            }
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Verify CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        $err = 'Invalid form submission.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($name) || empty($email)) {
            $err = 'Name and email are required.';
        } else {
            // Check if email is already taken by another user
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $user['id']]);
            if ($check->fetch()) {
                $err = 'Email already in use by another account.';
            } else {
                // Update profile
                $update = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $update->execute([$name, $email, $user['id']]);
                
                // Update session
                $_SESSION['user_id'] = $user['id'];
                $user['name'] = $name;
                $user['email'] = $email;
                
                $msg = 'Profile updated successfully!';
            }
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        $err = 'Invalid form submission.';
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $userData = $stmt->fetch();
        
        if (!password_verify($current, $userData['password'])) {
            $err = 'Current password is incorrect.';
        } elseif (empty($new) || empty($confirm)) {
            $err = 'Please fill in all password fields.';
        } elseif ($new !== $confirm) {
            $err = 'New passwords do not match.';
        } else {
            // Validate password strength
            $passwordErrors = validate_password_strength($new);
            if (!empty($passwordErrors)) {
                $err = implode('<br>', $passwordErrors);
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$hash, $user['id']]);
                $msg = 'Password changed successfully!';
            }
        }
    }
}

require 'header.php';
?>
<div class="max-w-3xl mx-auto py-10 px-4">
  <div class="card-custom shadow-lg">
    <div class="bg-blue-600 text-white text-center rounded-t-lg p-4">
      <h3 class="mb-0"><i class="bi bi-person-circle"></i> My Profile</h3>
    </div>
    <div class="p-6">
      <?php if($err): ?>
        <div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4'><?=$err?></div>
      <?php endif; ?>
      
      <?php if($msg): ?>
        <div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4'><?=$msg?></div>
      <?php endif; ?>
      
      <!-- Profile Picture Section -->
      <h4 class="text-lg font-semibold mb-4">Profile Picture</h4>
      <div class="flex items-center gap-6 mb-6 p-4 bg-gray-50 rounded-lg">
        <?php if(!empty($user['profile_image']) && file_exists(__DIR__.'/'.$user['profile_image'])): ?>
            <img src="<?=htmlspecialchars($user['profile_image'])?>" alt="Profile" class="w-24 h-24 rounded-full object-cover border-4 border-blue-500">
        <?php else: ?>
            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-4xl text-white font-bold border-4 border-white">
                <?=strtoupper(substr($user['name'], 0, 1))?>
            </div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data" class="flex-1">
            <?= csrf_field() ?>
            <input type="file" name="avatar" id="avatar" accept="image/*" class="mb-2 w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            <button type="submit" name="upload_avatar" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-sm">
                <i class="bi bi-upload"></i> Upload New Picture
            </button>
        </form>
      </div>
      
      <!-- Profile Information -->
      <h4 class="text-lg font-semibold mb-4">Profile Information</h4>
      <form method="post" class="mb-8">
        <?= csrf_field() ?>
        <div class="mb-4">
          <label class="block text-sm font-semibold" for="name">Name</label>
          <input name="name" id="name" value="<?=htmlspecialchars($user['name'])?>" class="w-full mt-1 p-2 border rounded" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-semibold" for="email">Email</label>
          <input name="email" id="email" type="email" value="<?=htmlspecialchars($user['email'])?>" class="w-full mt-1 p-2 border rounded" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-semibold">Member Since</label>
          <p class="text-gray-600 mt-1"><?=date('F j, Y', strtotime($user['created_at'] ?? 'now'))?></p>
        </div>
        <button type="submit" name="update_profile" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
          <i class="bi bi-save"></i> Save Changes
        </button>
      </form>
      
      <hr class="my-6">
      
      <!-- Change Password -->
      <h4 class="text-lg font-semibold mb-4">Change Password</h4>
      <form method="post">
        <?= csrf_field() ?>
        <div class="mb-4">
          <label class="block text-sm font-semibold" for="current_password">Current Password</label>
          <input name="current_password" id="current_password" type="password" class="w-full mt-1 p-2 border rounded" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-semibold" for="new_password">New Password</label>
          <input name="new_password" id="new_password" type="password" class="w-full mt-1 p-2 border rounded" required>
          <small class="text-gray-500">Min 8 chars with uppercase, lowercase, number, special char</small>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-semibold" for="confirm_password">Confirm New Password</label>
          <input name="confirm_password" id="confirm_password" type="password" class="w-full mt-1 p-2 border rounded" required>
        </div>
        <button type="submit" name="change_password" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
          <i class="bi bi-key"></i> Change Password
        </button>
      </form>
    </div>
  </div>
</div>
<?php require 'footer.php'; ?>
