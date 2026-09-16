<?php

namespace CPBConnect\Infrastructure\Persistence;

use Db;

class MappingRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'cpbsync_mapping';
    }

    public function create(
        int $sourceId,
        string $sourceField,
        string $targetField,
        string $transform = 'none',
        ?string $transformConfig = null
    ): int {
        if ($sourceId <= 0) {
            throw new \InvalidArgumentException(
                'The source ID is not valid.'
            );
        }

        $result = Db::getInstance()->insert(
            'cpbsync_mapping',
            [
                'id_source' => $sourceId,
                'source_field' => pSQL($sourceField),
                'target_field' => pSQL($targetField),
                'transform' => pSQL($transform),
                'transform_config' => $transformConfig !== null
                    ? pSQL($transformConfig)
                    : null,
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
            ]
        );

        if (!$result) {
            throw new \RuntimeException(
                'Unable to create CPB Sync mapping.'
            );
        }

        return (int) Db::getInstance()->Insert_ID();
    }

    public function findBySourceId(int $sourceId): array
    {
        if ($sourceId <= 0) {
            return [];
        }

        $sql = 'SELECT *
                FROM `' . $this->table . '`
                WHERE `id_source` = ' . $sourceId . '
                ORDER BY `id_mapping` ASC';

        return Db::getInstance()->executeS($sql) ?: [];
    }

    public function deleteBySourceId(int $sourceId): bool
    {
        if ($sourceId <= 0) {
            return false;
        }

        return (bool) Db::getInstance()->delete(
            'cpbsync_mapping',
            'id_source = ' . $sourceId
        );
    }

    public function replaceForSource(
        int $sourceId,
        array $mappings
    ): void {
        if ($sourceId <= 0) {
            throw new \InvalidArgumentException(
                'The source ID is not valid.'
            );
        }

        $db = Db::getInstance();

        $db->execute('START TRANSACTION');

        try {
            if (!$db->delete(
                'cpbsync_mapping',
                'id_source = ' . $sourceId
            )) {
                throw new \RuntimeException(
                    'The previous mapping could not be deleted.'
                );
            }

            foreach ($mappings as $mapping) {
                $result = $db->insert(
                    'cpbsync_mapping',
                    [
                        'id_source' => $sourceId,
                        'source_field' => pSQL(
                            $mapping['source_field']
                        ),
                        'target_field' => pSQL(
                            $mapping['target_field']
                        ),
                        'transform' => pSQL(
                            $mapping['transform']
                        ),
                        'transform_config' =>
                            $mapping['transform_config'] !== null
                                ? pSQL(
                                $mapping['transform_config']
                            )
                                : null,
                        'date_add' => date('Y-m-d H:i:s'),
                        'date_upd' => date('Y-m-d H:i:s'),
                    ]
                );

                if (!$result) {
                    throw new \RuntimeException(
                        'One of the mappings could not be saved.'
                    );
                }
            }

            $db->execute('COMMIT');

        } catch (\Throwable $e) {
            $db->execute('ROLLBACK');

            throw $e;
        }
    }
}