<?php

namespace CPBConnect\Application\Product;

use Product;

class ProductCreator
{
    private ProductDataApplier $dataApplier;
    private ProductManufacturerApplier $manufacturerApplier;
    private ProductCategoryApplier $categoryApplier;
    private ProductImageApplier $imageApplier;
    private ProductStockApplier $stockApplier;

    public function __construct(
        ?ProductImageProviderInterface $images = null
    ) {
        $this->dataApplier = new ProductDataApplier();
        $this->manufacturerApplier = new ProductManufacturerApplier();
        $this->categoryApplier = new ProductCategoryApplier();
        $this->imageApplier = new ProductImageApplier($images);
        $this->stockApplier = new ProductStockApplier();
    }

    public function create(array $data): int
    {
        if (empty($data['reference'])) {
            throw new \RuntimeException(
                'A product cannot be created without a reference.'
            );
        }

        if (empty($data['name'])) {
            throw new \RuntimeException(
                'A product cannot be created without a name.'
            );
        }

        $product = new Product();

        $this->dataApplier->apply($product, $data);
        $this->manufacturerApplier->apply($product, $data);

        $product->active = 1;

        if (!$product->add()) {
            throw new \RuntimeException(
                'The product could not be created in PrestaShop.'
            );
        }

        $this->stockApplier->apply($product, $data);
        $this->categoryApplier->apply($product, $data);
        $this->imageApplier->apply($product, $data);

        return (int) $product->id;
    }
}