<?php

namespace CPBConnect\Application\Product;

use Product;

class ProductStockApplier
{
    public function apply(Product $product, array $data): void
    {
        if (!isset($data['quantity'])) {
            return;
        }

        \StockAvailable::setQuantity(
            (int) $product->id,
            0,
            (int) $data['quantity']
        );
    }
}