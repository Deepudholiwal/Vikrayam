<?php
require 'config.php';

header('Content-Type: application/json');

$search = trim($_GET['search'] ?? '');

if (strlen($search) < 1) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, state FROM locations WHERE name LIKE ? OR state LIKE ? ORDER BY name ASC LIMIT 20");
$stmt->execute(["%$search%", "%$search%"]);
$results = $stmt->fetchAll();

echo json_encode($results);
?>
