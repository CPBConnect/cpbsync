<?php

namespace CPBConnect\Application\Product;

use Image;

/**
 * Borra una imagen de producto.
 *
 * `Image::delete()` consulta el contenedor de Symfony (para el watermark)
 * y fuera del back office —cron, consola— ese contenedor no existe: el
 * borrado se queda a medias, con las miniaturas en disco y una excepción
 * que aborta la sincronización. Cuando la vía de PrestaShop falla se
 * borran las filas y los archivos aquí, que es exactamente lo que hace.
 */
class ProductImageRemover
{
    public function remove(int $idImage): void
    {
        if ($idImage <= 0) {
            return;
        }

        $image = new Image($idImage);

        if (!\Validate::isLoadedObject($image)) {
            return;
        }

        try {
            $image->delete();

            return;

        } catch (\Throwable $e) {
            $this->removeManually($image);
        }
    }

    /**
     * @param array<int, int> $imageIds
     */
    public function removeAll(array $imageIds): void
    {
        foreach ($imageIds as $idImage) {
            $this->remove((int) $idImage);
        }
    }

    private function removeManually(Image $image): void
    {
        $idImage = (int) $image->id;

        $idProduct = (int) $image->id_product;

        $path = $image->getExistingImgPath();

        $this->removeRows($idImage);

        if (is_string($path) && $path !== '') {
            $this->removeFiles($path, $idProduct);
        }

        $this->renumberPositions($idProduct);
    }

    /**
     * Las filas pueden estar ya borradas: el fallo de PrestaShop ocurre
     * después de borrarlas.
     */
    private function removeRows(int $idImage): void
    {
        $tables = [
            'image',
            'image_lang',
            'image_shop',
            'product_attribute_image',
        ];

        foreach ($tables as $table) {
            \Db::getInstance()->execute(
                'DELETE FROM `' . _DB_PREFIX_ . $table . '`'
                . ' WHERE `id_image` = ' . $idImage
            );
        }
    }

    /**
     * La imagen y sus miniaturas comparten prefijo, así que se borran
     * por prefijo: la carpeta de cada imagen es única (una carpeta por
     * dígito del identificador).
     */
    private function removeFiles(string $path, int $idProduct): void
    {
        $base = _PS_PRODUCT_IMG_DIR_ . $path;

        foreach ((array) glob($base . '*') as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        foreach (['product_', 'product_mini_'] as $prefix) {
            $tmp = _PS_TMP_IMG_DIR_ . $prefix . $idProduct . '.jpg';

            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    private function renumberPositions(int $idProduct): void
    {
        if ($idProduct <= 0) {
            return;
        }

        \Db::getInstance()->execute('SET @position := 0');

        \Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'image`'
            . ' SET position = (@position := @position + 1)'
            . ' WHERE id_product = ' . $idProduct
            . ' ORDER BY position ASC'
        );
    }
}
