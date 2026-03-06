<?php
require_once 'config.php';
$user = current_user($pdo);
if (!$user) {
    header('Location: login'); exit;
}
$admin = !empty($_SESSION['admin']);

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: index'); exit;
}

$stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ?");
$stmt->execute([$id]);
$l = $stmt->fetch();
if (!$l) {
    $_SESSION['flash'] = 'Listing not found.';
    header('Location: index'); exit;
}

if (!$admin && $l['user_id'] != $user['id']) {
    $_SESSION['flash'] = 'Unauthorized.';
    header('Location: index'); exit;
}

$cats = $pdo->query("SELECT * FROM categories")->fetchAll();
$locs = $pdo->query("SELECT id, name, state FROM locations ORDER BY name ASC")->fetchAll();
$err = '';
$existingImages = [];
if ($l['image_path']) {
    $existingImages = array_filter(explode(',',$l['image_path']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        $err = 'Invalid form submission. Please try again.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $price = trim($_POST['price'] ?? '');
        $location_id = (int)($_POST['location_id'] ?? 0);
        $image_path = $l['image_path'];

        // Get location name from selected ID
        $location = '';
        if ($location_id) {
            $locStmt = $pdo->prepare("SELECT name FROM locations WHERE id = ?");
            $locStmt->execute([$location_id]);
            $location = $locStmt->fetchColumn() ?: $l['location'];
        } else {
            $location = $l['location'];
        }

        if (!empty($_POST['remove_images']) && is_array($_POST['remove_images'])) {
            foreach ($_POST['remove_images'] as $rem) {
                $idx = array_search($rem,$existingImages);
                if ($idx !== false) {
                    unset($existingImages[$idx]);
                    if (file_exists(__DIR__.'/'.$rem)) {
                        @unlink(__DIR__.'/'.$rem);
                    }
                }
            }
            $existingImages = array_values($existingImages);
            $image_path = implode(',',$existingImages);
        }

        if (!empty($_FILES['image']['name'])) {
            $allowed = ['jpg','jpeg','png','gif'];
            $paths = [];
            $names = (array)$_FILES['image']['name'];
            $tmps  = (array)$_FILES['image']['tmp_name'];
            $sizes = (array)$_FILES['image']['size'];
            $count = count($names);
            for ($i=0;$i<$count;$i++) {
                $name = $names[$i];
                $tmp = $tmps[$i];
                $size= $sizes[$i];
                if (!$name || !is_string($name)) continue;
                $ext = strtolower(pathinfo($name,PATHINFO_EXTENSION));
                if (!in_array($ext,$allowed) || $size>2*1024*1024) {
                    $err='Invalid image file.';
                    break;
                }
                $fname = uniqid('img_',true).'.'.$ext;
                $dest = __DIR__.'/uploads/'.$fname;
                if (move_uploaded_file($tmp,$dest)) {
                    $paths[]='uploads/'.$fname;
                } else { $err='Image upload failed'; break; }
            }
            if (!$err && $paths) {
                $combined = array_merge($existingImages, $paths);
                $image_path = implode(',',$combined);
            }
        }

        if (!$title || !$description || !$category_id) {
            $err = 'Please fill title, description and category.';
        } elseif (!$err) {
            $upd = $pdo->prepare("UPDATE listings SET category_id=?, title=?, description=?, price=?, location=?, image_path=? WHERE id=?");
            $upd->execute([$category_id,$title,$description,$price,$location,$image_path,$id]);
            $_SESSION['flash'] = 'Listing updated.';
            header('Location: view_listing?id='.$id); exit;
        }
    }
}

require 'header.php';
?>
<div class="max-w-3xl mx-auto py-10 px-4">
  <div class="card-custom shadow-lg">
    <div class="bg-green-600 text-white text-center rounded-t-lg p-4">
      <h3 class="mb-0"><i class="bi bi-pencil-square"></i> Edit Listing</h3>
    </div>
    <div class="p-6">
      <?php if($err) echo "<div class='bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 mb-4' role='alert'>$err</div>"; ?>
      <form method="post" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <div class="mb-4">
          <label class="block text-sm font-semibold dark:text-gray-300" for="title">Title <span class="text-red-500">*</span></label>
          <input name="title" id="title" value="<?=htmlspecialchars($l['title'])?>" class="w-full mt-1 p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-semibold dark:text-gray-300" for="category_id">Category <span class="text-red-500">*</span></label>
          <select name="category_id" id="category_id" class="w-full mt-1 p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
            <option value="">Choose a category</option>
            <?php foreach($cats as $c): ?>
              <option value="<?=$c['id']?>" <?= $l['category_id']==$c['id']?'selected':''?>><?=htmlspecialchars($c['name'])?></option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-semibold dark:text-gray-300" for="description">Description <span class="text-red-500">*</span></label>
          <textarea name="description" id="description" class="w-full mt-1 p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="6" required><?=htmlspecialchars($l['description'])?></textarea>
        </div>
        <div class="md:flex md:space-x-4">
          <div class="flex-1 mb-4">
            <label class="block text-sm font-semibold dark:text-gray-300" for="price">Price</label>
            <div class="flex">
              <span class="inline-flex items-center px-3 bg-gray-200 dark:bg-gray-600 rounded-l dark:text-gray-300">₹</span>
              <input name="price" id="price" value="<?=htmlspecialchars($l['price'])?>" class="flex-1 p-2 border rounded-r dark:bg-gray-700 dark:border-gray-600 dark:text-white" type="number" step="0.01">
            </div>
          </div>
          <div class="flex-1 mb-4">
            <label class="block text-sm font-semibold dark:text-gray-300" for="location_id">Location</label>
            <select name="location_id" id="location_id" class="w-full mt-1 p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white">
              <option value="">Select a location</option>
              <?php foreach($locs as $loc): 
                $locSelected = (strpos($l['location'], $loc['name']) !== false);
              ?>
                <option value="<?=$loc['id']?>" <?= $locSelected ? 'selected' : ''?>><?=htmlspecialchars($loc['name'])?><?php if($loc['state']): ?> - <?=htmlspecialchars($loc['state'])?><?php endif; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="mb-6">
          <label class="block text-sm font-semibold dark:text-gray-300" for="image">Images</label>
          <?php if(!empty($existingImages)): ?>
            <div class="grid grid-cols-3 gap-2 mb-2">
              <?php foreach($existingImages as $ei): ?>
                <div class="relative">
                  <img src="<?=htmlspecialchars($ei)?>" class="w-full h-24 object-cover rounded dark:border-gray-600">
                  <label class="absolute top-1 right-1 bg-white dark:bg-gray-800 bg-opacity-75 dark:bg-opacity-50 rounded-full p-1 cursor-pointer">
                    <input type="checkbox" name="remove_images[]" value="<?=htmlspecialchars($ei)?>" class="hidden remove-toggle">
                    <i class="bi bi-x-lg text-red-600 dark:text-red-400"></i>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
            <small class="text-gray-500 dark:text-gray-400">Check any images you want to remove.</small>
          <?php endif; ?>
          <input type="file" name="image[]" id="image" class="w-full mt-1 dark:text-gray-300" multiple accept="image/*">
          <div id="imagePreview" class="flex space-x-2 mt-2"></div>
          <small class="text-gray-500 dark:text-gray-400">Upload new pictures to add to the listing.</small>
        </div>
        <div class="text-center">
          <button class="inline-block px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition" type="submit">
            <i class="bi bi-save"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  (function(){
    const input = document.getElementById('image');
    const preview = document.getElementById('imagePreview');
    if(input && preview){
      input.addEventListener('change', () => {
        preview.innerHTML = '';
        Array.from(input.files).forEach(file => {
          if(!file.type.startsWith('image/')) return;
          const reader = new FileReader();
          reader.onload = e => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'h-20 w-20 object-cover rounded';
            preview.appendChild(img);
          };
          reader.readAsDataURL(file);
        });
      });
    }
  })();
  (function(){
    document.querySelectorAll('.remove-toggle').forEach(ch=>{
      ch.addEventListener('change', () => {
        const parent = ch.closest('div.relative');
        if(parent){
          parent.classList.toggle('opacity-50', ch.checked);
        }
      });
    });
  })();
</script>
<?php require 'footer.php'; ?>
