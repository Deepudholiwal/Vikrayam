<?php
/**
 * Fetch all Indian cities from Country State City API
 * Uses: https://api.countrystatecity.in/v1/countries/IN/states
 * No authentication required, free API
 */

require 'config.php';

function populateLocationsFromExternalAPI() {
    global $pdo;
    
    // Check if locations already populated
    $count = $pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn();
    if ($count > 100) {
        return ['success' => true, 'message' => 'Locations already populated', 'count' => $count];
    }
    
    $apiUrl = 'https://api.countrystatecity.in/v1/countries/IN/states';
    
    try {
        // Fetch all states
        $statesResponse = @file_get_contents($apiUrl);
        if ($statesResponse === false) {
            return ['success' => false, 'message' => 'Failed to connect to API'];
        }
        
        $states = json_decode($statesResponse, true);
        if (empty($states)) {
            return ['success' => false, 'message' => 'Invalid API response'];
        }
        
        $totalCities = 0;
        $stmt = $pdo->prepare("INSERT IGNORE INTO locations (name, state) VALUES (?, ?)");
        
        // Fetch cities for each state
        foreach ($states as $state) {
            $stateName = $state['name'] ?? '';
            $stateId = $state['id'] ?? '';
            
            if (!$stateId) continue;
            
            $citiesUrl = "https://api.countrystatecity.in/v1/countries/IN/states/$stateId/cities";
            
            try {
                $citiesResponse = @file_get_contents($citiesUrl);
                if ($citiesResponse === false) continue;
                
                $cities = json_decode($citiesResponse, true);
                if (empty($cities)) continue;
                
                // Insert each city
                foreach ($cities as $city) {
                    $cityName = $city['name'] ?? '';
                    if ($cityName) {
                        try {
                            $stmt->execute([$cityName, $stateName]);
                            $totalCities++;
                        } catch (Exception $e) {
                            // Skip duplicates
                        }
                    }
                }
                
                // Small delay to avoid rate limiting
                usleep(100000); // 0.1 second delay
                
            } catch (Exception $e) {
                continue;
            }
        }
        
        return [
            'success' => true, 
            'message' => 'Locations populated from Country State City API',
            'cities_added' => $totalCities,
            'states_processed' => count($states)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// Run population
header('Content-Type: application/json');
echo json_encode(populateLocationsFromExternalAPI());
?>
