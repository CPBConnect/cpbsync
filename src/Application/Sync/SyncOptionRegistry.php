<?php

namespace CPBConnect\Application\Sync;

/**
 * Opciones de sincronización que ofrece el formulario de fuentes.
 *
 * El comportamiento de cada opción vive donde se aplica (el motor de
 * sincronización y sus aplicadores); aquí sólo está lo que necesita el
 * formulario para pintarlas.
 */
class SyncOptionRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $options = [];

    /**
     * @param array<string, mixed> $description
     */
    public function register(
        string $name,
        array $description
    ): void {
        $this->options[$name] = $description;
    }

    public function has(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->options;
    }

    /**
     * Opciones listas para el formulario, en el orden en que se
     * registraron.
     *
     * @return array<int, array{name: string, label: string, hint: string, default: string, type: string}>
     */
    public function describeAll(): array
    {
        $described = [];

        foreach ($this->options as $name => $description) {
            $described[] = [
                'name' => $name,
                'label' => (string) ($description['label'] ?? ''),
                'hint' => (string) ($description['hint'] ?? ''),
                'type' => 'checkbox',
                'default' => '',
            ];
        }

        return $described;
    }
}
