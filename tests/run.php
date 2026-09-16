<?php

/**
 * Pruebas de CPB Sync.
 *
 * Ejecutar con: php tests/run.php
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/Support/Fakes.php';

use CPBConnect\Application\Import\ImportService;
use CPBConnect\Application\Mapping\MappingConfigurationBuilder;
use CPBConnect\Application\Mapping\MappingInputValidator;
use CPBConnect\Application\Mapping\MappingSaver;
use CPBConnect\Application\Source\Reader\SourceReaderRegistry;
use CPBConnect\Application\Source\SourceService;
use CPBConnect\Application\Source\SourceValidator;
use CPBConnect\Application\Sync\SourceSyncService;
use CPBConnect\Application\Sync\SyncHistoryService;
use CPBConnect\Infrastructure\PrestaShop\ModuleAdminShell;
use CPBConnect\Premium\Application\Source\Reader\JsonReader;
use CPBConnect\Premium\Application\Source\Reader\XmlReader;
use CPBConnect\Presentation\Admin\AdminActionRouter;
use CPBConnect\Presentation\Admin\AdminLinkBuilder;
use CPBConnect\Presentation\Admin\Handler\HistoryHandler;
use CPBConnect\Presentation\Admin\Handler\ImportHandler;
use CPBConnect\Presentation\Admin\Handler\MappingHandler;
use CPBConnect\Presentation\Admin\Handler\SourceHandler;
use CPBConnect\Presentation\Admin\Handler\SyncHandler;

$GLOBALS['cpbsync_checks'] = 0;
$GLOBALS['cpbsync_failures'] = 0;

function section(string $name): void
{
    echo "\n== {$name} ==\n";
}

function check(string $name, bool $ok, string $detail = ''): void
{
    $GLOBALS['cpbsync_checks']++;

    if ($ok) {
        echo "  ok   {$name}\n";

        return;
    }

    $GLOBALS['cpbsync_failures']++;

    echo "  FAIL {$name}"
         . ($detail !== '' ? " :: {$detail}" : '')
         . "\n";
}

function same($expected, $actual, string $name): void
{
    check(
        $name,
        $expected === $actual,
        'esperado ' . var_export($expected, true)
        . ' / obtenido ' . var_export($actual, true)
    );
}

function truthy($actual, string $name): void
{
    check($name, (bool) $actual, var_export($actual, true));
}

function throws(callable $callback, string $expectedMessage, string $name): void
{
    try {
        $callback();
    } catch (Throwable $e) {
        same($expectedMessage, $e->getMessage(), $name);

        return;
    }

    check($name, false, 'no se lanzó ninguna excepción');
}

/**
 * Registro de lectores simulados.
 *
 * @return array{0: SourceReaderRegistry, 1: array<string, FakeSourceReader>}
 */
function makeReaders(string ...$types): array
{
    $registry = new SourceReaderRegistry();
    $readers = [];

    foreach ($types as $type) {
        $reader = new FakeSourceReader($type);
        $registry->register($reader);
        $readers[$type] = $reader;
    }

    return [$registry, $readers];
}

/**
 * Crea el juego completo de handlers con dobles en memoria.
 *
 * @return array{shell: FakeShell, handlers: array, doubles: array}
 */
function buildAdminStack(): array
{
    $shell = new FakeShell();
    $links = new AdminLinkBuilder('cpbsync');

    $sourceRepository = new FakeSourceRepository();
    $csvSource = new FakeSourceReader();

    $readers = new SourceReaderRegistry();
    $readers->register($csvSource);

    $sourceService = new SourceService(
        $sourceRepository,
        $readers
    );

    $mappingRepository = new FakeMappingRepository();
    $logRepository = new FakeSyncLogRepository();
    $importRepository = new FakeImportRepository();
    $storage = new FakeImportFileStorage();
    $processor = new FakeImportBatchProcessor();
    $mapper = new FakeProductMapper();
    $dryRun = new FakeProductDryRun();
    $productSync = new FakeProductSync();

    $sourceHandler = new SourceHandler(
        $shell,
        $links,
        $sourceService,
        new SourceValidator($readers),
        $readers
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

    $syncHandler = new SyncHandler(
        $shell,
        $links,
        new SourceSyncService(
            $sourceService,
            $mappingRepository,
            new MappingConfigurationBuilder(),
            $mapper,
            $dryRun,
            $productSync,
            $logRepository
        ),
        $mappingHandler,
        $sourceHandler
    );

    $historyHandler = new HistoryHandler(
        $shell,
        $links,
        new SyncHistoryService(
            $logRepository,
            $sourceRepository
        ),
        $sourceHandler
    );

    $importHandler = new ImportHandler(
        $shell,
        $links,
        $sourceService,
        new ImportService(
            $sourceRepository,
            $importRepository,
            $readers,
            $storage,
            $processor
        )
    );

    return [
        'shell' => $shell,
        'handlers' => [
            'source' => $sourceHandler,
            'mapping' => $mappingHandler,
            'sync' => $syncHandler,
            'history' => $historyHandler,
            'import' => $importHandler,
        ],
        'doubles' => [
            'sourceRepository' => $sourceRepository,
            'csvSource' => $csvSource,
            'readers' => $readers,
            'sourceService' => $sourceService,
            'mappingRepository' => $mappingRepository,
            'logRepository' => $logRepository,
            'importRepository' => $importRepository,
            'storage' => $storage,
            'processor' => $processor,
            'mapper' => $mapper,
            'dryRun' => $dryRun,
            'productSync' => $productSync,
            'links' => $links,
        ],
    ];
}

/*
 * ---------------------------------------------------------------------
 * Autoload y cableado de dependencias
 * ---------------------------------------------------------------------
 */

section('Autoload y cableado de dependencias');

$sourceDirectory = dirname(__DIR__) . '/src';
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDirectory)
);

$loadedClasses = 0;
$broken = [];

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $relative = substr(
        $file->getPathname(),
        strlen($sourceDirectory) + 1,
        -4
    );

    $class = 'CPBConnect\\'
             . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

    if (
        !class_exists($class)
        && !interface_exists($class)
    ) {
        $broken[] = $class . ' (no se autocarga)';

        continue;
    }

    $loadedClasses++;

    $constructor = (new ReflectionClass($class))->getConstructor();

    if ($constructor === null) {
        continue;
    }

    foreach ($constructor->getParameters() as $parameter) {
        $type = $parameter->getType();

        if (
            !$type instanceof ReflectionNamedType
            || $type->isBuiltin()
        ) {
            continue;
        }

        $typeName = $type->getName();

        if (
            !class_exists($typeName)
            && !interface_exists($typeName)
        ) {
            $broken[] = $class . ' -> ' . $typeName;
        }
    }
}

