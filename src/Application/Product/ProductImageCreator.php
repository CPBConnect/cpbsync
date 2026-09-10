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
                'El producto no existe en PrestaShop.'
            );
        }

        if (!is_file($tmpFile)) {
            throw new \RuntimeException(
                'El archivo de imagen no existe.'
            );
        }

        $image = new Image();

        $image->id_product = $idProduct;
        $image->position =
            Image::getHighestPosition($idProduct) + 1;

        $image->cover = 1;

        if (!$image->add()) {
            throw new \RuntimeException(
                'No fue posible crear la imagen del producto.'
            );
        }

        $idShop = (int) \Context::getContext()->shop->id;

        \Db::getInstance()->update(
            'image_shop',
            [
                'cover' => 1,
            ],
            'id_image = ' . (int) $image->id .
            ' AND id_shop = ' . $idShop
        );

        $imagePath = $image->getPathForCreation();

        if (!\ImageManager::resize(
            $tmpFile,
            $imagePath . '.jpg'
        )) {
            $image->delete();

            throw new \RuntimeException(
                'No fue posible guardar la imagen del producto.'
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
                $sourceHeight,
                $imageType['image_fitment']
            );

            if (!$result) {
                $image->delete();

                throw new \RuntimeException(
                    'No fue posible generar el thumbnail "' .
                    $imageType['name'] .
                    '".'
                );
            }
        }

        return (int) $image->id;
    }
}