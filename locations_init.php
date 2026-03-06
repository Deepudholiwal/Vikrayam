<?php
require_once 'config.php';
header('Content-Type: text/html; charset=utf-8');

$locationCount = $pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn();
$statesCount = $pdo->query("SELECT COUNT(DISTINCT state) FROM locations")->fetchColumn();

// Test API
$context = stream_context_create([
    'http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0'],
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
]);
$apiTest = @file_get_contents('https://api.countrystatecity.in/v1/countries/IN/states', false, $context);
$apiWorking = $apiTest !== false && strlen($apiTest) > 10;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Location System Status</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; padding: 2rem 0; }
        .status-card { background: white; border-radius: 10px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .status-badge { font-weight: bold; padding: 0.5rem 1rem; border-radius: 20px; }
        .status-ok { background: #d4edda; color: #155724; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-error { background: #f8d7da; color: #721c24; }
        .stat-box { text-align: center; }
        .stat-number { font-size: 2.5rem; font-weight: bold; color: #0066cc; }
        .stat-label { color: #666; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">📍 Location System Status</h1>
        
        <!-- System Status -->
        <div class="status-card">
            <h3 class="mb-3">System Status</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $locationCount; ?></div>
                        <div class="stat-label">Cities in Database</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $statesCount; ?></div>
                        <div class="stat-label">States/UTs Covered</div>
                    </div>
                </div>
            </div>
            <hr>
            <p>
                <strong>Overall Status:</strong>
                <?php if ($locationCount >= 100): ?>
                    <span class="status-badge status-ok">✅ READY</span> - System has <?php echo $locationCount; ?> cities loaded
                <?php elseif ($apiWorking): ?>
                    <span class="status-badge status-warning">⏳ INITIALIZING</span> - API is reachable, waiting to populate
                <?php else: ?>
                    <span class="status-badge status-error">⚠️ CHECK CONNECTION</span> - API unreachable
                <?php endif; ?>
            </p>
        </div>

        <!-- API Status -->
        <div class="status-card">
            <h3 class="mb-3">API Connection</h3>
            <p>
                <strong>Country State City API:</strong>
                <?php if ($apiWorking): ?>
                    <span class="status-badge status-ok">✅ CONNECTED</span>
                <?php else: ?>
                    <span class="status-badge status-error">❌ DISCONNECTED</span>
                <?php endif; ?>
            </p>
            <small class="text-muted">API: https://api.countrystatecity.in/v1/countries/IN/states</small>
            <?php if (!$apiWorking): ?>
                <p class="alert alert-warning mt-2" role="alert">
                    <strong>⚠️ Note:</strong> The external API is currently unreachable. The system will use embedded city data as fallback. Check your internet connection or try again later.
                </p>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="status-card">
            <h3 class="mb-3">Actions</h3>
            <p class="mb-3">
                <a href="javascript:location.reload()" class="btn btn-primary">🔄 Refresh Status</a>
                <?php if ($apiWorking && empty($_GET['fetched'])): ?>
                    <a href="fetch_locations.php" class="btn btn-success">📥 Fetch from API</a>
                <?php endif; ?>
                <?php if (!empty($_SESSION['admin'])): ?>
                    <a href="manage_locations.php" class="btn btn-info">⚙️ Manage Locations (Admin)</a>
                <?php endif; ?>
            </p>
        </div>

        <!-- Sample Cities -->
        <div class="status-card">
            <h3 class="mb-3">Sample Cities</h3>
            <?php 
            $samples = $pdo->query("SELECT DISTINCT name, state FROM locations ORDER BY RAND() LIMIT 15")->fetchAll();
            if (!empty($samples)): 
            ?>
                <div class="row">
                    <?php foreach ($samples as $city): ?>
                        <div class="col-md-4 mb-2">
                            <small>
                                <strong><?php echo htmlspecialchars($city['name']); ?></strong>
                                <br>
                                <span class="text-muted"><?php echo htmlspecialchars($city['state']); ?></span>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-warning">No cities found in database yet.</p>
            <?php endif; ?>
        </div>

        <!-- Troubleshooting -->
        <div class="status-card">
            <h3 class="mb-3">❓ Troubleshooting</h3>
            <ul>
                <li><strong>No cities showing:</strong> Refresh the page. System auto-initializes on first access.</li>
                <li><strong>API unreachable:</strong> Check your internet connection. System uses embedded fallback data.</li>
                <li><strong>Still not working:</strong> Visit <code>admin_locations_api.php?action=refresh</code> (admin only) to manually refresh.</li>
                <li><strong>Check current status:</strong> Visit <code>location_status.php</code> for JSON status report.</li>
            </ul>
        </div>
    </div>
</body>
</html>