same([], $broken, 'todas las clases y sus dependencias existen');

truthy(
    $loadedClasses >= 40,
    'se revisaron ' . $loadedClasses . ' clases del proyecto'
);

/*
 * ---------------------------------------------------------------------
 * ValidationError / SourceValidator
 * ---------------------------------------------------------------------
 */

section('SourceValidator');

[$readerRegistry] = makeReaders('csv');

$validator = new SourceValidator($readerRegistry);

same(
    null,
    $validator->validate([
        'name' => 'Proveedor A',
        'type' => 'csv',
        'url' => 'https://example.com/catalogo.csv',
        'frequency' => 'daily',
    ]),
    'acepta una fuente válida'
);

same(
    'The synchronization frequency is not valid.',
    $validator->validate([
        'name' => '',
        'type' => 'csv',
        'url' => '',
        'frequency' => 'cada_rato',
    ])->getMessage(),
    'valida la frecuencia antes que el resto'
);

same(
    'The source name is required.',
    $validator->validate([
        'name' => '   ',
        'type' => 'csv',
        'url' => 'https://example.com/a.csv',
        'frequency' => 'manual',
    ])->getMessage(),
    'exige el nombre'
);

$typeError = $validator->validate([
    'name' => 'Proveedor A',
    'type' => 'xml',
    'url' => 'https://example.com/a.xml',
    'frequency' => 'manual',
]);

same(
    'The source type "%type%" is not supported.',
    $typeError->getMessage(),
    'exige un tipo de fuente soportado'
);
same(
    ['%type%' => 'xml'],
    $typeError->getParameters(),
    'identifica el tipo de fuente no soportado'
);

same(
    'The source URL is required.',
    $validator->validate([
        'name' => 'Proveedor A',
        'type' => 'csv',
        'url' => '',
        'frequency' => 'manual',
    ])->getMessage(),
    'exige la URL'
);

same(
    'The source URL is not valid.',
    $validator->validate([
        'name' => 'Proveedor A',
        'type' => 'csv',
        'url' => 'no-es-una-url',
        'frequency' => 'manual',
    ])->getMessage(),
    'valida el formato de la URL'
);

/*
 * ---------------------------------------------------------------------
 * MappingInputValidator
 * ---------------------------------------------------------------------
 */

section('MappingInputValidator');

$mappingValidator = new MappingInputValidator();

same(
    null,
    $mappingValidator->validate(
        ['sku' => 'reference', 'nombre' => 'name'],
        ['nombre' => 'normalize_text'],
        []
    ),
    'acepta un mapping válido'
);

same(
    'You must map a supplier field to reference.',
    $mappingValidator->validate(
        ['sku' => 'name'],
        [],
        []
    )->getMessage(),
    'exige reference'
);

$error = $mappingValidator->validate(
    ['sku' => 'reference', 'precio' => 'inventado'],
    [],
    []
);

same(
    'The PrestaShop field "%field%" is not valid.',
    $error->getMessage(),
    'rechaza campos destino desconocidos'
);

same(
    ['%field%' => 'inventado'],
    $error->getParameters(),
    'adjunta el parámetro del campo'
);

$error = $mappingValidator->validate(
    ['sku' => 'reference', 'ean' => 'reference'],
    [],
    []
);

same(
    'The PrestaShop field "%field%" is assigned more than once.',
    $error->getMessage(),
    'rechaza campos destino duplicados'
);

$error = $mappingValidator->validate(
    ['sku' => 'reference', 'precio' => 'price'],
    ['precio' => 'multiplicar_por_dos'],
    []
);

same(
    'The transformation "%transform%" is not valid.',
    $error->getMessage(),
    'rechaza transformaciones desconocidas'
);

same(
    ['%transform%' => 'multiplicar_por_dos'],
    $error->getParameters(),
    'adjunta el parámetro de la transformación'
);

$error = $mappingValidator->validate(
    ['sku' => 'reference', 'precio' => 'price'],
    ['precio' => 'replace_text'],
    []
);

same(
    'You must provide the text to replace for the "%field%" field.',
    $error->getMessage(),
    'exige el texto a reemplazar'
);

same(
    ['%field%' => 'precio'],
    $error->getParameters(),
    'identifica el campo sin texto de búsqueda'
);

same(
    null,
    $mappingValidator->validate(
        ['sku' => 'reference', 'precio' => 'price'],
        ['precio' => 'replace_text'],
        ['precio' => 'USD']
    ),
    'acepta replace_text con texto de búsqueda'
);

same(
    null,
    $mappingValidator->validate(
        ['sku' => 'reference', 'precio' => ''],
        [],
        []
    ),
    'ignora los campos sin mapear'
);

/*
 * ---------------------------------------------------------------------
 * MappingSaver
 * ---------------------------------------------------------------------
 */

section('MappingSaver');

$mappingRepository = new FakeMappingRepository();
$mappingSaver = new MappingSaver($mappingRepository);

$mappingSaver->save(
    3,
    ['sku' => 'reference', 'precio' => 'price', 'vacio' => ''],
    ['precio' => 'replace_text'],
    ['precio' => 'USD '],
    ['precio' => '']
);

same(1, count($mappingRepository->replaced), 'persiste el mapping');
same(3, $mappingRepository->replaced[0][0], 'usa el id de la fuente');

$rows = $mappingRepository->replaced[0][1];

same(2, count($rows), 'omite los campos sin mapear');

same(
    [
        'source_field' => 'sku',
        'target_field' => 'reference',
        'transform' => 'none',
        'transform_config' => null,
    ],
    $rows[0],
    'normaliza la transformación por defecto'
);

same(
    json_encode(
        ['search' => 'USD ', 'replace' => ''],
        JSON_UNESCAPED_UNICODE
    ),
    $rows[1]['transform_config'],
    'guarda la configuración de replace_text'
);

$mappingSaver->save(
    4,
    ['sku' => 'reference'],
    ['sku' => '  '],
    [],
    []
);

same(
    'none',
    $mappingRepository->replaced[1][1][0]['transform'],
    'una transformación vacía se normaliza a none'
);

/*
 * ---------------------------------------------------------------------
 * SourceService
 * ---------------------------------------------------------------------
 */

section('SourceService');

$sourceRepository = new FakeSourceRepository();
[$registry, $registeredReaders] = makeReaders('csv');
$csvSource = $registeredReaders['csv'];
$sourceService = new SourceService($sourceRepository, $registry);

