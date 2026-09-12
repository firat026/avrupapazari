<?php
declare(strict_types=1);
require_once __DIR__ . '/../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'method'], 405);
}
$user = currentUser();
if (!$user) {
    jsonResponse(['ok' => false, 'status' => 'login_required'], 401);
}
if (!csrfValid($_POST['csrf'] ?? null)) {
    jsonResponse(['ok' => false, 'error' => t('auth.session_expired')], 419);
}

$body = trim((string)($_POST['body'] ?? ''));
if (mb_strlen($body) < 2 || mb_strlen($body) > 3000) {
    jsonResponse(['ok' => false, 'error' => t('msg.too_short')], 422);
}

$pdo = getDB();
$userId = (int)$user['id'];
$conversationId = (int)($_POST['conversation_id'] ?? 0);
$listingId = (int)($_POST['listing_id'] ?? 0);

if ($conversationId > 0) {
    $stmt = $pdo->prepare('SELECT id, sender_id, receiver_id FROM conversations WHERE id = ? AND (sender_id = ? OR receiver_id = ?) LIMIT 1');
    $stmt->execute([$conversationId, $userId, $userId]);
    $conversation = $stmt->fetch();
    if (!$conversation) {
        jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
    }
} else {
    $stmt = $pdo->prepare('SELECT id, user_id FROM listings WHERE id = ? LIMIT 1');
    $stmt->execute([$listingId]);
    $listing = $stmt->fetch();
    if (!$listing) {
        jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
    }
    if ((int)$listing['user_id'] === $userId) {
        jsonResponse(['ok' => false, 'error' => t('msg.own_listing')], 422);
    }
    $stmt = $pdo->prepare('SELECT id FROM conversations WHERE listing_id = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) LIMIT 1');
    $stmt->execute([$listingId, $userId, (int)$listing['user_id'], (int)$listing['user_id'], $userId]);
    $conversationId = (int)($stmt->fetchColumn() ?: 0);
    if (!$conversationId) {
        $pdo->prepare('INSERT INTO conversations (listing_id, sender_id, receiver_id, created_at) VALUES (?, ?, ?, NOW())')->execute([$listingId, $userId, (int)$listing['user_id']]);
        $conversationId = (int)$pdo->lastInsertId();
    }
}

$pdo->prepare('INSERT INTO messages (conversation_id, sender_id, body, is_read, created_at) VALUES (?, ?, ?, 0, NOW())')->execute([$conversationId, $userId, $body]);
$pdo->prepare('UPDATE conversations SET updated_at = NOW() WHERE id = ?')->execute([$conversationId]);

jsonResponse(['ok' => true, 'conversation_id' => $conversationId, 'message' => t('msg.sent'), 'redirect' => url('messages.php?c=' . $conversationId)]);
