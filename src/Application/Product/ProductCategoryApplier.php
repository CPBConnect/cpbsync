<?php

namespace CPBConnect\Application\Product;

use Product;

class ProductCategoryApplier
{
    private CategoryFinder $finder;
    private CategoryCreator $creator;

    public function __construct()
    {
        $this->finder = new CategoryFinder();
        $this->creator = new CategoryCreator();
    }

    public function apply(Product $product, array $data): void
    {
        if (!isset($data['category'])) {
            return;
        }

        $categoryName = trim(
            (string) $data['category']
        );

        if ($categoryName === '') {
            return;
        }

        $idCategory =
            $this->finder->findByName(
                $categoryName
            );

        if ($idCategory === null) {
            $idCategory =
                $this->creator->create(
                    $categoryName
                );
        }

        $categories = $product->getCategories();

        $categories[] = $idCategory;

        $product->updateCategories(
            array_unique($categories)
        );
    }
}