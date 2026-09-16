<?php

namespace CPBConnect\Application\Sync;

/**
 * Cronómetro de una ejecución de sincronización.
 *
 * Mide la duración total, la de cada fase y el pico de memoria, que es
 * lo mínimo para poder monitorizar catálogos grandes.
 */
class SyncMetrics
{
    private float $startedAt;
    private float $phaseStartedAt;
    private ?string $currentPhase = null;

    /** @var array<string, float> */
    private array $phases = [];

    public function __construct()
    {
        $this->startedAt = microtime(true);
        $this->phaseStartedAt = $this->startedAt;
    }

    /**
     * Abre una fase; la anterior se cierra sola.
     */
    public function startPhase(string $name): void
    {
        $this->closePhase();

        $this->currentPhase = $name;
        $this->phaseStartedAt = microtime(true);
    }

    public function stopPhase(): void
    {
        $this->closePhase();
    }

    public function duration(): float
    {
        return round(microtime(true) - $this->startedAt, 3);
    }

    /**
     * Cierra la fase abierta y devuelve las métricas.
     *
     * Las fases van en milisegundos enteros (como duration_ms) para
     * que el valor guardado sea legible y ocupe lo mínimo.
     *
     * @return array{duration: float, memory: int, phases: array<string, int>}
     */
    public function finish(): array
    {
        $this->closePhase();

        $phases = [];

        foreach ($this->phases as $name => $seconds) {
            $phases[$name] = (int) round($seconds * 1000);
        }

        return [
            'duration' => $this->duration(),
            'memory' => (int) round(
                memory_get_peak_usage(true) / 1024
            ),
            'phases' => $phases,
        ];
    }

    private function closePhase(): void
    {
        if ($this->currentPhase === null) {
            return;
        }

        $elapsed = microtime(true) - $this->phaseStartedAt;

        $this->phases[$this->currentPhase] = round(
            ($this->phases[$this->currentPhase] ?? 0) + $elapsed,
            3
        );

        $this->currentPhase = null;
    }
}
