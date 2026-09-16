<?php

namespace CPBConnect\Presentation\Admin\Handler;

use CPBConnect\Application\Mapping\MappingInputValidator;
use CPBConnect\Application\Mapping\MappingSaver;
use CPBConnect\Application\Source\SourceService;
use CPBConnect\Application\Transform\TransformerFactory;
use CPBConnect\Application\Transform\TransformerRegistry;
use CPBConnect\Infrastructure\Persistence\MappingRepository;
use CPBConnect\Presentation\Admin\AdminLinkBuilder;
use CPBConnect\Presentation\Admin\AdminShellInterface;
use Tools;

/**
 * Acciones de administración sobre el mapping de una fuente.
 */
class MappingHandler
{
    /**
     * Campo destino que se propone para los nombres más habituales.
     *
     * Los nombres del catálogo vienen en el idioma del proveedor, así
     * que se aceptan los equivalentes en inglés y en español.
     */
    private const SUGGESTIONS = [
        'sku' => 'reference',
        'reference' => 'reference',
        'referencia' => 'reference',
        'ref' => 'reference',
        'name' => 'name',
        'nombre' => 'name',
        'title' => 'name',
        'titulo' => 'name',
        'description' => 'description',
        'descripcion' => 'description',
        'price' => 'price',
        'precio' => 'price',
        'precios' => 'price',
        'prices' => 'price',
        'stock' => 'quantity',
        'quantity' => 'quantity',
        'cantidad' => 'quantity',
        'existencias' => 'quantity',
        'category' => 'category',
        'categoria' => 'category',
        'categories' => 'category',
        'categorias' => 'category',
        'brand' => 'manufacturer',
        'brands' => 'manufacturer',
        'marca' => 'manufacturer',
        'fabricante' => 'manufacturer',
        'manufacturer' => 'manufacturer',
        'image' => 'image',
        'images' => 'image',
        'imagen' => 'image',
        'imagenes' => 'image',
        'ean' => 'ean13',
        'ean13' => 'ean13',
        'barcode' => 'ean13',
    ];

    private TransformerRegistry $transformers;

    public function __construct(
        private AdminShellInterface $shell,
        private AdminLinkBuilder $links,
        private SourceService $sources,
        private MappingRepository $mappingRepository,
        private MappingInputValidator $validator,
        private MappingSaver $saver,
        private SourceHandler $sourcesHandler,
        ?TransformerRegistry $transformers = null
    ) {
        $this->transformers = $transformers
            ?? TransformerFactory::create();
    }

