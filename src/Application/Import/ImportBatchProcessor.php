<?php

namespace CPBConnect\Application\Import;

use CPBConnect\Application\Product\ProductMapper;
use CPBConnect\Application\Product\ProductSync;
use CPBConnect\Application\Source\Reader\SourceReaderRegistry;
use CPBConnect\Infrastructure\Persistence\ImportRepository;
use CPBConnect\Infrastructure\Persistence\MappingRepository;
use CPBConnect\Infrastructure\Persistence\SourceRepository;
use RuntimeException;

class ImportBatchProcessor
{
    public function __construct(
        private SourceRepository $sourceRepository,
        private MappingRepository $mappingRepository,
        private ImportRepository $importRepository,
        private SourceReaderRegistry $readers,
        private ProductMapper $productMapper,
        private ProductSync $productSync
    ) {
    }

    /**
     * Mantiene compatibilidad con la importación manual.
     */
    public function process(int $importId): array
    {
        $result = $this->processBatch($importId);

        return $result['import'];
    }

    /**
     * Procesa un batch y devuelve:
     *
     * - estado de la importación
     * - resultado detallado del batch
     */
    public function processBatch(int $importId): array
    {
        $import = $this->importRepository->find($importId);

        if ($import === null) {
            throw new RuntimeException(
                'The import does not exist.'
            );
        }

        if (in_array(
            $import['status'],
            ['completed', 'failed'],
            true
        )) {
            return [
                'import' => $import,
                'batch' => [
                    'total' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'errors' => 0,
                    'items' => [],
                ],
            ];
        }

        $source = $this->sourceRepository->findById(
            (int) $import['id_source']
        );

        if ($source === null) {
            throw new RuntimeException(
                'The source does not exist.'
            );
        }

        $mappings = $this->mappingRepository->findBySourceId(
            (int) $import['id_source']
        );

        $mappings = $this->normalizeMappings($mappings);

        if ($mappings === []) {
            throw new RuntimeException(
                'There are no mappings for the source.'
            );
        }

        $this->importRepository->updateStatus(
            $importId,
            'processing'
        );

        $offset = (int) $import['current_position'];

        $reader = $this->readers->get(
            (string) ($source['type'] ?? '')
        );

        if ($reader === null) {
            throw new RuntimeException(
                'The source type is not supported.'
            );
        }

        $batchSize = max(1, $reader->getBatchSize());

        if (!empty($import['file_path'])) {
            $rows = $reader->readBatchFromFile(
                $import['file_path'],
                $offset,
                $batchSize
            );
        } else {
            $rows = $reader->readBatch(
                $source,
                $offset,
                $batchSize
            );
        }

        /*
         * Ya no hay más registros.
         */
        if ($rows === []) {
            $this->importRepository->updateStatus(
                $importId,
                'completed'
            );

            return [
                'import' => $this->importRepository->find(
                    $importId
                ),
                'batch' => [
                    'total' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'errors' => 0,
                    'items' => [],
                ],
            ];
        }

        $products = $this->productMapper->map(
            $rows,
            $mappings
        );

        /*
         * Procesamos los 50 productos.
         *
         * ProductSync ya se encarga de capturar
         * los errores individuales.
         */
        $syncResult = $this->productSync->sync(
            $products,
            SyncOptions::fromSource($source)
        );

        $success =
            (int) $syncResult['created']
            + (int) $syncResult['updated']
            + (int) $syncResult['skipped'];

        $errors =
            (int) $syncResult['errors'];

        $processed =
            $offset + count($rows);

        $totalSuccess =
            (int) $import['success']
            + $success;

        $totalErrors =
            (int) $import['errors']
            + $errors;

        $this->importRepository->updateProgress(
            $importId,
            $processed,
            $totalSuccess,
            $totalErrors,
            $processed
        );

        $status = $processed >= (int) $import['total']
            ? 'completed'
            : 'processing';

        $this->importRepository->updateStatus(
            $importId,
            $status
        );

        if ($status === 'completed') {
            $completedImport =
                $this->importRepository->find($importId);

            if ($completedImport !== null) {
                $this->cleanupImportFile($completedImport);
            }

            $this->importRepository->clearFilePath(
                $importId
            );
        }

        return [
            'import' => $this->importRepository->find(
                $importId
            ),
            'batch' => [
                'total' => (int) $syncResult['total'],
                'created' => (int) $syncResult['created'],
                'updated' => (int) $syncResult['updated'],
                'skipped' => (int) $syncResult['skipped'],
                'errors' => (int) $syncResult['errors'],
                'items' => $syncResult['items'] ?? [],
            ],
        ];
    }

    private function normalizeMappings(array $mappings): array
    {
        $result = [];

        foreach ($mappings as $mapping) {
            $sourceField = (string) (
                $mapping['source_field'] ?? ''
            );

            $targetField = (string) (
                $mapping['target_field'] ?? ''
            );

            if ($sourceField === '' || $targetField === '') {
                continue;
            }

            $transformConfig = [];

            if (!empty($mapping['transform_config'])) {
                $decoded = json_decode(
                    $mapping['transform_config'],
                    true
                );

                if (is_array($decoded)) {
                    $transformConfig = $decoded;
                }
            }

            $result[$sourceField] = [
                'target' => $targetField,
                'transform' => (string) (
                    $mapping['transform'] ?? 'none'
                ),
                'config' => $transformConfig,
            ];
        }

        return $result;
    }

    private function cleanupImportFile(array $import): void
    {
        $filePath = $import['file_path'] ?? null;

        if (
            empty($filePath) ||
            !is_file($filePath)
        ) {
            return;
        }

        if (!unlink($filePath)) {
            // No hacemos fallar la importación
            // solamente porque no se pudo eliminar el archivo.
            return;
        }
    }
}