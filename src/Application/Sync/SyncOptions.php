<?php

namespace CPBConnect\Application\Sync;

use CPBConnect\Application\Form\DescribedFields;

/**
 * Decisiones de sincronización de una fuente.
 *
 * No dependen del mapeo: si se pueden actualizar productos que ya
 * existen, si sólo se rellenan los campos vacíos, si se toca el stock o
 * si se importan imágenes. Cada opción se guarda como un valor booleano
 * y todas están apagadas por defecto, que es el comportamiento de
 * siempre.
 */
final class SyncOptions
{
    /**
     * Sólo crear productos nuevos: los que ya existen no se tocan.
     */
    public const CREATE_ONLY = 'create_only';

    /**
     * Rellenar sólo los campos vacíos, sin pisar lo que ya hay.
     */
    public const FILL_EMPTY = 'fill_empty';

    /**
     * No sincronizar el stock (lo lleva la tienda).
     */
    public const SKIP_STOCK = 'skip_stock';

    /**
     * No importar imágenes.
     */
    public const SKIP_IMAGES = 'skip_images';

    /** @var array<string, mixed> */
    private array $values;

    /**
     * @param array<string, mixed> $values
     */
    private function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }

    /**
     * @param mixed $json
     */
    public static function fromJson($json): self
    {
        return new self(DescribedFields::decode($json));
    }

    /**
     * Opciones de una fuente.
     *
     * @param array<string, mixed> $source
     */
    public static function fromSource(array $source): self
    {
        return self::fromJson($source['options'] ?? null);
    }

    /**
     * Opciones sin nada activado.
     */
    public static function none(): self
    {
        return new self([]);
    }

    public function isEnabled(string $name): bool
    {
        return !empty($this->values[$name]);
    }

    public function onlyCreate(): bool
    {
        return $this->isEnabled(self::CREATE_ONLY);
    }

    public function fillEmpty(): bool
    {
        return $this->isEnabled(self::FILL_EMPTY);
    }

    public function skipsStock(): bool
    {
        return $this->isEnabled(self::SKIP_STOCK);
    }

    public function skipsImages(): bool
    {
        return $this->isEnabled(self::SKIP_IMAGES);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->values;
    }
}
