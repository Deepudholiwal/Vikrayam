<?php
try {
    $pdo = new PDO(
        'mysql:host=metro.proxy.rlwy.net;port=34377;dbname=railway;charset=utf8mb4',
        'root',
        'wPYHsHeRZVYLYGddkRIzWrBvwzVNaqBG',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "Connected successfully!<br>";
    
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "<br>";
    
    if (in_array('users', $tables)) {
        $users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "Users count: $users<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
