<?php

namespace CPBConnect\Application\Schedule;

/**
 * Construye el registro de programaciones del paquete instalado.
 *
 * Las frecuencias básicas van en el núcleo; si la edición de pago está
 * presente, añade las suyas al mismo registro.
 */
class ScheduleFactory
{
    private const PREMIUM_REGISTRY =
        'CPBConnect\\Premium\\Application\\Schedule\\PremiumScheduleRegistry';

    public static function create(): ScheduleRegistry
    {
        $registry = new ScheduleRegistry();

        $registry->register(new IntervalSchedule(
            ScheduleRegistry::MANUAL,
            0,
            [
                'label' => 'Manual',
                'fields' => [],
            ]
        ));

        $registry->register(new IntervalSchedule(
            'hourly',
            60,
            [
                'label' => 'Hourly',
                'fields' => [],
            ]
        ));

        $registry->register(new IntervalSchedule(
            '6_hours',
            360,
            [
                'label' => 'Every 6 hours',
                'fields' => [],
            ]
        ));

        $registry->register(new IntervalSchedule(
            'daily',
            1440,
            [
                'label' => 'Daily',
                'fields' => [],
            ]
        ));

        if (class_exists(self::PREMIUM_REGISTRY)) {
            $class = self::PREMIUM_REGISTRY;

            $class::register($registry);
        }

        return $registry;
    }
}
