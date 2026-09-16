<?php

namespace CPBConnect\Application\Mapping;

use CPBConnect\Application\Transform\TransformerFactory;
use CPBConnect\Application\Transform\TransformerRegistry;
use CPBConnect\Application\Validation\ValidationError;

/**
 * Valida el formulario de mapping enviado desde el administrador.
 */
class MappingInputValidator
{
    public const TARGET_FIELDS = [
        'reference',
        'name',
        'description',
        'price',
        'quantity',
        'image',
        'category',
        'manufacturer',
        'ean13',
    ];

    private TransformerRegistry $transformers;

    public function __construct(?TransformerRegistry $transformers = null)
    {
        $this->transformers = $transformers
            ?? TransformerFactory::create();
    }

    /**
     * @param array<string, mixed> $mapping         campo de origen => destino
     * @param array<string, mixed> $transformations campo de origen => transformación
     * @param array<string, mixed> $config          campo de origen => configuración
     */
    public function validate(
        array $mapping,
        array $transformations,
        array $config
    ): ?ValidationError {
        if (!$this->hasReference($mapping)) {
            return new ValidationError(
                'You must map a supplier field to reference.'
            );
        }

        $usedTargetFields = [];

        foreach ($mapping as $sourceField => $targetField) {

            if ($targetField === '') {
                continue;
            }

            if (!in_array(
                $targetField,
                self::TARGET_FIELDS,
                true
            )) {
                return new ValidationError(
                    'The PrestaShop field "%field%" is not valid.',
                    ['%field%' => (string) $targetField]
                );
            }

            if (isset($usedTargetFields[$targetField])) {
                return new ValidationError(
                    'The PrestaShop field "%field%" is assigned more than once.',
                    ['%field%' => (string) $targetField]
                );
            }

            $usedTargetFields[$targetField] = true;

            $error = $this->validateTransformation(
                (string) $sourceField,
                $transformations,
                $config
            );

            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $transformations
     * @param array<string, mixed> $config
     */
    private function validateTransformation(
        string $sourceField,
        array $transformations,
        array $config
    ): ?ValidationError {
        $transform = MappingTransformInput::resolve(
            $transformations,
            $sourceField
        );

        if ($transform === 'none') {
            return null;
        }

        $transformer = $this->transformers->find($transform);

        if ($transformer === null) {
            return new ValidationError(
                'The transformation "%transform%" is not valid.',
                ['%transform%' => $transform]
            );
        }

        $posted = $config[$sourceField] ?? [];

        return $transformer->validate(
            is_array($posted) ? $posted : [],
            $sourceField
        );
    }

    private function hasReference(array $mapping): bool
    {
        foreach ($mapping as $targetField) {
            if ($targetField === 'reference') {
                return true;
            }
        }

        return false;
    }
}
