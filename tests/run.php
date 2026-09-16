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
use CPBConnect\Application\Mapping\MappingApplier;
use CPBConnect\Application\Mapping\MappingInputValidator;
use CPBConnect\Application\Mapping\MappingSaver;
use CPBConnect\Application\Product\ProductImageProviderFactory;
use CPBConnect\Application\Product\ImageList;
use CPBConnect\Application\Product\ProductStateFactory;
use CPBConnect\Application\Source\Reader\SourceReaderRegistry;
use CPBConnect\Application\Source\SourceService;
use CPBConnect\Application\Source\SourceValidator;
use CPBConnect\Application\Sync\SourceSyncService;
use CPBConnect\Application\Sync\SyncHistoryService;
use CPBConnect\Application\Sync\SyncMetrics;
use CPBConnect\Application\Transform\TransformerFactory;
use CPBConnect\Infrastructure\PrestaShop\ModuleAdminShell;
use CPBConnect\Premium\Application\Monitoring\SyncMonitoringService;
use CPBConnect\Premium\Application\Product\IncrementalProductState;
use CPBConnect\Premium\Application\Source\Reader\JsonReader;
use CPBConnect\Premium\Application\Source\Reader\RestApiReader;
use CPBConnect\Premium\Application\Source\Reader\XmlReader;
use CPBConnect\Premium\Presentation\Admin\Handler\MonitoringHandler;
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
        ['precio' => ['search' => 'USD']]
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
 * MappingApplier
 * ---------------------------------------------------------------------
 */

section('MappingApplier');

$mappingApplier = new MappingApplier();

$mapping = [
    'sku' => [
        'target' => 'reference',
        'transform' => 'none',
    ],
    'nombre' => [
        'target' => 'name',
        'transform' => 'normalize_text',
    ],
    'precio' => [
        'target' => 'price',
        'transform' => 'normalize_price',
    ],
    'stock' => [
        'target' => 'quantity',
        'transform' => 'normalize_stock',
    ],
    'descripcion' => [
        'target' => 'description',
        'transform' => 'replace_text',
        'config' => ['search' => 'IVA', 'replace' => ''],
    ],
    'sobra' => [
        'target' => '',
        'transform' => 'none',
    ],
];

$row = [
    'sku' => 'A-1',
    'nombre' => "  Producto   con   espacios  ",
    'precio' => '1.234,56',
    'stock' => '3 unidades',
    'descripcion' => 'Precio con IVA incluido',
    'sobra' => 'x',
];

$mapped = $mappingApplier->apply($row, $mapping);

same('A-1', $mapped['reference'], 'copia los campos sin transformación');
same(
    'Producto con espacios',
    $mapped['name'],
    'normaliza el texto al sincronizar'
);
same(1234.56, $mapped['price'], 'normaliza el precio');
same(3, $mapped['quantity'], 'normaliza el stock');
same(
    'Precio con  incluido',
    $mapped['description'],
    'aplica el reemplazo de texto'
);
same(5, count($mapped), 'ignora los campos sin destino');

$dryRun = $mappingApplier->applyWithOriginals($row, $mapping);

same(
    'Producto con espacios',
    $dryRun['name']['value'],
    'el Dry Run normaliza el texto'
);
same(
    "  Producto   con   espacios  ",
    $dryRun['name']['original'],
    'el Dry Run conserva el valor original'
);
same(true, $dryRun['name']['changed'], 'marca el campo como cambiado');

same(
    $mapped['name'],
    $dryRun['name']['value'],
    'el Dry Run y la sincronización dan el mismo resultado'
);
same(
    $mapped['quantity'],
    $dryRun['quantity']['value'],
    'el stock coincide en los dos recorridos'
);
same(
    false,
    $dryRun['reference']['changed'],
    'un campo sin transformación no cambia'
);

// Formato antiguo: el mapeo es directamente el campo destino.
same(
    ['reference' => 'A-1'],
    $mappingApplier->apply($row, ['sku' => 'reference']),
    'acepta el mapeo como cadena'
);
same(
    [
        'reference' => [
            'original' => 'A-1',
            'value' => 'A-1',
            'changed' => false,
        ],
    ],
    $mappingApplier->applyWithOriginals($row, ['sku' => 'reference']),
    'acepta el mapeo como cadena en el Dry Run'
);

same(
    [],
    $mappingApplier->apply($row, ['no_existe' => 'reference']),
    'ignora los campos que no vienen en la fila'
);

/*
 * ---------------------------------------------------------------------
 * Transformaciones
 * ---------------------------------------------------------------------
 */

section('Transformaciones');

$transformers = TransformerFactory::create();

/**
 * Busca una transformación y falla si no está registrada.
 */
$transformer = static function (string $name) use ($transformers) {
    $found = $transformers->find($name);

    if ($found === null) {
        throw new RuntimeException('Falta la transformación ' . $name);
    }

    return $found;
};

$catalogRow = [
    'sku' => 'A-1',
    'marca' => 'Acme',
    'ean' => '123-456',
    'categorias' => 'Ropa|Zapatos',
    'descripcion' => '<p>Texto <b>con</b> marcado</p>',
];

truthy(
    $transformers->has('normalize_price')
    && $transformers->has('normalize_stock')
    && $transformers->has('normalize_text')
    && $transformers->has('replace_text'),
    'el núcleo registra sus cuatro transformaciones'
);

// Precio: formatos que antes se rechazaban.
$price = $transformer('normalize_price');

