<?php
date_default_timezone_set('Asia/Kolkata');
require_once 'config.php';
$user = current_user($pdo);
if (!$user) {
    header('Location: login'); exit;
}

$err = '';
$msg = '';

// Handle profile update with image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        $err = 'Invalid form submission.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($name) || empty($email)) {
            $err = 'Name and email are required.';
        } else {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $user['id']]);
            if ($check->fetch()) {
                $err = 'Email already in use by another account.';
            } else {
                $profile_image = $user['profile_image'];
                
                if (!empty($_FILES['profile_image']['name'])) {
                    $allowed = ['jpg','jpeg','png','gif', 'webp'];
                    $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed) && $_FILES['profile_image']['size'] <= 2*1024*1024) {
                        if ($profile_image && file_exists(__DIR__.'/'.$profile_image)) {
                            @unlink(__DIR__.'/'.$profile_image);
                        }
                        $fname = 'profile_'.$user['id'].'_'.time().'.'.$ext;
                        $dest = __DIR__ . '/uploads/' . $fname;
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
                            $profile_image = 'uploads/' . $fname;
                        }
                    } else {
                        $err = 'Invalid profile image. Use JPG, PNG, GIF or WebP (max 2MB).';
                    }
                }
                
                if (!$err) {
                    $update = $pdo->prepare("UPDATE users SET name = ?, email = ?, profile_image = ? WHERE id = ?");
                    $update->execute([$name, $email, $profile_image, $user['id']]);
                    $user['name'] = $name;
                    $user['email'] = $email;
                    $user['profile_image'] = $profile_image;
                    $msg = 'Profile updated successfully!';
                }
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        $err = 'Invalid form submission.';
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
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
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$hash, $user['id']]);
            $msg = 'Password changed successfully!';
        }
    }
}

// Handle favorite removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_fav'], $_POST['listing_id'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verify_csrf($csrfToken)) {
        $lid = (int)$_POST['listing_id'];
        $pdo->prepare("DELETE FROM favorites WHERE user_id=? AND listing_id=?")->execute([$user['id'],$lid]);
    }
    header('Location: dashboard'); exit;
}

// Get user's listings
$stmt = $pdo->prepare("SELECT l.*, c.name as category FROM listings l JOIN categories c ON l.category_id=c.id WHERE l.user_id=? ORDER BY l.created_at DESC");
$stmt->execute([$user['id']]);
$my_listings = $stmt->fetchAll();

// Get user's favorites
$fav_stmt = $pdo->prepare("SELECT l.*, c.name as category FROM listings l JOIN categories c ON l.category_id=c.id JOIN favorites f ON f.listing_id=l.id WHERE f.user_id=? ORDER BY l.created_at DESC");
$fav_stmt->execute([$user['id']]);
$favorites = $fav_stmt->fetchAll();

// Get user's likes
$likes_stmt = $pdo->prepare("SELECT l.*, c.name as category FROM listings l JOIN categories c ON l.category_id=c.id JOIN likes lk ON lk.listing_id=l.id WHERE lk.user_id=? ORDER BY lk.created_at DESC");
$likes_stmt->execute([$user['id']]);
$likes = $likes_stmt->fetchAll();

// Get user's comments
$comments_stmt = $pdo->prepare("SELECT cm.*, l.id as listing_id, l.title as listing_title FROM comments cm JOIN listings l ON l.id=cm.listing_id WHERE cm.user_id=? ORDER BY cm.created_at DESC");
$comments_stmt->execute([$user['id']]);
$comments = $comments_stmt->fetchAll();

// Get stats
$total_listings = count($my_listings);
$total_favorites = count($favorites);
$total_likes = count($likes);
$total_comments = count($comments);

// Get global stats for comparison
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalListings = $pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();

// Time-based greeting
$hour = date('H');
if ($hour < 12) {
    $greeting = 'Good Morning';
    $greetingIcon = '☀️';
} elseif ($hour < 17) {
    $greeting = 'Good Afternoon';
    $greetingIcon = '🌤️';
} else {
    $greeting = 'Good Evening';
    $greetingIcon = '🌙';
}
?>

<?php require 'header.php'; ?>

