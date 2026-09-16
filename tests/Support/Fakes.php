<?php

/**
 * Dobles de prueba: repositorios y colaboradores en memoria.
 *
 * Extienden las clases reales (sin llamar a su constructor) para
 * poder usar los servicios de verdad en las pruebas.
 */

use CPBConnect\Application\Import\ImportBatchProcessor;
use CPBConnect\Application\Product\ProductDryRun;
use CPBConnect\Application\Product\ProductMapper;
use CPBConnect\Application\Product\ProductSync;
use CPBConnect\Application\Product\CatalogProductState;
use CPBConnect\Application\Source\Reader\AbstractSourceReader;
use CPBConnect\Infrastructure\Persistence\ImportRepository;
use CPBConnect\Infrastructure\Persistence\MappingRepository;
use CPBConnect\Infrastructure\Persistence\ProductMetaRepository;
use CPBConnect\Infrastructure\Persistence\ProductStateRepository;
use CPBConnect\Infrastructure\Persistence\SourceRepository;
use CPBConnect\Infrastructure\Persistence\SyncLogRepository;
use CPBConnect\Infrastructure\Source\HttpSourceReader;
use CPBConnect\Application\Import\ImportFileStorage;
use CPBConnect\Presentation\Admin\AdminShellInterface;

class FakeShell implements AdminShellInterface
{
    public array $assigned = [];
    public array $errors = [];
    public array $confirmations = [];
    public array $rendered = [];
    public array $emitted = [];

    /**
     * Catálogo de traducción simulado: [texto fuente => traducción].
     */
    public array $catalogue = [];

    public function assign(array $data): void
    {
        $this->assigned[] = $data;
    }

    public function fetch(string $template): string
    {
        $this->rendered[] = $template;

        return 'fetch:' . $template;
    }

    public function display(string $template): string
    {
        $this->rendered[] = $template;

        return 'display:' . $template;
    }

    public function translate(
        string $message,
        array $parameters = []
    ): string {
        $translated = $this->catalogue[$message] ?? $message;

        return $parameters === []
            ? $translated
            : strtr($translated, $parameters);
    }

    public function addError(
        string $message,
        array $parameters = []
    ): void {
        $this->errors[] = $this->translate($message, $parameters);
    }

    public function addConfirmation(
        string $message,
        array $parameters = []
    ): void {
        $this->confirmations[] = $this->translate(
            $message,
            $parameters
        );
    }

    public function translateMessages(array $messages): array
    {
        $translated = [];

        foreach ($messages as $message) {
            $translated[] = $this->translate((string) $message);
        }

        return $translated;
    }

    public function json(array $data): string
    {
        return (string) json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
        );
    }

    public function emitJson(array $data): void
    {
        $this->emitted[] = $data;
    }

    public function lastAssignment(): array
    {
        if ($this->assigned === []) {
            return [];
        }

        return $this->assigned[count($this->assigned) - 1];
    }

    public function lastTemplate(): ?string
    {
        if ($this->rendered === []) {
            return null;
        }

        return $this->rendered[count($this->rendered) - 1];
    }
}

class FakeSourceRepository extends SourceRepository
{
    /** @var array<int, array<string, mixed>> */
    public array $sources = [];

    public array $created = [];
    public array $updated = [];
    public array $deleted = [];

    public function __construct()
    {
    }

    public function create(array $data): int
    {
        $this->created[] = $data;

        return 10;
    }

    public function findAll(): array
    {
        return array_values($this->sources);
    }

    public function findById(int $id): ?array
    {
        return $this->sources[$id] ?? null;
    }

    public function update(int $id, array $data): bool
    {
        $this->updated[] = [$id, $data];

        return true;
    }

    public function delete(int $id): bool
    {
        $this->deleted[] = $id;

        return true;
    }
}

class FakeSourceReader extends AbstractSourceReader
{
    /** @var array<int, array<string, mixed>> */
    public array $readSources = [];

    public array $result = [
        'headers' => [],
        'rows' => [],
        'total' => 0,
    ];

    public ?Throwable $error = null;

    public int $fileRows = 0;

    public function __construct(
        private string $type = 'csv'
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): string
    {
        return strtoupper($this->type);
    }

    public function getFileExtensions(): array
    {
        return [$this->type];
    }

    public function read(array $source): array
    {
        $this->readSources[] = $source;

        if ($this->error !== null) {
            throw $this->error;
        }

        return $this->result;
    }

    public function countFileRows(string $path): int
    {
        return $this->fileRows;
    }
}

class FakeMappingRepository extends MappingRepository
{
    public array $mappings = [];
    public array $replaced = [];

    public function __construct()
    {
    }

    public function findBySourceId(int $sourceId): array
    {
        return $this->mappings;
    }

    public function replaceForSource(
        int $sourceId,
        array $mappings
    ): void {
        $this->replaced[] = [$sourceId, $mappings];
    }
}