$csvSource->result = [
    'headers' => ['sku'],
    'rows' => [['sku' => 'A']],
    'total' => 1,
];

$read = $sourceService->read([
    'type' => 'csv',
    'url' => 'https://example.com/catalogo.csv',
]);

same([['sku' => 'A']], $read['rows'], 'lee el catálogo de la fuente');
same(
    'https://example.com/catalogo.csv',
    $csvSource->readSources[0]['url'],
    'pasa la fuente completa al lector'
);

throws(
    static fn () => $sourceService->read([
        'type' => 'xml',
        'url' => 'https://example.com/catalogo.xml',
    ]),
    'The source type is not supported.',
    'rechaza fuentes con un tipo no registrado'
);

same(null, $sourceService->find(0), 'ignora identificadores no válidos');

$sourceRepository->sources = [
    5 => ['id_source' => 5, 'name' => 'Proveedor A'],
];

same(
    'Proveedor A',
    $sourceService->find(5)['name'],
    'busca una fuente existente'
);

/*
 * ---------------------------------------------------------------------
 * SyncHistoryService
 * ---------------------------------------------------------------------
 */

section('SyncHistoryService');

$logRepository = new FakeSyncLogRepository();
$sourceRepository = new FakeSourceRepository();

$sourceRepository->sources = [
    5 => ['id_source' => 5, 'name' => 'Proveedor A'],
];

$logRepository->logs = [
    1 => [
        'id_log' => 1,
        'id_source' => 5,
        'status' => 'success',
        'details' => json_encode(
            [['reference' => 'A', 'status' => 'created']]
        ),
    ],
    2 => [
        'id_log' => 2,
        'id_source' => 9,
        'status' => 'error',
        'details' => null,
    ],
];

$history = new SyncHistoryService(
    $logRepository,
    $sourceRepository
);

$recent = $history->recent(50);

same(2, count($recent), 'devuelve las ejecuciones');
same(
    'Proveedor A',
    $recent[0]['source_name'],
    'resuelve el nombre de la fuente'
);
same(
    null,
    $recent[1]['source_name'],
    'deja sin nombre las fuentes eliminadas'
);

$detail = $history->detail(1);

same('A', $detail['details'][0]['reference'], 'decodifica el detalle');
same('Proveedor A', $detail['source']['name'], 'incluye la fuente');
same([], $history->detail(2)['details'], 'sin detalle devuelve vacío');

throws(
    static fn () => $history->detail(99),
    'The run does not exist.',
    'falla si la ejecución no existe'
);

/*
 * ---------------------------------------------------------------------
 * SourceSyncService
 * ---------------------------------------------------------------------
 */

section('SourceSyncService');

$sourceRepository = new FakeSourceRepository();
[$registry, $registeredReaders] = makeReaders('csv');
$csvSource = $registeredReaders['csv'];
$mappingRepository = new FakeMappingRepository();
$logRepository = new FakeSyncLogRepository();
$mapper = new FakeProductMapper();
$dryRun = new FakeProductDryRun();
$productSync = new FakeProductSync();

$sourceRepository->sources = [
    5 => [
        'id_source' => 5,
        'name' => 'Proveedor A',
        'type' => 'csv',
        'url' => 'https://example.com/catalogo.csv',
    ],
];

$csvSource->result = [
    'headers' => ['sku'],
    'rows' => [['sku' => 'A'], ['sku' => 'B']],
    'total' => 2,
];

$mappingRepository->mappings = [
    [
        'source_field' => 'sku',
        'target_field' => 'reference',
        'transform' => 'none',
        'transform_config' => null,
    ],
];

$syncService = new SourceSyncService(
    new SourceService($sourceRepository, $registry),
    $mappingRepository,
    new MappingConfigurationBuilder(),
    $mapper,
    $dryRun,
    $productSync,
    $logRepository
);

$dryRunResult = $syncService->dryRun(5, 3);

same(
    'Proveedor A',
    $dryRunResult['source']['name'],
    'el dry run devuelve la fuente'
);
same(2, count($dryRun->calls[0][0]), 'el dry run recibe los registros');
same(3, $dryRun->calls[0][2], 'el dry run respeta el límite');
same(
    'reference',
    $dryRun->calls[0][1]['sku']['target'],
    'el dry run usa el mapping configurado'
);

$syncResult = $syncService->sync(5);

same(1, count($mapper->mapped), 'la sincronización mapea los registros');
same(1, count($productSync->synced), 'la sincronización aplica los productos');
same(5, $logRepository->created[0]['id_source'], 'registra el log');
same(
    'manual',
    $logRepository->created[0]['execution_type'],
    'el log manual se marca como manual'
);
same(1, $syncResult['log_id'], 'devuelve el id del log');

[$lonelyRegistry] = makeReaders('csv');

$noSource = new SourceSyncService(
    new SourceService(
        new FakeSourceRepository(),
        $lonelyRegistry
    ),
    $mappingRepository,
    new MappingConfigurationBuilder(),
    $mapper,
    $dryRun,
    $productSync,
    $logRepository
);

throws(
    static fn () => $noSource->sync(5),
    'The source does not exist.',
    'falla si la fuente no existe'
);

$emptyCsv = new FakeSourceReader();
$emptyCsv->result = ['headers' => ['sku'], 'rows' => [], 'total' => 0];

$emptyRegistry = new SourceReaderRegistry();
$emptyRegistry->register($emptyCsv);

$emptyRows = new SourceSyncService(
    new SourceService($sourceRepository, $emptyRegistry),
    $mappingRepository,
    new MappingConfigurationBuilder(),
    $mapper,
    $dryRun,
    $productSync,
    $logRepository
);

throws(
    static fn () => $emptyRows->sync(5),
    'The source contains no records.',
    'falla si la fuente está vacía'
);

$noMapping = new SourceSyncService(
    new SourceService($sourceRepository, $registry),
    new FakeMappingRepository(),
    new MappingConfigurationBuilder(),
    $mapper,
    $dryRun,
    $productSync,
    $logRepository
);

throws(
    static fn () => $noMapping->sync(5),
    'The source has no mapping configured.',
    'falla si falta el mapping'
);

/*
 * ---------------------------------------------------------------------
 * Registro de lectores
 * ---------------------------------------------------------------------
 */

section('SourceReaderRegistry');

[$twoReaders, $twoRegistered] = makeReaders('csv', 'xml');

