<?php

namespace CPBConnect\Application\Product;

/**
 * Construye el proveedor de imágenes del paquete instalado.
 */
class ProductImageProviderFactory
{
    private const PARALLEL_PROVIDER =
        'CPBConnect\\Premium\\Application\\Product\\ParallelImageProvider';

    public static function create(): ProductImageProviderInterface
    {
        if (class_exists(self::PARALLEL_PROVIDER)) {
            $class = self::PARALLEL_PROVIDER;

            return new $class();
        }

        return new SynchronousImageProvider();
    }
}
