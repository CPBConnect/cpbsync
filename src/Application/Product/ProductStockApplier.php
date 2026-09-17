<?php

namespace CPBConnect\Application\Product;

use CPBConnect\Application\Sync\SyncOptions;
use Product;

class ProductStockApplier
{
    public function apply(
        Product $product,
        array $data,
        ?SyncOptions $options = null
    ): void {
        if ($options !== null && $options->skipsStock()) {
            return;
        }

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