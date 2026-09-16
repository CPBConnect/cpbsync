<?php

namespace CPBConnect\Application\Sync;

use CPBConnect\Infrastructure\Persistence\SourceRepository;
use CPBConnect\Infrastructure\Persistence\SyncLogRepository;
use RuntimeException;

/**
 * Consulta el historial de sincronizaciones.
 */
class SyncHistoryService
{
    public function __construct(
        private SyncLogRepository $logRepository,
        private SourceRepository $sourceRepository
    ) {
    }

    /**
     * Últimas ejecuciones, con el nombre de su fuente.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recent(int $limit = 50): array
    {
        $logs = $this->logRepository->findAll($limit);

        foreach ($logs as &$log) {
            $source = $this->sourceRepository->findById(
                (int) $log['id_source']
            );

            $log['source_name'] =
                $source['name'] ?? null;
        }

        unset($log);

        return $logs;
    }

    /**
     * Detalle de una ejecución.
     *
     * @return array{log: array, source: ?array, details: array}
     */
    public function detail(int $logId): array
    {
        $log = $this->logRepository->findById($logId);

        if ($log === null) {
            throw new RuntimeException(
                'The run does not exist.'
            );
        }

        $source = $this->sourceRepository->findById(
            (int) $log['id_source']
        );

        return [
            'log' => $log,
            'source' => $source,
            'details' => $this->decodeDetails($log),
        ];
    }

    private function decodeDetails(array $log): array
    {
        if (empty($log['details'])) {
            return [];
        }

        $decoded = json_decode($log['details'], true);

        return is_array($decoded) ? $decoded : [];
    }
}