same(
    ['csv', 'xml'],
    $twoReaders->allowedTypes(),
    'enumera los tipos registrados'
);
same(
    ['csv' => 'CSV', 'xml' => 'XML'],
    $twoReaders->types(),
    'devuelve tipo y etiqueta para el formulario'
);
same(
    ['csv', 'xml'],
    $twoReaders->fileExtensions(),
    'reúne las extensiones admitidas'
);
truthy($twoReaders->has('xml'), 'reconoce un tipo registrado');
same(null, $twoReaders->get('json'), 'devuelve null si el tipo no existe');
same(
    $twoRegistered['xml'],
    $twoReaders->get('xml'),
    'devuelve el lector del tipo solicitado'
);

/*
 * ---------------------------------------------------------------------
 * Lectores de pago
 * ---------------------------------------------------------------------
 */

if (class_exists(XmlReader::class)) {
    class TestableXmlReader extends XmlReader
    {
        public string $content = '';

        protected function fetchContent(string $url): string
        {
            return $this->content;
        }
    }

    class TestableJsonReader extends JsonReader
    {
        public string $content = '';

        protected function fetchContent(string $url): string
        {
            return $this->content;
        }
    }
}

section('Lectores XML y JSON (edición de pago)');

if (!class_exists(XmlReader::class)) {
    echo "  (omitido: el paquete instalado es la edición gratuita)\n";
} else {
    $source = ['type' => 'xml', 'url' => 'https://example.com/a.xml'];

    $xml = new TestableXmlReader();
    $xml->content = '<catalog>'
                    . '<product><sku>A</sku><name>Uno</name></product>'
                    . '<product><sku>B</sku><name>Dos</name></product>'
                    . '</catalog>';

    $result = $xml->read($source);

    same(['sku', 'name'], $result['headers'], 'XML: detecta las columnas');
    same(2, $result['total'], 'XML: detecta el registro repetido');
    same('B', $result['rows'][1]['sku'], 'XML: lee los valores');

    $xml->content = '<catalog><products>'
                    . '<product><sku>A</sku></product>'
                    . '<product><sku>B</sku></product>'
                    . '</products></catalog>';

    same(
        2,
        $xml->read($source)['total'],
        'XML: desciende a un contenedor anidado'
    );

    $xml->content = '<root><list><item><sku>A</sku></item></list></root>';

    same(
        1,
        $xml->read($source + [
            'config' => json_encode(
                ['record_path' => '/root/list/item']
            ),
        ])['total'],
        'XML: respeta record_path'
    );

    $xml->content = '<catalog>'
                    . '<product><sku>A</sku>'
                    . '<attrs><color>rojo</color></attrs></product>'
                    . '<product><sku>B</sku>'
                    . '<attrs><color>azul</color></attrs></product>'
                    . '</catalog>';

    same(
        '<color>rojo</color>',
        $xml->read($source)['rows'][0]['attrs'],
        'XML: conserva el marcado interno'
    );

    $xml->content = 'esto no es xml';
    throws(
        static fn () => $xml->read($source),
        'The XML source could not be parsed.',
        'XML: informa de un documento inválido'
    );

    $xml->content = '<root><a>1</a></root>';
    throws(
        static fn () => $xml->read($source),
        'The XML source does not contain a list of records.',
        'XML: exige una lista de registros'
    );

    $jsonSource = ['type' => 'json', 'url' => 'https://example.com/a.json'];

    $json = new TestableJsonReader();
    $json->content = '[{"sku":"A","price":"1"},{"sku":"B","price":"2"}]';

    $result = $json->read($jsonSource);

    same(['sku', 'price'], $result['headers'], 'JSON: detecta las columnas');
    same(2, $result['total'], 'JSON: lee una lista en la raíz');
    same('B', $result['rows'][1]['sku'], 'JSON: lee los valores');

    $json->content = '{"meta":{"page":1},"products":[{"sku":"A"}]}';

    same(
        1,
        $json->read($jsonSource)['total'],
        'JSON: encuentra la lista dentro del objeto'
    );

    $json->content = '{"data":{"items":[{"sku":"A"},{"sku":"B"}]}}';

    same(
        2,
        $json->read($jsonSource + [
            'config' => json_encode(['record_path' => 'data.items']),
        ])['total'],
        'JSON: respeta record_path'
    );

    $json->content = '{"meta":{"page":1}}';
    throws(
        static fn () => $json->read($jsonSource),
        'The JSON source does not contain a list of records.',
        'JSON: exige una lista de registros'
    );

    $json->content = '{esto no es json';
    throws(
        static fn () => $json->read($jsonSource),
        'The JSON source could not be parsed.',
        'JSON: informa de un documento inválido'
    );
}

/*
 * ---------------------------------------------------------------------
 * AdminLinkBuilder
 * ---------------------------------------------------------------------
 */

section('AdminLinkBuilder');

$links = new AdminLinkBuilder('cpbsync');
$base = 'http://shop.test/admin/index.php?controller=AdminModules';

same(
    $base . '&configure=cpbsync',
    $links->home(),
    'url del módulo'
);

same(
    $base . '&configure=cpbsync&cpbsync_action=dry_run&id_source=7',
    $links->dryRun(7),
    'url del dry run'
);

same(
    $base . '&configure=cpbsync&cpbsync_action=save_mapping&id_source=7',
    $links->saveMapping(7),
    'url de guardado del mapping'
);

same(
    $base . '&configure=cpbsync&cpbsync_action=history_detail&id_log=3',
    $links->historyDetail(3),
    'url del detalle del historial'
);

same(
    $base . '&configure=cpbsync&cpbsync_action=import',
    $links->import(),
    'url de importación'
);

same(
    $base . '&configure=cpbsync&cpbsync_action=process_import',
    $links->processImport(),
    'url de procesado de importación'
);

/*
 * ---------------------------------------------------------------------
 * SourceHandler
 * ---------------------------------------------------------------------
 */

section('SourceHandler');

$stack = buildAdminStack();
$shell = $stack['shell'];
$sourceHandler = $stack['handlers']['source'];
$sourceRepository = $stack['doubles']['sourceRepository'];
$csvSource = $stack['doubles']['csvSource'];
$productSync = $stack['doubles']['productSync'];

$sourceRepository->sources = [
    1 => [
        'id_source' => 1,
        'name' => 'Proveedor A',
        'type' => 'csv',
        'url' => 'https://example.com/catalogo.csv',
        'frequency' => 'daily',
        'active' => 1,
    ],
];

Tools::set([]);