same(
    10.5,
    $price->transform('10,50 €', [], []),
    'acepta el precio con el símbolo de euro'
);
same(
    10.5,
    $price->transform('10.50 EUR', [], []),
    'acepta el precio con el código de moneda'
);
same(
    1234.56,
    $price->transform('1,234.56', [], []),
    'detecta el formato con la coma de miles'
);
same(
    1234.56,
    $price->transform('1.234,56', [], []),
    'detecta el formato con el punto de miles'
);
same(
    1234.56,
    $price->transform(
        '1,234.56',
        ['decimal_separator' => '.', 'thousands_separator' => ','],
        []
    ),
    'respeta los separadores configurados'
);
throws(
    static fn () => $price->transform('12.34.56', [], []),
    'The price format is not valid.',
    'rechaza un precio con dos decimales'
);

same(
    'Acme',
    $transformer('replace_text')->transform(
        'Acme S.L.',
        ['search' => ' S.L.', 'replace' => ''],
        []
    ),
    'replace_text quita el texto indicado'
);

if (!class_exists(
    'CPBConnect\\Premium\\Application\\Transform\\PremiumTransformerRegistry'
)) {
    // Sin la edición de pago sólo quedan las básicas.
    same(4, count($transformers->all()), 'el paquete gratuito no trae más');

    /*
     * -----------------------------------------------------------------
     * Fin de las pruebas
     * -----------------------------------------------------------------
     */
} else {
    section('Transformaciones avanzadas');

    truthy(
        count($transformers->all()) >= 19,
        'la edición de pago amplía el registro'
    );

    same(
        '1',
        $transformer('map_values')->transform(
            'En stock',
            ['map' => "En stock=1\nAgotado=0"],
            []
        ),
        'map_values traduce el valor'
    );
    same(
        'Otro',
        $transformer('map_values')->transform(
            'Otro',
            ['map' => 'En stock=1'],
            []
        ),
        'map_values deja el valor si no hay regla'
    );
    same(
        'You must provide at least one value to map for the "%field%" field.',
        $transformer('map_values')->validate([], 'stock')->getMessage(),
        'map_values exige reglas'
    );

    same(
        'Sin marca',
        $transformer('default_value')->transform(
            '  ',
            ['value' => 'Sin marca'],
            []
        ),
        'default_value cubre los valores vacíos'
    );
    same(
        'Acme',
        $transformer('default_value')->transform(
            'Acme',
            ['value' => 'Sin marca'],
            []
        ),
        'default_value respeta el valor recibido'
    );

    same(
        '123-456',
        $transformer('fallback_fields')->transform(
            '',
            ['fields' => 'ean,sku'],
            $catalogRow
        ),
        'fallback_fields usa otro campo de la fila'
    );
    same(
        'A-1',
        $transformer('fallback_fields')->transform(
            '',
            ['fields' => 'no_existe,sku'],
            $catalogRow
        ),
        'fallback_fields salta los campos que no existen'
    );

    same(
        'Camiseta - Acme',
        $transformer('concat')->transform(
            'Camiseta',
            ['fields' => 'marca', 'separator' => ' - '],
            $catalogRow
        ),
        'concat junta el valor con otro campo'
    );
    same(
        'Acme',
        $transformer('concat')->transform(
            '',
            ['fields' => 'marca', 'separator' => ' - '],
            $catalogRow
        ),
        'concat no deja separadores sueltos'
    );

    same(
        '[A-1]',
        $transformer('affix')->transform(
            'A-1',
            ['prefix' => '[', 'suffix' => ']'],
            []
        ),
        'affix añade prefijo y sufijo'
    );
    same(
        '',
        $transformer('affix')->transform(
            '',
            ['prefix' => '[', 'suffix' => ']'],
            []
        ),
        'affix no inventa un valor vacío'
    );

    $arithmetic = $transformer('arithmetic');

    same(
        12.71,
        $arithmetic->transform(
            '10.5',
            ['operation' => 'multiply', 'value' => '1.21', 'round' => '2'],
            []
        ),
        'arithmetic multiplica y redondea'
    );
    same(
        6.0,
        $arithmetic->transform(
            '12',
            ['operation' => 'divide', 'value' => '2'],
            []
        ),
        'arithmetic divide'
    );
    same(
        9.0,
        $arithmetic->transform(
            '10',
            ['operation' => 'subtract', 'value' => '1'],
            []
        ),
        'arithmetic resta'
    );
    same(
        'You cannot divide by zero in the "%field%" field.',
        $arithmetic->validate(
            ['operation' => 'divide', 'value' => '0'],
            'price'
        )->getMessage(),
        'arithmetic no permite dividir por cero'
    );
    same(
        'You must provide a number for the "%field%" field.',
        $arithmetic->validate(
            ['operation' => 'multiply', 'value' => 'dos'],
            'price'
        )->getMessage(),
        'arithmetic exige un número'
    );

    same(
        '123',
        $transformer('regex_extract')->transform(
            'ABC-123-XYZ',
            ['pattern' => '/([0-9]+)/', 'group' => '1'],
            []
        ),
        'regex_extract saca el grupo indicado'
    );
    same(
        '',
        $transformer('regex_extract')->transform(
            'ABC',
            ['pattern' => '/([0-9]+)/', 'group' => '1'],
            []
        ),
        'regex_extract devuelve vacío si no hay coincidencia'
    );
    same(
        'The pattern is not valid for the "%field%" field.',
        $transformer('regex_extract')->validate(
            ['pattern' => 'sin-delimitadores'],
            'reference'
        )->getMessage(),
        'regex_extract rechaza un patrón inválido'
    );

    same(
        'Camiseta azul',
        $transformer('regex_replace')->transform(
            'Camiseta   azul',
            ['pattern' => '/\s+/', 'replacement' => ' '],
            []
        ),
        'regex_replace normaliza con una expresión'
    );

    same(
        1,
        $transformer('boolean')->transform('sí', [], []),
        'boolean reconoce los valores afirmativos'
    );
    same(
        0,
        $transformer('boolean')->transform('agotado', [], []),
        'boolean deja el resto en cero'
    );

    same(
        'abcd...',
        $transformer('truncate')->transform(
            'abcdefgh',
            ['length' => '4', 'suffix' => '...'],
            []
        ),
        'truncate recorta y añade el sufijo'
    );
    same(
        'abc',
        $transformer('truncate')->transform(
            'abc',
            ['length' => '4', 'suffix' => '...'],
            []
        ),
        'truncate no toca los textos cortos'
    );
    same(
        'You must provide the maximum length for the "%field%" field.',
        $transformer('truncate')->validate([], 'name')->getMessage(),
        'truncate exige la longitud'
    );

    same(
        'camiseta-azul-nino',
        $transformer('slug')->transform(
            'Camiseta Azul Niño',
            ['separator' => '-'],
            []
        ),
        'slug quita acentos y espacios'
    );

    same(
        'CAMISETA',
        $transformer('case_format')->transform(
            'camiseta',
            ['mode' => 'upper'],
            []
        ),
        'case_format pone en mayúsculas'
    );
    same(
        'Camiseta Azul',
        $transformer('case_format')->transform(
            'camiseta azul',
            ['mode' => 'title'],
            []
        ),
        'case_format capitaliza cada palabra'
    );

    same(
        'Texto con marcado',
        $transformer('strip_html')->transform(
            '<p>Texto <b>con</b> marcado</p>',
            [],
            []
        ),
        'strip_html deja el texto sin etiquetas'
    );

    same(
        'Zapatos',
        $transformer('split_part')->transform(
            'Ropa|Zapatos',
            ['separator' => '|', 'index' => '2'],
            []
        ),
        'split_part devuelve la parte pedida'
    );
    same(
        '',
        $transformer('split_part')->transform(
            'Ropa',
            ['separator' => '|', 'index' => '2'],
            []
        ),
        'split_part devuelve vacío si no hay esa parte'
    );
    same(
        'The part number of the "%field%" field must be 1 or greater.',
        $transformer('split_part')->validate(
            ['separator' => '|', 'index' => '0'],
            'category'
        )->getMessage(),
        'split_part exige una parte válida'
    );

    same(
        '123456',
        $transformer('only_digits')->transform('123-456', [], []),
        'only_digits deja sólo los números'
    );
}

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
    ['precio' => ['search' => 'USD ', 'replace' => '']]
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
    []
);

