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

            $this->shell->assign([
                'log' => $detail['log'],
                'source' => $detail['source'],
                'details' => $this->translateDetails(
                    $detail['details']
                ),
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
