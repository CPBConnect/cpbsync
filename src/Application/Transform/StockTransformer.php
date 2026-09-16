<?php

namespace CPBConnect\Application\Transform;

use RuntimeException;

/**
 * Normaliza el stock.
 *
 * Se queda con el primer número del valor, así que acepta tanto `3`
 * como `3 unidades` o `más de 3`.
 */
class StockTransformer extends AbstractTransformer
{
    public function getName(): string
    {
        return 'normalize_stock';
    }

    public function describe(): array
    {
        return [
            'label' => 'Normalize stock',
            'targets' => ['quantity'],
            'fields' => [],
        ];
    }

    /**
     * @param mixed                $value
     * @param array<string, mixed> $config
     * @param array<string, mixed> $row
     */
    public function transform($value, array $config, array $row): int
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new RuntimeException(
                'The stock cannot be empty.'
            );
        }

        if (!preg_match('/^-?\d+(?:[.,]\d+)?/', $value, $matches)) {
            throw new RuntimeException(
                'The stock format is not valid.'
            );
        }

        return (int) floor(
            (float) str_replace(',', '.', $matches[0])
        );
    }
}
