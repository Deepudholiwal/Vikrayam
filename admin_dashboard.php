<?php
// admin_dashboard.php - Comprehensive Admin Panel
require 'config.php';
if (empty($_SESSION['admin'])) { header('Location: admin_login'); exit; }

$message = '';
$active_tab = $_GET['tab'] ?? 'overview';

// Handle user actions
if (isset($_POST['action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        $message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">Invalid form submission.</div>';
    } else {
        if ($_POST['action'] === 'delete_user') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id > 0) {
                $pdo->prepare("DELETE FROM listings WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM favorites WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM likes WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM comments WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
                $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">User deleted successfully!</div>';
            }
        } elseif ($_POST['action'] === 'make_admin') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id > 0) {
                $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?")->execute([$user_id]);
                $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">User promoted to admin!</div>';
            }
        } elseif ($_POST['action'] === 'remove_admin') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id > 0) {
                $pdo->prepare("UPDATE users SET is_admin = 0 WHERE id = ?")->execute([$user_id]);
                $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">Admin privileges removed!</div>';
            }
        } elseif ($_POST['action'] === 'verify_user') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id > 0) {
                $pdo->prepare("UPDATE users SET verified = 1 WHERE id = ?")->execute([$user_id]);
                $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">User verified!</div>';
            }
        } elseif ($_POST['action'] === 'delete_listing') {
            $listing_id = (int)($_POST['listing_id'] ?? 0);
            if ($listing_id > 0) {
                $pdo->prepare("DELETE FROM likes WHERE listing_id = ?")->execute([$listing_id]);
                $pdo->prepare("DELETE FROM comments WHERE listing_id = ?")->execute([$listing_id]);
                $pdo->prepare("DELETE FROM favorites WHERE listing_id = ?")->execute([$listing_id]);
                $pdo->prepare("DELETE FROM listings WHERE id = ?")->execute([$listing_id]);
                $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">Listing deleted!</div>';
            }
        } elseif ($_POST['action'] === 'toggle_listing') {
            $listing_id = (int)($_POST['listing_id'] ?? 0);
            if ($listing_id > 0) {
                $pdo->prepare("UPDATE listings SET active = NOT active WHERE id = ?")->execute([$listing_id]);
                $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">Listing status toggled!</div>';
            }
        } elseif ($_POST['action'] === 'add_category') {
            $name = trim($_POST['category_name'] ?? '');
            if ($name) {
                $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
                $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">Category added!</div>';
            }
        } elseif ($_POST['action'] === 'delete_category') {
            $cat_id = (int)($_POST['category_id'] ?? 0);
            if ($cat_id > 0) {
                $pdo->prepare("UPDATE listings SET category_id = 1 WHERE category_id = ?")->execute([$cat_id]);
                $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$cat_id]);
                $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">Category deleted!</div>';
            }
        } elseif ($_POST['action'] === 'upload_logo') {
            if (!empty($_FILES['logo']['name'])) {
                $allowed = ['jpg','jpeg','png','gif','webp'];
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed) && $_FILES['logo']['size'] <= 2*1024*1024) {
                    $fname = 'logo.' . $ext;
                    $dest = __DIR__ . '/assets/' . $fname;
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                        $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">Logo uploaded successfully!</div>';
                    } else {
                        $message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">Failed to upload logo.</div>';
                    }
                } else {
                    $message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">Invalid logo file. Use JPG, PNG, GIF or WebP (max 2MB).</div>';
                }
            }
        } elseif ($_POST['action'] === 'upload_footer_logo') {
            if (!empty($_FILES['footer_logo']['name'])) {
                $allowed = ['jpg','jpeg','png','gif','webp'];
                $ext = strtolower(pathinfo($_FILES['footer_logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed) && $_FILES['footer_logo']['size'] <= 2*1024*1024) {
                    $fname = 'Footer Logo.' . $ext;
                    $dest = __DIR__ . '/assets/' . $fname;
                    if (move_uploaded_file($_FILES['footer_logo']['tmp_name'], $dest)) {
                        $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">Footer logo uploaded successfully!</div>';
                    } else {
                        $message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">Failed to upload footer logo.</div>';
                    }
                } else {
                    $message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">Invalid logo file. Use JPG, PNG, GIF or WebP (max 2MB).</div>';
                }
            }
        }
    }
}

