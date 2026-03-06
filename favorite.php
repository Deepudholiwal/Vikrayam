<?php
require 'config.php';
if (!is_logged_in()) die('Login required');
$user = current_user($pdo);
$listing_id = (int)($_GET['id'] ?? 0);
if (!$listing_id) die('Invalid');
$exists = $pdo->prepare("SELECT * FROM favorites WHERE user_id=? AND listing_id=?");
$exists->execute([$user['id'],$listing_id]);
if ($exists->fetch()) {
    $pdo->prepare("DELETE FROM favorites WHERE user_id=? AND listing_id=?")->execute([$user['id'],$listing_id]);
    echo 'removed';
} else {
    $pdo->prepare("INSERT INTO favorites (user_id,listing_id) VALUES (?,?)")->execute([$user['id'],$listing_id]);
    echo 'added';
}
exit;