<div class="dashboard-container min-h-screen">
    <div class="max-w-7xl mx-auto">
        
        <!-- Welcome Banner with Animation -->
        <div class="relative overflow-hidden rounded-3xl mb-6 md:mb-8 shadow-2xl welcome-banner">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600"></div>
            <div class="absolute inset-0 opacity-30">
                <div class="floating-shapes">
                    <div class="shape shape-1"></div>
                    <div class="shape shape-2"></div>
                    <div class="shape shape-3"></div>
                </div>
            </div>
            <div class="relative z-10 p-6 md:p-10">
                <div class="welcome-banner-content">
                    <div class="text-center md:text-left mb-4 md:mb-0">
<div class="text-white/80 text-base md:text-lg mb-1"><?=$greetingIcon?> <?=$greeting?></div>
                        <h1 class="text-2xl md:text-4xl font-bold text-white mb-2">
                            Welcome back, <span class="text-yellow-300"><?=htmlspecialchars($user['name'])?></span>! 👋
                        </h1>
                        <p class="text-white/80 text-base">Here's what's happening with your listings today.</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center md:justify-end">
                        <a href="create_listing" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 md:px-6 md:py-3 bg-white text-blue-600 rounded-full font-semibold hover:bg-gray-100 transition shadow-lg transform hover:scale-105 text-sm md:text-base">
                            <i class="bi bi-plus-circle"></i> <span class="hidden sm:inline">New Listing</span><span class="sm:hidden">New</span>
                        </a>
                        <a href="index" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 md:px-6 md:py-3 bg-white/20 text-white rounded-full font-semibold hover:bg-white/30 transition backdrop-blur transform hover:scale-105 text-sm md:text-base">
                            <i class="bi bi-compass"></i> Explore
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards with Icons -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <i class="bi bi-briefcase text-blue-600 dark:text-blue-400 text-xl"></i>
                    </div>
                </div>
                <div class="text-3xl font-bold text-gray-800 dark:text-white mb-1"><?=$total_listings?></div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">My Listings</div>
                <div class="text-xs text-blue-600 dark:text-blue-400 mt-2"><?=$totalListings > 0 ? round(($total_listings/$totalListings)*100) : 0?>% of total</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-xl bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center">
                        <i class="bi bi-heart-fill text-pink-600 dark:text-pink-400 text-xl"></i>
                    </div>
                </div>
                <div class="text-3xl font-bold text-gray-800 dark:text-white mb-1"><?=$total_favorites?></div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Favorites</div>
                <div class="text-xs text-pink-600 dark:text-pink-400 mt-2">Saved items</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <i class="bi bi-hand-thumbs-up-fill text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                </div>
                <div class="text-3xl font-bold text-gray-800 dark:text-white mb-1"><?=$total_likes?></div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Likes Received</div>
                <div class="text-xs text-red-600 dark:text-red-400 mt-2">People loved it</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <i class="bi bi-chat-dots-fill text-green-600 dark:text-green-400 text-xl"></i>
                    </div>
                </div>
                <div class="text-3xl font-bold text-gray-800 dark:text-white mb-1"><?=$total_comments?></div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Comments</div>
                <div class="text-xs text-green-600 dark:text-green-400 mt-2">On your items</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-3 mb-8">
            <a href="create_listing" class="flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-medium hover:from-blue-700 hover:to-blue-800 transition shadow-sm hover:shadow-md">
                <i class="bi bi-plus-lg"></i> New Listing
            </a>
            <a href="index" class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-xl font-medium border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 transition shadow-sm">
                <i class="bi bi-compass"></i> Browse
            </a>
            <a href="favorites" class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-xl font-medium border border-gray-200 dark:border-gray-700 hover:border-pink-500 dark:hover:border-pink-500 hover:text-pink-600 dark:hover:text-pink-400 transition shadow-sm">
                <i class="bi bi-heart"></i> Favorites
            </a>
        </div>

        <!-- Messages -->
        <?php if($err): ?>
            <div class='alert alert-error'>
                <i class="bi bi-exclamation-circle"></i> <?=$err?>
            </div>
        <?php endif; ?>
        <?php if($msg): ?>
            <div class='alert alert-success'>
                <i class="bi bi-check-circle"></i> <?=$msg?>
            </div>
        <?php endif; ?>

        <!-- Dashboard Tabs -->
        <div class="dashboard-tabs">
            <button onclick="showTab('profile')" id="btn-profile" class="dashboard-tab active">
                <i class="bi bi-person"></i> <span class="hidden xs:inline">Profile</span>
            </button>
            <button onclick="showTab('listings')" id="btn-listings" class="dashboard-tab">
                <i class="bi bi-collection"></i> <span class="hidden xs:inline">My Listings</span>
            </button>
            <button onclick="showTab('favorites')" id="btn-favorites" class="dashboard-tab">
                <i class="bi bi-heart"></i> <span class="hidden xs:inline">Favorites</span>
            </button>
            <button onclick="showTab('likes')" id="btn-likes" class="dashboard-tab">
                <i class="bi bi-hand-thumbs-up"></i> <span class="hidden xs:inline">Liked</span>
            </button>
            <button onclick="showTab('comments')" id="btn-comments" class="dashboard-tab">
                <i class="bi bi-chat"></i> <span class="hidden xs:inline">Comments</span>
            </button>
        </div>

        <!-- Profile Tab Content -->
        <div id="tab-profile" class="tab-content">
            <div class="profile-cards-grid">
                <!-- Edit Profile -->
                <div class="auth-card shadow-xl">
                    <div class="auth-header" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 1.25rem md:1.5rem;">
                        <h3 class="text-lg md:text-xl font-bold text-white"><i class="bi bi-person-circle"></i> Edit Profile</h3>
                    </div>
                    <div class="auth-body">
                        <form method="post" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="mb-4">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Profile Photo</label>
                                <div class="profile-upload-container">
                                    <?php if(!empty($user['profile_image'])): ?>
                                        <img src="<?=htmlspecialchars($user['profile_image'])?>" class="profile-preview" alt="Profile">
                                    <?php else: ?>
                                        <div class="profile-preview" style="display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg, #3b82f6, #8b5cf6);color:white;font-weight:bold;font-size:1.5rem;">
                                            <?=strtoupper(substr($user['name'], 0, 1))?>
                                        </div>
                                    <?php endif; ?>
                                    <label class="profile-upload-btn">
                                        <i class="bi bi-camera"></i> Choose Photo
                                        <input type="file" name="profile_image" accept="image/*" class="hidden" style="display:none;">
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
                                <input type="text" name="name" value="<?=htmlspecialchars($user['name'])?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Email Address</label>
                                <input type="email" name="email" value="<?=htmlspecialchars($user['email'])?>" required>
                            </div>
                            
                            <button type="submit" class="w-full btn-gradient py-2.5 md:py-3 rounded-xl font-semibold">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="auth-card shadow-xl">
                    <div class="auth-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 1.25rem md:1.5rem;">
                        <h3 class="text-lg md:text-xl font-bold text-white"><i class="bi bi-key"></i> Change Password</h3>
                    </div>
                    <div class="auth-body">
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="mb-4">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Current Password</label>
                                <input type="password" name="current_password" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">New Password</label>
                                <input type="password" name="new_password" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Confirm Password</label>
                                <input type="password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="w-full btn-gradient btn-gradient-green py-2.5 md:py-3 rounded-xl font-semibold">
                                <i class="bi bi-key"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Account -->
                <div class="auth-card shadow-xl account-card">
                    <div class="auth-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 1.25rem md:1.5rem;">
                        <h3 class="text-lg md:text-xl font-bold text-white"><i class="bi bi-box-arrow-right"></i> Account</h3>
                    </div>
                    <div class="auth-body">
                        <p class="text-gray-600 dark:text-gray-400 mb-4 text-sm">Ready to leave? Log out of your account securely.</p>
                        <a href="logout" class="logout-btn">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Listings Tab -->
        <div id="tab-listings" class="tab-content hidden">
            <div class="section-header">
                <h3 class="section-title"><i class="bi bi-collection"></i> My Listings</h3>
                <a href="create_listing" class="btn-gradient py-2 px-4 rounded-lg font-semibold text-sm inline-flex items-center gap-1">
                    <i class="bi bi-plus-lg"></i> Add New
                </a>
            </div>
            <?php if(empty($my_listings)): ?>
                <div class="empty-state-container">
                    <div class="empty-state-icon listings">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h3 class="empty-state-title">No Listings Yet</h3>
                    <p class="empty-state-text">Start selling! Create your first listing and reach thousands of buyers.</p>
                    <a href="create_listing" class="btn-gradient py-2.5 px-5 rounded-xl font-semibold inline-flex items-center gap-2">
                        <i class="bi bi-plus-circle"></i> Post Your First Listing
                    </a>
                </div>
            <?php else: ?>
                <div class="listing-cards-grid">
                    <?php foreach($my_listings as $listing):
                        $type = 'my';
                        include 'listing_card.php';
                    endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Favorites Tab -->
        <div id="tab-favorites" class="tab-content hidden">
            <div class="section-header">
                <h3 class="section-title"><i class="bi bi-heart-fill text-pink-500"></i> Favorites</h3>
            </div>
            <?php if(empty($favorites)): ?>
                <div class="empty-state-container">
                    <div class="empty-state-icon favorites">
                        <i class="bi bi-heart-break"></i>
                    </div>
                    <h3 class="empty-state-title">No Favorites Yet</h3>
                    <p class="empty-state-text">Browse listings and save your favorites to see them here.</p>
                    <a href="index" class="btn-gradient py-2.5 px-5 rounded-xl font-semibold inline-flex items-center gap-2">
                        <i class="bi bi-search"></i> Browse Listings
                    </a>
                </div>
            <?php else: ?>
                <div class="listing-cards-grid">
                    <?php foreach($favorites as $listing):
                        $type = 'favorite';
                        include 'listing_card.php';
                    endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Likes Tab -->
        <div id="tab-likes" class="tab-content hidden">
            <div class="section-header">
                <h3 class="section-title"><i class="bi bi-hand-thumbs-up-fill text-red-500"></i> Liked Listings</h3>
            </div>
            <?php if(empty($likes)): ?>
                <div class="empty-state-container">
                    <div class="empty-state-icon likes">
                        <i class="bi bi-hand-thumbs-down"></i>
                    </div>
                    <h3 class="empty-state-title">No Liked Listings</h3>
                    <p class="empty-state-text">Explore listings and like the ones you find interesting!</p>
                    <a href="index" class="btn-gradient py-2.5 px-5 rounded-xl font-semibold inline-flex items-center gap-2">
                        <i class="bi bi-search"></i> Find Something You Like
                    </a>
                </div>
            <?php else: ?>
                <div class="listing-cards-grid">
                    <?php foreach($likes as $listing):
                        $type = 'default';
                        include 'listing_card.php';
                    endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Comments Tab -->
        <div id="tab-comments" class="tab-content hidden">
            <div class="section-header">
                <h3 class="section-title"><i class="bi bi-chat-fill text-green-500"></i> Your Comments</h3>
            </div>
            <?php if(empty($comments)): ?>
                <div class="empty-state-container">
                    <div class="empty-state-icon comments">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                    <h3 class="empty-state-title">No Comments Yet</h3>
                    <p class="empty-state-text">Join the conversation! Comment on listings you've seen.</p>
                    <a href="index" class="btn-gradient py-2.5 px-5 rounded-xl font-semibold inline-flex items-center gap-2">
                        <i class="bi bi-search"></i> Browse Listings
                    </a>
                </div>
            <?php else: ?>
                <div class="comments-list">
                    <?php foreach($comments as $cm): ?>
                        <div class="comment-card">
                            <div class="comment-avatar">
                                <?=strtoupper(substr($user['name'], 0, 1))?>
                            </div>
                            <div class="comment-content">
                                <a href="view_listing?id=<?=$cm['listing_id']?>" class="comment-listing-title"><?=htmlspecialchars($cm['listing_title'])?></a>
                                <p class="comment-text"><?=nl2br(htmlspecialchars($cm['comment']))?></p>
                                <span class="comment-date"><i class="bi bi-clock"></i> <?=date('F j, Y g:i A', strtotime($cm['created_at']))?></span>
                            </div>
                            <a href="view_listing?id=<?=$cm['listing_id']?>" class="btn-gradient py-2 px-3 rounded-lg text-sm self-center">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    // Show selected tab
    document.getElementById('tab-' + tabName).classList.remove('hidden');
    
    // Reset all buttons to inactive style
    const buttons = ['profile', 'listings', 'favorites', 'likes', 'comments'];
    buttons.forEach(btn => {
        const element = document.getElementById('btn-' + btn);
        if (element) {
            element.classList.remove('active');
        }
    });
    
    // Set active button
    const activeBtn = document.getElementById('btn-' + tabName);
    if (activeBtn) {
        activeBtn.classList.add('active');
    }
}
</script>

<?php require 'footer.php'; ?>
