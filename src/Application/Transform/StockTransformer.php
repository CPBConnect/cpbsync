<?php

namespace CPBConnect\Application\Transform;

class StockTransformer
{
    public function transform($value): int
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new \RuntimeException(
                'El stock no puede estar vacío.'
            );
        }

        if (!preg_match('/^-?\d+(?:[.,]\d+)?/', $value, $matches)) {
            throw new \RuntimeException(
                'El stock no tiene un formato válido.'
            );
        }

        return (int) floor(
            (float) str_replace(',', '.', $matches[0])
        );
    }
}