<?php

namespace CPBConnect\Application\Product;

use CPBConnect\Application\Sync\SyncOptions;
use Product;

class ProductImageApplier
{
    private ProductImageCreator $creator;
    private ProductImageProviderInterface $images;
    private ProductImageRemover $remover;
    private \CPBConnect\Infrastructure\Persistence\ProductMetaRepository $metaRepository;

    public function __construct(
        ?ProductImageProviderInterface $images = null
    ) {
        $this->creator = new ProductImageCreator();

        $this->images = $images ?? new SynchronousImageProvider();

        $this->remover = new ProductImageRemover();

        $this->metaRepository =
            new \CPBConnect\Infrastructure\Persistence\ProductMetaRepository();
    }

    public function apply(
        Product $product,
        array $data,
        ?SyncOptions $options = null
    ): void {
        if ($options !== null && $options->skipsImages()) {
            return;
        }

        if (!isset($data['image'])) {
            return;
        }

        $imageValue = trim((string) $data['image']);

        if ($imageValue === '') {
            return;
        }

        $urls = ImageList::parse($imageValue);

        if ($urls === []) {
            throw new \RuntimeException(
                'The image URL is not valid.'
            );
        }

        $idProduct = (int) $product->id;

        if ($idProduct <= 0) {
            throw new \RuntimeException(
                'An image cannot be applied to a product without an ID.'
            );
        }

        $savedImageUrl =
            $this->metaRepository->find(
                $idProduct,
                'image'
            );

        if (
            $savedImageUrl !== null &&
            $savedImageUrl === $imageValue
        ) {
            return;
        }

        /*
         * Primero se descarga todo: si no se puede traer ninguna imagen
         * no se toca lo que ya tiene el producto.
         */
        $files = [];
        $failure = null;

        foreach ($urls as $url) {

            try {
                $files[] = $this->images->localFile($url);

            } catch (\Throwable $e) {
                // Un enlace roto no debe impedir el resto de imágenes.
                $failure = $failure ?? $e;
            }
        }

        if ($files === []) {
            throw $failure ?? new \RuntimeException(
                'The image could not be downloaded.'
            );
        }

        $existingImages = $product->getImages(
            (int) \Configuration::get('PS_LANG_DEFAULT')
        );

        $this->remover->removeAll(
            array_map(
                static fn (array $image): int =>
                    (int) $image['id_image'],
                $existingImages
            )
        );

        foreach ($files as $file) {
            $this->creator->create(
                $idProduct,
                $file
            );
        }

        $this->metaRepository->save(
            $idProduct,
            'image',
            $imageValue
        );
    }
}
