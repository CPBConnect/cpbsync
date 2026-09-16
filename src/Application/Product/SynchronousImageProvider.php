<?php

namespace CPBConnect\Application\Product;

/**
 * Comportamiento por defecto: cada imagen se descarga cuando se pide.
 *
 * Es exactamente lo que hacía el módulo antes de existir esta
 * abstracción.
 */
class SynchronousImageProvider implements ProductImageProviderInterface
{
    private ProductImageDownloader $downloader;

    /** @var array<int, string> */
    private array $files = [];

    public function __construct(?ProductImageDownloader $downloader = null)
    {
        $this->downloader = $downloader ?? new ProductImageDownloader();
    }

    public function prefetch(array $products): void
    {
        // Sin descarga anticipada.
    }

    public function localFile(string $imageUrl): string
    {
        $file = $this->downloader->download($imageUrl);

        $this->files[] = $file;

        return $file;
    }

    public function cleanup(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        $this->files = [];
    }
}
