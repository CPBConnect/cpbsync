<?php

namespace CPBConnect\Application\Product;

use CPBConnect\Infrastructure\Source\HttpFetcher;
use CPBConnect\Infrastructure\Source\HttpRequestException;
use RuntimeException;

class ProductImageDownloader
{
    private const MAX_FILE_SIZE = 5242880; // 5 MB

    private HttpFetcher $fetcher;

    public function __construct(?HttpFetcher $fetcher = null)
    {
        $this->fetcher = $fetcher ?? new HttpFetcher(
            fn (string $url) => $this->validateUrl($url),
            ['Accept' => 'image/jpeg,image/png,image/gif,image/webp,*/*']
        );
    }

    public function download(string $url): string
    {
        return $this->save($this->fetch(trim($url)));
    }

    /**
     * Comprueba que la URL de imagen es admisible.
     *
     * Se expone para que una descarga en paralelo pueda validar cada
     * URL antes de lanzarla, en lugar de saltarse la protección.
     */
    public function validateImageUrl(string $url): void
    {
        $this->validateUrl(trim($url));
    }

    /**
     * Valida el contenido descargado y lo guarda en un temporal.
     */
    public function save(string $content): string
    {
        if (strlen($content) > self::MAX_FILE_SIZE) {
            throw new RuntimeException(
                'The image exceeds the maximum allowed size of 5 MB.'
            );
        }

        $this->validateImageContent($content);

        $tmpFile = tempnam(
            _PS_TMP_IMG_DIR_,
            'cpbsync_'
        );

        if ($tmpFile === false) {
            throw new RuntimeException(
                'The temporary file could not be created.'
            );
        }

        $bytesWritten = file_put_contents(
            $tmpFile,
            $content
        );

        if ($bytesWritten === false) {
            @unlink($tmpFile);

            throw new RuntimeException(
                'The temporary image could not be saved.'
            );
        }

        return $tmpFile;
    }

    /**
     * Obtiene la imagen por HTTP, siguiendo redirecciones.
     */
    private function fetch(string $url): string
    {
        try {
            return $this->fetcher->request($url);
        } catch (HttpRequestException $e) {
            throw new RuntimeException(
                'The image could not be downloaded.'
            );
        }
    }

    private function validateUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException(
                'The image URL is not valid.'
            );
        }

        $parts = parse_url($url);

        if ($parts === false) {
            throw new RuntimeException(
                'The image URL is not valid.'
            );
        }

        $scheme = strtolower($parts['scheme'] ?? '');

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException(
                'The image must use HTTP or HTTPS.'
            );
        }

        $host = $parts['host'] ?? '';

        if ($host === '') {
            throw new RuntimeException(
                'The image URL does not contain a valid host.'
            );
        }

        $this->validateHost($host);
    }

    private function validateHost(string $host): void
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if ($this->isBlockedIp($host)) {
                throw new RuntimeException(
                    'The image URL points to a disallowed address.'
                );
            }

            return;
        }

        $addresses = [];

        $ipv4 = gethostbynamel($host);

        if (is_array($ipv4)) {
            $addresses = array_merge(
                $addresses,
                $ipv4
            );
        }

        $records = dns_get_record(
            $host,
            DNS_AAAA
        );

        if (is_array($records)) {
            foreach ($records as $record) {
                if (!empty($record['ipv6'])) {
                    $addresses[] = $record['ipv6'];
                }
            }
        }

        if (empty($addresses)) {
            throw new RuntimeException(
                'The image host could not be resolved.'
            );
        }

        foreach ($addresses as $address) {
            if ($this->isBlockedIp($address)) {
                throw new RuntimeException(
                    'The image URL points to a disallowed address.'
                );
            }
        }
    }

    private function isBlockedIp(string $ip): bool
    {
        return filter_var(
                   $ip,
                   FILTER_VALIDATE_IP,
                   FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
               ) === false;
    }

    private function validateImageContent(string $content): void
    {
        $imageInfo = @getimagesizefromstring($content);

        if ($imageInfo === false) {
            throw new RuntimeException(
                'The downloaded content is not a valid image.'
            );
        }

        $mimeType = $imageInfo['mime'] ?? '';

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new RuntimeException(
                'The image format is not allowed.'
            );
        }
    }
}