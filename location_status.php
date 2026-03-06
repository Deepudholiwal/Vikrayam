<?php
require_once 'config.php';

$locationCount = $pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn();
$statesCount = $pdo->query("SELECT COUNT(DISTINCT state) FROM locations")->fetchColumn();
$sampleLocations = $pdo->query("SELECT * FROM locations LIMIT 10")->fetchAll();

// Test API connectivity
$context = stream_context_create([
    'http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0'],
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
]);

$apiTest = @file_get_contents('https://api.countrystatecity.in/v1/countries/IN/states', false, $context);
$apiWorking = $apiTest !== false && strlen($apiTest) > 10;
$statesFromApi = $apiWorking ? count(json_decode($apiTest, true)) : 0;

header('Content-Type: application/json');
echo json_encode([
    'database_status' => [
        'total_cities' => intval($locationCount),
        'states_coverage' => intval($statesCount),
        'sample_cities' => $sampleLocations,
        'is_populated' => $locationCount >= 100
    ],
    'api_status' => [
        'api_reachable' => $apiWorking,
        'states_in_api' => $statesFromApi,
        'last_checked' => date('Y-m-d H:i:s')
    ],
    'system_status' => $locationCount >= 100 ? 'READY' : ($apiWorking ? 'INITIALIZING' : 'CHECK_CONNECTION'),
    'message' => $locationCount >= 100 
        ? '✅ System is ready with ' . $locationCount . ' cities'
        : ($apiWorking 
            ? '⏳ API is reachable, initializing locations...' 
            : '⚠️ Check your internet connection')
]);
?>