    /**
     * Formulario de mapping de una fuente.
     */
    public function show(): string
    {
        $sourceId = (int) Tools::getValue('id_source');

        try {
            $source = $this->sources->find($sourceId);

            if ($source === null) {
                return $this->sourcesHandler->index();
            }

            $result = $this->sources->read($source);

            [$mappings, $transformations, $configs] =
                $this->buildFormData(
                    $this->mappingRepository->findBySourceId(
                        (int) $source['id_source']
                    )
                );

            $headers = $result['headers'];

            $this->shell->assign([
                'source' => $source,
                'headers' => $headers,
                'saved_mappings' => $mappings,
                'saved_transformations' => $transformations,
                'saved_transformation_configs' => $configs,
                'saved_config_json' => $this->encodeConfigs($configs),
                'selected_targets' => $this->selectedTargets(
                    $headers,
                    $mappings
                ),
                'transform_options' => $this->transformOptions(),
                'save_mapping_url' => $this->links->saveMapping(
                    (int) $source['id_source']
                ),
                'back_url' => $this->links->home(),
                'dry_run_url' => $this->links->dryRun(
                    (int) $source['id_source']
                ),
                'sync_url' => $this->links->sync(
                    (int) $source['id_source']
                ),
            ]);

            return $this->shell->fetch('source-mapping.tpl');

        } catch (\Throwable $e) {
            $this->shell->addError(
                'The source could not be loaded: %error%',
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
     * Guarda el mapping enviado desde el formulario.
     */
    public function save(): string
    {
        $sourceId = (int) Tools::getValue('id_source');

        $mapping = Tools::getValue('mapping', []);
        $transformations = Tools::getValue('transformation', []);
        $config = Tools::getValue('transformation_config', []);

        if (!is_array($mapping)) {
            $this->shell->addError(
                'The submitted mapping is not valid.'
            );

            return $this->show();
        }

        if (!is_array($transformations)) {
            $transformations = [];
        }

        if (!is_array($config)) {
            $config = [];
        }

        if ($sourceId <= 0) {
            return $this->sourcesHandler->index();
        }

        $error = $this->validator->validate(
            $mapping,
            $transformations,
            $config
        );

        if ($error !== null) {
            $this->shell->addError(
                $error->getMessage(),
                $error->getParameters()
            );

            return $this->show();
        }

        try {
            $this->saver->save(
                $sourceId,
                $mapping,
                $transformations,
                $config
            );

            $this->shell->addConfirmation(
                'The mapping was saved successfully.'
            );

        } catch (\Throwable $e) {
            $this->shell->addError(
                'The mapping could not be saved: %error%',
                [
                    '%error%' => $this->shell->translate(
                        $e->getMessage()
                    ),
                ]
            );
        }

        return $this->show();
    }

    /**
     * @return array{0: array, 1: array, 2: array}
     */
    private function buildFormData(array $savedMappings): array
    {
        $mappings = [];
        $transformations = [];
        $configs = [];

        foreach ($savedMappings as $savedMapping) {
            $sourceField = $savedMapping['source_field'];

            $mappings[$sourceField] =
                $savedMapping['target_field'];

            $transformations[$sourceField] =
                $savedMapping['transform'] ?? 'none';

            $configs[$sourceField] =
                !empty($savedMapping['transform_config'])
                    ? json_decode(
                        $savedMapping['transform_config'],
                        true
                    )
                    : [];
        }

        return [$mappings, $transformations, $configs];
    }

    /**
     * Configuración guardada, en JSON, para que el formulario pueda
     * reponerla sin recargar la página.
     *
     * @param array<string, mixed> $configs
     *
     * @return array<string, string>
     */
    private function encodeConfigs(array $configs): array
    {
        $encoded = [];

        foreach ($configs as $sourceField => $config) {
            $json = json_encode(
                is_array($config) ? $config : [],
                JSON_UNESCAPED_UNICODE
            );

            $encoded[$sourceField] = $json === false ? '{}' : $json;
        }

        return $encoded;
    }

    /**
     * Campos destino que el formulario deja elegidos.
     *
     * Si la fuente todavía no tiene mapeo guardado se propone el campo
     * que suele corresponder a cada nombre del catálogo; en cuanto hay
     * un mapeo guardado no se propone nada más, para que al guardar no
     * aparezcan mapeos que el usuario no ha elegido.
     *
     * @param array<int, string>   $headers
     * @param array<string, mixed> $saved
     *
     * @return array<string, string>
     */
    private function selectedTargets(
        array $headers,
        array $saved
    ): array {
        $selected = [];

        foreach ($headers as $header) {
            if (isset($saved[$header])) {
                $selected[$header] = (string) $saved[$header];

                continue;
            }

            $selected[$header] = $saved === []
                ? $this->suggestTarget((string) $header)
                : '';
        }

        return $selected;
    }

    /**
     * Campo destino que suele corresponder a un nombre del catálogo.
     *
     * Con columnas anidadas (`price.value`, `images.0`) se prueba el
     * nombre completo, el primer segmento y el último.
     */
    private function suggestTarget(string $header): string
    {
        $normalized = strtolower($header);

        $segments = explode('.', $normalized);

        $candidates = [
            $normalized,
            $segments[0],
            (string) end($segments),
        ];

        foreach ($candidates as $candidate) {
            $candidate = ltrim(trim($candidate), '@');

            if (isset(self::SUGGESTIONS[$candidate])) {
                return self::SUGGESTIONS[$candidate];
            }
        }

        return '';
    }

    /**
     * Transformaciones disponibles para el formulario.
     *
     * Las etiquetas vienen de cada transformación en inglés; aquí se
     * traducen y se preparan los campos de configuración.
     *
     * @return array<int, array<string, mixed>>
     */
    private function transformOptions(): array
    {
        $options = [];

        foreach ($this->transformers->all() as $transformer) {
            $description = $transformer->describe();

            $options[] = [
                'name' => $transformer->getName(),
                'label' => $this->shell->translate(
                    (string) $description['label']
                ),
                'targets' => implode(
                    ',',
                    array_map('strval', $description['targets'])
                ),
                'fields' => $this->transformFields(
                    $description['fields']
                ),
            ];
        }

        return $options;
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     *
     * @return array<int, array<string, mixed>>
     */
    private function transformFields(array $fields): array
    {
        $prepared = [];

        foreach ($fields as $field) {
            $prepared[] = [
                'name' => (string) ($field['name'] ?? ''),
                'label' => $this->shell->translate(
                    (string) ($field['label'] ?? '')
                ),
                'hint' => $this->shell->translate(
                    (string) ($field['hint'] ?? '')
                ),
                'type' => (string) ($field['type'] ?? 'text'),
                'default' => (string) ($field['default'] ?? ''),
                'options' => $this->transformFieldOptions(
                    $field['options'] ?? []
                ),
            ];
        }

        return $prepared;
    }

    /**
     * @param mixed $options
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function transformFieldOptions($options): array
    {
        if (!is_array($options)) {
            return [];
        }

        $prepared = [];

        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }

            $prepared[] = [
                'value' => (string) ($option['value'] ?? ''),
                'label' => $this->shell->translate(
                    (string) ($option['label'] ?? '')
                ),
            ];
        }

        return $prepared;
    }
}
