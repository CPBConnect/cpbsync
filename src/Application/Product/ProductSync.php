<?php

namespace CPBConnect\Application\Product;

class ProductSync
{
    private ProductCreator $creator;
    private ProductUpdater $updater;
    private ProductValidator $validator;
    private ProductStateInterface $state;
    private ProductImageProviderInterface $images;

    public function __construct(
        ?ProductStateInterface $state = null,
        ?ProductImageProviderInterface $images = null
    ) {
        $this->state = $state ?? ProductStateFactory::create();

        $this->images = $images ?? ProductImageProviderFactory::create();

        $this->creator = new ProductCreator($this->images);
        $this->updater = new ProductUpdater($this->images);
        $this->validator = new ProductValidator();
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

        /*
         * Las imágenes que van a hacer falta se preparan antes de
         * recorrer el lote, para poder descargarlas en paralelo.
         */
        $this->images->prefetch($products);

        try {
            return $this->processProducts($products, $result);
        } finally {
            $this->images->cleanup();
        }
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    private function processProducts(
        array $products,
        array $result
    ): array {
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