// Fetch statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_listings = $pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_active_listings = $pdo->query("SELECT COUNT(*) FROM listings WHERE active = 1")->fetchColumn();
$verified_users = $pdo->query("SELECT COUNT(*) FROM users WHERE verified = 1")->fetchColumn();
$admin_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1")->fetchColumn();

$recent_listings = $pdo->query("SELECT l.*, u.name as author FROM listings l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 10")->fetchAll();
$recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll();
$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM listings WHERE category_id = c.id) as listing_count FROM categories c")->fetchAll();

$search = $_GET['search'] ?? '';
$search_results = [];
if ($search) {
    $search_stmt = $pdo->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? LIMIT 20");
    $search_stmt->execute(["%$search%", "%$search%"]);
    $search_results = $search_stmt->fetchAll();
}

// Check logo existence
function checkLogo($prefix) {
    $extensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    foreach($extensions as $ext) {
        if(file_exists(__DIR__ . '/assets/'.$prefix.'.'.$ext)) {
            return $prefix.'.'.$ext;
        }
    }
    return null;
}

$headerLogo = checkLogo('logo');
$footerLogo = checkLogo('Footer Logo');
?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard - Vikrayam</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link rel="icon" type="image/png" href="../assets/logo.png">
  <link rel="stylesheet" href="modern.css">
</head>
<body>
<nav class="navbar-custom">
  <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between px-4">
    <div class="flex items-center gap-3">
      <i class="bi bi-shield-lock text-blue-600 text-2xl"></i>
      <a class="text-xl md:text-2xl font-bold text-blue-600" href="admin_dashboard">Admin Panel</a>
    </div>
    <div class="flex flex-wrap gap-2 md:gap-4 mt-2 md:mt-0">
      <a class="text-gray-700 hover:text-blue-600 transition text-sm md:text-base" href="?tab=overview">Overview</a>
      <a class="text-gray-700 hover:text-blue-600 transition text-sm md:text-base" href="?tab=users">Users</a>
      <a class="text-gray-700 hover:text-blue-600 transition text-sm md:text-base" href="?tab=listings">Listings</a>
      <a class="text-gray-700 hover:text-blue-600 transition text-sm md:text-base" href="?tab=categories">Categories</a>
      <a class="text-gray-700 hover:text-blue-600 transition text-sm md:text-base" href="?tab=search">Search</a>
      <a class="text-gray-700 hover:text-blue-600 transition text-sm md:text-base" href="?tab=settings">Settings</a>
      <a class="text-red-600 hover:text-red-800 transition text-sm md:text-base" href="admin_logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
  </div>
</nav>

