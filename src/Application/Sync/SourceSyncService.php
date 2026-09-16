<?php

namespace CPBConnect\Application\Sync;

use CPBConnect\Application\Mapping\MappingConfigurationBuilder;
use CPBConnect\Application\Product\ProductDryRun;
use CPBConnect\Application\Product\ProductMapper;
use CPBConnect\Application\Product\ProductSync;
use CPBConnect\Application\Source\SourceService;
use CPBConnect\Infrastructure\Persistence\MappingRepository;
use CPBConnect\Infrastructure\Persistence\SyncLogRepository;
use RuntimeException;

/**
 * Ejecuta el Dry Run y la sincronización de una fuente.
 *
 * Centraliza la carga de registros y de mapping que antes estaba
 * duplicada entre las acciones de administrador.
 */
class SourceSyncService
{
    public function __construct(
        private SourceService $sources,
        private MappingRepository $mappingRepository,
        private MappingConfigurationBuilder $mappingBuilder,
        private ProductMapper $productMapper,
        private ProductDryRun $productDryRun,
        private ProductSync $productSync,
        private SyncLogRepository $logRepository
    ) {
    }

    /**
     * @return array{source: array, products: array}
     */
    public function dryRun(int $sourceId, int $limit = 5): array
    {
        $source = $this->requireSource($sourceId);
        $rows = $this->requireRows($source);
        $mapping = $this->requireMapping($sourceId);

        return [
            'source' => $source,
            'products' => $this->productDryRun->run(
                $rows,
                $mapping,
                $limit
            ),
        ];
    }

    /**
     * @return array{source: array, result: array, log_id: int}
     */
    public function sync(int $sourceId): array
    {
        $source = $this->requireSource($sourceId);
        $rows = $this->requireRows($source);
        $mapping = $this->requireMapping($sourceId);

        $products = $this->productMapper->map($rows, $mapping);

        $result = $this->productSync->sync($products);

        return [
            'source' => $source,
            'result' => $result,
            'log_id' => $this->logRepository->create(
                $sourceId,
                $result,
                'manual'
            ),
        ];
    }

    private function requireSource(int $sourceId): array
    {
        $source = $this->sources->find($sourceId);

        if ($source === null) {
            throw new RuntimeException('The source does not exist.');
        }

        return $source;
    }

    private function requireRows(array $source): array
    {
        $result = $this->sources->read($source);

        if (empty($result['rows'])) {
            throw new RuntimeException(
                'The source contains no records.'
            );
        }

        return $result['rows'];
    }

    private function requireMapping(int $sourceId): array
    {
        $savedMappings =
            $this->mappingRepository->findBySourceId($sourceId);

        if (empty($savedMappings)) {
            throw new RuntimeException(
                'The source has no mapping configured.'
            );
        }

        return $this->mappingBuilder->build($savedMappings);
    }
}
