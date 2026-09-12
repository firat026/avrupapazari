<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$countryId = filter_input(INPUT_GET, 'country_id', FILTER_VALIDATE_INT);

if (!$countryId || $countryId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid country_id']);
    exit;
}

try {
    $statement = getDB()->prepare(
        'SELECT id, name FROM cities WHERE country_id = :country_id AND is_active = 1 ORDER BY name ASC'
    );
    $statement->execute(['country_id' => $countryId]);
    echo json_encode($statement->fetchAll(), JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed']);
}
