<?php

namespace CPBConnect\Application\Schedule;

/**
 * Programaciones disponibles en la edición instalada.
 */
class ScheduleRegistry
{
    /**
     * Programación que sólo se ejecuta a mano.
     */
    public const MANUAL = 'manual';

    /** @var array<string, ScheduleInterface> */
    private array $schedules = [];

    public function register(ScheduleInterface $schedule): void
    {
        $this->schedules[$schedule->getName()] = $schedule;
    }

    public function has(string $name): bool
    {
        return isset($this->schedules[$name]);
    }

    public function find(string $name): ?ScheduleInterface
    {
        return $this->schedules[$name] ?? null;
    }

    /**
     * @return array<string, ScheduleInterface>
     */
    public function all(): array
    {
        return $this->schedules;
    }

    /**
     * Programación de una fuente, con "manual" como respuesta segura
     * cuando el paquete instalado ya no ofrece la que tenía guardada.
     *
     * @param array<string, mixed> $source
     */
    public function forSource(array $source): ScheduleInterface
    {
        $name = (string) ($source['frequency'] ?? '');

        $schedule = $this->find($name);

        if ($schedule !== null) {
            return $schedule;
        }

        $manual = $this->find(self::MANUAL);

        if ($manual !== null) {
            return $manual;
        }

        foreach ($this->schedules as $registered) {
            return $registered;
        }

        throw new \RuntimeException(
            'The schedule registry is empty.'
        );
    }

    /**
     * @return array<int, array{name: string, label: string, fields: array<int, array<string, mixed>>}>
     */
    public function describeAll(): array
    {
        $described = [];

        foreach ($this->schedules as $schedule) {
            $description = $schedule->describe();

            $described[] = [
                'name' => $schedule->getName(),
                'label' => (string) $description['label'],
                'fields' => $description['fields'],
            ];
        }

        return $described;
    }
}