<div class="max-w-7xl mx-auto py-4 md:py-8 px-2 md:px-4">
  <?= $message ?>
  
  <!-- Overview Tab -->
  <?php if ($active_tab === 'overview'): ?>
  <h2 class="text-2xl font-bold mb-6">Dashboard Overview</h2>
  
  <div class="admin-grid mb-8">
    <div class="admin-stat-card">
      <i class="bi bi-people"></i>
      <div>
        <h5 class="text-lg font-semibold">Total Users</h5>
        <p class="text-3xl font-bold"><?= $total_users ?></p>
      </div>
    </div>
    <div class="admin-stat-card green">
      <i class="bi bi-collection"></i>
      <div>
        <h5 class="text-lg font-semibold">Total Listings</h5>
        <p class="text-3xl font-bold"><?= $total_listings ?></p>
      </div>
    </div>
    <div class="admin-stat-card yellow">
      <i class="bi bi-check-circle"></i>
      <div>
        <h5 class="text-lg font-semibold">Active Listings</h5>
        <p class="text-3xl font-bold"><?= $total_active_listings ?></p>
      </div>
    </div>
    <div class="admin-stat-card purple">
      <i class="bi bi-person-check"></i>
      <div>
        <h5 class="text-lg font-semibold">Verified Users</h5>
        <p class="text-3xl font-bold"><?= $verified_users ?></p>
      </div>
    </div>
    <div class="admin-stat-card red">
      <i class="bi bi-tags"></i>
      <div>
        <h5 class="text-lg font-semibold">Categories</h5>
        <p class="text-3xl font-bold"><?= $total_categories ?></p>
      </div>
    </div>
    <div class="admin-stat-card" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
      <i class="bi bi-shield-check"></i>
      <div>
        <h5 class="text-lg font-semibold">Admins</h5>
        <p class="text-3xl font-bold"><?= $admin_users ?></p>
      </div>
    </div>
  </div>
  
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="card-custom">
      <h3 class="text-lg font-semibold mb-4"><i class="bi bi-clock-history"></i> Recent Listings</h3>
      <div class="overflow-x-auto">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Author</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($recent_listings as $l): ?>
            <tr>
              <td><a href="view_listing?id=<?=$l['id']?>" class="text-blue-600 hover:underline"><?=htmlspecialchars(substr($l['title'],0,30))?></a></td>
              <td><?=htmlspecialchars($l['author'])?></td>
              <td><span class="px-2 py-1 rounded text-xs <?=$l['active']?'bg-green-100 text-green-800':'bg-red-100 text-red-800'?>"><?=$l['active']?'Active':'Inactive'?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <a href="?tab=listings" class="inline-block mt-4 text-blue-600 hover:underline">View All Listings →</a>
    </div>
    
    <div class="card-custom">
      <h3 class="text-lg font-semibold mb-4"><i class="bi bi-people"></i> Recent Users</h3>
      <div class="overflow-x-auto">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($recent_users as $u): ?>
            <tr>
              <td><?=htmlspecialchars($u['name'])?></td>
              <td><?=htmlspecialchars($u['email'])?></td>
              <td>
                <?php if($u['is_admin']): ?>
                  <span class="px-2 py-1 rounded text-xs bg-purple-100 text-purple-800">Admin</span>
                <?php elseif($u['verified']): ?>
                  <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-800">Verified</span>
                <?php else: ?>
                  <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-800">Pending</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <a href="?tab=users" class="inline-block mt-4 text-blue-600 hover:underline">View All Users →</a>
    </div>
  </div>
  <?php endif; ?>
  
  <!-- Users Tab -->
  <?php if ($active_tab === 'users'): ?>
  <h2 class="text-2xl font-bold mb-6">User Management</h2>
  <div class="card-custom">
    <div class="overflow-x-auto">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Joined</th>
            <th>Status</th>
            <th>Listings</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $all_users = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM listings WHERE user_id = u.id) as listing_count FROM users u ORDER BY u.created_at DESC")->fetchAll();
          foreach($all_users as $u): 
          ?>
          <tr>
            <td><?= $u['id'] ?></td>
            <td><?=htmlspecialchars($u['name'])?></td>
            <td><?=htmlspecialchars($u['email'])?></td>
            <td><?=date('M d, Y', strtotime($u['created_at']))?></td>
            <td>
              <?php if($u['is_admin']): ?>
                <span class="px-2 py-1 rounded text-xs bg-purple-100 text-purple-800">Admin</span>
              <?php elseif($u['verified']): ?>
                <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-800">Verified</span>
              <?php else: ?>
                <span class="px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-800">Pending</span>
              <?php endif; ?>
            </td>
            <td><?= $u['listing_count'] ?></td>
            <td class="admin-actions">
              <?php if(!$u['is_admin']): ?>
                <form method="post" class="inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" name="action" value="make_admin" class="admin-btn admin-btn-edit" title="Make Admin">
                    <i class="bi bi-shield-plus"></i>
                  </button>
                </form>
              <?php else: ?>
                <form method="post" class="inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" name="action" value="remove_admin" class="admin-btn" style="background:#8b5cf6;color:white" title="Remove Admin">
                    <i class="bi bi-shield-dash"></i>
                  </button>
                </form>
              <?php endif; ?>
              
              <?php if(!$u['verified']): ?>
                <form method="post" class="inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" name="action" value="verify_user" class="admin-btn admin-btn-view" title="Verify User">
                    <i class="bi bi-check-circle"></i>
                  </button>
                </form>
              <?php endif; ?>
              
              <a href="user?id=<?=$u['id']?>" class="admin-btn admin-btn-view" title="View Profile">
                <i class="bi bi-eye"></i>
              </a>
              
              <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete this user and all their listings?');">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button type="submit" name="action" value="delete_user" class="admin-btn admin-btn-delete" title="Delete User">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
  
  <!-- Listings Tab -->
  <?php if ($active_tab === 'listings'): ?>
  <h2 class="text-2xl font-bold mb-6">Listing Management</h2>
  <div class="card-custom">
    <div class="overflow-x-auto">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Author</th>
            <th>Category</th>
            <th>Price</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $all_listings = $pdo->query("SELECT l.*, u.name as author, c.name as category FROM listings l JOIN users u ON l.user_id = u.id JOIN categories c ON l.category_id = c.id ORDER BY l.created_at DESC")->fetchAll();
          foreach($all_listings as $l): 
          ?>
          <tr>
            <td><?= $l['id'] ?></td>
            <td><?=htmlspecialchars(substr($l['title'],0,25))?></td>
            <td><?=htmlspecialchars($l['author'])?></td>
            <td><?=htmlspecialchars($l['category'])?></td>
            <td><?= $l['price'] ? '₹'.$l['price'] : 'Free' ?></td>
            <td>
              <span class="px-2 py-1 rounded text-xs <?=$l['active']?'bg-green-100 text-green-800':'bg-red-100 text-red-800'?>">
                <?=$l['active']?'Active':'Inactive'?>
              </span>
            </td>
            <td><?=date('M d, Y', strtotime($l['created_at']))?></td>
            <td class="admin-actions">
              <a href="view_listing?id=<?=$l['id']?>" class="admin-btn admin-btn-view" title="View">
                <i class="bi bi-eye"></i>
              </a>
              <a href="edit_listing?id=<?=$l['id']?>" class="admin-btn admin-btn-edit" title="Edit">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="post" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                <button type="submit" name="action" value="toggle_listing" class="admin-btn" style="background:#f59e0b;color:white" title="Toggle Status">
                  <i class="bi bi-toggle-on"></i>
                </button>
              </form>
              <form method="post" class="inline" onsubmit="return confirm('Are you sure?');">
                <?= csrf_field() ?>
                <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                <button type="submit" name="action" value="delete_listing" class="admin-btn admin-btn-delete" title="Delete">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
  
  <!-- Categories Tab -->
  <?php if ($active_tab === 'categories'): ?>
  <h2 class="text-2xl font-bold mb-6">Category Management</h2>
  
  <div class="card-custom mb-6">
    <h3 class="text-lg font-semibold mb-4">Add New Category</h3>
    <form method="post" class="flex flex-col md:flex-row gap-4">
      <?= csrf_field() ?>
      <input type="text" name="category_name" placeholder="Category Name" class="flex-1" required>
      <button type="submit" name="action" value="add_category" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        <i class="bi bi-plus-circle"></i> Add Category
      </button>
    </form>
  </div>
  
  <div class="card-custom">
    <div class="overflow-x-auto">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Listings</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($categories as $cat): ?>
          <tr>
            <td><?= $cat['id'] ?></td>
            <td><?=htmlspecialchars($cat['name'])?></td>
            <td><?= $cat['listing_count'] ?></td>
            <td class="admin-actions">
              <?php if($cat['id'] > 1): ?>
              <form method="post" class="inline" onsubmit="return confirm('Delete this category? Listings will be moved to Uncategorized.');">
                <?= csrf_field() ?>
                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                <button type="submit" name="action" value="delete_category" class="admin-btn admin-btn-delete">
                  <i class="bi bi-trash"></i> Delete
                </button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
  
  <!-- Search Tab -->
  <?php if ($active_tab === 'search'): ?>
  <h2 class="text-2xl font-bold mb-6">Search Users</h2>
  <div class="card-custom mb-6">
    <form method="get" class="flex flex-col md:flex-row gap-4">
      <input type="hidden" name="tab" value="search">
      <input type="text" name="search" placeholder="Search by name or email..." value="<?=htmlspecialchars($search)?>" class="flex-1">
      <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        <i class="bi bi-search"></i> Search
      </button>
    </form>
  </div>
  
  <?php if($search): ?>
  <div class="card-custom">
    <h3 class="text-lg font-semibold mb-4">Search Results (<?= count($search_results) ?>)</h3>
    <?php if($search_results): ?>
    <div class="overflow-x-auto">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Joined</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($search_results as $u): ?>
          <tr>
            <td><?=htmlspecialchars($u['name'])?></td>
            <td><?=htmlspecialchars($u['email'])?></td>
            <td><?=date('M d, Y', strtotime($u['created_at']))?></td>
            <td class="admin-actions">
              <a href="user?id=<?=$u['id']?>" class="admin-btn admin-btn-view">View Profile</a>
              <?php if(!$u['is_admin']): ?>
              <form method="post" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button type="submit" name="action" value="make_admin" class="admin-btn admin-btn-edit">Make Admin</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <p class="text-gray-500">No users found matching "<?=htmlspecialchars($search)?>"</p>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
  
  <!-- Settings Tab -->
  <?php if ($active_tab === 'settings'): ?>
  <h2 class="text-2xl font-bold mb-6">Site Settings</h2>
  
  <div class="card-custom mb-6">
    <h3 class="text-lg font-semibold mb-4"><i class="bi bi-image"></i> Logo Settings</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Header Logo -->
      <div>
        <h4 class="font-semibold mb-2">Header Logo</h4>
        <div class="mb-4">
          <?php if($headerLogo): ?>
            <img src="assets/<?=$headerLogo?>" class="h-20 w-auto mb-2" alt="Current Logo">
            <p class="text-sm text-green-600"><i class="bi bi-check-circle"></i> Logo uploaded</p>
          <?php else: ?>
            <p class="text-sm text-gray-500 mb-2">No logo uploaded yet</p>
          <?php endif; ?>
        </div>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="upload_logo">
          <div class="flex gap-2">
            <input type="file" name="logo" accept="image/*" class="flex-1 border rounded p-2" required>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
              <i class="bi bi-upload"></i> Upload
            </button>
          </div>
          <p class="text-xs text-gray-500 mt-1">Recommended: 150x50px, max 2MB</p>
        </form>
      </div>
      
      <!-- Footer Logo -->
      <div>
        <h4 class="font-semibold mb-2">Footer Logo</h4>
        <div class="mb-4">
          <?php if($footerLogo): ?>
            <img src="assets/<?=$footerLogo?>" class="h-16 w-auto mb-2" alt="Current Footer Logo">
            <p class="text-sm text-green-600"><i class="bi bi-check-circle"></i> Footer logo uploaded</p>
          <?php else: ?>
            <p class="text-sm text-gray-500 mb-2">No footer logo uploaded yet</p>
          <?php endif; ?>
        </div>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="upload_footer_logo">
          <div class="flex gap-2">
            <input type="file" name="footer_logo" accept="image/*" class="flex-1 border rounded p-2" required>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
              <i class="bi bi-upload"></i> Upload
            </button>
          </div>
          <p class="text-xs text-gray-500 mt-1">Recommended: 200x60px, max 2MB</p>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
  
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const messages = document.querySelectorAll('[class*="bg-green-100"], [class*="bg-red-100"]');
        messages.forEach(function(msg) {
            msg.style.transition = 'opacity 0.5s';
            msg.style.opacity = '0';
            setTimeout(function() { msg.remove(); }, 500);
        });
    }, 5000);
});
</script>
</body>
</html>
