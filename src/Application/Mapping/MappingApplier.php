<?php

namespace CPBConnect\Application\Mapping;

use CPBConnect\Application\Transform\PriceTransformer;
use CPBConnect\Application\Transform\StockTransformer;
use CPBConnect\Application\Transform\TextTransformer;
use CPBConnect\Application\Transform\ReplaceTextTransformer;

/**
 * Aplica el mapeo a una fila del catálogo.
 *
 * La sincronización y el Dry Run recorren la misma configuración: si
 * cada uno tuviera su propio recorrido, el Dry Run podría prometer un
 * resultado que la sincronización no cumple (era el caso de la
 * normalización de texto, que sólo aplicaba el Dry Run).
 */
class MappingApplier
{
    private PriceTransformer $priceTransformer;
    private StockTransformer $stockTransformer;
    private TextTransformer $textTransformer;
    private ReplaceTextTransformer $replaceTextTransformer;

    public function __construct()
    {
        $this->priceTransformer = new PriceTransformer();
        $this->stockTransformer = new StockTransformer();
        $this->textTransformer = new TextTransformer();
        $this->replaceTextTransformer = new ReplaceTextTransformer();
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

            $value = $this->transformValue($original, $configuration);

            $result[$targetField] = $withOriginals
                ? $this->entry($original, $value)
                : $value;
        }

        return $result;
    }

    /**
     * Aplica a un valor la transformación configurada para su campo.
     *
     * @param mixed $value
     * @param array<string, mixed> $configuration
     *
     * @return mixed
     */
    private function transformValue($value, array $configuration)
    {
        $transform = $configuration['transform'] ?? 'none';

        $config = $configuration['config'] ?? [];

        if (!is_array($config)) {
            $config = [];
        }

        switch ($transform) {
            case 'normalize_price':
                return $this->priceTransformer->transform($value);

            case 'normalize_stock':
                return $this->stockTransformer->transform($value);

            case 'normalize_text':
                return $this->textTransformer->transform($value);

            case 'replace_text':
                return $this->replaceTextTransformer->transform(
                    $value,
                    (string) ($config['search'] ?? ''),
                    (string) ($config['replace'] ?? '')
                );
        }

        return $value;
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
