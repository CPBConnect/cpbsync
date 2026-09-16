<?php

namespace CPBConnect\Presentation\Admin;

use CPBConnect\Application\Import\ImportBatchProcessor;
use CPBConnect\Application\Import\ImportFileStorage;
use CPBConnect\Application\Import\ImportService;
use CPBConnect\Application\Mapping\MappingConfigurationBuilder;
use CPBConnect\Application\Mapping\MappingInputValidator;
use CPBConnect\Application\Mapping\MappingSaver;
use CPBConnect\Application\Product\ProductDryRun;
use CPBConnect\Application\Product\ProductMapper;
use CPBConnect\Application\Product\ProductSync;
use CPBConnect\Application\Source\CsvSourceService;
use CPBConnect\Application\Source\SourceService;
use CPBConnect\Application\Source\SourceValidator;
use CPBConnect\Application\Sync\SourceSyncService;
use CPBConnect\Application\Sync\SyncHistoryService;
use CPBConnect\Infrastructure\Persistence\ImportRepository;
use CPBConnect\Infrastructure\Persistence\MappingRepository;
use CPBConnect\Infrastructure\Persistence\SourceRepository;
use CPBConnect\Infrastructure\Persistence\SyncLogRepository;
use CPBConnect\Infrastructure\Source\CsvSourceReader;
use CPBConnect\Presentation\Admin\Handler\HistoryHandler;
use CPBConnect\Presentation\Admin\Handler\ImportHandler;
use CPBConnect\Presentation\Admin\Handler\MappingHandler;
use CPBConnect\Presentation\Admin\Handler\SourceHandler;
use CPBConnect\Presentation\Admin\Handler\SyncHandler;

/**
 * Punto de entrada de las acciones del administrador.
 *
 * Traduce el parámetro cpbsync_action en el handler correspondiente.
 */
final class AdminActionRouter
{
    public function __construct(
        private AdminShellInterface $shell,
        private SourceHandler $sources,
        private MappingHandler $mapping,
        private SyncHandler $sync,
        private HistoryHandler $history,
        private ImportHandler $import
    ) {
    }

    /**
     * Construye el router con sus dependencias.
     */
    public static function create(
        AdminShellInterface $shell,
        string $moduleName
    ): self {
        $links = new AdminLinkBuilder($moduleName);

        $sourceRepository = new SourceRepository();
        $mappingRepository = new MappingRepository();
        $logRepository = new SyncLogRepository();
        $importRepository = new ImportRepository();
        $csvSourceReader = new CsvSourceReader();

        $sourceService = new SourceService(
            $sourceRepository,
            new CsvSourceService()
        );

        $sourceHandler = new SourceHandler(
            $shell,
            $links,
            $sourceService,
            new SourceValidator()
        );

        $mappingHandler = new MappingHandler(
            $shell,
            $links,
            $sourceService,
            $mappingRepository,
            new MappingInputValidator(),
            new MappingSaver($mappingRepository),
            $sourceHandler
        );

        $batchProcessor = new ImportBatchProcessor(
            $sourceRepository,
            $mappingRepository,
            $importRepository,
            $csvSourceReader,
            new ProductMapper(),
            new ProductSync()
        );

        return new self(
            $shell,
            $sourceHandler,
            $mappingHandler,
            new SyncHandler(
                $shell,
                $links,
                new SourceSyncService(
                    $sourceService,
                    $mappingRepository,
                    new MappingConfigurationBuilder(),
                    new ProductMapper(),
                    new ProductDryRun(),
                    new ProductSync(),
                    $logRepository
                ),
                $mappingHandler,
                $sourceHandler
            ),
            new HistoryHandler(
                $shell,
                $links,
                new SyncHistoryService(
                    $logRepository,
                    $sourceRepository
                ),
                $sourceHandler
            ),
            new ImportHandler(
                $shell,
                $links,
                $sourceService,
                new ImportService(
                    $sourceRepository,
                    $importRepository,
                    $csvSourceReader,
                    new ImportFileStorage(),
                    $batchProcessor
                )
            )
        );
    }

    /**
     * Ejecuta una acción y devuelve el HTML resultante.
     *
     * Las acciones JSON escriben la respuesta y terminan la petición
     * para que PrestaShop no envuelva el JSON en la plantilla.
     */
    public function handle(string $action): string
    {
        switch ($action) {
            case 'add_source':
                return $this->sources->form();

            case 'save_source':
                return $this->sources->save();

            case 'test_source':
                return $this->sources->test();

            case 'map_source':
                return $this->mapping->show();

            case 'edit_source':
                return $this->sources->edit();

            case 'delete_source':
                return $this->sources->delete();

            case 'update_source':
                return $this->sources->update();

            case 'save_mapping':
                return $this->mapping->save();

            case 'dry_run':
                return $this->sync->dryRun();

            case 'sync':
                return $this->sync->sync();

            case 'history':
                return $this->history->index();

            case 'history_detail':
                return $this->history->detail();

            case 'import':
                return $this->import->index();

            case 'upload_import':
                $this->shell->emitJson($this->import->upload());
                exit;

            case 'process_import':
                $this->shell->emitJson($this->import->process());
                exit;

            default:
                return $this->sources->index();
        }
    }
}
