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
                'No se puede crear un fabricante sin nombre.'
            );
        }

        $manufacturer = new Manufacturer();

        $manufacturer->name = $name;
        $manufacturer->active = 1;

        if (!$manufacturer->add()) {
            throw new \RuntimeException(
                'No fue posible crear el fabricante en PrestaShop.'
            );
        }

        return (int) $manufacturer->id;
    }
}