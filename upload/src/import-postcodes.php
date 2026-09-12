<?php
/**
 * One-time importer for the internal postcodes table.
 * Run from the project root: php import-postcodes.php
 * Source files are downloaded once from GeoNames, then stored in MySQL.
 * The map itself reads only the local postcodes table after this import.
 */

require_once __DIR__ . '/config.php';

$pdo = getDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('SET NAMES utf8mb4');

$sourceFiles = [
    'AT' => 'AT.zip',
    'BE' => 'BE.zip',
    'DE' => 'DE.zip',
    'DK' => 'DK.zip',
    'FR' => 'FR.zip',
    'NL' => 'NL_full.csv.zip',
    'PL' => 'PL.zip',
    'TR' => 'TR.zip',
];
$sourceBase = 'https://download.geonames.org/export/zip/';
$cacheDir = __DIR__ . '/storage/postcodes-cache';
if (!is_dir($cacheDir) && !mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
    throw new RuntimeException('Cannot create postcode cache directory.');
}

$pdo->exec("CREATE TABLE IF NOT EXISTS postcodes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    country_id INT UNSIGNED NOT NULL,
    city_id INT UNSIGNED DEFAULT NULL,
    postcode VARCHAR(20) NOT NULL,
    postcode_normalized VARCHAR(20) NOT NULL,
    lat DECIMAL(10,7) NOT NULL,
    lng DECIMAL(10,7) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_postcode_country_city (country_id, city_id, postcode_normalized),
    KEY idx_postcode_country (country_id, postcode_normalized),
    CONSTRAINT fk_postcodes_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
    CONSTRAINT fk_postcodes_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$countryIds = [];
$stmt = $pdo->query('SELECT id, code FROM countries WHERE is_active = 1');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $countryIds[strtoupper($row['code'])] = (int)$row['id'];

$cityIds = [];
$stmt = $pdo->query('SELECT id, country_id, name FROM cities WHERE is_active = 1');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $key = (int)$row['country_id'] . '|' . normalizeName($row['name']);
    $cityIds[$key] = (int)$row['id'];
}

$insert = $pdo->prepare('INSERT IGNORE INTO postcodes (country_id, city_id, postcode, postcode_normalized, lat, lng) VALUES (?, ?, ?, ?, ?, ?)');
$total = 0;
foreach ($sourceFiles as $code => $fileName) {
    if (!isset($countryIds[$code])) { fwrite(STDERR, "Skipping {$code}: country not in countries table.\n"); continue; }
    $zipPath = $cacheDir . '/' . $fileName;
    if (!is_file($zipPath)) downloadFile($sourceBase . rawurlencode($fileName), $zipPath);
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) throw new RuntimeException("Cannot open {$fileName}");
    $entry = null;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if ($name && substr($name, -1) !== '/') { $entry = $name; break; }
    }
    if ($entry === null) { $zip->close(); continue; }
    $stream = $zip->getStream($entry);
    if (!$stream) { $zip->close(); throw new RuntimeException("Cannot read {$fileName}"); }
    $countryId = $countryIds[$code];
    while (($line = fgets($stream)) !== false) {
        $fields = explode("\t", trim($line));
        if (count($fields) < 11) continue;
        $postcode = trim($fields[1]);
        $place = trim($fields[2]);
        $lat = filter_var($fields[9], FILTER_VALIDATE_FLOAT);
        $lng = filter_var($fields[10], FILTER_VALIDATE_FLOAT);
        if ($postcode === '' || $lat === false || $lng === false) continue;
        $normalized = strtoupper(preg_replace('/\s+/', '', $postcode));
        $cityId = $cityIds[$countryId . '|' . normalizeName($place)] ?? null;
        $insert->execute([$countryId, $cityId, $postcode, $normalized, $lat, $lng]);
        $total += $insert->rowCount();
    }
    fclose($stream); $zip->close();
    echo "{$code} imported.\n";
}
echo "Done. {$total} postcode rows added.\n";

function normalizeName(string $value): string {
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    return preg_replace('/[^a-z0-9]+/', ' ', $converted !== false ? $converted : $value);
}
function downloadFile(string $url, string $destination): void {
    $context = stream_context_create(['http' => ['timeout' => 120, 'follow_location' => 1, 'user_agent' => 'AvrupaPazari postcode importer']]);
    $data = @file_get_contents($url, false, $context);
    if ($data === false || strlen($data) < 100) throw new RuntimeException("Download failed: {$url}");
    if (file_put_contents($destination, $data, LOCK_EX) === false) throw new RuntimeException("Cannot save {$destination}");
}
