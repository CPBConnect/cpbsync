<?php

namespace CPBConnect\Application\Product;

use CPBConnect\Application\Sync\SyncOptions;
use Product;

class ProductDataApplier
{
    public function apply(
        Product $product,
        array $data,
        ?SyncOptions $options = null
    ): void {
        $fillEmpty = $options !== null && $options->fillEmpty();

        if (
            isset($data['reference'])
            && $this->shouldWrite($product, 'reference', $fillEmpty)
        ) {
            $product->reference = (string) $data['reference'];
        }

        if (
            isset($data['name'])
            && $this->shouldWrite($product, 'name', $fillEmpty)
        ) {
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

        if (
            isset($data['description'])
            && $this->shouldWrite($product, 'description', $fillEmpty)
        ) {
            $product->description = [
                (int) \Configuration::get('PS_LANG_DEFAULT') =>
                    (string) $data['description'],
            ];
        }

        if (
            isset($data['price'])
            && $this->shouldWrite($product, 'price', $fillEmpty)
        ) {
            $product->price = (float) $data['price'];
        }

        if (
            isset($data['ean13'])
            && $this->shouldWrite($product, 'ean13', $fillEmpty)
        ) {
            $product->ean13 = (string) $data['ean13'];
        }
    }

    /**
     * ¿Se puede escribir este campo?
     *
     * Con "rellenar sólo los campos vacíos" se respeta lo que ya tiene
     * el producto, que es lo que quiere quien edita precios o
     * descripciones a mano.
     */
    private function shouldWrite(
        Product $product,
        string $field,
        bool $fillEmpty
    ): bool {
        if (!$fillEmpty) {
            return true;
        }

        return $this->isEmpty($product, $field);
    }

    private function isEmpty(Product $product, string $field): bool
    {
        if ($field === 'price') {
            // Un precio a cero es "todavía sin precio".
            return (float) $product->price <= 0;
        }

        $value = $product->{$field} ?? '';

        if (is_array($value)) {
            $value = $value[(int) \Configuration::get('PS_LANG_DEFAULT')]
                ?? '';
        }

        return trim((string) $value) === '';
    }
}