class FakeSyncLogRepository extends SyncLogRepository
{
    /** @var array<int, array<string, mixed>> */
    public array $logs = [];

    public array $created = [];
    public int $nextId = 1;

    public function __construct()
    {
    }

    public function create(
        int $sourceId,
        array $result,
        string $executionType = 'manual'
    ): int {
        $this->created[] = [
            'id_source' => $sourceId,
            'result' => $result,
            'execution_type' => $executionType,
        ];

        return $this->nextId++;
    }

    public function findById(int $id): ?array
    {
        return $this->logs[$id] ?? null;
    }

    public function findAll(int $limit = 50): array
    {
        return array_slice(array_values($this->logs), 0, $limit);
    }
}

class FakeImportRepository extends ImportRepository
{
    public array $created = [];

    public function __construct()
    {
    }

    public function create(
        int $sourceId,
        int $total,
        ?string $filePath = null,
        string $executionType = 'manual'
    ): int {
        $this->created[] = [
            'id_source' => $sourceId,
            'total' => $total,
            'file_path' => $filePath,
            'execution_type' => $executionType,
        ];

        return 7;
    }
}

class FakeImportFileStorage extends ImportFileStorage
{
    public function __construct()
    {
    }

    public function store(array $file): string
    {
        return '/tmp/cpbsync-import.csv';
    }
}

class FakeImportBatchProcessor extends ImportBatchProcessor
{
    public array $result = [
        'id_import' => 7,
        'status' => 'processing',
        'total' => 10,
        'processed' => 5,
        'success' => 4,
        'errors' => 1,
    ];

    public ?Throwable $error = null;

    public function __construct()
    {
    }

    public function process(int $importId): array
    {
        if ($this->error !== null) {
            throw $this->error;
        }

        return $this->result;
    }
}

class FakeProductMapper extends ProductMapper
{
    public array $mapped = [];

    public function __construct()
    {
    }

    public function map(array $rows, array $mapping): array
    {
        $this->mapped[] = [$rows, $mapping];

        return $rows;
    }
}

class FakeProductDryRun extends ProductDryRun
{
    public array $calls = [];

    public array $result = [['number' => 1]];

    public function __construct()
    {
    }

    public function run(
        array $rows,
        array $mapping,
        int $limit = 5
    ): array {
        $this->calls[] = [$rows, $mapping, $limit];

        return $this->result;
    }
}

class FakeProductSync extends ProductSync
{
    public array $synced = [];

    public array $result = [
        'total' => 1,
        'created' => 1,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'items' => [],
    ];

    public function __construct()
    {
    }

    public function sync(array $products): array
    {
        $this->synced[] = $products;

        return $this->result;
    }
}

class FakeHttpReader extends HttpSourceReader
{
    /** @var array<int, array<string, mixed>> */
    public array $requests = [];

    /** @var array<int, string> */
    public array $responses = [];

    public ?Throwable $error = null;

    public function __construct()
    {
    }

    public function request(
        string $url,
        string $method = 'GET',
        array $headers = [],
        array $parameters = [],
        ?string $body = null
    ): string {
        $this->requests[] = [
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'parameters' => $parameters,
            'body' => $body,
        ];

        if ($this->error !== null) {
            throw $this->error;
        }

        return array_shift($this->responses) ?? '{}';
    }
}

class FakeProductStateRepository extends ProductStateRepository
{
    /** @var array<string, int> */
    public array $ids = [];

    /** @var array<int, string> */
    public array $values = [];

    public array $calls = [];

    public function __construct()
    {
    }

    public function findIdsByReferences(array $references): array
    {
        $this->calls[] = ['ids', $references];

        return array_intersect_key(
            $this->ids,
            array_flip($references)
        );
    }

    public function findValuesByField(array $ids, string $field): array
    {
        $this->calls[] = ['values', $ids, $field];

        return array_intersect_key(
            $this->values,
            array_flip($ids)
        );
    }
}

class FakeProductMetaRepository extends ProductMetaRepository
{
    public array $saved = [];

    public function __construct()
    {
    }

    public function save(
        int $idProduct,
        string $field,
        string $sourceValue
    ): bool {
        $this->saved[] = [$idProduct, $field, $sourceValue];

        return true;
    }

    public function find(int $idProduct, string $field): ?string
    {
        foreach (array_reverse($this->saved) as $entry) {
            if ($entry[0] === $idProduct && $entry[1] === $field) {
                return $entry[2];
            }
        }

        return null;
    }
}

class FakeCatalogProductState extends CatalogProductState
{
    /** @var array<int, bool> */
    public array $changes = [];

    public array $asked = [];

    public function __construct()
    {
    }

    public function prepare(array $products): void
    {
    }

    public function findExistingId(string $reference): ?int
    {
        return null;
    }

    public function hasChanges(int $idProduct, array $product): bool
    {
        $this->asked[] = $idProduct;

        return $this->changes[$idProduct] ?? true;
    }

    public function remember(array $product, int $idProduct): void
    {
    }
}
