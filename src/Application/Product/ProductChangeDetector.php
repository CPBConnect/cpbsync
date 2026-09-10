<?php

namespace CPBConnect\Application\Product;

use Product;
use StockAvailable;

class ProductChangeDetector
{
    private \CPBConnect\Infrastructure\Persistence\ProductMetaRepository $metaRepository;

    public function __construct()
    {
        $this->metaRepository =
            new \CPBConnect\Infrastructure\Persistence\ProductMetaRepository();
    }
    public function hasChanges(
        Product $product,
        array $data
    ): bool {
        if (isset($data['name'])) {
            $currentName =
                $product->name[
                (int) \Configuration::get('PS_LANG_DEFAULT')
                ];

            if ((string) $data['name'] !== (string) $currentName) {
                return true;
            }
        }

        if (isset($data['description'])) {
            $currentDescription =
                $product->description[
                (int) \Configuration::get('PS_LANG_DEFAULT')
                ];

            if (
                (string) $data['description'] !==
                (string) $currentDescription
            ) {
                return true;
            }
        }

        if (isset($data['price'])) {
            if (
                (float) $data['price'] !==
                (float) $product->price
            ) {
                return true;
            }
        }

        if (isset($data['quantity'])) {

            $currentQuantity = (int) StockAvailable::getQuantityAvailableByProduct(
                (int) $product->id
            );

            if (
                (int) $data['quantity'] !==
                $currentQuantity
            ) {
                return true;
            }
        }

        if (isset($data['ean13'])) {
            if (
                (string) $data['ean13'] !==
                (string) $product->ean13
            ) {
                return true;
            }
        }

        if (isset($data['manufacturer'])) {
            $manufacturerName = trim(
                (string) $data['manufacturer']
            );

            $currentManufacturer = '';

            if ((int) $product->id_manufacturer > 0) {
                $manufacturer = new \Manufacturer(
                    (int) $product->id_manufacturer
                );

                if (\Validate::isLoadedObject($manufacturer)) {
                    $currentManufacturer =
                        (string) $manufacturer->name;
                }
            }

            if ($manufacturerName !== $currentManufacturer) {
                return true;
            }
        }

        if (isset($data['category'])) {
            $categoryName = trim(
                (string) $data['category']
            );

            if ($categoryName !== '') {
                $categoryFinder = new CategoryFinder();

                $idCategory =
                    $categoryFinder->findByName(
                        $categoryName
                    );

                if ($idCategory === null) {
                    return true;
                }

                $categories = $product->getCategories();

                if (!in_array(
                    $idCategory,
                    $categories
                )) {
                    return true;
                }
            }
        }

        if (isset($data['image'])) {
            $imageUrl = trim(
                (string) $data['image']
            );

            if ($imageUrl !== '') {

                $savedImageUrl =
                    $this->metaRepository->find(
                        (int) $product->id,
                        'image'
                    );

                if ($savedImageUrl === null) {
                    return true;
                }

                if ($savedImageUrl !== $imageUrl) {
                    return true;
                }
            }
        }

        return false;
    }
}