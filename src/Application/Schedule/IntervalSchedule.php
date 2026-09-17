<?php

namespace CPBConnect\Application\Schedule;

use DateTimeImmutable;

/**
 * Ejecución cada cierto número de minutos.
 *
 * Cubre desde "cada 15 minutos" hasta "una vez al día": es la misma
 * cuenta con otro intervalo. Un intervalo de cero minutos significa que
 * la fuente sólo se ejecuta a mano.
 */
class IntervalSchedule extends AbstractSchedule
{
    /**
     * @param array{label: string, fields: array<int, array<string, mixed>>} $description
     */
    public function __construct(
        private string $name,
        private int $minutes,
        private array $description
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function describe(): array
    {
        return $this->description;
    }

    public function isDue(
        array $config,
        ?string $lastRun,
        ?DateTimeImmutable $now = null
    ): bool {
        if ($this->minutes <= 0) {
            return false;
        }

        $last = $this->timestamp($lastRun);

        if ($last === null) {
            return true;
        }

        return $last + ($this->minutes * 60)
               <= $this->now($now)->getTimestamp();
    }

    public function nextRun(
        array $config,
        ?string $lastRun,
        ?DateTimeImmutable $now = null
    ): ?DateTimeImmutable {
        if ($this->minutes <= 0) {
            return null;
        }

        $now = $this->now($now);

        $last = $this->timestamp($lastRun);

        if ($last === null) {
            return $now;
        }

        $due = $last + ($this->minutes * 60);

        if ($due <= $now->getTimestamp()) {
            return $now;
        }

        return (new DateTimeImmutable('@' . $due))
            ->setTimezone($this->timezone());
    }
}
