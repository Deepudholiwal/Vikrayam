<?php
/**
 * Robust API Location Fetcher
 * Fetches all Indian cities from Country State City API
 * Includes error handling, logging, and fallback options
 */

require_once 'config.php';

function fetchFromExternalAPI() {
    global $pdo;
    
    $baseUrl = 'https://api.countrystatecity.in/v1/countries/IN/states';
    $totalCities = 0;
    $errors = [];
    
    try {
        // Create context with timeout
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        // Fetch all states
        $statesJson = @file_get_contents($baseUrl, false, $context);
        
        if ($statesJson === false) {
            return [
                'success' => false,
                'message' => 'Failed to connect to API. Check internet connection.',
                'method' => 'file_get_contents'
            ];
        }
        
        $states = json_decode($statesJson, true);
        
        if (!is_array($states) || empty($states)) {
            return [
                'success' => false,
                'message' => 'Invalid API response for states',
                'response' => substr($statesJson, 0, 100)
            ];
        }
        
        // Prepare statement
        $stmt = $pdo->prepare("INSERT IGNORE INTO locations (name, state) VALUES (?, ?)");
        
        foreach ($states as $state) {
            $stateName = trim($state['name'] ?? '');
            $stateId = trim($state['id'] ?? '');
            
            if (!$stateName || !$stateId) {
                continue;
            }
            
            // Fetch cities for this state
            $citiesUrl = "https://api.countrystatecity.in/v1/countries/IN/states/$stateId/cities";
            
            $citiesJson = @file_get_contents($citiesUrl, false, $context);
            
            if ($citiesJson === false) {
                $errors[] = "Failed to fetch cities for $stateName";
                continue;
            }
            
            $cities = json_decode($citiesJson, true);
            
            if (!is_array($cities) || empty($cities)) {
                $errors[] = "Invalid response for $stateName cities";
                continue;
            }
            
            // Insert cities
            foreach ($cities as $city) {
                $cityName = trim($city['name'] ?? '');
                
                if ($cityName) {
                    try {
                        $stmt->execute([$cityName, $stateName]);
                        $totalCities++;
                    } catch (Exception $e) {
                        // Duplicate, skip
                    }
                }
            }
            
            // Tiny delay to avoid rate limiting
            usleep(100000); // 0.1 seconds
        }
        
        return [
            'success' => true,
            'message' => "Successfully fetched $totalCities cities from API",
            'cities_count' => $totalCities,
            'states_count' => count($states),
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Exception: ' . $e->getMessage()
        ];
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode(fetchFromExternalAPI(), JSON_PRETTY_PRINT);
?>
