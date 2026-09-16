<?php

namespace CPBConnect\Application\Product;

use Manufacturer;

class ManufacturerCreator
{
    public function create(string $name): int
    {
        $name = trim($name);

        if ($name === '') {
            throw new \RuntimeException(
                'A manufacturer cannot be created without a name.'
            );
        }

        $manufacturer = new Manufacturer();

        $manufacturer->name = $name;
        $manufacturer->active = 1;

        if (!$manufacturer->add()) {
            throw new \RuntimeException(
                'The manufacturer could not be created in PrestaShop.'
            );
        }

        return (int) $manufacturer->id;
    }
}