same(
    'fetch:sources.tpl',
    $sourceHandler->index(),
    'el listado renderiza sources.tpl'
);

$assigned = $shell->lastAssignment();

same(1, count($assigned['sources']), 'asigna las fuentes');
truthy(
    isset($assigned['sources'][0]['map_url']),
    'añade la url de mapeo'
);
truthy(
    isset($assigned['sources'][0]['delete_url']),
    'añade la url de borrado'
);
truthy(isset($assigned['import_url']), 'añade la url de importación');
check(
    !isset($assigned['module_name']),
    'no asigna variables que ninguna plantilla usa'
);

same(
    'fetch:source-form.tpl',
    $sourceHandler->form(),
    'el alta renderiza el formulario'
);

Tools::set(['id_source' => 1]);
same(
    'fetch:source-form.tpl',
    $sourceHandler->edit(),
    'la edición renderiza el formulario'
);
same(
    $base . '&configure=cpbsync&cpbsync_action=update_source&id_source=1',
    $shell->lastAssignment()['form_action'],
    'la edición apunta a update_source'
);

Tools::set(['id_source' => 99]);
same(
    'fetch:sources.tpl',
    $sourceHandler->edit(),
    'una fuente inexistente vuelve al listado'
);

Tools::set([
    'name' => '',
    'type' => 'csv',
    'url' => '',
    'frequency' => 'daily',
]);

same(
    'fetch:source-form.tpl',
    $sourceHandler->save(),
    'los datos inválidos vuelven al formulario'
);
same(
    'The source name is required.',
    $shell->lastAssignment()['form_error'],
    'muestra el error de validación'
);
same([], $sourceRepository->created, 'no crea la fuente');

Tools::set([
    'name' => 'Proveedor B',
    'type' => 'csv',
    'url' => 'https://example.com/b.csv',
    'frequency' => 'daily',
    'active' => '1',
]);

same('fetch:sources.tpl', $sourceHandler->save(), 'crea la fuente');
same(1, count($sourceRepository->created), 'persiste la fuente');
same(
    'Proveedor B',
    $sourceRepository->created[0]['name'],
    'envía el nombre limpio'
);
same(1, $sourceRepository->created[0]['active'], 'normaliza active');
same(
    ['The source was created successfully.'],
    $shell->confirmations,
    'confirma la creación'
);

Tools::set([
    'id_source' => 1,
    'name' => 'Proveedor A2',
    'type' => 'csv',
    'url' => 'https://example.com/a2.csv',
    'frequency' => 'hourly',
    'active' => '1',
]);

same('fetch:sources.tpl', $sourceHandler->update(), 'actualiza la fuente');
same(1, count($sourceRepository->updated), 'persiste la actualización');
same(1, $sourceRepository->updated[0][0], 'actualiza el id correcto');
same(
    'The source was updated successfully.',
    $shell->confirmations[count($shell->confirmations) - 1],
    'confirma la actualización'
);

Tools::set(['id_source' => 0]);
$sourceHandler->delete();
same(
    'The source is not valid.',
    $shell->errors[count($shell->errors) - 1],
    'rechaza borrados sin id'
);

Tools::set(['id_source' => 99]);
$sourceHandler->delete();
same(
    'The source could not be found.',
    $shell->errors[count($shell->errors) - 1],
    'avisa si la fuente no existe'
);

Tools::set(['id_source' => 1]);
$sourceHandler->delete();
same([1], $sourceRepository->deleted, 'elimina la fuente');
same(
    'The source was deleted successfully.',
    $shell->confirmations[count($shell->confirmations) - 1],
    'confirma el borrado'
);

$csvSource->result = [
    'headers' => ['sku'],
    'rows' => array_fill(0, 7, ['sku' => 'A']),
    'total' => 7,
];

Tools::set(['id_source' => 1]);

same(
    'fetch:source-preview.tpl',
    $sourceHandler->test(),
    'la prueba de conexión renderiza la vista previa'
);
same(
    5,
    count($shell->lastAssignment()['rows']),
    'limita la vista previa a 5 registros'
);
same(7, $shell->lastAssignment()['total'], 'informa el total');

$csvSource->error = new RuntimeException('connection failed');

Tools::set(['id_source' => 1]);

same(
    'fetch:sources.tpl',
    $sourceHandler->test(),
    'una conexión fallida vuelve al listado'
);
same(
    'Could not connect to the source: connection failed',
    $shell->errors[count($shell->errors) - 1],
    'explica el fallo de conexión'
);

$csvSource->error = null;

/*
 * ---------------------------------------------------------------------
 * MappingHandler
 * ---------------------------------------------------------------------
 */

section('MappingHandler');

$stack = buildAdminStack();
$shell = $stack['shell'];
$mappingHandler = $stack['handlers']['mapping'];
$sourceRepository = $stack['doubles']['sourceRepository'];
$csvSource = $stack['doubles']['csvSource'];
$mappingRepository = $stack['doubles']['mappingRepository'];

$sourceRepository->sources = [
    1 => [
        'id_source' => 1,
        'name' => 'Proveedor A',
        'type' => 'csv',
        'url' => 'https://example.com/catalogo.csv',
    ],
];

$csvSource->result = [
    'headers' => ['sku', 'precio'],
    'rows' => [['sku' => 'A', 'precio' => '1']],
    'total' => 1,
];

$mappingRepository->mappings = [
    [
        'source_field' => 'sku',
        'target_field' => 'reference',
        'transform' => 'none',
        'transform_config' => null,
    ],
    [
        'source_field' => 'precio',
        'target_field' => 'price',
        'transform' => 'replace_text',
        'transform_config' => json_encode(
            ['search' => 'USD', 'replace' => '']
        ),
    ],
];

Tools::set(['id_source' => 1]);

same(
    'fetch:source-mapping.tpl',
    $mappingHandler->show(),
    'el mapping renderiza source-mapping.tpl'
);

$assigned = $shell->lastAssignment();

same(
    ['sku' => 'reference', 'precio' => 'price'],
    $assigned['saved_mappings'],
    'reconstruye los mappings guardados'
);
same(
    ['sku' => 'none', 'precio' => 'replace_text'],
    $assigned['saved_transformations'],
    'reconstruye las transformaciones'
);
same(
    'USD',
    $assigned['saved_transformation_configs']['precio']['search'],
    'reconstruye la configuración de la transformación'
);
truthy(isset($assigned['dry_run_url']), 'añade la url del dry run');
truthy(isset($assigned['sync_url']), 'añade la url del sync');

