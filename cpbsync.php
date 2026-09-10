<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use CPBConnect\Application\Mapping\MappingConfigurationBuilder;
use CPBConnect\Application\Product\ProductDryRun;
use CPBConnect\Application\Product\ProductMapper;
use CPBConnect\Application\Product\ProductSync;
use CPBConnect\Application\Source\CsvSourceService;
use CPBConnect\Infrastructure\Persistence\MappingRepository;
use CPBConnect\Infrastructure\Persistence\SourceRepository;
use CPBConnect\Infrastructure\Persistence\SyncLogRepository;

class CpbSync extends Module
{
    public function __construct()
    {
        $this->name = 'cpbsync';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'CPBConnect';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->confirmUninstall = true;

        parent::__construct();

        $this->displayName = $this->trans(
            'CPB Sync',
            [],
            'Modules.Cpbsync.Admin'
        );

        $this->description = $this->trans(
            'Synchronize external product catalogs with PrestaShop using configurable mappings and data transformations.',
            [],
            'Modules.Cpbsync.Admin'
        );

        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];
    }

    public function install(): bool
    {
        return parent::install()
                      && $this->installDatabase();
    }

    private function installDatabase(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cpbsync_sync_log` (
            `id_log` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_source` INT UNSIGNED NOT NULL,
            `status` VARCHAR(32) NOT NULL,
            `execution_type` VARCHAR(16) NOT NULL DEFAULT "manual",
            `total` INT UNSIGNED NOT NULL DEFAULT 0,
            `created` INT UNSIGNED NOT NULL DEFAULT 0,
            `updated` INT UNSIGNED NOT NULL DEFAULT 0,
            `skipped` INT UNSIGNED NOT NULL DEFAULT 0,
            `errors` INT UNSIGNED NOT NULL DEFAULT 0,
            `details` LONGTEXT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_log`),
            KEY `idx_cpbsync_sync_log_source` (`id_source`),
            KEY `idx_cpbsync_sync_log_date` (`date_add`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cpbsync_source` (
            `id_source` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `type` VARCHAR(32) NOT NULL,
            `url` TEXT NOT NULL,
            `frequency` VARCHAR(32) NOT NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_source`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cpbsync_mapping` (
            `id_mapping` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_source` INT UNSIGNED NOT NULL,
            `source_field` VARCHAR(255) NOT NULL,
            `target_field` VARCHAR(255) NOT NULL,
            `transform` VARCHAR(64) NOT NULL DEFAULT "none",
            `transform_config` TEXT NULL,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_mapping`),
            KEY `idx_cpbsync_mapping_source` (`id_source`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cpbsync_product_meta` (
            `id_meta` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_product` INT UNSIGNED NOT NULL,
            `field` VARCHAR(64) NOT NULL,
            `source_value` TEXT NOT NULL,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_meta`),
            UNIQUE KEY `uniq_cpbsync_product_field` (`id_product`, `field`),
            KEY `idx_cpbsync_product` (`id_product`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        return Db::getInstance()->execute($sql);
    }

    public function uninstall(): bool
    {
        return  $this->uninstallDatabase()
                && parent::uninstall();
    }

    private function uninstallDatabase(): bool
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'cpbsync_sync_log`';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'cpbsync_product_meta`';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'cpbsync_mapping`';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'cpbsync_source`';

        return Db::getInstance()->execute($sql);
    }

    public function getContent()
    {
        $action = (string) Tools::getValue('cpbsync_action');

        switch ($action) {
            case 'add_source':
                return $this->renderSourceForm();

            case 'save_source':
                return $this->saveSource();

            case 'test_source':
                return $this->testSource();

            case 'map_source':
                return $this->mapSource();

            case 'edit_source':
                return $this->editSource();

            case 'delete_source':
                return $this->deleteSource();

            case 'update_source':
                return $this->updateSource();

            case 'save_mapping':
                return $this->saveMapping();

            case 'dry_run':
                return $this->dryRun();

            case 'sync':
                return $this->sync();

            case 'history':
                return $this->history();

            case 'history_detail':
                return $this->historyDetail();

            default:
                return $this->renderSources();
        }
    }

    private function getCsvSourceData(array $source): array
    {
        if ($source['type'] !== 'csv') {
            throw new \RuntimeException(
                'Por ahora solo se pueden utilizar fuentes CSV.'
            );
        }

        $service = new CsvSourceService();

        return $service->read($source['url']);
    }

    private function getMappingConfiguration(int $sourceId): array
    {
        $repository = new MappingRepository();

        $savedMappings = $repository->findBySourceId($sourceId);

        if (empty($savedMappings)) {
            throw new \RuntimeException(
                'La fuente no tiene un mapping configurado.'
            );
        }

        $builder = new MappingConfigurationBuilder();

        return $builder->build($savedMappings);
    }

    private function testSource(): string
    {
        $id = (int) Tools::getValue('id_source');
        $source = $this->getSourceFromRequest($id);

        if (!$source) {
            return $this->renderSources();
        }

        try {
            $result = $this->getCsvSourceData($source);

            $this->context->smarty->assign([
                                               'source' => $source,
                                               'total' => $result['total'],
                                               'headers' => $result['headers'],
                                               'rows' => array_slice($result['rows'], 0, 5),
                                               'back_url' => $this->getAdminUrl(),
                                           ]);

            return $this->context->smarty->fetch(
                $this->local_path . 'views/templates/admin/source-preview.tpl'
            );
        } catch (\Throwable $e) {
            $this->addError(
                'No fue posible conectar con la fuente: %error%',
                [
                    '%error%' => $e->getMessage(),
                ]
            );
        }

        return $this->renderSources();
    }

    private function getSourceDataFromRequest(): array
    {
        return [
            'name' => trim((string) Tools::getValue('name')),
            'type' => (string) Tools::getValue('type'),
            'url' => trim((string) Tools::getValue('url')),
            'frequency' => (string) Tools::getValue('frequency'),
            'active' => (int) Tools::getValue('active'),
        ];
    }

    private function mapSource(): string
    {
        $id = (int) Tools::getValue('id_source');
        $source = $this->getSourceFromRequest($id);

        if (!$source) {
            return $this->renderSources();
        }

        try {
            $result = $this->getCsvSourceData($source);

            $mappingRepository = new MappingRepository();

            $savedMappings = $mappingRepository->findBySourceId(
                (int) $source['id_source']
            );

            $mappingValues = [];
            $transformationValues = [];
            $transformationConfigs = [];

            foreach ($savedMappings as $savedMapping) {
                $mappingValues[$savedMapping['source_field']] =
                    $savedMapping['target_field'];

                $transformationValues[$savedMapping['source_field']] =
                    $savedMapping['transform'] ?? 'none';

                $transformationConfigs[$savedMapping['source_field']] =
                    !empty($savedMapping['transform_config'])
                        ? json_decode(
                        $savedMapping['transform_config'],
                        true
                    )
                        : [];
            }

            $this->context->smarty->assign([
                                               'source' => $source,
                                               'headers' => $result['headers'],
                                               'saved_mappings' => $mappingValues,
                                               'saved_transformations' => $transformationValues,
                                               'saved_transformation_configs' => $transformationConfigs,
                                               'save_mapping_url' => $this->getSaveMappingUrl(
                                                   (int) $source['id_source']
                                               ),
                                               'back_url' => $this->getAdminUrl(),
                                               'dry_run_url' => $this->getDryRunUrl(
                                                   (int) $source['id_source']
                                               ),
                                               'sync_url' => $this->getSyncUrl(
                                                   (int) $source['id_source']
                                               ),
                                           ]);

            return $this->context->smarty->fetch(
                $this->local_path . 'views/templates/admin/source-mapping.tpl'
            );

        } catch (\Throwable $e) {
            $this->addError(
                'No fue posible cargar la fuente: %error%',
                ['%error%' => $e->getMessage()]
            );
        }

        return $this->renderSources();
    }

    private function dryRun(): string
    {
        $sourceId = (int) Tools::getValue('id_source');

        if ($sourceId <= 0) {
            return $this->renderSources();
        }

        $sourceRepository = new SourceRepository();

        try {
            $source = $sourceRepository->findById($sourceId);

            if (!$source) {
                throw new \RuntimeException(
                    'La fuente no existe.'
                );
            }

            $result = $this->getCsvSourceData($source);

            if (empty($result['rows'])) {
                throw new \RuntimeException(
                    'La fuente no contiene registros.'
                );
            }

            $mapping =
                $this->getMappingConfiguration($sourceId);

            $dryRun = new ProductDryRun();

            $products = $dryRun->run(
                $result['rows'],
                $mapping,
                5
            );

            $this->context->smarty->assign([
                                               'source' => $source,
                                               'products' => $products,
                                               'back_url' => $this->getMapSourceUrl($sourceId),
                                           ]);

            return $this->context->smarty->fetch(
                $this->local_path .
                'views/templates/admin/dry-run.tpl'
            );

        } catch (\Throwable $e) {
            $this->addError(
                'No fue posible ejecutar el Dry Run: %error%',
                ['%error%' => $e->getMessage()]
            );
        }

        return $this->mapSource();
    }

    private function sync(): string
    {
        $sourceId = (int) Tools::getValue('id_source');

        if ($sourceId <= 0) {
            return $this->renderSources();
        }

        $sourceRepository = new SourceRepository();

        try {
            $source = $sourceRepository->findById($sourceId);

            if (!$source) {
                throw new \RuntimeException(
                    'La fuente no existe.'
                );
            }

            $result = $this->getCsvSourceData($source);

            if (empty($result['rows'])) {
                throw new \RuntimeException(
                    'La fuente no contiene registros.'
                );
            }

            $mapping =
                $this->getMappingConfiguration($sourceId);

            $mapper = new ProductMapper();

            $products = $mapper->map(
                $result['rows'],
                $mapping
            );

            $productSync = new ProductSync();

            $syncResult = $productSync->sync($products);

            $logRepository = new SyncLogRepository();

            $logId = $logRepository->create(
                $sourceId,
                $syncResult,
                'manual'
            );

            $this->context->smarty->assign([
                                               'source' => $source,
                                               'result' => $syncResult,
                                               'log_id' => $logId,
                                               'back_url' => $this->getMapSourceUrl($sourceId),
                                           ]);

            return $this->context->smarty->fetch(
                $this->local_path .
                'views/templates/admin/sync-result.tpl'
            );

        } catch (\Throwable $e) {
            $this->addError(
                'No fue posible ejecutar el Sync: %error%',
                ['%error%' => $e->getMessage()]
            );
        }

        return $this->mapSource();
    }

    private function history(): string
    {
        $logRepository = new SyncLogRepository();

        $sourceRepository = new SourceRepository();

        try {
            $logs = $logRepository->findAll(50);

            foreach ($logs as &$log) {
                $source =
                    $sourceRepository->findById(
                        (int) $log['id_source']
                    );

                $log['source_name'] =
                    $source['name'] ?? 'Fuente eliminada';

                $log['detail_url'] =
                    $this->getHistoryDetailUrl(
                        (int) $log['id_log']
                    );
            }

            unset($log);

            $this->context->smarty->assign([
                                               'logs' => $logs,
                                               'back_url' => $this->getAdminUrl(),
                                           ]);

            return $this->context->smarty->fetch(
                $this->local_path .
                'views/templates/admin/history.tpl'
            );

        } catch (\Throwable $e) {
            $this->addError(
                'No fue posible cargar el historial: %error%',
                ['%error%' => $e->getMessage()]
            );
        }

        return $this->renderSources();
    }

    private function historyDetail(): string
    {
        $idLog = (int) Tools::getValue('id_log');

        if ($idLog <= 0) {
            return $this->history();
        }

        $logRepository = new SyncLogRepository();

        $sourceRepository = new SourceRepository();

        try {
            $log = $logRepository->findById($idLog);

            if (!$log) {
                throw new \RuntimeException(
                    'La ejecución no existe.'
                );
            }

            $source =
                $sourceRepository->findById(
                    (int) $log['id_source']
                );

            $details = [];

            if (!empty($log['details'])) {
                $decoded = json_decode(
                    $log['details'],
                    true
                );

                if (is_array($decoded)) {
                    $details = $decoded;
                }
            }

            $this->context->smarty->assign([
                                               'log' => $log,
                                               'source' => $source,
                                               'details' => $details,
                                               'back_url' => $this->getHistoryUrl(),
                                           ]);

            return $this->context->smarty->fetch(
                $this->local_path .
                'views/templates/admin/history-detail.tpl'
            );

        } catch (\Throwable $e) {
            $this->addError(
                'No fue posible cargar el detalle: %error%',
                ['%error%' => $e->getMessage()]
            );
        }

        return $this->history();
    }

    private function getHistoryUrl(): string
    {
        return $this->context->link->getAdminLink(
            'AdminModules',
            true,
            [],
            [
                'configure' => $this->name,
                'cpbsync_action' => 'history',
            ]
        );
    }

    private function getHistoryDetailUrl(int $idLog): string
    {
        return $this->context->link->getAdminLink(
            'AdminModules',
            true,
            [],
            [
                'configure' => $this->name,
                'cpbsync_action' => 'history_detail',
                'id_log' => $idLog,
            ]
        );
    }

    private function getSaveMappingUrl(int $id): string
    {
        return $this->context->link->getAdminLink(
            'AdminModules',
            true,
            [],
            [
                'configure' => $this->name,
                'cpbsync_action' => 'save_mapping',
                'id_source' => $id,
            ]
        );
    }

    private function getMapSourceUrl(int $id): string
    {
        return $this->context->link->getAdminLink(
            'AdminModules',
            true,
            [],
            [
                'configure' => $this->name,
                'cpbsync_action' => 'map_source',
                'id_source' => $id,
            ]
        );
    }

    private function editSource(): string
    {
        $id = (int) Tools::getValue('id_source');
        $source = $this->getSourceFromRequest($id);

        if (!$source) {
            return $this->renderSources();
        }

        $this->context->smarty->assign([
                                           'source' => $source,
                                           'cancel_url' => $this->getAdminUrl(),
                                           'form_action' => $this->getUpdateSourceUrl($id),
                                           'form_error' => null,
                                       ]);

        return $this->context->smarty->fetch(
            $this->local_path . 'views/templates/admin/source-form.tpl'
        );
    }

    private function getUpdateSourceUrl(int $id): string
    {
        return $this->context->link->getAdminLink(
            'AdminModules',
            true,
            [],
            [
                'configure' => $this->name,
                'cpbsync_action' => 'update_source',
                'id_source' => $id,
            ]
        );
    }

    private function updateSource(): string
    {
        $id = (int) Tools::getValue('id_source');
        $source = $this->getSourceFromRequest($id);

        if (!$source) {
            return $this->renderSources();
        }

        $sourceData = $this->getSourceDataFromRequest();

        $error = $this->validateSourceData(
            $sourceData['name'],
            $sourceData['type'],
            $sourceData['url'],
            $sourceData['frequency']
        );

        if ($error !== null) {
            return $this->renderSourceForm($error,
                                           [
                                               'id_source' => $id,
                                               'name' => $sourceData['name'],
                                               'type' => $sourceData['type'],
                                               'url' => $sourceData['url'],
                                               'frequency' => $sourceData['frequency'],
                                               'active' => $sourceData['active'],
                                           ],
                                           $this->getUpdateSourceUrl($id)
            );
        }
        $repository = new SourceRepository();
        $updated = $repository->update($id, [
            'name' => $sourceData['name'],
            'type' => $sourceData['type'],
            'url' => $sourceData['url'],
            'frequency' => $sourceData['frequency'],
            'active' => $sourceData['active'],
        ]);

        if (!$updated) {
            return $this->renderSourceForm(
                'No fue posible actualizar la fuente.',
                array_merge(
                    ['id_source' => $id],
                    $sourceData
                ),
                $this->getUpdateSourceUrl($id)
            );
        }

        $this->addConfirmation('La fuente se actualizó correctamente.');

        return $this->renderSources();
    }

    private function saveMapping(): string
    {
        $sourceId = (int) Tools::getValue('id_source');

        $mapping = Tools::getValue('mapping', []);

        $transformations =
            Tools::getValue('transformation', []);

        $transformationSearch =
            Tools::getValue('transformation_search', []);

        $transformationReplace =
            Tools::getValue('transformation_replace', []);

        /*
         * Validar estructura del formulario.
         */
        if (!is_array($mapping)) {
            $this->addError('El mapping recibido no es válido.');

            return $this->mapSource();
        }

        if (!is_array($transformations)) {
            $transformations = [];
        }

        if (!is_array($transformationSearch)) {
            $transformationSearch = [];
        }

        if (!is_array($transformationReplace)) {
            $transformationReplace = [];
        }

        if ($sourceId <= 0) {
            return $this->renderSources();
        }

        /*
         * Validar que reference esté configurado.
         */
        $hasReference = false;

        foreach ($mapping as $targetField) {

            if ($targetField === 'reference') {
                $hasReference = true;
                break;
            }
        }

        if (!$hasReference) {
            $this->addError('Debes asignar un campo del proveedor a reference.');

            return $this->mapSource();
        }

        /*
         * Campos destino que pueden utilizarse.
         */
        $allowedTargetFields = [
            'reference',
            'name',
            'description',
            'price',
            'quantity',
            'image',
            'category',
            'manufacturer',
            'ean13',
        ];

        /*
         * Transformaciones permitidas.
         */
        $allowedTransformations = [
            'none',
            'normalize_price',
            'normalize_stock',
            'normalize_text',
            'replace_text',
        ];

        $usedTargetFields = [];

        foreach ($mapping as $sourceField => $targetField) {

            if ($targetField === '') {
                continue;
            }

            /*
             * Validar campo destino.
             */
            if (!in_array(
                $targetField,
                $allowedTargetFields,
                true
            )) {
                $this->addError(
                    'El campo de PrestaShop "%field%" no es válido.',
                    ['%field%' => $targetField]
                );

                return $this->mapSource();
            }

            /*
             * Evitar campos destino duplicados.
             */
            if (isset($usedTargetFields[$targetField])) {
                $this->addError(
                    'El campo de PrestaShop "%field%" está asignado más de una vez.',
                    ['%field%' => $targetField]
                );

                return $this->mapSource();
            }

            $usedTargetFields[$targetField] = true;

            /*
             * Obtener transformación.
             */
            $transform =
                isset($transformations[$sourceField])
                    ? trim((string) $transformations[$sourceField])
                    : 'none';

            if ($transform === '') {
                $transform = 'none';
            }

            /*
             * Validar transformación.
             */
            if (!in_array(
                $transform,
                $allowedTransformations,
                true
            )) {
                $this->addError(
                    'La transformación "%transform%" no es válida.',
                    ['%transform%' => $transform]
                );

                return $this->mapSource();
            }

            /*
             * replace_text requiere texto a buscar.
             */
            if ($transform === 'replace_text') {

                $search =
                    isset($transformationSearch[$sourceField])
                        ? (string) $transformationSearch[$sourceField]
                        : '';

                $replace =
                    isset($transformationReplace[$sourceField])
                        ? (string) $transformationReplace[$sourceField]
                        : '';

                if (trim($search) === '') {
                    $this->addError(
                        'Debes indicar el texto que deseas reemplazar para el campo "%field%".',
                        ['%field%' => $sourceField]
                    );

                    return $this->mapSource();
                }
            }
        }

        $repository = new MappingRepository();

        try {

            $mappingsToSave = [];

            foreach ($mapping as $sourceField => $targetField) {

                if ($targetField === '') {
                    continue;
                }

                $transform =
                    isset($transformations[$sourceField])
                        ? trim((string) $transformations[$sourceField])
                        : 'none';

                if ($transform === '') {
                    $transform = 'none';
                }

                $transformConfig = null;

                if ($transform === 'replace_text') {

                    $search =
                        isset($transformationSearch[$sourceField])
                            ? (string) $transformationSearch[$sourceField]
                            : '';

                    $replace =
                        isset($transformationReplace[$sourceField])
                            ? (string) $transformationReplace[$sourceField]
                            : '';

                    $transformConfig = json_encode(
                        [
                            'search' => $search,
                            'replace' => $replace,
                        ],
                        JSON_UNESCAPED_UNICODE
                    );

                    if ($transformConfig === false) {
                        throw new \RuntimeException(
                            'No fue posible guardar la configuración de la transformación.'
                        );
                    }
                }

                $mappingsToSave[] = [
                    'source_field' => (string) $sourceField,
                    'target_field' => (string) $targetField,
                    'transform' => $transform,
                    'transform_config' => $transformConfig,
                ];
            }

            $repository->replaceForSource(
                $sourceId,
                $mappingsToSave
            );
            $this->addConfirmation('El mapping se guardó correctamente.');

        } catch (\Throwable $e) {
            $this->addError(
                'No fue posible guardar el mapping: %error%',
                ['%error%' => $e->getMessage()]
            );
        }

        return $this->mapSource();
    }

    private function getDryRunUrl(int $id)
    {
        return $this->context->link->getAdminLink(
            'AdminModules',
            true,
            [],
            [
                'configure' => $this->name,
                'cpbsync_action' => 'dry_run',
                'id_source' => $id,
            ]
        );
    }

    private function getSyncUrl(int $id)
    {
        return $this->context->link->getAdminLink(
            'AdminModules',
            true,
            [],
            [
                'configure' => $this->name,
                'cpbsync_action' => 'sync',
                'id_source' => $id,
            ]
        );
    }

    private function deleteSource(): string
    {
        $id = (int) Tools::getValue('id_source');

        if ($id <= 0) {
            $this->addError('La fuente no es válida.');

            return $this->renderSources();
        }

        $repository = new SourceRepository();

        $source = $repository->findById($id);

        if (!$source) {
            $this->addError('No fue posible encontrar la fuente.');

            return $this->renderSources();
        }

        $deleted = $repository->delete($id);

        if (!$deleted) {
            $this->addError('No fue posible eliminar la fuente.');

            return $this->renderSources();
        }

        $this->addConfirmation('La fuente se eliminó correctamente.');

        return $this->renderSources();
    }

    private function renderSources(): string
    {
        $repository = new SourceRepository();

        $sources = $repository->findAll();
        foreach ($sources as &$source) {
            $source['map_url'] = $this->getMapSourceUrl(
                (int) $source['id_source']
            );

            $source['test_url'] = $this->getTestSourceUrl(
                (int) $source['id_source']
            );

            $source['edit_url'] = $this->getEditSourceUrl(
                (int) $source['id_source']
            );

            $source['delete_url'] = $this->getDeleteSourceUrl(
                (int) $source['id_source']
            );
        }

        unset($source);

        $this->context->smarty->assign([
                                           'module_name' => $this->displayName,
                                           'sources' => $sources,
                                           'source_form_url' => $this->getSourceFormUrl(),
                                           'history_url' => $this->getHistoryUrl(),
                                       ]);

        return $this->context->smarty->fetch(
            $this->local_path . 'views/templates/admin/sources.tpl'
        );
    }

    private function getTestSourceUrl(int $id): string
    {
        return $this->context->link->getAdminLink(
            'AdminModules',
            true,
            [],
            [
                'configure' => $this->name,
                'cpbsync_action' => 'test_source',
                'id_source' => $id,
            ]
        );
    }

    private function getEditSourceUrl(int $id)
    {
        return $this->context->link->getAdminLink(
            'AdminModules',
            true,
            [],
            [
                'configure' => $this->name,
                'cpbsync_action' => 'edit_source',
                'id_source' => $id,
            ]
        );
    }

    private function getDeleteSourceUrl(int $id)
    {
        return $this->context->link->getAdminLink(
            'AdminModules',
            true,
            [],
            [
                'configure' => $this->name,
                'cpbsync_action' => 'delete_source',
                'id_source' => $id,
            ]
        );
    }

    private function validateSourceData(
        string $name,
        string $type,
        string $url,
        string $frequency
    ): ?string {
        $allowedFrequencies = [
            'manual',
            'hourly',
            '6_hours',
            'daily',
        ];

        if (!in_array($frequency, $allowedFrequencies, true)) {
            return 'La frecuencia de sincronización no es válida.';
        }

        if ($name === '') {
            return 'El nombre de la fuente es obligatorio.';
        }

        if ($type !== 'csv') {
            return 'En esta versión solo se admiten fuentes CSV.';
        }

        if ($url === '') {
            return 'La URL de la fuente es obligatoria.';
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return 'La URL de la fuente no es válida.';
        }

        return null;
    }

    private function saveSource(): string
    {
        $sourceData = $this->getSourceDataFromRequest();

        $error = $this->validateSourceData(
            $sourceData['name'],
            $sourceData['type'],
            $sourceData['url'],
            $sourceData['frequency']
        );

        if ($error !== null) {
            return $this->renderSourceForm(
                $error,
                [
                    'name' => $sourceData['name'],
                    'type' => $sourceData['type'],
                    'url' => $sourceData['url'],
                    'frequency' => $sourceData['frequency'],
                    'active' => $sourceData['active'],
                ],
                $this->getSaveSourceUrl()
            );
        }

        $repository = new SourceRepository();

        $created = $repository->create([
                                           'name' => $sourceData['name'],
                                           'type' => $sourceData['type'],
                                           'url' => $sourceData['url'],
                                           'frequency' => $sourceData['frequency'],
                                           'active' => $sourceData['active'],
                                       ]);

        if (!$created) {
            $this->addError('No fue posible crear la fuente.');
            return $this->renderSourceForm();
        }
        $this->addConfirmation('La fuente se creó correctamente.');

        return $this->renderSources();
    }

    private function getSourceFormUrl(): string
    {
        return $this->context->link->getAdminLink(
            'AdminModules',
            true,
            [],
            [
                'configure' => $this->name,
                'cpbsync_action' => 'add_source',
            ]
        );
    }

    private function renderSourceForm(
        ?string $error = null,
        ?array $source = null,
        ?string $formAction = null
    ): string {
        $this->context->smarty->assign([
                                           'source' => $source,
                                           'cancel_url' => $this->getAdminUrl(),
                                           'form_action' => $formAction ?? $this->getSaveSourceUrl(),
                                           'form_error' => $error,
                                       ]);

        return $this->context->smarty->fetch(
            $this->local_path . 'views/templates/admin/source-form.tpl'
        );
    }

    private function getSaveSourceUrl(): string
    {
        return $this->context->link->getAdminLink(
            'AdminModules',
            true,
            [],
            [
                'configure' => $this->name,
                'cpbsync_action' => 'save_source',
            ]
        );
    }

    private function getAdminUrl(): string
    {
        return $this->context->link->getAdminLink(
            'AdminModules',
            true,
            [],
            [
                'configure' => $this->name,
            ]
        );
    }

    private function getSourceFromRequest(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $repository = new SourceRepository();

        return $repository->findById($id);
    }

    private function addError(string $message, array $parameters = []): void
    {
        $this->context->controller->errors[] = $this->trans(
            $message,
            $parameters,
            'Modules.Cpbsync.Admin'
        );
    }

    private function addConfirmation(
        string $message,
        array $parameters = []
    ): void {
        $this->context->controller->confirmations[] = $this->trans(
            $message,
            $parameters,
            'Modules.Cpbsync.Admin'
        );
    }
}