<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/autoload.php';

use Biorrhythms\ApiV1;

[$status, $body] = ApiV1::handle($_GET, new DateTimeImmutable('now'));

http_response_code($status);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
if (($_GET['pretty'] ?? '0') === '1') {
    $flags |= JSON_PRETTY_PRINT;
}

echo json_encode($body, $flags);
