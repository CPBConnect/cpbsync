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
            $name = (string) $data['name'];

            $languageId = (int) \Configuration::get('PS_LANG_DEFAULT');

            $product->name = [$languageId => $name];

            /*
             * PrestaShop no genera la URL amable por su cuenta: sin
             * link_rewrite el producto queda sin URL propia. Sólo se
             * rellena cuando falta, para no pisar las direcciones que la
             * tienda ya tenga personalizadas.
             */
            $current = is_array($product->link_rewrite)
                ? ($product->link_rewrite[$languageId] ?? '')
                : (string) $product->link_rewrite;

            if (trim((string) $current) === '' && trim($name) !== '') {
                $product->link_rewrite = [
                    $languageId => \Tools::str2url($name),
                ];
            }
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