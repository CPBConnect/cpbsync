<?php

namespace CPBConnect\Presentation\Admin\Handler;

use CPBConnect\Application\Sync\SyncHistoryService;
use CPBConnect\Presentation\Admin\AdminLinkBuilder;
use CPBConnect\Presentation\Admin\AdminShellInterface;
use Tools;

/**
 * Acciones de administración sobre el historial de sincronizaciones.
 */
class HistoryHandler
{
    private const LOG_LIMIT = 50;

    public function __construct(
        private AdminShellInterface $shell,
        private AdminLinkBuilder $links,
        private SyncHistoryService $history,
        private SourceHandler $sourcesHandler
    ) {
    }

    /**
     * Listado de ejecuciones.
     */
    public function index(): string
    {
        try {
            $logs = $this->history->recent(self::LOG_LIMIT);

            foreach ($logs as &$log) {
                if ($log['source_name'] === null) {
                    $log['source_name'] = $this->shell->translate(
                        'Deleted source'
                    );
                }

                $log['duration'] = $this->toSeconds(
                    $log['duration_ms'] ?? null
                );

                $log['detail_url'] = $this->links->historyDetail(
                    (int) $log['id_log']
                );
            }

            unset($log);

            $this->shell->assign([
                'logs' => $logs,
                'back_url' => $this->links->home(),
            ]);

            return $this->shell->fetch('history.tpl');

        } catch (\Throwable $e) {
            $this->shell->addError(
                'The history could not be loaded: %error%',
                [
                    '%error%' => $this->shell->translate(
                        $e->getMessage()
                    ),
                ]
            );
        }

        return $this->sourcesHandler->index();
    }

    /**
     * Detalle de una ejecución.
     */
    public function detail(): string
    {
        $logId = (int) Tools::getValue('id_log');

        if ($logId <= 0) {
            return $this->index();
        }

        try {
            $detail = $this->history->detail($logId);

            $log = $detail['log'];

            $log['duration'] = $this->toSeconds(
                $log['duration_ms'] ?? null
            );

            $log['memory'] = $this->toMegabytes(
                $log['memory_kb'] ?? null
            );

            $this->shell->assign([
                'log' => $log,
                'source' => $detail['source'],
                'details' => $this->translateDetails(
                    $detail['details']
                ),
                'phases' => $this->decodePhases(
                    $log['phases'] ?? null
                ),
                'items_shown' => count($detail['details']),
                'items_total' => (int) ($log['items_total'] ?? 0),
                'back_url' => $this->links->history(),
            ]);

            return $this->shell->fetch('history-detail.tpl');

        } catch (\Throwable $e) {
            $this->shell->addError(
                'The detail could not be loaded: %error%',
                [
                    '%error%' => $this->shell->translate(
                        $e->getMessage()
                    ),
                ]
            );
        }

        return $this->index();
    }

    /**
     * Desglose de tiempos por fase, con nombres traducidos.
     *
     * Las fases se guardan en milisegundos y se muestran en segundos;
     * con dos decimales una fase de 3 ms aparecía como 0 s, así que
     * se usan tres.
     *
     * @param mixed $json
     *
     * @return array<int, array{name: string, seconds: float}>
     */
    private function decodePhases($json): array
    {
        if (!is_string($json) || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return [];
        }

        $phases = [];

        foreach ($decoded as $name => $milliseconds) {
            if (!is_numeric($milliseconds)) {
                continue;
            }

            $phases[] = [
                'name' => $this->phaseLabel((string) $name),
                'seconds' => round(((int) $milliseconds) / 1000, 3),
            ];
        }

        return $phases;
    }

    /**
     * Nombre traducido de una fase.
     *
     * La traducción se pide con el texto literal a la vista: dentro de
     * un array la comprobación de traducciones no la encontraría.
     */
    private function phaseLabel(string $name): string
    {
        switch ($name) {
            case 'read':
                return $this->shell->translate('Reading the source');

            case 'map':
                return $this->shell->translate('Applying the mapping');

            case 'sync':
                return $this->shell->translate('Writing products');
        }

        return $name;
    }

    /**
     * @param mixed $milliseconds
     */
    private function toSeconds($milliseconds): ?float
    {
        if ($milliseconds === null || !is_numeric($milliseconds)) {
            return null;
        }

        return round(((int) $milliseconds) / 1000, 2);
    }

    /**
     * @param mixed $kilobytes
     */
    private function toMegabytes($kilobytes): ?float
    {
        if ($kilobytes === null || !is_numeric($kilobytes)) {
            return null;
        }

        return round(((int) $kilobytes) / 1024, 1);
    }

    /**
     * Traduce los errores de cada producto del detalle.
     *
     * @param array<int, array<string, mixed>> $details
     *
     * @return array<int, array<string, mixed>>
     */
    private function translateDetails(array $details): array
    {
        foreach ($details as &$item) {
            if (empty($item['errors'])) {
                continue;
            }

            $item['errors'] = $this->shell->translateMessages(
                $item['errors']
            );
        }

        unset($item);

        return $details;
    }
}
