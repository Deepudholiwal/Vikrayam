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
$imageKey = isset($l['image_path']) ? 'image_path' : (isset($l['image']) ? 'image' : '');
if (!empty($imageKey) && !empty($l[$imageKey])) {
    $existingImages = array_filter(explode(',',$l[$imageKey]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        $err = 'Invalid form submission. Please try again.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $price = !empty(trim($_POST['price'] ?? '')) ? trim($_POST['price']) : null;
        $location_id = (int)($_POST['location_id'] ?? 0);
        
        // Determine which image column exists
        $imageKey = isset($l['image_path']) ? 'image_path' : (isset($l['image']) ? 'image' : '');
        $image_path = !empty($imageKey) ? $l[$imageKey] : '';

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
                    $err='Invalid image file (jpg, png, gif, max 2MB).';
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
            // Try image_path first, fallback to image
            try {
                $upd = $pdo->prepare("UPDATE listings SET category_id=?, title=?, description=?, price=?, location=?, image_path=? WHERE id=?");
                $upd->execute([$category_id,$title,$description,$price,$location,$image_path,$id]);
            } catch (Exception $e) {
                // Fallback to 'image' column
                $upd = $pdo->prepare("UPDATE listings SET category_id=?, title=?, description=?, price=?, location=?, image=? WHERE id=?");
                $upd->execute([$category_id,$title,$description,$price,$location,$image_path,$id]);
            }
            $_SESSION['flash'] = 'Listing updated.';
            header('Location: view_listing?id='.$id); exit;
        }
    }
}

