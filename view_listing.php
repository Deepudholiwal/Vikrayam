<?php
require_once 'config.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT l.*, c.name as category, u.name as author, u.email as author_email, u.profile_image as author_profile_image, u.id as author_id FROM listings l
            JOIN categories c ON l.category_id=c.id
            JOIN users u ON l.user_id=u.id
            WHERE l.id = ?");
$stmt->execute([$id]);
$l = $stmt->fetch();
$user = current_user($pdo);

// like/comment table handling (POST) - only check CSRF on POST requests
if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token for actions
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        // Invalid CSRF - show error message instead of redirecting
        $_SESSION['flash'] = 'Invalid form submission. Please try again.';
        header('Location: view_listing?id='.$id);
        exit;
    }
    
    if (isset($_POST['toggle_like'])) {
        $chk = $pdo->prepare("SELECT 1 FROM likes WHERE listing_id=? AND user_id=?");
        $chk->execute([$id,$user['id']]);
        if ($chk->fetch()) {
            $pdo->prepare("DELETE FROM likes WHERE listing_id=? AND user_id=?")->execute([$id,$user['id']]);
        } else {
            $pdo->prepare("INSERT IGNORE INTO likes (listing_id,user_id) VALUES (?,?)")->execute([$id,$user['id']]);
        }
        header('Location: view_listing?id='.$id); exit;
    }
    if (isset($_POST['comment']) && trim($_POST['comment'])!=='') {
        $c = trim($_POST['comment']);
        $pdo->prepare("INSERT INTO comments (listing_id,user_id,comment) VALUES (?,?,?)")->execute([$id,$user['id'],$c]);
        header('Location: view_listing?id='.$id.'#comments'); exit;
    }
}

// Handle delete
if ($l && $user && $l['user_id'] == $user['id'] && isset($_POST['delete'])) {
  // Verify CSRF token for delete action
  $csrfToken = $_POST['csrf_token'] ?? '';
  if (!verify_csrf($csrfToken)) {
    $_SESSION['flash'] = 'Invalid form submission. Please try again.';
    header('Location: view_listing?id='.$id);
    exit;
  }
  $del = $pdo->prepare("DELETE FROM listings WHERE id = ? AND user_id = ?");
  $del->execute([$id, $user['id']]);
  $_SESSION['flash'] = 'Listing deleted.';
  header('Location: index'); exit;
}
// prepare like/comment data
$like_count = 0;
$user_liked = false;
$comments = [];
if ($l) {
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE listing_id = ?");
    $stmt2->execute([$id]);
    $like_count = $stmt2->fetchColumn();
    if ($user) {
        $chk = $pdo->prepare("SELECT 1 FROM likes WHERE listing_id=? AND user_id=?");
        $chk->execute([$id,$user['id']]);
        $user_liked = (bool)$chk->fetchColumn();
    }
    $cstmt = $pdo->prepare("SELECT c.comment, c.created_at, u.name FROM comments c JOIN users u ON u.id = c.user_id WHERE c.listing_id = ? ORDER BY c.created_at ASC");
    $cstmt->execute([$id]);
    $comments = $cstmt->fetchAll();
}

require 'header.php';

