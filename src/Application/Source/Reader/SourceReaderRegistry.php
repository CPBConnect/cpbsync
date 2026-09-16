<?php

namespace CPBConnect\Application\Source\Reader;

/**
 * Lectores de catálogos disponibles en esta instalación.
 *
 * El registro es el punto de extensión del módulo: la edición gratuita
 * registra el lector CSV y las ediciones de pago añaden los suyos.
 */
class SourceReaderRegistry
{
    /** @var array<string, SourceReaderInterface> */
    private array $readers = [];

    public function register(SourceReaderInterface $reader): void
    {
        $this->readers[$reader->getType()] = $reader;
    }

    public function has(string $type): bool
    {
        return isset($this->readers[$type]);
    }

    public function get(string $type): ?SourceReaderInterface
    {
        return $this->readers[$type] ?? null;
    }

    /**
     * Tipos disponibles: identificador => etiqueta.
     *
     * @return array<string, string>
     */
    public function types(): array
    {
        $types = [];

        foreach ($this->readers as $type => $reader) {
            $types[$type] = $reader->getLabel();
        }

        return $types;
    }

    /**
     * @return array<int, string>
     */
    public function allowedTypes(): array
    {
        return array_keys($this->readers);
    }

    /**
     * Extensiones admitidas en la importación manual de archivos.
     *
     * @return array<int, string>
     */
    public function fileExtensions(): array
    {
        $extensions = [];

        foreach ($this->readers as $reader) {
            $extensions = array_merge(
                $extensions,
                $reader->getFileExtensions()
            );
        }

        return array_values(array_unique($extensions));
    }
}
