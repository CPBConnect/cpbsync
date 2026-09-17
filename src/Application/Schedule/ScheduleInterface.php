<?php

namespace CPBConnect\Application\Schedule;

use CPBConnect\Application\Validation\ValidationError;
use DateTimeImmutable;

/**
 * Cuándo se ejecuta una fuente.
 *
 * Una programación sabe cómo se llama, qué campos pide al usuario (hora,
 * día de la semana...) y si le toca ejecutar. El núcleo incluye las
 * frecuencias básicas y la edición de pago añade las demás, pero el cron
 * no distingue unas de otras: busca la programación por su nombre.
 */
interface ScheduleInterface
{
    /**
     * Identificador con el que se guarda en la fuente.
     */
    public function getName(): string;

    /**
     * Descripción para el formulario de la fuente.
     *
     * Las etiquetas se escriben en inglés y las traduce la capa de
     * presentación.
     *
     * @return array{
     *     label: string,
     *     fields: array<int, array<string, mixed>>
     * }
     */
    public function describe(): array;

    /**
     * ¿Toca ejecutar esta fuente?
     *
     * @param array<string, mixed> $config  configuración del calendario
     * @param string|null          $lastRun última ejecución (`Y-m-d H:i:s`)
     */
    public function isDue(
        array $config,
        ?string $lastRun,
        ?DateTimeImmutable $now = null
    ): bool;

    /**
     * Próxima ejecución prevista, o null si no se puede calcular.
     *
     * @param array<string, mixed> $config
     */
    public function nextRun(
        array $config,
        ?string $lastRun,
        ?DateTimeImmutable $now = null
    ): ?DateTimeImmutable;

    /**
     * Comprueba la configuración recibida del formulario.
     *
     * @param array<string, mixed> $config
     */
    public function validate(array $config): ?ValidationError;
}
