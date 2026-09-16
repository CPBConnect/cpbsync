<?php

namespace CPBConnect\Application\Mapping;

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
        return isset($values[$key]) ? (string) $values[$key] : '';
    }

    /**
     * Construye la configuración JSON de la transformación.
     */
    public static function buildConfig(
        string $transform,
        array $search,
        array $replace,
        $sourceField
    ): ?string {
        if ($transform !== 'replace_text') {
            return null;
        }

        $config = json_encode(
            [
                'search' => self::readValue($search, $sourceField),
                'replace' => self::readValue($replace, $sourceField),
            ],
            JSON_UNESCAPED_UNICODE
        );

        if ($config === false) {
            throw new RuntimeException(
                'The transformation configuration could not be saved.'
            );
        }

        return $config;
    }
}
