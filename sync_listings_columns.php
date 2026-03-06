<?php
/**
 * Sync script to ensure listings table has correct columns
 * Run this once on production to fix the database schema
 */
require_once 'config.php';

echo "<h1>Checking Listings Table Schema...</h1>";

// Check current columns
$stmt = $pdo->query("DESCRIBE listings");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>Current Columns:</h2>";
echo "<ul>";
foreach ($columns as $col) {
    echo "<li>$col</li>";
}
echo "</ul>";

// Check if image_path exists
if (!in_array('image_path', $columns)) {
    echo "<p><strong>Adding image_path column...</strong></p>";
    try {
        // Add image_path column if it doesn't exist
        $pdo->exec("ALTER TABLE listings ADD COLUMN image_path VARCHAR(255) AFTER location");
        echo "<p style='color:green'>✓ Added image_path column</p>";
        
        // Copy data from image to image_path if image column exists
        if (in_array('image', $columns)) {
            echo "<p><strong>Copying data from image to image_path...</strong></p>";
            $pdo->exec("UPDATE listings SET image_path = image WHERE image IS NOT NULL AND image != ''");
            echo "<p style='color:green'>✓ Copied data</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:green'>✓ image_path column already exists</p>";
}

// Check if image column exists
if (in_array('image', $columns)) {
    echo "<p><strong>Note: The 'image' column also exists. Consider removing it after verifying data.</strong></p>";
}

echo "<h2>Done!</h2>";
echo "<p><a href='index'>Go to Home</a></p>";

