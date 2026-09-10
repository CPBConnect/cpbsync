<?php

namespace CPBConnect\Application\Transform;

class PriceTransformer
{
    public function transform($value): float
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new \RuntimeException(
                'El precio no puede estar vacío.'
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
                'El precio no tiene un formato válido.'
            );
        }

        return (float) $value;
    }
}