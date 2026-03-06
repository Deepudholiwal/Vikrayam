<?php
require_once 'config.php';

// Check if admin
if (empty($_SESSION['admin'])) {
    header('Location: admin_login');
    exit;
}

$user = current_user($pdo);
$msg = '';
$err = '';
$stats = [];

// Get location statistics
$totalLocations = $pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn();
$statesCount = $pdo->query("SELECT COUNT(DISTINCT state) FROM locations")->fetchColumn();
$listingsWithLocations = $pdo->query("SELECT COUNT(DISTINCT location) FROM listings WHERE location IS NOT NULL AND location != ''")->fetchColumn();

$stats = [
    'total_locations' => $totalLocations,
    'states' => $statesCount,
    'active_in_use' => $listingsWithLocations
];

// Add new location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_location'])) {
    $name = trim($_POST['location_name'] ?? '');
    $state = trim($_POST['location_state'] ?? '');
    
    if (!$name) {
        $err = 'Location name is required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO locations (name, state) VALUES (?, ?)");
            $stmt->execute([$name, $state]);
            $msg = 'Location added successfully!';
        } catch (Exception $e) {
            $err = 'Location already exists.';
        }
    }
}

// Delete location
if (isset($_POST['delete_location'])) {
    $loc_id = (int)($_POST['location_id'] ?? 0);
    if ($loc_id) {
        $pdo->prepare("DELETE FROM locations WHERE id = ?")->execute([$loc_id]);
        $msg = 'Location deleted successfully!';
    }
}

