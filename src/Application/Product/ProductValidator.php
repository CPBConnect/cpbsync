<?php

namespace CPBConnect\Application\Product;

class ProductValidator
{
    public function validate(array $product): array
    {
        $errors = [];

        // Reference
        if (empty($product['reference'])) {
            $errors[] = 'El campo reference es obligatorio.';
        }

        // Name
        if (empty($product['name'])) {
            $errors[] = 'El campo name es obligatorio.';
        }

        // Price
        if (
            isset($product['price']) &&
            (
                !is_numeric($product['price']) ||
                (float) $product['price'] < 0
            )
        ) {
            $errors[] = 'El campo price debe ser un número mayor o igual a 0.';
        }

        // Quantity
        if (
            isset($product['quantity']) &&
            filter_var(
                $product['quantity'],
                FILTER_VALIDATE_INT
            ) === false
        ) {
            $errors[] = 'El campo quantity debe ser un número entero.';
        }

        // EAN13
        if (!empty($product['ean13'])) {
            if (
                !preg_match('/^\d{13}$/', (string) $product['ean13'])
            ) {
                $errors[] = 'El campo ean13 debe contener exactamente 13 dígitos.';
            }
        }

        // Image
        if (!empty($product['image'])) {
            if (!filter_var($product['image'], FILTER_VALIDATE_URL)) {
                $errors[] = 'El campo image debe contener una URL válida.';
            }
        }

        return $errors;
    }
}