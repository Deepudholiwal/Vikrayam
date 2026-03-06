<?php
require_once 'config.php';

// Check admin access
if (empty($_SESSION['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

header('Content-Type: application/json');

// Refresh locations from external API
if (isset($_GET['action']) && $_GET['action'] === 'refresh') {
    try {
        // Clear existing locations
        $pdo->query("TRUNCATE TABLE locations");
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 20,
                'user_agent' => 'Mozilla/5.0',
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $baseUrl = 'https://api.countrystatecity.in/v1/countries/IN/states';
        $statesJson = @file_get_contents($baseUrl, false, $context);
        
        if ($statesJson === false || strlen($statesJson) < 10) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch from API. Check your internet connection.'
            ]);
            exit;
        }
        
        $states = json_decode($statesJson, true);
        
        if (!is_array($states) || empty($states)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid API response. API may be unavailable.',
                'debug' => is_array($states) ? 'Empty array' : 'Not an array'
            ]);
            exit;
        }
        
        $totalCities = 0;
        $processedStates = 0;
        $stmt = $pdo->prepare("INSERT IGNORE INTO locations (name, state) VALUES (?, ?)");
        
        foreach ($states as $state) {
            $stateName = trim($state['name'] ?? '');
            $stateId = trim($state['id'] ?? '');
            
            if (!$stateName || !$stateId) continue;
            
            $citiesUrl = "https://api.countrystatecity.in/v1/countries/IN/states/$stateId/cities";
            $citiesJson = @file_get_contents($citiesUrl, false, $context);
            
            if ($citiesJson !== false && strlen($citiesJson) > 10) {
                $cities = json_decode($citiesJson, true);
                
                if (is_array($cities) && !empty($cities)) {
                    $processedStates++;
                    foreach ($cities as $city) {
                        $cityName = trim($city['name'] ?? '');
                        if ($cityName) {
                            try {
                                $stmt->execute([$cityName, $stateName]);
                                $totalCities++;
                            } catch (Exception $e) {
                                // Skip duplicates
                            }
                        }
                    }
                }
            }
            
            usleep(150000); // 0.15 second delay
        }
        
        if ($totalCities > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Successfully refreshed locations from Country State City API',
                'cities_added' => $totalCities,
                'states_processed' => $processedStates
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'API returned valid data but no cities were found. API may have changed format.'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Get statistics
if (isset($_GET['action']) && $_GET['action'] === 'stats') {
    $totalLocations = $pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn();
    $statesCount = $pdo->query("SELECT COUNT(DISTINCT state) FROM locations")->fetchColumn();
    $listingsWithLocations = $pdo->query("SELECT COUNT(DISTINCT location) FROM listings WHERE location IS NOT NULL AND location != ''")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'total_locations' => $totalLocations,
        'states' => $statesCount,
        'active_locations_in_use' => $listingsWithLocations
    ]);
    exit;
}

// Get all locations with counts
if (isset($_GET['action']) && $_GET['action'] === 'list') {
    $locations = $pdo->query("
        SELECT l.*, COUNT(li.id) as listing_count 
        FROM locations l 
        LEFT JOIN listings li ON li.location = l.name 
        GROUP BY l.id 
        ORDER BY l.name ASC
    ")->fetchAll();
    
    echo json_encode([
        'success' => true,
        'locations' => $locations
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