same(
    'none',
    $mappingRepository->replaced[1][1][0]['transform'],
    'una transformación vacía se normaliza a none'
);

// Una transformación desconocida no guarda configuración.
$mappingSaver->save(
    5,
    ['sku' => 'reference'],
    ['sku' => 'inventada'],
    ['sku' => ['search' => 'x']]
);

same(
    null,
    $mappingRepository->replaced[2][1][0]['transform_config'],
    'una transformación desconocida no guarda configuración'
);

// Sólo se guardan los campos que declara la transformación.
$mappingSaver->save(
    6,
    ['precio' => 'price'],
    ['precio' => 'normalize_price'],
    ['precio' => ['decimal_separator' => ',', 'inventado' => 'x']]
);

same(
    json_encode(
        ['decimal_separator' => ',', 'thousands_separator' => ''],
        JSON_UNESCAPED_UNICODE
    ),
    $mappingRepository->replaced[3][1][0]['transform_config'],
    'guarda sólo los campos declarados por la transformación'
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

section('Estado de producto');

$stateFactory = ProductStateFactory::create();

same(
    class_exists(
        'CPBConnect\\Premium\\Application\\Product\\IncrementalProductState'
    )
        ? 'CPBConnect\\Premium\\Application\\Product\\IncrementalProductState'
        : 'CPBConnect\\Application\\Product\\CatalogProductState',
    get_class($stateFactory),
    'la fábrica elige el estado según el paquete instalado'
);

same(
    class_exists(
        'CPBConnect\\Premium\\Application\\Product\\ParallelImageProvider'
    )
        ? 'CPBConnect\\Premium\\Application\\Product\\ParallelImageProvider'
        : 'CPBConnect\\Application\\Product\\SynchronousImageProvider',
    get_class(ProductImageProviderFactory::create()),
    'la fábrica elige el proveedor de imágenes según el paquete'
);

/*
 * ---------------------------------------------------------------------
 * Lista de imágenes
 * ---------------------------------------------------------------------
 */

section('ImageList');

same(
    ['https://cdn.test/a.jpg'],
    ImageList::parse('https://cdn.test/a.jpg'),
    'una sola URL'
);

same(
    ['https://cdn.test/a.jpg', 'https://cdn.test/b.jpg'],
    ImageList::parse(
        'https://cdn.test/a.jpg, https://cdn.test/b.jpg'
    ),
    'varias URLs separadas por comas'
);

same(
    ['https://cdn.test/a.jpg', 'https://cdn.test/b.jpg'],
    ImageList::parse(
        "https://cdn.test/a.jpg\nhttps://cdn.test/b.jpg"
    ),
    'varias URLs una por línea'
);

same(
    [
        'https://cdn.test/a.jpg',
        'https://cdn.test/b.jpg',
        'https://cdn.test/c.jpg',
    ],
    ImageList::parse(
        'https://cdn.test/a.jpg | https://cdn.test/b.jpg ;'
        . ' https://cdn.test/c.jpg'
    ),
    'varias URLs con separadores mezclados'
);

same(
    ['https://cdn.test/a.jpg?w=1,2&h=3'],
    ImageList::parse('https://cdn.test/a.jpg?w=1,2&h=3'),
    'una URL con comas en los parámetros no se parte'
);

same(
    ['https://cdn.test/a.jpg', 'https://cdn.test/b.jpg'],
    ImageList::parse(
        'https://cdn.test/a.jpg, https://cdn.test/a.jpg,'
        . ' https://cdn.test/b.jpg'
    ),
    'quita las URLs repetidas'
);

same(
    ['https://cdn.test/a.jpg'],
    ImageList::parse('sin-url, https://cdn.test/a.jpg, tambien-mal'),
    'descarta lo que no es una URL'
);

same([], ImageList::parse('   '), 'un valor vacío no tiene imágenes');
same([], ImageList::parse('sin-url'), 'un valor sin URLs no tiene imágenes');

$many = [];

for ($i = 0; $i < 30; $i++) {
    $many[] = 'https://cdn.test/' . $i . '.jpg';
}

same(
    ImageList::MAX_IMAGES,
    count(ImageList::parse(implode(',', $many))),
    'corta la lista en el máximo de imágenes'
);

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

    $flat = $xml->read($source);

    same(
        ['sku', 'attrs.color'],
        $flat['headers'],
        'XML: aplana los elementos anidados'
    );
    same(
        'rojo',
        $flat['rows'][0]['attrs.color'],
        'XML: lee el valor anidado'
    );

    $xml->content = '<catalog>'
                    . '<product><sku>A</sku>'
                    . '<description>Texto <b>con</b> marcado</description>'
                    . '<price><value>10.5</value><currency>EUR</currency></price>'
                    . '</product>'
                    . '<product><sku>B</sku>'
                    . '<description><![CDATA[<p>Otro</p>]]></description>'
                    . '<price><value>20</value><currency>EUR</currency></price>'
                    . '</product>'
                    . '</catalog>';

    $mixed = $xml->read($source);

    same(
        ['sku', 'description', 'price.value', 'price.currency'],
        $mixed['headers'],
        'XML: distingue el texto con marcado del contenedor'
    );
    same(
        'Texto <b>con</b> marcado',
        $mixed['rows'][0]['description'],
        'XML: conserva el marcado de un elemento con texto propio'
    );
    same(
        '<p>Otro</p>',
        $mixed['rows'][1]['description'],
        'XML: conserva el contenido CDATA'
    );
    same(
        '10.5',
        $mixed['rows'][0]['price.value'],
        'XML: aplana el precio anidado'
    );
    same(
        'EUR',
        $mixed['rows'][0]['price.currency'],
        'XML: aplana la moneda anidada'
    );

    // Elementos repetidos: se numeran y el primero queda también sin
    // número, para que valga con cualquier número de imágenes.
    $xml->content = '<catalog>'
                    . '<product><sku>A</sku>'
                    . '<image>u1</image><image>u2</image></product>'
                    . '<product><sku>B</sku><image>u3</image></product>'
                    . '</catalog>';

    $repeated = $xml->read($source);

    same(
        ['sku', 'image.0', 'image', 'image.1'],
        $repeated['headers'],
        'XML: numera los elementos repetidos'
    );
    same(
        'u2',
        $repeated['rows'][0]['image.1'],
        'XML: lee la segunda imagen'
    );
    same(
        'u1',
        $repeated['rows'][0]['image'],
        'XML: el primer repetido también queda sin número'
    );
    same(
        'u3',
        $repeated['rows'][1]['image'],
        'XML: y sigue valiendo cuando sólo hay uno'
    );

    // Estructura tipo CommerceML (1C).
    $xml->content = '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<КоммерческаяИнформация><Товары>'
                    . '<Товар><Ид>SKU-1</Ид><Наименование>Producto A</Наименование>'
                    . '<Цены><Цена><ЦенаЗаЕдиницу>10.50</ЦенаЗаЕдиницу>'
                    . '<Валюта>EUR</Валюта></Цена></Цены>'
                    . '<ЗначенияРеквизитов>'
                    . '<ЗначениеРеквизита><Наименование>Marca</Наименование>'
                    . '<Значение>Acme</Значение></ЗначениеРеквизита>'
                    . '<ЗначениеРеквизита><Наименование>Color</Наименование>'
                    . '<Значение>Rojo</Значение></ЗначениеРеквизита>'
                    . '</ЗначенияРеквизитов></Товар>'
                    . '<Товар><Ид>SKU-2</Ид><Наименование>Producto B</Наименование>'
                    . '<Цены><Цена><ЦенаЗаЕдиницу>20.00</ЦенаЗаЕдиницу>'
                    . '<Валюта>EUR</Валюта></Цена></Цены>'
                    . '<ЗначенияРеквизитов>'
                    . '<ЗначениеРеквизита><Наименование>Marca</Наименование>'
                    . '<Значение>Otra</Значение></ЗначениеРеквизита>'
                    . '<ЗначениеРеквизита><Наименование>Color</Наименование>'
                    . '<Значение>Azul</Значение></ЗначениеРеквизита>'
                    . '</ЗначенияРеквизитов></Товар>'
                    . '</Товары></КоммерческаяИнформация>';

    $cml = $xml->read(['type' => 'xml', 'url' => 'https://example.com/cml.xml']);

    same(
        [
            'Ид',
            'Наименование',
            'Цены.Цена.ЦенаЗаЕдиницу',
            'Цены.Цена.Валюта',
            'ЗначенияРеквизитов.ЗначениеРеквизита.0.Наименование',
            'ЗначенияРеквизитов.ЗначениеРеквизита.0.Значение',
            'ЗначенияРеквизитов.ЗначениеРеквизита.1.Наименование',
            'ЗначенияРеквизитов.ЗначениеРеквизита.1.Значение',
        ],
        $cml['headers'],
        'XML: un catálogo CML expone el precio y los atributos'
    );
    same(
        '10.50',
        $cml['rows'][0]['Цены.Цена.ЦенаЗаЕдиницу'],
        'XML: el precio del catálogo CML se puede mapear'
    );
    same(
        'Acme',
        $cml['rows'][0]['ЗначенияРеквизитов.ЗначениеРеквизита.0.Значение'],
        'XML: el primer atributo se puede mapear'
    );

    $xml->content = '<catalog>'
                    . '<product id="7"><sku>A</sku>'
                    . '<measure unit="kg">2</measure></product>'
                    . '<product id="8"><sku>B</sku>'
                    . '<measure unit="kg">3</measure></product>'
                    . '</catalog>';

    $attributes = $xml->read($source);

    same(
        ['@id', 'sku', 'measure.@unit', 'measure'],
        $attributes['headers'],
        'XML: aplana también los atributos anidados'
    );
    same('kg', $attributes['rows'][0]['measure.@unit'], 'XML: lee el atributo');
    same('2', $attributes['rows'][0]['measure'], 'XML: lee el valor del contenedor');

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

    $json->content = '{"data":{"items":[]}}';

    same(
        0,
        $json->read($jsonSource + [
            'config' => json_encode(['record_path' => 'data.items']),
        ])['total'],
        'JSON: una lista vacía son cero registros'
    );

    $json->content = '{"meta":{"page":1}}';
    throws(
        static fn () => $json->read($jsonSource),
        'The JSON source does not contain a list of records.',
        'JSON: exige una lista de registros'
    );

    // Registros anidados: cada valor acaba en su propia columna.
    $json->content = json_encode([
        'data' => [
            'items' => [
                [
                    'sku' => 'A',
                    'price' => ['value' => 10.5, 'currency' => 'EUR'],
                    'categories' => [['id' => 3, 'name' => 'Cat']],
                    'stock' => ['qty' => 5, 'in_stock' => true],
                    'images' => ['u1', 'u2'],
                    'tags' => [],
                ],
                [
                    'sku' => 'B',
                    'price' => ['value' => 20, 'currency' => 'EUR'],
                    'stock' => ['qty' => 0, 'in_stock' => false],
                    'images' => ['u3'],
                ],
            ],
        ],
    ]);

    $nested = $json->read($jsonSource + [
        'config' => json_encode(['record_path' => 'data.items']),
    ]);

    same(
        [
            'sku',
            'price.value',
            'price.currency',
            'categories.0.id',
            'categories.0.name',
            'stock.qty',
            'stock.in_stock',
            'images.0',
            'images',
            'images.1',
        ],
        $nested['headers'],
        'JSON: aplana objetos y listas anidadas'
    );
    same(
        10.5,
        $nested['rows'][0]['price.value'],
        'JSON: lee el precio anidado'
    );
    same(
        'EUR',
        $nested['rows'][0]['price.currency'],
        'JSON: lee la moneda anidada'
    );
    same(
        3,
        $nested['rows'][0]['categories.0.id'],
        'JSON: lee una lista de objetos'
    );
    same(
        true,
        $nested['rows'][0]['stock.in_stock'],
        'JSON: conserva los booleanos'
    );
    same(
        'u2',
        $nested['rows'][0]['images.1'],
        'JSON: lee la segunda imagen'
    );
    same(
        'u1',
        $nested['rows'][0]['images'],
        'JSON: el primer elemento también queda sin número'
    );
    same(
        'u3',
        $nested['rows'][1]['images'],
        'JSON: y vale cuando sólo hay una imagen'
    );

    // Un registro que no es un objeto no se pierde: una lista de
    // valores con record_path explícito se lee tal cual.
    $json->content = '{"categorias":["Ropa","Zapatos"]}';
    $scalars = $json->read($jsonSource + [
        'config' => json_encode(['record_path' => 'categorias']),
    ]);

    same(2, $scalars['total'], 'JSON: lee una lista de valores');
    same(
        'Zapatos',
        $scalars['rows'][1]['value'],
        'JSON: cada valor queda en la columna value'
    );

    $json->content = '{esto no es json';
    throws(
        static fn () => $json->read($jsonSource),
        'The JSON source could not be parsed.',
        'JSON: informa de un documento inválido'
    );

    /*
     * APIs REST
     */

    $restSource = [
        'type' => 'rest',
        'url' => 'https://api.example.com/v1/products',
    ];

    $http = new FakeHttpReader();
    $rest = new RestApiReader($http);

    $http->responses = ['{"data":{"items":[{"sku":"A"},{"sku":"B"}]}}'];
    $http->requests = [];

    $result = $rest->read($restSource + [
        'config' => json_encode(['record_path' => 'data.items']),
    ]);

    same(2, $result['total'], 'REST: lee la lista indicada en record_path');
    same(
        'https://api.example.com/v1/products',
        $http->requests[0]['url'],
        'REST: usa la URL de la fuente'
    );
    same('GET', $http->requests[0]['method'], 'REST: usa GET por defecto');
    same(
        'application/json',
        $http->requests[0]['headers']['Accept'],
        'REST: pide JSON por defecto'
    );
    same(
        [],
        $http->requests[0]['parameters'],
        'REST: sin paginación no añade parámetros'
    );

    $http->responses = ['{"data":{"items":[]}}'];
    $http->requests = [];

    $rest->read($restSource + [
        'config' => json_encode([
            'record_path' => 'data.items',
            'params' => ['lang' => 'es'],
            'headers' => ['X-Store' => '1'],
            'auth' => ['type' => 'bearer', 'token' => 'abc123'],
        ]),
    ]);

    same(
        'Bearer abc123',
        $http->requests[0]['headers']['Authorization'],
        'REST: autenticación por token'
    );
    same(
        '1',
        $http->requests[0]['headers']['X-Store'],
        'REST: cabeceras propias'
    );
    same(
        ['lang' => 'es'],
        $http->requests[0]['parameters'],
        'REST: parámetros propios'
    );

    $http->responses = ['{"data":{"items":[]}}'];
    $http->requests = [];

    $rest->read($restSource + [
        'config' => json_encode([
            'record_path' => 'data.items',
            'auth' => [
                'type' => 'basic',
                'username' => 'u',
                'password' => 'p',
            ],
        ]),
    ]);

    same(
        'Basic ' . base64_encode('u:p'),
        $http->requests[0]['headers']['Authorization'],
        'REST: autenticación básica'
    );

    $http->responses = ['{"data":{"items":[]}}'];
    $http->requests = [];

    $rest->read($restSource + [
        'config' => json_encode([
            'record_path' => 'data.items',
            'auth' => [
                'type' => 'header',
                'header' => 'X-Api-Key',
                'value' => 'k',
            ],
        ]),
    ]);

    same(
        'k',
        $http->requests[0]['headers']['X-Api-Key'],
        'REST: clave en cabecera'
    );

    $pageConfig = json_encode([
        'record_path' => 'data.items',
        'pagination' => [
            'type' => 'page',
            'page_size' => 2,
        ],
    ]);

    $http->responses = [
        '{"data":{"items":[{"sku":"A"},{"sku":"B"}]}}',
        '{"data":{"items":[{"sku":"C"}]}}',
    ];
    $http->requests = [];

    $result = $rest->read($restSource + ['config' => $pageConfig]);

    same(3, $result['total'], 'REST: recorre todas las páginas');
    same(
        2,
        count($http->requests),
        'REST: para al recibir una página incompleta'
    );
    same(
        ['page' => 1, 'per_page' => 2],
        $http->requests[0]['parameters'],
        'REST: primera página'
    );
    same(
        ['page' => 2, 'per_page' => 2],
        $http->requests[1]['parameters'],
        'REST: segunda página'
    );

    $http->responses = ['{"data":{"items":[{"sku":"C"},{"sku":"D"}]}}'];
    $http->requests = [];

    $rows = $rest->readBatch(
        $restSource + ['config' => $pageConfig],
        2,
        2
    );

    same(
        1,
        count($http->requests),
        'REST: el lote sólo pide las páginas necesarias'
    );
    same(
        ['page' => 2, 'per_page' => 2],
        $http->requests[0]['parameters'],
        'REST: pide la página que contiene el lote'
    );
    same(['sku' => 'C'], $rows[0], 'REST: devuelve el lote solicitado');

    $http->responses = ['{"items":[{"sku":"A"}]}'];
    $http->requests = [];

    $rest->read($restSource + [
        'config' => json_encode([
            'record_path' => 'items',
            'pagination' => [
                'type' => 'offset',
                'page_size' => 50,
            ],
        ]),
    ]);

    same(
        ['offset' => 0, 'limit' => 50],
        $http->requests[0]['parameters'],
        'REST: paginación por desplazamiento'
    );

    $http->responses = ['{"meta":{"total":42},"items":[]}'];
    $http->requests = [];

    same(
        42,
        $rest->countRows($restSource + [
            'config' => json_encode([
                'record_path' => 'items',
                'pagination' => [
                    'type' => 'page',
                    'total_path' => 'meta.total',
                ],
            ]),
        ]),
        'REST: lee el total del documento'
    );

    $http->error = new RuntimeException(
        'The source responded with an error status code.'
    );

    throws(
        static fn () => $rest->read($restSource),
        'The source responded with an error status code.',
        'REST: propaga los errores HTTP'
    );

    $http->error = null;

    /*
     * Sincronización incremental
     */

    $stateRepository = new FakeProductStateRepository();
    $stateRepository->ids = ['SKU-1' => 10, 'SKU-2' => 11];

    $metaRepository = new FakeProductMetaRepository();

    $fallbackState = new FakeCatalogProductState();
    $fallbackState->changes = [11 => false];

    $incremental = new IncrementalProductState(
        $stateRepository,
        $metaRepository,
        $fallbackState
    );

    $product = [
        'reference' => 'SKU-1',
        'name' => 'Producto uno',
        'price' => '19.99',
    ];

    same(
        null,
        $incremental->findExistingId('SKU-1'),
        'sin preparar delega en el estado por defecto'
    );

    $incremental->prepare([$product]);

    same(
        ['ids', 'values'],
        array_column($stateRepository->calls, 0),
        'precarga identificadores y huellas en bloque'
    );
    same(
        10,
        $incremental->findExistingId('SKU-1'),
        'resuelve el id de producto por referencia'
    );
    same(
        null,
        $incremental->findExistingId('SKU-9'),
        'devuelve null si la referencia no existe'
    );

    same(
        false,
        $incremental->hasChanges(11, $product),
        'sin huella guardada compara como siempre'
    );
    same(
        [11],
        $fallbackState->asked,
        'consulta al estado por defecto cuando no hay huella'
    );

    $incremental->remember($product, 10);

    same(1, count($metaRepository->saved), 'guarda la huella');
    same(10, $metaRepository->saved[0][0], 'la guarda en el producto correcto');
    same('hash', $metaRepository->saved[0][1], 'la guarda en el campo hash');

    $hash = $metaRepository->saved[0][2];

    $storedRepository = new FakeProductStateRepository();
    $storedRepository->ids = ['SKU-1' => 10];
    $storedRepository->values = [10 => $hash];

    $second = new IncrementalProductState(
        $storedRepository,
        new FakeProductMetaRepository(),
        new FakeCatalogProductState()
    );

    $second->prepare([$product]);

    same(
        false,
        $second->hasChanges(10, $product),
        'los mismos datos no son un cambio'
    );
    same(
        true,
        $second->hasChanges(10, [
            'reference' => 'SKU-1',
            'name' => 'Producto uno',
            'price' => '29.99',
        ]),
        'un precio distinto sí es un cambio'
    );
    same(
        true,
        $second->hasChanges(10, [
            'reference' => 'SKU-1',
            'name' => 'Producto uno',
            'price' => '19.99',
            'description' => 'Nueva descripción',
        ]),
        'un campo nuevo también es un cambio'
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

same(
    ['sku' => 'reference', 'precio' => 'price'],
    $assigned['selected_targets'],
    'con un mapeo guardado no propone nada nuevo'
);

// Fuente nueva: se proponen los campos destino habituales, también con
// columnas anidadas.
$csvSource->result = [
    'headers' => [
        'sku',
        'precio',
        'images.0',
        'Цены.Цена.ЦенаЗаЕдиницу',
        'attrs.color',
    ],
    'rows' => [],
    'total' => 0,
];

$mappingRepository->mappings = [];

Tools::set(['id_source' => 1]);

$mappingHandler->show();

same(
    [
        'sku' => 'reference',
        'precio' => 'price',
        'images.0' => 'image',
        'Цены.Цена.ЦенаЗаЕдиницу' => '',
        'attrs.color' => '',
    ],
    $shell->lastAssignment()['selected_targets'],
    'propone el campo destino de las columnas anidadas'
);

same(
    [],
    $mappingRepository->replaced,
    'las sugerencias no guardan nada por su cuenta'
);

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
        'items_total' => 500,
        'duration_ms' => 1500,
        'memory_kb' => 2048,
        'phases' => json_encode([
            'read' => 500,
            'map' => 100,
            'sync' => 900,
        ]),
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

$detail = $shell->lastAssignment();

same(1.5, $detail['log']['duration'], 'la duración se muestra en segundos');
same(2.0, $detail['log']['memory'], 'la memoria se muestra en MB');
same(
    [
        ['name' => 'Reading the source', 'seconds' => 0.5],
        ['name' => 'Applying the mapping', 'seconds' => 0.1],
        ['name' => 'Writing products', 'seconds' => 0.9],
    ],
    $detail['phases'],
    'desglosa las fases en segundos'
);
same(1, $detail['items_shown'], 'cuenta los items guardados');
same(500, $detail['items_total'], 'informa del total procesado');

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
 * Acciones de administración de otras ediciones
 * ---------------------------------------------------------------------
 */

section('AdminActionRouter: acciones adicionales');

$additional = new FakeAdditionalActions();

$routerWithAdditional = new AdminActionRouter(
    $shell,
    $stack['handlers']['source'],
    $stack['handlers']['mapping'],
    $stack['handlers']['sync'],
    $stack['handlers']['history'],
    $stack['handlers']['import'],
    $additional
);

same(
    'additional:monitor',
    $routerWithAdditional->handle('monitor'),
    'delega en el router adicional la acción que no es suya'
);
same(
    ['monitor'],
    $additional->handled,
    'sólo le pasa la acción desconocida'
);
same(
    'fetch:sources.tpl',
    $routerWithAdditional->handle('otra_accion'),
    'cae al listado si el router adicional tampoco la reconoce'
);
same(
    'fetch:sources.tpl',
    $router->handle('monitor'),
    'sin edición adicional la acción no existe'
);

/*
 * ---------------------------------------------------------------------
 * SyncMetrics
 * ---------------------------------------------------------------------
 */

section('SyncMetrics');

$metrics = new SyncMetrics();
usleep(3000);
$metrics->startPhase('read');
usleep(3000);
$metrics->startPhase('map');
usleep(3000);
$metrics->stopPhase();

$measured = $metrics->finish();

truthy($measured['duration'] > 0, 'mide la duración total');
truthy($measured['memory'] > 0, 'mide el pico de memoria en KB');
same(
    ['read', 'map'],
    array_keys($measured['phases']),
    'registra sólo las fases abiertas'
);
truthy(
    $measured['phases']['read'] >= 2,
    'cada fase acumula su tiempo en milisegundos'
);
truthy(
    array_sum($measured['phases']) / 1000 <= $measured['duration'],
    'la suma de fases no supera la duración total'
);

$repeated = new SyncMetrics();
$repeated->startPhase('sync');
usleep(2000);
$repeated->startPhase('sync');
usleep(2000);
$repeated->stopPhase();

truthy(
    $repeated->finish()['phases']['sync'] >= 3,
    'suma el tiempo de una fase abierta varias veces'
);

same(
    [],
    (new SyncMetrics())->finish()['phases'],
    'sin fases no hay desglose'
);

/*
 * ---------------------------------------------------------------------
 * Monitorización (edición de pago)
 * ---------------------------------------------------------------------
 */

if (class_exists(SyncMonitoringService::class)) {
    section('SyncMonitoringService');

    Db::reset();

    $monitoring = new SyncMonitoringService();

    Db::getInstance()->rowQueue = [[
        'runs' => 3,
        'products' => 120,
        'created' => 10,
        'updated' => 20,
        'skipped' => 90,
        'errors' => 2,
        'avg_duration' => 1500,
        'max_duration' => 2560,
        'last_run' => '2024-05-01 10:00:00',
    ]];

    $summary = $monitoring->summary(7);

    same(3, $summary['runs'], 'cuenta las ejecuciones del periodo');
    same(120, $summary['products'], 'suma los productos procesados');
    same(1.5, $summary['avg_duration'], 'convierte la media a segundos');
    same(2.56, $summary['max_duration'], 'convierte el máximo a segundos');
    truthy(
        str_contains(Db::getInstance()->queries[0], '`date_add` >='),
        'el resumen se limita al periodo'
    );

    Db::getInstance()->setQueue = [[
        [
            'id_log' => 2,
            'source_name' => 'Proveedor A',
            'duration_ms' => 1200,
            'memory_kb' => 2048,
        ],
        [
            'id_log' => 1,
            'source_name' => null,
            'duration_ms' => null,
            'memory_kb' => null,
        ],
    ]];

    $recent = $monitoring->recent(20);

    same(1.2, $recent[0]['duration'], 'convierte la duración a segundos');
    same(2.0, $recent[0]['memory'], 'convierte la memoria a megabytes');
    same(null, $recent[1]['duration'], 'deja la duración vacía sin datos');
    same(null, $recent[1]['memory'], 'deja la memoria vacía sin datos');
    truthy(
        str_contains(Db::getInstance()->queries[1], 'LIMIT 20'),
        'limita las ejecuciones devueltas'
    );

    Db::getInstance()->setQueue = [[
        [
            'details' => (string) json_encode([
                [
                    'reference' => 'A',
                    'errors' => [
                        'The reference is required.',
                        'No stock',
                    ],
                ],
                ['reference' => 'B', 'errors' => ['No stock']],
            ]),
        ],
        ['details' => 'no es json'],
        [
            'details' => (string) json_encode([
                ['reference' => 'C', 'errors' => ['No stock', '  ']],
            ]),
        ],
    ]];

    same(
        [
            'No stock' => 3,
            'The reference is required.' => 1,
        ],
        $monitoring->topErrors(30, 5),
        'agrupa los errores repetidos y descarta los vacíos'
    );

    Db::getInstance()->rowQueue = [[
        'rows_count' => 4,
        'bytes' => 2097152,
    ]];

    same(
        ['rows' => 4, 'megabytes' => 2.0],
        $monitoring->size(),
        'mide el tamaño del historial'
    );

    Db::getInstance()->affectedRows = 5;

    same(5, $monitoring->purge(30), 'devuelve las filas borradas');

    $delete = Db::getInstance()->writes[0];

    truthy(
        str_contains($delete, 'DELETE FROM `ps_cpbsync_sync_log`'),
        'borra de la tabla del historial'
    );
    truthy(
        str_contains($delete, '`date_add` <'),
        'aplica la retención por fecha'
    );

    section('MonitoringHandler');

    Db::reset();

    $monitoringShell = new FakeShell();

    $monitoringHandler = new MonitoringHandler(
        $monitoringShell,
        $stack['doubles']['links'],
        $monitoring,
        $stack['handlers']['source']
    );

    $fillMonitoringQueues = static function (): void {
        Db::getInstance()->rowQueue = [
            ['runs' => 1, 'errors' => 0],
            ['rows_count' => 1, 'bytes' => 1024],
        ];

        Db::getInstance()->setQueue = [
            [
                [
                    'id_log' => 1,
                    'source_name' => null,
                    'duration_ms' => 1000,
                    'memory_kb' => 1024,
                ],
            ],
            [],
        ];
    };

    $fillMonitoringQueues();
    Tools::set(['days' => '30']);

    same(
        'fetch:monitoring.tpl',
        $monitoringHandler->index(),
        'renderiza el panel de monitorización'
    );
    same(
        30,
        $monitoringShell->lastAssignment()['days'],
        'usa el periodo solicitado'
    );
    same(
        'Deleted source',
        $monitoringShell->lastAssignment()['logs'][0]['source_name'],
        'nombra las fuentes eliminadas'
    );
    truthy(
        str_contains(
            $monitoringShell->lastAssignment()['logs'][0]['detail_url'],
            'history_detail'
        ),
        'enlaza el detalle de cada ejecución'
    );

    $fillMonitoringQueues();
    Db::getInstance()->affectedRows = 5;
    Tools::set(['days' => '9999']);

    same(
        'fetch:monitoring.tpl',
        $monitoringHandler->purge(),
        'purga el historial y vuelve al panel'
    );
    same(
        ['Removed 5 runs older than 365 days.'],
        $monitoringShell->confirmations,
        'confirma cuántas ejecuciones se han borrado'
    );
    truthy(
        str_contains(Db::getInstance()->writes[0], 'DELETE FROM'),
        'la purga borra de verdad'
    );

    Db::reset();
    Tools::reset();
}

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
