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

    public function __construct(
        ?ProductImageProviderInterface $images = null
    ) {
        $this->dataApplier = new ProductDataApplier();
        $this->manufacturerApplier = new ProductManufacturerApplier();
        $this->categoryApplier = new ProductCategoryApplier();
        $this->imageApplier = new ProductImageApplier($images);
        $this->stockApplier = new ProductStockApplier();
    }

    public function update(int $idProduct, array $data): bool
    {
        $product = new Product($idProduct);

        if (!\Validate::isLoadedObject($product)) {
            throw new \RuntimeException(
                'The product does not exist in PrestaShop.'
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