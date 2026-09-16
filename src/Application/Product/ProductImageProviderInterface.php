<?php

namespace CPBConnect\Application\Product;

/**
 * Entrega los archivos locales de las imágenes de un lote.
 *
 * El motor de sincronización pide aquí las imágenes en lugar de
 * descargarlas él mismo: así una edición puede descargarlas por
 * adelantado y en paralelo sin tocar el motor.
 */
interface ProductImageProviderInterface
{
    /**
     * Prepara las imágenes que va a necesitar el lote.
     *
     * @param array<int, array<string, mixed>> $products
     */
    public function prefetch(array $products): void;

    /**
     * Ruta local de la imagen.
     *
     * El archivo pertenece al proveedor: quien lo consume no debe
     * borrarlo.
     */
    public function localFile(string $imageUrl): string;

    /**
     * Libera los archivos temporales preparados.
     */
    public function cleanup(): void;
}
