<?php

namespace CPBConnect\Application\Transform;

use CPBConnect\Application\Validation\ValidationError;

/**
 * Reemplaza un texto por otro.
 */
class ReplaceTextTransformer extends AbstractTransformer
{
    public function getName(): string
    {
        return 'replace_text';
    }

    public function describe(): array
    {
        return [
            'label' => 'Replace text',
            'targets' => [
                'name',
                'description',
                'manufacturer',
                'category',
                'reference',
                'ean13',
            ],
            'fields' => [
                [
                    'name' => 'search',
                    'label' => 'Search',
                    'hint' => 'Text to search for in the source value.',
                    'type' => 'text',
                    'default' => '',
                ],
                [
                    'name' => 'replace',
                    'label' => 'Replace with',
                    'hint' => 'Leave empty to remove the text.',
                    'type' => 'text',
                    'default' => '',
                ],
            ],
        ];
    }

    public function validate(
        array $config,
        string $sourceField
    ): ?ValidationError {
        if ($this->configToken($config, 'search') === '') {
            return new ValidationError(
                'You must provide the text to replace for the "%field%" field.',
                ['%field%' => $sourceField]
            );
        }

        return null;
    }

    /**
     * @param mixed                $value
     * @param array<string, mixed> $config
     * @param array<string, mixed> $row
     */
    public function transform($value, array $config, array $row): string
    {
        $value = (string) $value;

        $search = $this->configString($config, 'search');

        if ($search === '') {
            return $value;
        }

        return str_replace(
            $search,
            $this->configString($config, 'replace'),
            $value
        );
    }
}
