<?php
declare(strict_types=1);
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error'], 405);
}
$user = currentUser();
if (!$user) {
    jsonResponse(['status' => 'login_required'], 401);
}
$listingId = (int)($_POST['listing_id'] ?? 0);
$pdo = getDB();
$exists = $pdo->prepare('SELECT id FROM listings WHERE id = ? LIMIT 1');
$exists->execute([$listingId]);
if (!$exists->fetch()) {
    jsonResponse(['status' => 'error'], 404);
}
$check = $pdo->prepare('SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?');
$check->execute([(int)$user['id'], $listingId]);
if ($check->fetch()) {
    $pdo->prepare('DELETE FROM favorites WHERE user_id = ? AND listing_id = ?')->execute([(int)$user['id'], $listingId]);
    $status = 'removed';
} else {
    $pdo->prepare('INSERT INTO favorites (user_id, listing_id) VALUES (?, ?)')->execute([(int)$user['id'], $listingId]);
    $status = 'added';
}
$pdo->prepare('UPDATE listings SET favorite_count = (SELECT COUNT(*) FROM favorites WHERE listing_id = ?) WHERE id = ?')->execute([$listingId, $listingId]);
$count = $pdo->prepare('SELECT favorite_count FROM listings WHERE id = ?');
$count->execute([$listingId]);
jsonResponse(['status' => $status, 'count' => (int)$count->fetchColumn()]);
