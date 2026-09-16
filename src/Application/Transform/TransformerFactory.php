<?php

namespace CPBConnect\Application\Transform;

/**
 * Construye el registro de transformaciones del paquete instalado.
 *
 * Las básicas van en el núcleo; si la edición de pago está presente,
 * añade las suyas al mismo registro.
 */
class TransformerFactory
{
    private const PREMIUM_REGISTRY =
        'CPBConnect\\Premium\\Application\\Transform\\PremiumTransformerRegistry';

    public static function create(): TransformerRegistry
    {
        $registry = new TransformerRegistry();

        $registry->register(new PriceTransformer());
        $registry->register(new StockTransformer());
        $registry->register(new TextTransformer());
        $registry->register(new ReplaceTextTransformer());

        if (class_exists(self::PREMIUM_REGISTRY)) {
            $class = self::PREMIUM_REGISTRY;

            $class::register($registry);
        }

        return $registry;
    }
}
