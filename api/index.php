<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/autoload.php';

use Biorrhythms\ApiLegacy;

// Deprecated: kept for existing consumers. New integrations should use /api/v1/.
$payload = ApiLegacy::build($_GET, new DateTimeImmutable('now'));

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Deprecation: true');
header('Link: </api/v1/>; rel="successor-version"');

$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
if (($_GET['pretty'] ?? '0') === '1') {
    $flags |= JSON_PRETTY_PRINT;
}

echo json_encode($payload, $flags);
