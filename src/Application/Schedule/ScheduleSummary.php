<?php

namespace CPBConnect\Application\Schedule;

use CPBConnect\Application\Form\DescribedFields;
use CPBConnect\Infrastructure\Persistence\SyncLogRepository;

/**
 * Resumen de la programación de las fuentes.
 *
 * La lista de fuentes muestra con qué frecuencia se ejecuta cada una y
 * cuándo le toca la siguiente, que es lo que de verdad se quiere saber
 * al mirarla.
 */
class ScheduleSummary
{
    private ScheduleRegistry $schedules;
    private SyncLogRepository $logs;

    public function __construct(
        ?ScheduleRegistry $schedules = null,
        ?SyncLogRepository $logs = null
    ) {
        $this->schedules = $schedules ?? ScheduleFactory::create();
        $this->logs = $logs ?? new SyncLogRepository();
    }

    /**
     * @param array<int, array<string, mixed>> $sources
     *
     * @return array<int, array{frequency: string, next_run: string|null}>
     */
    public function describe(array $sources): array
    {
        $ids = [];

        foreach ($sources as $source) {
            $id = (int) ($source['id_source'] ?? 0);

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        $lastRuns = $this->logs->findLatestCronDates($ids);

        $summary = [];

        foreach ($sources as $source) {
            $id = (int) ($source['id_source'] ?? 0);

            $schedule = $this->schedules->forSource($source);

            $config = DescribedFields::decode(
                $source['schedule'] ?? null
            );

            $next = $schedule->nextRun(
                $config,
                $lastRuns[$id] ?? null
            );

            $summary[$id] = [
                'frequency' => (string) $schedule->describe()['label'],
                'next_run' => $next === null
                    ? null
                    : $next->format('Y-m-d H:i'),
            ];
        }

        return $summary;
    }
}
