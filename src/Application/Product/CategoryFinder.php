<?php

namespace CPBConnect\Application\Product;

use Category;

class CategoryFinder
{
    public function findByName(string $name): ?int
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        $sql = 'SELECT `c`.`id_category`
            FROM `' . _DB_PREFIX_ . 'category` c
            INNER JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                ON cl.`id_category` = c.`id_category`
            WHERE cl.`name` = \'' . pSQL($name) . '\'
              AND cl.`id_lang` = ' . (int) \Configuration::get('PS_LANG_DEFAULT') . '
            ORDER BY c.`id_category` ASC';

        $idCategory = (int) \Db::getInstance()->getValue($sql);

        if ($idCategory <= 0) {
            return null;
        }

        return $idCategory;
    }
}