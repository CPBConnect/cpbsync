<?php

namespace CPBConnect\Application\Source;

use CPBConnect\Infrastructure\Source\CsvSourceReader;
use CPBConnect\Infrastructure\Source\HttpSourceReader;

class CsvSourceService
{
    private HttpSourceReader $httpReader;
    private CsvSourceReader $csvReader;

    public function __construct()
    {
        $this->httpReader = new HttpSourceReader();
        $this->csvReader = new CsvSourceReader();
    }

    public function read(string $url): array
    {
        $content = $this->httpReader->read($url);

        return $this->csvReader->parse($content);
    }
}