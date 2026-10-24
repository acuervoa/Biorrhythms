<?php

declare(strict_types=1);

require_once __DIR__ . '/src/autoload.php';

use Biorrhythms\IcsFeed;

[$status, $contentType, $body] = IcsFeed::handle($_GET, new DateTimeImmutable('now'));

http_response_code($status);
header('Content-Type: ' . $contentType);
header('Cache-Control: no-store');
if ($status === 200) {
    header('Content-Disposition: attachment; filename="biorritmos-criticos.ics"');
}

echo $body;
