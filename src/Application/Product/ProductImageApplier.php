<?php

namespace CPBConnect\Application\Product;

use Product;

class ProductImageApplier
{
    private ProductImageDownloader $downloader;
    private ProductImageCreator $creator;
    private \CPBConnect\Infrastructure\Persistence\ProductMetaRepository $metaRepository;

    public function __construct()
    {
        $this->downloader = new ProductImageDownloader();
        $this->creator = new ProductImageCreator();

        $this->metaRepository =
            new \CPBConnect\Infrastructure\Persistence\ProductMetaRepository();
    }

    public function apply(Product $product, array $data): void
    {
        if (!isset($data['image'])) {
            return;
        }

        $imageUrl = trim((string) $data['image']);

        if ($imageUrl === '') {
            return;
        }

        $idProduct = (int) $product->id;

        if ($idProduct <= 0) {
            throw new \RuntimeException(
                'No se puede aplicar una imagen a un producto sin ID.'
            );
        }

        $savedImageUrl =
            $this->metaRepository->find(
                $idProduct,
                'image'
            );

        if (
            $savedImageUrl !== null &&
            $savedImageUrl === $imageUrl
        ) {
            return;
        }

        $existingImages = $product->getImages(
            (int) \Configuration::get('PS_LANG_DEFAULT')
        );

        $tmpFile = $this->downloader->download($imageUrl);

        try {

            foreach ($existingImages as $existingImage) {

                $oldImage = new \Image(
                    (int) $existingImage['id_image']
                );

                if (\Validate::isLoadedObject($oldImage)) {
                    $oldImage->delete();
                }
            }

            $newImageId = $this->creator->create(
                $idProduct,
                $tmpFile
            );

            $this->metaRepository->save(
                $idProduct,
                'image',
                $imageUrl
            );

        } finally {

            if (is_file($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }
}