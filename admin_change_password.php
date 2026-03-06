<?php
require 'config.php';
if (empty($_SESSION['admin'])) { header('Location: admin_login'); exit; }
// get user id
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: admin_users'); exit;
}
// fetch user
$stmt = $pdo->prepare("SELECT id,name,email FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) {
    $_SESSION['flash'] = 'User not found.';
    header('Location: admin_users'); exit;
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass1 = $_POST['password'] ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';
    if (!$pass1 || !$pass2) {
        $err = 'Please fill both password fields.';
    } elseif ($pass1 !== $pass2) {
        $err = 'Passwords do not match.';
    } elseif (strlen($pass1) < 6) {
        $err = 'Password should be at least 6 characters.';
    } else {
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
        $upd->execute([$hash, $id]);
        $_SESSION['flash'] = 'Password updated for ' . htmlspecialchars($user['name']);
        header('Location: admin_users'); exit;
    }
}
require 'header.php';
?>
<div class="max-w-md mx-auto py-10 px-4">
  <div class="card-custom shadow-lg">
    <div class="bg-yellow-600 text-white text-center rounded-t-lg p-4">
      <h3 class="mb-0"><i class="bi bi-key-fill"></i> Change Password</h3>
    </div>
    <div class="p-6">
      <?php if($err) echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>$err</div>"; ?>
      <form method="post" novalidate>
        <div class="mb-4">
          <label class="block text-sm font-semibold">User</label>
          <div class="mt-1 p-2 bg-gray-100 rounded"><?=htmlspecialchars($user['name'])?> &lt;<?=htmlspecialchars($user['email'])?>&gt;</div>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-semibold" for="password">New Password</label>
          <input name="password" id="password" type="password" class="w-full mt-1 p-2 border rounded" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-semibold" for="password_confirm">Confirm Password</label>
          <input name="password_confirm" id="password_confirm" type="password" class="w-full mt-1 p-2 border rounded" required>
        </div>
        <div class="text-center">
          <button class="inline-block px-6 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition" type="submit">
            Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php require 'footer.php'; ?>