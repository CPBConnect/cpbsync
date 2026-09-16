<?php

namespace CPBConnect\Infrastructure\Persistence;

use Db;

class SyncLogRepository
{
    /**
     * Items que se guardan de cada ejecución.
     *
     * Guardarlos todos hace inservible la tabla: un catálogo de 50.000
     * productos ocupa unos 4 MB por ejecución y con cron horario son
     * más de 100 MB al día. El resto se resume en items_total.
     */
    private const MAX_STORED_ITEMS = 200;

    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'cpbsync_sync_log';
    }

    /**
     * @param array<string, mixed> $metrics
     */
    public function create(
        int $sourceId,
        array $result,
        string $executionType = 'manual',
        array $metrics = []
    ): int {
        $status = 'success';

        if (($result['errors'] ?? 0) > 0) {
            $status = 'warning';
        }

        if (
            ($result['total'] ?? 0) > 0 &&
            ($result['errors'] ?? 0) === ($result['total'] ?? 0)
        ) {
            $status = 'error';
        }

        $items = is_array($result['items'] ?? null)
            ? $result['items']
            : [];

        $itemsTotal = count($items);

        $details = json_encode(
            array_slice($items, 0, self::MAX_STORED_ITEMS),
            JSON_UNESCAPED_UNICODE
        );

        $phases = !empty($metrics['phases'])
            ? json_encode(
                $metrics['phases'],
                JSON_UNESCAPED_UNICODE
            )
            : null;

        $data = [
            'id_source' => $sourceId,
            'status' => pSQL($status),
            'execution_type' => pSQL($executionType),
            'total' => (int) ($result['total'] ?? 0),
            'created' => (int) ($result['created'] ?? 0),
            'updated' => (int) ($result['updated'] ?? 0),
            'skipped' => (int) ($result['skipped'] ?? 0),
            'errors' => (int) ($result['errors'] ?? 0),
            'details' => $details !== false
                ? pSQL($details)
                : null,
            'items_total' => $itemsTotal,
            'duration_ms' => isset($metrics['duration'])
                ? (int) round((float) $metrics['duration'] * 1000)
                : null,
            'memory_kb' => isset($metrics['memory'])
                ? (int) $metrics['memory']
                : null,
            'phases' => $phases !== null ? pSQL($phases) : null,
            'date_add' => date('Y-m-d H:i:s'),
        ];

        $success = Db::getInstance()->insert(
            'cpbsync_sync_log',
            $data
        );

        if (!$success) {
            throw new \RuntimeException(
                'The synchronization log could not be saved.'
            );
        }

        return (int) Db::getInstance()->Insert_ID();
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT *
                FROM `' . $this->table . '`
                WHERE `id_log` = ' . (int) $id;

        $result = Db::getInstance()->getRow($sql);

        return $result ?: null;
    }

    public function findBySourceId(
        int $sourceId,
        int $limit = 20
    ): array {
        $limit = max(1, $limit);
        $sql = 'SELECT *
                FROM `' . $this->table . '`
                WHERE `id_source` = ' . (int) $sourceId . '
                ORDER BY `id_log` DESC
                LIMIT ' . (int) $limit;

        return Db::getInstance()->executeS($sql) ?: [];
    }

    public function findAll(int $limit = 50): array
    {
        $limit = max(1, $limit);
        $sql = 'SELECT *
            FROM `' . $this->table . '`
            ORDER BY `id_log` DESC
            LIMIT ' . (int) $limit;

        return Db::getInstance()->executeS($sql) ?: [];
    }

    public function findLatestBySourceId(
        int $sourceId
    ): ?array {
        $sql = 'SELECT *
            FROM `' . $this->table . '`
            WHERE `id_source` = ' . (int) $sourceId . '
            ORDER BY `id_log` DESC';

        $result = Db::getInstance()->getRow($sql);

        return $result ?: null;
    }

    public function findLatestCronBySourceId(
        int $sourceId
    ): ?array {
        $sql = 'SELECT *
            FROM `' . $this->table . '`
            WHERE `id_source` = ' . (int) $sourceId . '
            AND `execution_type` = "cron"
            ORDER BY `id_log` DESC';

        $result = Db::getInstance()->getRow($sql);

        return $result ?: null;
    }
}