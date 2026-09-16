<?php

namespace CPBConnect\Application\Cron;

use CPBConnect\Application\Import\ImportBatchProcessor;
use CPBConnect\Application\Product\ProductMapper;
use CPBConnect\Application\Product\ProductSync;
use CPBConnect\Application\Source\Reader\SourceReaderRegistry;
use CPBConnect\Infrastructure\Persistence\ImportRepository;
use CPBConnect\Infrastructure\Persistence\MappingRepository;
use CPBConnect\Infrastructure\Persistence\SourceRepository;
use CPBConnect\Infrastructure\Persistence\SyncLogRepository;
use CPBConnect\Infrastructure\Source\SourceReaderFactory;

class CronRunner
{
    private SourceRepository $sourceRepository;
    private MappingRepository $mappingRepository;
    private SyncLogRepository $logRepository;
    private ImportRepository $importRepository;
    private SourceReaderRegistry $readers;
    private ImportBatchProcessor $batchProcessor;

    public function __construct()
    {
        $this->sourceRepository =
            new SourceRepository();

        $this->mappingRepository =
            new MappingRepository();

        $this->logRepository =
            new SyncLogRepository();

        $this->importRepository =
            new ImportRepository();

        $this->readers =
            SourceReaderFactory::create();

        $this->batchProcessor =
            new ImportBatchProcessor(
                $this->sourceRepository,
                $this->mappingRepository,
                $this->importRepository,
                $this->readers,
                new ProductMapper(),
                new ProductSync()
            );
    }

    public function run(): array
    {
        $sources =
            $this->sourceRepository->findAll();

        $results = [];

        foreach ($sources as $source) {

            if (!(int) $source['active']) {
                continue;
            }

            if (!$this->shouldRun($source)) {
                continue;
            }

            try {

                $result =
                    $this->runSource($source);

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
        $reader = $this->readers->get(
            (string) ($source['type'] ?? '')
        );

        if ($reader === null) {
            throw new \RuntimeException(
                'The source type is not supported.'
            );
        }

        /*
         * Evitamos tomar una importación manual
         * que haya quedado pendiente.
         */
        $import =
            $this->findPendingCronImport(
                (int) $source['id_source']
            );

        /*
         * Si no existe una importación pendiente,
         * creamos una nueva.
         */
        if ($import === null) {

            $total = $reader->countRows($source);

            if ($total === 0) {
                throw new \RuntimeException(
                    'The source contains no records.'
                );
            }

            $mappings =
                $this->mappingRepository->findBySourceId(
                    (int) $source['id_source']
                );

            if (empty($mappings)) {
                throw new \RuntimeException(
                    'The source has no mapping configured.'
                );
            }

            $importId =
                $this->importRepository->create(
                    (int) $source['id_source'],
                    $total,
                    null,
                    'cron'
                );

            $import =
                $this->importRepository->find(
                    $importId
                );
        }

        if ($import === null) {
            throw new \RuntimeException(
                'The import could not be retrieved.'
            );
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $items = [];

        /*
         * Procesamos todos los batches.
         */
        while (true) {

            $batch =
                $this->batchProcessor->processBatch(
                    (int) $import['id_import']
                );

            $batchResult =
                $batch['batch'];

            $created +=
                (int) $batchResult['created'];

            $updated +=
                (int) $batchResult['updated'];

            $skipped +=
                (int) $batchResult['skipped'];

            $errors +=
                (int) $batchResult['errors'];

            if (!empty($batchResult['items'])) {
                $items = array_merge(
                    $items,
                    $batchResult['items']
                );
            }

            $import =
                $batch['import'];

            if (
                $import === null ||
                $import['status'] === 'completed' ||
                $import['status'] === 'failed'
            ) {
                break;
            }
        }

        $total =
            $created
            + $updated
            + $skipped
            + $errors;

        $result = [
            'total' => $total,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'items' => $items,
        ];

        /*
         * Un solo log final por ejecución cron.
         */
        $this->logRepository->create(
            (int) $source['id_source'],
            $result,
            'cron'
        );

        return $result;
    }

    private function shouldRun(array $source): bool
    {
        $frequency =
            $source['frequency'] ?? 'manual';

        if ($frequency === 'manual') {
            return false;
        }

        $lastLog =
            $this->logRepository
                ->findLatestCronBySourceId(
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

        $elapsed =
            time() - $lastRun;

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

    private function findPendingCronImport(
        int $sourceId
    ): ?array {
        $import =
            $this->importRepository
                ->findPendingBySourceId(
                    $sourceId
                );

        if ($import === null) {
            return null;
        }

        /*
         * No reutilizamos una importación manual.
         */
        if (
            ($import['execution_type'] ?? 'manual')
            !== 'cron'
        ) {
            return null;
        }

        return $import;
    }
}