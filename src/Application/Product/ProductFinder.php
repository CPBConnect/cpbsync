<?php

namespace CPBConnect\Application\Product;

use Db;

class ProductFinder
{
    public function findByReference(string $reference): ?int
    {
        $reference = pSQL($reference);

        $sql = 'SELECT `id_product`
                FROM `' . _DB_PREFIX_ . 'product`
                WHERE `reference` = \'' . $reference . '\'';

        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            return null;
        }

        return (int) $result['id_product'];
    }
}