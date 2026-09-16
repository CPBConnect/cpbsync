<?php

namespace CPBConnect\Application\Transform;

class StockTransformer
{
    public function transform($value): int
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new \RuntimeException(
                'The stock cannot be empty.'
            );
        }

        if (!preg_match('/^-?\d+(?:[.,]\d+)?/', $value, $matches)) {
            throw new \RuntimeException(
                'The stock format is not valid.'
            );
        }

        return (int) floor(
            (float) str_replace(',', '.', $matches[0])
        );
    }
}