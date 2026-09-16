<?php

namespace CPBConnect\Application\Mapping;

use CPBConnect\Application\Transform\TransformerInterface;
use RuntimeException;

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
        if (!isset($values[$key]) || !is_scalar($values[$key])) {
            return '';
        }

        return (string) $values[$key];
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
        $config = [];

        foreach ($transformer->describe()['fields'] as $field) {
            $name = (string) ($field['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $value = self::readValue($posted, $name);

            // Los valores se guardan tal cual (un espacio puede ser
            // significativo), pero el valor por defecto sólo entra si
            // el campo viene vacío.
            if (trim($value) === '' && isset($field['default'])) {
                $value = (string) $field['default'];
            }

            $config[$name] = $value;
        }

        return $config;
    }

    /**
     * Configuración serializada para guardarla con el mapeo.
     *
     * @param array<string, string> $config
     */
    public static function encodeConfig(array $config): ?string
    {
        if ($config === []) {
            return null;
        }

        $encoded = json_encode($config, JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            throw new RuntimeException(
                'The transformation configuration could not be saved.'
            );
        }

        return $encoded;
    }
}
