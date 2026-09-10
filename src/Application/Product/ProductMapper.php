<?php

namespace CPBConnect\Application\Product;

use CPBConnect\Application\Mapping\MappingApplier;

class ProductMapper
{
    private MappingApplier $mappingApplier;

    public function __construct()
    {
        $this->mappingApplier = new MappingApplier();
    }

    public function map(array $rows, array $mapping): array
    {
        $products = [];

        foreach ($rows as $row) {
            $products[] = $this->mappingApplier->apply(
                $row,
                $mapping
            );
        }

        return $products;
    }
}