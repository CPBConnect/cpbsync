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

            $this->shell->assign([
                'source' => $source,
                'headers' => $result['headers'],
                'saved_mappings' => $mappings,
                'saved_transformations' => $transformations,
                'saved_transformation_configs' => $configs,
                'saved_config_json' => $this->encodeConfigs($configs),
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
