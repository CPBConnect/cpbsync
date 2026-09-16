<?php

namespace CPBConnect\Application\Source;

use CPBConnect\Application\Source\Reader\SourceReaderRegistry;
use CPBConnect\Infrastructure\Persistence\SourceRepository;
use RuntimeException;

/**
 * Casos de uso sobre las fuentes configuradas.
 */
class SourceService
{
    public function __construct(
        private SourceRepository $repository,
        private SourceReaderRegistry $readers
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->repository->findAll();
    }

    public function find(int $sourceId): ?array
    {
        if ($sourceId <= 0) {
            return null;
        }

        return $this->repository->findById($sourceId);
    }

    /**
     * Lee el catálogo publicado por la fuente.
     *
     * @return array{headers: array, rows: array, total: int}
     */
    public function read(array $source): array
    {
        $reader = $this->readers->get(
            (string) ($source['type'] ?? '')
        );

        if ($reader === null) {
            throw new RuntimeException(
                'The source type is not supported.'
            );
        }

        return $reader->read($source);
    }

    public function create(array $data): int
    {
        return $this->repository->create($data);
    }

    public function update(int $sourceId, array $data): bool
    {
        return $this->repository->update($sourceId, $data);
    }

    public function delete(int $sourceId): bool
    {
        return $this->repository->delete($sourceId);
    }
}
