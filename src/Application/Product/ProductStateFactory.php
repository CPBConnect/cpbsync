<?php

namespace CPBConnect\Application\Product;

/**
 * Construye el estado de producto del paquete instalado.
 *
 * Si la edición de pago está presente se usa su implementación
 * incremental; si no, el comportamiento por defecto.
 */
class ProductStateFactory
{
    private const INCREMENTAL_STATE =
        'CPBConnect\\Premium\\Application\\Product\\IncrementalProductState';

    public static function create(): ProductStateInterface
    {
        if (class_exists(self::INCREMENTAL_STATE)) {
            $class = self::INCREMENTAL_STATE;

            return new $class();
        }

        return new CatalogProductState();
    }
}
