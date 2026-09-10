<?php

namespace CPBConnect\Application\Cron;

use CPBConnect\Application\Mapping\MappingConfigurationBuilder;
use CPBConnect\Application\Product\ProductMapper;
use CPBConnect\Application\Product\ProductSync;
use CPBConnect\Application\Source\CsvSourceService;
use CPBConnect\Infrastructure\Persistence\MappingRepository;
use CPBConnect\Infrastructure\Persistence\SourceRepository;
use CPBConnect\Infrastructure\Persistence\SyncLogRepository;

class CronRunner
{
    private SourceRepository $sourceRepository;
    private MappingRepository $mappingRepository;
    private SyncLogRepository $logRepository;
    private CsvSourceService $csvService;
    private MappingConfigurationBuilder $mappingBuilder;
    private ProductMapper $productMapper;
    private ProductSync $productSync;

    public function __construct()
    {
        $this->sourceRepository = new SourceRepository();
        $this->mappingRepository = new MappingRepository();
        $this->logRepository = new SyncLogRepository();

        $this->csvService = new CsvSourceService();
        $this->mappingBuilder = new MappingConfigurationBuilder();
        $this->productMapper = new ProductMapper();
        $this->productSync = new ProductSync();
    }

    public function run(): array
    {

        $sources = $this->sourceRepository->findAll();

        $results = [];

        foreach ($sources as $source) {
            if (!(int) $source['active']) {
                continue;
            }

            if (!$this->shouldRun($source)) {
                continue;
            }

            try {
                $result = $this->runSource($source);

                $results[] = [
                    'id_source' => (int) $source['id_source'],
                    'status' => 'success',
                    'result' => $result,
                ];
            } catch (\Throwable $e) {
                $errorResult = [
                    'total' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'errors' => 1,
                    'items' => [
                        [
                            'reference' => '',
                            'status' => 'error',
                            'errors' => [
                                $e->getMessage(),
                            ],
                        ],
                    ],
                ];

                $this->logRepository->create(
                    (int) $source['id_source'],
                    $errorResult,
                    'cron'
                );

                $results[] = [
                    'id_source' => (int) $source['id_source'],
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    private function runSource(array $source): array
    {
        if ($source['type'] !== 'csv') {
            throw new \RuntimeException(
                'Por ahora solo se pueden ejecutar fuentes CSV.'
            );
        }

        $result = $this->csvService->read(
            $source['url']
        );

        if (empty($result['rows'])) {
            throw new \RuntimeException(
                'La fuente no contiene registros.'
            );
        }

        $savedMappings =
            $this->mappingRepository->findBySourceId(
                (int) $source['id_source']
            );

        if (empty($savedMappings)) {
            throw new \RuntimeException(
                'La fuente no tiene un mapping configurado.'
            );
        }

        $mapping =
            $this->mappingBuilder->build(
                $savedMappings
            );

        $products =
            $this->productMapper->map(
                $result['rows'],
                $mapping
            );

        $syncResult =
            $this->productSync->sync(
                $products
            );

        $this->logRepository->create(
            (int) $source['id_source'],
            $syncResult,
            'cron'
        );

        return $syncResult;
    }

    private function shouldRun(array $source): bool
    {
        $frequency = $source['frequency'] ?? 'manual';

        if ($frequency === 'manual') {
            return false;
        }

        $lastLog =
            $this->logRepository->findLatestCronBySourceId(
                (int) $source['id_source']
            );

        if (!$lastLog) {
            return true;
        }

        $lastRun =
            strtotime($lastLog['date_add']);

        if ($lastRun === false) {
            return true;
        }

        $elapsed = time() - $lastRun;

        if ($frequency === 'hourly') {
            return $elapsed >= 3600;
        }

        if ($frequency === '6_hours') {
            return $elapsed >= 21600;
        }

        if ($frequency === 'daily') {
            return $elapsed >= 86400;
        }

        return false;
    }
}