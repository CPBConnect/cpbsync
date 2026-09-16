<?php

namespace CPBConnect\Application\Transform;

/**
 * Transformaciones disponibles en la edición instalada.
 */
class TransformerRegistry
{
    /** @var array<string, TransformerInterface> */
    private array $transformers = [];

    public function register(TransformerInterface $transformer): void
    {
        $this->transformers[$transformer->getName()] = $transformer;
    }

    public function has(string $name): bool
    {
        return isset($this->transformers[$name]);
    }

    public function find(string $name): ?TransformerInterface
    {
        return $this->transformers[$name] ?? null;
    }

    /**
     * Transformaciones registradas, sin la ausencia de transformación.
     *
     * @return array<string, TransformerInterface>
     */
    public function all(): array
    {
        return $this->transformers;
    }
}