Tools::set(['id_source' => 99]);
same(
    'fetch:sources.tpl',
    $mappingHandler->show(),
    'una fuente inexistente vuelve al listado'
);

Tools::set([
    'id_source' => 1,
    'mapping' => 'no-es-un-array',
]);

$mappingHandler->save();
same(
    'The submitted mapping is not valid.',
    $shell->errors[count($shell->errors) - 1],
    'rechaza mappings mal formados'
);

Tools::set([
    'id_source' => 1,
    'mapping' => ['sku' => 'name'],
    'transformation' => [],
    'transformation_search' => [],
    'transformation_replace' => [],
]);

$mappingHandler->save();
same(
    'You must map a supplier field to reference.',
    $shell->errors[count($shell->errors) - 1],
    'exige reference al guardar'
);

Tools::set([
    'id_source' => 1,
    'mapping' => ['sku' => 'reference', 'precio' => 'inventado'],
    'transformation' => [],
    'transformation_search' => [],
    'transformation_replace' => [],
]);

$mappingHandler->save();
same(
    'The PrestaShop field "inventado" is not valid.',
    $shell->errors[count($shell->errors) - 1],
    'sustituye el parámetro del campo inválido'
);
same(
    [],
    $mappingRepository->replaced,
    'no guarda mappings inválidos'
);

Tools::set([
    'id_source' => 1,
    'mapping' => ['sku' => 'reference', 'precio' => 'price'],
    'transformation' => ['precio' => 'normalize_price'],
    'transformation_search' => [],
    'transformation_replace' => [],
]);

same(
    'fetch:source-mapping.tpl',
    $mappingHandler->save(),
    'guarda el mapping y vuelve al formulario'
);
same(1, count($mappingRepository->replaced), 'persiste el mapping');
same(1, $mappingRepository->replaced[0][0], 'usa el id de la fuente');
same(
    'normalize_price',
    $mappingRepository->replaced[0][1][1]['transform'],
    'guarda la transformación elegida'
);
same(
    'The mapping was saved successfully.',
    $shell->confirmations[count($shell->confirmations) - 1],
    'confirma el guardado'
);

Tools::set(['id_source' => 0, 'mapping' => []]);
same(
    'fetch:sources.tpl',
    $mappingHandler->save(),
    'sin id de fuente vuelve al listado'
);

/*
 * ---------------------------------------------------------------------
 * SyncHandler
 * ---------------------------------------------------------------------
 */

section('SyncHandler');

$stack = buildAdminStack();
$shell = $stack['shell'];
$syncHandler = $stack['handlers']['sync'];
$sourceRepository = $stack['doubles']['sourceRepository'];
$csvSource = $stack['doubles']['csvSource'];
$mappingRepository = $stack['doubles']['mappingRepository'];
$logRepository = $stack['doubles']['logRepository'];
$mapper = $stack['doubles']['mapper'];
$productSync = $stack['doubles']['productSync'];

$sourceRepository->sources = [
    1 => [
        'id_source' => 1,
        'name' => 'Proveedor A',
        'type' => 'csv',
        'url' => 'https://example.com/catalogo.csv',
    ],
];

$csvSource->result = [
    'headers' => ['sku'],
    'rows' => [['sku' => 'A']],
    'total' => 1,
];

$mappingRepository->mappings = [
    [
        'source_field' => 'sku',
        'target_field' => 'reference',
        'transform' => 'none',
        'transform_config' => null,
    ],
];

Tools::set(['id_source' => 1]);

same(
    'fetch:dry-run.tpl',
    $syncHandler->dryRun(),
    'el dry run renderiza dry-run.tpl'
);
same(
    5,
    $stack['doubles']['dryRun']->calls[0][2],
    'el dry run usa el límite de 5 productos'
);

same(
    'fetch:sync-result.tpl',
    $syncHandler->sync(),
    'la sincronización renderiza sync-result.tpl'
);
same(1, count($mapper->mapped), 'mapea antes de sincronizar');
same(1, count($productSync->synced), 'sincroniza los productos');
same(1, $logRepository->created[0]['id_source'], 'registra el log manual');
same(
    1,
    $shell->lastAssignment()['log_id'],
    'expone el id del log a la plantilla'
);

Tools::set(['id_source' => 0]);
same(
    'fetch:sources.tpl',
    $syncHandler->dryRun(),
    'sin id de fuente vuelve al listado'
);

$sourceRepository->sources = [];

Tools::set(['id_source' => 1]);

same(
    'fetch:sources.tpl',
    $syncHandler->sync(),
    'si la fuente no existe vuelve al listado'
);
same(
    'The synchronization could not be executed: The source does not exist.',
    $shell->errors[count($shell->errors) - 1],
    'explica el fallo del sync'
);

/*
 * ---------------------------------------------------------------------
 * HistoryHandler
 * ---------------------------------------------------------------------
 */

section('HistoryHandler');

$stack = buildAdminStack();
$shell = $stack['shell'];
$historyHandler = $stack['handlers']['history'];
$sourceRepository = $stack['doubles']['sourceRepository'];
$logRepository = $stack['doubles']['logRepository'];

$sourceRepository->sources = [
    5 => ['id_source' => 5, 'name' => 'Proveedor A'],
];

$logRepository->logs = [
    1 => [
        'id_log' => 1,
        'id_source' => 5,
        'status' => 'success',
        'total' => 2,
        'created' => 2,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'date_add' => '2026-01-01 10:00:00',
        'details' => json_encode(
            [['reference' => 'A', 'status' => 'created']]
        ),
    ],
];

Tools::set([]);

same(
    'fetch:history.tpl',
    $historyHandler->index(),
    'el historial renderiza history.tpl'
);

$assigned = $shell->lastAssignment();

same('Proveedor A', $assigned['logs'][0]['source_name'], 'resuelve la fuente');
same(
    $base . '&configure=cpbsync&cpbsync_action=history_detail&id_log=1',
    $assigned['logs'][0]['detail_url'],
    'añade el enlace al detalle'
);

Tools::set(['id_log' => 0]);
same(
    'fetch:history.tpl',
    $historyHandler->detail(),
    'sin id vuelve al listado'
);

Tools::set(['id_log' => 1]);
same(
    'fetch:history-detail.tpl',
    $historyHandler->detail(),
    'el detalle renderiza history-detail.tpl'
);
same(
    'A',
    $shell->lastAssignment()['details'][0]['reference'],
    'pasa el detalle decodificado'
);

