<?php

namespace CPBConnect\Application\Product;

use CPBConnect\Application\Mapping\MappingApplier;

class ProductDryRun
{
    private MappingApplier $mappingApplier;
    private ProductValidator $validator;

    public function __construct()
    {
        $this->mappingApplier = new MappingApplier();
        $this->validator = new ProductValidator();
    }

    public function run(
        array $rows,
        array $mapping,
        int $limit = 5
    ): array {
        $products = [];

        foreach (array_slice($rows, 0, $limit) as $index => $row) {

            $mappedProduct =
                $this->mappingApplier->applyWithOriginals(
                    $row,
                    $mapping
                );

            $productData = [];

            foreach ($mappedProduct as $targetField => $fieldData) {
                $productData[$targetField] =
                    $fieldData['value'];
            }

            $validationErrors =
                $this->validator->validate(
                    $productData
                );

            $products[] = [
                'number' => $index + 1,
                'data' => $mappedProduct,
                'errors' => $validationErrors,
                'valid' => empty($validationErrors),
            ];
        }

        return $products;
    }
}