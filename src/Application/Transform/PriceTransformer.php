<?php

namespace CPBConnect\Application\Transform;

class PriceTransformer
{
    public function transform($value): float
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new \RuntimeException(
                'The price cannot be empty.'
            );
        }

        $value = str_replace(
            ['$', ' ', '.'],
            '',
            $value
        );

        $value = str_replace(
            ',',
            '.',
            $value
        );

        if (!is_numeric($value)) {
            throw new \RuntimeException(
                'The price format is not valid.'
            );
        }

        return (float) $value;
    }
}