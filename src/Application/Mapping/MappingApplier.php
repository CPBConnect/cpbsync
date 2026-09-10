<?php

namespace CPBConnect\Application\Mapping;

use CPBConnect\Application\Transform\PriceTransformer;
use CPBConnect\Application\Transform\StockTransformer;
use CPBConnect\Application\Transform\TextTransformer;
use CPBConnect\Application\Transform\ReplaceTextTransformer;

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

    public function apply(array $row, array $mapping): array
    {
        $result = [];

        foreach ($mapping as $sourceField => $configuration) {

            if (!array_key_exists($sourceField, $row)) {
                continue;
            }

            if (is_string($configuration)) {

                if ($configuration === '') {
                    continue;
                }

                $result[$configuration] = $row[$sourceField];

                continue;
            }

            $targetField =
                $configuration['target'] ?? '';

            if ($targetField === '') {
                continue;
            }

            $value = $row[$sourceField];

            $transform =
                $configuration['transform'] ?? 'none';

            if ($transform === 'normalize_price') {
                $value =
                    $this->priceTransformer->transform(
                        $value
                    );
            }

            if ($transform === 'normalize_stock') {
                $value =
                    $this->stockTransformer->transform(
                        $value
                    );
            }

            if ($transform === 'replace_text') {

                $config =
                    $configuration['config'] ?? [];

                $search =
                    (string) ($config['search'] ?? '');

                $replace =
                    (string) ($config['replace'] ?? '');

                $value =
                    $this->replaceTextTransformer->transform(
                        $value,
                        $search,
                        $replace
                    );
            }

            $result[$targetField] = $value;
        }

        return $result;
    }

    public function applyWithOriginals(
        array $row,
        array $mapping
    ): array {
        $result = [];

        foreach ($mapping as $sourceField => $configuration) {

            if (!array_key_exists($sourceField, $row)) {
                continue;
            }

            if (is_string($configuration)) {
                if ($configuration === '') {
                    continue;
                }

                $result[$configuration] = [
                    'original' => $row[$sourceField],
                    'value' => $row[$sourceField],
                    'changed' => false,
                ];

                continue;
            }

            $targetField =
                $configuration['target'] ?? '';

            if ($targetField === '') {
                continue;
            }

            $value = $row[$sourceField];

            $transform =
                $configuration['transform'] ?? 'none';

            if ($transform === 'normalize_price') {
                $value =
                    $this->priceTransformer->transform($value);
            }

            if ($transform === 'normalize_stock') {
                $value =
                    $this->stockTransformer->transform($value);
            }

            if ($transform === 'normalize_text') {
                $value =
                    $this->textTransformer->transform($value);
            }

            if ($transform === 'replace_text') {

                $config =
                    $configuration['config'] ?? [];

                $search =
                    (string) ($config['search'] ?? '');

                $replace =
                    (string) ($config['replace'] ?? '');

                $value =
                    $this->replaceTextTransformer->transform(
                        $value,
                        $search,
                        $replace
                    );
            }

            $result[$targetField] = [
                'original' => $row[$sourceField],
                'value' => $value,
                'changed' => (string) $row[$sourceField] !== (string) $value,
            ];
        }

        return $result;
    }
}