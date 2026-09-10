<?php

namespace CPBConnect\Infrastructure\Persistence;

use Db;

class SyncLogRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'cpbsync_sync_log';
    }

    public function create(
        int $sourceId,
        array $result,
        string $executionType = 'manual'
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

        $details = json_encode(
            $result['items'] ?? [],
            JSON_UNESCAPED_UNICODE
        );

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
            'date_add' => date('Y-m-d H:i:s'),
        ];

        $success = Db::getInstance()->insert(
            'cpbsync_sync_log',
            $data
        );

        if (!$success) {
            throw new \RuntimeException(
                'No fue posible guardar el log de sincronización.'
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