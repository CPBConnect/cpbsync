<?php

namespace CPBConnect\Infrastructure\Persistence;

use Db;

class SourceRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'cpbsync_source';
    }

    public function create(array $data): int
    {
        $result = Db::getInstance()->insert(
            'cpbsync_source',
            [
                'name' => pSQL($data['name']),
                'type' => pSQL($data['type']),
                'url' => pSQL($data['url']),
                'config' => $this->configForStorage($data),
                'frequency' => pSQL($data['frequency']),
                'active' => !empty($data['active']) ? 1 : 0,
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
            ]
        );

        if (!$result) {
            throw new \RuntimeException(
                'Unable to create CPB Sync source.'
            );
        }

        return (int) Db::getInstance()->Insert_ID();
    }

    public function findAll(): array
    {
        $sql = 'SELECT *
                FROM `' . $this->table . '`
                ORDER BY `id_source` DESC';

        return Db::getInstance()->executeS($sql) ?: [];
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT *
                FROM `' . $this->table . '`
                WHERE `id_source` = ' . (int) $id;

        $result = Db::getInstance()->getRow($sql);

        return $result ?: null;
    }

    public function update(int $id, array $data): bool
    {
        return (bool) Db::getInstance()->update(
            'cpbsync_source',
            [
                'name' => pSQL($data['name']),
                'type' => pSQL($data['type']),
                'url' => pSQL($data['url']),
                'config' => $this->configForStorage($data),
                'frequency' => pSQL($data['frequency']),
                'active' => !empty($data['active']) ? 1 : 0,
                'date_upd' => date('Y-m-d H:i:s'),
            ],
            'id_source = ' . (int) $id
        );
    }

    /**
     * La configuración adicional se guarda tal cual llegue: ya se ha
     * validado como JSON antes de persistirla.
     */
    private function configForStorage(array $data): ?string
    {
        $config = trim((string) ($data['config'] ?? ''));

        return $config === '' ? null : pSQL($config);
    }

    public function delete(int $id): bool
    {
        return (bool) Db::getInstance()->delete(
            'cpbsync_source',
            'id_source = ' . (int) $id
        );
    }
}