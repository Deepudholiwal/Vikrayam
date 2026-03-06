<?php
require 'config.php';
require 'header.php';
$user = current_user($pdo);
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($email && $message) {
        // pretend to send email
        $_SESSION['flash'] = 'Thank you! Your message has been received.';
        header('Location: contact'); exit;
    } else {
        $msg = 'Please fill both fields.';
    }
}
?>
<div class="max-w-4xl mx-auto py-10 px-4">
  <h2 class="text-2xl font-bold mb-4">Contact Us</h2>
  <?php if($msg) echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4'>$msg</div>"; ?>
  <form method="post" class="space-y-4">
    <div>
      <label class="block text-sm font-semibold" for="email">Your Email</label>
      <input type="email" name="email" id="email" class="w-full mt-1 p-2 border rounded" required value="<?=htmlspecialchars($user['email'] ?? '')?>">
    </div>
    <div>
      <label class="block text-sm font-semibold" for="message">Message</label>
      <textarea name="message" id="message" rows="5" class="w-full mt-1 p-2 border rounded" required></textarea>
    </div>
    <div>
      <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Send Message</button>
    </div>
  </form>
</div>
<?php require 'footer.php'; ?>