<?php

namespace CPBConnect\Application\Mapping;

use CPBConnect\Application\Transform\TransformerFactory;
use CPBConnect\Application\Transform\TransformerRegistry;
use CPBConnect\Infrastructure\Persistence\MappingRepository;

/**
 * Convierte el formulario de mapping en registros persistibles.
 */
class MappingSaver
{
    private TransformerRegistry $transformers;

    public function __construct(
        private MappingRepository $repository,
        ?TransformerRegistry $transformers = null
    ) {
        $this->transformers = $transformers
            ?? TransformerFactory::create();
    }

    /**
     * @param array<string, mixed> $mapping
     * @param array<string, mixed> $transformations
     * @param array<string, mixed> $config campo de origen => configuración
     */
    public function save(
        int $sourceId,
        array $mapping,
        array $transformations,
        array $config
    ): void {
        $rows = $this->buildRows(
            $mapping,
            $transformations,
            $config
        );

        $this->repository->replaceForSource($sourceId, $rows);
    }

    /**
     * @param array<string, mixed> $mapping
     * @param array<string, mixed> $transformations
     * @param array<string, mixed> $config
     *
     * @return array<int, array<string, string|null>>
     */
    private function buildRows(
        array $mapping,
        array $transformations,
        array $config
    ): array {
        $rows = [];

        foreach ($mapping as $sourceField => $targetField) {

            if ($targetField === '') {
                continue;
            }

            $transform = MappingTransformInput::resolve(
                $transformations,
                $sourceField
            );

            $rows[] = [
                'source_field' => (string) $sourceField,
                'target_field' => (string) $targetField,
                'transform' => $transform,
                'transform_config' => $this->buildConfig(
                    $transform,
                    $config[$sourceField] ?? []
                ),
            ];
        }

        return $rows;
    }

    /**
     * @param mixed $posted
     */
    private function buildConfig(
        string $transform,
        $posted
    ): ?string {
        $transformer = $this->transformers->find($transform);

        if ($transformer === null || !is_array($posted)) {
            return null;
        }

        return MappingTransformInput::encodeConfig(
            MappingTransformInput::collectConfig($transformer, $posted)
        );
    }
}
