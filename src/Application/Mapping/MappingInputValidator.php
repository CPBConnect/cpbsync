<?php

namespace CPBConnect\Application\Mapping;

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

    public const TRANSFORMATIONS = [
        'none',
        'normalize_price',
        'normalize_stock',
        'normalize_text',
        'replace_text',
    ];

    public function validate(
        array $mapping,
        array $transformations,
        array $search
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

            $transform = MappingTransformInput::resolve(
                $transformations,
                $sourceField
            );

            if (!in_array(
                $transform,
                self::TRANSFORMATIONS,
                true
            )) {
                return new ValidationError(
                    'The transformation "%transform%" is not valid.',
                    ['%transform%' => $transform]
                );
            }

            if (
                $transform === 'replace_text'
                && trim(
                    MappingTransformInput::readValue(
                        $search,
                        $sourceField
                    )
                ) === ''
            ) {
                return new ValidationError(
                    'You must provide the text to replace for the "%field%" field.',
                    ['%field%' => (string) $sourceField]
                );
            }
        }

        return null;
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
