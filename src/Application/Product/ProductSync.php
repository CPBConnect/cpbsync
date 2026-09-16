<?php

namespace CPBConnect\Application\Product;

class ProductSync
{
    private ProductFinder $finder;
    private ProductCreator $creator;
    private ProductUpdater $updater;
    private ProductValidator $validator;

    private ProductChangeDetector $changeDetector;

    public function __construct()
    {
        $this->finder = new ProductFinder();
        $this->creator = new ProductCreator();
        $this->updater = new ProductUpdater();
        $this->validator = new ProductValidator();
        $this->changeDetector = new ProductChangeDetector();
    }

    public function sync(array $products): array
    {
        $result = [
            'total' => count($products),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'items' => [],
        ];

        foreach ($products as $product) {

            try {
                $validationErrors =
                    $this->validator->validate($product);

                if (!empty($validationErrors)) {
                    $result['errors']++;

                    $result['items'][] = [
                        'reference' => $product['reference'] ?? '',
                        'status' => 'error',
                        'errors' => $validationErrors,
                    ];

                    continue;
                }

                $existingId =
                    $this->finder->findByReference(
                        (string) $product['reference']
                    );

                if ($existingId !== null) {

                    $existingProduct = new \Product($existingId);

                    if (!\Validate::isLoadedObject($existingProduct)) {
                        throw new \RuntimeException(
                            'The existing product could not be loaded.'
                        );
                    }

                    $hasChanges =
                        $this->changeDetector->hasChanges(
                            $existingProduct,
                            $product
                        );

                    if (!$hasChanges) {

                        $result['skipped']++;

                        $result['items'][] = [
                            'reference' => $product['reference'],
                            'status' => 'skipped',
                            'id_product' => $existingId,
                        ];

                        continue;
                    }

                    $updated =
                        $this->updater->update(
                            $existingId,
                            $product
                        );

                    if (!$updated) {
                        throw new \RuntimeException(
                            'The product could not be updated.'
                        );
                    }

                    $result['updated']++;

                    $result['items'][] = [
                        'reference' => $product['reference'],
                        'status' => 'updated',
                        'id_product' => $existingId,
                    ];

                    continue;
                }

                $idProduct =
                    $this->creator->create($product);

                $result['created']++;

                $result['items'][] = [
                    'reference' => $product['reference'],
                    'status' => 'created',
                    'id_product' => $idProduct,
                ];

            } catch (\Throwable $e) {

                $result['errors']++;

                $result['items'][] = [
                    'reference' => $product['reference'] ?? '',
                    'status' => 'error',
                    'errors' => [
                        $e->getMessage(),
                    ],
                ];
            }
        }

        return $result;
    }
}