<?php

namespace CPBConnect\Application\Source\Reader;

use CPBConnect\Application\Source\CsvSourceService;
use CPBConnect\Infrastructure\Source\CsvSourceReader;

/**
 * Lector de catálogos CSV.
 *
 * Mantiene la lectura por lotes en streaming, sin descargar el
 * catálogo completo en cada paso.
 */
class CsvReader extends AbstractSourceReader
{
    public function __construct(
        private CsvSourceService $catalogService,
        private CsvSourceReader $csvSourceReader
    ) {
    }

    public function getType(): string
    {
        return 'csv';
    }

    public function getLabel(): string
    {
        return 'CSV';
    }

    public function getFileExtensions(): array
    {
        return ['csv'];
    }

    public function read(array $source): array
    {
        return $this->catalogService->read(
            (string) $source['url']
        );
    }

    public function readBatch(
        array $source,
        int $offset,
        int $limit
    ): array {
        return $this->csvSourceReader->readBatch(
            (string) $source['url'],
            $offset,
            $limit
        );
    }

    public function readBatchFromFile(
        string $path,
        int $offset,
        int $limit
    ): array {
        return $this->csvSourceReader->readBatchFromFile(
            $path,
            $offset,
            $limit
        );
    }

    public function countRows(array $source): int
    {
        return $this->csvSourceReader->countRows(
            (string) $source['url']
        );
    }

    public function countFileRows(string $path): int
    {
        return $this->csvSourceReader->countFileRows($path);
    }
}
