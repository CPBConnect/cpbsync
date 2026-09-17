<?php

namespace CPBConnect\Application\Form;

use RuntimeException;

/**
 * Campos que una funcionalidad declara en su describe().
 *
 * Las transformaciones del mapeo y las programaciones de las fuentes
 * declaran sus campos con la misma forma (nombre, etiqueta, ayuda, tipo,
 * valor por defecto y opciones), así que el formulario, el guardado y la
 * traducción se resuelven una sola vez aquí.
 */
final class DescribedFields
{
    private function __construct()
    {
    }

    /**
     * Recoge los valores enviados, quedándose sólo con los campos
     * declarados.
     *
     * @param array<int, array<string, mixed>> $fields
     * @param array<string, mixed>             $posted
     *
     * @return array<string, string>
     */
    public static function collect(array $fields, array $posted): array
    {
        $values = [];

        foreach ($fields as $field) {
            $name = (string) ($field['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $value = self::readValue($posted, $name);

            /*
             * Los valores se guardan tal cual (un espacio puede ser
             * significativo), pero el valor por defecto sólo entra si el
             * campo viene vacío.
             */
            if (trim($value) === '' && isset($field['default'])) {
                $value = (string) $field['default'];
            }

            $values[$name] = $value;
        }

        return $values;
    }

    /**
     * Configuración serializada, o null si no hay nada que guardar.
     *
     * @param array<string, mixed> $values
     */
    public static function encode(array $values): ?string
    {
        if ($values === []) {
            return null;
        }

        $encoded = json_encode($values, JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            throw new RuntimeException(
                'The configuration could not be saved.'
            );
        }

        return $encoded;
    }

    /**
     * @param mixed $json
     *
     * @return array<string, mixed>
     */
    public static function decode($json): array
    {
        if (!is_string($json) || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Campos listos para la plantilla: etiquetas y ayudas traducidas.
     *
     * @param array<int, array<string, mixed>> $fields
     * @param callable(string): string         $translate
     *
     * @return array<int, array<string, mixed>>
     */
    public static function translate(
        array $fields,
        callable $translate
    ): array {
        $prepared = [];

        foreach ($fields as $field) {
            $prepared[] = [
                'name' => (string) ($field['name'] ?? ''),
                'label' => $translate(
                    (string) ($field['label'] ?? '')
                ),
                'hint' => $translate(
                    (string) ($field['hint'] ?? '')
                ),
                'type' => (string) ($field['type'] ?? 'text'),
                'default' => (string) ($field['default'] ?? ''),
                'options' => self::translateOptions(
                    $field['options'] ?? [],
                    $translate
                ),
            ];
        }

        return $prepared;
    }

    /**
     * @param mixed                    $values
     * @param callable(string): string $translate
     *
     * @return array<int, array{value: string, label: string}>
     */
    private static function translateOptions(
        $values,
        callable $translate
    ): array {
        if (!is_array($values)) {
            return [];
        }

        $options = [];

        foreach ($values as $value) {
            if (!is_array($value)) {
                continue;
            }

            $options[] = [
                'value' => (string) ($value['value'] ?? ''),
                'label' => $translate(
                    (string) ($value['label'] ?? '')
                ),
            ];
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $values
     * @param mixed                $key
     */
    public static function readValue(array $values, $key): string
    {
        if (!isset($values[$key]) || !is_scalar($values[$key])) {
            return '';
        }

        return (string) $values[$key];
    }
}