require 'header.php';
?>
<div class="min-h-screen py-10 px-4">
  <div class="max-w-3xl mx-auto">
    <div class="auth-card shadow-2xl">
      <!-- Header -->
      <div class="auth-header" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/20 mb-3">
          <i class="bi bi-pencil-square text-3xl text-white"></i>
        </div>
        <h3 class="text-2xl font-bold"><i class="bi bi-pencil"></i> Edit Listing</h3>
        <p class="text-white/80 text-sm mt-1">Update your listing details</p>
      </div>
      
      <!-- Form Body -->
      <div class="auth-body">
        <?php if($err): ?>
          <div class="bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 mb-4 rounded-lg">
            <i class="bi bi-exclamation-circle"></i> <?=$err?>
          </div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data" novalidate>
          <?= csrf_field() ?>
          
          <!-- Title -->
          <div class="mb-5">
            <label for="title" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              <i class="bi bi-card-heading"></i> Title <span class="text-red-500">*</span>
            </label>
            <input name="title" id="title" type="text" 
                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                   value="<?=htmlspecialchars($l['title'])?>" required>
          </div>
          
          <!-- Category -->
          <div class="mb-5">
            <label for="category_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              <i class="bi bi-tags"></i> Category <span class="text-red-500">*</span>
            </label>
            <select name="category_id" id="category_id" 
                    class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                    required>
              <option value="">Choose a category</option>
              <?php foreach($cats as $c): ?>
                <option value="<?=$c['id']?>" <?= $l['category_id']==$c['id']?'selected':''?>><?=htmlspecialchars($c['name'])?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <!-- Description -->
          <div class="mb-5">
            <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              <i class="bi bi-text-paragraph"></i> Description <span class="text-red-500">*</span>
            </label>
            <textarea name="description" id="description" 
                      class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                      rows="5" required><?=htmlspecialchars($l['description'])?></textarea>
          </div>
          
          <!-- Price & Location Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
            <!-- Price -->
            <div>
              <label for="price" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                <i class="bi bi-currency-rupee"></i> Price
              </label>
              <div class="relative">
                <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500">
                  <i class="bi bi-currency-rupee"></i>
                </span>
                <input name="price" id="price" type="number" step="0.01" min="0"
                       class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all"
                       value="<?=htmlspecialchars($l['price'])?>"
                       placeholder="Enter price">
              </div>
              <p class="text-xs text-gray-500 mt-1"><i class="bi bi-info-circle"></i> Leave blank for negotiable or free items</p>
            </div>
            
            <!-- Location -->
            <div>
              <label for="location_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                <i class="bi bi-geo-alt"></i> Location
              </label>
              <select name="location_id" id="location_id" 
                      class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all">
                <option value="">Choose a location</option>
                <?php foreach($locs as $loc): 
                  $locSelected = (strpos($l['location'], $loc['name']) !== false);
                ?>
                  <option value="<?=$loc['id']?>" <?= $locSelected ? 'selected' : ''?>><?=htmlspecialchars($loc['name'])?><?php if($loc['state']): ?> - <?=htmlspecialchars($loc['state'])?><?php endif; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          
          <!-- Images -->
          <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              <i class="bi bi-images"></i> Images
            </label>
            
            <!-- Existing Images -->
            <?php if(!empty($existingImages)): ?>
              <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Current images:</p>
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2">
                  <?php foreach($existingImages as $ei): ?>
                    <div class="relative group">
                      <img src="<?=htmlspecialchars($ei)?>" class="w-full h-20 object-cover rounded-lg">
                      <label class="absolute top-1 right-1 bg-white dark:bg-gray-800 bg-opacity-75 dark:bg-opacity-50 rounded-full p-1 cursor-pointer">
                        <input type="checkbox" name="remove_images[]" value="<?=htmlspecialchars($ei)?>" class="hidden remove-toggle">
                        <i class="bi bi-x-lg text-red-600 dark:text-red-400"></i>
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-500 mt-1"><i class="bi bi-info-circle"></i> Check any images you want to remove</p>
              </div>
            <?php endif; ?>
            
            <!-- Upload New Images -->
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 text-center hover:border-blue-400 transition-colors">
              <input type="file" name="image[]" id="image" class="hidden" multiple accept="image/*">
              <label for="image" class="cursor-pointer">
                <div class="text-blue-500 text-4xl mb-2">
                  <i class="bi bi-cloud-arrow-up"></i>
                </div>
                <p class="text-gray-600 dark:text-gray-400 font-medium">
                  <span class="text-blue-500">Click to upload</span> or drag and drop
                </p>
                <p class="text-gray-400 text-sm mt-1">PNG, JPG, GIF (max 2MB each)</p>
              </label>
            </div>
            <div id="imagePreview" class="mt-4 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2"></div>
          </div>
          
          <!-- Submit Button -->
          <div class="text-center">
            <button class="w-full py-4 rounded-xl font-semibold text-lg inline-flex items-center justify-center gap-2" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color: white;" type="submit">
              <i class="bi bi-save"></i> Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
  // Form validation
  (function () {
    'use strict'
    const forms = document.querySelectorAll('form')
    Array.prototype.slice.call(forms).forEach(function(form) {
      form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        form.classList.add('was-validated')
      }, false)
    })
  })()

  // Image preview for multiple selection
  (function(){
    const input = document.getElementById('image');
    const preview = document.getElementById('imagePreview');
    if(input && preview){
      input.addEventListener('change', () => {
        preview.innerHTML = '';
        Array.from(input.files).forEach((file, index) => {
          if(!file.type.startsWith('image/')) return;
          const reader = new FileReader();
          reader.onload = e => {
            const wrapper = document.createElement('div');
            wrapper.className = 'relative group';
            
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'w-full h-20 object-cover rounded-lg';
            
            // Remove button
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity';
            removeBtn.innerHTML = '<i class="bi bi-x"></i>';
            removeBtn.onclick = (e) => {
              e.preventDefault();
              const dt = new DataTransfer();
              const files = input.files;
              for (let i = 0; i < files.length; i++) {
                if (i !== index) dt.items.add(files[i]);
              }
              input.files = dt.files;
              preview.removeChild(wrapper);
            };
            
            wrapper.appendChild(img);
            wrapper.appendChild(removeBtn);
            preview.appendChild(wrapper);
          };
          reader.readAsDataURL(file);
        });
      });
    }
  })();
  
  // Handle remove toggle
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

