<?php

namespace CPBConnect\Application\Product;

use Product;
class ProductDataApplier
{
    public function apply(Product $product, array $data): void
    {
        if (isset($data['reference'])) {
            $product->reference = (string) $data['reference'];
        }

        if (isset($data['name'])) {
            $product->name = [
                (int) \Configuration::get('PS_LANG_DEFAULT') =>
                    (string) $data['name'],
            ];
        }

        if (isset($data['description'])) {
            $product->description = [
                (int) \Configuration::get('PS_LANG_DEFAULT') =>
                    (string) $data['description'],
            ];
        }

        if (isset($data['price'])) {
            $product->price = (float) $data['price'];
        }

        if (isset($data['quantity']) && (int) $product->id > 0) {
            \StockAvailable::setQuantity(
                (int) $product->id,
                0,
                (int) $data['quantity']
            );
        }

        if (isset($data['ean13'])) {
            $product->ean13 = (string) $data['ean13'];
        }
    }
}