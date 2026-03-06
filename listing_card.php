<?php
/**
 * Reusable Listing Card Component
 * $listing - listing data array
 * $type - 'my' (own listings), 'favorite', 'liked', or default
 * $pdo - database connection
 */

if (!isset($listing)) return;

$l = $listing;
$imgs = $l['image'] ? explode(',', $l['image']) : [];
$first = $imgs ? $imgs[0] : 'https://via.placeholder.com/300?text=No+Image';

// Get likes count
$like_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE listing_id = ?");
$like_stmt->execute([$l['id']]);
$like_count = $like_stmt->fetchColumn();

// Get comments count
$comment_stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE listing_id = ?");
$comment_stmt->execute([$l['id']]);
$comment_count = $comment_stmt->fetchColumn();

// Get author info (profile picture)
$author_stmt = $pdo->prepare("SELECT id, name, profile_image FROM users WHERE id = ?");
$author_stmt->execute([$l['user_id']]);
$author = $author_stmt->fetch();
?>

<div class="relative bg-white dark:bg-gray-800 rounded shadow hover:shadow-lg transition duration-300">
    <img src="<?=htmlspecialchars($first)?>" class="w-full h-40 object-cover rounded-t" alt="<?=htmlspecialchars($l['title'])?>">
    
    <div class="p-4">
        <h5 class="font-semibold text-lg line-clamp-2">
            <a href="view_listing?id=<?=$l['id']?>" class="hover:text-blue-600 dark:hover:text-blue-400"><?=htmlspecialchars($l['title'])?></a>
        </h5>
        
        <?php if (isset($l['category'])): ?>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">📁 <?=htmlspecialchars($l['category'])?></p>
        <?php endif; ?>
        
        <?php if (isset($l['price']) && $l['price']): ?>
            <p class="text-green-600 dark:text-green-400 font-bold mt-2">₹<?=htmlspecialchars($l['price'])?></p>
        <?php endif; ?>
        
        <!-- Author Info with Profile Pic - COMPACT -->
        <?php if ($author): ?>
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <a href="user?id=<?=$author['id']?>" class="flex items-center gap-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded p-1 -m-1 transition">
                    <?php if (!empty($author['profile_image']) && file_exists(__DIR__.'/'.$author['profile_image'])): ?>
                        <img src="<?=htmlspecialchars($author['profile_image'])?>" alt="<?=htmlspecialchars($author['name'])?>" class="w-5 h-5 rounded-full object-cover">
                    <?php else: ?>
                        <div class="w-5 h-5 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-[10px] text-white font-bold">
                            <?=strtoupper(substr($author['name'], 0, 1))?>
                        </div>
                    <?php endif; ?>
                    <span class="text-xs text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"><?=htmlspecialchars(substr($author['name'], 0, 12))?></span>
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Stats Row -->
        <div class="flex justify-between items-center mt-2 text-xs text-gray-600 dark:text-gray-400">
            <div class="flex space-x-3">
                <span title="Likes">❤️ <?=$like_count?></span>
                <span title="Comments">💬 <?=$comment_count?></span>
            </div>
            <small class="text-gray-400"><?=date('M j', strtotime($l['created_at']))?></small>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-b flex gap-2">
        <a href="view_listing?id=<?=$l['id']?>" class="flex-1 text-center px-3 py-1 border border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400 rounded hover:bg-blue-50 dark:hover:bg-blue-900 transition text-sm font-medium">View</a>
        
        <?php if ($type === 'my'): ?>
            <a href="edit_listing?id=<?=$l['id']?>" class="flex-1 text-center px-3 py-1 border border-green-600 text-green-600 dark:text-green-400 dark:border-green-400 rounded hover:bg-green-50 dark:hover:bg-green-900 transition text-sm font-medium">Edit</a>
        <?php elseif ($type === 'favorite'): ?>
            <form method="post" action="favorites" class="flex-1">
                <input type="hidden" name="listing_id" value="<?=$l['id']?>">
                <button type="submit" name="remove_fav" class="w-full px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition text-sm font-medium" onclick="return confirm('Remove from favorites?')">Remove</button>
            </form>
        <?php endif; ?>
    </div>
</div>
