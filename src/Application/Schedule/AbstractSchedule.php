<?php

namespace CPBConnect\Application\Schedule;

use CPBConnect\Application\Validation\ValidationError;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Base de las programaciones.
 */
abstract class AbstractSchedule implements ScheduleInterface
{
    public function validate(array $config): ?ValidationError
    {
        return null;
    }

    /**
     * Momento actual con el que comparar.
     *
     * La hora del calendario se interpreta en la zona horaria de la
     * tienda, no en la del servidor: quien configura "a las 3:00" piensa
     * en la hora de su tienda.
     */
    protected function now(?DateTimeImmutable $now): DateTimeImmutable
    {
        if ($now !== null) {
            return $now->setTimezone($this->timezone());
        }

        return new DateTimeImmutable('now', $this->timezone());
    }

    protected function timezone(): DateTimeZone
    {
        $name = class_exists('Configuration')
            ? (string) \Configuration::get('PS_TIMEZONE')
            : '';

        if ($name !== '') {
            try {
                return new DateTimeZone($name);

            } catch (\Throwable $e) {
                // Zona horaria desconocida: se usa la del servidor.
            }
        }

        return new DateTimeZone(date_default_timezone_get());
    }

    /**
     * Marca de tiempo de una fecha guardada, o null si no hay.
     */
    protected function timestamp(?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $parsed = strtotime($value);

        return $parsed === false ? null : $parsed;
    }

    /**
     * Hora del día configurada, como [horas, minutos].
     *
     * @param array<string, mixed> $config
     *
     * @return array{0: int, 1: int}
     */
    protected function timeOfDay(array $config, string $default = '03:00'): array
    {
        $value = $this->configString($config, 'time', $default);

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $matches) !== 1) {
            $value = $default;

            preg_match('/^(\d{1,2}):(\d{2})$/', $value, $matches);
        }

        return [
            min(23, (int) $matches[1]),
            min(59, (int) $matches[2]),
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function configString(
        array $config,
        string $key,
        string $default = ''
    ): string {
        if (!isset($config[$key]) || !is_scalar($config[$key])) {
            return $default;
        }

        return trim((string) $config[$key]);
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function configInt(
        array $config,
        string $key,
        int $default = 0
    ): int {
        $value = $this->configString($config, $key);

        return is_numeric($value) ? (int) $value : $default;
    }
}
