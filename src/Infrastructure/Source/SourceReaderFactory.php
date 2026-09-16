<?php

namespace CPBConnect\Infrastructure\Source;

use CPBConnect\Application\Source\CsvSourceService;
use CPBConnect\Application\Source\Reader\CsvReader;
use CPBConnect\Application\Source\Reader\SourceReaderRegistry;

/**
 * Construye el registro de lectores de la instalación.
 *
 * Es el único punto donde el núcleo conoce los lectores de pago, y lo
 * hace por nombre de clase: si el paquete instalado no los incluye
 * (edición gratuita), simplemente no se registran.
 */
class SourceReaderFactory
{
    private const PREMIUM_FEATURES =
        'CPBConnect\\Premium\\PremiumFeatures';

    public static function create(): SourceReaderRegistry
    {
        $registry = new SourceReaderRegistry();

        $registry->register(
            new CsvReader(
                new CsvSourceService(),
                new CsvSourceReader()
            )
        );

        if (class_exists(self::PREMIUM_FEATURES)) {
            call_user_func(
                [self::PREMIUM_FEATURES, 'registerSourceReaders'],
                $registry
            );
        }

        return $registry;
    }
}
