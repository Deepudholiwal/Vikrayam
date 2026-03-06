<?php
require 'config.php';
if (empty($_SESSION['admin'])) { header('Location: admin_login'); exit; }
// Handle delete
if (isset($_GET['delete'])) {
  $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$_GET['delete']]);
  header('Location: admin_categories'); exit;
}
$cats = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  if ($name) {
    $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
    header('Location: admin_categories'); exit;
  } else {
    $err = 'Category name required.';
  }
}
?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Categories</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="modern.css">
  <style>
    @keyframes fadeIn { from { opacity:0; transform: translateY(10px);} to {opacity:1; transform: translateY(0);} }
    .fade-in { opacity:0; transform: translateY(10px); transition: opacity 0.6s, transform 0.6s; }
    .fade-in.visible { opacity:1; transform: translateY(0); }
  </style>
</head>
<body>
<div class="max-w-4xl mx-auto py-6 px-4">
  <h3 class="text-xl font-semibold mb-4">Manage Categories</h3>
  <?php if($err) echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4'>$err</div>"; ?>
  <div class="card-custom mb-6">
    <div class="p-4 border-b">
      <h5 class="font-semibold">Add New Category</h5>
    </div>
    <div class="p-4">
      <form method="post" class="md:flex md:items-end md:space-x-4">
        <div class="flex-1 mb-4">
          <label for="name" class="block text-sm font-semibold">Category Name</label>
          <input name="name" id="name" class="w-full mt-1 p-2 border rounded" placeholder="Enter category name" required>
        </div>
        <div class="mb-4">
          <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition" type="submit">
            <i class="bi bi-plus-circle"></i> Add Category
          </button>
        </div>
      </form>
    </div>
  </div>
  <div class="card-custom mb-4">
    <div class="p-4 border-b">
      <h5 class="font-semibold">Existing Categories</h5>
    </div>
    <div class="p-0 overflow-auto">
      <table class="w-full table-auto">
        <thead class="bg-gray-100">
          <tr><th class="p-2 text-left">ID</th><th class="p-2 text-left">Name</th><th class="p-2">Action</th></tr>
        </thead>
        <tbody>
          <?php foreach($cats as $c): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="p-2"><?=$c['id']?></td>
              <td class="p-2"><?=htmlspecialchars($c['name'])?></td>
              <td class="p-2 text-center">
                <a href="admin_categories?delete=<?=$c['id']?>" class="px-2 py-1 bg-red-600 text-white rounded text-sm" onclick="return confirm('Delete this category?')">
                  <i class="bi bi-trash"></i> Delete
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <a href="admin_dashboard" class="inline-block mt-3 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition">Back to Dashboard</a>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const obs = new IntersectionObserver(entries=>{ entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('visible'); obs.unobserve(e.target);} }); },{threshold:0.1});
  document.querySelectorAll('.card-custom, table').forEach(el=>{ el.classList.add('fade-in'); obs.observe(el); });
});
</script>
<?php require 'footer.php'; ?>