if (!$l) {
  echo "<div class='max-w-4xl mx-auto py-12'><div class='bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4' role='alert'>Listing not found.</div></div>";
  require 'footer.php';
  exit;
}
?>
<div class="max-w-6xl mx-auto py-5 px-4 md:flex md:space-x-6">
    <div class="md:w-2/3">
      <div class="card-custom shadow-lg">
        <div class="p-6">
          <?php if (!empty($l['image'])): ?>
            <?php
              $imgs = array_filter(explode(',', $l['image']));
              $first = $imgs[0] ?? '';
            ?>
            <div class="mb-6 relative listing-gallery" data-images="<?=htmlspecialchars(json_encode($imgs))?>">
              <img src="<?=htmlspecialchars($first)?>" class="w-full mb-6 rounded main-img" style="max-height:400px;object-fit:contain;" alt="Listing image">
              <?php if(count($imgs) > 1): ?>
                <button class="prev absolute left-2 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-75 rounded-full p-2">
                  <i class="bi bi-chevron-left"></i>
                </button>
                <button class="next absolute right-2 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-75 rounded-full p-2">
                  <i class="bi bi-chevron-right"></i>
                </button>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <h1 class="mb-3 text-2xl font-bold"><?=htmlspecialchars($l['title'])?></h1>
          <div class="mb-3 space-x-2">
            <span class="inline-block bg-blue-200 text-blue-800 text-xs px-2 py-1 rounded">Category: <?=htmlspecialchars($l['category'])?></span>
            <?php if ($l['location']): ?>
              <span class="inline-block bg-gray-200 text-gray-800 text-xs px-2 py-1 rounded">Location: <?=htmlspecialchars($l['location'])?></span>
            <?php endif; ?>
            <?php if ($l['price']): ?>
              <span class="inline-block bg-green-200 text-green-800 text-xs px-2 py-1 rounded">Price: ₹<?=htmlspecialchars($l['price'])?></span>
            <?php else: ?>
              <span class="inline-block bg-yellow-200 text-yellow-800 text-xs px-2 py-1 rounded">Free</span>
            <?php endif; ?>
          </div>
          <p class="text-gray-600 mb-4"><?=nl2br(htmlspecialchars($l['description']))?></p>
          <hr class="my-4">
          <!-- Author Section - COMPACT -->
          <div class="flex items-center mb-3">
            <?php if(!empty($l['author_profile_image']) && file_exists(__DIR__.'/'.$l['author_profile_image'])): ?>
                <img src="<?=htmlspecialchars($l['author_profile_image'])?>" alt="<?=htmlspecialchars($l['author'])?>" class="w-8 h-8 rounded-full object-cover mr-2">
            <?php else: ?>
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold mr-2">
                    <?=strtoupper(substr($l['author'], 0, 1))?>
                </div>
            <?php endif; ?>
            <div>
              <a href="user?id=<?=$l['author_id']?>" class="hover:text-blue-600 font-semibold"><?=htmlspecialchars($l['author'])?></a>
              <a href="mailto:<?=htmlspecialchars($l['author_email'])?>" class="text-blue-600 hover:underline text-sm ml-2">
                <i class="bi bi-envelope"></i>
              </a>
            </div>
          </div>
          <p class="text-gray-500 mb-0 text-sm">
            <i class="bi bi-calendar"></i> Posted on <?=date('F j, Y', strtotime($l['created_at']))?>
          </p>
        </div>
      </div>
    </div>
    <div class="md:w-1/3 mt-6 md:mt-0">
      <div class="card-custom mb-6">
        <div class="p-4 border-b">
          <h5 class="font-semibold">Actions</h5>
        </div>
        <div class="p-4 space-y-2">
          <?php if($user): ?>
            <form method="post">
              <?= csrf_field() ?>
              <button name="toggle_like" class="block w-full text-center px-4 py-2 border border-red-400 text-red-600 rounded hover:bg-red-50 transition">
                <i class="bi bi-heart" style="color:<?= $user_liked?'#e0245e':''?>"></i> Like (<?=$like_count?>)
              </button>
            </form>
          <?php else: ?>
            <div class="block w-full text-center px-4 py-2 border border-gray-300 text-gray-600 rounded">
              <i class="bi bi-heart"></i> Likes: <?=$like_count?> (<a href="login" class="text-blue-600 hover:underline">login to like</a>)
            </div>
          <?php endif; ?>
          <button type="button" onclick="shareListing()" class="block w-full text-center px-4 py-2 border border-blue-400 text-blue-600 rounded hover:bg-blue-50 transition">
            <i class="bi bi-share-fill mr-1"></i> Share
          </button>
          <a href="mailto:<?=htmlspecialchars($l['author_email'])?>?subject=Inquiry about <?=urlencode($l['title'])?>" class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
            <i class="bi bi-envelope mr-1"></i> Contact Seller
          </a>
          <?php if($user && ($user['id']==$l['user_id'] || !empty($_SESSION['admin']))): ?>
            <a href="edit_listing?id=<?=$l['id']?>" class="block w-full text-center mt-2 px-4 py-2 border border-gray-600 text-gray-600 rounded hover:bg-gray-100 transition">
              <i class="bi bi-pencil"></i> Edit Listing
            </a>
          <?php endif; ?>
          <button class="block w-full text-center px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 transition" onclick="window.print()">
            <i class="bi bi-printer mr-1"></i> Print
          </button>
        </div>
      </div>
      <?php if ($user && $l['user_id'] == $user['id']): ?>
        <div class="card-custom">
          <div class="p-4 bg-yellow-200 text-yellow-800 font-semibold">Manage Listing</div>
          <div class="p-4">
            <button class="block w-full text-center px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition" id="deleteBtn">
              <i class="bi bi-trash mr-1"></i> Delete Listing
            </button>
          </div>
        </div>
      <?php endif; ?>
    </div>
