<?php

namespace CPBConnect\Presentation\Admin\Handler;

use CPBConnect\Application\Mapping\MappingInputValidator;
use CPBConnect\Application\Mapping\MappingSaver;
use CPBConnect\Application\Source\SourceService;
use CPBConnect\Infrastructure\Persistence\MappingRepository;
use CPBConnect\Presentation\Admin\AdminLinkBuilder;
use CPBConnect\Presentation\Admin\AdminShellInterface;
use Tools;

/**
 * Acciones de administración sobre el mapping de una fuente.
 */
class MappingHandler
{
    public function __construct(
        private AdminShellInterface $shell,
        private AdminLinkBuilder $links,
        private SourceService $sources,
        private MappingRepository $mappingRepository,
        private MappingInputValidator $validator,
        private MappingSaver $saver,
        private SourceHandler $sourcesHandler
    ) {
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
        $search = Tools::getValue('transformation_search', []);
        $replace = Tools::getValue('transformation_replace', []);

        if (!is_array($mapping)) {
            $this->shell->addError(
                'The submitted mapping is not valid.'
            );

            return $this->show();
        }

        if (!is_array($transformations)) {
            $transformations = [];
        }

        if (!is_array($search)) {
            $search = [];
        }

        if (!is_array($replace)) {
            $replace = [];
        }

        if ($sourceId <= 0) {
            return $this->sourcesHandler->index();
        }

        $error = $this->validator->validate(
            $mapping,
            $transformations,
            $search
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
                $search,
                $replace
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
}
