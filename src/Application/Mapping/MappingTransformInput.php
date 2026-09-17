<?php

namespace CPBConnect\Application\Mapping;

use CPBConnect\Application\Form\DescribedFields;
use CPBConnect\Application\Transform\TransformerInterface;

/**
 * Normaliza los valores de transformación tal y como llegan desde
 * el formulario de mapping.
 */
final class MappingTransformInput
{
    private function __construct()
    {
    }

    /**
     * Devuelve la transformación solicitada para un campo de origen.
     */
    public static function resolve(
        array $transformations,
        $sourceField
    ): string {
        $transform = isset($transformations[$sourceField])
            ? trim((string) $transformations[$sourceField])
            : 'none';

        return $transform === '' ? 'none' : $transform;
    }

    public static function readValue(array $values, $key): string
    {
        return DescribedFields::readValue($values, $key);
    }

    /**
     * Recoge la configuración enviada, quedándose sólo con los campos
     * que declara la transformación.
     *
     * @param array<string, mixed> $posted
     *
     * @return array<string, string>
     */
    public static function collectConfig(
        TransformerInterface $transformer,
        array $posted
    ): array {
        return DescribedFields::collect(
            $transformer->describe()['fields'],
            $posted
        );
    }

    /**
     * Configuración serializada para guardarla con el mapeo.
     *
     * @param array<string, string> $config
     */
    public static function encodeConfig(array $config): ?string
    {
        return DescribedFields::encode($config);
    }
}