</div>

<div class="max-w-6xl mx-auto py-6 px-4" id="comments">
  <h3 class="text-xl font-semibold mb-4">Comments (<?=count($comments)?>)</h3>
  <?php if($comments): foreach($comments as $c): ?>
    <div class="mb-4 p-4 bg-gray-100 rounded">
      <strong><?=htmlspecialchars($c['name'])?></strong> <small class="text-gray-500"><?=date('F j, Y g:i A',strtotime($c['created_at']))?></small>
      <p class="mt-1"><?=nl2br(htmlspecialchars($c['comment']))?></p>
    </div>
  <?php endforeach; else: ?>
    <p class="text-gray-600">No comments yet.</p>
  <?php endif; ?>

  <?php if($user): ?>
      <form method="post" class="mt-4">
        <?= csrf_field() ?>
        <textarea name="comment" rows="3" class="w-full p-2 border rounded" placeholder="Leave a comment..." required></textarea>
        <button type="submit" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Post Comment</button>
      </form>
  <?php else: ?>
      <p><a href="login" class="text-blue-600 hover:underline">Log in</a> to add a comment.</p>
  <?php endif; ?>
</div>

<?php if ($user && $l['user_id'] == $user['id']): ?>
<!-- simple tailwind confirm dialog -->
<div id="deleteDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
  <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
    <h3 class="text-xl font-semibold mb-4">Delete Listing</h3>
    <p class="mb-4">Are you sure you want to delete this listing? This action cannot be undone.</p>
    <div class="flex justify-end space-x-3">
      <button id="cancelDelete" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
      <form method="post" class="inline">
        <?= csrf_field() ?>
        <button type="submit" name="delete" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
      </form>
    </div>
  </div>
</div>
<script>
  document.getElementById('deleteBtn').addEventListener('click', function() {
    document.getElementById('deleteDialog').classList.remove('hidden');
  });
  document.getElementById('cancelDelete').addEventListener('click', function() {
    document.getElementById('deleteDialog').classList.add('hidden');
  });
</script>
<?php endif; ?>
</div>
<script>
// carousel for view listing gallery
(function(){
  const wrapper = document.querySelector('.listing-gallery');
  if(wrapper){
    const imgs = JSON.parse(wrapper.dataset.images || '[]');
    let idx = 0;
    const imgEl = wrapper.querySelector('.main-img');
    const prev = wrapper.querySelector('.prev');
    const next = wrapper.querySelector('.next');
    const update = () => { if(imgEl) imgEl.src = imgs[idx]; };
    if(prev) prev.addEventListener('click', e=>{ e.stopPropagation(); idx=(idx-1+imgs.length)%imgs.length; update(); });
    if(next) next.addEventListener('click', e=>{ e.stopPropagation(); idx=(idx+1)%imgs.length; update(); });
    if(imgEl) imgEl.addEventListener('click', ()=>{ if(imgs.length>1){ idx=(idx+1)%imgs.length; update(); } });
  }
})();

// share helper
function shareListing(){
  const url = window.location.href;
  if(navigator.share){
    navigator.share({ title: <?=json_encode($l['title'])?>, url });
  } else if(navigator.clipboard){
    navigator.clipboard.writeText(url).then(()=>{
      alert('Link copied to clipboard');
    });
  } else {
    prompt('Copy this link', url);
  }
}
</script>
<?php require 'footer.php'; ?>
