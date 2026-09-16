<?php

namespace CPBConnect\Infrastructure\Persistence;

use Db;

/**
 * Lecturas en bloque del estado del catálogo.
 *
 * Se usan para resolver muchas referencias de una sola consulta en
 * lugar de preguntar producto a producto.
 */
class ProductStateRepository
{
    /**
     * Identificadores de producto por referencia.
     *
     * @param array<int, string> $references
     *
     * @return array<string, int>
     */
    public function findIdsByReferences(array $references): array
    {
        if ($references === []) {
            return [];
        }

        $quoted = [];

        foreach ($references as $reference) {
            $quoted[] = '\'' . pSQL((string) $reference) . '\'';
        }

        $sql = 'SELECT `id_product`, `reference`
                FROM `' . _DB_PREFIX_ . 'product`
                WHERE `reference` IN (' . implode(', ', $quoted) . ')';

        $map = [];

        foreach ((array) Db::getInstance()->executeS($sql) as $row) {
            $map[(string) $row['reference']] = (int) $row['id_product'];
        }

        return $map;
    }

    /**
     * Valores guardados de un campo, por identificador de producto.
     *
     * @param array<int, int> $ids
     *
     * @return array<int, string>
     */
    public function findValuesByField(array $ids, string $field): array
    {
        if ($ids === []) {
            return [];
        }

        $sql = 'SELECT `id_product`, `source_value`
                FROM `' . _DB_PREFIX_ . 'cpbsync_product_meta`
                WHERE `field` = \'' . pSQL($field) . '\'
                AND `id_product` IN ('
                . implode(', ', array_map('intval', $ids)) . ')';

        $map = [];

        foreach ((array) Db::getInstance()->executeS($sql) as $row) {
            $map[(int) $row['id_product']] = (string) $row['source_value'];
        }

        return $map;
    }
}
