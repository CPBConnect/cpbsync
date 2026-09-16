<?php

namespace CPBConnect\Presentation\Admin\Handler;

use CPBConnect\Application\Import\ImportService;
use CPBConnect\Application\Source\SourceService;
use CPBConnect\Presentation\Admin\AdminLinkBuilder;
use CPBConnect\Presentation\Admin\AdminShellInterface;
use Tools;

/**
 * Importación manual de CSV por lotes.
 *
 * upload() y process() devuelven la carga JSON que consume
 * views/js/import.js.
 */
class ImportHandler
{
    public function __construct(
        private AdminShellInterface $shell,
        private AdminLinkBuilder $links,
        private SourceService $sources,
        private ImportService $imports
    ) {
    }

    /**
     * Formulario de importación.
     */
    public function index(?int $importId = null): string
    {
        $this->shell->assign([
            'sources' => $this->sources->all(),
            'import_id' => $importId,
            'process_url' => $this->links->processImport(),
            'back_url' => $this->links->home(),
            'import_messages' => $this->scriptMessages(),
        ]);

        return $this->shell->display('import.tpl');
    }

    /**
     * Mensajes que consume views/js/import.js.
     *
     * @return array<string, string>
     */
    private function scriptMessages(): array
    {
        return [
            'uploading' => $this->shell->translate(
                'Uploading file...'
            ),
            'uploaded' => $this->shell->translate(
                'File uploaded. Starting import...'
            ),
            'completed' => $this->shell->translate(
                'Import completed successfully.'
            ),
            'failed' => $this->shell->translate(
                'The import finished with errors.'
            ),
            'failedTitle' => $this->shell->translate(
                'Import error:'
            ),
        ];
    }

    /**
     * Guarda el CSV subido y crea la importación.
     */
    public function upload(): array
    {
        $sourceId = (int) Tools::getValue('id_source');

        if ($sourceId <= 0) {
            return [
                'success' => false,
                'message' => $this->shell->translate(
                    'You must select a source.'
                ),
            ];
        }

        if (
            !isset($_FILES['import_file'])
            || !is_array($_FILES['import_file'])
        ) {
            return [
                'success' => false,
                'message' => $this->shell->translate(
                    'You must select a CSV file.'
                ),
            ];
        }

        try {
            $import = $this->imports->createFromUpload(
                $sourceId,
                $_FILES['import_file']
            );

            return [
                'success' => true,
                'import_id' => $import['import_id'],
                'total' => $import['total'],
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $this->shell->translate(
                    $e->getMessage()
                ),
            ];
        }
    }

    /**
     * Procesa el siguiente lote de una importación.
     */
    public function process(): array
    {
        $importId = (int) Tools::getValue('import_id');

        if ($importId <= 0) {
            return [
                'success' => false,
                'message' => $this->shell->translate(
                    'The import is not valid.'
                ),
            ];
        }

        try {
            return array_merge(
                ['success' => true],
                $this->imports->process($importId)
            );

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $this->shell->translate(
                    $e->getMessage()
                ),
            ];
        }
    }
}