Tools::set(['id_log' => 99]);
same(
    'fetch:history.tpl',
    $historyHandler->detail(),
    'una ejecución inexistente vuelve al listado'
);
same(
    'The detail could not be loaded: The run does not exist.',
    $shell->errors[count($shell->errors) - 1],
    'explica el fallo del detalle'
);

/*
 * ---------------------------------------------------------------------
 * ImportHandler
 * ---------------------------------------------------------------------
 */

section('ImportHandler');

$stack = buildAdminStack();
$shell = $stack['shell'];
$importHandler = $stack['handlers']['import'];
$sourceRepository = $stack['doubles']['sourceRepository'];
$importRepository = $stack['doubles']['importRepository'];
$reader = $stack['doubles']['csvSource'];
$processor = $stack['doubles']['processor'];

$sourceRepository->sources = [
    1 => ['id_source' => 1, 'name' => 'Proveedor A', 'type' => 'csv'],
];

Tools::set([]);
same(
    'display:import.tpl',
    $importHandler->index(),
    'el formulario usa Module::display'
);

unset($_FILES['import_file']);

Tools::set(['id_source' => 0]);
$response = $importHandler->upload();
same(false, $response['success'], 'sin fuente no sube nada');
same(
    'You must select a source.',
    $response['message'],
    'explica que falta la fuente'
);

Tools::set(['id_source' => 1]);
$response = $importHandler->upload();
same(false, $response['success'], 'sin archivo no sube nada');
same(
    'You must select a CSV file.',
    $response['message'],
    'explica que falta el archivo'
);

$_FILES['import_file'] = [
    'name' => 'catalogo.csv',
    'tmp_name' => '/tmp/catalogo.csv',
    'error' => UPLOAD_ERR_OK,
];

$reader->fileRows = 25;

$response = $importHandler->upload();
same(true, $response['success'], 'sube el archivo');
same(7, $response['import_id'], 'devuelve el id de importación');
same(25, $response['total'], 'devuelve el total de registros');
same(25, $importRepository->created[0]['total'], 'crea la importación');
same(
    'manual',
    $importRepository->created[0]['execution_type'],
    'marca la importación como manual'
);

$reader->fileRows = 0;
$response = $importHandler->upload();
same(false, $response['success'], 'un CSV vacío no se importa');
same(
    'The file contains no products.',
    $response['message'],
    'explica que el CSV está vacío'
);

Tools::set(['import_id' => 0]);
$response = $importHandler->process();
same(false, $response['success'], 'sin importación no procesa');
same(
    'The import is not valid.',
    $response['message'],
    'explica que la importación no es válida'
);

Tools::set(['import_id' => 7]);
$response = $importHandler->process();
same(true, $response['success'], 'procesa el lote');
same(7, $response['id_import'], 'devuelve el id');
same('processing', $response['status'], 'devuelve el estado');
same(50, $response['progress'], 'calcula el progreso');
same(4, $response['success_count'], 'informa los éxitos');
same(1, $response['errors'], 'informa los errores');

$processor->result = [
    'id_import' => 7,
    'status' => 'completed',
    'total' => 0,
    'processed' => 0,
    'success' => 0,
    'errors' => 0,
];

same(
    100,
    $importHandler->process()['progress'],
    'un total de cero se considera completado'
);

$processor->error = new RuntimeException('No fue posible procesar el lote.');

$response = $importHandler->process();
same(false, $response['success'], 'un lote roto no rompe la respuesta');
same(
    'No fue posible procesar el lote.',
    $response['message'],
    'propaga el error del procesador'
);

/*
 * ---------------------------------------------------------------------
 * AdminActionRouter
 * ---------------------------------------------------------------------
 */

section('AdminActionRouter');

$stack = buildAdminStack();
$shell = $stack['shell'];
$sourceRepository = $stack['doubles']['sourceRepository'];
$csvSource = $stack['doubles']['csvSource'];
$mappingRepository = $stack['doubles']['mappingRepository'];

$sourceRepository->sources = [
    1 => [
        'id_source' => 1,
        'name' => 'Proveedor A',
        'type' => 'csv',
        'url' => 'https://example.com/catalogo.csv',
        'frequency' => 'daily',
        'active' => 1,
    ],
];

$csvSource->result = [
    'headers' => ['sku'],
    'rows' => [['sku' => 'A']],
    'total' => 1,
];

$mappingRepository->mappings = [
    [
        'source_field' => 'sku',
        'target_field' => 'reference',
        'transform' => 'none',
        'transform_config' => null,
    ],
];

$router = new AdminActionRouter(
    $shell,
    $stack['handlers']['source'],
    $stack['handlers']['mapping'],
    $stack['handlers']['sync'],
    $stack['handlers']['history'],
    $stack['handlers']['import']
);

Tools::reset();

same(
    'fetch:sources.tpl',
    $router->handle(''),
    'sin acción muestra el listado'
);
same(
    'fetch:sources.tpl',
    $router->handle('accion_desconocida'),
    'una acción desconocida muestra el listado'
);
same(
    'fetch:source-form.tpl',
    $router->handle('add_source'),
    'enruta add_source'
);

Tools::set(['id_source' => 1]);

same(
    'fetch:source-form.tpl',
    $router->handle('edit_source'),
    'enruta edit_source'
);
same(
    'fetch:source-preview.tpl',
    $router->handle('test_source'),
    'enruta test_source'
);
same(
    'fetch:source-mapping.tpl',
    $router->handle('map_source'),
    'enruta map_source'
);
same(
    'fetch:dry-run.tpl',
    $router->handle('dry_run'),
    'enruta dry_run'
);
same(
    'fetch:sync-result.tpl',
    $router->handle('sync'),
    'enruta sync'
);
same(
    'fetch:history.tpl',
    $router->handle('history'),
    'enruta history'
);
same(
    'fetch:source-mapping.tpl',
    $router->handle('save_mapping'),
    'enruta save_mapping'
);
same(
    'display:import.tpl',
    $router->handle('import'),
    'enruta import'
);

Tools::set([
    'name' => 'Proveedor B',
    'type' => 'csv',
    'url' => 'https://example.com/b.csv',
    'frequency' => 'manual',
    'active' => 0,
]);

same(
    'fetch:sources.tpl',
    $router->handle('save_source'),
    'enruta save_source'
);

Tools::set([
    'id_source' => 1,
    'name' => 'Proveedor A',
    'type' => 'csv',
    'url' => 'https://example.com/a.csv',
    'frequency' => 'manual',
    'active' => 1,
]);

same(
    'fetch:sources.tpl',
    $router->handle('update_source'),
    'enruta update_source'
);
same(
    'fetch:sources.tpl',
    $router->handle('delete_source'),
    'enruta delete_source'
);

