<?php
require_once 'config.php';
require_once 'header.php';
date_default_timezone_set('Asia/Kolkata');

$q = $_GET['q'] ?? '';
$cat = $_GET['cat'] ?? '';
$loc = $_GET['loc'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 12;
$offset = ($page - 1) * $per_page;

$sql = "SELECT l.*, c.name as category, u.name as author, u.profile_image as author_image FROM listings l
  JOIN categories c ON l.category_id = c.id
  JOIN users u ON l.user_id = u.id
  WHERE 1=1 ";

$user = current_user($pdo);
$params = [];

if ($q) {
    $sql .= " AND (l.title LIKE ? OR l.description LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($cat) {
    $sql .= " AND c.id = ?";
    $params[] = $cat;
}

if ($loc) {
    $sql .= " AND l.location = ?";
    $params[] = $loc;
}

if ($min_price !== '') {
    $sql .= " AND (l.price >= ? OR (l.price IS NULL OR l.price = ''))";
    $params[] = (int)$min_price;
}

if ($max_price !== '') {
    $sql .= " AND (l.price <= ? OR (l.price IS NULL OR l.price = ''))";
    $params[] = (int)$max_price;
}

// Get total count for pagination
$count_sql = str_replace("SELECT l.*, c.name as category, u.name as author, u.profile_image as author_image", "SELECT COUNT(*)", $sql);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_count = $count_stmt->fetchColumn();
$total_pages = ceil($total_count / $per_page);

$sql .= " ORDER BY l.created_at DESC LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

$cats = $pdo->query("SELECT * FROM categories")->fetchAll();
$locs = $pdo->query("SELECT DISTINCT name as location FROM locations ORDER BY name ASC")->fetchAll();
?>

<!-- HERO -->
<div class="hero text-center py-12 md:py-16 mb-8 relative overflow-hidden">
  <div class="absolute inset-0 opacity-30">
    <div class="absolute top-10 left-10 w-32 h-32 bg-blue-400 rounded-full mix-blend-multiply filter blur-xl animate-pulse"></div>
    <div class="absolute top-10 right-10 w-32 h-32 bg-purple-400 rounded-full mix-blend-multiply filter blur-xl animate-pulse" style="animation-delay: 2s;"></div>
    <div class="absolute -bottom-8 left-20 w-32 h-32 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl animate-pulse" style="animation-delay: 4s;"></div>
  </div>
  <div class="max-w-4xl mx-auto px-4 relative z-10">
    <h1 class="text-4xl md:text-5xl font-bold mb-4 bg-gradient-to-r from-blue-600 via-purple-500 to-pink-500 bg-clip-text text-transparent dark:from-blue-400 dark:via-purple-400 dark:to-pink-400">
Welcome to Vikrayam
    </h1>
    <p class="mt-2 text-lg md:text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-8">
      Discover amazing deals and sell your items in your local community. Connect with buyers and sellers near you!
    </p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="create_listing"
         class="btn-gradient px-8 py-4 rounded-xl font-semibold text-lg inline-flex items-center justify-center gap-2">
         <i class="bi bi-plus-circle"></i> Post a Listing
      </a>
      <a href="#listings"
         class="px-8 py-4 rounded-xl font-semibold text-lg inline-flex items-center justify-center gap-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition-all shadow-lg hover:shadow-xl border-2 border-gray-200 dark:border-gray-600">
         <i class="bi bi-search"></i> Browse Listings
      </a>
    </div>
    <!-- Stats -->
    <div class="mt-12 flex justify-center gap-8 md:gap-16">
      <div class="text-center">
        <div class="text-3xl md:text-4xl font-bold text-blue-600 dark:text-blue-400"><?= $pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn() ?: 0 ?></div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Listings</div>
      </div>
      <div class="text-center">
        <div class="text-3xl md:text-4xl font-bold text-purple-600 dark:text-purple-400"><?= $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0 ?></div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Users</div>
      </div>
      <div class="text-center">
        <div class="text-3xl md:text-4xl font-bold text-pink-600 dark:text-pink-400"><?= $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn() ?: 0 ?></div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Categories</div>
      </div>
    </div>
  </div>
</div>

<!-- SEARCH -->
<div class="card-custom p-6 mb-6">
  <h4 class="mb-4 text-center text-xl font-semibold dark:text-white">
    <i class="bi bi-search"></i> Search Listings
  </h4>

  <form method="get"
        class="space-y-4 md:space-y-0 md:flex md:items-end md:gap-4">

    <div class="flex-1">
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Search</label>
      <input type="text" name="q"
             class="w-full mt-1 p-2 rounded-lg shadow-inner bg-gray-100 dark:bg-gray-700 dark:text-white"
             placeholder="Search by title or description"
             value="<?=htmlspecialchars($q)?>">
    </div>

    <div class="flex-1">
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Category</label>
      <select name="cat" class="w-full mt-1 p-2 rounded-lg bg-gray-100 dark:bg-gray-700 dark:text-white">
        <option value="">All categories</option>
        <?php foreach($cats as $c): ?>
          <option value="<?=$c['id']?>"
            <?= $cat==$c['id'] ? 'selected' : '' ?>>
            <?=htmlspecialchars($c['name'])?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="flex-1">
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Location</label>
      <select name="loc" class="w-full mt-1 p-2 rounded-lg bg-gray-100 dark:bg-gray-700 dark:text-white">
        <option value="">All locations</option>
        <?php foreach($locs as $l): ?>
          <option value="<?=htmlspecialchars($l['location'])?>"
            <?= $loc==htmlspecialchars($l['location']) ? 'selected' : '' ?>>
            <?=htmlspecialchars($l['location'])?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    
    <div class="flex-1">
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Price Range</label>
      <div class="flex gap-2 mt-1">
        <input type="number" name="min_price" min="0"
               class="w-full p-2 rounded-lg bg-gray-100 dark:bg-gray-700 dark:text-white"
               placeholder="Min" value="<?=htmlspecialchars($min_price)?>">
        <input type="number" name="max_price" min="0"
               class="w-full p-2 rounded-lg bg-gray-100 dark:bg-gray-700 dark:text-white"
               placeholder="Max" value="<?=htmlspecialchars($max_price)?>">
      </div>
    </div>

    <div>
      <button type="submit"
        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
        Search
      </button>
    </div>
  </form>
</div>

<?php if(empty($listings)): ?>

  <div class="card-custom p-6 text-center">
    <p class="text-gray-600 dark:text-gray-400">No listings found. Try adjusting your search criteria.</p>
  </div>

<?php else: ?>

<?php
// Favorites of logged-in user
$fav_ids = [];
if ($user) {
  $favs = $pdo->prepare("SELECT listing_id FROM favorites WHERE user_id=?");
  $favs->execute([$user['id']]);
  $fav_ids = array_column($favs->fetchAll(), 'listing_id');
}
?>

<div class="flex justify-between items-center mb-4">
  <p class="text-gray-600 dark:text-gray-400">Showing <?=count($listings)?> of <?=$total_count?> listings</p>
</div>

<div class="flex flex-wrap -mx-2">

<?php foreach($listings as $l):
        // compute like/comment counts for display
        $lcStmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE listing_id=?");
        $lcStmt->execute([$l['id']]);
        $lc = $lcStmt->fetchColumn();
        $ccStmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE listing_id=?");
        $ccStmt->execute([$l['id']]);
        $cc = $ccStmt->fetchColumn();
?>
  <div class="w-full md:w-1/2 lg:w-1/3 px-2">
    <div class="card-listing h-full mb-6 relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-md hover:shadow-2xl transition-shadow duration-300">

      <?php if (!empty($l['image'])): ?>
        <?php
          $imgs = array_filter(explode(',', $l['image']));
          $first = $imgs[0] ?? '';
        ?>
        <div class="relative listing-images group" data-images="<?=htmlspecialchars(json_encode($imgs))?>">
          <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent z-10"></div>
          <img src="<?=htmlspecialchars($first)?>" class="w-full object-cover main-img" style="height:200px;">
          <span class="absolute bottom-3 left-3 bg-blue-600 text-white text-xs font-semibold px-3 py-1 rounded-full z-20"><?=htmlspecialchars($l['category'])?></span>
          <?php if ($l['price']): ?>
            <span class="absolute top-3 right-3 bg-green-500 text-white font-bold px-4 py-2 rounded-lg z-20 shadow-lg">₹<?=htmlspecialchars($l['price'])?></span>
          <?php else: ?>
            <span class="absolute top-3 right-3 bg-yellow-500 text-gray-900 font-bold px-4 py-2 rounded-lg z-20 shadow-lg">Free</span>
          <?php endif; ?>
          <?php if (count($imgs) > 1): ?>
            <button class="prev absolute left-3 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 rounded-full p-2 z-20 opacity-0 group-hover:opacity-100 transition-opacity"><i class="bi bi-chevron-left font-bold"></i></button>
            <button class="next absolute right-3 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 rounded-full p-2 z-20 opacity-0 group-hover:opacity-100 transition-opacity"><i class="bi bi-chevron-right font-bold"></i></button>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="p-4">
        <div class="flex justify-between items-start gap-2 mb-3">
          <div class="flex-1">
            <a href="view_listing?id=<?=$l['id']?>" class="text-lg font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors line-clamp-2"><?=htmlspecialchars($l['title'])?></a>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1"><span class="inline-block mr-3"><i class="bi bi-heart-fill text-red-500"></i> <?=$lc?></span><span class="inline-block"><i class="bi bi-chat-fill text-blue-400"></i> <?=$cc?></span></div>
          </div>
          <?php if ($user): ?>
            <div class="flex items-center gap-1">
              <button class="fav-btn text-xl transition-colors" data-id="<?=$l['id']?>" style="color:<?=in_array($l['id'],$fav_ids)?'#e0245e':'#ccc'?>;">
                <i class="bi <?=in_array($l['id'],$fav_ids)?'bi-heart-fill':'bi-heart'?>"></i>
              </button>
              <?php if($user['id']==$l['user_id'] || !empty($_SESSION['admin'])): ?>
                <a href="edit_listing?id=<?=$l['id']?>" class="text-green-600 hover:text-green-800 transition-colors text-lg" title="Edit"><i class="bi bi-pencil-square"></i></a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="flex flex-wrap gap-2 mb-3">
          <span class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300 text-xs font-medium px-3 py-1 rounded-full"><?=htmlspecialchars($l['category'])?></span>
          <?php if ($l['location']): ?><span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium px-3 py-1 rounded-full"><i class="bi bi-geo-alt"></i> <?=htmlspecialchars($l['location'])?></span><?php endif; ?>
        </div>
        <p class="mt-2 text-gray-600 dark:text-gray-400 text-sm leading-relaxed mb-3 line-clamp-2"><?=htmlspecialchars(substr($l['description'],0,80))?>...</p>
        
        <!-- Author Section - COMPACT -->
        <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-1">
            <a href="user?id=<?=$l['user_id']?>" class="text-[10px] font-semibold text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"><?=htmlspecialchars(substr($l['author'],0,10))?></a>
            <span class="text-[10px] text-gray-400">· <?=date('M d', strtotime($l['created_at']))?></span>
          </div>
          <a href="view_listing?id=<?=$l['id']?>" class="px-2 py-1 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition-colors shadow-sm">View</a>
        </div>
      </div>

    </div>

  </div>
<?php endforeach; ?>

</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav class="flex justify-center mt-8" aria-label="Pagination">
  <ul class="flex gap-2">
    <?php if ($page > 1): ?>
      <li>
        <a href="?<?=http_build_query(array_merge($_GET, ['page' => $page - 1]))?>" class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">Previous</a>
      </li>
    <?php endif; ?>
    
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <?php if ($i == $page): ?>
        <li><span class="px-4 py-2 bg-blue-600 text-white rounded-lg"><?=$i?></span></li>
      <?php elseif ($i <= 3 || $i > $total_pages - 3 || abs($i - $page) < 2): ?>
        <li><a href="?<?=http_build_query(array_merge($_GET, ['page' => $i]))?>" class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition"><?=$i?></a></li>
      <?php elseif ($i == 4 || $i == $total_pages - 3): ?>
        <li><span class="px-4 py-2 text-gray-500">...</span></li>
      <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($page < $total_pages): ?>
      <li>
        <a href="?<?=http_build_query(array_merge($_GET, ['page' => $page + 1]))?>" class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">Next</a>
      </li>
    <?php endif; ?>
  </ul>
</nav>
<?php endif; ?>

<?php endif; ?>

<!-- FAVORITE SCRIPT -->
<script>
// image carousel for cards
(function(){
  document.querySelectorAll('.listing-images').forEach(wrapper => {
    const imgs = JSON.parse(wrapper.dataset.images || '[]');
    let idx = 0;
    const imgEl = wrapper.querySelector('.main-img');
    const prev = wrapper.querySelector('.prev');
    const next = wrapper.querySelector('.next');
    const update = () => { if(imgEl) imgEl.src = imgs[idx]; };
    if(prev) prev.addEventListener('click', e=>{ e.stopPropagation(); idx=(idx-1+imgs.length)%imgs.length; update(); });
    if(next) next.addEventListener('click', e=>{ e.stopPropagation(); idx=(idx+1)%imgs.length; update(); });
  });
})();

document.querySelectorAll('.fav-btn').forEach(btn => {
  btn.onclick = function(e) {
    e.preventDefault();
    const id = this.getAttribute('data-id');

    fetch('favorite.php?id=' + id)
      .then(r => r.text())
      .then(res => {
        const icon = this.querySelector('i');

        if(res === 'added') {
          icon.className = 'bi bi-heart-fill';
          this.style.color = '#e0245e';
        } else {
          icon.className = 'bi bi-heart';
          this.style.color = '#bbb';
        }
      });
  }
});
</script>

<?php require 'footer.php'; ?>
