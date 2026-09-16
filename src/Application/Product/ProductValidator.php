<?php

namespace CPBConnect\Application\Product;

class ProductValidator
{
    public function validate(array $product): array
    {
        $errors = [];

        // Reference
        if (empty($product['reference'])) {
            $errors[] = 'The reference field is required.';
        }

        // Name
        if (empty($product['name'])) {
            $errors[] = 'The name field is required.';
        }

        // Price
        if (
            isset($product['price']) &&
            (
                !is_numeric($product['price']) ||
                (float) $product['price'] < 0
            )
        ) {
            $errors[] = 'The price field must be a number greater than or equal to 0.';
        }

        // Quantity
        if (
            isset($product['quantity']) &&
            filter_var(
                $product['quantity'],
                FILTER_VALIDATE_INT
            ) === false
        ) {
            $errors[] = 'The quantity field must be an integer.';
        }

        // EAN13
        if (!empty($product['ean13'])) {
            if (
                !preg_match('/^\d{13}$/', (string) $product['ean13'])
            ) {
                $errors[] = 'The ean13 field must contain exactly 13 digits.';
            }
        }

        // Image
        if (!empty($product['image'])) {
            if (!filter_var($product['image'], FILTER_VALIDATE_URL)) {
                $errors[] = 'The image field must contain a valid URL.';
            }
        }

        return $errors;
    }
}