<?php

namespace CPBConnect\Application\Product;

use Product;

class ProductImageApplier
{
    private ProductImageCreator $creator;
    private ProductImageProviderInterface $images;
    private \CPBConnect\Infrastructure\Persistence\ProductMetaRepository $metaRepository;

    public function __construct(
        ?ProductImageProviderInterface $images = null
    ) {
        $this->creator = new ProductImageCreator();

        $this->images = $images ?? new SynchronousImageProvider();

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
            $savedImageUrl === $imageUrl
        ) {
            return;
        }

        $existingImages = $product->getImages(
            (int) \Configuration::get('PS_LANG_DEFAULT')
        );

        $tmpFile = $this->images->localFile($imageUrl);

        foreach ($existingImages as $existingImage) {

            $oldImage = new \Image(
                (int) $existingImage['id_image']
            );

            if (\Validate::isLoadedObject($oldImage)) {
                $oldImage->delete();
            }
        }

        $this->creator->create(
            $idProduct,
            $tmpFile
        );

        $this->metaRepository->save(
            $idProduct,
            'image',
            $imageUrl
        );
    }
}
