<?php

namespace CPBConnect\Infrastructure\Persistence;

use Db;

class ProductMetaRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'cpbsync_product_meta';
    }

    public function save(
        int $idProduct,
        string $field,
        string $sourceValue
    ): bool {
        if ($idProduct <= 0) {
            throw new \InvalidArgumentException(
                'The product ID is not valid.'
            );
        }

        $now = date('Y-m-d H:i:s');

        return (bool) Db::getInstance()->execute(
            'INSERT INTO `' . $this->table . '`
            (
                `id_product`,
                `field`,
                `source_value`,
                `date_add`,
                `date_upd`
            )
            VALUES (
                ' . $idProduct . ',
                \'' . pSQL($field) . '\',
                \'' . pSQL($sourceValue) . '\',
                \'' . pSQL($now) . '\',
                \'' . pSQL($now) . '\'
            )
            ON DUPLICATE KEY UPDATE
                `source_value` = VALUES(`source_value`),
                `date_upd` = VALUES(`date_upd`)'
        );
    }

    public function find(
        int $idProduct,
        string $field
    ): ?string {
        if ($idProduct <= 0) {
            return null;
        }

        $sql = 'SELECT `source_value`
                FROM `' . $this->table . '`
                WHERE `id_product` = ' . $idProduct . '
                AND `field` = \'' . pSQL($field) . '\'';

        $value = Db::getInstance()->getValue($sql);

        return $value !== false
            ? (string) $value
            : null;
    }
}