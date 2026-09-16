<?php

namespace CPBConnect\Application\Product;

class ProductSync
{
    private ProductCreator $creator;
    private ProductUpdater $updater;
    private ProductValidator $validator;
    private ProductStateInterface $state;

    public function __construct(?ProductStateInterface $state = null)
    {
        $this->creator = new ProductCreator();
        $this->updater = new ProductUpdater();
        $this->validator = new ProductValidator();
        $this->state = $state ?? ProductStateFactory::create();
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

        $this->state->prepare($products);

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

                $existingId = $this->state->findExistingId(
                    (string) $product['reference']
                );

                if ($existingId !== null) {

                    if (!$this->state->hasChanges($existingId, $product)) {

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

                    $this->state->remember($product, $existingId);

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

                $this->state->remember($product, $idProduct);

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