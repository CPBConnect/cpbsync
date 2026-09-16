<?php

namespace CPBConnect\Presentation\Admin;

use Context;

/**
 * Construye las URLs de administración del módulo.
 */
class AdminLinkBuilder
{
    private const CONTROLLER = 'AdminModules';

    public function __construct(
        private string $moduleName
    ) {
    }

    public function home(): string
    {
        return $this->withAction([]);
    }

    public function history(): string
    {
        return $this->withAction(
            ['cpbsync_action' => 'history']
        );
    }

    public function historyDetail(int $logId): string
    {
        return $this->withAction(
            [
                'cpbsync_action' => 'history_detail',
                'id_log' => $logId,
            ]
        );
    }

    public function sourceForm(): string
    {
        return $this->withAction(
            ['cpbsync_action' => 'add_source']
        );
    }

    public function saveSource(): string
    {
        return $this->withAction(
            ['cpbsync_action' => 'save_source']
        );
    }

    public function editSource(int $sourceId): string
    {
        return $this->withSourceAction(
            'edit_source',
            $sourceId
        );
    }

    public function updateSource(int $sourceId): string
    {
        return $this->withSourceAction(
            'update_source',
            $sourceId
        );
    }

    public function deleteSource(int $sourceId): string
    {
        return $this->withSourceAction(
            'delete_source',
            $sourceId
        );
    }

    public function testSource(int $sourceId): string
    {
        return $this->withSourceAction(
            'test_source',
            $sourceId
        );
    }

    public function mapSource(int $sourceId): string
    {
        return $this->withSourceAction(
            'map_source',
            $sourceId
        );
    }

    public function saveMapping(int $sourceId): string
    {
        return $this->withSourceAction(
            'save_mapping',
            $sourceId
        );
    }

    public function dryRun(int $sourceId): string
    {
        return $this->withSourceAction(
            'dry_run',
            $sourceId
        );
    }

    public function sync(int $sourceId): string
    {
        return $this->withSourceAction(
            'sync',
            $sourceId
        );
    }

    public function import(): string
    {
        return $this->legacy('import');
    }

    public function processImport(): string
    {
        return $this->legacy('process_import');
    }

    private function withSourceAction(
        string $action,
        int $sourceId
    ): string {
        return $this->withAction(
            [
                'cpbsync_action' => $action,
                'id_source' => $sourceId,
            ]
        );
    }

    private function withAction(array $parameters): string
    {
        return $this->link()->getAdminLink(
            self::CONTROLLER,
            true,
            [],
            array_merge(
                ['configure' => $this->moduleName],
                $parameters
            )
        );
    }

    /**
     * Enlaces que el módulo construye concatenando parámetros.
     */
    private function legacy(string $action): string
    {
        $url = $this->link()->getAdminLink(self::CONTROLLER);

        $url .= '&configure=' . $this->moduleName;
        $url .= '&cpbsync_action=' . $action;

        return $url;
    }

    private function link(): \Link
    {
        return Context::getContext()->link;
    }
}
