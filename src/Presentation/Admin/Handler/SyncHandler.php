<?php

namespace CPBConnect\Presentation\Admin\Handler;

use CPBConnect\Application\Sync\SourceSyncService;
use CPBConnect\Presentation\Admin\AdminLinkBuilder;
use CPBConnect\Presentation\Admin\AdminShellInterface;
use Tools;

/**
 * Acciones de administración de Dry Run y sincronización.
 */
class SyncHandler
{
    private const DRY_RUN_LIMIT = 5;

    public function __construct(
        private AdminShellInterface $shell,
        private AdminLinkBuilder $links,
        private SourceSyncService $sync,
        private MappingHandler $mappingHandler,
        private SourceHandler $sourcesHandler
    ) {
    }

    /**
     * Dry Run: previsualiza el resultado sin tocar el catálogo.
     */
    public function dryRun(): string
    {
        $sourceId = (int) Tools::getValue('id_source');

        if ($sourceId <= 0) {
            return $this->sourcesHandler->index();
        }

        try {
            $dryRun = $this->sync->dryRun(
                $sourceId,
                self::DRY_RUN_LIMIT
            );

            $this->shell->assign([
                'source' => $dryRun['source'],
                'products' => $this->translateProductErrors(
                    $dryRun['products']
                ),
                'back_url' => $this->links->mapSource($sourceId),
            ]);

            return $this->shell->fetch('dry-run.tpl');

        } catch (\Throwable $e) {
            $this->shell->addError(
                'The Dry Run could not be executed: %error%',
                [
                    '%error%' => $this->shell->translate(
                        $e->getMessage()
                    ),
                ]
            );
        }

        return $this->mappingHandler->show();
    }

    /**
     * Sincroniza la fuente y registra el resultado.
     */
    public function sync(): string
    {
        $sourceId = (int) Tools::getValue('id_source');

        if ($sourceId <= 0) {
            return $this->sourcesHandler->index();
        }

        try {
            $sync = $this->sync->sync($sourceId);

            $this->shell->assign([
                'source' => $sync['source'],
                'result' => $this->translateItemErrors(
                    $sync['result']
                ),
                'log_id' => $sync['log_id'],
                'back_url' => $this->links->mapSource($sourceId),
            ]);

            return $this->shell->fetch('sync-result.tpl');

        } catch (\Throwable $e) {
            $this->shell->addError(
                'The synchronization could not be executed: %error%',
                [
                    '%error%' => $this->shell->translate(
                        $e->getMessage()
                    ),
                ]
            );
        }

        return $this->mappingHandler->show();
    }

    /**
     * Traduce los errores de validación de cada producto.
     *
     * @param array<int, array<string, mixed>> $products
     *
     * @return array<int, array<string, mixed>>
     */
    private function translateProductErrors(array $products): array
    {
        foreach ($products as &$product) {
            if (empty($product['errors'])) {
                continue;
            }

            $product['errors'] = $this->shell->translateMessages(
                $product['errors']
            );
        }

        unset($product);

        return $products;
    }

    /**
     * Traduce los errores de cada elemento del resultado.
     *
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    private function translateItemErrors(array $result): array
    {
        if (empty($result['items'])) {
            return $result;
        }

        foreach ($result['items'] as &$item) {
            if (empty($item['errors'])) {
                continue;
            }

            $item['errors'] = $this->shell->translateMessages(
                $item['errors']
            );
        }

        unset($item);

        return $result;
    }
}
