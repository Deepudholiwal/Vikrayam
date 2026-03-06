<?php
require 'config.php';

header('Content-Type: application/json');

// Get system status
$locationCount = $pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn();
$statesCount = $pdo->query("SELECT COUNT(DISTINCT state) FROM locations")->fetchColumn();
$listingsCount = $pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

$locations = $pdo->query("SELECT state, COUNT(*) as count FROM locations GROUP BY state ORDER BY count DESC LIMIT 5")->fetchAll();

echo json_encode([
    'status' => 'OK',
    'system_ready' => true,
    'locations_populated' => $locationCount > 0,
    'statistics' => [
        'total_cities' => $locationCount,
        'states_coverage' => $statesCount,
        'total_listings' => $listingsCount,
        'total_users' => $usersCount,
    ],
    'top_states' => $locations,
    'message' => $locationCount > 0 
        ? "✅ Location system is fully operational! All $locationCount Indian cities are ready for use."
        : "⚠️ Locations are being initialized. Refresh the page."
]);
?>
