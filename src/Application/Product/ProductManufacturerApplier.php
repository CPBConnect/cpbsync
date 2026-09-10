<?php

namespace CPBConnect\Application\Product;

use Product;

class ProductManufacturerApplier
{
    private ManufacturerFinder $finder;
    private ManufacturerCreator $creator;

    public function __construct()
    {
        $this->finder = new ManufacturerFinder();
        $this->creator = new ManufacturerCreator();
    }

    public function apply(Product $product, array $data): void
    {
        if (!isset($data['manufacturer'])) {
            return;
        }

        $manufacturerName = trim(
            (string) $data['manufacturer']
        );

        if ($manufacturerName === '') {
            return;
        }

        $idManufacturer =
            $this->finder->findByName(
                $manufacturerName
            );

        if ($idManufacturer === null) {
            $idManufacturer =
                $this->creator->create(
                    $manufacturerName
                );
        }

        $product->id_manufacturer = $idManufacturer;
    }
}