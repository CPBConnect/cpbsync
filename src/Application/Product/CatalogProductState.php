<?php

namespace CPBConnect\Application\Product;

use CPBConnect\Infrastructure\Persistence\ProductMetaRepository;
use Product;
use RuntimeException;
use Validate;

/**
 * Comportamiento por defecto: consulta producto a producto.
 *
 * Cada producto existente se carga entero y se compara campo a campo,
 * que es exactamente lo que hacía el módulo antes de existir esta
 * abstracción.
 */
class CatalogProductState implements ProductStateInterface
{
    public function __construct(
        private ?ProductFinder $finder = null,
        private ?ProductChangeDetector $changeDetector = null
    ) {
        $this->finder = $finder ?? new ProductFinder();
        $this->changeDetector = $changeDetector
            ?? new ProductChangeDetector();
    }

    public function prepare(array $products): void
    {
        // Nada que precargar.
    }

    public function findExistingId(string $reference): ?int
    {
        return $this->finder->findByReference($reference);
    }

    public function hasChanges(int $idProduct, array $product): bool
    {
        $existing = new Product($idProduct);

        if (!Validate::isLoadedObject($existing)) {
            throw new RuntimeException(
                'The existing product could not be loaded.'
            );
        }

        return $this->changeDetector->hasChanges($existing, $product);
    }

    public function remember(array $product, int $idProduct): void
    {
        // Sin huella que guardar.
    }
}
