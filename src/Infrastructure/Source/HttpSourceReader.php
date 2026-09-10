<?php

namespace CPBConnect\Infrastructure\Source;

use RuntimeException;

class HttpSourceReader
{
    public function read(string $url): string
    {
        $this->validateUrl($url);

        $context = stream_context_create([
                                             'http' => [
                                                 'method' => 'GET',
                                                 'timeout' => 30,
                                                 'ignore_errors' => true,
                                                 'follow_location' => 0,
                                                 'header' => [
                                                     'User-Agent: CPB Sync/0.1',
                                                     'Accept: text/csv,application/csv,text/plain,*/*',
                                                 ],
                                             ],
                                         ]);

        $content = @file_get_contents(
            $url,
            false,
            $context
        );

        if ($content === false) {
            throw new RuntimeException(
                'No fue posible obtener el contenido de la fuente.'
            );
        }

        return $content;
    }

    private function validateUrl(string $url): void
    {
        $parts = parse_url($url);

        if ($parts === false) {
            throw new RuntimeException(
                'La URL de la fuente no es válida.'
            );
        }

        $scheme = strtolower($parts['scheme'] ?? '');

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException(
                'La fuente debe utilizar HTTP o HTTPS.'
            );
        }

        $host = $parts['host'] ?? '';

        if ($host === '') {
            throw new RuntimeException(
                'La URL de la fuente no contiene un host válido.'
            );
        }

        $this->validateHost($host);
    }

    private function validateHost(string $host): void
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if ($this->isBlockedIp($host)) {
                throw new RuntimeException(
                    'La URL de la fuente apunta a una dirección no permitida.'
                );
            }

            return;
        }

        $addresses = [];

        $ipv4 = gethostbynamel($host);

        if (is_array($ipv4)) {
            $addresses = array_merge($addresses, $ipv4);
        }

        $ipv6 = $this->resolveIpv6($host);

        if ($ipv6 !== null) {
            $addresses[] = $ipv6;
        }

        if (empty($addresses)) {
            throw new RuntimeException(
                'No fue posible resolver el host de la fuente.'
            );
        }

        foreach ($addresses as $address) {
            if ($this->isBlockedIp($address)) {
                throw new RuntimeException(
                    'La URL de la fuente apunta a una dirección no permitida.'
                );
            }
        }
    }

    private function resolveIpv6(string $host): ?string
    {
        $records = dns_get_record(
            $host,
            DNS_AAAA
        );

        if (empty($records)) {
            return null;
        }

        foreach ($records as $record) {
            if (!empty($record['ipv6'])) {
                return $record['ipv6'];
            }
        }

        return null;
    }

    private function isBlockedIp(string $ip): bool
    {
        if (filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) !== false) {
            return false;
        }

        return true;
    }
}