<?php

namespace CPBConnect\Application\Product;

use Image;
use Product;

class ProductImageCreator
{
    public function create(int $idProduct, string $tmpFile): int
    {
        $product = new Product($idProduct);

        if (!\Validate::isLoadedObject($product)) {
            throw new \RuntimeException(
                'The product does not exist in PrestaShop.'
            );
        }

        if (!is_file($tmpFile)) {
            throw new \RuntimeException(
                'The image file does not exist.'
            );
        }

        $image = new Image();

        $image->id_product = $idProduct;
        $image->position =
            Image::getHighestPosition($idProduct) + 1;

        /*
         * Sólo la primera imagen del producto es la portada: PrestaShop
         * guarda una fila por imagen y tienda en image_shop, con un
         * índice único sobre (producto, tienda, portada), y las que no
         * son portada van con NULL.
         */
        $isCover = !Image::getCover($idProduct);

        $image->cover = $isCover ? true : null;

        if (!$image->add()) {
            throw new \RuntimeException(
                'The product image could not be created.'
            );
        }

        $idShop = (int) \Context::getContext()->shop->id;

        if ($isCover) {
            \Db::getInstance()->update(
                'image_shop',
                [
                    'cover' => 1,
                ],
                'id_image = ' . (int) $image->id .
                ' AND id_shop = ' . $idShop
            );
        }

        $imagePath = $image->getPathForCreation();

        if (!\ImageManager::resize(
            $tmpFile,
            $imagePath . '.jpg'
        )) {
            $image->delete();

            throw new \RuntimeException(
                'The product image could not be saved.'
            );
        }

        $imageTypes = \ImageType::getImagesTypes(
            'products',
            true
        );

        foreach ($imageTypes as $imageType) {
            $destination = $imagePath .
                           '-' .
                           stripslashes($imageType['name']) .
                           '.jpg';

            $result = \ImageManager::resize(
                $imagePath . '.jpg',
                $destination,
                (int) $imageType['width'],
                (int) $imageType['height'],
                'jpg',
                false,
                $error,
                $targetWidth,
                $targetHeight,
                5,
                $sourceWidth,
                $sourceHeight
            );

            if (!$result) {
                $image->delete();

                /*
                 * El mensaje no incluye el tipo de imagen: se traduce
                 * por el catálogo y con un valor variable no habría
                 * forma de encontrarlo.
                 */
                throw new \RuntimeException(
                    'The thumbnail could not be generated.'
                );
            }
        }

        return (int) $image->id;
    }
}