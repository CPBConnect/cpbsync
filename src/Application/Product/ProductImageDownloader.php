<?php

namespace CPBConnect\Application\Product;

use RuntimeException;

class ProductImageDownloader
{
    private const MAX_FILE_SIZE = 5242880; // 5 MB

    public function download(string $url): string
    {
        $url = trim($url);

        $this->validateUrl($url);

        $context = stream_context_create([
                                             'http' => [
                                                 'method' => 'GET',
                                                 'timeout' => 30,
                                                 'ignore_errors' => true,
                                                 'follow_location' => 0,
                                                 'header' => [
                                                     'User-Agent: CPB Sync/0.1',
                                                     'Accept: image/jpeg,image/png,image/gif,image/webp,*/*',
                                                 ],
                                             ],
                                         ]);

        $content = @file_get_contents(
            $url,
            false,
            $context
        );

        if ($content === false || $content === '') {
            throw new RuntimeException(
                'The image could not be downloaded.'
            );
        }

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