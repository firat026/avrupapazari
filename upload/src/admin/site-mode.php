<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Site Online / Offline';
$currentPage = 'site-mode';
$modeFile = __DIR__ . '/.site_mode';
$message = '';

$mode = is_file($modeFile)
    ? trim((string) file_get_contents($modeFile))
    : 'online';

if (!in_array($mode, ['online', 'offline'], true)) {
    $mode = 'online';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newMode = ($_POST['site_mode'] ?? '') === 'offline'
        ? 'offline'
        : 'online';

    $saved = file_put_contents(
        $modeFile,
        $newMode . PHP_EOL,
        LOCK_EX
    );

    if ($saved === false) {
        $message = 'The setting could not be saved. Check the admin folder permissions.';
    } else {
        $mode = $newMode;
        $message = $mode === 'offline'
            ? 'The site is now in maintenance mode.'
            : 'The site is online again.';
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="admin-table-wrap" style="max-width: 720px;">
    <div class="admin-table-header">
        <span class="admin-table-title">Site status</span>
        <span class="badge <?= $mode === 'offline' ? 'badge-yellow' : 'badge-green' ?>">
            <?= $mode === 'offline' ? 'Maintenance' : 'Online' ?>
        </span>
    </div>

    <div style="padding: 24px;">
        <?php if ($message !== ''): ?>
            <div class="alert">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <p style="color: #7a9488; margin: 0 0 20px;">
            In maintenance mode, visitors see the maintenance page while the admin panel remains available.
        </p>

        <form method="post">
            <button
                class="btn <?= $mode === 'offline' ? 'btn-outline' : 'btn-primary' ?>"
                type="submit"
                name="site_mode"
                value="<?= $mode === 'offline' ? 'online' : 'offline' ?>"
            >
                <?= $mode === 'offline' ? 'Set site online' : 'Enable maintenance mode' ?>
            </button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
