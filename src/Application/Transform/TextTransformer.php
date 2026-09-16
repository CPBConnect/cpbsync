<?php

namespace CPBConnect\Application\Transform;

/**
 * Normaliza un texto: quita espacios sobrantes al principio, al final
 * y entre palabras.
 */
class TextTransformer extends AbstractTransformer
{
    public function getName(): string
    {
        return 'normalize_text';
    }

    public function describe(): array
    {
        return [
            'label' => 'Normalize text',
            'targets' => [
                'name',
                'description',
                'manufacturer',
                'category',
            ],
            'fields' => [],
        ];
    }

    /**
     * @param mixed                $value
     * @param array<string, mixed> $config
     * @param array<string, mixed> $row
     */
    public function transform($value, array $config, array $row): string
    {
        $value = trim((string) $value);

        return (string) preg_replace('/\s+/u', ' ', $value);
    }
}
