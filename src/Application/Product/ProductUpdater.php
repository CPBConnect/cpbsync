<?php

namespace CPBConnect\Application\Product;

use Product;

class ProductUpdater
{
    private ProductDataApplier $dataApplier;
    private ProductManufacturerApplier $manufacturerApplier;
    private ProductCategoryApplier $categoryApplier;
    private ProductImageApplier $imageApplier;
    private ProductStockApplier $stockApplier;

    public function __construct()
    {
        $this->dataApplier = new ProductDataApplier();
        $this->manufacturerApplier = new ProductManufacturerApplier();
        $this->categoryApplier = new ProductCategoryApplier();
        $this->imageApplier = new ProductImageApplier();
        $this->stockApplier = new ProductStockApplier();
    }

    public function update(int $idProduct, array $data): bool
    {
        $product = new Product($idProduct);

        if (!\Validate::isLoadedObject($product)) {
            throw new \RuntimeException(
                'El producto no existe en PrestaShop.'
            );
        }

        $this->dataApplier->apply($product, $data);
        $this->stockApplier->apply($product, $data);
        $this->manufacturerApplier->apply($product, $data);
        $this->categoryApplier->apply($product, $data);
        $this->imageApplier->apply($product, $data);

        return (bool) $product->update();
    }
}