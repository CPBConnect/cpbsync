<?php

namespace CPBConnect\Application\Mapping;

use CPBConnect\Infrastructure\Persistence\MappingRepository;

/**
 * Convierte el formulario de mapping en registros persistibles.
 */
class MappingSaver
{
    public function __construct(
        private MappingRepository $repository
    ) {
    }

    public function save(
        int $sourceId,
        array $mapping,
        array $transformations,
        array $search,
        array $replace
    ): void {
        $rows = $this->buildRows(
            $mapping,
            $transformations,
            $search,
            $replace
        );

        $this->repository->replaceForSource($sourceId, $rows);
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function buildRows(
        array $mapping,
        array $transformations,
        array $search,
        array $replace
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
                'transform_config' => MappingTransformInput::buildConfig(
                    $transform,
                    $search,
                    $replace,
                    $sourceField
                ),
            ];
        }

        return $rows;
    }
}
