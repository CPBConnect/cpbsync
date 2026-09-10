<?php

namespace CPBConnect\Application\Product;

use Manufacturer;

class ManufacturerFinder
{
    public function findByName(string $name): ?int
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        $idManufacturer = (int) Manufacturer::getIdByName($name);

        if ($idManufacturer <= 0) {
            return null;
        }

        return $idManufacturer;
    }
}