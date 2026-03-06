<?php
require 'config.php';
if (empty($_SESSION['admin'])) { header('Location: admin_login'); exit; }
// Handle delete
if (isset($_GET['delete'])) {
  $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$_GET['delete']]);
  header('Location: admin_users'); exit;
}
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Users</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="modern.css">
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    @keyframes fadeIn { from { opacity:0; transform: translateY(10px);} to {opacity:1; transform: translateY(0);} }
    .fade-in { opacity:0; transform: translateY(10px); transition: opacity 0.6s, transform 0.6s; }
    .fade-in.visible { opacity:1; transform: translateY(0); }
  </style>
</head>
<body>
<div class="max-w-4xl mx-auto py-6 px-4">
  <h3 class="text-xl font-semibold mb-4">All Users</h3>
  <div class="overflow-x-auto">
    <table class="min-w-full bg-white rounded shadow">
      <thead>
        <tr class="bg-gray-100">
          <th class="p-2 text-left">ID</th><th class="p-2 text-left">Name</th><th class="p-2 text-left">Email</th><th class="p-2">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($users as $u): ?>
          <tr class="border-b hover:bg-gray-50">
            <td class="p-2"><?=$u['id']?></td>
            <td class="p-2"><?=htmlspecialchars($u['name'])?></td>
            <td class="p-2"><?=htmlspecialchars($u['email'])?></td>
            <td class="p-2 text-center space-x-1">
              <a href="admin_change_password?id=<?=$u['id']?>" class="px-2 py-1 bg-green-600 text-white rounded text-sm">Password</a>
              <a href="admin_users?delete=<?=$u['id']?>" class="px-2 py-1 bg-red-600 text-white rounded text-sm" onclick="return confirm('Delete this user?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <a href="admin_dashboard" class="inline-block mt-3 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition">Back to Dashboard</a>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const obs = new IntersectionObserver(entries=>{ entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('visible'); obs.unobserve(e.target);} }); },{threshold:0.1});
  document.querySelectorAll('.card, .table').forEach(el=>{ el.classList.add('fade-in'); obs.observe(el); });
});
</script>
<?php require 'footer.php'; ?>
