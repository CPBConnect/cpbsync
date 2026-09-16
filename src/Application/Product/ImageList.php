<?php

namespace CPBConnect\Application\Product;

/**
 * Lista de imágenes de un producto.
 *
 * El campo de imagen de un catálogo puede traer varias URLs: separadas
 * por comas, por punto y coma, por barras verticales o una por línea.
 * Además una sola URL puede llevar comas en sus parámetros, así que
 * primero se comprueba si el valor entero ya es una URL.
 */
final class ImageList
{
    /**
     * Tope de imágenes por producto, para que un catálogo con una lista
     * enorme no llene la tienda de miniaturas.
     */
    public const MAX_IMAGES = 20;

    private function __construct()
    {
    }

    /**
     * @return array<int, string>
     */
    public static function parse(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return [];
        }

        $urls = [];

        foreach (preg_split('/[\r\n;|,]+/', $value) ?: [] as $part) {
            $part = trim($part);

            if ($part === '' || !self::isUrl($part)) {
                continue;
            }

            if (!in_array($part, $urls, true)) {
                $urls[] = $part;
            }

            if (count($urls) >= self::MAX_IMAGES) {
                break;
            }
        }

        /*
         * Una sola URL puede llevar comas en sus parámetros: si al
         * trocear no sale más de una URL y el valor entero lo es, se
         * toma el valor entero.
         */
        if (count($urls) <= 1 && self::isUrl($value)) {
            return [$value];
        }

        return $urls;
    }

    /**
     * Comprobación ligera: la validación de verdad (incluido el bloqueo
     * de direcciones privadas) la hace el descargador.
     */
    private static function isUrl(string $value): bool
    {
        return preg_match('#^https?://\S+$#i', $value) === 1;
    }
}
