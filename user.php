<?php
require_once 'config.php';

$user_id = (int)($_GET['id'] ?? 0);
if (!$user_id) {
    header('Location: index'); 
    exit;
}

// Get user info
$stmt = $pdo->prepare("SELECT id, name, email, profile_image, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profile_user = $stmt->fetch();

if (!$profile_user) {
    require 'header.php';
    echo "<div class='max-w-4xl mx-auto py-12'><div class='bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4'>User not found.</div></div>";
    require 'footer.php';
    exit;
}

// Get user's listings
$listings_stmt = $pdo->prepare("SELECT l.*, c.name as category FROM listings l JOIN categories c ON l.category_id=c.id WHERE l.user_id = ? ORDER BY l.created_at DESC");
$listings_stmt->execute([$user_id]);
$listings = $listings_stmt->fetchAll();

// Get user stats
$total_listings = count($listings);
$likes_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes lk JOIN listings l ON lk.listing_id=l.id WHERE l.user_id = ?");
$likes_stmt->execute([$user_id]);
$total_likes = $likes_stmt->fetchColumn();

// Get current user 
$current_user = current_user($pdo);
$is_own_profile = $current_user && $current_user['id'] == $user_id;

require 'header.php';
?>

<div class="max-w-6xl mx-auto py-10 px-4">
    <!-- Profile Header - COMPACT -->
    <div class="card-custom p-4 mb-6">
        <div class="flex items-center gap-4">
            <!-- Profile Image - SMALLER -->
            <div class="flex-shrink-0">
                <?php if(!empty($profile_user['profile_image']) && file_exists(__DIR__.'/'.$profile_user['profile_image'])): ?>
                    <img src="<?=htmlspecialchars($profile_user['profile_image'])?>" alt="Profile" class="w-16 h-16 rounded-full object-cover border-2 border-blue-500 shadow">
                <?php else: ?>
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-2xl text-white font-bold border-2 border-white shadow">
                        <?=strtoupper(substr($profile_user['name'], 0, 1))?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- User Info -->
            <div class="flex-1">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white"><?=htmlspecialchars($profile_user['name'])?></h1>
                <p class="text-sm text-gray-600 dark:text-gray-400"><?=htmlspecialchars($profile_user['email'])?></p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1"><i class="bi bi-calendar"></i> Member since <?=date('F j, Y', strtotime($profile_user['created_at']))?></p>
                
                <!-- Stats - COMPACT -->
                <div class="flex gap-4 mt-2">
                    <div>
                        <span class="text-sm font-bold text-blue-600"><?=$total_listings?></span>
                        <span class="text-xs text-gray-500">Listings</span>
                    </div>
                    <div>
                        <span class="text-sm font-bold text-pink-600"><?=$total_likes?></span>
                        <span class="text-xs text-gray-500">Likes</span>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex-shrink-0">
                <?php if($is_own_profile): ?>
                    <a href="dashboard" class="px-3 py-1.5 bg-blue-300 text-white rounded text-sm hover:bg-blue-700 transition">
                        <i class="bi bi-gear"></i>
                    </a>
                <?php else: ?>
                    <a href="index" class="px-3 py-1.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        <i class="bi bi-search"></i> Browse
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- User's Listings -->
    <h2 class="text-xl font-bold mb-4 dark:text-white"><?=htmlspecialchars($profile_user['name'])?>'s Listings</h2>
    
    <?php if(empty($listings)): ?>
        <div class="card-custom p-4 text-center">
            <p class="text-gray-600 dark:text-gray-400 text-sm">This user hasn't posted any listings yet.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach($listings as $listing):
                $type = ($current_user && $listing['user_id'] == $current_user['id']) ? 'my' : 'default';
                include 'listing_card.php';
            endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>
