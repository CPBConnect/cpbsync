<?php

namespace CPBConnect\Infrastructure\Persistence;

use Db;

class ImportRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'cpbsync_import';
    }

    public function create(
        int $sourceId,
        int $total,
        ?string $filePath = null,
        string $executionType = 'manual'
    ): int {
        $now = date('Y-m-d H:i:s');

        $data = [
            'id_source' => $sourceId,
            'file_path' => $filePath !== null
                ? pSQL($filePath)
                : null,
            'execution_type' => pSQL($executionType),
            'status' => 'pending',
            'total' => $total,
            'processed' => 0,
            'success' => 0,
            'errors' => 0,
            'current_position' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $result = Db::getInstance()->insert(
            'cpbsync_import',
            $data
        );

        if (!$result) {
            throw new \RuntimeException(
                'The import could not be created.'
            );
        }

        return (int) Db::getInstance()->Insert_ID();
    }

    public function find(int $importId): ?array
    {
        $sql = 'SELECT *
                FROM `' . pSQL($this->table) . '`
                WHERE `id_import` = ' . (int) $importId;

        $result = Db::getInstance()->getRow($sql);

        return $result ?: null;
    }

    public function updateProgress(
        int $importId,
        int $processed,
        int $success,
        int $errors,
        int $currentPosition
    ): bool {
        return Db::getInstance()->update(
            'cpbsync_import',
            [
                'processed' => $processed,
                'success' => $success,
                'errors' => $errors,
                'current_position' => $currentPosition,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            'id_import = ' . (int) $importId
        );
    }

    public function updateStatus(
        int $importId,
        string $status
    ): bool {
        return Db::getInstance()->update(
            'cpbsync_import',
            [
                'status' => pSQL($status),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            'id_import = ' . (int) $importId
        );
    }

    public function findPendingBySourceId(
        int $sourceId
    ): ?array {
        $sql = 'SELECT *
            FROM `' . pSQL($this->table) . '`
            WHERE `id_source` = ' . (int) $sourceId . '
            AND `status` IN ("pending", "processing")
            ORDER BY `id_import` DESC';

        $result = Db::getInstance()->getRow($sql);

        return $result ?: null;
    }

    public function updateFilePath(
        int $importId,
        string $filePath
    ): bool {
        return Db::getInstance()->update(
            'cpbsync_import',
            [
                'file_path' => pSQL($filePath),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            'id_import = ' . (int) $importId
        );
    }

    public function clearFilePath(int $importId): bool
    {
        return Db::getInstance()->update(
            'cpbsync_import',
            [
                'file_path' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            'id_import = ' . (int) $importId
        );
    }
}