Tools::set(['id_log' => 0]);
same(
    'fetch:history.tpl',
    $router->handle('history_detail'),
    'enruta history_detail'
);

/*
 * ---------------------------------------------------------------------
 * Traducción de los textos de la interfaz
 * ---------------------------------------------------------------------
 */

section('Traducción de textos');

$stack = buildAdminStack();
$shell = $stack['shell'];
$sourceRepository = $stack['doubles']['sourceRepository'];
$csvSource = $stack['doubles']['csvSource'];
$mappingRepository = $stack['doubles']['mappingRepository'];
$logRepository = $stack['doubles']['logRepository'];
$dryRun = $stack['doubles']['dryRun'];

$shell->catalogue = [
    'The source name is required.'
        => 'El nombre de la fuente es obligatorio.',
    'Deleted source' => 'Fuente eliminada',
    'The name field is required.'
        => 'El campo name es obligatorio.',
    'You must select a source.'
        => 'Debes seleccionar una fuente.',
    'Uploading file...' => 'Subiendo archivo...',
];

same(
    [
        'El campo name es obligatorio.',
        'Untranslated message',
    ],
    $shell->translateMessages([
        'The name field is required.',
        'Untranslated message',
    ]),
    'traduce listas de mensajes y respeta los desconocidos'
);

$sourceRepository->sources = [
    1 => [
        'id_source' => 1,
        'name' => 'Proveedor A',
        'type' => 'csv',
        'url' => 'https://example.com/catalogo.csv',
        'frequency' => 'daily',
        'active' => 1,
    ],
];

Tools::set([
    'name' => '',
    'type' => 'csv',
    'url' => '',
    'frequency' => 'daily',
]);

$stack['handlers']['source']->save();

same(
    'El nombre de la fuente es obligatorio.',
    $shell->lastAssignment()['form_error'],
    'traduce el error de validación del formulario'
);

$logRepository->logs = [
    1 => [
        'id_log' => 1,
        'id_source' => 9,
        'status' => 'success',
        'details' => null,
    ],
];

$sourceRepository->sources = [];

Tools::set([]);
$stack['handlers']['history']->index();

same(
    'Fuente eliminada',
    $shell->lastAssignment()['logs'][0]['source_name'],
    'traduce las fuentes eliminadas del historial'
);

$sourceRepository->sources = [
    1 => [
        'id_source' => 1,
        'name' => 'Proveedor A',
        'type' => 'csv',
        'url' => 'https://example.com/catalogo.csv',
    ],
];

$csvSource->result = [
    'headers' => ['sku'],
    'rows' => [['sku' => 'A']],
    'total' => 1,
];

$mappingRepository->mappings = [
    [
        'source_field' => 'sku',
        'target_field' => 'reference',
        'transform' => 'none',
        'transform_config' => null,
    ],
];

$dryRun->result = [
    [
        'number' => 1,
        'valid' => false,
        'errors' => ['The name field is required.'],
        'data' => [],
    ],
];

Tools::set(['id_source' => 1]);
$stack['handlers']['sync']->dryRun();

same(
    'El campo name es obligatorio.',
    $shell->lastAssignment()['products'][0]['errors'][0],
    'traduce los errores de producto del Dry Run'
);

unset($_FILES['import_file']);
Tools::set(['id_source' => 0]);

same(
    'Debes seleccionar una fuente.',
    $stack['handlers']['import']->upload()['message'],
    'traduce los mensajes JSON de la importación'
);

Tools::set([]);
$stack['handlers']['import']->index();

same(
    'Subiendo archivo...',
    $shell->lastAssignment()['import_messages']['uploading'],
    'pasa los mensajes traducidos al script de importación'
);

/*
 * ---------------------------------------------------------------------
 * ModuleAdminShell
 * ---------------------------------------------------------------------
 */

section('ModuleAdminShell (adaptador PrestaShop)');

$module = new Module();
$shell = new ModuleAdminShell(
    $module,
    '/shop/modules/cpbsync/cpbsync.php'
);
$context = Context::getContext();

$context->smarty->vars = [];

$shell->assign(['clave' => 'valor']);

same(
    'valor',
    $context->smarty->vars['clave'],
    'asigna variables al smarty del contexto'
);

same(
    'smarty:' . $module->getLocalPath()
    . 'views/templates/admin/sources.tpl',
    $shell->fetch('sources.tpl'),
    'renderiza la plantilla desde la ruta del módulo'
);

same(
    'module-display:views/templates/admin/import.tpl',
    $shell->display('import.tpl'),
    'delega en Module::display'
);
same(
    '/shop/modules/cpbsync/cpbsync.php',
    $module->lastFile,
    'pasa el archivo del módulo a display'
);
same(
    '/modules/cpbsync/',
    $context->smarty->vars['module_dir'],
    'Module::display expone module_dir para import.tpl'
);

same(
    'Hola mundo',
    $shell->translate('Hola %quien%', ['%quien%' => 'mundo']),
    'traduce con parámetros'
);
same(
    'Modules.Cpbsync.Admin',
    $context->getTranslator()->calls[0]['domain'],
    'traduce con el dominio del módulo'
);

$context->controller->errors = [];
$context->controller->confirmations = [];

$shell->addError('Fallo %detalle%', ['%detalle%' => 'X']);
$shell->addConfirmation('Hecho');

same(
    ['Fallo X'],
    $context->controller->errors,
    'acumula errores en el controlador'
);
same(
    ['Hecho'],
    $context->controller->confirmations,
    'acumula confirmaciones en el controlador'
);

/*
 * En CLI header() avisa porque la prueba ya escribió en la salida;
 * en una petición real de PrestaShop no ocurre.
 */
$reporting = error_reporting(E_ALL & ~E_WARNING);

$json = $shell->json(['ok' => true]);

ob_start();
$shell->emitJson(['ok' => true]);
$emitted = ob_get_clean();

error_reporting($reporting);

same('{"ok":true}', $json, 'serializa JSON');
same('{"ok":true}', $emitted, 'emite la respuesta JSON');

/*
 * ---------------------------------------------------------------------
 * Resumen
 * ---------------------------------------------------------------------
 */

echo "\n----------------------------------------\n";
echo 'Comprobaciones: ' . $GLOBALS['cpbsync_checks'] . "\n";
echo 'Fallos: ' . $GLOBALS['cpbsync_failures'] . "\n";

exit($GLOBALS['cpbsync_failures'] === 0 ? 0 : 1);
