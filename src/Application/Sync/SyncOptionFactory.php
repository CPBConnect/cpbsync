<?php

namespace CPBConnect\Application\Sync;

/**
 * Construye el registro de opciones de sincronización del paquete
 * instalado.
 *
 * Las cuatro opciones básicas van en el núcleo; si una edición
 * adicional quiere ofrecer más, las añade al mismo registro.
 */
class SyncOptionFactory
{
    private const ADDITIONAL_REGISTRY =
        'CPBConnect\\Premium\\Application\\Sync\\PremiumSyncOptionRegistry';

    public static function create(): SyncOptionRegistry
    {
        $registry = new SyncOptionRegistry();

        $registry->register(
            SyncOptions::CREATE_ONLY,
            [
                'label' => 'Only create new products',
                'hint' => 'Products that already exist are left untouched, even if the catalog has changed.',
            ]
        );

        $registry->register(
            SyncOptions::FILL_EMPTY,
            [
                'label' => 'Only fill empty fields',
                'hint' => 'Values already present in the product are kept: useful when the descriptions or the prices are edited by hand.',
            ]
        );

        $registry->register(
            SyncOptions::SKIP_STOCK,
            [
                'label' => 'Do not synchronise stock',
                'hint' => 'The quantity in the shop is left as it is.',
            ]
        );

        $registry->register(
            SyncOptions::SKIP_IMAGES,
            [
                'label' => 'Do not import images',
                'hint' => 'The images the product already has are kept, and no new ones are downloaded.',
            ]
        );

        if (class_exists(self::ADDITIONAL_REGISTRY)) {
            $class = self::ADDITIONAL_REGISTRY;

            $class::register($registry);
        }

        return $registry;
    }
}
