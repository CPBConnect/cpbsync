<?php

namespace CPBConnect\Application\Import;

use CPBConnect\Application\Source\Reader\SourceReaderRegistry;
use CPBConnect\Infrastructure\Persistence\ImportRepository;
use CPBConnect\Infrastructure\Persistence\SourceRepository;
use RuntimeException;

/**
 * Creación y avance de las importaciones por lotes.
 */
class ImportService
{
    public function __construct(
        private SourceRepository $sourceRepository,
        private ImportRepository $importRepository,
        private SourceReaderRegistry $readers,
        private ImportFileStorage $fileStorage,
        private ImportBatchProcessor $batchProcessor
    ) {
    }

    /**
     * Guarda el archivo subido y prepara la importación.
     *
     * @return array{import_id: int, total: int}
     */
    public function createFromUpload(int $sourceId, array $file): array
    {
        $source = $this->sourceRepository->findById($sourceId);

        if ($source === null) {
            throw new RuntimeException(
                'The selected source does not exist.'
            );
        }

        $reader = $this->readers->get(
            (string) ($source['type'] ?? '')
        );

        if ($reader === null) {
            throw new RuntimeException(
                'The source type is not supported.'
            );
        }

        $filePath = $this->fileStorage->store($file);

        $total = $reader->countFileRows($filePath);

        if ($total <= 0) {
            throw new RuntimeException(
                'The file contains no products.'
            );
        }

        return [
            'import_id' => $this->importRepository->create(
                $sourceId,
                $total,
                $filePath,
                'manual'
            ),
            'total' => $total,
        ];
    }

    /**
     * Procesa el siguiente lote de una importación.
     *
     * @return array<string, int|string>
     */
    public function process(int $importId): array
    {
        $import = $this->batchProcessor->process($importId);

        $total = (int) $import['total'];
        $processed = (int) $import['processed'];

        return [
            'id_import' => (int) $import['id_import'],
            'status' => $import['status'],
            'total' => $total,
            'processed' => $processed,
            'success_count' => (int) $import['success'],
            'errors' => (int) $import['errors'],
            'progress' => $total > 0
                ? (int) round(($processed / $total) * 100)
                : 100,
        ];
    }
}
