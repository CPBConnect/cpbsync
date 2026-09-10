<?php

require_once __DIR__ . '/HttpSourceReader.php';
require_once __DIR__ . '/CsvSourceReader.php';

use CPBConnect\Infrastructure\Source\HttpSourceReader;
use CPBConnect\Infrastructure\Source\CsvSourceReader;

$url = 'AQUI_TU_URL_CSV';

$httpReader = new HttpSourceReader();

$content = $httpReader->read($url);

$csvReader = new CsvSourceReader();

$result = $csvReader->parse($content);

echo '<pre>';
print_r($result);
echo '</pre>';