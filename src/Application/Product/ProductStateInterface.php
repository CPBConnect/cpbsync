<?php

namespace CPBConnect\Application\Product;

/**
 * Estado de los productos que ya existen en el catálogo.
 *
 * El motor de sincronización pregunta aquí qué productos existen, si
 * han cambiado y qué recordar tras escribirlos. Así la edición de pago
 * puede responder con precargas en bloque sin tocar el motor.
 */
interface ProductStateInterface
{
    /**
     * Prepara el estado del lote completo antes de recorrerlo.
     *
     * @param array<int, array<string, mixed>> $products
     */
    public function prepare(array $products): void;

    /**
     * Identificador del producto que ya existe con esa referencia.
     */
    public function findExistingId(string $reference): ?int;

    /**
     * ¿El producto existente difiere de los datos recibidos?
     *
     * @param array<string, mixed> $product
     */
    public function hasChanges(int $idProduct, array $product): bool;

    /**
     * Guarda lo necesario para reconocer el producto en la próxima
     * sincronización.
     *
     * @param array<string, mixed> $product
     */
    public function remember(array $product, int $idProduct): void;
}
