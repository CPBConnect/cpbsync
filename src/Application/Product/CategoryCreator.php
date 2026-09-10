<?php

namespace CPBConnect\Application\Product;

use Category;

class CategoryCreator
{
    public function create(string $name): int
    {
        $name = trim($name);

        if ($name === '') {
            throw new \RuntimeException(
                'No se puede crear una categoría sin nombre.'
            );
        }

        $category = new Category();

        $category->name = [
            (int) \Configuration::get('PS_LANG_DEFAULT') => $name,
        ];

        $category->link_rewrite = [
            (int) \Configuration::get('PS_LANG_DEFAULT') =>
                \Tools::link_rewrite($name),
        ];

        $category->id_parent =
            (int) \Configuration::get('PS_ROOT_CATEGORY');

        $category->active = 1;

        if (!$category->add()) {
            throw new \RuntimeException(
                'No fue posible crear la categoría en PrestaShop.'
            );
        }

        return (int) $category->id;
    }
}