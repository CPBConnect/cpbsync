<?php

namespace CPBConnect\Application\Mapping;

use CPBConnect\Application\Transform\TransformerFactory;
use CPBConnect\Application\Transform\TransformerRegistry;

/**
 * Aplica el mapeo a una fila del catálogo.
 *
 * La sincronización y el Dry Run recorren la misma configuración: si
 * cada uno tuviera su propio recorrido, el Dry Run podría prometer un
 * resultado que la sincronización no cumple (era el caso de la
 * normalización de texto, que sólo aplicaba el Dry Run).
 *
 * Las transformaciones no están escritas aquí: se buscan en el
 * registro, que la edición de pago amplía con las suyas.
 */
class MappingApplier
{
    private TransformerRegistry $transformers;

    public function __construct(?TransformerRegistry $transformers = null)
    {
        $this->transformers = $transformers
            ?? TransformerFactory::create();
    }

    /**
     * Campos destino listos para guardar.
     *
     * @param array<string, mixed> $row
     * @param array<string, mixed> $mapping
     *
     * @return array<string, mixed>
     */
    public function apply(array $row, array $mapping): array
    {
        return $this->map($row, $mapping, false);
    }

    /**
     * Campos destino con el valor original, para el Dry Run.
     *
     * @param array<string, mixed> $row
     * @param array<string, mixed> $mapping
     *
     * @return array<string, mixed>
     */
    public function applyWithOriginals(
        array $row,
        array $mapping
    ): array {
        return $this->map($row, $mapping, true);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $mapping
     *
     * @return array<string, mixed>
     */
    private function map(
        array $row,
        array $mapping,
        bool $withOriginals
    ): array {
        $result = [];

        foreach ($mapping as $sourceField => $configuration) {

            if (!array_key_exists($sourceField, $row)) {
                continue;
            }

            $original = $row[$sourceField];

            if (is_string($configuration)) {

                if ($configuration === '') {
                    continue;
                }

                $result[$configuration] = $withOriginals
                    ? $this->entry($original, $original)
                    : $original;

                continue;
            }

            $targetField = $configuration['target'] ?? '';

            if ($targetField === '') {
                continue;
            }

            $value = $this->transformValue(
                $original,
                $configuration,
                $row
            );

            $result[$targetField] = $withOriginals
                ? $this->entry($original, $value)
                : $value;
        }

        return $result;
    }

    /**
     * Aplica a un valor la transformación configurada para su campo.
     *
     * @param mixed                $value
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $row
     *
     * @return mixed
     */
    private function transformValue(
        $value,
        array $configuration,
        array $row
    ) {
        $transform = $configuration['transform'] ?? 'none';

        $transformer = is_string($transform)
            ? $this->transformers->find($transform)
            : null;

        if ($transformer === null) {
            return $value;
        }

        $config = $configuration['config'] ?? [];

        if (!is_array($config)) {
            $config = [];
        }

        return $transformer->transform($value, $config, $row);
    }

    /**
     * @param mixed $original
     * @param mixed $value
     *
     * @return array{original: mixed, value: mixed, changed: bool}
     */
    private function entry($original, $value): array
    {
        return [
            'original' => $original,
            'value' => $value,
            'changed' => (string) $original !== (string) $value,
        ];
    }
}
