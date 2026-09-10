<?php

namespace CPBConnect\Application\Mapping;

class MappingConfigurationBuilder
{
    public function build(array $savedMappings): array
    {
        $mapping = [];

        foreach ($savedMappings as $savedMapping) {
            $mapping[$savedMapping['source_field']] = [
                'target' => $savedMapping['target_field'],
                'transform' => $savedMapping['transform'] ?? 'none',
                'config' => !empty($savedMapping['transform_config'])
                    ? json_decode(
                        $savedMapping['transform_config'],
                        true
                    )
                    : [],
            ];
        }

        return $mapping;
    }
}