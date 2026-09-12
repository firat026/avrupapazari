<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$columns = getDB()->query('SHOW COLUMNS FROM users')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>User columns</title></head>
<body>
<h1>User table columns</h1>
<pre><?php foreach ($columns as $column): ?><?= htmlspecialchars($column['Field'] . ' (' . $column['Type'] . ')' . PHP_EOL, ENT_QUOTES, 'UTF-8') ?><?php endforeach; ?></pre>
</body>
</html>
