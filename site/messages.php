<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
$user = requireLogin();
$pdo = getDB();
$userId = (int)$user['id'];

$stmt = $pdo->prepare("SELECT c.id, c.listing_id, l.title AS listing_title, l.slug AS listing_slug, l.image AS listing_image,
    IF(c.sender_id = ?, c.receiver_id, c.sender_id) AS other_id,
    u.first_name AS other_first, u.last_name AS other_last,
    (SELECT body FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_body,
    (SELECT created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_at,
    (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.sender_id <> ?) AS unread
    FROM conversations c
    LEFT JOIN listings l ON l.id = c.listing_id
    JOIN users u ON u.id = IF(c.sender_id = ?, c.receiver_id, c.sender_id)
    WHERE c.sender_id = ? OR c.receiver_id = ?
    ORDER BY COALESCE(c.updated_at, c.created_at) DESC");
$stmt->execute([$userId, $userId, $userId, $userId, $userId]);
$conversations = $stmt->fetchAll();

$activeId = (int)($_GET['c'] ?? 0);
$active = null;
$thread = [];
foreach ($conversations as $c) {
    if ((int)$c['id'] === $activeId) { $active = $c; }
}
if ($active) {
    $pdo->prepare('UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id <> ?')->execute([$activeId, $userId]);
    $stmt = $pdo->prepare('SELECT m.id, m.sender_id, m.body, m.created_at FROM messages m WHERE m.conversation_id = ? ORDER BY m.id ASC');
    $stmt->execute([$activeId]);
    $thread = $stmt->fetchAll();
}

$pageTitle = t('msg.title') . ' - ' . setting('site_name', 'AvrupaPazari');
$pageStyles = ['messages.css'];
include __DIR__ . '/includes/header.php';
?>
<main class="messages-page" data-testid="messages-page">
  <aside class="msg-list" data-testid="conversation-list">
    <h1><?= e(t('msg.title')) ?></h1>
    <?php if (!$conversations): ?>
      <p class="msg-empty" data-testid="messages-empty"><?= e(t('msg.empty')) ?></p>
    <?php endif; ?>
    <?php foreach ($conversations as $c): ?>
    <a class="msg-item<?= (int)$c['id'] === $activeId ? ' active' : '' ?>" href="<?= url('messages.php?c=' . (int)$c['id']) ?>" data-testid="conversation-<?= (int)$c['id'] ?>">
      <span class="msg-avatar"><?= e(mb_strtoupper(mb_substr((string)$c['other_first'], 0, 1))) ?></span>
      <span class="msg-item-body">
        <span class="msg-item-top"><b><?= e($c['other_first'] . ' ' . $c['other_last']) ?></b><small><?= e(timeAgo($c['last_at'])) ?></small></span>
        <span class="msg-item-listing"><?= e($c['listing_title'] ?? '') ?></span>
        <span class="msg-item-preview"><?= e(mb_strimwidth((string)$c['last_body'], 0, 60, '…')) ?></span>
      </span>
      <?php if ((int)$c['unread'] > 0): ?><span class="msg-unread" data-testid="conversation-unread"><?= (int)$c['unread'] ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
  </aside>

  <section class="msg-thread" data-testid="conversation-thread">
    <?php if (!$active): ?>
      <div class="msg-select"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><p><?= e(t('msg.select')) ?></p></div>
    <?php else: ?>
      <header class="msg-thread-head">
        <span class="msg-avatar"><?= e(mb_strtoupper(mb_substr((string)$active['other_first'], 0, 1))) ?></span>
        <div><b><?= e($active['other_first'] . ' ' . $active['other_last']) ?></b>
          <?php if ($active['listing_slug']): ?><a href="<?= url('pages/listing.php?slug=' . rawurlencode((string)$active['listing_slug'])) ?>"><?= e(t('msg.about')) ?>: <?= e($active['listing_title']) ?></a><?php endif; ?>
        </div>
      </header>
      <div class="msg-bubbles" id="msgBubbles">
        <?php foreach ($thread as $m): ?>
        <div class="msg-bubble<?= (int)$m['sender_id'] === $userId ? ' mine' : '' ?>"><p><?= nl2br(e($m['body'])) ?></p><time><?= e(date('d.m.Y H:i', strtotime((string)$m['created_at']))) ?></time></div>
        <?php endforeach; ?>
      </div>
      <form class="msg-reply" id="msgReplyForm" data-testid="reply-form">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="conversation_id" value="<?= $activeId ?>">
        <textarea name="body" rows="2" required placeholder="<?= e(t('msg.reply')) ?>…" data-testid="reply-body"></textarea>
        <button type="submit" class="btn-primary" data-testid="reply-submit"><?= e(t('msg.send')) ?></button>
      </form>
    <?php endif; ?>
  </section>
</main>
<script>
(function () {
  var form = document.getElementById('msgReplyForm');
  if (!form) return;
  var bubbles = document.getElementById('msgBubbles');
  bubbles.scrollTop = bubbles.scrollHeight;
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var btn = form.querySelector('button'); btn.disabled = true;
    fetch('<?= url('ajax/send-message.php') ?>', { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.ok) { alert(d.error || 'Error'); btn.disabled = false; return; }
        var b = document.createElement('div'); b.className = 'msg-bubble mine';
        b.innerHTML = '<p></p><time></time>';
        b.querySelector('p').textContent = form.body.value;
        b.querySelector('time').textContent = new Date().toLocaleString();
        bubbles.appendChild(b); bubbles.scrollTop = bubbles.scrollHeight;
        form.body.value = ''; btn.disabled = false;
      });
  });
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
