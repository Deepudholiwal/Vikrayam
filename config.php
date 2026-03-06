<?php
// config.php
// Prevent multiple includes
if (defined('CONFIG_LOADED')) return;
define('CONFIG_LOADED', true);

// Session security settings
session_start([
    'cookie_httponly' => 1,
    'cookie_secure' => false, // Set to true if using HTTPS
    'use_strict_mode' => 1,
    'use_only_cookies' => 1,
]);

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['created_at'])) {
    $_SESSION['created_at'] = time();
} elseif (time() - $_SESSION['created_at'] > 3600) {
    session_regenerate_id(true);
    $_SESSION['created_at'] = time();
}

$DB_HOST = 'localhost';
$DB_NAME = 'classifieds';
$DB_USER = 'root';
$DB_PASS = 'Deepak@123'; 

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("DB Connection failed: " . $e->getMessage());
}

// CSRF Token Functions
if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field() {
        return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Password strength validation
if (!function_exists('validate_password_strength')) {
    function validate_password_strength($password) {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
        if (!preg_match("/[A-Z]/", $password)) {
            $errors[] = "Password must contain at least one uppercase letter.";
        }
        if (!preg_match("/[a-z]/", $password)) {
            $errors[] = "Password must contain at least one lowercase letter.";
        }
        if (!preg_match("/[0-9]/", $password)) {
            $errors[] = "Password must contain at least one number.";
        }
        if (!preg_match("/[^A-Za-z0-9]/", $password)) {
            $errors[] = "Password must contain at least one special character.";
        }
        return $errors;
    }
}

// Input sanitization helper
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        if (is_array($data)) {
            return array_map('sanitize_input', $data);
        }
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('current_user')) {
    function current_user($pdo) {
        if (!is_logged_in()) return null;
        $stmt = $pdo->prepare("SELECT id,name,email,profile_image,created_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
}

// Ensure users table has required columns
try {
    // Check if profile_image column exists
    $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL");
    }
    
    // Check if is_admin column exists
    $admin_cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_admin'")->fetchAll();
    if (empty($admin_cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
    }
    
    // Check if verified column exists
    $verified_cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'verified'")->fetchAll();
    if (empty($verified_cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN verified TINYINT(1) DEFAULT 0");
    }
    
    // Check if listings has active column
    $active_cols = $pdo->query("SHOW COLUMNS FROM listings LIKE 'active'")->fetchAll();
    if (empty($active_cols)) {
        $pdo->exec("ALTER TABLE listings ADD COLUMN active TINYINT(1) DEFAULT 1");
    }
} catch (Exception $e) {
    // Ignore if columns already exist
}

// ensure auxiliary tables exist (likes, comments, locations)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        listing_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY(listing_id,user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        listing_id INT NOT NULL,
        user_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        state VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FULLTEXT INDEX ft_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Password reset tokens table
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Auto-populate locations from external API on first access
    $check = $pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn();
    if ($check < 100) {
        // Try to fetch from external API
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'user_agent' => 'Mozilla/5.0',
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            $statesJson = @file_get_contents('https://api.countrystatecity.in/v1/countries/IN/states', false, $context);
            
            if ($statesJson !== false && strlen($statesJson) > 10) {
                $states = json_decode($statesJson, true);
                
                if (is_array($states) && !empty($states)) {
                    // Clear existing data if less than 100 cities
                    if ($check < 100) {
                        $pdo->query("TRUNCATE TABLE locations");
                    }
                    
                    $stmt = $pdo->prepare("INSERT IGNORE INTO locations (name, state) VALUES (?, ?)");
                    
                    foreach ($states as $state) {
                        $stateName = trim($state['name'] ?? '');
                        $stateId = trim($state['id'] ?? '');
                        
                        if (!$stateName || !$stateId) continue;
                        
                        $citiesJson = @file_get_contents("https://api.countrystatecity.in/v1/countries/IN/states/$stateId/cities", false, $context);
                        
                        if ($citiesJson !== false) {
                            $cities = json_decode($citiesJson, true);
                            
                            if (is_array($cities) && !empty($cities)) {
                                foreach ($cities as $city) {
                                    $cityName = trim($city['name'] ?? '');
                                    if ($cityName) {
                                        try {
                                            $stmt->execute([$cityName, $stateName]);
                                        } catch (Exception $e) {
                                            // Skip duplicates
                                        }
                                    }
                                }
                            }
                        }
                        usleep(100000);
                    }
                }
            }
        } catch (Exception $e) {
            // API failed, use fallback
        }
        
        // Fallback: If still no cities, use embedded data
        $fallbackCheck = $pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn();
        if ($fallbackCheck < 50) {
            require_once __DIR__ . '/api_populate_locations.php';
            @populateLocationsFromAPI();
        }
    }
} catch (Exception $e) {
    // ignore if table already exists or cannot create
}
?>
