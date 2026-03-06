<?php
require 'config.php';
if (!is_logged_in()) {
    header('Location: login'); exit;
}
$user = current_user($pdo);
$stmt = $pdo->prepare("SELECT l.*, c.name as category FROM listings l JOIN categories c ON l.category_id=c.id WHERE l.user_id = ? ORDER BY l.created_at DESC");
$stmt->execute([$user['id']]);
$listings = $stmt->fetchAll();
require 'header.php';
?>
<div class="p-4">
  <h3 class="text-2xl font-semibold mb-4">My Listings</h3>
  <?php if (empty($listings)): ?>
    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4">You have not posted any listings yet.</div>
  <?php else: ?>
    <div class="flex flex-wrap -mx-2">
      <?php foreach($listings as $l):
            $lcStmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE listing_id=?");
            $lcStmt->execute([$l['id']]);
            $lc = $lcStmt->fetchColumn();
            $ccStmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE listing_id=?");
            $ccStmt->execute([$l['id']]);
            $cc = $ccStmt->fetchColumn();
      ?>
        <div class="w-full md:w-1/2 px-2">
          <div class="card-listing h-full mb-4 relative bg-white rounded-2xl shadow-md hover:shadow-2xl transition-shadow duration-300 overflow-hidden">
            <?php if (!empty($l['image'])): ?>
              <?php
                $imgs = array_filter(explode(',', $l['image']));
                $first = $imgs[0] ?? '';
              ?>
              <div class="relative listing-images group" data-images="<?=htmlspecialchars(json_encode($imgs))?>">
                <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent z-10"></div>
                <img src="<?=htmlspecialchars($first)?>" class="w-full main-img" style="height:250px;object-fit:cover;">
                <span class="absolute bottom-2 left-2 bg-blue-600 text-white text-xs font-semibold px-3 py-1 rounded-full z-20"><?=htmlspecialchars($l['category'])?></span>
                <?php if(count($imgs) > 1): ?>
                  <button class="prev absolute left-2 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 rounded-full p-2 z-20 opacity-0 group-hover:opacity-100 transition-opacity"><i class="bi bi-chevron-left"></i></button>
                  <button class="next absolute right-2 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 rounded-full p-2 z-20 opacity-0 group-hover:opacity-100 transition-opacity"><i class="bi bi-chevron-right"></i></button>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <div class="p-4">
              <h5 class="text-lg font-bold text-gray-900 hover:text-blue-600 transition-colors mb-1">
                <a href="view_listing?id=<?=$l['id']?>" class="hover:text-blue-600"><?=htmlspecialchars($l['title'])?></a>
                <div class="text-sm text-gray-500 mt-1">
                  <span class="inline-block mr-3"><i class="bi bi-heart-fill text-red-500"></i> <?=$lc?></span>
                  <span class="inline-block"><i class="bi bi-chat-fill text-blue-400"></i> <?=$cc?></span>
                </div>
              </h5>
              <div class="mb-3 flex flex-wrap gap-2">
                <span class="inline-block bg-blue-100 text-blue-800 text-xs font-medium px-3 py-1 rounded-full"><?=htmlspecialchars($l['category'])?></span>
                <?php if ($l['location']): ?>
                  <span class="inline-block bg-gray-100 text-gray-700 text-xs font-medium px-3 py-1 rounded-full"><i class="bi bi-geo-alt"></i> <?=htmlspecialchars($l['location'])?></span>
                <?php endif; ?>
              </div>
              <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?=htmlspecialchars(substr($l['description'],0,100))?>...</p>
              <div class="flex flex-wrap gap-2 pt-3 border-t border-gray-100">
                <a href="view_listing?id=<?=$l['id']?>" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 transition">View</a>
                <a href="edit_listing?id=<?=$l['id']?>" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 transition">Edit</a>
                <form method="post" action="view_listing?id=<?=$l['id']?>" onsubmit="return confirm('Delete this listing?');" class="contents">
                  <?= csrf_field() ?>
                  <button type="submit" name="delete" class="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700 transition">Delete</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<script>
// card carousel for listings in my_listings
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
</script>
<?php require 'footer.php'; ?>