// Get all locations
$locations = $pdo->query("SELECT l.*, COUNT(li.id) as listing_count FROM locations l 
    LEFT JOIN listings li ON li.location = l.name 
    GROUP BY l.id 
    ORDER BY l.name ASC")->fetchAll();
?>
<?php require 'header.php'; ?>

<div class="max-w-6xl mx-auto py-10 px-4">
  <div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold">📍 Manage Locations</h2>
    <a href="admin_dashboard" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition">Back to Admin</a>
  </div>

  <?php if($msg): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
      <i class="bi bi-check-circle"></i> <?=htmlspecialchars($msg)?>
    </div>
  <?php endif; ?>

  <?php if($err): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
      <i class="bi bi-exclamation-circle"></i> <?=htmlspecialchars($err)?>
    </div>
  <?php endif; ?>

  <!-- Statistics -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
      <div class="text-2xl font-bold text-blue-600"><?=$stats['total_locations']?></div>
      <div class="text-gray-600">Total Cities</div>
      <small class="text-gray-500">All Indian locations</small>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
      <div class="text-2xl font-bold text-green-600"><?=$stats['states']?></div>
      <div class="text-gray-600">States & UTs</div>
      <small class="text-gray-500">Coverage area</small>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
      <div class="text-2xl font-bold text-purple-600"><?=$stats['active_in_use']?></div>
      <div class="text-gray-600">Active in Listings</div>
      <small class="text-gray-500">Used by users</small>
    </div>
  </div>

  <!-- Info Box -->
  <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded">
    <p class="text-blue-800"><strong>ℹ️ Info:</strong> Locations are automatically fetched from the <strong>Country State City API</strong>. All Indian cities and states are available. Click refresh to update from the latest API data.</p>
  </div>

  <!-- Refresh Button -->
  <div class="mb-6 flex gap-3">
    <button id="refreshBtn" class="px-6 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition font-semibold">
      <i class="bi bi-arrow-clockwise"></i> Refresh from API
    </button>
    <span id="refreshStatus" class="text-sm text-gray-600 self-center"></span>
  </div>

  <!-- Add Location Form -->
  <div class="card-custom shadow-lg mb-6">
    <div class="bg-blue-600 text-white rounded-t-lg p-4">
      <h3 class="mb-0"><i class="bi bi-plus-circle"></i> Add New Location</h3>
    </div>
    <div class="p-6">
      <form method="post">
        <div class="md:flex md:space-x-4">
          <div class="flex-1 mb-4">
            <label for="location_name" class="block text-sm font-semibold">City Name <span class="text-red-500">*</span></label>
            <input type="text" name="location_name" id="location_name" class="w-full mt-1 p-2 border rounded" placeholder="e.g., Mumbai, Delhi, Bangalore" required>
          </div>
          <div class="flex-1 mb-4">
            <label for="location_state" class="block text-sm font-semibold">State</label>
            <input type="text" name="location_state" id="location_state" class="w-full mt-1 p-2 border rounded" placeholder="e.g., Maharashtra, Delhi">
          </div>
        </div>
        <div class="text-center">
          <button type="submit" name="add_location" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
            <i class="bi bi-plus"></i> Add Location
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Locations List -->
  <div class="card-custom shadow-lg">
    <div class="bg-gray-700 text-white rounded-t-lg p-4">
      <h3 class="mb-0"><i class="bi bi-geo-alt"></i> All Locations (<?=count($locations)?>)</h3>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-100 border-b">
          <tr>
            <th class="px-4 py-3 text-left text-sm font-semibold">City</th>
            <th class="px-4 py-3 text-left text-sm font-semibold">State</th>
            <th class="px-4 py-3 text-center text-sm font-semibold">Listings</th>
            <th class="px-4 py-3 text-center text-sm font-semibold">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($locations as $loc): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="px-4 py-3"><strong><?=htmlspecialchars($loc['name'])?></strong></td>
              <td class="px-4 py-3"><?=htmlspecialchars($loc['state'] ?? '-')?></td>
              <td class="px-4 py-3 text-center">
                <span class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm"><?=$loc['listing_count']?></span>
              </td>
              <td class="px-4 py-3 text-center">
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this location?');">
                  <input type="hidden" name="location_id" value="<?=$loc['id']?>">
                  <button type="submit" name="delete_location" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition text-sm">
                    <i class="bi bi-trash"></i> Delete
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require 'footer.php'; ?>

<script>
// Real-time statistics update
(function(){
  setInterval(() => {
    fetch('admin_locations_api.php?action=stats')
      .then(r => r.json())
      .then(data => {
        if(data.success) {
          document.querySelectorAll('[data-stat="locations"]').forEach(el => {
            el.textContent = data.total_locations;
          });
          document.querySelectorAll('[data-stat="states"]').forEach(el => {
            el.textContent = data.states;
          });
          document.querySelectorAll('[data-stat="in_use"]').forEach(el => {
            el.textContent = data.active_locations_in_use;
          });
        }
      });
  }, 30000); // Update every 30 seconds
})();

// Refresh from external API
(function(){
  const refreshBtn = document.getElementById('refreshBtn');
  const refreshStatus = document.getElementById('refreshStatus');
  
  if(refreshBtn) {
    refreshBtn.addEventListener('click', () => {
      refreshBtn.disabled = true;
      refreshStatus.textContent = '⏳ Fetching cities from API...';
      
      fetch('admin_locations_api.php?action=refresh')
        .then(r => r.json())
        .then(data => {
          if(data.success) {
            refreshStatus.textContent = `✅ Success! Added ${data.cities_added} cities from ${data.states_processed} states`;
            refreshStatus.classList.remove('text-red-600');
            refreshStatus.classList.add('text-green-600');
            // Reload page after 2 seconds
            setTimeout(() => location.reload(), 2000);
          } else {
            refreshStatus.textContent = `❌ Error: ${data.message}`;
            refreshStatus.classList.add('text-red-600');
            refreshBtn.disabled = false;
          }
        })
        .catch(err => {
          refreshStatus.textContent = `❌ Error: ${err.message}`;
          refreshStatus.classList.add('text-red-600');
          refreshBtn.disabled = false;
        });
    });
  }
})();
</script>
