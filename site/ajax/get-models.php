<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$action = $_GET['action'] ?? 'models';
$pdo = getDB();

try {
    if ($action === 'brands') {
        $statement = $pdo->query(
            'SELECT id, name, is_popular FROM vehicle_brands
             WHERE is_active = 1
             ORDER BY is_popular DESC, sort_order ASC, name ASC'
        );
        echo json_encode(['success' => true, 'brands' => $statement->fetchAll()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $brand = trim((string) ($_GET['brand'] ?? ''));
    $brandId = filter_input(INPUT_GET, 'brand_id', FILTER_VALIDATE_INT) ?: null;

    if ($brand === '' && !$brandId) {
        echo json_encode(['success' => false, 'error' => 'brand or brand_id required']);
        exit;
    }

    if ($brand !== '' && !$brandId) {
        $statement = $pdo->prepare('SELECT id FROM vehicle_brands WHERE LOWER(name) = LOWER(:name) LIMIT 1');
        $statement->execute(['name' => $brand]);
        $brandId = (int) ($statement->fetchColumn() ?: 0);
    }

    $models = [];

    if ($brandId) {
        $statement = $pdo->prepare('SELECT id, name FROM vehicle_models WHERE brand_id = :brand_id ORDER BY name ASC');
        $statement->execute(['brand_id' => $brandId]);
        $models = $statement->fetchAll();
    }

    if ($models === [] && $brand !== '') {
        $statement = $pdo->prepare(
            "SELECT DISTINCT model AS name FROM listing_arac
             WHERE LOWER(brand) = LOWER(:brand) AND model IS NOT NULL AND model != ''
             ORDER BY model ASC"
        );
        $statement->execute(['brand' => $brand]);
        $models = $statement->fetchAll();
    }

    echo json_encode([
        'success' => true,
        'brand' => $brand,
        'brand_id' => $brandId,
        'models' => $models,
        'count' => count($models),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database query failed']);
}
