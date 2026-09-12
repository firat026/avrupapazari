<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

try {
    $pdo = getDB();

    $popularStatement = $pdo->query(
        'SELECT id, name FROM vehicle_brands
         WHERE is_active = 1 AND is_popular = 1
         ORDER BY sort_order ASC, name ASC'
    );
    $otherStatement = $pdo->query(
        'SELECT id, name FROM vehicle_brands
         WHERE is_active = 1 AND is_popular = 0
         ORDER BY name ASC'
    );

    $popular = $popularStatement->fetchAll();
    $others = $otherStatement->fetchAll();

    echo json_encode([
        'success' => true,
        'popular' => $popular,
        'others' => $others,
        'total' => count($popular) + count($others),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database query failed',
    ]);
}
